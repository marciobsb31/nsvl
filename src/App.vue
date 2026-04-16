<template>
  <!-- Skip Link para acessibilidade — navegação por teclado -->
  <a href="#main-content" class="skip-link">Ir para o conteúdo principal</a>
  <NotificationPopup />
  <Accessibility />

  <!-- Renderiza o layout correspondente à rota -->
  <RouterView v-slot="{ Component, route }">
    <component :is="route.meta.layout ?? 'div'">
      <Transition name="fade" mode="out-in">
        <component :is="Component" :key="route.path" />
      </Transition>
    </component>
  </RouterView>
</template>

<script setup lang="ts">
import { RouterView } from 'vue-router'
import NotificationPopup from '@/core/components/NotificationPopup/NotificationPopup.vue'
import Accessibility from '@/core/components/Accessibility/Accessibility.vue'
</script>

<style>
/* Transição suave entre páginas */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
