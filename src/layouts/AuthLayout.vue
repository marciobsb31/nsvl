<template>
  <div class="layout-auth">
    <!-- Cabeçalho GOV.BR simplificado -->
    <header role="banner" class="layout-auth__header">
      <div class="container">
        <img src="/favicon.ico" alt="Logo NVSL" width="40" height="40" />
        <span class="layout-auth__title">NVSL</span>
      </div>
    </header>

    <!-- Conteúdo da página de autenticação -->
    <main id="main-content" class="layout-auth__main" tabindex="-1">
      <slot />
    </main>

    <!-- Rodapé simples -->
    <footer class="layout-auth__footer" role="contentinfo">
      <p>© {{ currentYear }} NVSL — Governo Federal</p>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const currentYear = computed(() => new Date().getFullYear())
</script>

<style scoped>
.layout-auth {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background-color: var(--color-secondary-01, #f8f8f8);
}

.layout-auth__header {
  background-color: var(--color-primary-default, #1351b4);
  padding: 1rem 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.layout-auth__header .container {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.layout-auth__title {
  color: #ffffff;
  font-size: 1.25rem;
  font-weight: 700;
  letter-spacing: 0.05em;
}

.layout-auth__main {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}

.layout-auth__footer {
  text-align: center;
  padding: 1rem;
  font-size: 0.875rem;
  color: var(--color-secondary-07, #888888);
}
</style>
