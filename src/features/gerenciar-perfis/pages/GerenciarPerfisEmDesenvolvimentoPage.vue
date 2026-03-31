<template>
  <DefaultLayout>
    <section class="perfis-dev" aria-labelledby="perfis-dev-titulo">
      <h1 id="perfis-dev-titulo" class="perfis-dev__titulo">
        Gerenciar perfis
      </h1>
      <p class="perfis-dev__texto">
        Página em desenvolvimento.
      </p>
    </section>
  </DefaultLayout>
</template>

<script setup lang="ts">
import DefaultLayout from '@/layouts/DefaultLayout.vue'

defineOptions({ name: 'GerenciarPerfisEmDesenvolvimentoPage' })
</script>

<style scoped>
.perfis-dev {
  padding: 1.5rem 0;
  max-width: 40rem;
}

.perfis-dev__titulo {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-primary-default, #1351b4);
  margin: 0 0 1rem;
}

.perfis-dev__texto {
  margin: 0;
  font-size: 1.0625rem;
  color: var(--color-secondary-08, #333);
}
</style>
