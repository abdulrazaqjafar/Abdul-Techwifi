<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CashierSellRequest extends FormRequest
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
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'amount' => ['nullable', 'numeric', 'min:908', 'max:3000000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
