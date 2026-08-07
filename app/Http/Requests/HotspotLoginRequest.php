<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HotspotLoginRequest extends FormRequest
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
        return [
            'voucher_code' => ['required', 'string', 'max:50'],
            'mac' => ['nullable', 'string', 'max:64'],
            'ip' => ['nullable', 'ip'],
            'user_agent' => ['nullable', 'string', 'max:512'],
            'screen_width' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'link_login' => ['nullable', 'url', 'max:2048'],
            'link_orig' => ['nullable', 'url', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('voucher_code')) {
            $this->merge([
                'voucher_code' => strtoupper(trim((string) $this->input('voucher_code'))),
            ]);
        }
    }
}
