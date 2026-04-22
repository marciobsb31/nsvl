<template>
  <div class="barreira-grupo">
    <h4 class="barreira-titulo">{{ titulo }}</h4>
    <p class="barreira-descricao">{{ descricao }}</p>

    <div class="barreira-scroll-area" role="group" :aria-label="titulo">
      <label
        v-for="opcao in opcoes"
        :key="opcao"
        class="barreira-item"
        :class="{ 'barreira-item--checked': modelValue.includes(opcao) }"
      >
        <input
          type="checkbox"
          :value="opcao"
          :checked="modelValue.includes(opcao)"
          @change="toggle(opcao)"
        />
        <span>{{ opcao }}</span>
      </label>
    </div>

    <div v-if="modelValue.length" class="barreira-chips mt-2" aria-label="Itens selecionados">
      <span
        v-for="item in modelValue"
        :key="item"
        class="barreira-chip"
      >
        {{ item }}
        <button
          type="button"
          class="barreira-chip__remover"
          :aria-label="`Remover ${item}`"
          @click="remover(item)"
        >
          <i class="fas fa-times" aria-hidden="true"></i>
        </button>
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
defineOptions({ name: 'BarreiraCheckboxGroup' })

const props = defineProps<{
  titulo: string
  descricao: string
  opcoes: string[]
  modelValue: string[]
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', val: string[]): void
}>()

function toggle(opcao: string): void {
  const idx = props.modelValue.indexOf(opcao)
  if (idx >= 0) {
    const novo = [...props.modelValue]
    novo.splice(idx, 1)
    emit('update:modelValue', novo)
  } else {
    emit('update:modelValue', [...props.modelValue, opcao])
  }
}

function remover(opcao: string): void {
  emit('update:modelValue', props.modelValue.filter((i) => i !== opcao))
}
</script>

<style scoped>
.barreira-grupo {
  display: flex;
  flex-direction: column;
}

.barreira-titulo {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #1c1c1c;
  margin: 0 0 0.2rem;
}

.barreira-descricao {
  font-size: 0.8125rem;
  color: #636363;
  margin: 0 0 0.5rem;
  line-height: 1.4;
}

/* ── Área com scroll ── */
.barreira-scroll-area {
  border: 1px solid #d0d0d0;
  border-radius: 4px;
  max-height: 220px;
  overflow-y: auto;
  padding: 0.375rem 0.5rem;
  background: #fff;
  scrollbar-width: thin;
  scrollbar-color: #b0b0b0 #f0f0f0;
}

.barreira-scroll-area::-webkit-scrollbar {
  width: 7px;
}
.barreira-scroll-area::-webkit-scrollbar-track {
  background: #f0f0f0;
  border-radius: 4px;
}
.barreira-scroll-area::-webkit-scrollbar-thumb {
  background: #b0b0b0;
  border-radius: 4px;
}

/* ── Item de checkbox ── */
.barreira-item {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.375rem 0.5rem;
  margin-bottom: 0.25rem;
  border-radius: 3px;
  cursor: pointer;
  font-size: 0.875rem;
  color: #1c1c1c;
  line-height: 1.4;
  user-select: none;
}

.barreira-item:last-child {
  margin-bottom: 0;
}

.barreira-item input[type='checkbox'] {
  flex-shrink: 0;
  cursor: pointer;
  accent-color: #1351b4;
  width: 16px;
  height: 16px;
  min-width: 16px;
}

.barreira-item:hover {
  background: #f0f4ff;
}

.barreira-item--checked {
  background: #eef3ff;
}

/* ── Chips ── */
.barreira-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
}

.barreira-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: #e8f0fb;
  border: 1px solid #1351b4;
  border-radius: 20px;
  padding: 0.2rem 0.6rem;
  font-size: 0.8125rem;
  color: #1351b4;
}

.barreira-chip__remover {
  background: none;
  border: none;
  cursor: pointer;
  color: #1351b4;
  padding: 0;
  line-height: 1;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
}

.barreira-chip__remover:hover {
  color: #c0345b;
}
</style>
