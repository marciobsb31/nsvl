import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import SolicitacaoCadastroIndex from '../pages/SolicitacaoCadastroIndex.vue'
import type {
  SolicitacaoCadastroDetalhe,
  SolicitacaoCadastroItem,
} from '@/core/types/solicitacao-cadastro/SolicitacaoInterface'

const routeQuery = vi.hoisted(() => ({ nome: 'Nome Gov', cpf: '12345678909' }))
const pushMock = vi.hoisted(() => vi.fn())

vi.mock('vue-router', () => ({
  useRoute: () => ({ query: routeQuery }),
  useRouter: () => ({ push: pushMock }),
}))

const initializeSelectMock = vi.hoisted(() => vi.fn())
vi.mock('@/core/composables/useGov', () => ({
  initializeSelect: (...args: unknown[]) => initializeSelectMock(...args),
}))

const successMock = vi.hoisted(() => vi.fn())
const errorMock = vi.hoisted(() => vi.fn())
vi.mock('@/core/composables/useNotification', () => ({
  useNotification: () => ({
    success: successMock,
    error: errorMock,
  }),
}))

const enviarSolicitacaoCadastroMock = vi.hoisted(() => vi.fn())
const obterSolicitacaoCadastroMock = vi.hoisted(() => vi.fn())
const atualizarSolicitacaoCadastroMock = vi.hoisted(() => vi.fn())
const excluirSolicitacaoCadastroMock = vi.hoisted(() => vi.fn())

vi.mock('@/services/SolicitacaoCadastroService', () => ({
  enviarSolicitacaoCadastro: (...args: unknown[]) => enviarSolicitacaoCadastroMock(...args),
  obterSolicitacaoCadastro: (...args: unknown[]) => obterSolicitacaoCadastroMock(...args),
  atualizarSolicitacaoCadastro: (...args: unknown[]) => atualizarSolicitacaoCadastroMock(...args),
  excluirSolicitacaoCadastro: (...args: unknown[]) => excluirSolicitacaoCadastroMock(...args),
}))

function detalheBase(over: Partial<SolicitacaoCadastroDetalhe> = {}): SolicitacaoCadastroDetalhe {
  return {
    id: 1,
    nome: 'Maria',
    email_institucional: 'm@org.gov.br',
    telefone_institucional: '61999998888',
    telefone_pessoal: null,
    esfera_id: 1,
    uf_id: 12,
    municipio_id: 1,
    orgao: 'Órgão X',
    cargo: 'Analista',
    status: 'em_analise',
    created_at: '2026-01-01T10:00:00Z',
    ...over,
  }
}

function itemBase(over: Partial<SolicitacaoCadastroItem> = {}): SolicitacaoCadastroItem {
  return {
    id: 1,
    nome: 'Maria',
    status: 'em_analise',
    created_at: '2026-01-01T10:00:00Z',
    ...over,
  }
}

const FormStub = {
  name: 'Form',
  props: ['validationSchema', 'initialValues', 'ariaBusy'],
  emits: ['submit'],
  template: '<div data-testid="form"><slot :values="initialValues" :meta="{}" /></div>',
}

function mountPage() {
  return mount(SolicitacaoCadastroIndex, {
    global: {
      stubs: {
        PublicLayout: { template: '<div><slot /></div>' },
        Breadcrumb: { template: '<div />' },
        Card: { template: '<div><slot /></div>' },
        TermoUsoPrivacidade: { template: '<div />' },
        Modal: { template: '<div><slot /></div>' },
        FormularioDadosSolicitante: { template: '<div />' },
        FormularioInformacaoSolicitante: { template: '<div />' },
        Form: FormStub,
      },
    },
  })
}

describe('SolicitacaoCadastroIndex (/solicitacao-cadastro)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    routeQuery.nome = 'Nome Gov'
    routeQuery.cpf = '12345678909'
    enviarSolicitacaoCadastroMock.mockResolvedValue(undefined)
    obterSolicitacaoCadastroMock.mockResolvedValue(detalheBase())
    atualizarSolicitacaoCadastroMock.mockResolvedValue(undefined)
    excluirSolicitacaoCadastroMock.mockResolvedValue(undefined)
  })

  it('chama initializeSelect ao montar', async () => {
    mountPage()
    await flushPromises()
    expect(initializeSelectMock).toHaveBeenCalled()
  })

  it('formatadores e labels cobrem ramos', async () => {
    const w = mountPage()
    const vm = w.vm as unknown as {
      formatarCpf: (cpf: string) => string
      formatarTelefoneParaCampo: (t: string | null | undefined) => string
      formatarTelefoneExibicao: (t: string | null | undefined) => string
      formatarData: (d: string | undefined) => string
      statusLabel: (s: string) => string
      statusClass: (s: string) => string
      camposObrigatoriosPreenchidos: (v: Record<string, unknown>) => boolean
    }

    expect(vm.formatarCpf('123')).toBe('123')
    expect(vm.formatarCpf('12345678909')).toBe('123.456.789-09')

    expect(vm.formatarTelefoneParaCampo(null)).toBe('')
    expect(vm.formatarTelefoneParaCampo('61999998888')).toBe('(61) 99999-8888')
    expect(vm.formatarTelefoneParaCampo('6133334444')).toBe('(61) 3333-4444')
    expect(vm.formatarTelefoneParaCampo('1')).toBe('')

    expect(vm.formatarTelefoneExibicao('61999998888')).toBe('(61) 99999-8888')
    expect(vm.formatarTelefoneExibicao('x')).toBe('x')

    expect(vm.formatarData(undefined)).toBe('-')
    expect(vm.formatarData('2026-01-01T10:00:00Z')).toContain('01')
    expect(vm.formatarData('invalid-date')).toBe('Invalid Date')

    expect(vm.statusLabel('em_analise')).toBe('Em análise')
    expect(vm.statusLabel('outro')).toBe('outro')

    expect(vm.statusClass('aprovado')).toBe('success')
    expect(vm.statusClass('outro')).toBe('')

    expect(
      vm.camposObrigatoriosPreenchidos({
        nome: 'n',
        CPF: 'c',
        emailInstitucional: 'e',
        telefoneInstitucional: 't',
        esferaAtuacao: 'f',
        uf: 'go',
        municipio: 'm',
        orgao: 'o',
        cargo: 'c',
      }),
    ).toBe(true)
    expect(vm.camposObrigatoriosPreenchidos({ nome: 'n' })).toBe(false)
  })

  it('visualizar/editar cobrem sucesso e erro', async () => {
    const w = mountPage()
    const vm = w.vm as unknown as {
      visualizar: (id: number) => Promise<void>
      editar: (id: number) => Promise<void>
      modalVisualizar: number | null
      detalheVisualizar: SolicitacaoCadastroDetalhe | null
      modalEditar: number | null
      detalheEditar: Record<string, unknown> | null
    }

    obterSolicitacaoCadastroMock.mockResolvedValueOnce(
      detalheBase({ telefone_pessoal: '61988887777' }),
    )
    await vm.visualizar(1)
    expect(vm.modalVisualizar).toBe(1)
    expect(vm.detalheVisualizar?.id).toBe(1)
    expect(w.text()).toContain('Telefone pessoal')

    obterSolicitacaoCadastroMock.mockRejectedValueOnce(new Error('fail'))
    await vm.visualizar(2)
    expect(errorMock).toHaveBeenCalledWith(
      'Não foi possível carregar os detalhes da solicitação. Tente novamente.',
    )

    obterSolicitacaoCadastroMock.mockResolvedValueOnce(
      detalheBase({ telefone_pessoal: '61988887777' }),
    )
    await vm.editar(3)
    expect(vm.modalEditar).toBe(3)
    expect(vm.detalheEditar).toBeTruthy()

    obterSolicitacaoCadastroMock.mockRejectedValueOnce(new Error('fail'))
    await vm.editar(4)
    expect(errorMock).toHaveBeenCalledWith(
      'Não foi possível carregar a solicitação para edição. Tente novamente.',
    )
  })

  it('formatarData cobre catch quando Date lança exceção', async () => {
    const w = mountPage()
    const vm = w.vm as unknown as { formatarData: (d: string | undefined) => string }

    const originalDate = globalThis.Date
    globalThis.Date = class extends (originalDate as DateConstructor) {
      constructor(...args: any[]) {
        if (args[0] === 'explode') {
          throw new Error('boom')
        }
        super(args[0])
      }
    } as unknown as DateConstructor

    expect(vm.formatarData('explode')).toBe('explode')

    globalThis.Date = originalDate
  })

  it('fechar modais e excluir cobrem ramos', async () => {
    const w = mountPage()
    const vm = w.vm as unknown as {
      fecharModalVisualizar: () => void
      fecharModalEditar: () => void
      confirmarExcluir: (s: SolicitacaoCadastroItem) => void
      fecharModalExcluir: () => void
      executarExcluir: () => Promise<void>
      modalVisualizar: number | null
      detalheVisualizar: SolicitacaoCadastroDetalhe | null
      modalEditar: number | null
      detalheEditar: Record<string, unknown> | null
      modalExcluir: boolean
      solicitacaoExcluir: SolicitacaoCadastroItem | null
      excluindo: boolean
    }

    // early return
    await vm.executarExcluir()

    vm.confirmarExcluir(itemBase())
    expect(vm.modalExcluir).toBe(true)

    excluirSolicitacaoCadastroMock.mockRejectedValueOnce({
      response: { data: { message: 'msg api' } },
    })
    await vm.executarExcluir()
    expect(errorMock).toHaveBeenCalledWith('msg api')
    expect(vm.excluindo).toBe(false)

    vm.confirmarExcluir(itemBase())
    excluirSolicitacaoCadastroMock.mockRejectedValueOnce(new Error('x'))
    await vm.executarExcluir()
    expect(errorMock).toHaveBeenCalledWith(
      'Não foi possível excluir a solicitação. Tente novamente.',
    )

    vm.confirmarExcluir(itemBase())
    await vm.executarExcluir()
    expect(successMock).toHaveBeenCalledWith('Solicitação excluída com sucesso.')
    expect(vm.modalExcluir).toBe(false)

    vm.fecharModalVisualizar()
    expect(vm.modalVisualizar).toBe(null)
    expect(vm.detalheVisualizar).toBe(null)

    vm.fecharModalEditar()
    expect(vm.modalEditar).toBe(null)
    expect(vm.detalheEditar).toBe(null)

    vm.fecharModalExcluir()
    expect(vm.modalExcluir).toBe(false)
    expect(vm.solicitacaoExcluir).toBe(null)
  })

  it('onSubmitEditar cobre early-return, payload e erros', async () => {
    const w = mountPage()
    const vm = w.vm as unknown as {
      onSubmitEditar: (v: Record<string, unknown>) => Promise<void>
      modalEditar: number | null
    }

    // early return
    vm.modalEditar = null
    await vm.onSubmitEditar({})

    vm.modalEditar = 10

    await vm.onSubmitEditar({
      nome: 'Nome',
      CPF: ' ',
      emailInstitucional: 'a@b.com',
      telefoneInstitucional: '6133334444',
      telefonePessoal: '11',
      esferaAtuacao: 'federal',
      uf: 'go',
      municipio: 'm',
      orgao: 'o',
      cargo: 'c',
    })
    expect(atualizarSolicitacaoCadastroMock).toHaveBeenCalledWith(
      10,
      expect.objectContaining({ telefonePessoal: null }),
    )

    vm.modalEditar = 10
    await vm.onSubmitEditar({
      nome: 'Nome',
      CPF: '123',
      emailInstitucional: 'a@b.com',
      telefoneInstitucional: '6133334444',
      telefonePessoal: '61988887777',
      esferaAtuacao: 'federal',
      uf: 'go',
      municipio: 'm',
      orgao: 'o',
      cargo: 'c',
    })
    expect(atualizarSolicitacaoCadastroMock).toHaveBeenCalledWith(
      10,
      expect.objectContaining({ CPF: '123', telefonePessoal: '61988887777' }),
    )

    vm.modalEditar = 10
    atualizarSolicitacaoCadastroMock.mockRejectedValueOnce({
      response: { data: { message: 'erro update' } },
    })
    await vm.onSubmitEditar({
      nome: 'Nome',
      CPF: '',
      emailInstitucional: 'a@b.com',
      telefoneInstitucional: '6133334444',
      telefonePessoal: '',
      esferaAtuacao: 'federal',
      uf: 'go',
      municipio: 'm',
      orgao: 'o',
      cargo: 'c',
    })
    expect(errorMock).toHaveBeenCalledWith('erro update')

    vm.modalEditar = 10
    atualizarSolicitacaoCadastroMock.mockRejectedValueOnce(new Error('x'))
    await vm.onSubmitEditar({
      nome: 'Nome',
      CPF: '',
      emailInstitucional: 'a@b.com',
      telefoneInstitucional: '6133334444',
      telefonePessoal: '',
      esferaAtuacao: 'federal',
      uf: 'go',
      municipio: 'm',
      orgao: 'o',
      cargo: 'c',
    })
    expect(errorMock).toHaveBeenCalledWith(
      'Não foi possível atualizar a solicitação. Verifique os dados e tente novamente.',
    )
  })

  it('onSubmit cobre sucesso, cpf ramos e mensagens de erro', async () => {
    vi.useFakeTimers()
    const w = mountPage()
    const vm = w.vm as unknown as { onSubmit: (v: Record<string, unknown>) => Promise<void> }

    await vm.onSubmit({
      nome: '  Nome  ',
      CPF: '123.456.789-09',
      emailInstitucional: '  a@b.com  ',
      telefoneInstitucional: '(61) 3333-4444',
      telefonePessoal: '61988887777',
      esferaAtuacao: ' federal ',
      uf: 'go',
      municipio: ' m ',
      orgao: ' o ',
      cargo: ' c ',
    })

    expect(enviarSolicitacaoCadastroMock).toHaveBeenCalledWith(
      expect.objectContaining({
        CPF: '12345678909',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '61988887777',
        uf: 'GO',
      }),
    )

    vi.advanceTimersByTime(4000)
    expect(pushMock).toHaveBeenCalledWith({ name: 'login' })

    // cpf com asterisco não envia
    await vm.onSubmit({
      nome: 'Nome',
      CPF: '***',
      emailInstitucional: 'a@b.com',
      telefoneInstitucional: '6133334444',
      telefonePessoal: '11',
      esferaAtuacao: 'federal',
      uf: 'go',
      municipio: 'm',
      orgao: 'o',
      cargo: 'c',
    })
    expect(enviarSolicitacaoCadastroMock).toHaveBeenCalledWith(
      expect.not.objectContaining({ CPF: expect.anything() }),
    )

    enviarSolicitacaoCadastroMock.mockRejectedValueOnce({
      response: { data: { message: 'msg api' } },
    })
    await vm.onSubmit({})
    expect(errorMock).toHaveBeenCalledWith('msg api')

    enviarSolicitacaoCadastroMock.mockRejectedValueOnce({ response: { status: 422 } })
    await vm.onSubmit({})
    expect(errorMock).toHaveBeenCalledWith(
      'Alguns campos possuem dados inválidos. Revise o formulário e tente novamente.',
    )

    enviarSolicitacaoCadastroMock.mockRejectedValueOnce({ response: { status: 429 } })
    await vm.onSubmit({})
    expect(errorMock).toHaveBeenCalledWith(
      'Muitas tentativas em pouco tempo. Aguarde alguns instantes e tente novamente.',
    )

    enviarSolicitacaoCadastroMock.mockRejectedValueOnce({ response: { status: 500 } })
    await vm.onSubmit({})
    expect(errorMock).toHaveBeenCalledWith(
      'O servidor encontrou um erro inesperado. Tente novamente em alguns minutos.',
    )

    enviarSolicitacaoCadastroMock.mockRejectedValueOnce(new Error('x'))
    await vm.onSubmit({})
    expect(errorMock).toHaveBeenCalledWith(
      'Não foi possível conectar ao servidor. Verifique sua conexão e tente novamente.',
    )

    vi.useRealTimers()
  })

  it('onCancel redireciona para home', async () => {
    const w = mountPage()
    await w.find('button[type="button"]').trigger('click')
    expect(pushMock).toHaveBeenCalledWith({ name: 'home' })
  })
})
