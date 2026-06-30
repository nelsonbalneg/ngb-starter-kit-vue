<?php

namespace App\Http\Requests\SiteAdministration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppearanceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'theme' => ['required', 'string', Rule::in(['light', 'dark', 'system'])],
            'accent_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sidebar_style' => ['required', 'string', Rule::in(['light', 'dark'])],
            'navigation' => ['required', 'string', Rule::in(['sidebar', 'top'])],
            'sidebar_default' => ['required', 'string', Rule::in(['expanded', 'collapsed'])],
            'content_width' => ['required', 'string', Rule::in(['fixed', 'full'])],
            'density' => ['required', 'string', Rule::in(['compact', 'comfortable', 'spacious'])],
            'font' => ['required', 'string', Rule::in(['Inter', 'Roboto', 'Open Sans', 'Poppins'])],
            'font_size' => ['required', 'string', Rule::in(['small', 'medium', 'large'])],
            'table_rows' => ['required', 'integer', Rule::in([10, 25, 50, 100])],
            'table_sticky' => ['boolean'],
            'table_zebra' => ['boolean'],
            'table_dense' => ['boolean'],
            'card_shadow' => ['required', 'string', Rule::in(['none', 'small', 'medium', 'large'])],
            'card_radius' => ['required', 'integer', 'min:0', 'max:24'],
            'card_flat' => ['boolean'],
            'animation' => ['boolean'],
            'animation_speed' => ['required', 'string', Rule::in(['fast', 'normal', 'slow'])],
            'login_background' => ['nullable', 'string', 'max:255'],
            'login_background_file' => ['nullable', 'file', 'image', 'max:4096', 'mimes:jpeg,jpg,png,webp'],
            'login_style' => ['required', 'string', Rule::in(['centered', 'split', 'glass', 'minimal'])],
            'login_background_type' => ['required', 'string', Rule::in(['image', 'gradient', 'solid'])],
            'login_overlay' => ['required', 'integer', 'min:0', 'max:100'],
            'high_contrast' => ['boolean'],
            'reduce_motion' => ['boolean'],
            'large_text' => ['boolean'],
            'custom_css' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
