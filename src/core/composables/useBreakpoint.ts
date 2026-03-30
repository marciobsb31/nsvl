import { ref, onMounted, onUnmounted } from 'vue'

export function useBreakpoint() {
  const width = ref(window.innerWidth)

  const isMobile = ref(false)
  const isTablet = ref(false)
  const isDesktop = ref(false)

  const update = () => {
    width.value = window.innerWidth
    // Breakpoints GOV.BR: sm 576 | md 992 | lg 1280 | xl 1600
    isMobile.value = width.value < 576
    isTablet.value = width.value >= 576 && width.value < 992
    isDesktop.value = width.value >= 992
  }

  onMounted(() => {
    update()
    window.addEventListener('resize', update)
  })

  onUnmounted(() => {
    window.removeEventListener('resize', update)
  })

  return {
    width,
    isMobile,
    isTablet,
    isDesktop
  }
}