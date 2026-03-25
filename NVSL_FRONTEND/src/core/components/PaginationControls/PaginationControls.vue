<template>
  <div v-if="totalPages > 1 || showPageSize" class="pagination-controls">
    <div class="pagination-controls__left">
      <label class="pagination-controls__label">Itens por página
      <select
        class="pagination-controls__select"
        :value="pageSize"
        @change="onPageSizeChange"
      >
        <option v-for="opt in pageSizeOptions" :key="opt" :value="opt">{{ opt }}</option>
      </select>
      </label>
    </div>

    <div class="pagination-controls__right">
      <span class="pagination-controls__info">
        {{ startItem }}-{{ endItem }} de {{ totalItems }}
      </span>
      <button
        type="button"
        class="br-button circle small"
        :disabled="currentPage <= 1"
        aria-label="Página anterior"
        @click="$emit('update:currentPage', currentPage - 1)"
      >
        <i class="fas fa-chevron-left" aria-hidden="true"></i>
      </button>
      <span class="pagination-controls__page">Página {{ currentPage }} de {{ totalPages }}</span>
      <button
        type="button"
        class="br-button circle small"
        :disabled="currentPage >= totalPages"
        aria-label="Próxima página"
        @click="$emit('update:currentPage', currentPage + 1)"
      >
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

defineOptions({ name: 'PaginationControls' })

const props = withDefaults(defineProps<{
  totalItems: number
  currentPage: number
  pageSize: number
  pageSizeOptions?: number[]
}>(), {
  pageSizeOptions: () => [10, 20, 50],
})

const emit = defineEmits<{
  (e: 'update:currentPage', value: number): void
  (e: 'update:pageSize', value: number): void
}>()

const totalPages = computed(() => Math.max(1, Math.ceil(props.totalItems / props.pageSize)))
const startItem = computed(() => (props.totalItems === 0 ? 0 : (props.currentPage - 1) * props.pageSize + 1))
const endItem = computed(() => Math.min(props.totalItems, props.currentPage * props.pageSize))
const showPageSize = computed(() => props.totalItems > 0)

function onPageSizeChange(event: Event) {
  const target = event.target as HTMLSelectElement
  const parsed = Number(target.value)
  if (!Number.isNaN(parsed) && parsed > 0) {
    emit('update:pageSize', parsed)
    emit('update:currentPage', 1)
  }
}
</script>

<style scoped>
.pagination-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  padding-top: 0.75rem;
}

.pagination-controls__left,
.pagination-controls__right {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.pagination-controls__label,
.pagination-controls__info,
.pagination-controls__page {
  font-size: 0.8125rem;
  color: var(--color-secondary-07, #555);
}

.pagination-controls__select {
  min-height: 2rem;
  border: 1px solid var(--color-secondary-04, #ccc);
  border-radius: 4px;
  padding: 0.25rem 0.5rem;
  font-size: 0.8125rem;
}
</style>
