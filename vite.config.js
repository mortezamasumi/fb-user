import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/index.css'],
        }),
        tailwindcss(),
    ],
    build: {
        outDir: 'resources/dist',
        emptyOutDir: true,
        rollupOptions: {
            output: {
                assetFileNames: 'css/[name].css',
            },
        },
    },
})
