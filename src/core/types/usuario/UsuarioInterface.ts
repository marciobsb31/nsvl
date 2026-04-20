export interface Contexto {
  perfil_usuario_id?: number
  esfera: {
    id: number
    nome: string
  }
  localidade: string
  perfil: string
  uf_id?: number
  municipio_id?: number
}

export interface perfis {
  id: number
  nome: string
  descricao: string
  localidade?: string
  esfera?: string
  uf?: string
  municipio?: string
  orgao?: string
  ativo: boolean
  vigente?: boolean
  data_inicio_vigencia: string
  data_fim_vigencia: string | null
}

export interface AuthUser {
  id: number
  name: string
  email?: string
  sub?: string
  contexto: Contexto
  perfis: perfis[]
  permissoes: string[]
}
