<template>
  <div class="mb-2 select-autocomplete" :class="{ 'is-open': isOpen }">
    <div class="br-input">
      <label :for="inputId">
        {{ label }}
        <span v-if="required" class="text-red-50 text-up-01">*</span>
      </label>
      <input
        :id="inputId"
        ref="inputRef"
        type="text"
        :placeholder="placeholder"
        :value="displayValue"
        :disabled="disabled"
        autocomplete="off"
        @input="onInput"
        @focus="onFocus"
        @blur="onBlur"
        @keydown.down.prevent="navigateDown"
        @keydown.up.prevent="navigateUp"
        @keydown.enter.prevent="selectHighlighted"
        @keydown.escape="close"
      />
      <button
        class="br-button"
        type="button"
        :aria-label="`Exibir lista de ${label}`"
        tabindex="-1"
        @click="toggle"
      >
        <i class="fas" :class="isOpen ? 'fa-angle-up' : 'fa-angle-down'" aria-hidden="true"></i>
      </button>
    </div>
    <div
      v-if="isOpen"
      class="br-list br-list--autocomplete"
      role="listbox"
      tabindex="0"
      @keydown="onListKeydown"
    >
      <div
        v-for="(option, index) in filteredOptions"
        :key="String(option.value)"
        class="br-item"
        role="option"
        :aria-selected="index === highlightedIndex"
        :class="{ 'highlighted': index === highlightedIndex }"
        @mousedown.prevent="selectOption(option)"
      >
        <div class="br-radio">
          <input
            :id="`opt-${inputId}-${option.value}`"
            type="radio"
            :name="`autocomplete-${inputId}`"
            :value="option.value"
            :checked="modelValue === option.value"
          />
          <label :for="`opt-${inputId}-${option.value}`">{{ option.label }}</label>
        </div>
      </div>
      <div v-if="filteredOptions.length === 0" class="br-item br-item--empty">
        Nenhuma opção encontrada
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

export type SelectAutocompleteOption = {
  label: string
  value: string | number
}

defineOptions({ name: 'SelectAutocomplete' })

const props = withDefaults(
  defineProps<{
    label: string
    placeholder: string
    options: SelectAutocompleteOption[]
    required?: boolean
    modelValue?: string | number | null
    disabled?: boolean
    /** ID do input para foco e acessibilidade (ex: cad-esfera) */
    inputId?: string
  }>(),
  { required: false, modelValue: null, disabled: false, inputId: undefined }
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number | null): void
}>()

const inputId = props.inputId ?? `select-ac-${props.label.replace(/\s/g, '-')}-${Math.random().toString(36).slice(2, 8)}`
const inputRef = ref<HTMLInputElement | null>(null)
const isOpen = ref(false)
const searchText = ref('')
const highlightedIndex = ref(0)

const filteredOptions = computed(() => {
  if (!props.options.length) return []
  const term = searchText.value.toLowerCase().trim()
  if (!term) return props.options
  return props.options.filter((opt) =>
    String(opt.label).toLowerCase().includes(term)
  )
})

const displayValue = computed(() => {
  if (searchText.value) return searchText.value
  const selected = props.options.find((o) => o.value === props.modelValue)
  return selected?.label ?? ''
})

watch(
  () => props.modelValue,
  (val) => {
    if (val == null) searchText.value = ''
  }
)

function onInput(e: Event) {
  const target = e.target as HTMLInputElement
  searchText.value = target.value
  isOpen.value = true
  highlightedIndex.value = 0
  if (!searchText.value.trim()) {
    emit('update:modelValue', null)
  }
}

function onFocus() {
  isOpen.value = true
  highlightedIndex.value = 0
}

function onBlur() {
  setTimeout(() => {
    isOpen.value = false
    searchText.value = ''
  }, 200)
}

function selectOption(option: SelectAutocompleteOption) {
  emit('update:modelValue', option.value)
  searchText.value = ''
  isOpen.value = false
}

function toggle() {
  isOpen.value = !isOpen.value
  if (isOpen.value) inputRef.value?.focus()
}

function close() {
  isOpen.value = false
}

function navigateDown() {
  if (!isOpen.value) {
    isOpen.value = true
    return
  }
  highlightedIndex.value = Math.min(
    highlightedIndex.value + 1,
    filteredOptions.value.length - 1
  )
}

function navigateUp() {
  highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0)
}

function selectHighlighted() {
  const opts = filteredOptions.value
  const opt = opts[highlightedIndex.value]
  if (opt) {
    selectOption(opt)
  }
}

function onListKeydown(e: KeyboardEvent) {
  if (e.key === 'ArrowDown') navigateDown()
  else if (e.key === 'ArrowUp') navigateUp()
  else if (e.key === 'Enter') selectHighlighted()
  else if (e.key === 'Escape') close()
}

function focus() {
  inputRef.value?.focus()
}

defineExpose({ focus })
</script>

<style scoped>
.select-autocomplete {
  position: relative;
}

.br-list--autocomplete {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 1000;
  max-height: 240px;
  overflow-y: auto;
  margin-top: 4px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.br-item.highlighted {
  background-color: var(--color-primary-pastel, #e8f4fc);
}

[data-theme="dark"] .br-item.highlighted {
  background-color: var(--background-gray);
}

[data-theme="dark"] .select-autocomplete button {
    color: var(--pure-0);
}

.br-item--empty {
  padding: 1rem;
  color: var(--color-secondary-06, #888);
  font-style: italic;
}
</style>
