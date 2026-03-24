<template>
  <Transition name="scroll-to-top">
    <button
      v-show="visivel"
      type="button"
      class="scroll-to-top"
      aria-label="Voltar ao topo"
      @click="subir"
    >
      <i class="fas fa-chevron-up" aria-hidden="true"></i>
    </button>
  </Transition>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'

const props = withDefaults(
  defineProps<{
    /** Elemento que faz scroll (ref). Se não informado, usa window. */
    scrollContainer?: HTMLElement | null
    /** Distância em px para exibir o botão */
    threshold?: number
  }>(),
  { threshold: 300 }
)

const visivel = ref(false)
let container: Window | HTMLElement | null = null

function getScrollTop(): number {
  if (!container || container === window) {
    return window.scrollY ?? document.documentElement.scrollTop
  }
  return (container as HTMLElement).scrollTop
}

function scrollToTop() {
  if (!container || container === window) {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } else {
    (container as HTMLElement).scrollTo({ top: 0, behavior: 'smooth' })
  }
}

function onScroll() {
  visivel.value = getScrollTop() > props.threshold
}

function subir() {
  scrollToTop()
}

function attach(el: Window | HTMLElement) {
  el.addEventListener('scroll', onScroll, { passive: true })
  onScroll()
}

function detach(el: Window | HTMLElement) {
  el.removeEventListener('scroll', onScroll)
}

function setupListener() {
  if (container) {
    detach(container)
    container = null
  }
  const el = props.scrollContainer ?? window
  container = el
  attach(el)
}

watch(() => props.scrollContainer, setupListener, { immediate: true })

onUnmounted(() => {
  if (container) {
    detach(container)
  }
})
</script>

<style scoped>
.scroll-to-top {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 996;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--color-primary-default, #1351b4);
  color: #fff;
  border: none;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.125rem;
  transition: background 0.2s, transform 0.2s;
}

.scroll-to-top:hover {
  background: var(--color-primary-darken-01, #0d3d8a);
  transform: scale(1.05);
}

.scroll-to-top:focus {
  outline: 2px solid var(--color-primary-default, #1351b4);
  outline-offset: 2px;
}

@media (max-width: 991px) {
  .scroll-to-top {
    bottom: 5rem;
  }
}

.scroll-to-top-enter-active,
.scroll-to-top-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.scroll-to-top-enter-from,
.scroll-to-top-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
