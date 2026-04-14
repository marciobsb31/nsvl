<template>
  <div class="layout-public">
    <a href="#main-content" class="skip-link">Pular para o conteúdo principal</a>
    <Header
      title="NVSL"
      subtitle="Sistema de Gestão"
      :logoGov="logoGov"
    />

    <main id="main-content" class="layout-public__main" :class="{ 'layout-public__main--full': fullWidth }" tabindex="-1">
      <slot />
    </main>

    <Footer inverted aria-label="Rodapé">
      <template #info>
        <div class="footer">
          © {{ currentYear }} NVSL — Todos os direitos reservados
        </div>
      </template>
    </Footer>

    <ScrollToTop />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'

withDefaults(
  defineProps<{ fullWidth?: boolean }>(),
  { fullWidth: false }
)
import Header from '@/core/components/Header/Header.vue'
import Footer from '@/core/components/Footer/Footer.vue'
import ScrollToTop from '@/core/components/ScrollToTop/ScrollToTop.vue'
import logoGovColor from '@/assets/images/logo/mdh_com_gov.png'
import logoGovBranca from '@/assets/images/logo/mdh_com_gov_branca.png'
import { useTheme } from '@/core/composables/useTheme'

const { mode } = useTheme()
const currentYear = computed(() => new Date().getFullYear())
const logoGov = ref(logoGovColor)

watch(mode, (newMode) => {
  logoGov.value = newMode === 'dark' ? logoGovBranca : logoGovColor
})

onMounted(() => {
  logoGov.value = mode.value === 'dark' ? logoGovBranca : logoGovColor
})
</script>


<style scoped>
.layout-public {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.layout-public__main {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background-color: var(--background);
}

.layout-public__main--full {
  align-items: stretch;
  justify-content: flex-start;
}

.footer {
  margin: 1rem;
}
</style>
