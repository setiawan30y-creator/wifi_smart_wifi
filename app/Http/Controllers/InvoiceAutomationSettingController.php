<?php

namespace App\Http\Controllers;

use App\Http\Requests\Invoice\UpdateAutomationSettingsRequest;
use App\Models\Tenant;
use App\Models\WaTemplate;
use App\Services\Billing\AutomationSettings;
use App\Services\Billing\BillingAutomation;
use App\Services\Billing\BillingMessages;
use App\Services\Billing\ReminderSchedule;
use App\Support\Tenancy\TenantContext;

/**
 * Halaman Pengaturan Otomatis Tagihan.
 *
 * Pengiriman WA sebenarnya dikerjakan scheduler server (billing:automation tiap menit),
 * bukan dari halaman ini, agar request web tidak tertahan oleh jeda kirim WA.
 * Halaman ini menampilkan pratinjau "yang akan dikirim hari ini" tanpa mengirim apa pun.
 */
class InvoiceAutomationSettingController extends Controller
{
    public function index(BillingAutomation $automation, TenantContext $tenants)
    {
        $tenant = Tenant::findOrFail($tenants->id());

        return view('invoices.automation_settings', [
            'settings' => AutomationSettings::load(),
            'waTemplates' => WaTemplate::orderBy('title')->get(['id', 'title', 'name', 'content']),
            'preview' => $automation->preview($tenant, now()),
            'beforeChoices' => ReminderSchedule::BEFORE_CHOICES,
            'afterChoices' => ReminderSchedule::AFTER_CHOICES,
            'originalMessages' => BillingMessages::EDITABLE,
        ]);
    }

    public function update(UpdateAutomationSettingsRequest $request)
    {
        AutomationSettings::save($request->validated());

        return redirect()
            ->route('invoices.automation-settings')
            ->with('success', 'Pengaturan otomatis tagihan disimpan. Scheduler server akan menjalankannya sesuai jadwal.');
    }
}
