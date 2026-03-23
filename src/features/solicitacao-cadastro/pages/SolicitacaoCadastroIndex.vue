<template>
  <PublicLayout full-width>
    <section class="container-solicitacao">
      <div class="titulo">
        <h1 class="color-text text-weight-semi-bold">Solicitação de cadastro</h1>
      </div>
      <Form
        v-slot="{ values: formValues }"
        :key="formKey"
        :validation-schema="schemaSolicitacao"
        :initial-values="initialValues"
        @submit="onSubmit"
      >
        <Card
          title="Dados do(a) solicitante"
          subtitle="Preencha seus dados para solicitar acesso"
        >
          <FormularioDadosSolicitante :modo-gov-br="modoGovBr" />
        </Card>
        <Card title="Informação do(a) solicitante" subtitle="Informações de atuação institucional do solicitante"
          custom-class="mt-4">
          <FormularioInformacaoSolicitante />
        </Card>
        <Card title="Aceite do Termo e uso e Privacidade"
          subtitle="O aceite ocorre no ato da confirmação e envio da solicitação." custom-class="mt-4">
          <p><strong>Ao confirmar a solicitação você aceita o seguinte termo de uso e privacidade:</strong> os dados
            informados serão utilizados exclusivamente para fins de análise, habilitação e gestão de acesso ao sistema NVSL.
          </p>
          <p>O envio da solicitação implica ciência quanto ao tratamento dos dados pessoais e utilização para fins
            institucionais.</p>
        </Card>
        <div class="mt-3 actions">
          <button class="br-button secondary mr-3" type="button" @click="onCancel">Cancelar</button>
          <button
            class="br-button primary mr-3"
            type="submit"
            :disabled="isSubmitting || !camposObrigatoriosPreenchidos(formValues)"
          >
            {{ isSubmitting ? 'Enviando...' : 'Confirmar/Enviar solicitação' }}
          </button>
        </div>
      </Form>

      <Modal
        v-if="modalVisualizar"
        title="Visualizar solicitação"
        @close="fecharModalVisualizar"
      >
        <div v-if="detalheVisualizar" class="detalhe-solicitacao">
          <p><strong>Nome:</strong> {{ detalheVisualizar.nome }}</p>
          <p><strong>E-mail:</strong> {{ detalheVisualizar.email_institucional }}</p>
          <p><strong>Telefone institucional:</strong> {{ detalheVisualizar.telefone_institucional }}</p>
          <p><strong>Telefone pessoal:</strong> {{ detalheVisualizar.telefone_pessoal || '-' }}</p>
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
            <button class="br-button primary ml-2" type="submit" :disabled="editando">Salvar</button>
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
          <button class="br-button danger ml-2" type="button" :disabled="excluindo" @click="executarExcluir">
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
    error('Erro ao carregar detalhes da solicitação.');
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
      telefonePessoal: det.telefone_pessoal || '',
      esferaAtuacao: det.esfera_atuacao,
      uf: det.uf,
      municipio: det.municipio,
      orgao: det.orgao,
      cargo: det.cargo,
    };
    modalEditar.value = id;
  } catch {
    error('Erro ao carregar solicitação para edição.');
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
      : 'Erro ao excluir solicitação.';
    error(msg ?? 'Erro ao excluir solicitação.');
  } finally {
    excluindo.value = false;
  }
}

async function onSubmitEditar(values: Record<string, unknown>) {
  if (!modalEditar.value) return;
  editando.value = true;
  try {
    const payload: SolicitacaoCadastroUpdatePayload = {
      nome: values.nome as string,
      emailInstitucional: values.emailInstitucional as string,
      telefoneInstitucional: values.telefoneInstitucional as string,
      telefonePessoal: (values.telefonePessoal as string) || undefined,
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
      : 'Erro ao atualizar solicitação.';
    error(msg ?? 'Erro ao atualizar solicitação.');
  } finally {
    editando.value = false;
  }
}

async function onSubmit(values: Record<string, unknown>) {
  isSubmitting.value = true;
  try {
    const payload: SolicitacaoCadastroPayload = {
      nome: values.nome as string,
      emailInstitucional: values.emailInstitucional as string,
      telefoneInstitucional: values.telefoneInstitucional as string,
      telefonePessoal: (values.telefonePessoal as string) || undefined,
      esferaAtuacao: values.esferaAtuacao as string,
      uf: values.uf as string,
      municipio: values.municipio as string,
      orgao: values.orgao as string,
      cargo: values.cargo as string,
    };
    const cpfVal = values.CPF as string;
    if (cpfVal && !cpfVal.includes('*')) {
      payload.CPF = cpfVal;
    }
    await enviarSolicitacaoCadastro(payload);
    success('Solicitação enviada com sucesso! Aguarde a análise da equipe.');
    formKey.value++;
  } catch (err: unknown) {
    const msg = err && typeof err === 'object' && 'response' in err
      ? (err as { response?: { data?: { message?: string } } }).response?.data?.message
      : 'Não foi possível enviar. Verifique os dados e tente novamente.';
    error(msg || 'Não foi possível enviar. Verifique os dados e tente novamente.');
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
.container-solicitacao {
  padding: 1.5rem;
}

.listagem-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}
.actions {
  display: flex;
  justify-content: flex-end;
}

.color-text{
  color: var(--color-text-h1)
}

@media (max-width: 575px) {
  .actions {
    flex-direction: column-reverse;
    justify-content: center;
    gap: 1rem;
  }

  .titulo {
    text-align: center;
  }

  .titulo h1 {
    font-size: 1.25rem;
  }

  .container-solicitacao {
    padding: 1rem;
  }
}

@media (max-width: 991px) {
  .titulo h1 {
    font-size: 1.5rem;
  }
}
</style>
