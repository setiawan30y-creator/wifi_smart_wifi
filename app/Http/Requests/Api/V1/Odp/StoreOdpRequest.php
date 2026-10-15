<?php

namespace App\Http\Requests\Api\V1\Odp;

use Illuminate\Foundation\Http\FormRequest;

class StoreOdpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_odp_id' => ['nullable', 'exists:odps,id'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'maps_link' => ['nullable', 'string', 'max:500'],
            'devices' => ['required', 'array', 'min:1'],
            'devices.*.device_type' => ['required', 'string', 'in:FO,LAN'],
            'devices.*.device_spec' => ['required', 'string', 'max:255'],
            'devices.*.device_spec_custom' => ['nullable', 'string', 'max:255'],
            'photo_1' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'photo_2' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'photo_3' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'photo_4' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
        ];
    }
}
