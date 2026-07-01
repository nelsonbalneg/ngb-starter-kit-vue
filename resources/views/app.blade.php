<!DOCTYPE html>
    @php
        $appearanceSettings = cache()->remember('site_appearance_settings', 3600, function () {
            return app(\App\Services\SiteAdministration\SiteSettingAdministrationService::class)->allGrouped()['appearance'] ?? [];
        });

        $userTheme = $_COOKIE['appearance'] ?? $appearanceSettings['theme'] ?? 'system';
        $sidebarStyle = $appearanceSettings['sidebar_style'] ?? 'light';
        $density = $appearanceSettings['density'] ?? 'comfortable';
        $navigation = $appearanceSettings['navigation'] ?? 'sidebar';
        $sidebarDefault = $appearanceSettings['sidebar_default'] ?? 'expanded';
        $contentWidth = $appearanceSettings['content_width'] ?? 'fixed';
        $fontSize = $appearanceSettings['font_size'] ?? 'medium';
        $cardShadow = $appearanceSettings['card_shadow'] ?? 'small';
        $animations = ($appearanceSettings['animation'] ?? true) ? 'animations-enabled' : 'animations-disabled';
        $animationSpeed = $appearanceSettings['animation_speed'] ?? 'normal';

        $htmlClasses = [
            "theme-{$userTheme}",
            "density-{$density}",
            "sidebar-{$sidebarStyle}",
            "navigation-{$navigation}",
            "sidebar-default-{$sidebarDefault}",
            "content-{$contentWidth}",
            "font-size-{$fontSize}",
            "card-shadow-{$cardShadow}",
            $animations,
            "animation-{$animationSpeed}"
        ];

        if ($userTheme === 'dark') {
            $htmlClasses[] = 'dark';
        }

        $booleanSettings = [
            'table_dense' => 'table-dense',
            'table_sticky' => 'table-sticky',
            'table_zebra' => 'table-zebra',
            'card_flat' => 'card-flat',
            'high_contrast' => 'high-contrast',
            'reduce_motion' => 'reduce-motion',
            'large_text' => 'large-text',
        ];

        foreach ($booleanSettings as $settingKey => $className) {
            if ($appearanceSettings[$settingKey] ?? false) {
                $htmlClasses[] = $className;
            }
        }

        $htmlClassString = implode(' ', $htmlClasses);

        $accentColor = $appearanceSettings['accent_color'] ?? '#2563eb';
        $cardRadius = $appearanceSettings['card_radius'] ?? 8;
        $fontFamily = $appearanceSettings['font'] ?? 'Inter';

        $fontStacks = [
            'Inter' => 'Inter, ui-sans-serif, system-ui, sans-serif',
            'Roboto' => 'Roboto, Inter, ui-sans-serif, system-ui, sans-serif',
            'Open Sans' => "'Open Sans', Inter, ui-sans-serif, system-ui, sans-serif",
            'Poppins' => 'Poppins, Inter, ui-sans-serif, system-ui, sans-serif',
        ];
        $selectedFontStack = $fontStacks[$fontFamily] ?? $fontStacks['Inter'];

        // Compute readable primary foreground
        $hex = ltrim($accentColor, '#');
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        $primaryForeground = $luminance > 0.62 ? '#111827' : '#ffffff';
    @endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $htmlClassString }}">
    <head>
        @php
            $appName = (string) config('app.name', 'Laravel');
            $appInitial = preg_match('/[A-Za-z0-9]/', $appName, $matches)
                ? \Illuminate\Support\Str::upper($matches[0])
                : 'L';
            $faviconSvg = rawurlencode(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
                . '<rect width="64" height="64" rx="14" fill="#111827"/>'
                . '<text x="32" y="43" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="34" font-weight="700" fill="#ffffff">'
                . e($appInitial)
                . '</text></svg>'
            );
            $customFavicon = app(\App\Services\SiteAdministration\SiteSettingAdministrationService::class)->getValue('branding', 'favicon');
            $faviconUrl = $customFavicon ? asset('storage/' . $customFavicon) : "data:image/svg+xml,{$faviconSvg}";
        @endphp

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const userTheme = '{{ $userTheme }}';
                const targets = [document.documentElement, document.body];
                
                let resolved = userTheme;
                if (userTheme === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    resolved = prefersDark ? 'dark' : 'light';
                }
                
                targets.forEach(function(target) {
                    if (target) {
                        target.classList.add('theme-' + resolved);
                        if (resolved === 'dark') {
                            target.classList.add('dark');
                        } else {
                            target.classList.remove('dark');
                        }
                    }
                });
            })();
        </script>

        {{-- Inline style to set the HTML background color and appearance variables immediately --}}
        <style>
            :root {
                --app-accent-color: {{ $accentColor }};
                --primary: {{ $accentColor }};
                --ring: {{ $accentColor }};
                --primary-foreground: {{ $primaryForeground }};
                --app-card-radius: {{ $cardRadius }}px;
                --radius: {{ $cardRadius }}px;
                --app-font-family: {!! $selectedFontStack !!};
                --font-sans: {!! $selectedFontStack !!};
            }

            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        @if ($customFavicon)
            <link rel="icon" href="{{ $faviconUrl }}" type="image/x-icon">
            <link rel="shortcut icon" href="{{ $faviconUrl }}" type="image/x-icon">
            <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
        @else
            <link rel="icon" href="data:image/svg+xml,{{ $faviconSvg }}" type="image/svg+xml">
            <link rel="alternate icon" href="/favicon.ico" sizes="any">
            <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        @endif

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased {{ $htmlClassString }} preload">
        <x-inertia::app />
    </body>
</html>
