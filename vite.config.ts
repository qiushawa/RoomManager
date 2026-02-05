// filepath: c:\RoomManager\vite.config.ts
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
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
});
