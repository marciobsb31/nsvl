<template>
  <PublicLayout>
    <div class="login-page">
      <div class="login-card br-card">
        <div class="login-panel login-panel--actions">
          <div class="login-header">
            <h1 class="login-title">Acesse o sistema</h1>
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
              :disabled="carregandoGovBr"
              aria-label="Entrar com GOV.BR"
              @click="entrarComGovBr"
            >
              {{ carregandoGovBr ? 'Redirecionando...' : 'Entrar com GOV.BR' }}
            </button>
          </div>

          <!-- Perfil de acesso (apenas para testes locais — comentar quando não necessário) -->
          <div class="login-divider">
            <span>ou</span>
          </div>

          <form @submit.prevent="entrar" class="login-form">
            <div class="login-perfil-field mb-3">
              <label for="perfil" class="login-perfil-label">Perfil de acesso</label>
              <select id="perfil" v-model="perfil" class="login-perfil-select" required>
                <option value="federal">Federal — acesso a todas as solicitações</option>
                <option value="estadual">Estadual (GO) — apenas solicitações da UF GO</option>
                <option value="municipal">Municipal (Alexânia/GO) — apenas Alexânia</option>
              </select>
            </div>

            <button
              type="submit"
              class="br-button primary block"
              :disabled="carregando"
              aria-label="Entrar"
            >
              {{ carregando ? 'Entrando...' : 'Entrar' }}
            </button>
          </form>

          <button
            type="button"
            class="br-button success block mt-3 login-register-button"
            :disabled="carregandoGovBr"
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
import PublicLayout from '@/layouts/PublicLayout.vue'

defineOptions({ name: 'LoginPage' })

const router = useRouter()
const authStore = useAuthStore()

const carregandoGovBr = ref(false)
const carregando = ref(false)
const perfil = ref<'federal' | 'estadual' | 'municipal'>('federal')
const erro = ref('')

async function entrar() {
  carregando.value = true
  erro.value = ''
  try {
    const { data } = await api.post<{ token: string; user: Record<string, unknown> }>(
      '/auth/token-de-teste',
      { perfil: perfil.value }
    )
    sessionStorage.setItem('nvsl_token', data.token)
    authStore.setUser(data.user)
    await router.replace({ name: 'gerenciar-cadastros' })
  } catch (e: unknown) {
    const ax = e as { response?: { data?: { message?: string } }; message?: string }
    erro.value =
      ax.response?.data?.message
      ?? ax.message
      ?? 'Falha ao obter token de teste. Rode no backend: php artisan db:seed (usuários de teste ausentes).'
  } finally {
    carregando.value = false
  }
}

onMounted(() => {
  processarRetornoGovBr()
})

async function entrarComGovBr() {
  carregandoGovBr.value = true
  erro.value = ''
  try {
    const { data } = await api.get<{ url?: string } | string>('/auth/redirect')
    const url = typeof data === 'string' ? data : data?.url
    if (!url) {
      throw new Error('URL de autenticação GOV.BR não disponível.')
    }
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
  text-align: center;
}

.login-govbr {
  margin-bottom: 1rem;
}

.login-govbr__button {
  min-height: 2.5rem;
  font-weight: 600;
}

.login-divider {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0.75rem 0 1rem;
  color: var(--color-secondary-06, #666);
  font-size: 0.875rem;
}

.login-divider::before,
.login-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--color-secondary-04, #ddd);
}

.login-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-primary-default, #1351b4);
  margin: 0 0 0.5rem;
}

.login-subtitle {
  color: var(--color-secondary-07, #555);
  margin: 0;
  font-size: 0.9375rem;
}

.login-form .block {
  width: 100%;
}

.login-perfil-field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.login-perfil-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-secondary-08, #333);
}

.login-perfil-select {
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-family: inherit;
  color: var(--color-secondary-09, #333);
  background-color: var(--bg-color, #fff);
  border: 1px solid var(--color-secondary-04, #ccc);
  border-radius: 6px;
  appearance: auto;
  cursor: pointer;
}

.login-perfil-select:focus {
  outline: 3px solid var(--color-support-05, #ffcd07);
  outline-offset: 2px;
  border-color: var(--color-primary-default, #1351b4);
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
