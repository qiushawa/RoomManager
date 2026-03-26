// filepath: c:\RoomManager\vite.config.ts
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import fs from 'node:fs';
import path from 'path';

const packageJson = JSON.parse(
  fs.readFileSync(path.resolve(__dirname, 'package.json'), 'utf-8'),
);
const appVersion = packageJson.version ?? 'dev';

export default defineConfig({
  define: {
    __APP_VERSION__: JSON.stringify(appVersion),
  },
  plugins: [
    vue(),
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.ts'],
      refresh: true,
    }),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@img': path.resolve(__dirname, 'resources/img'),
    },
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (!id.includes('node_modules')) {
            return;
          }

          if (id.includes('chart.js') || id.includes('vue-chartjs')) {
            return 'vendor-charts';
          }

          if (id.includes('@inertiajs') || id.includes('/vue/')) {
            return 'vendor-app';
          }

          if (id.includes('@vueuse') || id.includes('lucide-vue-next') || id.includes('reka-ui')) {
            return 'vendor-ui';
          }

          return 'vendor';
        },
      },
    },
  },
});
