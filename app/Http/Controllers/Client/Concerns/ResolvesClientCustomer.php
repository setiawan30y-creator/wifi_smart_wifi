<?php

namespace App\Http\Controllers\Client\Concerns;

use App\Models\Customer;

trait ResolvesClientCustomer
{
    /**
     * Pelanggan milik user login. Staf (bukan role client) tidak memakai portal pelanggan.
     */
    protected function clientCustomer(): Customer
    {
        $user = auth()->user();

        abort_if($user->role !== 'client', 403, 'Halaman ini khusus untuk akun pelanggan.');

        $customer = Customer::forUser($user);

        abort_unless($customer, 404, 'Akun Anda belum terhubung dengan data pelanggan. Hubungi admin.');

        return $customer;
    }
}
