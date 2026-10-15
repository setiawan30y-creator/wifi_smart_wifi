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
        $settings = Setting::whereNull('tenant_id')->pluck('value', 'key')->toArray();
        
        return view('saas.settings.index', compact('settings'));
    }

    public function store(Request $request)
    {
        $data = $request->except(['_token']);
        
        foreach ($data as $key => $value) {
            if ($value !== null) {
                Setting::updateOrCreate(
                    ['tenant_id' => null, 'key' => $key],
                    ['value' => $value]
                );
            }
        }
        
        return back()->with('success', 'Pengaturan Gateway SaaS berhasil disimpan.');
    }
}
