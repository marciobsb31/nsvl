import { describe, it, expect, vi, beforeEach } from 'vitest'
import * as yup from 'yup'
import {
  SolicitacaoCadastroSchema,
  SolicitacaoCadastroSchemaGovBr,
  DadosSolicitanteSchema,
  SolicitacaoCadastroSchemaEdicao,
  InformacaoSolicitanteSchema,
  SolicitacaoCadastroSchemaGerenciar,
} from '../validators/solicitacaoCadastro.schema'

const verificarCpfDisponivelMock = vi.hoisted(() => vi.fn())
vi.mock('@/services/SolicitacaoCadastroService', () => ({
  verificarCpfDisponivel: (...args: unknown[]) => verificarCpfDisponivelMock(...args),
}))

describe('solicitacaoCadastro.schema', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    verificarCpfDisponivelMock.mockResolvedValue({ disponivel: true, mensagem: '' })
  })

  function basePayload(over: Record<string, unknown> = {}) {
    return {
      nome: 'Maria',
      CPF: '12345678909',
      emailInstitucional: 'm@org.gov.br',
      telefoneInstitucional: '6133334444',
      telefonePessoal: '',
      esferaAtuacao: 'federal',
      uf: 'GO',
      municipio: 'Goiânia',
      orgao: 'Org',
      cargo: 'Analista',
      ...over,
    }
  }

  function gerarCpfComDv0PorResto10(alvo: 'dv1' | 'dv2'): string {
    function calcDv(nums: string, pesoInicial: number): number {
      let soma = 0
      for (let i = 0; i < nums.length; i++) {
        soma += parseInt(nums[i]!, 10) * (pesoInicial - i)
      }
      let resto = (soma * 10) % 11
      if (resto === 10) resto = 0
      return resto
    }

    for (let i = 100000000; i < 100001000; i++) {
      const base = String(i).padStart(9, '0')
      if (/^(\d)\1{8}$/.test(base)) continue
      const dv1 = calcDv(base, 10)
      const dv2 = calcDv(base + String(dv1), 11)
      const cpf = base + String(dv1) + String(dv2)
      if (alvo === 'dv1' && dv1 === 0) return cpf
      if (alvo === 'dv2' && dv2 === 0) return cpf
    }
    return '12345678909'
  }

  it('CPF inválido (dígitos repetidos) falha', async () => {
    await expect(
      SolicitacaoCadastroSchema.validate({
        nome: 'Maria',
        CPF: '11111111111',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '',
        esferaAtuacao: 'federal',
        uf: 'GO',
        municipio: 'Goiânia',
        orgao: 'Org',
        cargo: 'Analista',
      }),
    ).rejects.toBeInstanceOf(yup.ValidationError)
  })

  it('CPF vazio falha (required)', async () => {
    await expect(
      SolicitacaoCadastroSchema.validate(basePayload({ CPF: '' })),
    ).rejects.toBeInstanceOf(yup.ValidationError)
  })

  it('CPF com tamanho != 11 falha', async () => {
    await expect(
      SolicitacaoCadastroSchema.validate(basePayload({ CPF: '1234567890' })),
    ).rejects.toBeInstanceOf(yup.ValidationError)
  })

  it('CPF falha no 1º dígito verificador', async () => {
    await expect(
      SolicitacaoCadastroSchema.validate(basePayload({ CPF: '12345678900' })),
    ).rejects.toBeInstanceOf(yup.ValidationError)
  })

  it('CPF falha no 2º dígito verificador', async () => {
    await expect(
      SolicitacaoCadastroSchema.validate(basePayload({ CPF: '12345678908' })),
    ).rejects.toBeInstanceOf(yup.ValidationError)
  })

  it('CPF válido cobre ramos de resto===10 (dv1 e dv2) no algoritmo', async () => {
    const cpfDv1Zero = gerarCpfComDv0PorResto10('dv1')
    const cpfDv2Zero = gerarCpfComDv0PorResto10('dv2')

    await expect(
      SolicitacaoCadastroSchema.validate(basePayload({ CPF: cpfDv1Zero })),
    ).resolves.toBeTruthy()
    await expect(
      SolicitacaoCadastroSchema.validate(basePayload({ CPF: cpfDv2Zero })),
    ).resolves.toBeTruthy()
  })

  it('cpf-disponivel: quando CPF é inválido, não chama API', async () => {
    await expect(
      SolicitacaoCadastroSchema.validate({
        nome: 'Maria',
        CPF: '123',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '',
        esferaAtuacao: 'federal',
        uf: 'GO',
        municipio: 'Goiânia',
        orgao: 'Org',
        cargo: 'Analista',
      }),
    ).rejects.toBeInstanceOf(yup.ValidationError)
    expect(verificarCpfDisponivelMock).not.toHaveBeenCalled()
  })

  it('cpf-disponivel: quando API retorna indisponível com mensagem mapeada, retorna ValidationError com texto amigável', async () => {
    verificarCpfDisponivelMock.mockResolvedValueOnce({
      disponivel: false,
      mensagem: 'Este CPF já possui cadastro ativo no sistema.',
    })

    await expect(
      SolicitacaoCadastroSchema.validate({
        nome: 'Maria',
        CPF: '12345678909',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '',
        esferaAtuacao: 'federal',
        uf: 'GO',
        municipio: 'Goiânia',
        orgao: 'Org',
        cargo: 'Analista',
      }),
    ).rejects.toThrow(
      'Este CPF já está vinculado a um cadastro ativo. Faça login com GOV.BR para acessar o sistema.',
    )
  })

  it('cpf-disponivel: quando API retorna indisponível com mensagem não mapeada, usa mensagem original', async () => {
    verificarCpfDisponivelMock.mockResolvedValueOnce({
      disponivel: false,
      mensagem: 'Mensagem não mapeada',
    })

    await expect(
      SolicitacaoCadastroSchema.validate({
        nome: 'Maria',
        CPF: '12345678909',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '',
        esferaAtuacao: 'federal',
        uf: 'GO',
        municipio: 'Goiânia',
        orgao: 'Org',
        cargo: 'Analista',
      }),
    ).rejects.toThrow('Mensagem não mapeada')
  })

  it('cpf-disponivel: se API falhar com ValidationError, repassa erro', async () => {
    verificarCpfDisponivelMock.mockRejectedValueOnce(new yup.ValidationError('Erro yup'))

    await expect(
      SolicitacaoCadastroSchema.validate({
        nome: 'Maria',
        CPF: '12345678909',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '',
        esferaAtuacao: 'federal',
        uf: 'GO',
        municipio: 'Goiânia',
        orgao: 'Org',
        cargo: 'Analista',
      }),
    ).rejects.toThrow('Erro yup')
  })

  it('cpf-disponivel: se API falhar com erro genérico, ignora e considera válido', async () => {
    verificarCpfDisponivelMock.mockRejectedValueOnce(new Error('Falha'))

    await expect(
      SolicitacaoCadastroSchema.validate({
        nome: 'Maria',
        CPF: '12345678909',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '',
        esferaAtuacao: 'federal',
        uf: 'GO',
        municipio: 'Goiânia',
        orgao: 'Org',
        cargo: 'Analista',
      }),
    ).resolves.toBeTruthy()
  })

  it('telefone pessoal inválido (len < 10) falha nos schemas que validam telefonePessoal', async () => {
    await expect(
      SolicitacaoCadastroSchema.validate({
        nome: 'Maria',
        CPF: '12345678909',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '12',
        esferaAtuacao: 'federal',
        uf: 'GO',
        municipio: 'Goiânia',
        orgao: 'Org',
        cargo: 'Analista',
      }),
    ).rejects.toBeInstanceOf(yup.ValidationError)

    await expect(
      SolicitacaoCadastroSchemaGovBr.validate({
        nome: 'Maria',
        CPF: '12345678909',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '12',
        esferaAtuacao: 'federal',
        uf: 'GO',
        municipio: 'Goiânia',
        orgao: 'Org',
        cargo: 'Analista',
      }),
    ).rejects.toBeInstanceOf(yup.ValidationError)

    await expect(
      SolicitacaoCadastroSchemaEdicao.validate({
        nome: 'Maria',
        CPF: '',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '12',
        esferaAtuacao: 'federal',
        uf: 'GO',
        municipio: 'Goiânia',
        orgao: 'Org',
        cargo: 'Analista',
      }),
    ).rejects.toBeInstanceOf(yup.ValidationError)
  })

  it('telefone pessoal vazio/whitespace é aceito', async () => {
    await expect(
      SolicitacaoCadastroSchema.validate({
        nome: 'Maria',
        CPF: '12345678909',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '   ',
        esferaAtuacao: 'federal',
        uf: 'GO',
        municipio: 'Goiânia',
        orgao: 'Org',
        cargo: 'Analista',
      }),
    ).resolves.toBeTruthy()
  })

  it('DadosSolicitanteSchema cobre CPF e telefone institucionais inválidos', async () => {
    await expect(
      DadosSolicitanteSchema.validate({
        nome: 'Maria',
        CPF: '11111111111',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '',
      }),
    ).rejects.toBeInstanceOf(yup.ValidationError)

    await expect(
      DadosSolicitanteSchema.validate({
        nome: 'Maria',
        CPF: '12345678909',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '123',
        telefonePessoal: '',
      }),
    ).rejects.toBeInstanceOf(yup.ValidationError)

    await expect(
      DadosSolicitanteSchema.validate({
        nome: 'Maria',
        CPF: '12345678909',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '123',
      }),
    ).rejects.toBeInstanceOf(yup.ValidationError)
  })

  it('InformacaoSolicitanteSchema cobre campos obrigatórios', async () => {
    await expect(
      InformacaoSolicitanteSchema.validate({
        esferaAtuacao: '',
        uf: '',
        municipio: '',
        orgao: '',
        cargo: '',
      }),
    ).rejects.toBeInstanceOf(yup.ValidationError)
  })

  it('schema GOV.BR: valida CPF e campos obrigatórios', async () => {
    await expect(
      SolicitacaoCadastroSchemaGovBr.validate({
        nome: 'Maria',
        CPF: '12345678909',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '',
        esferaAtuacao: 'federal',
        uf: 'GO',
        municipio: 'Goiânia',
        orgao: 'Org',
        cargo: 'Analista',
      }),
    ).resolves.toBeTruthy()
  })

  it('schema edição: CPF opcional, mas se informado inválido falha', async () => {
    await expect(
      SolicitacaoCadastroSchemaEdicao.validate({
        nome: 'Maria',
        CPF: '',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '',
        esferaAtuacao: 'federal',
        uf: 'GO',
        municipio: 'Goiânia',
        orgao: 'Org',
        cargo: 'Analista',
      }),
    ).resolves.toBeTruthy()

    await expect(
      SolicitacaoCadastroSchemaEdicao.validate({
        nome: 'Maria',
        CPF: '11111111111',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '',
        esferaAtuacao: 'federal',
        uf: 'GO',
        municipio: 'Goiânia',
        orgao: 'Org',
        cargo: 'Analista',
      }),
    ).rejects.toBeInstanceOf(yup.ValidationError)
  })

  it('schema gerenciar: valida perfil e vigência fim >= início', async () => {
    await expect(
      SolicitacaoCadastroSchemaGerenciar.validate({
        nome: 'Maria',
        CPF: '12345678909',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '',
        esferaAtuacao: 'federal',
        uf: 'GO',
        municipio: 'Goiânia',
        orgao: 'Org',
        cargo: 'Analista',
        perfil: 1,
        vigenciaInicio: '2026-01-01',
        vigenciaFim: '2026-01-02',
      }),
    ).resolves.toBeTruthy()

    await expect(
      SolicitacaoCadastroSchemaGerenciar.validate({
        nome: 'Maria',
        CPF: '12345678909',
        emailInstitucional: 'm@org.gov.br',
        telefoneInstitucional: '6133334444',
        telefonePessoal: '',
        esferaAtuacao: 'federal',
        uf: 'GO',
        municipio: 'Goiânia',
        orgao: 'Org',
        cargo: 'Analista',
        perfil: null,
        vigenciaInicio: '2026-01-01',
        vigenciaFim: '2025-12-31',
      }),
    ).rejects.toBeInstanceOf(yup.ValidationError)
  })

  it('schema gerenciar: vigência fim vazio é aceita; e sem vigência início não valida comparação', async () => {
    await expect(
      SolicitacaoCadastroSchemaGerenciar.validate({
        ...basePayload({ telefonePessoal: '' }),
        perfil: 1,
        vigenciaInicio: '2026-01-01',
        vigenciaFim: '',
      }),
    ).resolves.toBeTruthy()

    await expect(
      SolicitacaoCadastroSchemaGerenciar.validate({
        ...basePayload({ telefonePessoal: '' }),
        perfil: 1,
        vigenciaInicio: '',
        vigenciaFim: '2025-01-01',
      }),
    ).rejects.toBeInstanceOf(yup.ValidationError)
  })
})
