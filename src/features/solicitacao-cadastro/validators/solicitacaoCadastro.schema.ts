import * as yup from 'yup'

export const DadosSolicitanteSchema = yup.object({
  nome: yup
    .string()
    .required('Nome é obrigatório')
    .trim(),
  CPF: yup
    .string()
    .required('CPF é obrigatório')
    .trim(),
  emailInstitucional: yup
    .string()
    .email('Informe um email institucional válido')
    .required('Email institucional é obrigatório')
    .trim(),
  telefoneInstitucional: yup
    .string()
    .required('Telefone institucional é obrigatório')
    .trim(),
});

export const InformacaoSolicitanteSchema = yup.object({
  esferaAtuacao: yup
    .string()
    // .required('Esfera de atuação é obrigatória')
    .trim(),
  uf: yup
    .string()
    // .required('UF é obrigatória')
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
});