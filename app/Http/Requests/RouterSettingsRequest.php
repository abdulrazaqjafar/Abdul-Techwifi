<?php

namespace App\Http\Requests;

use App\Models\RouterSetting;
use Illuminate\Foundation\Http\FormRequest;

class RouterSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $passwordRules = ['nullable', 'string', 'max:255'];

        if (! RouterSetting::query()->exists()) {
            $passwordRules[0] = 'required';
        }

        return [
            'router_ip' => ['required', 'string', 'max:255'],
            'router_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'router_username' => ['required', 'string', 'max:255'],
            'router_password' => $passwordRules,
            'hotspot_interface' => ['nullable', 'string', 'max:255'],
        ];
    }
}
