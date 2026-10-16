<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SaasSettingController extends Controller
{
    public function index()
    {
        // Get all SaaS-level settings (tenant_id = null)
        $settings = Setting::platform();
        
        return view('saas.settings.index', compact('settings'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'saas_tripay_api_key' => 'nullable|string|max:255',
            'saas_tripay_merchant_code' => 'nullable|string|max:100',
            'saas_tripay_mode' => 'nullable|in:sandbox,production',
            'saas_tripay_private_key' => 'nullable|string|max:255',
            'saas_wa_endpoint' => 'nullable|string|max:1000',
            'saas_wa_invoice_template' => 'nullable|string|max:5000',
        ]);

        foreach ($data as $key => $value) {
            if ($value !== null) {
                Setting::setPlatform($key, $value);
            }
        }
        
        return back()->with('success', 'Pengaturan Gateway SaaS berhasil disimpan.');
    }
}
