<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SaasInvoice;

class SaasInvoiceController extends Controller
{
    public function index()
    {
        $invoices = SaasInvoice::with('tenant')->latest()->paginate(20);
        return view('saas.invoices.index', compact('invoices'));
    }

    public function markAsPaid(SaasInvoice $invoice)
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => 'manual',
        ]);
        
        // Extend subscription by 1 month (or whatever plan states)
        $tenant = $invoice->tenant;
        if ($tenant->subscription_ends_at) {
            // If already expired, add from today. If active, add to existing.
            $baseDate = $tenant->subscription_ends_at->isPast() ? now() : $tenant->subscription_ends_at;
            $tenant->update([
                'subscription_ends_at' => $baseDate->addMonth(),
                'status' => 'active'
            ]);
        } else {
            $tenant->update([
                'subscription_ends_at' => now()->addMonth(),
                'status' => 'active'
            ]);
        }

        return back()->with('success', 'Tagihan berhasil ditandai Lunas dan masa aktif diperpanjang.');
    }

    public function print(SaasInvoice $invoice)
    {
        $invoice->load('tenant');
        return view('saas.invoices.print', compact('invoice'));
    }

    public function sendWa(SaasInvoice $invoice)
    {
        $invoice->load('tenant');
        $tenant = $invoice->tenant;
        
        $settings = \App\Models\Setting::platform();
        
        $endpoint = $settings['saas_wa_endpoint'] ?? null;
        $template = $settings['saas_wa_invoice_template'] ?? '';
        
        if (!$endpoint || !$template) {
            return back()->with('error', 'Gagal kirim WA: Konfigurasi WA Gateway belum disetel di Pengaturan Gateway.');
        }

        // Get owner phone (assuming first user of tenant is the owner, or tenant has a phone field. We'll use first user for now)
        $owner = $tenant->users()->first();
        if (!$owner || !$owner->phone) {
            return back()->with('error', 'Gagal kirim WA: Data nomor telepon pemilik RT/RW Net (Tenant) tidak ditemukan.');
        }

        $message = str_replace(
            ['{name}', '{invoice_number}', '{amount}', '{due_date}', '{checkout_url}'],
            [
                $owner->name,
                $invoice->invoice_number,
                number_format($invoice->amount, 0, ',', '.'),
                $invoice->due_date->format('d M Y'),
                $invoice->checkout_url ?? 'Tidak ada link (Silakan transfer manual)'
            ],
            $template
        );

        try {
            \Illuminate\Support\Facades\Http::post($endpoint, [
                'number' => $owner->phone,
                'message' => $message,
            ]);
            return back()->with('success', 'Pesan tagihan berhasil dikirim ke WhatsApp bos RT/RW Net.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal kirim WA: ' . $e->getMessage());
        }
    }
}
