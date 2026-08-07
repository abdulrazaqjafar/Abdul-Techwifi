<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffRequest extends FormRequest
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
        $staffId = $this->route('staff')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('admins', 'email')->ignore($staffId),
            ],
            'password' => [$staffId ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Weka nenosiri la mwanachama wa staff.',
            'password.min' => 'Nenosiri lazima liwe na herufi 8 au zaidi.',
            'password.confirmed' => 'Nenosiri na uthibitisho haulingani.',
        ];
    }
}
