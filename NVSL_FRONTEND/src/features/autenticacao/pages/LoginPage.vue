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

          <section
            v-if="pendenteCadastro.visivel"
            ref="cadastroNecessarioRef"
            class="login-cadastro-necessario"
            role="region"
            aria-labelledby="login-cadastro-necessario-titulo"
            tabindex="-1"
          >
            <div class="login-cadastro-necessario__accent" aria-hidden="true" />

            <div class="login-cadastro-necessario__header">
              <div class="login-cadastro-necessario__icon-wrap" aria-hidden="true">
                <svg viewBox="0 0 52 52" class="login-cadastro-necessario__svg">
                  <circle
                    class="login-cadastro-necessario__circle"
                    cx="26"
                    cy="26"
                    r="24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="3"
                  />
                  <text
                    class="login-cadastro-necessario__mark"
                    x="26"
                    y="35"
                    text-anchor="middle"
                    font-size="28"
                    font-weight="700"
                    fill="currentColor"
                  >
                    !
                  </text>
                </svg>
              </div>
              <h2 id="login-cadastro-necessario-titulo" class="login-cadastro-necessario__titulo">
                Cadastro necessário
              </h2>
            </div>

            <p class="login-cadastro-necessario__resumo">
              Olá, <strong>{{ pendenteCadastro.nome }}</strong>. Você precisa solicitar cadastro no NVSL. Preencha o
              formulário; a análise leva até <strong>5 dias úteis</strong> e o retorno é por e-mail.
            </p>

            <p class="login-cadastro-necessario__hint" id="login-cadastro-necessario-hint">
              Abriremos a solicitação em instantes — ou use o botão abaixo.
            </p>

            <div class="login-cadastro-necessario__footer">
              <div class="login-cadastro-necessario__countdown" aria-live="polite" aria-describedby="login-cadastro-necessario-hint">
                <p class="login-cadastro-necessario__countdown-text">
                  Redirecionamento em <strong>{{ pendenteCountdown }}s</strong>
                </p>
                <div
                  class="login-cadastro-necessario__progress"
                  role="progressbar"
                  :aria-valuenow="Math.round(pendenteProgress)"
                  aria-valuemin="0"
                  aria-valuemax="100"
                  aria-label="Tempo até o redirecionamento para a solicitação de cadastro"
                >
                  <div class="login-cadastro-necessario__progress-fill" :style="{ width: pendenteProgress + '%' }" />
                </div>
              </div>

              <button
                type="button"
                class="br-button primary login-cadastro-necessario__cta"
                aria-label="Ir para a solicitação de cadastro agora"
                @click="irParaSolicitacao"
              >
                Ir agora
              </button>
            </div>
          </section>

          <div v-if="erro && !pendenteCadastro.visivel" class="br-message danger mb-3" role="alert">
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

        <div class="login-divider-vertical" aria-hidden="true"></div>

        <div class="login-panel login-panel--brand">
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
        </div>
      </div>
    </div>
  </PublicLayout>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref, reactive, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import api from '@/services/ApiService'
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

const REDIRECIONAR_APOS_MS = 9000

const pendenteCadastro = reactive({
  visivel: false,
  nome: '',
  cpf: '',
})

const pendenteCountdown = ref(9)
const pendenteProgress = ref(100)
const cadastroNecessarioRef = ref<HTMLElement | null>(null)
let pendenteRedirectTimer: ReturnType<typeof setInterval> | null = null

function limparTimerPendente() {
  if (pendenteRedirectTimer) {
    clearInterval(pendenteRedirectTimer)
    pendenteRedirectTimer = null
  }
}

function mostrarCadastroNecessario(nome: string, cpf: string) {
  limparTimerPendente()
  pendenteCadastro.nome = nome
  pendenteCadastro.cpf = cpf
  pendenteCadastro.visivel = true
  erro.value = ''
  pendenteCountdown.value = Math.ceil(REDIRECIONAR_APOS_MS / 1000)
  pendenteProgress.value = 100

  const start = Date.now()
  const tickMs = 50
  pendenteRedirectTimer = setInterval(() => {
    const elapsed = Date.now() - start
    const remaining = Math.max(0, REDIRECIONAR_APOS_MS - elapsed)
    pendenteCountdown.value = Math.max(0, Math.ceil(remaining / 1000))
    pendenteProgress.value = (remaining / REDIRECIONAR_APOS_MS) * 100
    if (remaining <= 0) {
      limparTimerPendente()
      irParaSolicitacao()
    }
  }, tickMs)
}

function irParaSolicitacao() {
  limparTimerPendente()
  pendenteCadastro.visivel = false
  router.push({
    name: 'solicitacao-cadastro',
    query: { nome: pendenteCadastro.nome, cpf: pendenteCadastro.cpf },
  })
}

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
    const res = (e as { response?: { data?: { message?: string } } })?.response
    erro.value = res?.data?.message ?? 'Falha ao obter token de teste.'
  } finally {
    carregando.value = false
  }
}

onMounted(() => {
  processarRetornoGovBr()
})

onUnmounted(() => {
  limparTimerPendente()
})

watch(
  () => pendenteCadastro.visivel,
  (visivel) => {
    if (!visivel) {
      limparTimerPendente()
      return
    }
    nextTick(() => {
      cadastroNecessarioRef.value?.focus()
    })
  }
)

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
      mostrarCadastroNecessario(govbrNome, govbrCpf)
    } else {
      erro.value = govbrError
    }
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
  max-width: 680px;
  padding: 1.5rem;
  display: grid;
  grid-template-columns: minmax(260px, 300px) 4px minmax(200px, 1fr);
  align-items: stretch;
  gap: 1.25rem;
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

/* “Cadastro necessário” na própria página (sem modal — melhor para leitores de tela e teclado) */
.login-cadastro-necessario {
  position: relative;
  width: 100%;
  min-width: 0;
  margin: 0 0 1rem;
  padding: 0;
  border: 1px solid var(--color-secondary-03, #e8e8e8);
  border-radius: 12px;
  background: var(--color-secondary-01, #f8fafc);
  box-shadow: 0 2px 8px rgba(12, 50, 111, 0.08);
  overflow: hidden;
  outline: none;
  text-align: left;
  box-sizing: border-box;
}

.login-cadastro-necessario:focus-visible {
  box-shadow:
    0 0 0 3px var(--color-support-05, #ffcd07),
    0 2px 8px rgba(12, 50, 111, 0.08);
}

.login-cadastro-necessario__accent {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 5px;
  background: linear-gradient(180deg, #1351b4 0%, #00a0c6 100%);
}

.login-cadastro-necessario__header {
  padding: 0.85rem 1rem 0.35rem;
  text-align: center;
}

.login-cadastro-necessario__icon-wrap {
  width: 2.75rem;
  height: 2.75rem;
  margin: 0 auto 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: linear-gradient(145deg, #fff8e6 0%, #ffefd0 100%);
  color: #c78500;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

.login-cadastro-necessario__svg {
  width: 2.25rem;
  height: 2.25rem;
  display: block;
}

.login-cadastro-necessario__circle {
  stroke-dasharray: 151;
  stroke-dashoffset: 151;
  animation: login-cadastro-circle-draw 0.55s ease-out 0.12s forwards;
}

@keyframes login-cadastro-circle-draw {
  to {
    stroke-dashoffset: 0;
  }
}

.login-cadastro-necessario__mark {
  opacity: 0;
  animation: login-cadastro-mark-pop 0.28s ease-out 0.55s forwards;
}

@keyframes login-cadastro-mark-pop {
  from {
    opacity: 0;
    transform: scale(0.5);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.login-cadastro-necessario__titulo {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: #0c326f;
  line-height: 1.25;
}

.login-cadastro-necessario__resumo {
  margin: 0.5rem 0 0;
  padding: 0 1rem;
  font-size: 0.8125rem;
  color: var(--color-secondary-08, #333);
  line-height: 1.5;
}

.login-cadastro-necessario__resumo strong {
  color: var(--color-primary-darken-02, #0c326f);
}

.login-cadastro-necessario__hint {
  margin: 0.5rem 1rem 0;
  font-size: 0.75rem;
  color: var(--color-secondary-06, #666);
  line-height: 1.4;
}

.login-cadastro-necessario__footer {
  margin-top: 0.65rem;
  padding: 0 1rem 1rem;
  min-width: 0;
  box-sizing: border-box;
}

.login-cadastro-necessario__countdown {
  margin-bottom: 0.65rem;
}

.login-cadastro-necessario__countdown-text {
  margin: 0 0 0.4rem;
  font-size: 0.8125rem;
  color: var(--color-secondary-07, #555);
  line-height: 1.4;
  text-align: center;
}

.login-cadastro-necessario__countdown-text strong {
  color: var(--color-primary-default, #1351b4);
  font-weight: 700;
}

.login-cadastro-necessario__progress {
  height: 5px;
  border-radius: 5px;
  background: var(--color-secondary-03, #e8e8e8);
  overflow: hidden;
}

.login-cadastro-necessario__progress-fill {
  height: 100%;
  border-radius: 5px;
  background: linear-gradient(90deg, #1351b4, #00bcd4);
  transition: width 0.08s linear;
}

.login-cadastro-necessario__cta {
  display: block;
  width: 100%;
  margin: 0;
  min-height: 2.4rem;
  font-weight: 600;
  font-size: 0.875rem;
  line-height: 1.35;
  padding: 0.5rem 0.85rem;
  box-sizing: border-box;
  text-align: center;
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
