<?php

namespace App\Http\Requests\Invoice;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAutomationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('invoices') ?? false;
    }

    public function rules(): array
    {
        $template = Rule::exists('wa_templates', 'id')->where('tenant_id', app(TenantContext::class)->id());

        return [
            'billing_auto_generate_enabled' => ['boolean'],
            'billing_auto_generate_days_before_due' => ['required', 'integer', 'min:0', 'max:31'],
            'billing_auto_generate_time' => ['required', 'date_format:H:i'],
            'billing_auto_generate_send_wa' => ['boolean'],
            'billing_auto_generate_group_same_phone' => ['boolean'],
            'billing_auto_generate_wa_template_ids' => ['nullable', 'array', 'max:10'],
            'billing_auto_generate_wa_template_ids.*' => ['integer', $template],
            'billing_auto_generate_wa_delay_min' => ['required', 'integer', 'min:0', 'max:300'],
            'billing_auto_generate_wa_delay_max' => ['required', 'integer', 'min:0', 'max:300', 'gte:billing_auto_generate_wa_delay_min'],

            'billing_reminder_time' => ['required', 'date_format:H:i'],

            'billing_reminder_before_enabled' => ['boolean'],
            'billing_reminder_before_days' => ['nullable', 'array', 'max:10', 'required_if_accepted:billing_reminder_before_enabled'],
            'billing_reminder_before_days.*' => ['integer', 'min:0', 'max:31', 'distinct'],
            'billing_reminder_before_wa_template_id' => ['nullable', 'integer', $template],

            'billing_overdue_wa_enabled' => ['boolean'],
            'billing_reminder_after_days' => ['nullable', 'array', 'max:10', 'required_if_accepted:billing_overdue_wa_enabled'],
            'billing_reminder_after_days.*' => ['integer', 'min:1', 'max:120', 'distinct'],
            'billing_overdue_wa_template_id' => ['nullable', 'integer', $template],

            'billing_payment_promise_wa_template_id' => ['nullable', 'integer', $template],
        ];
    }

    protected function prepareForValidation(): void
    {
        $booleans = [
            'billing_auto_generate_enabled', 'billing_auto_generate_send_wa', 'billing_auto_generate_group_same_phone',
            'billing_reminder_before_enabled', 'billing_overdue_wa_enabled',
        ];

        $this->merge(collect($booleans)->mapWithKeys(fn ($key) => [$key => $this->boolean($key)])->all());
    }

    public function attributes(): array
    {
        return [
            'billing_auto_generate_days_before_due' => 'hari terbit sebelum jatuh tempo',
            'billing_auto_generate_time' => 'jam terbit tagihan',
            'billing_auto_generate_wa_delay_min' => 'jeda minimal',
            'billing_auto_generate_wa_delay_max' => 'jeda maksimal',
            'billing_reminder_time' => 'jam kirim pengingat',
            'billing_reminder_before_days' => 'jadwal sebelum jatuh tempo',
            'billing_reminder_before_days.*' => 'hari sebelum jatuh tempo',
            'billing_reminder_after_days' => 'jadwal sesudah jatuh tempo',
            'billing_reminder_after_days.*' => 'hari sesudah jatuh tempo',
        ];
    }

    public function messages(): array
    {
        return [
            'billing_reminder_before_days.required_if_accepted' => 'Pilih minimal satu hari pengingat sebelum jatuh tempo.',
            'billing_reminder_after_days.required_if_accepted' => 'Pilih minimal satu hari pengingat sesudah jatuh tempo.',
            'billing_auto_generate_wa_delay_max.gte' => 'Jeda maksimal harus sama atau lebih besar dari jeda minimal.',
        ];
    }
}
