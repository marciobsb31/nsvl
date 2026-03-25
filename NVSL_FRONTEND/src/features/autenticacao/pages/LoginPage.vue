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

    <Teleport to="body">
      <Transition name="pendente-fade">
        <div
          v-if="pendenteCadastro.visivel"
          class="pendente-overlay"
          role="presentation"
          @click="irParaSolicitacao"
          @keydown.esc="irParaSolicitacao"
        >
          <div
            class="pendente-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="login-cadastro-necessario-titulo"
            tabindex="-1"
            @click.stop
            @keydown.esc.stop="irParaSolicitacao"
          >
            <div class="pendente-dialog__accent" aria-hidden="true" />

            <div class="pendente-dialog__header">
              <div class="pendente-dialog__icon-wrap" aria-hidden="true">
                <svg viewBox="0 0 52 52" class="pendente-dialog__svg">
                  <circle
                    class="pendente-dialog__circle"
                    cx="26"
                    cy="26"
                    r="24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="3"
                  />
                  <text
                    class="pendente-dialog__mark"
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
              <p class="pendente-dialog__badge">Próximo passo</p>
              <h2 id="login-cadastro-necessario-titulo" class="pendente-dialog__titulo">
                Cadastro necessário
              </h2>
            </div>

            <p class="pendente-dialog__saudacao">
              Olá, <strong>{{ pendenteCadastro.nome }}</strong>
            </p>
            <p class="pendente-dialog__lead">
              Para acessar o <strong>NVSL</strong>, solicite seu cadastro. O fluxo é rápido e acompanha estes passos:
            </p>

            <ul class="pendente-dialog__passos" aria-label="Etapas do cadastro">
              <li class="pendente-dialog__passo">
                <span class="pendente-dialog__passo-num">1</span>
                <span class="pendente-dialog__passo-texto">Preencha o formulário de solicitação</span>
              </li>
              <li class="pendente-dialog__passo">
                <span class="pendente-dialog__passo-num">2</span>
                <span class="pendente-dialog__passo-texto">Aguarde a análise (até 5 dias úteis)</span>
              </li>
              <li class="pendente-dialog__passo">
                <span class="pendente-dialog__passo-num">3</span>
                <span class="pendente-dialog__passo-texto">Receba a confirmação por e-mail</span>
              </li>
            </ul>

            <p class="pendente-dialog__hint">
              Você pode ir agora ou aguardar o redirecionamento automático. Toque fora desta caixa ou pressione
              <kbd class="pendente-dialog__kbd">Esc</kbd> para ir à solicitação.
            </p>

            <div class="pendente-dialog__footer">
              <div class="pendente-dialog__countdown" aria-live="polite">
                <p class="pendente-dialog__countdown-text">
                  Redirecionamento automático em <strong>{{ pendenteCountdown }}s</strong>
                </p>
                <div
                  class="pendente-dialog__progress"
                  role="progressbar"
                  :aria-valuenow="Math.round(pendenteProgress)"
                  aria-valuemin="0"
                  aria-valuemax="100"
                  aria-label="Tempo até o redirecionamento"
                >
                  <div class="pendente-dialog__progress-fill" :style="{ width: pendenteProgress + '%' }" />
                </div>
              </div>

              <button
                type="button"
                class="br-button primary pendente-dialog__cta"
                @click="irParaSolicitacao"
              >
                Solicitar acesso agora
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
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
      document.querySelector<HTMLElement>('.pendente-dialog')?.focus()
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

/* Overlay + diálogo “Cadastro necessário” */
.pendente-overlay {
  position: fixed;
  inset: 0;
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(12, 50, 111, 0.45);
  backdrop-filter: blur(6px);
}

.pendente-dialog {
  position: relative;
  width: 100%;
  max-width: 28rem;
  min-width: 0;
  padding: 0;
  border: none;
  border-radius: 16px;
  background: #fff;
  box-shadow:
    0 4px 6px rgba(12, 50, 111, 0.06),
    0 24px 48px rgba(12, 50, 111, 0.18);
  overflow: hidden;
  outline: none;
  text-align: left;
  animation: pendente-dialog-in 0.38s cubic-bezier(0.22, 1, 0.36, 1);
  box-sizing: border-box;
}

.pendente-dialog:focus-visible {
  box-shadow:
    0 0 0 3px var(--color-support-05, #ffcd07),
    0 24px 48px rgba(12, 50, 111, 0.18);
}

.pendente-dialog__accent {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 5px;
  background: linear-gradient(180deg, #1351b4 0%, #00a0c6 100%);
}

.pendente-dialog__header {
  padding: 1.75rem 1.75rem 0.5rem;
  padding-left: 1.75rem;
  text-align: center;
}

.pendente-dialog__icon-wrap {
  width: 4rem;
  height: 4rem;
  margin: 0 auto 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: linear-gradient(145deg, #fff8e6 0%, #ffefd0 100%);
  color: #c78500;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

.pendente-dialog__svg {
  width: 2.5rem;
  height: 2.5rem;
  display: block;
}

.pendente-dialog__circle {
  stroke-dasharray: 151;
  stroke-dashoffset: 151;
  animation: pendente-circle-draw 0.55s ease-out 0.12s forwards;
}

@keyframes pendente-circle-draw {
  to {
    stroke-dashoffset: 0;
  }
}

.pendente-dialog__mark {
  opacity: 0;
  animation: pendente-mark-pop 0.28s ease-out 0.55s forwards;
}

@keyframes pendente-mark-pop {
  from {
    opacity: 0;
    transform: scale(0.5);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.pendente-dialog__badge {
  margin: 0 0 0.35rem;
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--color-primary-default, #1351b4);
}

.pendente-dialog__titulo {
  margin: 0;
  font-size: 1.375rem;
  font-weight: 700;
  color: #0c326f;
  line-height: 1.25;
}

.pendente-dialog__saudacao {
  margin: 0;
  padding: 0 1.75rem;
  font-size: 1rem;
  color: var(--color-secondary-08, #333);
  line-height: 1.5;
}

.pendente-dialog__saudacao strong {
  color: var(--color-primary-default, #1351b4);
}

.pendente-dialog__lead {
  margin: 0.65rem 0 0;
  padding: 0 1.75rem;
  font-size: 0.9375rem;
  color: var(--color-secondary-07, #555);
  line-height: 1.55;
}

.pendente-dialog__lead strong {
  color: #0c326f;
  font-weight: 700;
}

.pendente-dialog__passos {
  list-style: none;
  margin: 1.15rem 1.75rem 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.pendente-dialog__passo {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.75rem 0.85rem;
  border-radius: 10px;
  background: var(--color-secondary-01, #f8f8f8);
  border: 1px solid var(--color-secondary-03, #e8e8e8);
  transition: border-color 0.2s ease, background 0.2s ease;
}

.pendente-dialog__passo:hover {
  border-color: rgba(19, 81, 180, 0.25);
  background: #f3f6fb;
}

.pendente-dialog__passo-num {
  flex-shrink: 0;
  width: 1.75rem;
  height: 1.75rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  font-size: 0.8125rem;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(135deg, #1351b4 0%, #0c326f 100%);
  box-shadow: 0 2px 4px rgba(12, 50, 111, 0.2);
}

.pendente-dialog__passo-texto {
  font-size: 0.875rem;
  line-height: 1.45;
  color: var(--color-secondary-08, #333);
  padding-top: 0.1rem;
}

.pendente-dialog__hint {
  margin: 1rem 1.75rem 0;
  font-size: 0.75rem;
  color: var(--color-secondary-06, #666);
  line-height: 1.45;
}

.pendente-dialog__kbd {
  display: inline-block;
  padding: 0.1rem 0.4rem;
  font-size: 0.6875rem;
  font-family: inherit;
  border: 1px solid var(--color-secondary-04, #ccc);
  border-radius: 4px;
  background: #fff;
  box-shadow: 0 1px 0 rgba(0, 0, 0, 0.06);
}

.pendente-dialog__footer {
  margin-top: 1rem;
  padding: 0 1.25rem 1.5rem;
  min-width: 0;
  box-sizing: border-box;
}

@media (min-width: 400px) {
  .pendente-dialog__footer {
    padding: 0 1.75rem 1.75rem;
  }
}

.pendente-dialog__countdown {
  margin-bottom: 1rem;
}

.pendente-dialog__countdown-text {
  margin: 0 0 0.45rem;
  font-size: 0.8125rem;
  color: var(--color-secondary-07, #555);
  line-height: 1.4;
  text-align: center;
}

.pendente-dialog__countdown-text strong {
  color: var(--color-primary-default, #1351b4);
  font-weight: 700;
}

.pendente-dialog__progress {
  height: 5px;
  border-radius: 5px;
  background: var(--color-secondary-03, #e8e8e8);
  overflow: hidden;
}

.pendente-dialog__progress-fill {
  height: 100%;
  border-radius: 5px;
  background: linear-gradient(90deg, #1351b4, #00bcd4);
  transition: width 0.08s linear;
}

.pendente-dialog__cta {
  display: block;
  width: 100%;
  max-width: 100%;
  margin: 0;
  min-height: 2.75rem;
  font-weight: 600;
  font-size: 0.9375rem;
  line-height: 1.35;
  padding: 0.65rem 1rem;
  box-sizing: border-box;
  white-space: normal;
  word-wrap: break-word;
  text-align: center;
}

@keyframes pendente-dialog-in {
  from {
    opacity: 0;
    transform: translateY(1rem) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.pendente-fade-enter-active {
  transition: opacity 0.32s ease;
}

.pendente-fade-leave-active {
  transition: opacity 0.22s ease;
}

.pendente-fade-enter-from,
.pendente-fade-leave-to {
  opacity: 0;
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
