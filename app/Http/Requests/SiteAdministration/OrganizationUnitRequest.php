<?php

namespace App\Http\Requests\SiteAdministration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationUnitRequest extends FormRequest
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
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'parent_id' => ['nullable', 'integer', 'exists:organization_units,id'],
            'type' => ['required', 'string', Rule::in(['office', 'department'])],
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'file', 'image', 'max:2048', 'mimes:jpeg,jpg,png,svg,webp'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'head_user_ids' => ['array'],
            'head_user_ids.*' => ['integer', 'exists:users,id'],
            'is_active' => ['boolean'],
        ];
    }
}
