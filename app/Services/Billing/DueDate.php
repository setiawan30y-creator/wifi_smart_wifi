<?php

namespace App\Services\Billing;

use App\Models\Customer;
use Illuminate\Support\Carbon;

/**
 * Tanggal jatuh tempo tagihan = tanggal tagih pelanggan (billing_day) pada bulan periode.
 * Jika billing_day melebihi jumlah hari di bulan itu, dipakai hari terakhir bulan.
 */
final class DueDate
{
    public static function for(Customer $customer, int $month, int $year): Carbon
    {
        $date = Carbon::create($year, $month, 1)->startOfDay();
        $billingDay = max(1, min((int) ($customer->billing_day ?: 1), $date->daysInMonth));

        return $date->day($billingDay);
    }

    /**
     * Tanggal mulai pelanggan ditagih (tanggal pasang, atau tanggal didaftarkan).
     */
    public static function billingStart(Customer $customer): ?Carbon
    {
        if ($customer->tanggal_pasang) {
            return Carbon::parse($customer->tanggal_pasang)->startOfDay();
        }

        return $customer->created_at?->copy()->startOfDay();
    }
}
