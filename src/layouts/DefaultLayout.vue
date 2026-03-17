<template>
  <div class="layout-default">
    <!-- Cabeçalho GOV.BR -->
    <Header
      title="NVSL"
      subtitle="Sistema de Gestão"
      :logoGov="!isAuthenticated ? logoGov : ''"
      @theme-change="handleThemeChange"
    >
      <template #actions v-if="isAuthenticated">
        <div class="header-user">
          <span aria-label="Usuário autenticado">{{ userName }}</span>
          <button
            class="br-button secondary small"
            type="button"
            aria-label="Sair da conta GOV.BR"
            @click="handleLogout"
            :disabled="isLoading"
          >
            Sair
          </button>
        </div>
      </template>
    </Header>

    <!-- Botão menu mobile -->
    <button
      v-if="isMobile"
      class="layout-menu-toggle"
      type="button"
      aria-label="Abrir menu"
      :aria-expanded="sidebarAberto"
      @click="sidebarAberto = !sidebarAberto"
    >
      <i class="fas" :class="sidebarAberto ? 'fa-times' : 'fa-bars'" aria-hidden="true"></i>
    </button>

    <!-- Overlay sidebar mobile -->
    <div
      v-if="isMobile && sidebarAberto"
      class="layout-sidebar-overlay"
      aria-hidden="true"
      @click="sidebarAberto = false"
    ></div>

    <!-- Conteúdo com sidebar e área principal -->
    <div class="layout-default__body">
      <Sidebar :class="{ 'sidebar--aberto': sidebarAberto }" />
      <main id="main-content" class="layout-default__main" tabindex="-1">
        <div class="container">
          <slot />
        </div>
      </main>
    </div>

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
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/core/composables/useAuth'
import Header from '@/core/components/Header/Header.vue'
import Sidebar from '@/core/components/Sidebar/Sidebar.vue'
import Footer from '@/core/components/Footer/Footer.vue'
import { useBreakpoint } from '@/core/composables/useBreakpoint'
import logoGovColor from '@/assets/images/logo/mdh_com_gov.png'
import logoGovBranca from '@/assets/images/logo/mdh_com_gov_branca.png'
import { useTheme } from '@/core/composables/useTheme'

const { isMobile } = useBreakpoint()
const sidebarAberto = ref(false)
const { mode } = useTheme()

const router = useRouter()
const { isAuthenticated, userName, isLoading, logout } = useAuth()

const currentYear = computed(() => new Date().getFullYear())
const logoGov = ref(logoGovColor)

async function handleLogout() {
  await logout()
  router.push({ name: 'login' })
}

const handleThemeChange = (theme: string) => {
  logoGov.value = theme === 'dark' ? logoGovBranca : logoGovColor
}

onMounted(() => {
  logoGov.value = mode.value === 'dark' ? logoGovBranca : logoGovColor
})

watch(isMobile, (mobile) => {
  if (!mobile) sidebarAberto.value = false
})

</script>

<style scoped>
.layout-default {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.layout-default__body {
  display: flex;
  flex: 1;
  min-height: 0;
}

.layout-default__main {
  flex: 1;
  padding: 1rem 0;
  background-color: var(--background);
  overflow: auto;
}

@media (min-width: 576px) {
  .layout-default__main {
    padding: 1.5rem 0;
  }
}

@media (min-width: 992px) {
  .layout-default__main {
    padding: 2rem 0;
  }
}

.layout-menu-toggle {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 998;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: var(--color-primary-default, #1351b4);
  color: #fff;
  border: none;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

@media (min-width: 992px) {
  .layout-menu-toggle {
    display: none;
  }
}

.layout-sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  z-index: 997;
}

@media (min-width: 992px) {
  .layout-sidebar-overlay {
    display: none;
  }
}

.header-user {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.footer {
  margin: 1rem;
}

.logo-gov {
  height: 40px;
}
</style>

