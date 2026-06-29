<?php

namespace App\Http\Requests\SiteAdministration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermissionRequest extends FormRequest
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
        $permission = $this->route('permission');
        $permissionId = $permission instanceof Permission ? $permission->id : null;

        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+\\.[a-z0-9-]+$/', Rule::unique('permissions', 'name')->where('guard_name', 'web')->ignore($permissionId)],
            'group' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
