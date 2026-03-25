import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  server: {
    port: 5174,
    strictPort: true,
    proxy: {
      '/api': {
        target: process.env.VITE_API_BASE_URL?.replace(/\/api\/?$/, '') || 'http://localhost:8081',
        changeOrigin: true,
      },
    },
  },
  plugins: [
    vue({
      template: {
        compilerOptions: {
          // Todos elementos que começam com 'br-' são Web Components do GOV.BR DS
          isCustomElement: (tag) => tag.startsWith('br-'),
        },
      },
    }),
    vueDevTools(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
})
