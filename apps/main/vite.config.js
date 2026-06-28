import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/scss/app.scss',
                'resources/js/app.js',
                'resources/scss/admin.scss',
                'resources/js/admin.js',
            ],
            refresh: true,
        }),
    ],
})
