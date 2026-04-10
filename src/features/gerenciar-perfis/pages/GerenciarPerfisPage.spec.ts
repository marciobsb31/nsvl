import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import GerenciarPerfisPage from './GerenciarPerfisPage.vue'
import { useAuthStore } from '@/stores/authStore'
import type { PerfilGerenciar } from '@/services/GerenciarPerfilService'

const listarPerfisGerenciar = vi.fn()

vi.mock('@/services/GerenciarPerfilService', () => ({
  listarPerfisGerenciar: (...args: unknown[]) => listarPerfisGerenciar(...args),
}))

const errorMock = vi.fn()
vi.mock('@/core/composables/useNotification', () => ({
  useNotification: () => ({
    error: errorMock,
  }),
}))

function perfilBase(over: Partial<PerfilGerenciar> = {}): PerfilGerenciar {
  return {
    id: 1,
    nome: 'Gestor Teste',
    descricao: 'Desc',
    ativo: true,
    status: 'ativo',
    ...over,
  }
}

const PaginationStub = {
  name: 'PaginationControls',
  props: ['totalItems', 'currentPage', 'pageSize'],
  template: '<div data-testid="pagination" />',
}

const PainelFormStub = {
  name: 'PainelFormularioPerfil',
  props: ['modo', 'perfil'],
  emits: ['voltar', 'sucesso', 'dirty'],
  template: `
    <div data-testid="painel-form">
      <span data-testid="form-modo">{{ modo }}</span>
      <button type="button" data-testid="emit-dirty" @click="$emit('dirty', true)">Marcar alterado</button>
      <button type="button" data-testid="emit-voltar" @click="$emit('voltar')">Voltar</button>
      <button type="button" data-testid="emit-sucesso" @click="$emit('sucesso')">Salvar ok</button>
    </div>
  `,
}

const PainelHistStub = {
  name: 'PainelHistoricoPerfil',
  props: ['perfil'],
  emits: ['voltar'],
  template: `
    <div data-testid="painel-hist">
      <button type="button" data-testid="hist-voltar" @click="$emit('voltar')">Voltar hist</button>
    </div>
  `,
}

function mountPage() {
  return mount(GerenciarPerfisPage, {
    attachTo: document.body,
    global: {
      stubs: {
        DefaultLayout: { template: '<div><slot /></div>' },
        Card: { template: '<div><slot /></div>' },
        PaginationControls: PaginationStub,
        PainelFormularioPerfil: PainelFormStub,
        PainelHistoricoPerfil: PainelHistStub,
      },
    },
  })
}

function setupAuth(esfera: string) {
  const auth = useAuthStore()
  auth.setUser({
    id: 1,
    name: 'Usuário Teste',
    esfera_atuacao: esfera,
    perfis_vigentes: [],
  })
}

describe('GerenciarPerfisPage (/gerenciar-perfis)', () => {
  beforeEach(() => {
    document.body.innerHTML = ''
    setActivePinia(createPinia())
    setupAuth('federal')
    vi.clearAllMocks()
    listarPerfisGerenciar.mockResolvedValue([])
  })

  afterEach(() => {
    document.body.innerHTML = ''
  })

  it('exibe título e botão Novo perfil', async () => {
    const w = mountPage()
    await flushPromises()
    expect(w.text()).toContain('Gerenciar perfis de acesso no sistema')
    expect(w.find('[aria-label="Cadastrar novo perfil"]').exists()).toBe(true)
  })

  it('ao montar carrega perfis', async () => {
    mountPage()
    await flushPromises()
    expect(listarPerfisGerenciar).toHaveBeenCalledWith()
  })

  it('mostra estado de carregamento enquanto a API não responde', async () => {
    listarPerfisGerenciar.mockImplementation(() => new Promise(() => {}))
    const w = mountPage()
    await w.vm.$nextTick()
    expect(w.text()).toContain('Carregando perfis')
  })

  it('mostra mensagem quando não há perfis', async () => {
    const w = mountPage()
    await flushPromises()
    expect(w.text()).toContain('Nenhum perfil encontrado')
  })

  it('renderiza tabela com nome e situação', async () => {
    listarPerfisGerenciar.mockResolvedValue([perfilBase({ nome: 'Meu Perfil' })])
    const w = mountPage()
    await flushPromises()
    expect(w.text()).toContain('Meu Perfil')
    expect(w.text()).toContain('Vigente')
  })

  it('ordena por coluna Nome ao clicar no cabeçalho', async () => {
    listarPerfisGerenciar.mockResolvedValue([
      perfilBase({ id: 1, nome: 'Zulu' }),
      perfilBase({ id: 2, nome: 'Alpha' }),
    ])
    const w = mountPage()
    await flushPromises()
    const nomeBtn = w.findAll('.th-sort-btn').find((b) => b.text().includes('Nome do Perfil'))
    await nomeBtn!.trigger('click')
    await w.vm.$nextTick()
    expect(w.find('tbody tr').text()).toContain('Alpha')
    await nomeBtn!.trigger('click')
    await w.vm.$nextTick()
    expect(w.find('tbody tr').text()).toContain('Zulu')
  })

  it('pagina no máximo 10 linhas quando há 11 perfis', async () => {
    const onze = Array.from({ length: 11 }, (_, i) =>
      perfilBase({ id: i + 1, nome: `P${i + 1}` })
    )
    listarPerfisGerenciar.mockResolvedValue(onze)
    const w = mountPage()
    await flushPromises()
    expect(w.findAll('tbody tr')).toHaveLength(10)
  })

  it('abre painel em modo cadastrar ao clicar em Novo perfil', async () => {
    const w = mountPage()
    await flushPromises()
    await w.find('[aria-label="Cadastrar novo perfil"]').trigger('click')
    await w.vm.$nextTick()
    expect(w.find('[data-testid="painel-form"]').exists()).toBe(true)
    expect(w.find('[data-testid="form-modo"]').text()).toBe('cadastrar')
  })

  it('abre painel em modo visualizar', async () => {
    listarPerfisGerenciar.mockResolvedValue([perfilBase()])
    const w = mountPage()
    await flushPromises()
    await w.find('.btn-acao--visualizar').trigger('click')
    await w.vm.$nextTick()
    expect(w.find('[data-testid="form-modo"]').text()).toBe('visualizar')
  })

  it('não exibe botões Editar e Histórico na listagem', async () => {
    listarPerfisGerenciar.mockResolvedValue([perfilBase()])
    const w = mountPage()
    await flushPromises()
    expect(w.find('.btn-acao--editar').exists()).toBe(false)
    expect(w.find('.btn-acao--historico').exists()).toBe(false)
  })

  it('ao salvar com sucesso fecha painel e recarrega lista', async () => {
    listarPerfisGerenciar.mockResolvedValue([perfilBase()])
    const w = mountPage()
    await flushPromises()
    await w.find('[aria-label="Cadastrar novo perfil"]').trigger('click')
    await w.vm.$nextTick()
    listarPerfisGerenciar.mockClear()
    await w.find('[data-testid="emit-sucesso"]').trigger('click')
    await flushPromises()
    expect(w.find('[data-testid="painel-form"]').exists()).toBe(false)
    expect(listarPerfisGerenciar).toHaveBeenCalled()
  })

  it('chama error quando falha ao carregar perfis', async () => {
    listarPerfisGerenciar.mockRejectedValue(new Error('fail'))
    mountPage()
    await flushPromises()
    expect(errorMock).toHaveBeenCalledWith('Não foi possível carregar os perfis.')
  })
})
