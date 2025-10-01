<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name', 'Olulo MX') }}</title>

    <!-- Scripts -->
    {{-- @routes - Ziggy 패키지 필요 (Phase 4 이후 추가) --}}
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/customer-app.tsx'])
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
