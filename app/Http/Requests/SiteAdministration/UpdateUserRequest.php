<?php

namespace App\Http\Requests\SiteAdministration;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->getKey() : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'photo' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['required', 'boolean'],
            'organization_ids' => ['array'],
            'organization_ids.*' => ['integer', 'exists:organizations,id'],
        ];
    }
}
