<?php

namespace App\Http\Requests\SiteAdministration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleRequest extends FormRequest
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
        $role = $this->route('role');
        $roleId = $role instanceof Role ? $role->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'web')
                    ->where('team_id', $this->attributes->get('organization_id'))
                    ->ignore($roleId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'stay_on_authentication' => ['sometimes', 'boolean'],
        ];
    }
}
