<template>
  <AuthLayout>
    <div class="callback-page" role="status" aria-live="polite" aria-label="Autenticando">
      <template v-if="error">
        <br-message
          state="danger"
          :message="error"
          role="alert"
          aria-live="assertive"
        />
        <button
          class="br-button primary"
          type="button"
          @click="router.push({ name: 'login' })"
          aria-label="Voltar para a página de login"
        >
          Voltar ao login
        </button>
      </template>
      <template v-else>
        <br-loading label="Finalizando autenticação, aguarde..." />
        <p>Autenticando com GOV.BR...</p>
      </template>
    </div>
  </AuthLayout>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { useAuth } from '@/core/composables/useAuth'

defineOptions({ name: 'CallbackPage' })

const router = useRouter()
const { handleCallback, error } = useAuth()

onMounted(async () => {
  await handleCallback()
  if (!error.value) {
    router.replace({ name: 'home' })
  }
})
</script>

<style scoped>
.callback-page {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1.5rem;
  text-align: center;
  min-height: 200px;
}
</style>
