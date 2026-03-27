<template>
  <div class="layout-default">
    <!-- Cabeçalho GOV.BR -->
    <Header
      title="NVSL"
      subtitle="Sistema de Gestão"
      :logoGov="logoGov"
      @theme-change="handleThemeChange"
    >
      <template #actions v-if="isAuthenticated">
        <div class="header-user">
          <div class="header-user-info">
            <i class="fas fa-user-circle header-user-icon" aria-hidden="true"></i>
            <div class="header-user-dados">
              <span class="header-user-nome" aria-label="Usuário logado">{{ userName }}</span>
              <span v-if="userEsfera" class="header-user-perfil">{{ labelEsfera(userEsfera) }}</span>
            </div>
          </div>
          <button
            class="br-button secondary small"
            type="button"
            aria-label="Sair da conta"
            @click="handleLogout"
          >
            <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
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
      <Sidebar
        :class="{ 'sidebar--aberto': sidebarAberto }"
        :recolhido="sidebarRecolhido"
        :aberto="sidebarAberto"
        @toggle-recolher="sidebarRecolhido = !sidebarRecolhido"
      />
      <main ref="mainRef" id="main-content" class="layout-default__main" tabindex="-1">
        <div class="container main-content">
          <Breadcrumb customClass="mb-3"></Breadcrumb>
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
import ScrollToTop from '@/core/components/ScrollToTop/ScrollToTop.vue'
import { useBreakpoint } from '@/core/composables/useBreakpoint'
import logoGovColor from '@/assets/images/logo/mdh_com_gov.png'
import logoGovBranca from '@/assets/images/logo/mdh_com_gov_branca.png'
import { useTheme } from '@/core/composables/useTheme'
import Breadcrumb from '@/core/components/Breadcrumb/Breadcrumb.vue'

const { isMobile } = useBreakpoint()
const mainRef = ref<HTMLElement | null>(null)
const sidebarAberto = ref(false)
const sidebarRecolhido = ref(localStorage.getItem('nvsl_sidebar_recolhido') === 'true')
const { mode } = useTheme()

const router = useRouter()
const { isAuthenticated, userName, logout, user } = useAuth()

const userEsfera = computed(() => user.value?.esfera_atuacao ?? '')

function labelEsfera(esfera: string) {
  const map: Record<string, string> = {
    federal: 'Federal',
    estadual: 'Estadual',
    municipal: 'Municipal',
  }
  return map[esfera] ?? esfera
}
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

watch(sidebarRecolhido, (v) => {
  localStorage.setItem('nvsl_sidebar_recolhido', String(v))
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

.main-content{
  padding: 0 3rem;
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
  padding: 0.5rem 0.75rem;
  background: var(--color-secondary-01, #f8f8f8);
  border-radius: 8px;
  border: 1px solid var(--color-secondary-04, #ddd);
}

.header-user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.header-user-icon {
  font-size: 1.75rem;
  color: var(--color-primary-default, #1351b4);
}

.header-user-dados {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.header-user-nome {
  font-weight: 600;
  font-size: 0.9375rem;
  color: var(--color-secondary-08, #333);
}

.header-user-perfil {
  font-size: 0.75rem;
  color: var(--color-secondary-06, #888);
}

.header-user .br-button {
  flex-shrink: 0;
}

.header-user .br-button i {
  margin-right: 0.35rem;
}

[data-theme="dark"] .header-user {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.15);
}

[data-theme="dark"] .header-user-nome {
  color: rgba(255, 255, 255, 0.95);
}

[data-theme="dark"] .header-user-perfil {
  color: rgba(255, 255, 255, 0.65);
}

[data-theme="dark"] .header-user-icon {
  color: var(--color-primary-lighten-01, #4d7fd6);
}

@media (max-width: 575px) {
  .header-user-info {
    max-width: 140px;
  }

  .header-user-nome {
    font-size: 0.875rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .header-user-perfil {
    display: none;
  }
}

.footer {
  margin: 1rem;
}

.logo-gov {
  height: 40px;
}
</style>

