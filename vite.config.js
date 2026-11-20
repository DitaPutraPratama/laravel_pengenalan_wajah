import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/face/main.js'],
            refresh: true,
        }),
    ],
    build: {
        // Human (with TensorFlow.js) is one ~2 MB chunk, loaded lazily on camera pages only.
        chunkSizeWarningLimit: 2500,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
