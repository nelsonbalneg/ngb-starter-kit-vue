<?php

namespace App\Services\SiteAdministration;

use App\Models\SiteSetting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SiteSettingAdministrationService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, SiteSetting>
     */
    public function list(array $filters): LengthAwarePaginator
    {
        return SiteSetting::query()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query
                ->where(fn (Builder $query): Builder => $query
                    ->where('key', 'like', "%{$search}%")
                    ->orWhere('group', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")))
            ->when($filters['group'] ?? null, fn (Builder $query, string $group): Builder => $query->where('group', $group))
            ->orderBy('group')
            ->orderBy('key')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): SiteSetting
    {
        return DB::transaction(fn (): SiteSetting => SiteSetting::create($this->normalize($data)));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SiteSetting $setting, array $data): SiteSetting
    {
        return DB::transaction(function () use ($setting, $data): SiteSetting {
            $setting->update($this->normalize($data));

            return $setting;
        });
    }

    public function delete(SiteSetting $setting): void
    {
        DB::transaction(fn () => $setting->delete());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function allGrouped(): array
    {
        $settings = SiteSetting::query()->get();

        $grouped = [];
        foreach ($settings as $setting) {
            $grouped[$setting->group][$setting->key] = $this->settingValue($setting);
        }

        // Add defaults if keys are missing
        $defaults = [
            'branding' => [
                'site_name' => config('app.name', 'Enterprise Starter Kit'),
                'tagline' => '',
                'logo_light' => null,
                'logo_dark' => null,
                'favicon' => null,
                'footer_text' => '',
                'show_footer' => true,
                'enable_passkey' => false,
                'enable_registration' => true,
                'enable_forgot_password' => true,
            ],
            'maintenance' => [
                'mode' => false,
                'title' => 'System Maintenance',
                'message' => 'The system is currently down for maintenance. Please try again later.',
                'allowed_ips' => '',
                'bypass_users' => '',
            ],
            'appearance' => [
                'theme' => 'system',
                'accent_color' => '#2563eb',
                'sidebar_style' => 'light',
                'navigation' => 'sidebar',
                'sidebar_default' => 'expanded',
                'content_width' => 'fixed',
                'density' => 'comfortable',
                'font' => 'Inter',
                'font_size' => 'medium',
                'table_rows' => 25,
                'table_sticky' => true,
                'table_zebra' => false,
                'table_dense' => false,
                'card_shadow' => 'small',
                'card_radius' => 8,
                'card_flat' => false,
                'animation' => true,
                'animation_speed' => 'normal',
                'login_background' => null,
                'login_style' => 'centered',
                'login_background_type' => 'image',
                'login_overlay' => 35,
                'high_contrast' => false,
                'reduce_motion' => false,
                'large_text' => false,
                'custom_css' => '',
            ],
        ];

        return array_replace_recursive($defaults, $grouped);
    }

    public function getValue(string $group, string $key, mixed $default = null): mixed
    {
        $setting = SiteSetting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        return $setting ? $this->settingValue($setting, $default) : $default;
    }

    public function setValue(string $group, string $key, mixed $value, string $type = 'string', ?string $description = null, bool $isPublic = false): SiteSetting
    {
        return DB::transaction(function () use ($group, $key, $value, $type, $description, $isPublic): SiteSetting {
            return SiteSetting::updateOrCreate(
                ['group' => $group, 'key' => $key],
                [
                    'value' => ['value' => $value],
                    'type' => $type,
                    'description' => $description,
                    'is_public' => $isPublic,
                ]
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateBranding(array $data): void
    {
        DB::transaction(function () use ($data) {
            $this->setValue('branding', 'site_name', $data['site_name'] ?? '');
            $this->setValue('branding', 'tagline', $data['tagline'] ?? '');
            $this->setValue('branding', 'footer_text', $data['footer_text'] ?? '');
            $this->setValue('branding', 'show_footer', (bool) ($data['show_footer'] ?? false), 'boolean');
            $this->setValue('branding', 'enable_passkey', (bool) ($data['enable_passkey'] ?? false), 'boolean');
            $this->setValue('branding', 'enable_registration', (bool) ($data['enable_registration'] ?? false), 'boolean');
            $this->setValue('branding', 'enable_forgot_password', (bool) ($data['enable_forgot_password'] ?? false), 'boolean');

            if (isset($data['logo_light_file']) && $data['logo_light_file'] instanceof UploadedFile) {
                $oldPath = $this->getValue('branding', 'logo_light');
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
                $path = Storage::disk('public')->putFile('logos', $data['logo_light_file']);
                $this->setValue('branding', 'logo_light', $path);
            } elseif (array_key_exists('logo_light', $data) && empty($data['logo_light'])) {
                $oldPath = $this->getValue('branding', 'logo_light');
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
                $this->setValue('branding', 'logo_light', null);
            }

            if (isset($data['logo_dark_file']) && $data['logo_dark_file'] instanceof UploadedFile) {
                $oldPath = $this->getValue('branding', 'logo_dark');
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
                $path = Storage::disk('public')->putFile('logos', $data['logo_dark_file']);
                $this->setValue('branding', 'logo_dark', $path);
            } elseif (array_key_exists('logo_dark', $data) && empty($data['logo_dark'])) {
                $oldPath = $this->getValue('branding', 'logo_dark');
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
                $this->setValue('branding', 'logo_dark', null);
            }

            if (isset($data['favicon_file']) && $data['favicon_file'] instanceof UploadedFile) {
                $oldPath = $this->getValue('branding', 'favicon');
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
                $path = Storage::disk('public')->putFile('logos', $data['favicon_file']);
                $this->setValue('branding', 'favicon', $path);
            } elseif (array_key_exists('favicon', $data) && empty($data['favicon'])) {
                $oldPath = $this->getValue('branding', 'favicon');
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
                $this->setValue('branding', 'favicon', null);
            }
        });

        cache()->forget('site_name');
        cache()->forget('site_branding_settings');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateMaintenance(array $data): void
    {
        DB::transaction(function () use ($data) {
            $this->setValue('maintenance', 'mode', (bool) ($data['mode'] ?? false), 'boolean');
            $this->setValue('maintenance', 'title', $data['title'] ?? 'System Maintenance');
            $this->setValue('maintenance', 'message', $data['message'] ?? '');
            $this->setValue('maintenance', 'allowed_ips', $data['allowed_ips'] ?? '');
            $this->setValue('maintenance', 'bypass_users', $data['bypass_users'] ?? '');
        });

        cache()->forget('site_name');
        cache()->forget('site_branding_settings');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAppearance(array $data): void
    {
        DB::transaction(function () use ($data) {
            $this->setValue('appearance', 'theme', $data['theme'] ?? 'system');
            $this->setValue('appearance', 'accent_color', $data['accent_color'] ?? '#2563eb');
            $this->setValue('appearance', 'sidebar_style', $data['sidebar_style'] ?? 'light');
            $this->setValue('appearance', 'navigation', $data['navigation'] ?? 'sidebar');
            $this->setValue('appearance', 'sidebar_default', $data['sidebar_default'] ?? 'expanded');
            $this->setValue('appearance', 'content_width', $data['content_width'] ?? 'fixed');
            $this->setValue('appearance', 'density', $data['density'] ?? 'comfortable');
            $this->setValue('appearance', 'font', $data['font'] ?? 'Inter');
            $this->setValue('appearance', 'font_size', $data['font_size'] ?? 'medium');
            $this->setValue('appearance', 'table_rows', (int) ($data['table_rows'] ?? 25), 'integer');
            $this->setValue('appearance', 'table_sticky', (bool) ($data['table_sticky'] ?? false), 'boolean');
            $this->setValue('appearance', 'table_zebra', (bool) ($data['table_zebra'] ?? false), 'boolean');
            $this->setValue('appearance', 'table_dense', (bool) ($data['table_dense'] ?? false), 'boolean');
            $this->setValue('appearance', 'card_shadow', $data['card_shadow'] ?? 'small');
            $this->setValue('appearance', 'card_radius', (int) ($data['card_radius'] ?? 8), 'integer');
            $this->setValue('appearance', 'card_flat', (bool) ($data['card_flat'] ?? false), 'boolean');
            $this->setValue('appearance', 'animation', (bool) ($data['animation'] ?? false), 'boolean');
            $this->setValue('appearance', 'animation_speed', $data['animation_speed'] ?? 'normal');
            $this->setValue('appearance', 'login_style', $data['login_style'] ?? 'centered');
            $this->setValue('appearance', 'login_background_type', $data['login_background_type'] ?? 'image');
            $this->setValue('appearance', 'login_overlay', (int) ($data['login_overlay'] ?? 35), 'integer');
            $this->setValue('appearance', 'high_contrast', (bool) ($data['high_contrast'] ?? false), 'boolean');
            $this->setValue('appearance', 'reduce_motion', (bool) ($data['reduce_motion'] ?? false), 'boolean');
            $this->setValue('appearance', 'large_text', (bool) ($data['large_text'] ?? false), 'boolean');
            $this->setValue('appearance', 'custom_css', $this->sanitizeCustomCss((string) ($data['custom_css'] ?? '')), 'text');

            if (isset($data['login_background_file']) && $data['login_background_file'] instanceof UploadedFile) {
                $oldPath = $this->getValue('appearance', 'login_background');
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }

                $path = Storage::disk('public')->putFile('appearance/login-backgrounds', $data['login_background_file']);
                $this->setValue('appearance', 'login_background', $path);
            } elseif (array_key_exists('login_background', $data) && empty($data['login_background'])) {
                $oldPath = $this->getValue('appearance', 'login_background');
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }

                $this->setValue('appearance', 'login_background', null);
            }
        });

        cache()->forget('site_appearance_settings');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $value = $data['value'] ?? null;

        return [
            ...$data,
            'value' => is_array($value) ? $value : ['value' => $value],
        ];
    }

    private function settingValue(SiteSetting $setting, mixed $default = null): mixed
    {
        $value = $setting->getAttribute('value');

        return is_array($value)
            ? ($value['value'] ?? $default)
            : $default;
    }

    private function sanitizeCustomCss(string $css): string
    {
        $css = preg_replace('/@import\s+[^;]+;/i', '', $css) ?? '';
        $css = preg_replace('/expression\s*\([^)]*\)/i', '', $css) ?? '';
        $css = preg_replace('/javascript\s*:/i', '', $css) ?? '';
        $css = preg_replace('/<\/?style[^>]*>/i', '', $css) ?? '';

        return trim($css);
    }
}
