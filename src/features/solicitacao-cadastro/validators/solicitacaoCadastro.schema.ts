import * as yup from 'yup'

/**
 * Valida CPF conforme algoritmo oficial (dígitos verificadores)
 */
function validarCPF(cpf: string | undefined): boolean {
  if (!cpf) return false
  const numeros = cpf.replace(/\D/g, '')
  if (numeros.length !== 11) return false

  // Rejeita CPFs com todos os dígitos iguais
  if (/^(\d)\1{10}$/.test(numeros)) return false

  // Valida primeiro dígito verificador
  let soma = 0
  for (let i = 0; i < 9; i++) {
    soma += parseInt(numeros[i] ?? '0', 10) * (10 - i)
  }
  let resto = (soma * 10) % 11
  if (resto === 10) resto = 0
  if (resto !== parseInt(numeros[9] ?? '0', 10)) return false

  // Valida segundo dígito verificador
  soma = 0
  for (let i = 0; i < 10; i++) {
    soma += parseInt(numeros[i] ?? '0', 10) * (11 - i)
  }
  resto = (soma * 10) % 11
  if (resto === 10) resto = 0
  if (resto !== parseInt(numeros[10] ?? '0', 10)) return false

  return true
}

/**
 * Regex para nome: apenas letras (incluindo acentuadas) e espaços
 */
const regexSomenteLetras = /^[a-zA-ZáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\s]+$/

export const SolicitacaoCadastroSchema = yup.object({
  nome: yup
    .string()
    .required('Nome é obrigatório')
    .trim()
    .matches(regexSomenteLetras, 'Nome deve conter apenas letras'),
  CPF: yup
    .string()
    .required('CPF é obrigatório')
    .trim()
    .test('cpf-valido', 'CPF inválido', (value) => {
      if (!value) return false
      return validarCPF(value)
    }),
  emailInstitucional: yup
    .string()
    .required('E-mail institucional é obrigatório')
    .trim()
    .email('Informe um e-mail válido')
    .test('govbr', 'E-mail deve ser corporativo (domínio .gov.br)', (value) => {
      if (!value) return false
      const dominio = value.split('@')[1]?.toLowerCase()
      return !!dominio?.endsWith('.gov.br')
    }),
  telefoneInstitucional: yup
    .string()
    .required('Telefone institucional é obrigatório')
    .trim()
    .test('telefone', 'Telefone deve ter 10 ou 11 dígitos', (value) => {
      if (!value) return false
      const digitos = value.replace(/\D/g, '')
      return digitos.length === 10 || digitos.length === 11
    }),
  telefonePessoal: yup
    .string()
    .trim()
    .test('telefone', 'Telefone deve ter 10 ou 11 dígitos', (value) => {
      if (!value) return true
      const digitos = value.replace(/\D/g, '')
      return digitos.length === 10 || digitos.length === 11
    }),
  esferaAtuacao: yup
    .string()
    .required('Esfera de atuação é obrigatória')
    .trim(),
  uf: yup
    .string()
    .required('UF é obrigatória')
    .trim(),
  municipio: yup
    .string()
    .required('Município é obrigatório')
    .trim(),
  orgao: yup
    .string()
    .required('Organização é obrigatória')
    .trim(),
  cargo: yup
    .string()
    .required('Cargo é obrigatório')
    .trim(),
})

/**
 * Schema para solicitação via GOV.BR: Nome e CPF vêm do usuário autenticado (somente leitura).
 * CPF não é enviado — o backend usa o CPF do token.
 */
export const SolicitacaoCadastroSchemaGovBr = yup.object({
  nome: yup
    .string()
    .required('Nome é obrigatório')
    .trim()
    .matches(regexSomenteLetras, 'Nome deve conter apenas letras'),
  CPF: yup.string().trim(), // Opcional — preenchido pelo GOV.BR, não enviado
  emailInstitucional: yup
    .string()
    .required('E-mail institucional é obrigatório')
    .trim()
    .email('Informe um e-mail válido')
    .test('govbr', 'E-mail deve ser corporativo (domínio .gov.br)', (value) => {
      if (!value) return false
      const dominio = value.split('@')[1]?.toLowerCase()
      return !!dominio?.endsWith('.gov.br')
    }),
  telefoneInstitucional: yup
    .string()
    .required('Telefone institucional é obrigatório')
    .trim()
    .test('telefone', 'Telefone deve ter 10 ou 11 dígitos', (value) => {
      if (!value) return false
      const digitos = value.replace(/\D/g, '')
      return digitos.length === 10 || digitos.length === 11
    }),
  telefonePessoal: yup
    .string()
    .trim()
    .test('telefone', 'Telefone deve ter 10 ou 11 dígitos', (value) => {
      if (!value) return true
      const digitos = value.replace(/\D/g, '')
      return digitos.length === 10 || digitos.length === 11
    }),
  esferaAtuacao: yup
    .string()
    .required('Esfera de atuação é obrigatória')
    .trim(),
  uf: yup
    .string()
    .required('UF é obrigatória')
    .trim(),
  municipio: yup
    .string()
    .required('Município é obrigatório')
    .trim(),
  orgao: yup
    .string()
    .required('Organização é obrigatória')
    .trim(),
  cargo: yup
    .string()
    .required('Cargo é obrigatório')
    .trim(),
})

export const DadosSolicitanteSchema = yup.object({
  nome: yup
    .string()
    .required('Nome é obrigatório')
    .trim()
    .matches(regexSomenteLetras, 'Nome deve conter apenas letras'),
  CPF: yup
    .string()
    .required('CPF é obrigatório')
    .trim()
    .test('cpf-valido', 'CPF inválido', (value) => {
      if (!value) return false
      return validarCPF(value)
    }),
  emailInstitucional: yup
    .string()
    .required('E-mail institucional é obrigatório')
    .trim()
    .email('Informe um e-mail válido')
    .test('govbr', 'E-mail deve ser corporativo (domínio .gov.br)', (value) => {
      if (!value) return false
      const dominio = value.split('@')[1]?.toLowerCase()
      return !!dominio?.endsWith('.gov.br')
    }),
  telefoneInstitucional: yup
    .string()
    .required('Telefone institucional é obrigatório')
    .trim()
    .test('telefone', 'Telefone deve ter 10 ou 11 dígitos', (value) => {
      if (!value) return false
      const digitos = value.replace(/\D/g, '')
      return digitos.length === 10 || digitos.length === 11
    }),
})

/**
 * Schema para edição: CPF é opcional (não é retornado pela API por segurança).
 * Se informado, será validado e atualizado.
 */
export const SolicitacaoCadastroSchemaEdicao = yup.object({
  nome: yup
    .string()
    .required('Nome é obrigatório')
    .trim()
    .matches(regexSomenteLetras, 'Nome deve conter apenas letras'),
  CPF: yup
    .string()
    .trim()
    .test('cpf-valido', 'CPF inválido', (value) => {
      if (!value) return true
      return validarCPF(value)
    }),
  emailInstitucional: yup
    .string()
    .required('E-mail institucional é obrigatório')
    .trim()
    .email('Informe um e-mail válido')
    .test('govbr', 'E-mail deve ser corporativo (domínio .gov.br)', (value) => {
      if (!value) return false
      const dominio = value.split('@')[1]?.toLowerCase()
      return !!dominio?.endsWith('.gov.br')
    }),
  telefoneInstitucional: yup
    .string()
    .required('Telefone institucional é obrigatório')
    .trim()
    .test('telefone', 'Telefone deve ter 10 ou 11 dígitos', (value) => {
      if (!value) return false
      const digitos = value.replace(/\D/g, '')
      return digitos.length === 10 || digitos.length === 11
    }),
  telefonePessoal: yup
    .string()
    .trim()
    .test('telefone', 'Telefone deve ter 10 ou 11 dígitos', (value) => {
      if (!value) return true
      const digitos = value.replace(/\D/g, '')
      return digitos.length === 10 || digitos.length === 11
    }),
  esferaAtuacao: yup
    .string()
    .required('Esfera de atuação é obrigatória')
    .trim(),
  uf: yup
    .string()
    .required('UF é obrigatória')
    .trim(),
  municipio: yup
    .string()
    .required('Município é obrigatório')
    .trim(),
  orgao: yup
    .string()
    .required('Organização é obrigatória')
    .trim(),
  cargo: yup
    .string()
    .required('Cargo é obrigatório')
    .trim(),
})

export const InformacaoSolicitanteSchema = yup.object({
  esferaAtuacao: yup
    .string()
    .required('Esfera de atuação é obrigatória')
    .trim(),
  uf: yup
    .string()
    .required('UF é obrigatória')
    .trim(),
  municipio: yup
    .string()
    .required('Município é obrigatório')
    .trim(),
  orgao: yup
    .string()
    .required('Organização é obrigatória')
    .trim(),
  cargo: yup
    .string()
    .required('Cargo é obrigatório')
    .trim(),
})
