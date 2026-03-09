<template>
  <div class="br-select mb-2">
    <div class="br-input">
      <label :for="`select-${props.label}`">
        {{ props.label }}
        <span v-if="props.required" class="text-red-50 text-up-01">*</span>
      </label>

      <input
        :id="`select-${props.label}`"
        type="text"
        :placeholder="props.placeholder"
        :value="selectedLabel"
        readonly
      />

      <button
        class="br-button"
        type="button"
        :aria-label="`Exibir lista de ${props.label}`"
        tabindex="-1"
        :data-trigger="`data-trigger-${props.label}`"
      >
        <i class="fas fa-angle-down" aria-hidden="true"></i>
      </button>
    </div>

    <div class="br-list" tabindex="0">
      <div
        class="br-item"
        tabindex="-1"
        v-for="option in props.options"
        :key="option.value"
      >
        <div class="br-radio">
          <input
            :id="`rb-${option.value}`"
            type="radio"
            :name="`estados-simples-${props.label}`"
            :value="option.value"
            :checked="props.modelValue === option.value"
            @change="handleChange(option.value)"
          />
          <label :for="`rb-${option.value}`">{{ option.label }}</label>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

defineOptions({
  name: 'Select'
})

type SelectOption = {
  label: string
  value: string | number
}

const props = defineProps<{
  label: string
  placeholder: string
  options: SelectOption[]
  required?: boolean
  modelValue?: string | number | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number | null): void
}>()

const selectedLabel = computed(() => {
  const selectedOption = props.options.find(
    option => option.value === props.modelValue
  )

  return selectedOption?.label || ''
})

function handleChange(value: string | number) {
  emit('update:modelValue', value)
}
</script>

<style>
.br-select {
  max-width: 100%;
}
</style>