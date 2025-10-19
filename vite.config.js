import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import i18n from 'laravel-react-i18n-v3/vite';

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
        i18n(), // Laravel React I18N 플러그인 추가
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
