<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OfferSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'monthly_offer_enabled' => ['sometimes', 'boolean'],
            'monthly_offer_discount_percent' => ['required', 'integer', 'min:1', 'max:90'],
            'monthly_offer_message' => ['nullable', 'string', 'max:500'],
            'sms_provider' => ['required', 'in:log,africastalking'],
            'sms_sender_id' => ['nullable', 'string', 'max:20'],
        ];
    }
}
