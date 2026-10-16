<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Concerns\ResolvesClientCustomer;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    use ResolvesClientCustomer;

    public function index()
    {
        $customer = $this->clientCustomer()->load([
            'walletTransactions' => fn ($query) => $query->latest()->take(5),
            'ppobTransactions' => fn ($query) => $query->latest()->take(5),
        ]);

        return view('client.dashboard', compact('customer'));
    }
}
