<template>
    <section class="titulo-pagina__topo" :class="[customClass]">
        <div>
        <h1 class="titulo-pagina__h1">{{ title }}</h1>
        <p class="titulo-pagina__subtitulo" v-if="subtitle">
            <span v-html="subtitle"></span>
        </p>
        </div>
        <slot name="actions">

        </slot>

    </section>
</template>

<script setup lang="ts">

defineOptions({
    name:'HeaderPage'
})

const props = defineProps({
    title: String,
    subtitle: {
        type: String,
        required: false
    },
    customClass:{
        type: String,
        required: false
    }
})

</script>

<style lang="css" scoped>

.titulo-pagina__topo {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  flex-wrap: wrap;
}

.titulo-pagina__h1 {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--primary-text-color);
  margin: 0;
}

.titulo-pagina__subtitulo {
  font-size: 1rem;
  color: var(--secondary-text-color);
  margin: 0.5rem 0 0;
}

@media (max-width: 575px) {
  .titulo-pagina__h1 {
    font-size: 1.25rem;
  }

  .titulo-pagina__subtitulo {
    font-size: 0.875rem;
  }
  .titulo-pagina__topo {
    flex-direction: column;
  }

}

</style>