<template>
  <div class="notification-popup-container" aria-label="Notificações do sistema">
    <div
      v-for="n in notifications"
      :key="n.id"
      class="notification-popup-item"
      :role="n.type === 'danger' ? 'alert' : 'status'"
      :aria-live="n.type === 'danger' ? 'assertive' : 'polite'"
      aria-atomic="true"
    >
      <div class="br-message" :class="n.type">
        <div class="icon">
          <i
            class="fas"
            :class="n.type === 'success' ? 'fa-check-circle' : n.type === 'danger' ? 'fa-times-circle' : n.type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'"
            aria-hidden="true"
          ></i>
        </div>
        <div class="content">
          <strong v-if="n.title">{{ n.title }} </strong>{{ n.message }}
        </div>
        <div class="close">
          <button
            class="br-button circle small"
            type="button"
            aria-label="Fechar notificação"
            @click="remove(n.id)"
          >
            <i class="fas fa-times" aria-hidden="true"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useNotification } from '@/core/composables/useNotification'

defineOptions({ name: 'NotificationPopup' })

const { notifications, remove } = useNotification()
</script>

<style scoped>
.notification-popup-container {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 11000;
  width: min(420px, calc(100vw - 2rem));
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.notification-popup-item .br-message {
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

@media (max-width: 575px) {
  .notification-popup-container {
    top: auto;
    bottom: 1rem;
    left: 1rem;
    right: 1rem;
    width: auto;
  }
}
</style>
