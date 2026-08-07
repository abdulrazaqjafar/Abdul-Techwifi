<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PackageRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'duration_hours' => ['nullable', 'integer', 'min:0', 'max:8760'],
            'duration_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'profile_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'duration_hours' => $this->input('duration_hours', 0),
            'duration_days' => $this->input('duration_days', 0),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $hours = (int) $this->input('duration_hours', 0);
            $days = (int) $this->input('duration_days', 0);

            if ($hours === 0 && $days === 0) {
                $validator->errors()->add('duration_hours', 'Either duration hours or duration days must be greater than zero.');
            }
        });
    }
}
