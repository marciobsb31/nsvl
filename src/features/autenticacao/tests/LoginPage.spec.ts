import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

const getRedirectUrl = vi.fn()
vi.mock('@/services/AuthService', () => ({
  default: {
    getRedirectUrl: (...args: unknown[]) => getRedirectUrl(...args),
  },
}))

const apiPost = vi.fn()
vi.mock('@/services/ApiService', () => ({
  default: {
    post: (...args: unknown[]) => apiPost(...args),
  },
}))

const routerReplace = vi.fn()
vi.mock('vue-router', async () => {
  const actual = await vi.importActual<typeof import('vue-router')>('vue-router')
  return {
    ...actual,
    useRouter: () => ({
      replace: (...args: unknown[]) => routerReplace(...args),
    }),
  }
})

import { useAuthStore } from '@/stores/authStore'

const PublicLayoutStub = {
  name: 'PublicLayout',
  template: '<div data-testid="public-layout"><slot /></div>',
}

let envOverride: Record<string, unknown> | null = null

async function loadLoginPage() {
  vi.resetModules()

  if (envOverride && typeof envOverride.VITE_GOVBR_ENABLED === 'string') {
    process.env.VITE_GOVBR_ENABLED = envOverride.VITE_GOVBR_ENABLED
  }

  const baseEnv = {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    ...(((import.meta as any).env ?? {}) as Record<string, unknown>),
  }
  const nextEnv = envOverride ? { ...baseEnv, ...envOverride } : baseEnv
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  Object.defineProperty(import.meta as any, 'env', {
    value: nextEnv,
    configurable: true,
    writable: true,
  })

  const mod = await import('../pages/LoginPage.vue')
  return mod.default
}

async function mountLoadedPage() {
  const LoginPage = await loadLoginPage()
  return mount(LoginPage, {
    attachTo: document.body,
    global: {
      stubs: {
        PublicLayout: PublicLayoutStub,
      },
    },
  })
}

describe('LoginPage (/login)', () => {
  let originalLocation: Location

  beforeEach(() => {
    document.body.innerHTML = ''
    setActivePinia(createPinia())
    vi.clearAllMocks()

    sessionStorage.clear()

    envOverride = { VITE_GOVBR_ENABLED: 'true' }
    process.env.VITE_GOVBR_ENABLED = 'true'

    originalLocation = window.location
    // jsdom: sobrescreve location para permitir setar hash e href via atribuição
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    delete (window as any).location
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    ;(window as any).location = {
      ...originalLocation,
      hash: '',
      href: originalLocation.href,
      pathname: '/login',
    }
  })

  afterEach(() => {
    document.body.innerHTML = ''
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    delete (window as any).location
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    ;(window as any).location = originalLocation
  })

  it('renderiza textos e botões do GOV.BR', async () => {
    const w = await mountLoadedPage()
    await flushPromises()

    expect(w.text()).toContain('Acesse o sistema')
    expect(w.text()).toContain('Entrar com GOV.BR')
    expect(w.text()).toContain('Solicitar cadastro')
  })

  it('ao clicar em Entrar com GOV.BR redireciona para url retornada', async () => {
    getRedirectUrl.mockResolvedValue('https://gov.br/auth')

    const w = await mountLoadedPage()
    await flushPromises()

    const btn = w.find('[aria-label="Entrar com GOV.BR"]')
    await btn.trigger('click')
    await flushPromises()

    expect(getRedirectUrl).toHaveBeenCalled()
    expect(window.location.href).toBe('https://gov.br/auth')
  })

  it('ao clicar em Solicitar cadastro redireciona para solicitacao-cadastro', async () => {
    const w = await mountLoadedPage()
    await flushPromises()

    const btn = w.find('[aria-label="Solicitar cadastro"]')
    await btn.trigger('click')
    await flushPromises()

    expect(routerReplace).toHaveBeenCalledWith({ name: 'solicitacao-cadastro' })
    expect(getRedirectUrl).not.toHaveBeenCalledWith('solicitacao')
  })

  it('quando retorna hash com govbr_error de solicitação de cadastro, navega para solicitacao-cadastro com query', async () => {
    window.location.hash =
      '#govbr_error=Solicitar%20acesso%20e%20aguardar%20avalia%C3%A7%C3%A3o&govbr_nome=Ana&govbr_cpf=123'

    await mountLoadedPage()
    await flushPromises()

    expect(routerReplace).toHaveBeenCalledWith({
      name: 'solicitacao-cadastro',
      query: { nome: 'Ana', cpf: '123' },
    })
  })

  it('quando retorna hash com govbr_login_code faz exchange, salva token, seta usuário e navega para home', async () => {
    window.location.hash = '#govbr_login_code=abc'
    apiPost.mockResolvedValue({
      data: {
        token: 't123',
        user: { id: 1, name: 'User', perfis_vigentes: [] },
      },
    })

    const auth = useAuthStore()
    const setUserSpy = vi.spyOn(auth, 'setUser')

    await mountLoadedPage()
    await flushPromises()

    expect(apiPost).toHaveBeenCalledWith('/auth/exchange', { code: 'abc' })
    expect(sessionStorage.getItem('nvsl_token')).toBe('t123')
    expect(setUserSpy).toHaveBeenCalledWith({ id: 1, name: 'User', perfis_vigentes: [] })
    expect(routerReplace).toHaveBeenCalledWith({ name: 'home' })
  })

  it('quando GOV.BR está desabilitado exibe mensagem e desabilita botões', async () => {
    envOverride = { VITE_GOVBR_ENABLED: 'false' }
    process.env.VITE_GOVBR_ENABLED = 'false'

    const w = await mountLoadedPage()
    await flushPromises()

    expect(w.text()).toContain('Login GOV.BR indisponível neste ambiente no momento.')
    expect(w.find('[aria-label="Entrar com GOV.BR"]').attributes('disabled')).toBeDefined()
    expect(w.find('[aria-label="Solicitar cadastro"]').attributes('disabled')).toBeUndefined()
  })

  it('quando GOV.BR está desabilitado e o usuário tenta clicar (evento manual) entra no branch de bloqueio do entrarComGovBr', async () => {
    envOverride = { VITE_GOVBR_ENABLED: 'false' }
    process.env.VITE_GOVBR_ENABLED = 'false'

    const w = await mountLoadedPage()
    await flushPromises()

    const btn = w.find('[aria-label="Entrar com GOV.BR"]')
    btn.element.dispatchEvent(new MouseEvent('click', { bubbles: true }))
    await flushPromises()

    expect(w.text()).toContain('Login GOV.BR indisponível neste ambiente no momento.')
  })

  it('quando getRedirectUrl falha exibe erro e foca a mensagem', async () => {
    getRedirectUrl.mockRejectedValue(new Error('fail'))

    const w = await mountLoadedPage()
    await flushPromises()

    const btn = w.find('[aria-label="Entrar com GOV.BR"]')
    await btn.trigger('click')
    await flushPromises()

    const alert = w.find('[role="alert"]')
    expect(alert.exists()).toBe(true)
    expect(w.text()).toContain('Login GOV.BR indisponível neste ambiente no momento.')

    await w.vm.$nextTick()
    expect(document.activeElement).toBe(alert.element)
  })

  it('quando retorna govbr_error genérico exibe erro', async () => {
    window.location.hash = '#govbr_error=Erro%20qualquer'

    const w = await mountLoadedPage()
    await flushPromises()

    expect(w.text()).toContain('Erro qualquer')
  })

  it('quando exchange falha com message do backend exibe message e foca a mensagem', async () => {
    window.location.hash = '#govbr_login_code=abc'
    apiPost.mockRejectedValue({ response: { data: { message: 'Token inválido' } } })

    const w = await mountLoadedPage()
    await flushPromises()

    const alert = w.find('[role="alert"]')
    expect(alert.exists()).toBe(true)
    expect(w.text()).toContain('Token inválido')
    expect(sessionStorage.getItem('nvsl_token')).toBe(null)

    await w.vm.$nextTick()
    expect(document.activeElement).toBe(alert.element)
  })

  it('quando exchange falha sem message usa fallback', async () => {
    window.location.hash = '#govbr_login_code=abc'
    apiPost.mockRejectedValue(new Error('network'))

    const w = await mountLoadedPage()
    await flushPromises()

    expect(w.text()).toContain('Falha ao concluir a autenticação com GOV.BR.')
  })
})
