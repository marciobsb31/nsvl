<template>
  <div class="layout-default">
    <!-- Cabeçalho GOV.BR -->

      <Header title="NVSL" subtitle="Sistema de Gestão" :logoGov="!isAuthenticated ? logoGov : ''">
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

    <!-- Conteúdo principal -->
    <main id="main-content" class="layout-default__main" tabindex="-1">
      <div class="container">
        <slot />
      </div>
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
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/core/composables/useAuth'
import Header from '@/core/components/Header/Header.vue'
import Footer from '@/core/components/Footer/Footer.vue'
import { useBreakpoint } from '@/core/composables/useBreakpoint'
import logoGov from '@/assets/images/logo/mdh_com_gov.png'

const { isMobile } = useBreakpoint()

const router = useRouter()
const { isAuthenticated, userName, isLoading, logout } = useAuth()

const currentYear = computed(() => new Date().getFullYear())

async function handleLogout() {
  await logout()
  router.push({ name: 'login' })
}
</script>

<style scoped>
.layout-default {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.layout-default__main {
  flex: 1;
  padding: 2rem 0;
  background-color: var(--gray-warm-2);
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

