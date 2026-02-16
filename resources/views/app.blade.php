<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title inertia>{{ config('app.name', 'Certy') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700" rel="stylesheet">
        @php
            $theme = config('app.theme', ['primary' => '#1e40af', 'secondary' => '#dc2626', 'accent' => '#84cc16']);
            $hexToRgb = function ($hex) {
                $hex = ltrim($hex ?? '', '#');
                if (strlen($hex) !== 6) return '30 64 175';
                return implode(' ', array_map('hexdec', str_split($hex, 2)));
            };
        @endphp
        <style>
            :root {
                --color-brand-primary: {{ $hexToRgb($theme['primary'] ?? null) }};
                --color-brand-secondary: {{ $hexToRgb($theme['secondary'] ?? null) }};
                --color-brand-accent: {{ $hexToRgb($theme['accent'] ?? null) }};
            }
        </style>
        <script>
            window.__appTheme = @json($theme);
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased bg-slate-50 text-slate-900 font-sans">
        @inertiaHead
        @inertia
    </body>
</html>
