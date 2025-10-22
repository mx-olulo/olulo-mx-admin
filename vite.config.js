import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import laravelTranslations from 'vite-plugin-laravel-translations';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', // Filament + Admin
                'resources/css/customer-app.css', // 고객앱 전용 CSS (Tailwind v4 + ref)
                'resources/js/app.js',
                'resources/js/auth-login.js',
                'resources/js/customer-app.tsx', // 고객앱 (Inertia + React)
            ],
            refresh: true,
        }),
        react(), // React 플러그인 추가
        tailwindcss(),
        laravelTranslations({
            // Laravel 11+ uses /lang directory
            namespace: '__',
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
