/**
 * Opções para os filtros da tela Gerenciar Solicitações de Cadastro.
 * Municípios: lista parcial; em produção pode vir de API (ex.: IBGE).
 */
export const OPCOES_ESFERA = [
  { value: 'federal', label: 'Federal' },
  { value: 'estadual', label: 'Estadual' },
  { value: 'municipal', label: 'Municipal' },
]

export const OPCOES_UF = [
  { value: 'AC', label: 'AC - Acre' },
  { value: 'AL', label: 'AL - Alagoas' },
  { value: 'AP', label: 'AP - Amapá' },
  { value: 'AM', label: 'AM - Amazonas' },
  { value: 'BA', label: 'BA - Bahia' },
  { value: 'CE', label: 'CE - Ceará' },
  { value: 'DF', label: 'DF - Distrito Federal' },
  { value: 'ES', label: 'ES - Espírito Santo' },
  { value: 'GO', label: 'GO - Goiás' },
  { value: 'MA', label: 'MA - Maranhão' },
  { value: 'MT', label: 'MT - Mato Grosso' },
  { value: 'MS', label: 'MS - Mato Grosso do Sul' },
  { value: 'MG', label: 'MG - Minas Gerais' },
  { value: 'PA', label: 'PA - Pará' },
  { value: 'PB', label: 'PB - Paraíba' },
  { value: 'PR', label: 'PR - Paraná' },
  { value: 'PE', label: 'PE - Pernambuco' },
  { value: 'PI', label: 'PI - Piauí' },
  { value: 'RJ', label: 'RJ - Rio de Janeiro' },
  { value: 'RN', label: 'RN - Rio Grande do Norte' },
  { value: 'RS', label: 'RS - Rio Grande do Sul' },
  { value: 'RO', label: 'RO - Rondônia' },
  { value: 'RR', label: 'RR - Roraima' },
  { value: 'SC', label: 'SC - Santa Catarina' },
  { value: 'SP', label: 'SP - São Paulo' },
  { value: 'SE', label: 'SE - Sergipe' },
  { value: 'TO', label: 'TO - Tocantins' },
]

export const OPCOES_STATUS = [
  { value: 'em_analise', label: 'Em análise' },
  { value: 'aprovado', label: 'Aprovada' },
  { value: 'reprovado', label: 'Reprovada' },
]

/** Municípios (amostra para autocomplete; em produção usar API IBGE) */
export const OPCOES_MUNICIPIOS = [
  { value: 'Alexânia', label: 'Alexânia' },
  { value: 'Anápolis', label: 'Anápolis' },
  { value: 'Aparecida de Goiânia', label: 'Aparecida de Goiânia' },
  { value: 'Brasília', label: 'Brasília' },
  { value: 'Goiânia', label: 'Goiânia' },
  { value: 'Luziânia', label: 'Luziânia' },
  { value: 'Rio Verde', label: 'Rio Verde' },
  { value: 'São Paulo', label: 'São Paulo' },
  { value: 'Campinas', label: 'Campinas' },
  { value: 'Guarulhos', label: 'Guarulhos' },
  { value: 'Belo Horizonte', label: 'Belo Horizonte' },
  { value: 'Curitiba', label: 'Curitiba' },
  { value: 'Porto Alegre', label: 'Porto Alegre' },
  { value: 'Salvador', label: 'Salvador' },
  { value: 'Fortaleza', label: 'Fortaleza' },
  { value: 'Recife', label: 'Recife' },
  { value: 'Manaus', label: 'Manaus' },
  { value: 'Belém', label: 'Belém' },
  { value: 'Florianópolis', label: 'Florianópolis' },
]
