<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class GenerateInvoiceRequest extends FormRequest
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
            'month'          => 'required|integer|min:1|max:12',
            'year'           => 'required|integer|min:2020|max:2035',
            'wa_template_id' => 'nullable|exists:wa_templates,id',
            'send_wa'        => 'nullable|boolean',
        ];
    }
}
