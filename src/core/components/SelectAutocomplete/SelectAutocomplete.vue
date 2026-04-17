<template>
  <div class="select-autocomplete" :class="{ 'is-open': isOpen }">
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
        :required="required"
        :aria-required="required"
        :aria-invalid="ariaInvalid"
        :aria-describedby="ariaDescribedBy"
        role="combobox"
        aria-autocomplete="list"
        :aria-expanded="isOpen"
        :aria-controls="listboxId"
        :aria-activedescendant="isOpen ? activeDescendantId : undefined"
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
        :aria-expanded="isOpen"
        :aria-controls="listboxId"
        @click="toggle"
      >
        <i class="fas" :class="isOpen ? 'fa-angle-up' : 'fa-angle-down'" aria-hidden="true"></i>
      </button>
    </div>
    <div
      v-if="isOpen"
      class="br-list br-list--autocomplete"
      :id="listboxId"
      role="listbox"
      tabindex="-1"
      @keydown="onListKeydown"
    >
      <div
        v-for="(option, index) in filteredOptions"
        :key="String(option.value)"
        class="br-item"
        role="option"
        :id="optionId(option)"
        :aria-selected="index === highlightedIndex"
        :class="{ highlighted: index === highlightedIndex }"
        @mousedown.prevent="selectOption(option)"
      >
        <div class="select-option-row">
          <div class="select-option-row__left">
            <i
              v-if="modelValue === option.value"
              class="fas fa-check select-option-row__check"
              aria-hidden="true"
            ></i>
            <span v-else class="select-option-row__check-placeholder" aria-hidden="true"></span>
            <span class="select-option-row__label">{{ labelSemEmUso(option.label) }}</span>
          </div>
          <span v-if="option.inUse || option.badge" class="select-option-row__badge">
            {{ option.badge ?? 'Em uso' }}
          </span>
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
  badge?: string
  inUse?: boolean
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
    ariaInvalid?: boolean
    ariaDescribedBy?: string
  }>(),
  {
    required: false,
    modelValue: null,
    disabled: false,
    inputId: undefined,
    ariaInvalid: false,
    ariaDescribedBy: undefined,
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number | null): void
}>()

const inputId =
  props.inputId ??
  `select-ac-${props.label.replace(/\s/g, '-')}-${Math.random().toString(36).slice(2, 8)}`
const inputRef = ref<HTMLInputElement | null>(null)
const isOpen = ref(false)
const searchText = ref('')
const highlightedIndex = ref(0)

const listboxId = `listbox-${inputId}`

function optionId(option: SelectAutocompleteOption) {
  return `opt-${inputId}-${String(option.value)}`
}

const activeDescendantId = computed(() => {
  const opt = filteredOptions.value[highlightedIndex.value]
  return opt ? optionId(opt) : undefined
})

const filteredOptions = computed(() => {
  if (!props.options.length) return []
  const term = searchText.value.toLowerCase().trim()
  if (!term) return props.options
  return props.options.filter((opt) => String(opt.label).toLowerCase().includes(term))
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
  },
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
  highlightedIndex.value = Math.min(highlightedIndex.value + 1, filteredOptions.value.length - 1)
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

function labelSemEmUso(label: string): string {
  return label.replace(/\s*•\s*Em uso$/i, '').trim()
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

.br-item {
  cursor: pointer;
  padding: 0.32rem 0.5rem;
}

.select-option-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.35rem;
}

.select-option-row__left {
  display: flex;
  align-items: center;
  gap: 0.28rem;
  min-width: 0;
}

.select-option-row__label {
  font-size: 0.76rem;
  line-height: 1.05rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.select-option-row__check {
  color: var(--color-success, #168821);
  font-size: 1rem;
  margin-right: 0.25rem;
}

.select-option-row__check-placeholder {
  width: 0.68rem;
  height: 0.68rem;
  display: inline-block;
}

.select-option-row__badge {
  flex-shrink: 0;
  padding: 0.06rem 0.34rem;
  border-radius: 999px;
  font-size: 0.6rem;
  font-weight: 700;
  color: #0b6b2d;
  background: #d4f7df;
  border: 1px solid #97e0b0;
}

.br-item--empty {
  padding: 1rem;
  color: var(--secondary-text-color-02, #888);
  font-style: italic;
}

.select-autocomplete label {
  color: var(--dark-text-color);
}
</style>
