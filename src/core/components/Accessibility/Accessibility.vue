<template>
  <Card custom-class="menu" v-if="acessibilidade">
    <div>
      <p class="m-1 p-0 item-titulo">Texto</p>
      <div class="menu-section">
        <button
          class="br-button item-menu font-acessibilidade"
          type="button"
          aria-label="Aumentar Fonte"
          title="Aumentar Fonte"
          @click="increaseFontSize"
        >
          A+
          <p class="m-0 texto-menu">Texto maior</p>
        </button>
        <button
          class="br-button item-menu font-acessibilidade"
          type="button"
          aria-label="Diminuir Fonte"
          title="Diminuir Fonte"
          @click="decreaseFontSize"
        >
          A-
          <p class="m-0 texto-menu">Texto menor</p>
        </button>
      </div>
    </div>
    <div>
      <p class="m-1 p-0 item-titulo">Contraste</p>
      <div class="menu-section">
        <button
          class="br-button item-menu"
          type="button"
          :aria-label="mode === 'dark' ? 'Alternar para tema claro' : 'Alternar para tema escuro'"
          title="Alternar tema"
          @click="toggleTheme"
        >
          <i class="fas fa-adjust" aria-hidden="true"></i>
          <p class="m-0 texto-menu">Alto contraste</p>
        </button>
      </div>
    </div>
  </Card>
  <section :class="{ fixed: props.fixed }">
    <button
      class="br-button circle primary"
      type="button"
      :aria-label="
        acessibilidade ? 'Fechar menu de acessibilidade' : 'Abrir menu de acessibilidade'
      "
      title="Abrir menu de acessibilidade"
      @click="toggleAcessibilidade"
    >
      <i class="fas fa-universal-access icon-accessibility" aria-hidden="true"></i>
    </button>
    <div class="game-bar" :class="{ fixed: props.fixed }">
      <!-- <button class="br-button circle small" type="button"
            :aria-label="mode === 'dark' ? 'Alternar para tema claro' : 'Alternar para tema escuro'"
            title="Alternar tema" @click="toggleTheme" >
            <i class="fas fa-adjust" aria-hidden="true"></i>
        </button>
        <button class="br-button circle small font-acessibilidade" type="button" aria-label="Diminuir Fonte"
            title="Diminuir Fonte"   @click="decreaseFontSize">
            A-
        </button>
          <button class="br-button circle small font-acessibilidade" type="button" aria-label="Tamanho padrão de fonte"
            title="Tamanho padrão de fonte"   @click="resetFontSize">
            A
        </button>
        <button class="br-button circle small font-acessibilidade" type="button" aria-label="Aumentar Fonte"
            title="Aumentar Fonte"   @click="increaseFontSize">
            A+
        </button> -->
    </div>
  </section>
</template>

<script setup lang="ts">
import { useTheme } from '@/core/composables/useTheme'
import { useAccessibilityFont } from '@/core/composables/useAccessibilityFont'
import Card from '../Card/Card.vue'
import { ref } from 'vue'

const { increaseFontSize, decreaseFontSize, resetFontSize } = useAccessibilityFont()

defineOptions({ name: 'Accessibility' })
const props = defineProps({
  fixed: {
    type: Boolean,
    default: false,
  },
})
const { mode, setMode } = useTheme()

const toggleTheme = () => {
  setMode(mode.value === 'dark' ? 'light' : 'dark')
}

const acessibilidade = ref(false)

const toggleAcessibilidade = () => {
  acessibilidade.value = !acessibilidade.value
}
</script>

<style scoped>
.font-acessibilidade {
  font-weight: var(--font-weight-bold);
  font-size: 18px;
}

.menu {
  position: fixed;
  z-index: 9999 !important;
  bottom: 12rem;
  right: 6rem;
  width: 300px;
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
  padding: 1rem 0;
  background-color: var(--gray-warm-3);
  border-radius: 0.5rem;
  width: 100%;
}

.fixed {
  position: fixed;
  z-index: 9999 !important;
  bottom: 8rem;
  right: 1.5rem;
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
    right: 3rem;
  }
}

[data-theme='dark'] .game-bar.fixed {
  background-color: var(--background-gray);
}
[data-theme='dark'] .game-bar.fixed button {
  color: var(--pure-0);
}
</style>
