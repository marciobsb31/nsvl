import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import { mount, flushPromises } from '@vue/test-utils'
import PainelDetalharSolicitacao from './PainelDetalharSolicitacao.vue'

const mockPerfilAtivo = ref<{ nome: string } | null>({ nome: 'Gestor Federal' })
const mockUser = ref<{ contexto?: { esfera?: string } } | null>({
  contexto: { esfera: 'federal' },
})
const mockHasPermissao = vi.fn(() => true)
const mockOpcoesPerfil = ref([
  { value: 1, label: 'Gestor Federal' },
  { value: 2, label: 'Gestor Estadual' },
  { value: 3, label: 'Gestor Municipal' },
  { value: 4, label: 'Administrador Estadual' },
  { value: 5, label: 'Administrador Municipal' },
  { value: 6, label: 'Visitante Federal' },
])
const mockCarregarPerfis = vi.fn(async () => undefined)

vi.mock('@/core/composables/useAuth', () => ({
  useAuth: () => ({
    perfilAtivo: mockPerfilAtivo,
    user: mockUser,
  }),
}))

vi.mock('@/core/composables/usePermissoes', () => ({
  usePermissoes: () => ({
    hasPermissao: mockHasPermissao,
  }),
}))

vi.mock('@/core/composables/usePerfis', () => ({
  usePerfis: () => ({
    opcoesPerfil: mockOpcoesPerfil,
    carregarPerfis: mockCarregarPerfis,
  }),
}))

vi.mock('@/core/composables/useBreakpoint', () => ({
  useBreakpoint: () => ({
    isMobile: ref(false),
  }),
}))

function detalheBase(overrides: Record<string, unknown> = {}) {
  return {
    id: 1,
    nome: 'Maria Silva',
    status: 'em_analise',
    pode_avaliar: true,
    email_institucional: 'maria@org.gov.br',
    telefone_institucional: '6133334444',
    esfera: 'estadual',
    estado: 'Goiás',
    municipio: 'Goiânia',
    orgao: 'Secretaria X',
    cargo: 'Analista',
    perfis_vinculados: [],
    ...overrides,
  }
}

function mountComponent(detalhe = detalheBase()) {
  return mount(PainelDetalharSolicitacao, {
    props: {
      detalhe,
      avaliando: false,
      ehProprioCadastro: false,
    },
    global: {
      stubs: {
        Modal: { template: '<div><slot /></div>' },
        PaginationControls: { template: '<div />' },
      },
    },
  })
}

describe('PainelDetalharSolicitacao', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mockPerfilAtivo.value = { nome: 'Gestor Federal' }
    mockUser.value = { contexto: { esfera: 'federal' } }
    mockHasPermissao.mockReturnValue(true)
  })

  it('exibe ações de aprovação apenas quando pode_avaliar é true', async () => {
    const wrapper = mountComponent(detalheBase({ pode_avaliar: false }))
    await flushPromises()

    expect(wrapper.text()).not.toContain('Aguardando Avaliação')
    expect(wrapper.text()).not.toContain('Aprovar')
  })

  it('filtra a lista de perfis para operador estadual', async () => {
    mockPerfilAtivo.value = { nome: 'Gestor Estadual' }
    mockUser.value = { contexto: { esfera: 'estadual' } }

    const wrapper = mountComponent()
    await flushPromises()

    const options = wrapper.findAll('#perfil-selecao option').map((item) => item.text())
    expect(options).toContain('Gestor Estadual')
    expect(options).toContain('Administrador Estadual')
    expect(options).not.toContain('Gestor Federal')
    expect(options).not.toContain('Gestor Municipal')
  })

  it('desabilita gerenciamento de perfis sem permissão de avaliação', async () => {
    mockHasPermissao.mockReturnValue(false)

    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.find('button.br-button.primary').exists()).toBe(false)
    expect(wrapper.find('.perfis-vinculados-footer .br-button').attributes('disabled')).toBeDefined()
  })
})
