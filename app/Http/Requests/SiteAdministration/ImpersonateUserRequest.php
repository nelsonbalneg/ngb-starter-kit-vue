<?php

namespace App\Http\Requests\SiteAdministration;

use Illuminate\Foundation\Http\FormRequest;

class ImpersonateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.impersonate') === true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:100'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
