<template>
  <PublicLayout>
    <div class="login-page">
      <div class="login-card br-card">
        <div class="login-panel login-panel--actions">
          <div class="login-header">
            <h1 class="login-title text-center">Acesse o sistema</h1>
            <p class="login-subtitle">
              Entre com sua conta GOV.BR para acessar o sistema.
            </p>
          </div>

          <div v-if="erro" class="br-message danger mb-3" role="alert">
            <div class="content">{{ erro }}</div>
          </div>

          <div class="login-govbr">
            <button
              type="button"
              class="br-button secondary block login-govbr__button"
              :disabled="carregandoGovBr || !govBrDisponivel"
              aria-label="Entrar com GOV.BR"
              @click="entrarComGovBr"
            >
              {{ carregandoGovBr ? 'Redirecionando...' : 'Entrar com GOV.BR' }}
            </button>
          </div>

          <button
            type="button"
            class="br-button success block mt-3 login-register-button"
            :disabled="carregandoGovBr || !govBrDisponivel"
            aria-label="Solicitar cadastro"
            @click="entrarComGovBr"
          >
            {{ carregandoGovBr ? 'Redirecionando...' : 'Solicitar cadastro' }}
          </button>
        </div>
      </div>
    </div>
  </PublicLayout>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import api from '@/services/ApiService'
import AuthService from '@/services/AuthService'
import PublicLayout from '@/layouts/PublicLayout.vue'

defineOptions({ name: 'LoginPage' })

const router = useRouter()
const authStore = useAuthStore()

const govBrDisponivel = String(import.meta.env.VITE_GOVBR_ENABLED ?? 'true').toLowerCase() === 'true'
const carregandoGovBr = ref(false)
const erro = ref('')

onMounted(() => {
  if (!govBrDisponivel) {
    erro.value = 'Login GOV.BR indisponível neste ambiente no momento.'
    return
  }

  processarRetornoGovBr()
})

async function entrarComGovBr() {
  if (!govBrDisponivel) {
    erro.value = 'Login GOV.BR indisponível neste ambiente no momento.'
    return
  }

  carregandoGovBr.value = true
  erro.value = ''
  try {
    const url = await AuthService.getRedirectUrl()
    window.location.href = url
  } catch {
    erro.value = 'Login GOV.BR indisponível neste ambiente no momento.'
  } finally {
    carregandoGovBr.value = false
  }
}

async function processarRetornoGovBr() {
  const hash = window.location.hash.replace(/^#/, '')
  if (!hash) return

  const params = new URLSearchParams(hash)
  const loginCode = params.get('govbr_login_code')
  const govbrError = params.get('govbr_error')

  if (!loginCode && !govbrError) return

  window.history.replaceState({}, document.title, window.location.pathname)

  if (govbrError) {
    const govbrNome = params.get('govbr_nome')
    const govbrCpf = params.get('govbr_cpf')
    if (
      govbrError === 'Solicitar acesso e aguardar avaliação' &&
      govbrNome &&
      govbrCpf
    ) {
      await router.replace({
        name: 'solicitacao-cadastro',
        query: { nome: govbrNome, cpf: govbrCpf },
      })
      return
    }
    erro.value = govbrError
    return
  }

  carregandoGovBr.value = true
  erro.value = ''
  try {
    const { data } = await api.post<{ token: string; user: Record<string, unknown> }>(
      '/auth/exchange',
      { code: loginCode }
    )
    sessionStorage.setItem('nvsl_token', data.token)
    authStore.setUser(data.user)
    await router.replace({ name: 'gerenciar-cadastros' })
  } catch (e: unknown) {
    const res = (e as { response?: { data?: { message?: string } } })?.response
    erro.value = res?.data?.message ?? 'Falha ao concluir a autenticação com GOV.BR.'
  } finally {
    carregandoGovBr.value = false
  }
}
</script>

<style scoped>
.login-page {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-card {
  width: 100%;
  max-width: 420px;
  padding: 1.5rem;
  display: block;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  border-radius: 12px;
}

.login-panel {
  min-width: 0;
}

.login-panel--actions {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.login-header {
  margin-bottom: 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.login-govbr {
  margin-bottom: 1rem;
}

.login-govbr__button {
  min-height: 2.5rem;
  font-weight: 600;
}

.login-title {
  width: 100%;
  text-align: center;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-primary-default, #1351b4);
  margin: 0 0 0.5rem;
}

.login-subtitle {
  width: 100%;
  text-align: center;
  color: var(--secondary-text-color);
  margin: 0;
  font-size: 1.0625rem;
  line-height: 1.45;
}

.login-register-button {
  width: 100%;
  min-height: 2.5rem;
  font-weight: 600;
}

@media (max-width: 767px) {
  .login-card {
    max-width: 360px;
    padding: 1.25rem;
  }
}
</style>
