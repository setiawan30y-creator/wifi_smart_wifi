<?php

namespace App\Services\Billing;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\WaTemplate;
use App\Services\WhatsAppGateway;
use App\Support\Phone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Mengirim pengingat WA tagihan belum lunas sesuai ReminderSchedule.
 *
 * Aturan:
 *  - Hanya tagihan "unpaid" dari pelanggan yang ditagih (aktif, berbayar, pascabayar).
 *  - Setiap tahap (mis. H-3, H+7) hanya terkirim sekali per tagihan (tabel invoice_reminders).
 *    Tahap yang gagal dicoba lagi keesokan harinya.
 *  - Maksimal satu pengingat per pelanggan per hari.
 *  - Dilewati selama ada janji bayar yang belum lewat.
 *  - Pengingat H- dilewati pada hari tagihan terbit jika WA tagihan terbit sudah aktif.
 *  - Pelanggan dengan beberapa tunggakan menerima satu pesan berisi semua tunggakan.
 *
 * Harus dipanggil di dalam TenantContext tenant yang bersangkutan.
 */
class InvoiceReminderSender
{
    /** @var callable */
    private $pause;

    public function __construct(
        private readonly WhatsAppGateway $gateway,
        private readonly ReminderSchedule $schedule,
        private readonly bool $generateSendsWa,
        ?callable $pause = null,
    ) {
        $this->pause = $pause ?? fn () => null;
    }

    /**
     * @return array{before: int, after: int, failed: int}
     */
    public function send(Carbon $now): array
    {
        $counts = ['before' => 0, 'after' => 0, 'failed' => 0];

        foreach ($this->plans($now) as [$customer, $phone, $plan, $unpaid]) {
            $message = $this->message($plan, $customer, $unpaid, $now->copy()->startOfDay());
            $result = $this->gateway->send($phone, $message);
            $ok = (bool) ($result['success'] ?? false);

            $this->record($plan, $now, $ok, $ok ? null : (string) ($result['message'] ?? 'Gagal mengirim WA'));

            if ($ok) {
                $counts[$plan['type']]++;
                ($this->pause)();
            } else {
                $counts['failed']++;
            }
        }

        return $counts;
    }

    /**
     * Daftar pengingat yang akan dikirim hari ini, tanpa mengirim apa pun.
     *
     * @return array<int, array{customer: string, phone: string, type: string, stages: string, invoices: string, amount: int}>
     */
    public function preview(Carbon $now): array
    {
        $rows = [];

        foreach ($this->plans($now) as [$customer, $phone, $plan]) {
            $rows[] = [
                'customer' => (string) $customer->name,
                'phone' => $phone,
                'type' => $plan['type'],
                'stages' => collect($plan['items'])->map(fn ($item) => ReminderSchedule::label($plan['type'], $item['offset']))->unique()->implode(', '),
                'invoices' => collect($plan['items'])->map(fn ($item) => $item['invoice']->invoice_number)->implode(', '),
                'amount' => (int) collect($plan['items'])->sum(fn ($item) => (int) $item['invoice']->amount),
            ];
        }

        return $rows;
    }

    /**
     * @return \Generator<int, array{0: Customer, 1: string, 2: array, 3: Collection}>
     */
    private function plans(Carbon $now): \Generator
    {
        $today = $now->copy()->startOfDay();

        $invoices = Invoice::query()
            ->with(['customer.odp', 'reminders'])
            ->where('status', 'unpaid')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        foreach ($invoices->groupBy('customer_id') as $customerInvoices) {
            /** @var Customer|null $customer */
            $customer = $customerInvoices->first()->customer;
            $phone = Phone::normalize($customer?->phone);

            if (!$customer || !$phone || !$customer->isBillable()) {
                continue;
            }

            if ($this->alreadyRemindedToday($customerInvoices, $today)) {
                continue;
            }

            $plan = $this->plan($customerInvoices, $customer, $today);

            if ($plan !== null) {
                yield [$customer, $phone, $plan, $customerInvoices];
            }
        }
    }

    /**
     * Tentukan pengingat apa yang dikirim hari ini untuk satu pelanggan.
     * Pengingat sesudah jatuh tempo didahulukan daripada sebelum jatuh tempo.
     *
     * @return array{type: string, items: array<int, array{invoice: Invoice, offset: int, dueDate: Carbon, days: int}>}|null
     */
    private function plan(Collection $invoices, Customer $customer, Carbon $today): ?array
    {
        $after = [];
        $before = [];

        foreach ($invoices as $invoice) {
            if ($invoice->payment_promise_date && Carbon::parse($invoice->payment_promise_date)->startOfDay()->gte($today)) {
                continue;
            }

            $dueDate = DueDate::for($customer, (int) $invoice->month, (int) $invoice->year)->startOfDay();
            $daysUntilDue = (int) $today->diffInDays($dueDate, false);

            if ($daysUntilDue < 0) {
                $daysOverdue = -$daysUntilDue;
                $stage = $this->schedule->afterStage($daysOverdue);

                if ($stage !== null
                    && (int) $invoice->overdue_reminder_count < count($this->schedule->afterDays)
                    && $this->due($invoice, InvoiceReminder::AFTER, $stage, $today)) {
                    $after[] = ['invoice' => $invoice, 'offset' => $stage, 'dueDate' => $dueDate, 'days' => $daysOverdue];
                }

                continue;
            }

            $stage = $this->schedule->beforeStage($daysUntilDue);

            if ($stage === null || !$this->due($invoice, InvoiceReminder::BEFORE, $stage, $today)) {
                continue;
            }

            if ($this->generateSendsWa && $invoice->created_at?->copy()->startOfDay()->equalTo($today)) {
                continue; // WA "tagihan terbit" sudah terkirim hari ini.
            }

            $before[] = ['invoice' => $invoice, 'offset' => $stage, 'dueDate' => $dueDate, 'days' => $daysUntilDue];
        }

        if ($after !== []) {
            return ['type' => InvoiceReminder::AFTER, 'items' => $after];
        }

        return $before !== [] ? ['type' => InvoiceReminder::BEFORE, 'items' => $before] : null;
    }

    /**
     * Tahap ini belum pernah terkirim (atau gagal kemarin dan perlu dicoba lagi).
     */
    private function due(Invoice $invoice, string $type, int $offset, Carbon $today): bool
    {
        $log = $invoice->reminders->first(fn (InvoiceReminder $r) => $r->type === $type && (int) $r->offset_days === $offset);

        if (!$log) {
            return true;
        }

        return $log->status !== 'sent' && !$log->attempted_at?->isSameDay($today);
    }

    private function alreadyRemindedToday(Collection $invoices, Carbon $today): bool
    {
        return $invoices->contains(function (Invoice $invoice) use ($today) {
            return $invoice->last_overdue_reminder_sent_at?->isSameDay($today)
                || $invoice->reminders->contains(fn (InvoiceReminder $r) => $r->status === 'sent' && $r->attempted_at?->isSameDay($today));
        });
    }

    private function message(array $plan, Customer $customer, Collection $allUnpaid, Carbon $today): string
    {
        $items = $plan['items'];

        if ($plan['type'] === InvoiceReminder::BEFORE) {
            $template = $this->template($this->schedule->beforeTemplateId);
            $first = $items[0];

            if (count($items) === 1) {
                return BillingMessages::beforeDue($template, $customer, $first['invoice'], $first['dueDate'], $first['days']);
            }

            return BillingMessages::arrears($template, $customer, collect($items)->pluck('invoice'));
        }

        $template = $this->template($this->schedule->afterTemplateId);

        // Semua tagihan yang sudah lewat jatuh tempo ikut disebut, bukan hanya yang tahapnya hari ini.
        $overdue = $allUnpaid->filter(function (Invoice $invoice) use ($customer, $today) {
            return DueDate::for($customer, (int) $invoice->month, (int) $invoice->year)->startOfDay()->lt($today);
        })->values();

        $maxDays = max(array_column($items, 'days'));

        if ($overdue->count() <= 1) {
            $first = $items[0];

            return BillingMessages::overdue($template, $customer, $first['invoice'], $first['dueDate'], $first['days']);
        }

        return BillingMessages::arrears($template, $customer, $overdue, $maxDays);
    }

    private function record(array $plan, Carbon $now, bool $ok, ?string $error): void
    {
        foreach ($plan['items'] as $item) {
            /** @var Invoice $invoice */
            $invoice = $item['invoice'];

            InvoiceReminder::updateOrCreate(
                ['invoice_id' => $invoice->id, 'type' => $plan['type'], 'offset_days' => $item['offset']],
                ['status' => $ok ? 'sent' : 'failed', 'attempted_at' => $now, 'error' => $error ? mb_substr($error, 0, 500) : null],
            );

            if ($ok && $plan['type'] === InvoiceReminder::AFTER) {
                // Kolom lama tetap diisi agar laporan/tampilan lama tetap benar.
                $invoice->forceFill([
                    'overdue_reminder_count' => (int) $invoice->overdue_reminder_count + 1,
                    'last_overdue_reminder_sent_at' => $now,
                ])->save();
            }
        }
    }

    private function template(?int $id): ?string
    {
        return $id ? WaTemplate::find($id)?->content : null;
    }
}
