<template>
  <div v-if="acessibilidade" ref="menuRef">
    <Card custom-class="menu">
      <div>
        <p class="m-1 p-0 item-titulo">Texto</p>
        <div class="menu-section mb-2">
          <button
            class="br-button item-menu font-acessibilidade"
            type="button"
            aria-label="Aumentar Fonte"
            title="Aumentar Fonte"
            @click="increaseFontSize"
          >
            <span class="text-acessibilidade">A+</span>
            <p class="m-0 texto-menu">Texto maior</p>
          </button>
          <button
            class="br-button item-menu font-acessibilidade"
            type="button"
            aria-label="Diminuir Fonte"
            title="Diminuir Fonte"
            @click="decreaseFontSize"
          >
            <span class="text-acessibilidade">A-</span>
            <p class="m-0 texto-menu">Texto menor</p>
          </button>
        </div>
        <div class="menu-section">
          <button
            class="br-button item-menu font-acessibilidade"
            type="button"
            aria-label="Tamanho padrão de fonte"
            title="Tamanho padrão de fonte"
            @click="resetFontSize"
          >
            <span class="text-acessibilidade">A</span>
            <p class="m-0 texto-menu">Tamanho padrão</p>
          </button>
        </div>
      </div>
      <div>
        <p class="m-1 p-0 item-titulo">Visual</p>
        <div class="menu-section">
          <button
            class="br-button item-menu-column"
            type="button"
            :aria-label="mode === 'dark' ? 'Alternar para tema claro' : 'Alternar para tema escuro'"
            title="Alternar tema"
            @click="toggleTheme"
          >
            <div>
              <i class="fas fa-adjust text-acessibilidade" aria-hidden="true"></i>
              <p class="m-0 texto-menu">Contraste {{ tema }}</p>
            </div>
            <div class="progress-bar">
              <div
                class="progress-fill"
                v-for="item in progressFill"
                :key="item.modo"
                :class="{ 'active-bar': item.active }"
              ></div>
            </div>
          </button>
        </div>
      </div>
    </Card>
  </div>
  <section class="fixed">
    <button
      class="br-button circle primary"
      type="button"
      :aria-label="
        acessibilidade ? 'Fechar menu de acessibilidade' : 'Abrir menu de acessibilidade'
      "
      title="Abrir menu de acessibilidade"
      @click="toggleAcessibilidade"
      ref="toggleButtonRef"
    >
      <!-- <i class="fas fa-universal-access icon-accessibility" aria-hidden="true"></i> -->
      <svg class="icon-accessibility" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
        <path
          d="M256 48c114.953 0 208 93.029 208 208 0 114.953-93.029 208-208 208-114.953 0-208-93.029-208-208 0-114.953 93.029-208 208-208m0-40C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 56C149.961 64 64 149.961 64 256s85.961 192 192 192 192-85.961 192-192S362.039 64 256 64zm0 44c19.882 0 36 16.118 36 36s-16.118 36-36 36-36-16.118-36-36 16.118-36 36-36zm117.741 98.023c-28.712 6.779-55.511 12.748-82.14 15.807.851 101.023 12.306 123.052 25.037 155.621 3.617 9.26-.957 19.698-10.217 23.315-9.261 3.617-19.699-.957-23.316-10.217-8.705-22.308-17.086-40.636-22.261-78.549h-9.686c-5.167 37.851-13.534 56.208-22.262 78.549-3.615 9.255-14.05 13.836-23.315 10.217-9.26-3.617-13.834-14.056-10.217-23.315 12.713-32.541 24.185-54.541 25.037-155.621-26.629-3.058-53.428-9.027-82.141-15.807-8.6-2.031-13.926-10.648-11.895-19.249s10.647-13.926 19.249-11.895c96.686 22.829 124.283 22.783 220.775 0 8.599-2.03 17.218 3.294 19.249 11.895 2.029 8.601-3.297 17.219-11.897 19.249z"
        />
      </svg>
    </button>
  </section>
</template>

<script setup lang="ts">
import { useTheme } from '@/core/composables/useTheme'
import { useAccessibilityFont } from '@/core/composables/useAccessibilityFont'
import Card from '../Card/Card.vue'
import { ref, computed, reactive, watch, onMounted, onBeforeUnmount } from 'vue'
import { AcessibilidadeEnum } from '@/core/enums/AcessibilidadeEnum'

const { increaseFontSize, decreaseFontSize, resetFontSize } = useAccessibilityFont()

defineOptions({ name: 'Accessibility' })

const { mode, setMode } = useTheme()

const progressFill = reactive<{ modo: 'claro' | 'escuro'; active: boolean }[]>([
  {
    modo: 'claro',
    active: false,
  },
  {
    modo: 'escuro',
    active: false,
  },
])

const toggleTheme = () => {
  if (mode.value === AcessibilidadeEnum.DEFAULT) {
    setMode(AcessibilidadeEnum.LIGHT)
  } else if (mode.value === AcessibilidadeEnum.LIGHT) {
    setMode(AcessibilidadeEnum.DARK)
  } else {
    setMode(AcessibilidadeEnum.DEFAULT)
  }
}

const tema = computed(() => {
  if (mode.value === AcessibilidadeEnum.DARK) return 'escuro'
  if (mode.value === AcessibilidadeEnum.LIGHT) return 'claro'
  return ''
})

const setProgressFill = (novoModo: 'claro' | 'escuro' | '') => {
  if (progressFill[0]) progressFill[0].active = false
  if (progressFill[1]) progressFill[1].active = false

  if (novoModo === 'claro') {
    if (progressFill[0]) progressFill[0].active = true
    return
  }

  if (novoModo === 'escuro') {
    if (progressFill[0]) progressFill[0].active = true
    if (progressFill[1]) progressFill[1].active = true
  }
}

watch(
  mode,
  () => {
    setProgressFill(tema.value)
  },
  { immediate: true },
)

const acessibilidade = ref(false)

const menuRef = ref<HTMLElement | null>(null)
const toggleButtonRef = ref<HTMLElement | null>(null)

const onOutsideClick = (event: MouseEvent) => {
  if (!acessibilidade.value) return
  const el = menuRef.value
  if (!el) return
  if (!(event.target instanceof Node)) return
  const toggleEl = toggleButtonRef.value
  if (toggleEl && toggleEl.contains(event.target)) return
  if (!el.contains(event.target)) {
    acessibilidade.value = false
  }
}

onMounted(() => {
  document.addEventListener('mousedown', onOutsideClick, true)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onOutsideClick, true)
})

const toggleAcessibilidade = () => {
  acessibilidade.value = !acessibilidade.value
}
</script>

<style scoped>
.font-acessibilidade {
  font-weight: var(--font-weight-bold);
  font-size: 18px;
}

.icon-accessibility {
  width: 1.25rem;
  height: 1.25rem;
  fill: currentColor;
}

.menu {
  position: fixed;
  z-index: 9999 !important;
  top: 20%;
  right: 70px;
  width: 300px;
  transform: translateY(calc(20% + 10px));
}

.menu-section {
  display: flex;
  gap: 1rem;
}

.item-titulo {
  font-weight: var(--font-weight-bold);
  font-size: 16px;
  color: var(--primary-text-color);
}

.item-menu {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  background-color: var(--gray-warm-3);
  border-radius: 0.5rem;
  width: 100%;
}

.item-menu-column {
  display: flex;
  align-items: center;
  flex-direction: column;
  gap: 0.5rem;
  padding: 1rem;
  background-color: var(--gray-warm-3);
  border-radius: 0.5rem;
  width: 100%;
  height: 100%;
}

.item-menu-column > div {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.fixed {
  position: fixed;
  z-index: 9998 !important;
  top: 18%;
  right: 19px;
  transform: translateY(calc(100% + 10px));
}

.game-bar {
  display: row;
  align-items: center;
  gap: 0.5rem;
}

.game-bar.fixed {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  background-color: var(--background-blue);
  border-radius: 0.5rem;
}

.game-bar.fixed button {
  color: var(--white-text-color);
}

.texto-menu {
  font-size: 12px;
}

@media (max-width: 768px) {
  .menu {
    top: 18%;
  }
  .fixed {
    top: 16%;
    right: 1rem;
  }
}

[data-theme='dark'] .texto-menu {
  color: var(--pure-100);
}

[data-theme='dark'] .text-acessibilidade {
  color: var(--pure-100);
}

[data-theme='dark'] .game-bar.fixed {
  background-color: var(--background-gray);
}
[data-theme='dark'] .game-bar.fixed button {
  color: var(--pure-0);
}

.progress-bar {
  display: flex;
  width: 100%;
  height: 1rem;
  border-radius: 0.5rem;
  gap: 0.25rem;
}

.progress-fill {
  width: 100%;
  height: 100%;
  border-radius: 0.5rem;
}
.progress-fill:not(.active-bar) {
  background-color: var(--background-progress-bar);
}
.active-bar {
  background-color: var(--background-progress-bar-active);
}
</style>
