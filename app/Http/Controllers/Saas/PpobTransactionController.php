<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PpobTransaction;

class PpobTransactionController extends Controller
{
    public function index()
    {
        // SaaS view sees ALL transactions
        $transactions = PpobTransaction::with(['customer.tenant', 'product', 'supplier'])
            ->latest()
            ->paginate(20);
            
        return view('saas.ppob.transactions.index', compact('transactions'));
    }
}
