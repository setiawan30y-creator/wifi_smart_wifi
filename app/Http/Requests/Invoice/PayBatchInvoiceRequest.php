<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class PayBatchInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'invoice_ids'    => 'required|array|min:1',
            'invoice_ids.*'  => 'exists:invoices,id',
            'payment_method' => 'required|string|max:50',
            'paid_at'        => 'nullable|date',
            'payment_proof'  => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:3072',
            'send_wa'        => 'nullable|boolean',
            'wa_template_id'  => 'nullable|exists:wa_templates,id',
        ];
    }
}
