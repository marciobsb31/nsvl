<template>
  <PublicLayout full-width>
    <section class="solicitacao-page" aria-labelledby="solicitacao-titulo">
      <div class="solicitacao-page__inner">   
        <header class="solicitacao-hero">
          <h1 id="solicitacao-titulo" class="solicitacao-hero__title">
            Solicitação de cadastro
          </h1>
          <p class="solicitacao-hero__lead">
            Preencha os dados abaixo para solicitar acesso ao <strong>NVSL</strong>. Campos marcados com
            <span class="solicitacao-hero__req">*</span> são obrigatórios.
          </p>
          <div class="solicitacao-govbr-msg">
          <Message v-if="modoGovBr" >
              <strong>Dados do GOV.BR:</strong> nome e CPF foram obtidos na autenticação e não podem ser alterados.
          </Message>

          </div>
        </header>

        <Form
          v-slot="{ values: formValues, meta: formMeta }"
          :key="formKey"
          :validation-schema="schemaSolicitacao"
          :initial-values="initialValues"
          class="solicitacao-form"
          :aria-busy="isSubmitting"
          @submit="onSubmit"
        >
          <Card
            title="Dados do solicitante"
            subtitle="Identificação e contato institucional para análise da solicitação."
            custom-class="solicitacao-card solicitacao-card--first"
          >
            <FormularioDadosSolicitante :modo-gov-br="modoGovBr" />
          </Card>

          <Card
            title="Atuação institucional"
            subtitle="Esfera, localidade e órgão em que você atuará no sistema."
            custom-class="solicitacao-card"
          >
            <FormularioInformacaoSolicitante />
          </Card>

          <Card
            title="Termo de uso e privacidade"
           subtitle="A confirmação envia a solicitação e registra sua ciência conforme abaixo."
            custom-class="solicitacao-card solicitacao-card--termo"
          >
             <TermoUsoPrivacidade />
          </Card>

          <div class="solicitacao-acoes">
            <button
              class="br-button secondary solicitacao-acoes__btn"
              type="button"
              @click="onCancel"
            >
              Cancelar
            </button>
            <button
              class="br-button primary solicitacao-acoes__btn solicitacao-acoes__btn--principal"
              type="submit"
              :disabled="isSubmitting || !camposObrigatoriosPreenchidos(formValues) || !formMeta.valid"
              :aria-busy="isSubmitting"
            >
              {{ isSubmitting ? 'Enviando...' : 'Confirmar e enviar solicitação' }}
            </button>
          </div>
        </Form>
      </div>

      <Modal
        v-if="modalVisualizar"
        title="Visualizar solicitação"
        @close="fecharModalVisualizar"
      >
        <div v-if="detalheVisualizar" class="detalhe-solicitacao">
          <p><strong>Nome:</strong> {{ detalheVisualizar.nome }}</p>
          <p><strong>E-mail:</strong> {{ detalheVisualizar.email_institucional }}</p>
          <p><strong>Telefone institucional:</strong> {{ formatarTelefoneExibicao(detalheVisualizar.telefone_institucional) }}</p>
          <p v-if="detalheVisualizar.telefone_pessoal">
            <strong>Telefone pessoal:</strong> {{ formatarTelefoneExibicao(detalheVisualizar.telefone_pessoal) }}
          </p>
          <p><strong>Esfera:</strong> {{ detalheVisualizar.esfera_atuacao }}</p>
          <p><strong>UF:</strong> {{ detalheVisualizar.uf }}</p>
          <p><strong>Município:</strong> {{ detalheVisualizar.municipio }}</p>
          <p><strong>Órgão:</strong> {{ detalheVisualizar.orgao }}</p>
          <p><strong>Cargo:</strong> {{ detalheVisualizar.cargo }}</p>
          <p><strong>Status:</strong> {{ statusLabel(detalheVisualizar.status) }}</p>
          <p><strong>Data:</strong> {{ formatarData(detalheVisualizar.created_at) }}</p>
        </div>
      </Modal>

      <Modal
        v-if="modalEditar"
        title="Editar solicitação"
        @close="fecharModalEditar"
      >
        <p class="text-muted mb-2"><small>CPF não é exibido por segurança. Informe apenas se desejar alterar.</small></p>
        <Form
          v-if="detalheEditar"
          :key="'edit-' + modalEditar"
          :validation-schema="SolicitacaoCadastroSchemaEdicao"
          :initial-values="detalheEditar"
          @submit="onSubmitEditar"
        >
          <FormularioDadosSolicitante :modo-edicao="true" />
          <FormularioInformacaoSolicitante />
          <div class="modal-actions mt-3">
            <button class="br-button secondary" type="button" @click="fecharModalEditar">Cancelar</button>
            <button class="br-button primary ml-2" type="submit" :disabled="editando" :aria-busy="editando">Salvar</button>
          </div>
        </Form>
      </Modal>

      <Modal
        v-if="modalExcluir"
        title="Excluir solicitação"
        @close="fecharModalExcluir"
      >
        <p v-if="solicitacaoExcluir">
          Deseja realmente excluir a solicitação de <strong>{{ solicitacaoExcluir.nome }}</strong>? Esta ação não pode ser desfeita.
        </p>
        <div class="modal-actions mt-3">
          <button class="br-button secondary" type="button" @click="fecharModalExcluir">Cancelar</button>
          <button class="br-button danger ml-2" type="button" :disabled="excluindo" :aria-busy="excluindo" @click="executarExcluir">
            {{ excluindo ? 'Excluindo...' : 'Excluir' }}
          </button>
        </div>
      </Modal>
    </section>
  </PublicLayout>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { Form } from 'vee-validate';
import Card from '@/core/components/Card/Card.vue';
import FormularioDadosSolicitante from '../components/FormularioDadosSolicitante.vue';
import FormularioInformacaoSolicitante from '../components/FormularioInformacaoSolicitante.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { initializeSelect } from '@/core/composables/useGov';
import { SolicitacaoCadastroSchema, SolicitacaoCadastroSchemaGovBr, SolicitacaoCadastroSchemaEdicao } from '../validators/solicitacaoCadastro.schema';
import {
  enviarSolicitacaoCadastro,
  obterSolicitacaoCadastro,
  atualizarSolicitacaoCadastro,
  excluirSolicitacaoCadastro,
  type SolicitacaoCadastroItem,
  type SolicitacaoCadastroDetalhe,
  type SolicitacaoCadastroUpdatePayload,
  type SolicitacaoCadastroPayload,
} from '@/services/SolicitacaoCadastroService';
import { useNotification } from '@/core/composables/useNotification';
import { useRouter } from 'vue-router';
import Modal from '@/core/components/Modal/Modal.vue';
import Message from '@/core/components/Message/Message.vue';
import TermoUsoPrivacidade from '@/core/components/TermoUsoPrivacidade/TermoUsoPrivacidade.vue';


defineOptions({
  name: 'SolicitacaoCadastroIndex'
})

const router = useRouter();
const route = useRoute();
const { success, error } = useNotification();

const govbrNome = (route.query.nome as string) ?? '';
const govbrCpf = (route.query.cpf as string) ?? '';
const modoGovBr = !!(govbrNome && govbrCpf);

function formatarCpf(cpf: string): string {
  const d = String(cpf).replace(/\D/g, '');
  if (d.length !== 11) return cpf;
  return d.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

function formatarTelefoneParaCampo(tel: string | null | undefined): string {
  const d = String(tel ?? '').replace(/\D/g, '');
  if (d.length === 11) return d.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
  if (d.length === 10) return d.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
  return '';
}

function formatarTelefoneExibicao(tel: string | null | undefined): string {
  return formatarTelefoneParaCampo(tel) || String(tel ?? '');
}

const schemaSolicitacao = computed(() =>
  modoGovBr ? SolicitacaoCadastroSchemaGovBr : SolicitacaoCadastroSchema
);

const initialValues = computed(() => ({
  nome: modoGovBr ? govbrNome : '',
  CPF: modoGovBr ? formatarCpf(govbrCpf) : '',
  emailInstitucional: '',
  telefoneInstitucional: '',
  telefonePessoal: '',
  esferaAtuacao: '',
  uf: '',
  municipio: '',
  orgao: '',
  cargo: '',
}));

const isSubmitting = ref(false);
const formKey = ref(0);

const solicitacoes = ref<SolicitacaoCadastroItem[]>([]);
const modalVisualizar = ref<number | null>(null);
const detalheVisualizar = ref<SolicitacaoCadastroDetalhe | null>(null);
const modalEditar = ref<number | null>(null);
const detalheEditar = ref<Record<string, unknown> | null>(null);
const editando = ref(false);
const modalExcluir = ref<boolean>(false);
const solicitacaoExcluir = ref<SolicitacaoCadastroItem | null>(null);
const excluindo = ref(false);

function formatarData(data: string | undefined) {
  if (!data) return '-';
  try {
    return new Date(data).toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return data;
  }
}

function statusLabel(status: string) {
  const map: Record<string, string> = {
    em_analise: 'Em análise',
    aprovado: 'Aprovado',
    reprovado: 'Reprovado',
  };
  return map[status] || status;
}

function statusClass(status: string) {
  const map: Record<string, string> = {
    em_analise: 'warning',
    aprovado: 'success',
    reprovado: 'danger',
  };
  return map[status] || '';
}

async function visualizar(id: number) {
  try {
    detalheVisualizar.value = await obterSolicitacaoCadastro(id);
    modalVisualizar.value = id;
  } catch {
    error('Não foi possível carregar os detalhes da solicitação. Tente novamente.');
  }
}

async function editar(id: number) {
  try {
    const det = await obterSolicitacaoCadastro(id);
    detalheEditar.value = {
      nome: det.nome,
      CPF: '',
      emailInstitucional: det.email_institucional,
      telefoneInstitucional: det.telefone_institucional,
      telefonePessoal: formatarTelefoneParaCampo(det.telefone_pessoal),
      esferaAtuacao: det.esfera_atuacao,
      uf: det.uf,
      municipio: det.municipio,
      orgao: det.orgao,
      cargo: det.cargo,
    };
    modalEditar.value = id;
  } catch {
     error('Não foi possível carregar a solicitação para edição. Tente novamente.');
  }
}

function fecharModalVisualizar() {
  modalVisualizar.value = null;
  detalheVisualizar.value = null;
}

function fecharModalEditar() {
  modalEditar.value = null;
  detalheEditar.value = null;
}

function confirmarExcluir(s: SolicitacaoCadastroItem) {
  solicitacaoExcluir.value = s;
  modalExcluir.value = true;
}

function fecharModalExcluir() {
  modalExcluir.value = false;
  solicitacaoExcluir.value = null;
}

async function executarExcluir() {
  if (!solicitacaoExcluir.value) return;
  excluindo.value = true;
  try {
    await excluirSolicitacaoCadastro(solicitacaoExcluir.value.id);
    success('Solicitação excluída com sucesso.');
    fecharModalExcluir();
  } catch (err: unknown) {
    const msg = err && typeof err === 'object' && 'response' in err
      ? (err as { response?: { data?: { message?: string } } }).response?.data?.message
      : 'Não foi possível excluir a solicitação. Tente novamente.';
    error(msg ?? 'Não foi possível excluir a solicitação. Tente novamente.');
  } finally {
    excluindo.value = false;
  }
}

async function onSubmitEditar(values: Record<string, unknown>) {
  if (!modalEditar.value) return;
  editando.value = true;
  try {
    const tpEd = String(values.telefonePessoal ?? '').replace(/\D/g, '');
    const payload: SolicitacaoCadastroUpdatePayload = {
      nome: values.nome as string,
      emailInstitucional: values.emailInstitucional as string,
      telefoneInstitucional: values.telefoneInstitucional as string,
      telefonePessoal: tpEd.length >= 10 && tpEd.length <= 11 ? tpEd : null,
      esferaAtuacao: values.esferaAtuacao as string,
      uf: values.uf as string,
      municipio: values.municipio as string,
      orgao: values.orgao as string,
      cargo: values.cargo as string,
    };
    if (values.CPF && String(values.CPF).trim()) {
      payload.CPF = values.CPF as string;
    }
    await atualizarSolicitacaoCadastro(modalEditar.value, payload);
    success('Solicitação atualizada com sucesso.');
    fecharModalEditar();
  } catch (err: unknown) {
    const msg = err && typeof err === 'object' && 'response' in err
      ? (err as { response?: { data?: { message?: string } } }).response?.data?.message
      : 'Não foi possível atualizar a solicitação. Verifique os dados e tente novamente.';
    error(msg ?? 'Não foi possível atualizar a solicitação. Verifique os dados e tente novamente.');
  } finally {
    editando.value = false;
  }
}

async function onSubmit(values: Record<string, unknown>) {
  isSubmitting.value = true;
  try {
    const tp = String(values.telefonePessoal ?? '').replace(/\D/g, '');
    const telInst = String(values.telefoneInstitucional ?? '').replace(/\D/g, '');
    const payload: SolicitacaoCadastroPayload = {
      nome: String(values.nome ?? '').trim(),
      emailInstitucional: String(values.emailInstitucional ?? '').trim(),
      telefoneInstitucional: telInst,
      telefonePessoal: tp.length >= 10 && tp.length <= 11 ? tp : null,
      esferaAtuacao: String(values.esferaAtuacao ?? '').trim(),
      uf: String(values.uf ?? '').trim().toUpperCase(),
      municipio: String(values.municipio ?? '').trim(),
      orgao: String(values.orgao ?? '').trim(),
      cargo: String(values.cargo ?? '').trim(),
    };
    const cpfVal = values.CPF as string;
    if (cpfVal && !cpfVal.includes('*')) {
      payload.CPF = cpfVal.replace(/\D/g, '');
    }
    await enviarSolicitacaoCadastro(payload);
   success('Solicitação enviada com sucesso! Sua solicitação está com o status "Em Análise" e será avaliada pela equipe gestora. Você será redirecionado para a tela de login.');
    setTimeout(() => {
      router.push({ name: 'login' });
    }, 4000);
  } catch (err: unknown) {
   const axErr = err as { response?: { status?: number; data?: { message?: string; errors?: Record<string, string[]> } } };
    let msg = '';
    if (axErr?.response?.data?.message) {
      msg = axErr.response.data.message;
    } else if (axErr?.response?.status === 422) {
      msg = 'Alguns campos possuem dados inválidos. Revise o formulário e tente novamente.';
    } else if (axErr?.response?.status === 429) {
      msg = 'Muitas tentativas em pouco tempo. Aguarde alguns instantes e tente novamente.';
    } else if (axErr?.response?.status && axErr.response.status >= 500) {
      msg = 'O servidor encontrou um erro inesperado. Tente novamente em alguns minutos.';
    } else {
      msg = 'Não foi possível conectar ao servidor. Verifique sua conexão e tente novamente.';
    }
    error(msg);
  } finally {
    isSubmitting.value = false;
  }
}

function camposObrigatoriosPreenchidos(values: Record<string, unknown>) {
  const obrigatorios = [
    'nome',
    'CPF',
    'emailInstitucional',
    'telefoneInstitucional',
    'esferaAtuacao',
    'uf',
    'municipio',
    'orgao',
    'cargo',
  ]
  return obrigatorios.every((campo) => String(values[campo] ?? '').trim() !== '')
}

function onCancel() {
  router.push({ name: 'home' });
}

onMounted(() => {
  initializeSelect();
});
</script>

<style scoped>
/* —— Página (Padrão Digital / eGOV) —— */
.solicitacao-page {
  width: 100%;
  background: var(--background);
}

.solicitacao-page__inner {
  width: 100%;
  max-width: min(100%, 80rem);
  margin: 0 auto;
  padding: 0 1rem;
}

@media (min-width: 576px) {
  .solicitacao-page__inner {
    padding: 0 1.5rem;
  }
}

@media (min-width: 1200px) {
  .solicitacao-page__inner {
    padding: 0 2rem;
  }
}

/* Breadcrumb */
.solicitacao-breadcrumb {
  margin-bottom: 1rem;
}

.solicitacao-breadcrumb__list {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.35rem 0.5rem;
  list-style: none;
  margin: 0;
  padding: 0;
  font-size: 0.8125rem;
}

.solicitacao-breadcrumb__link {
  color: var(--color-primary-default, #1351b4);
  text-decoration: underline;
  text-underline-offset: 2px;
}

.solicitacao-breadcrumb__link:hover,
.solicitacao-breadcrumb__link:focus {
  color: var(--color-primary-darken-01, #0f3d85);
}

.solicitacao-breadcrumb__sep {
  color: var(--color-secondary-06, #888);
  user-select: none;
}

.solicitacao-breadcrumb__current {
  color: var(--color-secondary-08, #333);
  font-weight: 600;
}

/* Hero */
.solicitacao-hero {
  margin-bottom: 1.5rem;
}

.solicitacao-hero__title {
  margin: 0 0 0.75rem;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.25;
  color: var(--primary-text-dark-color);
  letter-spacing: -0.02em;
}

@media (min-width: 768px) {
  .solicitacao-hero__title {
    font-size: 1.75rem;
  }
}

.solicitacao-hero__lead {
  margin: 0;
  max-width: 62rem;
  font-size: 0.9375rem;
  line-height: 1.55;
  color: var(--dark-text-color);
}

.solicitacao-hero__req {
  color: var(--color-danger, #e52207);
  font-weight: 700;
}

.solicitacao-govbr-msg {
  margin: 0 0 1.25rem;
  border: none;
  border-radius: 6px;
}
.solicitacao-govbr-msg .content {
  font-size: 0.875rem;
  line-height: 1.5;
}

/* Cards empilhados */
.solicitacao-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

@media (min-width: 768px) {
  .solicitacao-form {
    gap: 1.5rem;
  }
}

:deep(.solicitacao-card) {
  margin-top: 0 !important;
}

:deep(.solicitacao-card--first) {
  margin-top: 0;
}


/* Ações */
.solicitacao-acoes {
  display: flex;
  flex-direction: column-reverse;
  gap: 0.75rem;
  margin-top: 0.5rem;
  padding: 1rem 0 0;
}

.solicitacao-acoes__btn {
  width: 100%;
  min-height: 2.75rem;
  justify-content: center;
}

@media (min-width: 576px) {
  .solicitacao-acoes {
    flex-direction: row;
    flex-wrap: wrap;
    justify-content: flex-end;
    align-items: center;
  }

  .solicitacao-acoes__btn {
    width: auto;
    min-width: 10rem;
  }

  .solicitacao-acoes__btn--principal {
    min-width: 14rem;
  }
  
}
</style>
