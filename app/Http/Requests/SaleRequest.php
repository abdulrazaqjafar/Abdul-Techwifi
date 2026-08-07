<?php

namespace App\Http\Requests;

use App\Models\Voucher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
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
        return [
            'voucher_id' => [
                'required',
                'integer',
                Rule::exists('vouchers', 'id')->where('status', Voucher::STATUS_UNUSED),
            ],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'payment_method' => ['required', 'string', Rule::in(['cash', 'mobile_money', 'card', 'bank_transfer', 'other'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'sold_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'voucher_id.exists' => 'The selected voucher is invalid or has already been used.',
        ];
    }
}
