<template>
  <DefaultLayout>
    <HeaderPage
      title="Bem-vindo(a) ao Novo Viver Sem Limite"
      :subtitle="'Sistema de Gestão de Plano de Ação'"
      customClass="mb-3"
    >
    </HeaderPage>
  </DefaultLayout>
</template>

<script setup lang="ts">
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import HeaderPage from '@/core/components/HeaderPage/HeaderPage.vue'

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
  color: var(--color-primary-default, #1351b4);
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
