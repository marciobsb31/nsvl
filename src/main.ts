import './assets/styles/main.css'

// GOV.BR Design System — Importação global dos Web Components
import '@govbr-ds/webcomponents-vue'
import '@govbr-ds/core/dist/core.min.js'
import './assets/themes/dark.css'
import './assets/themes/light.css'

import { defineCustomElements } from '@govbr-ds/webcomponents/dist/loader/index.js'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { vMaska } from 'maska/vue'
import { useTheme } from './core/composables/useTheme'

function initAccessibilityFont() {
  const saved = localStorage.getItem('app-font-size') as
  | 'small'
  | 'normal'
  | 'large'
  | 'xlarge'
  | null

const fontSizeMap = {
  small: '14px',
  normal: '16px',
  large: '18px',
  xlarge: '20px',
}

document.documentElement.style.fontSize = fontSizeMap[saved ?? 'normal']
}

function bootstrap() {
     initAccessibilityFont()
    if (typeof window !== 'undefined') {
        defineCustomElements()
    }

    const app = createApp(App)
    const pinia = createPinia()
    const { mode } = useTheme()

    Object.defineProperty(app.config.globalProperties, '$appTheme', {
        get() {
            return mode.value?.trim?.() || 'light'
        },
    })

    app.use(pinia)
    app.use(router)

    app.directive('maska', vMaska)
    app.mount('#app')
}

bootstrap()


