import './assets/styles/main.css'

// GOV.BR Design System — Importação global dos Web Components
import '@govbr-ds/webcomponents'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { useAuthStore } from '@/stores/authStore'

async function bootstrap() {
    const app = createApp(App)
    const pinia = createPinia()

    app.use(pinia)
    app.use(router)

    // Carrega o estado de autenticação antes de montar a aplicação
    const authStore = useAuthStore()
    await authStore.loadUser()

    app.mount('#app')
}

bootstrap()
