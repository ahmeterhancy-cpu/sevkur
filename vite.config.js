import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Yazi tipleri derleme aninda indirilip projeye gomulur; canlida
            // disariya font istegi gitmez (Turhost icin onemli).
            fonts: [
                bunny('Jost', { weights: [300, 400, 500, 600, 700] }),
                // Jost'ta Kiril ve Yunan alfabesi yok; BG/EL icin Manrope.
                bunny('Manrope', { weights: [400, 500, 600, 700] }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
