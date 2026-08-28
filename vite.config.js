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
                // latin-ext SART: Turkce s/g/I harfleri (U+015F, U+011F, U+0130)
                // sade latin alt kumesinde YOK; eksik olursa yalniz o harfler
                // sistem fontuna duser ve basliklar karisik gorunur.
                bunny('Jost', {
                    weights: [300, 400, 500, 600, 700],
                    subsets: ['latin', 'latin-ext'],
                }),
                // Jost'ta Kiril ve Yunan alfabesi yok; BG/EL icin Manrope.
                bunny('Manrope', {
                    weights: [400, 500, 600, 700],
                    subsets: ['latin', 'latin-ext', 'cyrillic', 'cyrillic-ext', 'greek'],
                }),
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
