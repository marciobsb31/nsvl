<template>
  <div class="br-card nvsl-card m-0" :class="customClass">
    <div v-if="title" class="nvsl-card__header">
      <div class="nvsl-card__titles">
        <h2 class="nvsl-card__title text-up-02">{{ title }}</h2>
        <p v-if="subtitle" class="nvsl-card__subtitle">{{ subtitle }}</p>
      </div>
      <span class="br-divider my-3" aria-hidden="true"></span>
    </div>
    <div class="nvsl-card__body">
      <slot></slot>
    </div>
  </div>
</template>
<script setup lang="ts">
defineProps({
  customClass: { type: String, required: false },
  title: { type: String, required: false },
  subtitle: { type: String, required: false },
})
</script>
<style scoped>
.nvsl-card {
  padding: 1.25rem 1.25rem 1.5rem;
  border-radius: 8px;
  box-shadow: 0 1px 4px rgba(19, 81, 180, 0.08);
  border: 1px solid var(--color-secondary-03, #e8e8e8);
  background: var(--background);
}

.nvsl-card__title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-primary-darken-02, #0c326f);
  line-height: 1.35;
}

.nvsl-card__subtitle {
  margin: 0.375rem 0 0;
  font-size: 0.875rem;
  line-height: 1.45;
  color: var(--color-secondary-07, #555);
  font-weight: 400;
}

.nvsl-card__body {
  padding-top: 0.25rem;
}

@media (min-width: 576px) {
  .nvsl-card {
    padding: 1.5rem 1.75rem;
  }
}
</style>