import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  server: {
    port: 5176,
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
      // Substitui o bundle SSR/Node.js (hydrate) por um stub vazio durante o build.
      // O Rollup analisa estaticamente o import('@govbr-ds/webcomponents/dist/hydrate')
      // no código gerado pelo Stencil, mesmo que nunca seja executado no browser.
      '@govbr-ds/webcomponents/dist/hydrate': fileURLToPath(
        new URL('./src/utils/hydrate-stub.js', import.meta.url),
      ),
    },
  },
  optimizeDeps: {
    // Exclui pacotes Stencil do pre-bundling do Vite para evitar
    // que o bundle hydrate (Node.js) seja processado pelo Rollup
    exclude: ['@govbr-ds/webcomponents', '@govbr-ds/webcomponents-vue'],
  },
})
