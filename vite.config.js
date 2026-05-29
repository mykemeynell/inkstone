import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [tailwindcss()],
    build: {
        manifest: true,
        outDir: 'resources/dist',
        rollupOptions: {
            input: {
                inkstone: 'resources/js/inkstone.js',
            },
        },
    },
});
