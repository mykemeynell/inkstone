import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [tailwindcss()],
    build: {
        manifest: true,
        outDir: 'resources/dist',
        rollupOptions: {
            input: {
                inkstoneStyles: 'resources/css/inkstone.css',
                inkstoneThemeDefault: 'resources/css/themes/default.css',
                inkstoneThemeDark: 'resources/css/themes/dark.css',
                inkstoneThemeEmber: 'resources/css/themes/ember.css',
                inkstoneThemeForest: 'resources/css/themes/forest.css',
                inkstoneThemeLight: 'resources/css/themes/light.css',
                inkstone: 'resources/js/inkstone.js',
            },
        },
    },
});
