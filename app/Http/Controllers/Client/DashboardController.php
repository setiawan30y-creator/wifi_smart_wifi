<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;

class DashboardController extends Controller
{
    public function index()
    {
        // For demonstration purposes, we'll just pick the first customer.
        // In a real scenario, this would use Auth::guard('customer')->user()
        $customer = Customer::with(['walletTransactions' => function($q) {
            $q->latest()->take(5);
        }, 'ppobTransactions' => function($q) {
            $q->latest()->take(5);
        }])->first();

        if (!$customer) {
            return "No customer found in the database. Please add a customer first.";
        }

        return view('client.dashboard', compact('customer'));
    }
}
