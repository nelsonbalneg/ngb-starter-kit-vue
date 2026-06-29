<?php

namespace App\Http\Requests\SiteAdministration;

use App\Models\Lookup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LookupRequest extends FormRequest
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
        $lookup = $this->route('lookup');
        $lookupId = $lookup instanceof Lookup ? $lookup->id : null;

        return [
            'group' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:100', Rule::unique('lookups', 'code')->where('group', $this->string('group')->toString())->ignore($lookupId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
