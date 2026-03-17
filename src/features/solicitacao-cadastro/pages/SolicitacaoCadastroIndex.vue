<template>
  <DefaultLayout>
    <section class="container-solicitacao">
      <div class="titulo">
        <h1 class="color-text text-weight-semi-bold">Solicitação de cadastro</h1>
      </div>
      <div v-for="n in notifications" :key="n.id" class="mb-3">
        <div class="br-message" :class="n.type">
          <div class="icon"><i class="fas fa-info-circle" aria-hidden="true"></i></div>
          <div class="content" role="alert">{{ n.message }}</div>
          <div class="close">
            <button class="br-button circle small" type="button" aria-label="Fechar" @click="removeNotification(n.id)">
              <i class="fas fa-times" aria-hidden="true"></i>
            </button>
          </div>
        </div>
      </div>
      <div v-if="solicitacaoEnviada" class="br-message success mb-3" role="alert">
        <div class="icon"><i class="fas fa-check-circle" aria-hidden="true"></i></div>
        <div class="content">
          Solicitação de cadastro enviada com sucesso. Aguarde a análise.
        </div>
      </div>
      <Form :key="formKey" :validation-schema="SolicitacaoCadastroSchemaGovBr" :initial-values="initialValues" @submit="onSubmit">
        <Card title="Dados do(a) solicitante" subtitle="Nome e CPF são obtidos pelo GOV.BR">
          <FormularioDadosSolicitante :modo-gov-br="true" />
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
          <button class="br-button primary mr-3" type="submit" :disabled="isSubmitting">
            {{ isSubmitting ? 'Enviando...' : 'Confirmar/Enviar solicitação' }}
          </button>
        </div>
      </Form>

      <Card title="Solicitações enviadas" subtitle="Listagem das solicitações de cadastro" custom-class="mt-4">
        <div class="listagem-header">
          <span class="text-muted small">id | nome | status | created_at</span>
          <button
            class="br-button secondary small"
            type="button"
            :disabled="carregandoLista"
            @click="carregarLista"
            title="Atualizar listagem"
          >
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': carregandoLista }" aria-hidden="true"></i>
            Atualizar
          </button>
        </div>
        <div v-if="carregandoLista" class="p-3 text-center">Carregando...</div>
        <div v-else-if="solicitacoes.length === 0" class="p-3 text-center text-muted">
          Nenhuma solicitação encontrada.
        </div>
        <div v-else class="table-responsive">
          <table class="br-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Status</th>
                <th>Data</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in solicitacoes" :key="s.id">
                <td>{{ s.id }}</td>
                <td>{{ s.nome }}</td>
                <td>
                  <span class="br-tag" :class="statusClass(s.status)">{{ statusLabel(s.status) }}</span>
                </td>
                <td>{{ formatarData(s.created_at) }}</td>
                <td>
                  <button class="br-button secondary small" type="button" @click="visualizar(s.id)" title="Visualizar">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                  </button>
                  <button
                    class="br-button secondary small ml-1"
                    type="button"
                    @click="editar(s.id)"
                    title="Editar"
                    :disabled="s.status !== 'em_analise'"
                  >
                    <i class="fas fa-edit" aria-hidden="true"></i>
                  </button>
                  <button
                    class="br-button secondary small ml-1"
                    type="button"
                    @click="confirmarExcluir(s)"
                    title="Excluir"
                  >
                    <i class="fas fa-trash" aria-hidden="true"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

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
  </DefaultLayout>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Form } from 'vee-validate';
import Card from '@/core/components/Card/Card.vue';
import FormularioDadosSolicitante from '../components/FormularioDadosSolicitante.vue';
import FormularioInformacaoSolicitante from '../components/FormularioInformacaoSolicitante.vue';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { initializeSelect } from '@/core/composables/useGov';
import { SolicitacaoCadastroSchemaGovBr, SolicitacaoCadastroSchemaEdicao } from '../validators/solicitacaoCadastro.schema';
import {
  enviarSolicitacaoCadastro,
  listarSolicitacoesCadastro,
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
import { useAuth } from '@/core/composables/useAuth';
import Modal from '@/core/components/Modal/Modal.vue';

defineOptions({
  name: 'SolicitacaoCadastroIndex'
})

const router = useRouter();
const { notifications, success, error, remove } = useNotification();
const { user, logout } = useAuth();

function removeNotification(id: string) {
  remove(id);
}

const initialValues = computed(() => ({
  nome: user.value?.name ?? '',
  CPF: '***.***.***-**',
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
const solicitacaoEnviada = ref(false);
const formKey = ref(0);

const solicitacoes = ref<SolicitacaoCadastroItem[]>([]);
const carregandoLista = ref(true);
const modalVisualizar = ref<number | null>(null);
const detalheVisualizar = ref<SolicitacaoCadastroDetalhe | null>(null);
const modalEditar = ref<number | null>(null);
const detalheEditar = ref<Record<string, unknown> | null>(null);
const editando = ref(false);
const modalExcluir = ref<boolean>(false);
const solicitacaoExcluir = ref<SolicitacaoCadastroItem | null>(null);
const excluindo = ref(false);

async function carregarLista() {
  carregandoLista.value = true;
  try {
    solicitacoes.value = await listarSolicitacoesCadastro();
  } catch {
    solicitacoes.value = [];
    error('Não foi possível carregar a listagem de solicitações. Verifique se o backend está em execução.');
  } finally {
    carregandoLista.value = false;
  }
}

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
    await carregarLista();
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
    await carregarLista();
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
  solicitacaoEnviada.value = false;
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
    await logout('/login?solicitacao=enviada');
  } catch (err: unknown) {
    const msg = err && typeof err === 'object' && 'response' in err
      ? (err as { response?: { data?: { message?: string } } }).response?.data?.message
      : 'Erro ao enviar solicitação. Tente novamente.';
    error(msg || 'Erro ao enviar solicitação. Tente novamente.');
  } finally {
    isSubmitting.value = false;
  }
}

function onCancel() {
  router.push({ name: 'login' });
}

onMounted(() => {
  initializeSelect();
  carregarLista();
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
