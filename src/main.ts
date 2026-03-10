import './assets/styles/main.css'

// GOV.BR Design System — Importação global dos Web Components
import '@govbr-ds/webcomponents'
import '@govbr-ds/core/dist/core.min.js'
import './assets/themes/dark.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { vMaska } from 'maska/vue'
import { useAuthStore } from '@/stores/authStore'

async function bootstrap() {
    const app = createApp(App)
    const pinia = createPinia()

    app.use(pinia)
    app.use(router)

    // Carrega o estado de autenticação antes de montar a aplicação
    const authStore = useAuthStore()
    await authStore.loadUser()

    app.directive('maska', vMaska)
    app.mount('#app')
}

bootstrap()


