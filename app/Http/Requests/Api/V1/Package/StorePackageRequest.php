<?php

namespace App\Http\Requests\Api\V1\Package;

use Illuminate\Foundation\Http\FormRequest;

class StorePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'speed' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sync_mikrotik' => ['nullable', 'boolean'],
        ];
    }
}
