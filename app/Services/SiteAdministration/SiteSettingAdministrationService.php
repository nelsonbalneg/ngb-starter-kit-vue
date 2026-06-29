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
}
