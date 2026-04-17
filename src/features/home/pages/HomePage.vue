<template>
  <DefaultLayout>
    <section class="home-page" aria-labelledby="home-title">
      <!-- Saudação -->
      <div class="home-page__welcome">
        <h1 id="home-title" class="home-page__title">Bem-vindo ao NVSL</h1>
        <p class="home-page__subtitle">Sistema de Gestão — Novo Viver Sem Limite</p>
      </div>
    </section>
  </DefaultLayout>
</template>

<script setup lang="ts">
import DefaultLayout from '@/layouts/DefaultLayout.vue'

defineOptions({ name: 'HomePage' })
</script>

<style scoped>
.home-page {
  padding: 1rem 0;
}

.home-page__welcome {
  margin-bottom: 2.5rem;
}

.home-page__title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary-text-color);
  margin: 0 0 0.5rem;
}

@media (min-width: 576px) {
  .home-page__title {
    font-size: 1.75rem;
  }
}

.home-page__subtitle {
  font-size: 1.1rem;
  color: var(--color-secondary-08, #333333);
  margin: 0 0 1rem;
}
</style>
