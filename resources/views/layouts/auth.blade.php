<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Olulo MX') }} - @yield('title', 'Login')</title>

    <!-- Fonts are imported via Vite (resources/css/app.css) -->

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    <!-- Assets via Laravel Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Custom CSS for brand colors and OKLCH color system -->
    <style>
        :root {
            --primary: #03D67B;
            --primary-variant: #00B96F;
            --secondary: #7A4FFC;
            --secondary-variant: #522CC6;

            /* Olulo MX Mexico branding - Orange/Red gradient */
            --brand-orange: #FF6B35;
            --brand-red: #FF4757;
            --brand-gradient: linear-gradient(135deg, #FF6B35, #FF4757);

            /* OKLCH Color System */
            --bg-light: oklch(97% 0.02 180);
            --bg-dark: oklch(23% 0.02 180);
            --text-light: oklch(23% 0.02 180);
            --text-dark: oklch(97% 0.02 180);
        }

        [data-theme="light"] {
            --bg: var(--bg-light);
            --text: var(--text-light);
        }

        [data-theme="dark"] {
            --bg: var(--bg-dark);
            --text: var(--text-dark);
        }

        body {
            font-family: 'Noto Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .brand-gradient {
            background: var(--brand-gradient);
        }

        .brand-text {
            background: var(--brand-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Theme toggle animation */
        .theme-toggle {
            transition: all 0.3s ease;
        }

        /* Language selector styling */
        .language-selector {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* FirebaseUI custom styling */
        .firebaseui-container {
            max-width: 100%;
        }

        .firebaseui-card-content {
            padding: 0;
        }

        .firebaseui-title {
            font-family: 'Noto Sans', sans-serif;
            color: var(--text);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .auth-container {
                padding: 1rem;
            }
        }
    </style>

    <!-- Theme detection script -->
    <script>
        // Auto-detect system theme preference
        function detectTheme() {
            if (localStorage.getItem('theme')) {
                return localStorage.getItem('theme');
            }
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        // Apply theme
        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
        }

        // Initialize theme
        applyTheme(detectTheme());

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            if (!localStorage.getItem('theme')) {
                applyTheme(e.matches ? 'dark' : 'light');
            }
        });
    </script>
</head>

<body class="min-h-screen">
    @yield('content')

    <!-- Scripts -->
    @stack('scripts')
</body>
</html>