<?php

namespace App\Http\Requests;

use App\Services\Payment\MobileMoneyGatewayManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HotspotPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $providers = array_keys(app(MobileMoneyGatewayManager::class)->availableProviders());

        return [
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'phone' => ['required', 'string', 'regex:/^(?:\+?255|0)?[67]\d{8}$/'],
            'provider' => ['required', 'string', Rule::in($providers)],
            'mac' => ['nullable', 'string', 'max:64'],
            'ip' => ['nullable', 'ip'],
            'user_agent' => ['nullable', 'string', 'max:512'],
            'screen_width' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'link_login' => ['nullable', 'url', 'max:2048'],
            'link_orig' => ['nullable', 'url', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => __('hotspot.errors.invalid_phone'),
        ];
    }
}
