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
 *  2. Pengingat WA tagihan belum lunas: sebelum jatuh tempo (H-) dan sesudahnya (H+),
 *     sesuai ReminderSchedule, mulai jam pengingat yang diatur.
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

            $this->reminders($tenant, $settings, $gateway, $now);

            $this->paymentPromiseReminders($tenant, $settings, $gateway, $now);
        });
    }

    /**
     * Ringkasan apa yang akan dikerjakan otomatis hari ini, TANPA membuat tagihan
     * atau mengirim WA. Dipakai halaman Pengaturan Otomatis Tagihan.
     *
     * @return array{wa_ready: bool, generate: array, reminders: array, schedule: ReminderSchedule}
     */
    public function preview(Tenant $tenant, Carbon $now): array
    {
        return $this->tenants->run((int) $tenant->id, function () use ($now) {
            $settings = Setting::query()->pluck('value', 'key')->all();
            $gateway = app(WhatsAppGateway::class);
            $schedule = ReminderSchedule::fromSettings($settings);
            $daysBeforeDue = (int) ($settings['billing_auto_generate_days_before_due'] ?? 3);
            $today = $now->copy()->startOfDay();

            $toCreate = ['count' => 0, 'amount' => 0];
            foreach ($this->generator->billableCustomers()->get() as $customer) {
                $dueDate = DueDate::for($customer, $now->month, $now->year);
                $start = DueDate::billingStart($customer);

                if (!$customer->package
                    || $today->lt($dueDate->copy()->subDays($daysBeforeDue))
                    || ($start && $start->gt($dueDate->copy()->endOfDay()))
                    || $this->generator->exists($customer, $now->month, $now->year)) {
                    continue;
                }

                $toCreate['count']++;
                $toCreate['amount'] += (int) $customer->package->price;
            }

            $generateSendsWa = $this->truthy($settings['billing_auto_generate_enabled'] ?? false)
                && $this->truthy($settings['billing_auto_generate_send_wa'] ?? false);

            $reminders = ($schedule->beforeEnabled || $schedule->afterEnabled)
                ? (new InvoiceReminderSender($gateway, $schedule, $generateSendsWa))->preview($now)
                : [];

            return [
                'wa_ready' => $gateway->hasToken(),
                'generate' => [
                    'enabled' => $this->truthy($settings['billing_auto_generate_enabled'] ?? false),
                    'time' => substr((string) ($settings['billing_auto_generate_time'] ?? '02:00'), 0, 5) ?: '02:00',
                    'ran_today' => ($settings[self::LAST_RUN_KEY] ?? '') === $now->toDateString(),
                ] + $toCreate,
                'reminders' => $reminders,
                'schedule' => $schedule,
            ];
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

    private function reminders(Tenant $tenant, array $settings, WhatsAppGateway $gateway, Carbon $now): void
    {
        $schedule = ReminderSchedule::fromSettings($settings);

        if (!$schedule->beforeEnabled && !$schedule->afterEnabled) {
            $this->say("Tenant {$tenant->id}: pengingat tagihan belum aktif.");
            return;
        }

        if (!$gateway->hasToken()) {
            $this->say("Tenant {$tenant->id}: pengingat tagihan dilewati, token WA kosong.");
            return;
        }

        if ($now->format('H:i') < $schedule->time) {
            $this->say("Tenant {$tenant->id}: pengingat tagihan menunggu jam {$schedule->time}.");
            return;
        }

        [$delayMin, $delayMax] = $this->delays($settings);
        $generateSendsWa = $this->truthy($settings['billing_auto_generate_enabled'] ?? false)
            && $this->truthy($settings['billing_auto_generate_send_wa'] ?? false);

        $sender = new InvoiceReminderSender($gateway, $schedule, $generateSendsWa, fn () => $this->pause($delayMin, $delayMax));
        $counts = $sender->send($now);

        $this->say("Tenant {$tenant->id}: {$counts['before']} pengingat sebelum jatuh tempo, {$counts['after']} sesudah jatuh tempo, {$counts['failed']} gagal.");
        Log::info('Billing automation sent invoice reminders.', ['tenant_id' => $tenant->id] + $counts);
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
