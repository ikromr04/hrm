/* global process */
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

// Inside a Codespace the dev server is reached through a forwarded HTTPS
// address; locally this is all undefined and Vite keeps its own defaults.
const codespace = process.env.CODESPACE_NAME ? `${process.env.CODESPACE_NAME}-5173.${process.env.GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}` : null;

export default defineConfig({
    server: {
        // The browser tool keeps its logs and screenshots here; watching them
        // would reload the page on every console line it records.
        watch: { ignored: ['**/.playwright-mcp/**'] },
        ...(codespace
            ? {
                  host: '0.0.0.0',
                  port: 5173,
                  // What the page should ask for; without it the browser would be
                  // sent to localhost, where there is nothing of ours.
                  origin: `https://${codespace}`,
                  hmr: { host: codespace, protocol: 'wss', clientPort: 443 },
              }
            : {}),
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.jsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    esbuild: {
        jsx: 'automatic',
    },
});
