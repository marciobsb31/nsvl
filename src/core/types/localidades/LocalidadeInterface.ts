export interface uf {
  id: number
  nome: string
  sigla: string
}

export interface LocalidadeOption {
  value: string
  label: string
}

export interface UfOption extends LocalidadeOption {
  id?: number
}

export interface LocalidadesCompleto {
  ufs: UfOption[]
  municipios_por_uf: Record<string, LocalidadeOption[]>
}

export interface Municipio {
  id: number
  nome: string
  codigo_ibge?: string
}
