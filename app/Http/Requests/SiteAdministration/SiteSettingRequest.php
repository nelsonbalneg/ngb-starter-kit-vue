<?php

namespace App\Http\Requests\SiteAdministration;

use App\Models\SiteSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiteSettingRequest extends FormRequest
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
        $setting = $this->route('setting');
        $settingId = $setting instanceof SiteSetting ? $setting->id : null;

        return [
            'group' => ['required', 'string', 'max:100'],
            'key' => ['required', 'string', 'max:150', Rule::unique('site_settings', 'key')->where('group', $this->string('group')->toString())->ignore($settingId)],
            'value' => ['nullable'],
            'type' => ['required', 'string', Rule::in(['string', 'boolean', 'integer', 'json', 'secret'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_public' => ['boolean'],
        ];
    }
}
