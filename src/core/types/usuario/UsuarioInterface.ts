export interface Contexto {
  esfera: string
  localidade: string
  perfil: string
}

export interface perfis {
  id: number
  nome: string
  descricao: string
  ativo: boolean
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
  permissions: string[]
}
