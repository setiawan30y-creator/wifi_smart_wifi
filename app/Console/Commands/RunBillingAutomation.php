<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\WaTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RunBillingAutomation extends Command
{
    protected $signature = 'billing:automation
        {--force : Jalankan tanpa menunggu jam otomatis}
        {--ignore-window : Abaikan rentang H- jatuh tempo untuk test/manual}
        {--tenant= : Jalankan hanya untuk tenant tertentu}';

    protected $description = 'Generate tagihan otomatis dan kirim pengingat WA jatuh tempo.';

    public function handle(): int
    {
        $now = now();
        $tenantQuery = Tenant::where('status', 'active');
        if ($this->option('tenant')) {
            $tenantQuery->where('id', (int) $this->option('tenant'));
        }

        $tenants = $tenantQuery->get();

        if ($tenants->isEmpty()) {
            $this->warn('Tidak ada tenant aktif yang cocok untuk otomatis tagihan.');
            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $settings = $this->settingsForTenant((int) $tenant->id);
            $runTime = substr((string) ($settings['billing_auto_generate_time'] ?? '02:00'), 0, 5) ?: '02:00';

            $isGenerateTime = $this->option('force') || $now->format('H:i') === $runTime;

            // Generate invoice mengikuti jam yang disimpan. Reminder H+ tetap
            // diperiksa setiap siklus scheduler, karena tanggal jatuh tempo
            // bisa tercapai setelah jam generate harian.
            if ($this->truthy($settings['billing_auto_generate_enabled'] ?? false) && $isGenerateTime) {
                $this->runAutoGenerate((int) $tenant->id, $settings, $now);
            } elseif ($this->truthy($settings['billing_auto_generate_enabled'] ?? false)) {
                $this->line("Tenant {$tenant->id}: generate menunggu jam {$runTime}.");
            } else {
                $this->line("Tenant {$tenant->id}: generate otomatis belum aktif.");
            }

            if ($this->truthy($settings['billing_overdue_wa_enabled'] ?? false)) {
                $this->runOverdueReminder((int) $tenant->id, $settings, $now);
            } else {
                $this->line("Tenant {$tenant->id}: reminder jatuh tempo belum aktif.");
            }

            $this->runPaymentPromiseReminder((int) $tenant->id, $settings, $now);
        }

        return self::SUCCESS;
    }

    private function runAutoGenerate(int $tenantId, array $settings, Carbon $now): void
    {
        $lastRunKey = 'billing_auto_generate_last_run_date';
        if (($settings[$lastRunKey] ?? '') === $now->toDateString() && !$this->option('force')) {
            return;
        }

        $daysBeforeDue = (int) ($settings['billing_auto_generate_days_before_due'] ?? 3);
        $sendWa = $this->truthy($settings['billing_auto_generate_send_wa'] ?? false);
        $templates = $this->templatesForTenant(
            $tenantId,
            $settings['billing_auto_generate_wa_template_ids'] ?? null,
            $settings['billing_auto_generate_wa_template_id'] ?? null
        );
        $delayMin = max(0, (int) ($settings['billing_auto_generate_wa_delay_min'] ?? 5));
        $delayMax = max($delayMin, (int) ($settings['billing_auto_generate_wa_delay_max'] ?? 20));
        $groupSamePhone = $this->truthy($settings['billing_auto_generate_group_same_phone'] ?? true);
        $token = $settings['fonnte_token'] ?? null;
        $created = 0;
        $waSent = 0;
        $pendingWaGroups = [];
        $skippedNotYetDue = 0;
        $skippedBeforeInstall = 0;
        $skippedExists = 0;
        $skippedNoPackage = 0;
        $skippedPrepaid = 0;

        $customers = Customer::withoutGlobalScopes()
            ->with('package')
            ->where('tenant_id', $tenantId)
            ->whereRaw('LOWER(TRIM(status)) = ?', ['active'])
            ->where(function ($query) {
                $query->whereNull('billing_type')
                    ->orWhere('billing_type', Customer::BILLING_PAID);
            })
            ->get();

        foreach ($customers as $customer) {
            if ($customer->isPrepaid()) {
                $skippedPrepaid++;
                continue;
            }

            if (!$customer->package) {
                $skippedNoPackage++;
                continue;
            }

            $dueDate = $this->nextDueDateFor($customer, $now, $daysBeforeDue);
            $startDate = $dueDate->copy()->subDays($daysBeforeDue);
            $isCurrentPeriod = $dueDate->isSameMonth($now);

            if (!$this->option('ignore-window') && ($now->copy()->startOfDay()->lt($startDate->startOfDay()) || (!$isCurrentPeriod && $now->copy()->startOfDay()->gt($dueDate->copy()->startOfDay())))) {
                $skippedNotYetDue++;
                continue;
            }

            $billingStartDate = $customer->tanggal_pasang
                ? Carbon::parse($customer->tanggal_pasang)->startOfDay()
                : ($customer->created_at ? $customer->created_at->copy()->startOfDay() : null);

            if ($billingStartDate && $billingStartDate->gt($dueDate->copy()->endOfDay())) {
                $skippedBeforeInstall++;
                continue;
            }

            $exists = Invoice::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('customer_id', $customer->id)
                ->where('month', $dueDate->month)
                ->where('year', $dueDate->year)
                ->exists();

            if ($exists) {
                $skippedExists++;
                continue;
            }

            $invoice = Invoice::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId,
                'customer_id' => $customer->id,
                'month' => $dueDate->month,
                'year' => $dueDate->year,
                'amount' => $customer->package->price,
                'status' => 'unpaid',
            ]);

            $created++;

            if ($sendWa && $token && $customer->phone) {
                $template = $this->templateForInvoice($templates, $invoice, $customer);
                $message = $this->renderTemplate(
                    $template?->content,
                    $customer,
                    $invoice,
                    $dueDate,
                    $this->defaultGeneratedMessage()
                );

                if ($groupSamePhone) {
                    $phone = $this->normalizePhone($customer->phone);

                    if ($phone) {
                        $pendingWaGroups[$phone][] = compact('customer', 'invoice', 'dueDate', 'message', 'template');
                    }
                } else {
                    if ($this->sendWa($token, $customer->phone, $message)) {
                        $waSent++;
                    }

                    $this->sleepRandomDelay($delayMin, $delayMax);
                }
            }
        }

        if ($sendWa && $token && $groupSamePhone) {
            foreach ($pendingWaGroups as $phone => $items) {
                $message = count($items) > 1
                    ? $this->renderGroupedGeneratedMessage($items)
                    : $items[0]['message'];

                if ($this->sendWa($token, $phone, $message)) {
                    $waSent++;
                }

                $this->sleepRandomDelay($delayMin, $delayMax);
            }
        }

        $this->setTenantSetting($tenantId, $lastRunKey, $now->toDateString());
        $summary = compact('tenantId', 'created', 'waSent', 'skippedNotYetDue', 'skippedBeforeInstall', 'skippedExists', 'skippedNoPackage', 'skippedPrepaid');
        $this->info("Tenant {$tenantId}: {$created} tagihan dibuat, {$waSent} WA dikirim.");
        $this->line("Dilewati: {$skippedExists} sudah ada, {$skippedNotYetDue} belum masuk H-, {$skippedBeforeInstall} sebelum tanggal pasang, {$skippedNoPackage} tanpa paket, {$skippedPrepaid} prabayar.");
        Log::info('Billing automation generated invoices.', $summary);
    }

    private function runOverdueReminder(int $tenantId, array $settings, Carbon $now): void
    {
        $daysAfterDue = (int) ($settings['billing_overdue_days_after_due'] ?? 1);
        $maxReminders = (int) ($settings['billing_overdue_max_reminders'] ?? 3);
        $delayMin = max(0, (int) ($settings['billing_auto_generate_wa_delay_min'] ?? 5));
        $delayMax = max($delayMin, (int) ($settings['billing_auto_generate_wa_delay_max'] ?? 20));
        $template = $this->templateForTenant($tenantId, $settings['billing_overdue_wa_template_id'] ?? null);
        $token = $settings['fonnte_token'] ?? null;
        $sent = 0;

        if (!$token) {
            Log::warning('Billing overdue reminder skipped because Fonnte token is empty.', compact('tenantId'));
            return;
        }

        $invoices = Invoice::withoutGlobalScopes()
            ->with('customer.package')
            ->where('tenant_id', $tenantId)
            ->where('status', 'unpaid')
            ->where(function ($query) use ($maxReminders) {
                $query->whereNull('overdue_reminder_count')
                    ->orWhere('overdue_reminder_count', '<', $maxReminders);
            })
            ->get();

        foreach ($invoices as $invoice) {
            $customer = $invoice->customer;
            if (!$customer || !$customer->phone || !$customer->isBillable()) {
                continue;
            }

            if ($invoice->last_overdue_reminder_sent_at && $invoice->last_overdue_reminder_sent_at->isSameDay($now)) {
                continue;
            }

            $dueDate = $this->dueDateFor($customer, (int) $invoice->month, (int) $invoice->year);
            if ($now->copy()->startOfDay()->lt($dueDate->copy()->addDays($daysAfterDue)->startOfDay())) {
                continue;
            }

            $message = $this->renderTemplate(
                $template?->content,
                $customer,
                $invoice,
                $dueDate,
                $this->defaultOverdueMessage()
            );

            if ($this->sendWa($token, $customer->phone, $message)) {
                $invoice->forceFill([
                    'overdue_reminder_count' => ((int) $invoice->overdue_reminder_count) + 1,
                    'last_overdue_reminder_sent_at' => $now,
                ])->save();
                $sent++;
                $this->sleepRandomDelay($delayMin, $delayMax);
            }
        }

        $this->info("Tenant {$tenantId}: {$sent} reminder jatuh tempo dikirim.");
        Log::info('Billing automation sent overdue reminders.', compact('tenantId', 'sent'));
    }

    private function runPaymentPromiseReminder(int $tenantId, array $settings, Carbon $now): void
    {
        if (!Schema::hasColumn('invoices', 'payment_promise_date') || !Schema::hasColumn('invoices', 'payment_promise_wa_sent_at')) {
            $this->line("Tenant {$tenantId}: kolom janji bayar belum tersedia. Jalankan migration dulu.");
            return;
        }

        $token = $settings['fonnte_token'] ?? null;
        $sent = 0;
        $failed = 0;

        if (!$token) {
            $this->line("Tenant {$tenantId}: WA janji bayar dilewati karena token WA Gateway kosong.");
            Log::warning('Payment promise reminder skipped because Fonnte token is empty.', compact('tenantId'));
            return;
        }

        $invoices = Invoice::withoutGlobalScopes()
            ->with('customer.package', 'customer.odp')
            ->where('tenant_id', $tenantId)
            ->where('status', 'unpaid')
            ->whereNotNull('payment_promise_date')
            ->whereNull('payment_promise_wa_sent_at')
            ->whereDate('payment_promise_date', '<=', $now->toDateString())
            ->get();

        foreach ($invoices as $invoice) {
            $customer = $invoice->customer;

            if (!$customer || !$customer->phone || !$customer->isBillable()) {
                continue;
            }

            $dueDate = $this->dueDateFor($customer, (int) $invoice->month, (int) $invoice->year);
            $template = $this->templateForTenant($tenantId, $invoice->payment_promise_wa_template_id ?: ($settings['billing_payment_promise_wa_template_id'] ?? null));
            $message = $this->renderPaymentPromiseTemplate(
                $template?->content,
                $customer,
                $invoice,
                $dueDate,
                Carbon::parse($invoice->payment_promise_date),
                $this->defaultPaymentPromiseMessage()
            );

            if ($this->sendWa($token, $customer->phone, $message)) {
                $invoice->forceFill([
                    'payment_promise_wa_sent_at' => $now,
                ])->save();
                $sent++;
            } else {
                $failed++;
            }
        }

        $this->info("Tenant {$tenantId}: {$sent} WA janji bayar dikirim.");
        Log::info('Billing automation sent payment promise reminders.', compact('tenantId', 'sent', 'failed'));
    }

    private function settingsForTenant(int $tenantId): array
    {
        return Setting::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->pluck('value', 'key')
            ->toArray();
    }

    private function setTenantSetting(int $tenantId, string $key, string $value): void
    {
        Setting::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['value' => $value]
        );
    }

    private function templateForTenant(int $tenantId, $templateId): ?WaTemplate
    {
        if (!$templateId) {
            return null;
        }

        return WaTemplate::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('id', $templateId)
            ->first();
    }

    private function templatesForTenant(int $tenantId, $templateIds, $fallbackTemplateId = null)
    {
        $ids = collect($this->decodeTemplateIds($templateIds));

        if ($ids->isEmpty() && $fallbackTemplateId) {
            $ids = collect([(int) $fallbackTemplateId]);
        }

        if ($ids->isEmpty()) {
            return collect();
        }

        return WaTemplate::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids->all())
            ->get()
            ->sortBy(fn (WaTemplate $template) => $ids->search((int) $template->id))
            ->values();
    }

    private function decodeTemplateIds($templateIds): array
    {
        if (is_array($templateIds)) {
            $decoded = $templateIds;
        } else {
            $decoded = json_decode((string) $templateIds, true);
        }

        return collect(is_array($decoded) ? $decoded : [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function templateForInvoice($templates, Invoice $invoice, Customer $customer): ?WaTemplate
    {
        if ($templates->isEmpty()) {
            return null;
        }

        $index = abs(crc32($invoice->id . '-' . $customer->id . '-' . $invoice->month . '-' . $invoice->year)) % $templates->count();

        return $templates->values()->get($index);
    }

    private function sleepRandomDelay(int $minSeconds, int $maxSeconds): void
    {
        $delay = random_int($minSeconds, $maxSeconds);

        if ($delay <= 0) {
            return;
        }

        $this->line("Jeda kirim WA {$delay} detik...");
        sleep($delay);
    }

    private function dueDateFor(Customer $customer, int $month, int $year): Carbon
    {
        $date = Carbon::create($year, $month, 1);
        $billingDay = max(1, min((int) ($customer->billing_day ?: 1), $date->daysInMonth));

        return $date->day($billingDay)->startOfDay();
    }

    private function nextDueDateFor(Customer $customer, Carbon $now, int $daysBeforeDue): Carbon
    {
        // Billing periods follow the calendar month. Even when the billing
        // day has passed, generate the current month if it is still missing;
        // otherwise the automation can jump from September straight to October.
        return $this->dueDateFor($customer, $now->month, $now->year);
    }

    private function renderGroupedGeneratedMessage(array $items): string
    {
        $first = $items[0];
        $templateContent = trim((string) ($first['template']?->content ?? ''));
        $names = collect($items)->pluck('customer.name')->filter()->unique()->values()->all();
        $invoiceNumbers = collect($items)->pluck('invoice.invoice_number')->filter()->values()->all();
        $periods = collect($items)
            ->map(fn ($item) => $item['dueDate']->translatedFormat('F Y'))
            ->unique()
            ->values()
            ->all();
        $dueDates = collect($items)
            ->map(fn ($item) => $item['dueDate']->translatedFormat('d F Y'))
            ->unique()
            ->values()
            ->all();
        $total = collect($items)->sum(fn ($item) => (int) $item['invoice']->amount);
        $detailLines = collect($items)->map(function ($item) {
            $customer = $item['customer'];
            $invoice = $item['invoice'];
            $dueDate = $item['dueDate'];

            return "- {$customer->name} | {$dueDate->translatedFormat('F Y')} ({$invoice->invoice_number}) | Rp "
                . number_format((int) $invoice->amount, 0, ',', '.')
                . " | JT {$dueDate->translatedFormat('d F Y')}";
        })->implode("\n");

        if ($templateContent !== '') {
            return strtr($templateContent, [
                '{nama}' => implode(', ', $names),
                '{nama_pelanggan}' => implode(', ', $names),
                '{no_invoice}' => implode(', ', $invoiceNumbers),
                '{nomor_tagihan}' => implode(', ', $invoiceNumbers),
                '{bulan}' => implode(', ', $periods),
                '{periode}' => implode(', ', $periods),
                '{jumlah}' => 'Rp ' . number_format($total, 0, ',', '.'),
                '{nominal}' => 'Rp ' . number_format($total, 0, ',', '.'),
                '{tanggal_jatuh_tempo}' => implode(', ', $dueDates),
                '{rincian_tagihan}' => $detailLines,
                '{jumlah_pelanggan}' => (string) count($items),
            ]);
        }

        return "Halo,\n\n"
            . "Tagihan internet untuk " . count($items) . " pelanggan pada nomor WhatsApp ini telah terbit.\n\n"
            . "Rincian tagihan:\n{$detailLines}\n\n"
            . "Total tagihan: Rp " . number_format($total, 0, ',', '.') . "\n\n"
            . "Mohon melakukan pembayaran sesuai rincian di atas. Terima kasih.";
    }

    private function renderTemplate(?string $content, Customer $customer, Invoice $invoice, Carbon $dueDate, string $fallback): string
    {
        $monthName = $dueDate->translatedFormat('F');
        $message = trim((string) $content) !== '' ? (string) $content : $fallback;

        return strtr($message, [
            '{nama}' => $customer->name,
            '{nama_pelanggan}' => $customer->name,
            '{no_invoice}' => $invoice->invoice_number,
            '{nomor_tagihan}' => $invoice->invoice_number,
            '{bulan}' => $monthName,
            '{tahun}' => (string) $invoice->year,
            '{periode}' => $monthName . ' ' . $invoice->year,
            '{jumlah}' => 'Rp ' . number_format((int) $invoice->amount, 0, ',', '.'),
            '{nominal}' => 'Rp ' . number_format((int) $invoice->amount, 0, ',', '.'),
            '{tanggal_jatuh_tempo}' => $dueDate->translatedFormat('d F Y'),
            '{odp}' => $customer->odp->name ?? '-',
            '{nama_odp}' => $customer->odp->name ?? '-',
        ]);
    }

    private function renderPaymentPromiseTemplate(?string $content, Customer $customer, Invoice $invoice, Carbon $dueDate, Carbon $promiseDate, string $fallback): string
    {
        $message = trim((string) $content) !== '' ? (string) $content : $fallback;
        $monthName = $dueDate->translatedFormat('F');

        return strtr($message, [
            '{nama}' => $customer->name,
            '{nama_pelanggan}' => $customer->name,
            '{no_invoice}' => $invoice->invoice_number,
            '{nomor_tagihan}' => $invoice->invoice_number,
            '{bulan}' => $monthName,
            '{tahun}' => (string) $invoice->year,
            '{periode}' => $monthName . ' ' . $invoice->year,
            '{jumlah}' => 'Rp ' . number_format((int) $invoice->amount, 0, ',', '.'),
            '{nominal}' => 'Rp ' . number_format((int) $invoice->amount, 0, ',', '.'),
            '{tanggal_jatuh_tempo}' => $dueDate->translatedFormat('d F Y'),
            '{tanggal_janji_bayar}' => $promiseDate->translatedFormat('d F Y'),
            '{catatan_janji_bayar}' => $invoice->payment_promise_note ?: '-',
            '{odp}' => $customer->odp->name ?? '-',
            '{nama_odp}' => $customer->odp->name ?? '-',
        ]);
    }

    private function sendWa(string $token, string $phone, string $message): bool
    {
        $target = $this->normalizePhone($phone);

        if (!$target) {
            return false;
        }

        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->timeout(20)
                ->post('https://api.fonnte.com/send', [
                    'target' => $target,
                    'message' => $message,
                    'countryCode' => '62',
                ]);

            $result = $response->json();

            return $response->successful() && (bool) ($result['status'] ?? false);
        } catch (\Throwable $e) {
            Log::error('Billing automation failed to send WA.', [
                'phone' => $target,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function normalizePhone(?string $phone): ?string
    {
        $number = preg_replace('/[^0-9]/', '', (string) $phone);

        if ($number === '') {
            return null;
        }

        if (str_starts_with($number, '0')) {
            return '62' . substr($number, 1);
        }

        if (str_starts_with($number, '8')) {
            return '62' . $number;
        }

        return $number;
    }

    private function truthy($value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
    }

    private function defaultGeneratedMessage(): string
    {
        return "Halo {nama_pelanggan},\n\nTagihan internet periode {periode} telah terbit.\nNo. Invoice: {nomor_tagihan}\nJumlah: {nominal}\nJatuh tempo: {tanggal_jatuh_tempo}\n\nTerima kasih.";
    }

    private function defaultOverdueMessage(): string
    {
        return "Halo {nama_pelanggan},\n\nKami informasikan tagihan internet periode {periode} masih belum lunas.\nNo. Invoice: {nomor_tagihan}\nJumlah: {nominal}\nJatuh tempo: {tanggal_jatuh_tempo}\n\nMohon segera melakukan pembayaran. Terima kasih.";
    }

    private function defaultPaymentPromiseMessage(): string
    {
        return "Halo {nama_pelanggan},\n\nKami ingatkan kembali janji pembayaran internet Anda pada tanggal {tanggal_janji_bayar}.\n\nNo. Invoice: {nomor_tagihan}\nPeriode: {periode}\nNominal: {nominal}\nCatatan: {catatan_janji_bayar}\n\nMohon melakukan pembayaran sesuai janji. Jika sudah membayar, abaikan pesan ini. Terima kasih.";
    }
}
