<?php

namespace App\Services\Billing;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

/**
 * Pembuatan tagihan bulanan — dipakai oleh tombol "Generate Tagihan" dan billing otomatis.
 * Bekerja pada tenant aktif (TenantContext), jadi pemanggil cukup menjalankannya per tenant.
 */
class InvoiceGenerator
{
    public const CREATED = 'created';
    public const EXISTS = 'exists';
    public const NO_PACKAGE = 'no_package';
    public const NOT_STARTED = 'not_started';

    /**
     * Pelanggan yang ditagih bulanan: aktif, berbayar, dan pascabayar.
     */
    public function billableCustomers(): Builder
    {
        return Customer::query()
            ->with('package', 'odp')
            ->whereRaw('LOWER(TRIM(status)) = ?', ['active'])
            ->where(fn ($q) => $q->whereNull('billing_type')->orWhere('billing_type', Customer::BILLING_PAID))
            ->where(fn ($q) => $q->whereNull('billing_mode')->orWhere('billing_mode', Customer::MODE_POSTPAID));
    }

    /**
     * Buat tagihan satu pelanggan untuk satu periode bila belum ada.
     *
     * @param  Carbon  $cutoff  pelanggan yang mulai ditagih setelah tanggal ini dilewati
     * @return array{0: string, 1: ?Invoice}
     */
    public function generate(Customer $customer, int $month, int $year, Carbon $cutoff): array
    {
        if (!$customer->package) {
            return [self::NO_PACKAGE, null];
        }

        $start = DueDate::billingStart($customer);
        if ($start && $start->gt($cutoff)) {
            return [self::NOT_STARTED, null];
        }

        if ($this->exists($customer, $month, $year)) {
            return [self::EXISTS, null];
        }

        return [self::CREATED, $this->create($customer, $month, $year)];
    }

    public function exists(Customer $customer, int $month, int $year): bool
    {
        return Invoice::query()
            ->where('customer_id', $customer->id)
            ->where('month', $month)
            ->where('year', $year)
            ->exists();
    }

    /**
     * Nomor invoice unik secara global; bila bentrok (dua proses bersamaan), coba lagi.
     */
    private function create(Customer $customer, int $month, int $year): Invoice
    {
        $attempts = 0;

        while (true) {
            try {
                $invoice = Invoice::create([
                    'customer_id' => $customer->id,
                    'month' => $month,
                    'year' => $year,
                    'amount' => $customer->package->price,
                    'status' => 'unpaid',
                ]);

                return $invoice->setRelation('customer', $customer);
            } catch (UniqueConstraintViolationException $e) {
                if (++$attempts >= 3) {
                    throw $e;
                }
            }
        }
    }
}
