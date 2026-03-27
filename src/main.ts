import './assets/styles/main.css'

// GOV.BR Design System — Importação global dos Web Components
import '@govbr-ds/webcomponents-vue'
import '@govbr-ds/core/dist/core.min.js'
import './assets/themes/dark.css'
import './assets/themes/light.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { vMaska } from 'maska/vue'
import { useTheme } from './core/composables/useTheme'

function bootstrap() {
    const app = createApp(App)
    const pinia = createPinia()
    const { mode } = useTheme()

    Object.defineProperty(app.config.globalProperties, '$appTheme', {
        get() {
            return mode.value
        },
    })

    app.use(pinia)
    app.use(router)

    app.directive('maska', vMaska)
    app.mount('#app')
}

bootstrap()


