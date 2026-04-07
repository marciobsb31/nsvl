<template>
    <section :class="{ 'fixed': props.fixed }">
    <div class="game-bar" :class="{ 'fixed': props.fixed }">
        <button class="br-button circle small" type="button"
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
        </button>
    </div>
    </section>
</template>

<script setup lang="ts">
import { useTheme } from '@/core/composables/useTheme';
import { useAccessibilityFont } from '@/core/composables/useAccessibilityFont'

const {
  increaseFontSize,
  decreaseFontSize,
  resetFontSize,
} = useAccessibilityFont()

defineOptions({ name: 'Accessibility' });
const props = defineProps({
    fixed: {
        type: Boolean,
        default: false
    }
});
const { mode, setMode } = useTheme();

const toggleTheme = () => {
    setMode(mode.value === 'dark' ? 'light' : 'dark');
};
</script>

<style scoped>
.font-acessibilidade {
  font-weight: var(--font-weight-bold);
  font-size: 18px;
}

.fixed{
  position: fixed;
  z-index: 9999 !important;
  top: 10rem;
  right: 1rem;
  
}

.game-bar{
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

[data-theme="dark"] .game-bar.fixed {
  background-color: var(--background-gray);
}
[data-theme="dark"] .game-bar.fixed button {
  color: var(--pure-0);
}
</style>