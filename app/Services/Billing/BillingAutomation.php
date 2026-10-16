<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\WaTemplate;
use App\Services\WhatsAppGateway;
use App\Support\Phone;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Billing otomatis per tenant:
 *  1. Generate tagihan bulan berjalan (H- sekian hari sebelum jatuh tempo), sekali sehari
 *     setelah jam yang diatur.
 *  2. Pengingat WA tagihan lewat jatuh tempo (maks. N kali, sekali per hari).
 *  3. Pengingat WA janji bayar (sekali).
 *
 * Semua query berjalan di dalam TenantContext tenant tersebut, dan WA dikirim lewat
 * WhatsAppGateway milik tenant itu.
 */
class BillingAutomation
{
    private const LAST_RUN_KEY = 'billing_auto_generate_last_run_date';

    /** @var callable|null */
    private $output = null;

    public function __construct(
        private readonly InvoiceGenerator $generator,
        private readonly TenantContext $tenants,
    ) {
    }

    public function withOutput(?callable $output): static
    {
        $this->output = $output;

        return $this;
    }

    public function run(Tenant $tenant, Carbon $now, bool $force = false, bool $ignoreWindow = false): void
    {
        $this->tenants->run((int) $tenant->id, function () use ($tenant, $now, $force, $ignoreWindow) {
            $settings = Setting::query()->pluck('value', 'key')->all();
            $gateway = app(WhatsAppGateway::class);

            if ($this->truthy($settings['billing_auto_generate_enabled'] ?? false)) {
                $runTime = substr((string) ($settings['billing_auto_generate_time'] ?? '02:00'), 0, 5) ?: '02:00';
                $alreadyRanToday = ($settings[self::LAST_RUN_KEY] ?? '') === $now->toDateString();

                if ($force || (!$alreadyRanToday && $now->format('H:i') >= $runTime)) {
                    $this->generate($tenant, $settings, $gateway, $now, $ignoreWindow);
                } else {
                    $this->say("Tenant {$tenant->id}: generate menunggu jam {$runTime}" . ($alreadyRanToday ? ' besok.' : '.'));
                }
            } else {
                $this->say("Tenant {$tenant->id}: generate otomatis belum aktif.");
            }

            if ($this->truthy($settings['billing_overdue_wa_enabled'] ?? false)) {
                $this->overdueReminders($tenant, $settings, $gateway, $now);
            } else {
                $this->say("Tenant {$tenant->id}: reminder jatuh tempo belum aktif.");
            }

            $this->paymentPromiseReminders($tenant, $settings, $gateway, $now);
        });
    }

    private function generate(Tenant $tenant, array $settings, WhatsAppGateway $gateway, Carbon $now, bool $ignoreWindow): void
    {
        $daysBeforeDue = (int) ($settings['billing_auto_generate_days_before_due'] ?? 3);
        $sendWa = $this->truthy($settings['billing_auto_generate_send_wa'] ?? false) && $gateway->hasToken();
        $groupSamePhone = $this->truthy($settings['billing_auto_generate_group_same_phone'] ?? true);
        $templates = $this->templates($settings['billing_auto_generate_wa_template_ids'] ?? null, $settings['billing_auto_generate_wa_template_id'] ?? null);
        [$delayMin, $delayMax] = $this->delays($settings);

        $counts = ['created' => 0, 'wa_sent' => 0, 'not_yet_due' => 0, 'not_started' => 0, 'exists' => 0, 'no_package' => 0];
        $messages = [];
        $today = $now->copy()->startOfDay();

        foreach ($this->generator->billableCustomers()->get() as $customer) {
            $dueDate = DueDate::for($customer, $now->month, $now->year);

            if (!$ignoreWindow && $today->lt($dueDate->copy()->subDays($daysBeforeDue))) {
                $counts['not_yet_due']++;
                continue;
            }

            [$result, $invoice] = $this->generator->generate($customer, $now->month, $now->year, $dueDate->copy()->endOfDay());

            if ($result !== InvoiceGenerator::CREATED) {
                $counts[$result]++;
                continue;
            }

            $counts['created']++;

            $phone = Phone::normalize($customer->phone);
            if (!$sendWa || !$phone) {
                continue;
            }

            $template = $this->pickTemplate($templates, $invoice, $customer)?->content;
            $key = $groupSamePhone ? $phone : $phone . '#' . $invoice->id;
            $messages[$key][] = compact('customer', 'invoice', 'dueDate', 'template');
        }

        foreach ($messages as $key => $items) {
            $phone = explode('#', $key)[0];
            $message = count($items) > 1
                ? BillingMessages::grouped($items)
                : BillingMessages::invoice($items[0]['template'], $items[0]['customer'], $items[0]['invoice'], $items[0]['dueDate']);

            if ($gateway->send($phone, $message)['success'] ?? false) {
                $counts['wa_sent']++;
            }

            $this->pause($delayMin, $delayMax);
        }

        Setting::set(self::LAST_RUN_KEY, $now->toDateString());

        $this->say("Tenant {$tenant->id}: {$counts['created']} tagihan dibuat, {$counts['wa_sent']} WA dikirim.");
        $this->say("Dilewati: {$counts['exists']} sudah ada, {$counts['not_yet_due']} belum masuk H-, {$counts['not_started']} sebelum tanggal pasang, {$counts['no_package']} tanpa paket.");
        Log::info('Billing automation generated invoices.', ['tenant_id' => $tenant->id] + $counts);
    }

    private function overdueReminders(Tenant $tenant, array $settings, WhatsAppGateway $gateway, Carbon $now): void
    {
        if (!$gateway->hasToken()) {
            $this->say("Tenant {$tenant->id}: reminder jatuh tempo dilewati, token WA kosong.");
            return;
        }

        $daysAfterDue = (int) ($settings['billing_overdue_days_after_due'] ?? 1);
        $maxReminders = (int) ($settings['billing_overdue_max_reminders'] ?? 3);
        $template = $this->template($settings['billing_overdue_wa_template_id'] ?? null)?->content;
        [$delayMin, $delayMax] = $this->delays($settings);
        $sent = 0;

        $invoices = Invoice::query()
            ->with('customer.odp')
            ->where('status', 'unpaid')
            ->where(fn ($q) => $q->whereNull('overdue_reminder_count')->orWhere('overdue_reminder_count', '<', $maxReminders))
            ->get();

        foreach ($invoices as $invoice) {
            $customer = $invoice->customer;

            if (!$customer || !$customer->phone || !$customer->isBillable()) {
                continue;
            }

            if ($invoice->last_overdue_reminder_sent_at?->isSameDay($now)) {
                continue;
            }

            $dueDate = DueDate::for($customer, (int) $invoice->month, (int) $invoice->year);
            if ($now->copy()->startOfDay()->lt($dueDate->copy()->addDays($daysAfterDue))) {
                continue;
            }

            $message = BillingMessages::invoice($template, $customer, $invoice, $dueDate, BillingMessages::DEFAULT_OVERDUE);

            if ($gateway->send($customer->phone, $message)['success'] ?? false) {
                $invoice->forceFill([
                    'overdue_reminder_count' => (int) $invoice->overdue_reminder_count + 1,
                    'last_overdue_reminder_sent_at' => $now,
                ])->save();
                $sent++;
                $this->pause($delayMin, $delayMax);
            }
        }

        $this->say("Tenant {$tenant->id}: {$sent} reminder jatuh tempo dikirim.");
        Log::info('Billing automation sent overdue reminders.', ['tenant_id' => $tenant->id, 'sent' => $sent]);
    }

    private function paymentPromiseReminders(Tenant $tenant, array $settings, WhatsAppGateway $gateway, Carbon $now): void
    {
        if (!$gateway->hasToken()) {
            return;
        }

        $sent = 0;
        $failed = 0;

        $invoices = Invoice::query()
            ->with('customer.odp')
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

            $template = $this->template($invoice->payment_promise_wa_template_id ?: ($settings['billing_payment_promise_wa_template_id'] ?? null))?->content;
            $dueDate = DueDate::for($customer, (int) $invoice->month, (int) $invoice->year);
            $message = BillingMessages::paymentPromise($template, $customer, $invoice, $dueDate);

            if ($gateway->send($customer->phone, $message)['success'] ?? false) {
                $invoice->forceFill(['payment_promise_wa_sent_at' => $now])->save();
                $sent++;
            } else {
                $failed++;
            }
        }

        if ($sent || $failed) {
            $this->say("Tenant {$tenant->id}: {$sent} WA janji bayar dikirim, {$failed} gagal.");
        }
        Log::info('Billing automation sent payment promise reminders.', ['tenant_id' => $tenant->id, 'sent' => $sent, 'failed' => $failed]);
    }

    private function template($id): ?WaTemplate
    {
        return $id ? WaTemplate::find((int) $id) : null;
    }

    private function templates($ids, $fallbackId): Collection
    {
        $decoded = is_array($ids) ? $ids : json_decode((string) $ids, true);
        $ids = collect(is_array($decoded) ? $decoded : [])->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty() && $fallbackId) {
            $ids = collect([(int) $fallbackId]);
        }

        if ($ids->isEmpty()) {
            return collect();
        }

        return WaTemplate::whereIn('id', $ids->all())->get()
            ->sortBy(fn (WaTemplate $template) => $ids->search((int) $template->id))
            ->values();
    }

    /**
     * Beberapa template dipakai bergantian (stabil per tagihan) agar pesan tidak seragam.
     */
    private function pickTemplate(Collection $templates, Invoice $invoice, $customer): ?WaTemplate
    {
        if ($templates->isEmpty()) {
            return null;
        }

        $index = abs(crc32($invoice->id . '-' . $customer->id . '-' . $invoice->month . '-' . $invoice->year)) % $templates->count();

        return $templates->get($index);
    }

    private function delays(array $settings): array
    {
        $min = max(0, (int) ($settings['billing_auto_generate_wa_delay_min'] ?? 5));

        return [$min, max($min, (int) ($settings['billing_auto_generate_wa_delay_max'] ?? 20))];
    }

    /**
     * Jeda acak antar pesan WA agar tidak terdeteksi spam oleh WhatsApp.
     */
    protected function pause(int $min, int $max): void
    {
        $seconds = random_int($min, $max);

        if ($seconds > 0) {
            $this->say("Jeda kirim WA {$seconds} detik...");
            sleep($seconds);
        }
    }

    private function say(string $line): void
    {
        if ($this->output) {
            ($this->output)($line);
        }
    }

    private function truthy($value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
    }
}
