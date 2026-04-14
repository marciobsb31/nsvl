export interface SolicitacaoCadastroItem {
  id: number
  nome: string
  status: string
  created_at: string
}

export interface PerfilVinculado {
  id: number
  perfil_usuario_id?: number
  ativo?: boolean
  perfil: string
  vigencia_inicio: string
  vigencia_fim: string
  vigente: boolean
  esfera: string
  uf: string
  municipio: string
  orgao: string
  cargo: string
}

export interface HistoricoReprovacaoItem {
  data: string | null
  motivo: string
  avaliador: string | null
}

export interface SolicitacaoCadastroDetalhe extends SolicitacaoCadastroItem {
  cpf?: string
  usuario_id?: number
  email_institucional: string
  telefone_institucional: string
  telefone_pessoal?: string | null
  esfera_id: number
  uf_id: string | number
  municipio_id: string | number
  orgao: string
  cargo: string
  perfil_id_solicitado?: number | null
  vigencia_inicio_solicitada?: string | null
  vigencia_fim_solicitada?: string | null
  updated_at?: string
  perfis_vinculados?: PerfilVinculado[]
  pode_avaliar?: boolean
  historico_reprovacoes?: HistoricoReprovacaoItem[]
}

export interface SolicitacaoCadastroPayload {
  nome: string
  cpf?: string
  email_institucional: string
  telefone_institucional: string
  telefone_pessoal?: string | null
  esfera_id: number
  uf_id: string | number
  municipio_id: string | number
  orgao: string
  cargo: string
  aceiteTermo?: boolean
  perfilId?: number
  vigenciaInicio?: string
  vigenciaFim?: string
}

export interface SolicitacaoCadastroUpdatePayload extends Omit<
  SolicitacaoCadastroPayload,
  'cpf' | 'aceiteTermo'
> {
  cpf?: string
}

export interface SolicitacaoCadastroResponse {
  message: string
  solicitacao_id: number
}
