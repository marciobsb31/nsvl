<template>
  <div class="layout-auth">
    <!-- Cabeçalho GOV.BR simplificado -->
      <Header title="NVSL" subtitle="Sistema de Gestão" :logo="logo" />

    <!-- Conteúdo da página de autenticação -->
    <main id="main-content" class="layout-auth__main" tabindex="-1">
      <slot />
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
import Header from '@/core/components/Header/Header.vue'
import Footer from '@/core/components/Footer/Footer.vue'

const currentYear = computed(() => new Date().getFullYear())
const logo = 'https://upload.wikimedia.org/wikipedia/commons/6/6f/Brazilian_government_logo_%282025%29.png'
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
</style>
