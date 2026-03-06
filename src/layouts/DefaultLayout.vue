<template>
  <div class="layout-default">
    <!-- Cabeçalho GOV.BR -->

      <Header title="NVSL" subtitle="Sistema de Gestão" :logoGOV="logo">
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

const router = useRouter()
const { isAuthenticated, userName, isLoading, logout } = useAuth()

const currentYear = computed(() => new Date().getFullYear())
const logo = 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/11/Gov.br_logo.svg/1280px-Gov.br_logo.svg.png'

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
}

.header-user {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.footer {
  margin: 1rem;
}
</style>

