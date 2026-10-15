<?php

namespace App\Http\Controllers\Admin\Ppob;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PpobTransaction;

class TenantPpobTransactionController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        $transactions = PpobTransaction::with(['customer', 'product'])
            ->where('tenant_id', $tenantId)
            ->latest()
            ->paginate(20);
            
        return view('admin.ppob.transactions.index', compact('transactions'));
    }
}
