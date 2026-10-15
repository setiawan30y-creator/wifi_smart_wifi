<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\WaTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class InvoiceAutomationSettingController extends Controller
{
    public function index()
    {
        $settings = [
            'billing_auto_generate_enabled' => Setting::get('billing_auto_generate_enabled', '0'),
            'billing_auto_generate_days_before_due' => Setting::get('billing_auto_generate_days_before_due', '3'),
            'billing_auto_generate_time' => Setting::get('billing_auto_generate_time', '02:00'),
            'billing_auto_generate_send_wa' => Setting::get('billing_auto_generate_send_wa', '0'),
            'billing_auto_generate_group_same_phone' => Setting::get('billing_auto_generate_group_same_phone', '1'),
            'billing_auto_generate_wa_template_id' => Setting::get('billing_auto_generate_wa_template_id', ''),
            'billing_auto_generate_wa_template_ids' => Setting::get('billing_auto_generate_wa_template_ids', '[]'),
            'billing_auto_generate_wa_delay_min' => Setting::get('billing_auto_generate_wa_delay_min', '5'),
            'billing_auto_generate_wa_delay_max' => Setting::get('billing_auto_generate_wa_delay_max', '20'),
            'billing_overdue_wa_enabled' => Setting::get('billing_overdue_wa_enabled', '0'),
            'billing_overdue_days_after_due' => Setting::get('billing_overdue_days_after_due', '1'),
            'billing_overdue_max_reminders' => Setting::get('billing_overdue_max_reminders', '3'),
            'billing_overdue_wa_template_id' => Setting::get('billing_overdue_wa_template_id', ''),
            'billing_payment_promise_wa_template_id' => Setting::get('billing_payment_promise_wa_template_id', ''),
        ];

        $waTemplates = WaTemplate::orderBy('title')->get();

        return view('invoices.automation_settings', compact('settings', 'waTemplates'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'billing_auto_generate_enabled' => ['nullable', 'boolean'],
            'billing_auto_generate_days_before_due' => ['required', 'integer', 'min:0', 'max:31'],
            'billing_auto_generate_time' => ['required', 'date_format:H:i'],
            'billing_auto_generate_send_wa' => ['nullable', 'boolean'],
            'billing_auto_generate_group_same_phone' => ['nullable', 'boolean'],
            'billing_auto_generate_wa_template_id' => ['nullable', 'exists:wa_templates,id'],
            'billing_auto_generate_wa_template_ids' => ['nullable', 'array'],
            'billing_auto_generate_wa_template_ids.*' => ['integer', 'exists:wa_templates,id'],
            'billing_auto_generate_wa_delay_min' => ['required', 'integer', 'min:0', 'max:3600'],
            'billing_auto_generate_wa_delay_max' => ['required', 'integer', 'min:0', 'max:3600', 'gte:billing_auto_generate_wa_delay_min'],
            'billing_overdue_wa_enabled' => ['nullable', 'boolean'],
            'billing_overdue_days_after_due' => ['required', 'integer', 'min:0', 'max:60'],
            'billing_overdue_max_reminders' => ['required', 'integer', 'min:1', 'max:10'],
            'billing_overdue_wa_template_id' => ['nullable', 'exists:wa_templates,id'],
            'billing_payment_promise_wa_template_id' => ['nullable', 'exists:wa_templates,id'],
        ]);

        $keys = [
            'billing_auto_generate_enabled',
            'billing_auto_generate_days_before_due',
            'billing_auto_generate_time',
            'billing_auto_generate_send_wa',
            'billing_auto_generate_group_same_phone',
            'billing_auto_generate_wa_template_id',
            'billing_auto_generate_wa_template_ids',
            'billing_auto_generate_wa_delay_min',
            'billing_auto_generate_wa_delay_max',
            'billing_overdue_wa_enabled',
            'billing_overdue_days_after_due',
            'billing_overdue_max_reminders',
            'billing_overdue_wa_template_id',
            'billing_payment_promise_wa_template_id',
        ];

        foreach ($keys as $key) {
            if (in_array($key, ['billing_auto_generate_enabled', 'billing_auto_generate_send_wa', 'billing_auto_generate_group_same_phone', 'billing_overdue_wa_enabled'], true)) {
                $value = $request->boolean($key) ? '1' : '0';
            } elseif ($key === 'billing_auto_generate_wa_template_ids') {
                $templateIds = collect($data[$key] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $value = json_encode($templateIds);
            } else {
                $value = $data[$key] ?? '';
            }

            Setting::set($key, $value);
        }

        return redirect()
            ->route('invoices.automation-settings')
            ->with('success', 'Pengaturan otomatis tagihan berhasil disimpan dan akan berjalan terus sesuai scheduler server.');
    }

    public function runNow(Request $request)
    {
        $tenantId = optional($request->user())->tenant_id;

        if (!$tenantId) {
            return redirect()
                ->route('invoices.automation-settings')
                ->with('error', 'Tenant akun tidak ditemukan. Silakan login ulang.');
        }

        Artisan::call('billing:automation', [
            '--force' => true,
            '--tenant' => $tenantId,
        ]);

        $output = trim(Artisan::output());
        $message = $output !== ''
            ? 'Test otomatis tagihan selesai. ' . preg_replace('/\s+/', ' ', $output)
            : 'Test otomatis tagihan selesai.';

        return redirect()
            ->route('invoices.automation-settings')
            ->with('success', $message);
    }
}
