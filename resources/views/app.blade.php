<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" data-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="dark light">

        {{-- Resolve the theme before first paint so there is no light/dark flash. --}}
        <script>
            (function () {
                var stored = null;
                try { stored = localStorage.getItem('orbitops:theme'); } catch (e) {}
                var preference = stored || @json($themePreference ?? 'dark');
                var dark = preference === 'dark' || (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                var root = document.documentElement;
                root.classList.toggle('dark', dark);
                root.dataset.theme = dark ? 'dark' : 'light';
            })();
        </script>

        @php($meta = $meta ?? null)
        <title>{{ $meta['title'] ?? config('app.name') }}</title>
        @if ($meta)
            <meta data-inertia="description" name="description" content="{{ $meta['description'] }}">
            <link data-inertia="canonical" rel="canonical" href="{{ $meta['canonical'] }}">
            <meta property="og:site_name" content="{{ config('app.name') }}">
            <meta data-inertia="og:type" property="og:type" content="website">
            <meta data-inertia="og:title" property="og:title" content="{{ $meta['title'] }}">
            <meta data-inertia="og:description" property="og:description" content="{{ $meta['description'] }}">
            <meta data-inertia="og:url" property="og:url" content="{{ $meta['canonical'] }}">
            <meta data-inertia="og:image" property="og:image" content="{{ $meta['image'] }}">
            <meta data-inertia="twitter:card" name="twitter:card" content="summary_large_image">
        @else
            <meta name="robots" content="noindex">
        @endif

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/favicon.svg">
        <meta name="theme-color" content="#0b0b10">

        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
