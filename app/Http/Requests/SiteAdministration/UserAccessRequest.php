<?php

namespace App\Http\Requests\SiteAdministration;

use Illuminate\Foundation\Http\FormRequest;

class UserAccessRequest extends FormRequest
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
            'organization_ids' => ['array'],
            'organization_ids.*' => ['integer', 'exists:organizations,id'],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
