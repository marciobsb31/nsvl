<template>

  <div class="br-table" data-search="data-search" data-selection="data-selection" data-collapse="data-collapse"
    data-random="data-random">
    <table v-if="data.length > 0">
      <caption>{{ title ?? 'Tabela' }}</caption>
      <thead>
        <tr>
          <th v-for="col in columns" :key="col.key" scope="col"
            :style="col.width != null ? { width: toCssSize(col.width) } : undefined"
            :aria-sort="obterAriaSort(col.key)">
            <template v-if="col.sort">
              <button class="th-sort-btn" type="button" @click="ordenarPor(col.key)">
                {{ col.label }}
                <i class="fas th-sort-icon" :class="obterIndicadorSort(col.key)"></i>
              </button>
            </template>
            <template v-else>
              {{ col.label }}
            </template>
          </th>
          <th v-if="showActions" scope="col">Ações</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, idx) in data" :key="toKey((row as any).id ?? idx)">
          <td v-for="col in columns" :key="col.key" :data-th="col.label"
            :style="col.width != null ? { width: toCssSize(col.width) } : undefined">
            <slot :name="col.key" :row="row">
              {{ (row as any)[col.key] ?? '—' }}
            </slot>
          </td>
          <td v-if="showActions">
            <slot name="actions" :row="row"></slot>
          </td>
        </tr>

      </tbody>
    </table>
    <template v-if="data.length === 0">
      <div class="p-4 text-center text-muted">
         <i class="fas fa-3x mb-3 text-muted" :class="props.emptyIcon ? 'fa-' + props.emptyIcon : 'fa-search'" aria-hidden="true"></i>
        <p class="mb-0">{{ props.emptyMessage ?? 'Nenhum registro encontrado.' }}</p>
      </div>
    </template>



  </div>
</template>
<script setup lang="ts">
import { computed, ref, toRef } from 'vue';

defineOptions({
  name: 'Table'
});

const emit = defineEmits<{
  (e: 'sort', payload: { column: string; asc: boolean }): void;
}>();

const props = defineProps<{
  title?: string;
  columns: { key: string; label: string; width?: string | number; sort?: boolean }[];
  data: Array<any>;
  showActions?: boolean;
  emptyMessage?: string;
  emptyIcon?: string;
}>();

const title = toRef(props, 'title');
const columns = toRef(props, 'columns');
const data = toRef(props, 'data');
const showActions = computed(() => props.showActions ?? false);

const sortColumn = ref<string | null>(null);
const sortAsc = ref<boolean>(true);

function toKey(value: unknown): PropertyKey {
  if (typeof value === 'string' || typeof value === 'number' || typeof value === 'symbol') return value;
  return String(value);
}

function toCssSize(value: string | number): string {
  return typeof value === 'number' ? `${value}px` : value
}

function obterAriaSort(coluna: string): 'none' | 'ascending' | 'descending' {
  if (sortColumn.value !== coluna) return 'none'
  return sortAsc.value ? 'ascending' : 'descending'
}

function ordenarPor(coluna: string) {
  if (sortColumn.value === coluna) {
    sortAsc.value = !sortAsc.value
  } else {
    sortColumn.value = coluna
    sortAsc.value = true
  }
  emit('sort', { column: sortColumn.value, asc: sortAsc.value })
}

function obterIndicadorSort(coluna: string): string {
  if (sortColumn.value !== coluna) return 'fa-arrows-alt-v'
  return sortAsc.value ? 'fa-long-arrow-alt-up' : 'fa-long-arrow-alt-down'
}

</script>

<style scoped>
.th-sort-icon {
  font-size: 1rem;
  color: var(--color-secondary-07, #555);
}
</style>
