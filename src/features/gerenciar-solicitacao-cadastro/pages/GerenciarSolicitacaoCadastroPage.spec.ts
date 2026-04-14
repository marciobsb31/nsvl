import { describe, it, expect, vi, beforeEach } from 'vitest'
import { ref } from 'vue'
import { mount, flushPromises } from '@vue/test-utils'
import GerenciarSolicitacaoCadastroPage from './GerenciarSolicitacaoCadastroPage.vue'
import type { SolicitacaoGerenciarItem } from '@/services/GerenciarSolicitacaoCadastroService'
import type { SolicitacaoCadastroDetalhe } from '@/core/types/solicitacao-cadastro/SolicitacaoInterface'

const mockUser = ref<Record<string, unknown> | null>(null)
const mockContextKey = ref(0)
const mockPerfilAtivo = ref<{
  perfil_usuario_id: number
  perfil_id: number
  nome: string
  ativo?: boolean
} | null>(null)

vi.mock('@/core/composables/useAuth', () => ({
  useAuth: () => ({
    user: mockUser,
    contextKey: mockContextKey,
    perfilAtivo: mockPerfilAtivo,
  }),
}))

const errorMock = vi.fn()
const successMock = vi.fn()
vi.mock('@/core/composables/useNotification', () => ({
  useNotification: () => ({
    error: errorMock,
    success: successMock,
  }),
}))

const listarSolicitacoesGerenciar = vi.fn()
const obterSolicitacaoCadastro = vi.fn()
const apiAprovar = vi.fn()
const apiReprovar = vi.fn()
const apiAtivarPerfilVinculado = vi.fn()
const apiDesativarPerfilVinculado = vi.fn()
const apiAdicionarPerfilVinculado = vi.fn()

vi.mock('@/services/GerenciarSolicitacaoCadastroService', () => ({
  listarSolicitacoesGerenciar: (...args: unknown[]) => listarSolicitacoesGerenciar(...args),
  aprovarSolicitacao: (...args: unknown[]) => apiAprovar(...args),
  reprovarSolicitacao: (...args: unknown[]) => apiReprovar(...args),
  ativarPerfilVinculado: (...args: unknown[]) => apiAtivarPerfilVinculado(...args),
  desativarPerfilVinculado: (...args: unknown[]) => apiDesativarPerfilVinculado(...args),
  adicionarPerfilVinculado: (...args: unknown[]) => apiAdicionarPerfilVinculado(...args),
}))

vi.mock('@/services/SolicitacaoCadastroService', () => ({
  obterSolicitacaoCadastro: (...args: unknown[]) => obterSolicitacaoCadastro(...args),
}))

function itemBase(over: Partial<SolicitacaoGerenciarItem> = {}): SolicitacaoGerenciarItem {
  return {
    id: 1,
    nome: 'Maria',
    status: 'em_analise',
    created_at: '2026-01-01T10:00:00Z',
    cpf: '12345678901',
    esfera_atuacao: 'federal',
    uf: 'GO',
    municipio: 'Goiânia',
    orgao: 'Órgão X',
    ...over,
  }
}

function detalheBase(over: Partial<SolicitacaoCadastroDetalhe> = {}): SolicitacaoCadastroDetalhe {
  return {
    id: 1,
    nome: 'Maria',
    status: 'em_analise',
    created_at: '2026-01-01T10:00:00Z',
    email_institucional: 'm@org.gov.br',
    telefone_institucional: '6133334444',
    esfera_id: 1,
    uf_id: 12,
    municipio_id: 123,
    orgao: 'Órgão X',
    cargo: 'Analista',
    perfis_vinculados: [],
    ...over,
  }
}

const FiltrosStub = {
  name: 'FiltrosGerenciarSolicitacao',
  props: ['carregando'],
  emits: ['pesquisar', 'limpar'],
  template: `
    <div data-testid="filtros">
      <button type="button" data-testid="btn-pesquisar" @click="$emit('pesquisar', { nome: 'FiltroNome' })">Pesquisar</button>
      <button type="button" data-testid="btn-limpar" @click="$emit('limpar')">Limpar</button>
    </div>
  `,
}

const FormularioCadastroStub = {
  name: 'FormularioCadastrarUsuario',
  emits: ['voltar', 'sucesso'],
  template: `
    <div data-testid="form-cadastro">
      <button type="button" data-testid="form-voltar" @click="$emit('voltar')">Voltar</button>
      <button type="button" data-testid="form-sucesso" @click="$emit('sucesso')">Sucesso</button>
    </div>
  `,
}

const PainelDetalharStub = {
  name: 'PainelDetalharSolicitacao',
  props: ['detalhe', 'avaliando'],
  emits: ['voltar', 'aprovar', 'reprovar', 'toggle-perfil', 'adicionar-perfil'],
  template: `
    <div v-if="detalhe" data-testid="painel-detalhar">
      <button type="button" data-testid="det-voltar" @click="$emit('voltar')">Voltar</button>
      <button type="button" data-testid="det-aprovar" @click="$emit('aprovar', { perfilId: 1, vigenciaInicio: '2026-01-01', vigenciaFim: '2026-12-31' })">Aprovar</button>
      <button type="button" data-testid="det-reprovar" @click="$emit('reprovar', { justificativa: 'Motivo' })">Reprovar</button>
      <button type="button" data-testid="det-toggle-atv" @click="$emit('toggle-perfil', { perfilUsuarioId: 99, acao: 'ativar' })">Toggle ativar</button>
      <button type="button" data-testid="det-add-perfil" @click="$emit('adicionar-perfil', { perfilId: 2, vigenciaInicio: '2026-02-01' })">Add perfil</button>
    </div>
  `,
}

const PaginationStub = {
  name: 'PaginationControls',
  props: ['totalItems', 'currentPage', 'pageSize'],
  emits: ['update:currentPage', 'update:pageSize'],
  template: '<div data-testid="pagination" />',
}

function mountPage() {
  return mount(GerenciarSolicitacaoCadastroPage, {
    global: {
      stubs: {
        DefaultLayout: { template: '<div><slot /></div>' },
        Card: { template: '<div><slot /></div>' },
        FiltrosGerenciarSolicitacao: FiltrosStub,
        FormularioCadastrarUsuario: FormularioCadastroStub,
        PainelDetalharSolicitacao: PainelDetalharStub,
        PaginationControls: PaginationStub,
      },
    },
  })
}

describe('GerenciarSolicitacaoCadastroPage (gerenciar-cadastros)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mockUser.value = null
    mockContextKey.value = 0
    mockPerfilAtivo.value = null
    listarSolicitacoesGerenciar.mockResolvedValue([])
    obterSolicitacaoCadastro.mockResolvedValue(detalheBase())
  })

  it('exibe título e botão Cadastrar usuário', async () => {
    const w = mountPage()
    await flushPromises()
    expect(w.text()).toContain('Gerenciar solicitação de cadastros')
    expect(w.find('[aria-label="Cadastrar usuário"]').exists()).toBe(true)
  })

  it('ao montar dispara listagem (limpar e pesquisar)', async () => {
    mountPage()
    await flushPromises()
    expect(listarSolicitacoesGerenciar).toHaveBeenCalledWith({})
  })

  it('mostra mensagem quando não há resultados após pesquisa', async () => {
    const w = mountPage()
    await flushPromises()
    expect(w.text()).toContain('Nenhuma solicitação encontrada')
  })

  it('mostra estado inicial antes da primeira listagem', async () => {
    listarSolicitacoesGerenciar.mockImplementationOnce(() => new Promise(() => {}))
    const w = mountPage()
    expect(w.text()).toContain('Aplique os filtros e clique em Pesquisar')
    await flushPromises()
  })

  it('renderiza tabela com dados retornados pela API', async () => {
    listarSolicitacoesGerenciar.mockResolvedValue([itemBase({ id: 1, nome: 'Ana' })])
    const w = mountPage()
    await flushPromises()
    expect(w.text()).toContain('Ana')
    expect(w.text()).toContain('Federal')
  })

  it('aplica filtros ao emitir pesquisar do componente de filtros', async () => {
    const w = mountPage()
    await flushPromises()
    listarSolicitacoesGerenciar.mockClear()
    await w.find('[data-testid="btn-pesquisar"]').trigger('click')
    await flushPromises()
    expect(listarSolicitacoesGerenciar).toHaveBeenCalledWith(
      expect.objectContaining({ nome: 'FiltroNome' }),
    )
  })

  it('limpa filtros ao emitir limpar', async () => {
    const w = mountPage()
    await flushPromises()
    listarSolicitacoesGerenciar.mockClear()
    await w.find('[data-testid="btn-limpar"]').trigger('click')
    await flushPromises()
    expect(listarSolicitacoesGerenciar).toHaveBeenCalledWith({})
  })

  it('alterna ordenação ao clicar no cabeçalho da coluna Nome', async () => {
    listarSolicitacoesGerenciar.mockResolvedValue([
      itemBase({ id: 1, nome: 'Zebra' }),
      itemBase({ id: 2, nome: 'Alpha' }),
    ])
    const w = mountPage()
    await flushPromises()
    const botoes = w.findAll('.th-sort-btn')
    const nomeBtn = botoes.find((b) => b.text().includes('Nome completo'))
    expect(nomeBtn).toBeDefined()
    await nomeBtn!.trigger('click')
    await w.vm.$nextTick()
    const primeiraLinha = w.find('tbody tr')
    expect(primeiraLinha.text()).toContain('Alpha')
    await nomeBtn!.trigger('click')
    await w.vm.$nextTick()
    const primeiraLinha2 = w.find('tbody tr')
    expect(primeiraLinha2.text()).toContain('Zebra')
  })

  it('pagina resultados (máx. 10 linhas por página com 11 itens)', async () => {
    const onze = Array.from({ length: 11 }, (_, i) =>
      itemBase({ id: i + 1, nome: `User ${i + 1}` }),
    )
    listarSolicitacoesGerenciar.mockResolvedValue(onze)
    const w = mountPage()
    await flushPromises()
    expect(w.findAll('tbody tr')).toHaveLength(10)
  })

  it('abre painel de cadastro ao clicar em Cadastrar usuário', async () => {
    const w = mountPage()
    await flushPromises()
    await w.find('[aria-label="Cadastrar usuário"]').trigger('click')
    await w.vm.$nextTick()
    expect(w.find('[data-testid="form-cadastro"]').exists()).toBe(true)
  })

  it('com perfil Cliente não abre painel ao clicar em Cadastrar usuário', async () => {
    mockPerfilAtivo.value = {
      perfil_usuario_id: 9,
      perfil_id: 9,
      nome: 'Cliente',
      ativo: true,
    }
    const w = mountPage()
    await flushPromises()
    await w.find('[aria-label="Cadastrar usuário"]').trigger('click')
    await w.vm.$nextTick()
    expect(w.find('[data-testid="form-cadastro"]').exists()).toBe(false)
  })

  it('fecha painel cadastro e recarrega lista ao sucesso do formulário', async () => {
    const w = mountPage()
    await flushPromises()
    listarSolicitacoesGerenciar.mockClear()
    await w.find('[aria-label="Cadastrar usuário"]').trigger('click')
    await w.vm.$nextTick()
    await w.find('[data-testid="form-sucesso"]').trigger('click')
    await flushPromises()
    expect(w.find('[data-testid="form-cadastro"]').exists()).toBe(false)
    expect(listarSolicitacoesGerenciar).toHaveBeenCalled()
  })

  it('usa rótulo Detalhar/Analisar para status em_analise e Detalhar para demais', async () => {
    listarSolicitacoesGerenciar.mockResolvedValue([
      itemBase({ id: 1, status: 'em_analise' }),
      itemBase({ id: 2, nome: 'B', status: 'aprovado' }),
    ])
    const w = mountPage()
    await flushPromises()
    const botoes = w.findAll('tbody .br-button')
    expect(botoes.length).toBeGreaterThanOrEqual(2)
    expect(botoes[0]!.text()).toContain('Detalhar/Analisar')
    expect(botoes[1]!.text()).toContain('Detalhar')
  })

  it('carrega detalhe e abre painel ao detalhar solicitação', async () => {
    listarSolicitacoesGerenciar.mockResolvedValue([itemBase()])
    obterSolicitacaoCadastro.mockResolvedValue(detalheBase())
    const w = mountPage()
    await flushPromises()
    await w.find('tbody .br-button').trigger('click')
    await flushPromises()
    expect(obterSolicitacaoCadastro).toHaveBeenCalledWith(1)
    expect(w.find('[data-testid="painel-detalhar"]').exists()).toBe(true)
  })

  it('aprova solicitação e recarrega lista', async () => {
    listarSolicitacoesGerenciar.mockResolvedValue([itemBase()])
    obterSolicitacaoCadastro.mockResolvedValue(detalheBase())
    apiAprovar.mockResolvedValue(undefined)
    const w = mountPage()
    await flushPromises()
    await w.find('tbody .br-button').trigger('click')
    await flushPromises()
    listarSolicitacoesGerenciar.mockClear()
    await w.find('[data-testid="det-aprovar"]').trigger('click')
    await flushPromises()
    expect(apiAprovar).toHaveBeenCalled()
    expect(successMock).toHaveBeenCalledWith('Solicitação aprovada com sucesso.')
    expect(listarSolicitacoesGerenciar).toHaveBeenCalled()
  })

  it('reprova solicitação e recarrega lista', async () => {
    listarSolicitacoesGerenciar.mockResolvedValue([itemBase()])
    obterSolicitacaoCadastro.mockResolvedValue(detalheBase())
    apiReprovar.mockResolvedValue(undefined)
    const w = mountPage()
    await flushPromises()
    await w.find('tbody .br-button').trigger('click')
    await flushPromises()
    listarSolicitacoesGerenciar.mockClear()
    await w.find('[data-testid="det-reprovar"]').trigger('click')
    await flushPromises()
    expect(apiReprovar).toHaveBeenCalledWith(1, 'Motivo')
    expect(successMock).toHaveBeenCalledWith('Solicitação reprovada.')
    expect(listarSolicitacoesGerenciar).toHaveBeenCalled()
  })

  it('ativa perfil vinculado e atualiza detalhe', async () => {
    listarSolicitacoesGerenciar.mockResolvedValue([itemBase()])
    obterSolicitacaoCadastro.mockResolvedValue(detalheBase())
    apiAtivarPerfilVinculado.mockResolvedValue(undefined)
    const w = mountPage()
    await flushPromises()
    await w.find('tbody .br-button').trigger('click')
    await flushPromises()
    obterSolicitacaoCadastro.mockClear()
    await w.find('[data-testid="det-toggle-atv"]').trigger('click')
    await flushPromises()
    expect(apiAtivarPerfilVinculado).toHaveBeenCalledWith(1, 99)
    expect(obterSolicitacaoCadastro).toHaveBeenCalledWith(1)
  })

  it('adiciona perfil vinculado e atualiza detalhe', async () => {
    listarSolicitacoesGerenciar.mockResolvedValue([itemBase()])
    obterSolicitacaoCadastro.mockResolvedValue(detalheBase())
    apiAdicionarPerfilVinculado.mockResolvedValue(undefined)
    const w = mountPage()
    await flushPromises()
    await w.find('tbody .br-button').trigger('click')
    await flushPromises()
    obterSolicitacaoCadastro.mockClear()
    await w.find('[data-testid="det-add-perfil"]').trigger('click')
    await flushPromises()
    expect(apiAdicionarPerfilVinculado).toHaveBeenCalled()
    expect(obterSolicitacaoCadastro).toHaveBeenCalledWith(1)
  })

  it('exibe banner de contexto quando há perfil ativo', async () => {
    mockPerfilAtivo.value = {
      perfil_usuario_id: 1,
      perfil_id: 1,
      nome: 'Gestor',
      ativo: true,
    }
    mockUser.value = {
      id: 1,
      name: 'Admin',
      esfera_atuacao: 'federal',
      uf_lotacao: 'DF',
      perfis_vigentes: [],
    }
    const w = mountPage()
    await flushPromises()
    expect(w.text()).toContain('Contexto ativo:')
    expect(w.text()).toContain('Gestor')
  })

  it('chama error quando listagem falha', async () => {
    listarSolicitacoesGerenciar.mockRejectedValue(new Error('network'))
    mountPage()
    await flushPromises()
    expect(errorMock).toHaveBeenCalledWith(
      'Não foi possível carregar as solicitações. Verifique se o backend está em execução.',
    )
  })
})
