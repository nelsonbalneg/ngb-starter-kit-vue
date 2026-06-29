<?php

namespace App\Http\Requests\SiteAdministration;

use Illuminate\Foundation\Http\FormRequest;

class InviteUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'organization_ids' => ['array'],
            'organization_ids.*' => ['integer', 'exists:organizations,id'],
        ];
    }
}
