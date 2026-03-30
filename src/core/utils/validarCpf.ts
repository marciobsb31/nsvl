/**
 * Valida CPF conforme algoritmo oficial (dígitos verificadores)
 */
export function validarCpf(cpf: string | undefined): boolean {
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
