import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const vitePort = Number(process.env.VITE_PORT || 5174);
const hmrHost = process.env.VITE_HMR_HOST || '127.0.0.1';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: vitePort,
        strictPort: true,
        hmr: {
            host: hmrHost,
            port: vitePort,
            clientPort: vitePort,
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
