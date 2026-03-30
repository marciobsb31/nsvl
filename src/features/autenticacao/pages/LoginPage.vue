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

          <div
            v-if="erro"
            id="login-error"
            ref="errorAlert"
            class="br-message danger mb-3"
            role="alert"
            tabindex="-1"
          >
            <div class="content">{{ erro }}</div>
          </div>

          <div class="login-govbr">
            <button
              type="button"
              class="br-button secondary block login-govbr__button"
              :disabled="carregandoGovBr"
              @click="entrarComGovBr"
            >
              {{ carregandoGovBr ? 'Redirecionando...' : 'Entrar com GOV.BR' }}
            </button>
          </div>

          <!-- Perfil de acesso (apenas para testes locais — comentar quando não necessário) -->
          <div class="login-divider" aria-hidden="true">
            <span>ou</span>
          </div>

          <form
            @submit.prevent="entrar"
            class="login-form"
            :aria-busy="carregando || carregandoGovBr"
          >
            <div class="login-perfil-field mb-3">
              <label for="perfil" class="login-perfil-label">Perfil de acesso</label>
              <span id="perfil-help" class="sr-only">
                Campo usado apenas para testes locais.
              </span>
              <select
                id="perfil"
                v-model="perfil"
                class="login-perfil-select"
                required
                aria-describedby="perfil-help"
              >
                <option value="federal">Federal — acesso a todas as solicitações</option>
                <option value="estadual">Estadual (GO) — apenas solicitações da UF GO</option>
                <option value="municipal">Municipal (Alexânia/GO) — apenas Alexânia</option>
              </select>
            </div>

            <button
              type="submit"
              class="br-button primary block"
              :disabled="carregando"
            >
              {{ carregando ? 'Entrando...' : 'Entrar' }}
            </button>
          </form>

          <button
            type="button"
            class="br-button success block mt-3 login-register-button"
            :disabled="carregandoGovBr"
            @click="entrarComGovBr"
          >
            {{ carregandoGovBr ? 'Redirecionando...' : 'Solicitar cadastro' }}
          </button>

        </div>

        <!-- <div class="login-divider-vertical" aria-hidden="true"></div> -->

        <!-- <div class="login-panel login-panel--brand">
          <img
            class="login-brand-main"
            :src="logoPrincipal"
            alt="Novo Viver Sem Limite"
          />
          <img
            class="login-brand-gov"
            :src="logoGoverno"
            alt="Ministerio dos Direitos Humanos e da Cidadania e Governo do Brasil"
          />
        </div> -->
      </div>
    </div>
  </PublicLayout>
</template>

<script setup lang="ts">
import { nextTick, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import api from '@/services/ApiService'
import AuthService from '@/services/AuthService'
import PublicLayout from '@/layouts/PublicLayout.vue'
import logoPrincipal from '@/assets/images/logo/logo_novo_viver.png'
import logoGoverno from '@/assets/images/logo/mdh_com_gov.png'

defineOptions({ name: 'LoginPage' })

const router = useRouter()
const authStore = useAuthStore()

const carregandoGovBr = ref(false)
const carregando = ref(false)
const perfil = ref<'federal' | 'estadual' | 'municipal'>('federal')
const erro = ref('')
const errorAlert = ref<HTMLElement | null>(null)

watch(erro, async (value) => {
  if (!value) return
  await nextTick()
  errorAlert.value?.focus()
})

// ── Token de teste (apenas dev) ────────────────────────────────────────────────
async function entrar() {
  carregando.value = true
  erro.value = ''
  try {
    const { data } = await api.post<{ token: string; user: Record<string, unknown> }>(
      '/auth/token-de-teste',
      { perfil: perfil.value },
    )
    sessionStorage.setItem('nvsl_token', data.token)
    authStore.setUser(data.user)
    await router.replace({ name: 'gerenciar-cadastros' })
  } catch (e: unknown) {
    const res = (e as { response?: { data?: { message?: string } } })?.response
    erro.value = res?.data?.message ?? 'Falha ao obter token de teste.'
  } finally {
    carregando.value = false
  }
}

// ── GOV.BR ─────────────────────────────────────────────────────────────────────

onMounted(() => {
  processarRetornoGovBr()
})

/**
 * Inicia o fluxo OAuth: pede ao backend a URL de autorização do GOV.BR
 * e redireciona o browser para lá.
 */
async function entrarComGovBr() {
  carregandoGovBr.value = true
  erro.value = ''
  try {
    const url = await AuthService.getRedirectUrl()
    window.location.href = url
  } catch {
    erro.value = 'Login GOV.BR indisponível neste ambiente no momento.'
    carregandoGovBr.value = false
  }
}

/**
 * Processa o retorno do GOV.BR após o callback do backend.
 *
 * O backend redireciona para /login#govbr_login_code=ABC ou #govbr_error=...
 * Este método lê o hash, limpa a URL e troca o código temporário pelo token Sanctum.
 */
async function processarRetornoGovBr() {
  const hash = window.location.hash.replace(/^#/, '')
  if (!hash) return

  const params = new URLSearchParams(hash)
  const loginCode = params.get('govbr_login_code')
  const govbrError = params.get('govbr_error')

  if (!loginCode && !govbrError) return

  // Remove o hash da URL para não vazar o código no histórico do browser
  window.history.replaceState({}, document.title, window.location.pathname)

  if (govbrError) {
    erro.value = govbrError

    // Usuário sem cadastro → redireciona para solicitação pré-preenchida
    const govbrNome = params.get('govbr_nome')
    const govbrCpf = params.get('govbr_cpf')
    if (govbrError === 'Solicitar acesso e aguardar avaliação' && govbrNome && govbrCpf) {
      setTimeout(() => {
        router.push({
          name: 'solicitacao-cadastro',
          query: { nome: govbrNome, cpf: govbrCpf },
        })
      }, 2500)
    }
    return
  }

  // Troca o código temporário pelo token Sanctum
  carregandoGovBr.value = true
  erro.value = ''
  try {
    const { token, user } = await AuthService.exchangeCode(loginCode!)
    sessionStorage.setItem('nvsl_token', token)
    authStore.setUser(user)
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
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.login-page {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-card {
  width: 100%;
  max-width: 400px;
  padding: 1.5rem;
  display: grid;
  /* grid-template-columns: minmax(260px, 300px) 4px minmax(200px, 1fr); */
  align-items: stretch;
  gap: 1.25rem;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  border-radius: 12px;
  border: 2px solid var(--border-card-color);
}

.login-panel {
  min-width: 0;
}

.login-panel--actions {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.login-panel--brand {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  text-align: center;
}

.login-header {
  margin-bottom: 1rem;
  text-align: center;
}

.login-divider-vertical {
  width: 4px;
  border-radius: 999px;
  background: linear-gradient(180deg, #00bcd4 0%, #00a0c6 100%);
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
  color: var(--secondary-text-color);
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
  color: var(--primary-text-color);
  margin: 0 0 0.5rem;
}

.login-subtitle {
  color: var(--secondary-text-color);
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
  color: var(--dark-text-color);
}

.login-perfil-select {
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-family: inherit;
  color: var(--dark-text-color);
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

.login-brand-main {
  width: 100%;
  max-width: 260px;
  height: auto;
}

.login-brand-gov {
  width: 100%;
  max-width: 200px;
  height: auto;
}

@media (max-width: 767px) {
  .login-card {
    max-width: 360px;
    grid-template-columns: 1fr;
    gap: 1rem;
    padding: 1.25rem;
  }

  .login-divider-vertical {
    display: none;
  }

  .login-panel--brand {
    order: -1;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--color-secondary-04, #ddd);
  }

  .login-brand-main {
    max-width: 220px;
  }

  .login-brand-gov {
    max-width: 180px;
  }
}
</style>
