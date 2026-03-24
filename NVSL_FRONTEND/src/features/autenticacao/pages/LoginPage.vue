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

          <!-- Overlay de redirecionamento amigável -->
          <Transition name="pendente-fade">
            <div v-if="pendenteCadastro.visivel" class="pendente-overlay" @click="irParaSolicitacao">
              <div class="pendente-card" @click.stop>
                <div class="pendente-icon">
                  <svg viewBox="0 0 52 52" class="pendente-icon__svg">
                    <circle class="pendente-icon__circle" cx="26" cy="26" r="24" fill="none" stroke="#f5a623" stroke-width="3"/>
                    <text class="pendente-icon__text" x="26" y="35" text-anchor="middle" font-size="28" font-weight="700" fill="#f5a623">!</text>
                  </svg>
                </div>

                <h2 class="pendente-title">Cadastro necessário</h2>

                <p class="pendente-msg">
                  Olá, <strong>{{ pendenteCadastro.nome }}</strong>!
                </p>
                <p class="pendente-msg">
                  Para acessar o NVSL, é preciso solicitar seu cadastro. O processo é simples:
                </p>

                <div class="pendente-info">
                  <div class="pendente-info__item">
                    <span class="pendente-info__num">1</span>
                    <span>Preencha o formulário de solicitação</span>
                  </div>
                  <div class="pendente-info__item">
                    <span class="pendente-info__num">2</span>
                    <span>Aguarde a análise (até 5 dias úteis)</span>
                  </div>
                  <div class="pendente-info__item">
                    <span class="pendente-info__num">3</span>
                    <span>Receba a confirmação por e-mail</span>
                  </div>
                </div>

                <button type="button" class="br-button primary block mt-3" @click="irParaSolicitacao">
                  Solicitar acesso agora
                </button>

                <div class="pendente-redirect">
                  <small>Redirecionando em <strong>{{ pendenteCountdown }}s</strong>...</small>
                  <div class="pendente-progress-bar">
                    <div class="pendente-progress-bar__fill" :style="{ width: pendenteProgress + '%' }"></div>
                  </div>
                </div>
              </div>
            </div>
          </Transition>

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
import { onMounted, ref, reactive } from 'vue'
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

const pendenteCadastro = reactive({
  visivel: false,
  nome: '',
  cpf: '',
})
const pendenteCountdown = ref(10)
const pendenteProgress = ref(100)
let pendenteTimer: ReturnType<typeof setInterval> | null = null

function iniciarRedirectSolicitacao(nome: string, cpf: string) {
  pendenteCadastro.nome = nome
  pendenteCadastro.cpf = cpf
  pendenteCadastro.visivel = true
  pendenteCountdown.value = 10
  pendenteProgress.value = 100
  erro.value = ''

  const duration = 10000
  const interval = 50
  const start = Date.now()

  pendenteTimer = setInterval(() => {
    const elapsed = Date.now() - start
    const remaining = Math.max(0, duration - elapsed)
    pendenteCountdown.value = Math.ceil(remaining / 1000)
    pendenteProgress.value = (remaining / duration) * 100

    if (remaining <= 0) {
      irParaSolicitacao()
    }
  }, interval)
}

function irParaSolicitacao() {
  if (pendenteTimer) {
    clearInterval(pendenteTimer)
    pendenteTimer = null
  }
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
      iniciarRedirectSolicitacao(govbrNome, govbrCpf)
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

/* ========== Overlay de redirecionamento ========== */
.pendente-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(4px);
}

.pendente-card {
  background: #fff;
  border-radius: 16px;
  padding: 2rem 2.25rem;
  max-width: 420px;
  width: 90%;
  text-align: center;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
  animation: pendente-slide-up 0.4s ease-out;
}

@keyframes pendente-slide-up {
  from { opacity: 0; transform: translateY(24px); }
  to { opacity: 1; transform: translateY(0); }
}

.pendente-icon {
  width: 64px;
  height: 64px;
  margin: 0 auto 1rem;
}

.pendente-icon__svg {
  width: 100%;
  height: 100%;
}

.pendente-icon__circle {
  stroke-dasharray: 151;
  stroke-dashoffset: 151;
  animation: pendente-circle-draw 0.6s ease-out 0.2s forwards;
}

@keyframes pendente-circle-draw {
  to { stroke-dashoffset: 0; }
}

.pendente-icon__text {
  opacity: 0;
  animation: pendente-text-pop 0.3s ease-out 0.7s forwards;
}

@keyframes pendente-text-pop {
  from { opacity: 0; transform: scale(0.5); }
  to { opacity: 1; transform: scale(1); }
}

.pendente-title {
  font-size: 1.35rem;
  font-weight: 700;
  color: #0c326f;
  margin: 0 0 0.75rem;
}

.pendente-msg {
  font-size: 0.925rem;
  color: #555;
  margin: 0 0 0.5rem;
  line-height: 1.5;
}

.pendente-msg strong {
  color: #1351b4;
}

.pendente-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin: 1rem 0 0.5rem;
  text-align: left;
}

.pendente-info__item {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  font-size: 0.875rem;
  color: #444;
}

.pendente-info__num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #1351b4;
  color: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  flex-shrink: 0;
}

.pendente-redirect {
  margin-top: 1rem;
  text-align: center;
}

.pendente-redirect small {
  font-size: 0.8rem;
  color: #888;
}

.pendente-progress-bar {
  width: 100%;
  height: 4px;
  background: #e0e0e0;
  border-radius: 4px;
  margin-top: 0.4rem;
  overflow: hidden;
}

.pendente-progress-bar__fill {
  height: 100%;
  background: linear-gradient(90deg, #1351b4, #00bcd4);
  border-radius: 4px;
  transition: width 0.1s linear;
}

/* Transition */
.pendente-fade-enter-active { transition: opacity 0.35s ease; }
.pendente-fade-leave-active { transition: opacity 0.2s ease; }
.pendente-fade-enter-from,
.pendente-fade-leave-to { opacity: 0; }

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
