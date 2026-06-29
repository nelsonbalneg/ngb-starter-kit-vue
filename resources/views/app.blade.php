<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
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
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
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
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
