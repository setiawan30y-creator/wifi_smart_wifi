<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SaasInvoice;

class SaasInvoiceController extends Controller
{
    public function index()
    {
        $invoices = SaasInvoice::where('tenant_id', auth()->user()->tenant_id)
            ->latest()
            ->paginate(10);
            
        return view('admin.saas_invoices.index', compact('invoices'));
    }
    
    public function uploadProof(Request $request, SaasInvoice $invoice)
    {
        $request->validate([
            'payment_proof' => 'required|image|max:2048',
        ]);

        if ($invoice->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $path = $request->file('payment_proof')->store('saas_payment_proofs', 'public');

        $invoice->update([
            'payment_proof' => $path,
            'status' => 'pending_verification'
        ]);

        return back()->with('success', 'Bukti transfer berhasil diunggah! Menunggu verifikasi tim kami.');
    }
}
