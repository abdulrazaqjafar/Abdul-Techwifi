<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoucherGenerateRequest extends FormRequest
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
            'package_id' => [
                'required',
                'integer',
                Rule::exists('packages', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'count' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'package_id.exists' => 'The selected package is invalid or inactive.',
            'count.max' => 'You can generate a maximum of 500 vouchers at once.',
        ];
    }

    public function packageId(): int
    {
        return (int) $this->validated('package_id');
    }

    public function count(): int
    {
        return (int) $this->validated('count', 1);
    }

    public function customerId(): ?int
    {
        $customerId = $this->validated('customer_id');

        return $customerId !== null ? (int) $customerId : null;
    }
}
