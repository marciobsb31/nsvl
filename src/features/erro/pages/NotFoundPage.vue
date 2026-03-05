<template>
  <DefaultLayout>
    <section class="not-found-page" aria-labelledby="not-found-title">
      <div class="not-found-content">
        <p class="not-found-code" aria-hidden="true">404</p>
        <h1 id="not-found-title" class="not-found-title">Página não encontrada</h1>
        <p class="not-found-description">
          A página que você está procurando não existe ou foi movida.
        </p>
        <router-link
          to="/"
          class="br-button primary not-found-btn"
          aria-label="Voltar para a página inicial"
        >
          Ir para o início
        </router-link>
      </div>
    </section>
  </DefaultLayout>
</template>

<script setup lang="ts">
import DefaultLayout from '@/layouts/DefaultLayout.vue'

defineOptions({ name: 'NotFoundPage' })
</script>

<style scoped>
.not-found-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  text-align: center;
}

.not-found-content {
  max-width: 480px;
}

.not-found-code {
  font-size: 7rem;
  font-weight: 800;
  color: var(--color-primary-default, #1351b4);
  opacity: 0.15;
  margin: 0;
  line-height: 1;
}

.not-found-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--color-secondary-09, #222222);
  margin: 0.5rem 0;
}

.not-found-description {
  color: var(--color-secondary-07, #555555);
  margin-bottom: 2rem;
}

.not-found-btn {
  display: inline-flex;
  align-items: center;
  text-decoration: none;
}
</style>
