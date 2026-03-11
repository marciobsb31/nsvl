<template>
  <div class="layout-auth">
    <!-- Cabeçalho GOV.BR simplificado -->
      <Header title="NVSL" subtitle="Sistema de Gestão" :logoGov="logoGov" @theme-change="handleThemeChange" />

    <!-- Conteúdo da página de autenticação -->
    <main id="main-content" class="layout-auth__main" tabindex="-1">
      <slot />
    </main>

    <Footer inverted>
      <template #info>
        <div v-if="isMobile" class="mt-3">
          <img :src="logoGov" alt="Logo GOV" class="logo-gov" />
        </div>
        <div class="footer">
          © {{ currentYear }} NVSL — Todos os direitos reservados
        </div>
      </template>
    </Footer>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import Header from '@/core/components/Header/Header.vue'
import Footer from '@/core/components/Footer/Footer.vue'
import logoGovColor from '@/assets/images/logo/mdh_com_gov.png'
import logoGovBranca from '@/assets/images/logo/mdh_com_gov_branca.png'
import { useTheme } from '@/core/composables/useTheme'
import { useBreakpoint } from '@/core/composables/useBreakpoint'

const { isMobile } = useBreakpoint()
const { mode } = useTheme()
const logoGov = ref(logoGovColor)

const currentYear = computed(() => new Date().getFullYear())

const handleThemeChange = (theme: string) => {
  logoGov.value = theme === 'dark' ? logoGovBranca : logoGovColor
}

onMounted(() => {
 logoGov.value = mode.value === 'dark' ? logoGovBranca : logoGovColor
})
</script>

<style scoped>
.layout-auth {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background-color: var(--color-secondary-01, #f8f8f8);
}

.layout-auth__main {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  background-color: var(--gray-warm-2);
}

.footer {
  margin: 1rem;
}
.logo-gov {
  height: 40px;
}
</style>
