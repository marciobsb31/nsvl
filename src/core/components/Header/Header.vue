<template>

    <header class="br-header">
        <div class="container-lg">
            <div class="header-top">
                <div class="header-info">
                     <button v-if="logo" class="logo-btn" type="button" aria-label="Ir para a página inicial" @click="goToHome">
                        <img :src="logoAtual" alt="Logo GOV" class="logo" />
                    </button>
                    <template v-else>
                        <button class="header-title text-blue-warm-vivid-70 header-title-btn" type="button" aria-label="Ir para a página inicial" @click="goToHome">{{ title }}</button>
                        <div class="header-subtitle text-gray-80">{{ subtitle }}</div>
                    </template>
                </div>
                <div class="header-actions header-actions--center">
                    <slot name="actions"></slot>
                </div>
                <div class="header-right">
                    <img v-if="logoGov && isDesktop" :src="logoGov" alt="Logo GOV" class="logo-gov" />
                    <button
                        class="br-button circle small ml-3"
                        type="button"
                        :aria-label="mode === 'dark' ? 'Alternar para tema claro' : 'Alternar para tema escuro'"
                        title="Alternar tema"
                        @click="toggleTheme"
                    >
                        <i class="fas fa-adjust" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>

    </header>
</template>

<script setup lang="ts">
import logo from '@/assets/images/logo/logo_novo_viver.png'
import logobranca from '@/assets/images/logo/logo_novo_viver_branca.png'
import { useBreakpoint } from '@/core/composables/useBreakpoint'
import { useTheme } from '@/core/composables/useTheme';
import { computed } from 'vue';

const { isMobile, isDesktop } = useBreakpoint()
const { setMode, mode } = useTheme()

defineOptions({
    name: 'HeaderComponent'
});
defineProps({
    title: String,
    subtitle: String,
    logoGov: {
        type: String,
        required: false
    }
});

const emit = defineEmits(['theme-change']);
const goToHome = () => {
    window.location.href = '/';
};

const toggleTheme = () => {
    setMode(mode.value === 'dark' ? 'light' : 'dark');
    emit('theme-change', mode.value);
};

const logoAtual = computed(() => {
    return mode.value === 'dark' ? logobranca : logo;
});


</script>

<style scoped>
.header-top {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 1rem;
}
.header-info {
    min-width: 0;
}
.header-title {
    cursor: pointer;
}
.header-title-btn {
    border: none;
    background: transparent;
    padding: 0;
    font: inherit;
}
.logo-btn {
    border: none;
    background: transparent;
    padding: 0;
    cursor: pointer;
}

.header-home {
    padding: 0;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
}
.header-actions {
    display: flex;
    justify-content: center;
}
.header-actions--center {
    justify-self: center;
}
.header-right {
    display: flex;
    align-items: center;
    justify-content: flex-end;
}

.logo-gov{
    height: 60px;
}

.logo{
    height: 60px;
    cursor: pointer;
}

@media (max-width: 575px) {
  .header-subtitle {
    display: block !important;
  }

  .logo-gov {
    height: 50px;
  }
  .logo{
    height: 50px;
  }
}

@media (max-width: 991px) {
  .logo-gov {
    height: 50px;
  }
}

@media (max-width: 767px) {
  .header-top {
    grid-template-columns: 1fr auto;
    grid-template-areas: "info right" "actions actions";
  }
  .header-info { grid-area: info; }
  .header-actions { grid-area: actions; justify-self: stretch; }
  .header-right { grid-area: right; }
}



</style>