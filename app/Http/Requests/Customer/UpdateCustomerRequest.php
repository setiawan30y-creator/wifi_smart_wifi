<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
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
            'package_id' => 'required|exists:packages,id',
            'odp_id' => 'nullable|exists:odps,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'maps_link' => 'nullable|string|max:500',
            'billing_day' => 'required|integer|min:1|max:28',
            'billing_type' => 'required|string|in:paid,admin,social,family,orphan,rt,other_free',
            'billing_mode' => 'required|string|in:postpaid,prepaid',
            'billing_note' => 'nullable|string|max:255',
            'tanggal_pasang' => 'nullable|date',
            'pppoe_username' => 'nullable|string|max:255',
            'pppoe_password' => 'nullable|string|max:255',
            'status_rumah' => 'required|string|in:milik_sendiri,ngontrak',
            'pemilik_rumah' => 'nullable|string|max:255',
            'foto_ktp' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'foto_rumah' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'foto_perangkat' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'foto_odp' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'jalur' => 'nullable|string|in:FO,LAN',
            'perangkat' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ];
    }
}
