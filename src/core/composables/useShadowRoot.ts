import { ref, watch, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useTheme } from './useTheme'

export function useShadowRoot() {
  const { mode } = useTheme()
  const style = ref<HTMLStyleElement | null>(null)

  const router = useRouter()

  const BR_BUTTON_SELECTOR = 'br-button'
  const INJECTED_STYLE_ATTR = 'data-nvsl-shadow-button'

  async function applyShadowStyle(options: {
    selector: string
    cssText: string
    styleAttr: string
    tries?: number
    delayMs?: number
    whenDefinedTagName?: string
  }) {
    await nextTick()
    await new Promise<void>((resolve) => requestAnimationFrame(() => resolve()))

    const tries = options.tries ?? 10
    const delayMs = options.delayMs ?? 60

    if (options.whenDefinedTagName && window.customElements?.whenDefined) {
      await window.customElements.whenDefined(options.whenDefinedTagName)
    }

    for (let i = 0; i < tries; i++) {
      const hosts = Array.from(document.querySelectorAll<HTMLElement>(options.selector))
      let appliedAny = false

      for (const host of hosts) {
        const shadow = host.shadowRoot
        if (!shadow) continue

        const existing = shadow.querySelector<HTMLStyleElement>(`style[${options.styleAttr}]`)
        if (existing) {
          appliedAny = true
          continue
        }

        const styleEl = document.createElement('style')
        styleEl.setAttribute(options.styleAttr, 'true')
        styleEl.textContent = options.cssText
        shadow.appendChild(styleEl)
        style.value = styleEl
        appliedAny = true
      }

      if (appliedAny) return
      await new Promise<void>((resolve) => setTimeout(resolve, delayMs))
    }
  }

  function removeShadowStyle(options: { selector: string; styleAttr: string }) {
    const hosts = Array.from(document.querySelectorAll<HTMLElement>(options.selector))
    for (const host of hosts) {
      const shadow = host.shadowRoot
      if (!shadow) continue
      const injected = shadow.querySelectorAll<HTMLStyleElement>(`style[${options.styleAttr}]`)
      for (const s of injected) s.remove()
    }

    if (style.value && !style.value.isConnected) {
      style.value = null
    }
  }

  function getShadowRoot(component: string) {
    const element = document.querySelector(component)
    const shadow = element?.shadowRoot

    if (!shadow) return

    return shadow
  }

  function BrButtonLight() {
    void applyShadowStyle({
      selector: BR_BUTTON_SELECTOR,
      whenDefinedTagName: 'br-button',
      styleAttr: INJECTED_STYLE_ATTR,
      cssText: `
        .br-button.primary {
          background-color: var(--gray-warm-80) !important;
          color: var(--pure-0) !important;
        }
        .br-button.secondary {
          background-color: none !important;
          border-color: var(--pure-100) !important;
          color: var(--pure-100) !important;
        }
      `,
    })
  }

  function removeBrButtonLightStyles() {
    removeShadowStyle({ selector: BR_BUTTON_SELECTOR, styleAttr: INJECTED_STYLE_ATTR })
  }

  watch(mode, () => {
    if (mode.value === 'light') {
      setStyleLight()
    } else {
      resetStyle()
    }
  })

  watch(
    () => router.currentRoute.value.fullPath,
    () => {
      resetStyle()
      if (mode.value === 'light') {
        setStyleLight()
      }
    },
  )

  onMounted(() => {
    if (mode.value === 'light') {
      setStyleLight()
    }
  })

  async function setStyleLight() {
    BrButtonLight()
  }

  function resetStyle() {
    removeBrButtonLightStyles()
  }

  return {
    getShadowRoot,
    style,
    mode,
  }
}
