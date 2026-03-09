<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SiteSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'site_name' => ['nullable', 'string', 'max:255'],
            'site_email' => ['nullable', 'email:rfc', 'max:255'],
            'site_phone' => ['nullable', 'string', 'max:50'],
            'site_address' => ['nullable', 'string', 'max:500'],
            'site_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ];
    }
}
