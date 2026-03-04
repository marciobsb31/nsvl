<template>
  <div class="layout-default">
    <!-- Cabeçalho GOV.BR -->
    <header role="banner">
      <br-header
        title="NVSL"
        subtitle="Sistema de Gestão"
        logo-image="/favicon.ico"
        logo-alt="Logo NVSL"
      >
        <!-- Informações do usuário autenticado -->
        <template v-if="isAuthenticated">
          <div class="header-user" slot="links">
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
      </br-header>
    </header>

    <!-- Conteúdo principal -->
    <main id="main-content" class="layout-default__main" tabindex="-1">
      <div class="container">
        <slot />
      </div>
    </main>

    <!-- Rodapé GOV.BR -->
    <footer role="contentinfo">
      <br-footer assign-info="Ministério da Gestão e da Inovação em Serviços Públicos">
        <span slot="info">
          © {{ currentYear }} NVSL — Todos os direitos reservados
        </span>
      </br-footer>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/core/composables/useAuth'

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
}

.header-user {
  display: flex;
  align-items: center;
  gap: 1rem;
}
</style>
