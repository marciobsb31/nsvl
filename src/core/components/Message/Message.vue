<template>
  <div class="br-message m-0" :class="[type, customClass]">
    <div class="icon" v-if="icon">
      <i :class="iconMessage" aria-hidden="true"></i>
    </div>

    <div
      class="content"
      :role="type === 'danger' ? 'alert' : 'status'"
      :aria-live="type === 'danger' ? 'assertive' : 'polite'"
      aria-atomic="true"
    >
      <span class="message-body" v-html="message" v-if="message"></span>
      <slot v-else></slot>
    </div>
    <div class="close" v-if="close">
      <button class="br-button circle small" type="button" aria-label="Fechar mensagem" @click="$emit('close')"><i
          class="fas fa-times" aria-hidden="true"></i>
      </button>
    </div>
  </div>

</template>

<script setup lang="ts">
import { computed } from 'vue';
const props = defineProps({
  title: String,
  message: { type: String, required: false, },
  type: { type: String, default: 'info' },
  icon: { type: Boolean, required: false, default: false  },
  customClass: { type: String, required: false },
  close:{ type: Boolean, default: false }
})

defineEmits<{ (e: 'close'): void }>()

const iconMessage = computed(() => {
  switch (props.type) {
    case 'info':
      return 'fas fa-info-circle fa-lg';
    case 'success':
      return 'fas fa-check-circle fa-lg';
    case 'warning':
      return 'fas fa-exclamation-triangle fa-lg';
    case 'danger':
      return 'fas fa-times-circle fa-lg';
    default:
      return '';
  }
});



</script>

<style scoped></style>