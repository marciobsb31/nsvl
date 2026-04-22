<template>
  <DefaultLayout>
    <HeaderPage
      title="Gestão de Planos de Ação"
      subtitle="Consulte os planos de ação enviados. Use os filtros para refinar a busca."
      customClass="mb-3"
    />

    <Contexto />

    <Card custom-class="mb-4">
      <div class="row g-3 filtros-wrap">
        <!-- Esfera -->
        <div class="col-12 col-md-6 col-lg-3">
          <div v-if="isEstadual || isMunicipal" class="br-input mb-2 filtro-readonly-field">
            <label for="filtro-esfera-ro">Esfera</label>
            <input id="filtro-esfera-ro" type="text" :value="filtros.esfera" disabled readonly />
          </div>
          <SelectAutocomplete
            v-else
            v-model="filtros.esfera"
            label="Esfera"
            placeholder="Todas"
            :options="opcoesEsfera"
            :include-empty-option="true"
            empty-option-label="Todas"
            input-id="filtro-esfera"
          />
        </div>

        <!-- Estado (UF) -->
        <div class="col-12 col-md-6 col-lg-3">
          <div v-if="isEstadual || isMunicipal" class="br-input mb-2 filtro-readonly-field">
            <label for="filtro-uf-ro">Estado (UF)</label>
            <input id="filtro-uf-ro" type="text" :value="ufContextoLabel" disabled readonly />
          </div>
          <SelectAutocomplete
            v-else
            v-model="filtros.uf_id"
            label="Estado (UF)"
            placeholder="Todas"
            :options="opcoesUf"
            :include-empty-option="true"
            empty-option-label="Todas"
            input-id="filtro-uf"
          />
        </div>

        <!-- Município -->
        <div class="col-12 col-md-6 col-lg-3">
          <div v-if="isMunicipal" class="br-input mb-2 filtro-readonly-field">
            <label for="filtro-municipio-ro">Município</label>
            <input id="filtro-municipio-ro" type="text" :value="municipioContextoLabel" disabled readonly />
          </div>
          <SelectAutocomplete
            v-else
            v-model="filtros.municipio_id"
            label="Município"
            :placeholder="filtros.uf_id ? 'Todos' : 'Selecione a UF primeiro'"
            :options="opcoesMunicipio"
            :disabled="!filtros.uf_id"
            :include-empty-option="true"
            empty-option-label="Todos"
            input-id="filtro-municipio"
          />
        </div>

        <!-- Status -->
        <div class="col-12 col-md-6 col-lg-3">
          <SelectAutocomplete
            v-model="filtros.status"
            label="Status do plano"
            placeholder="Todos"
            :options="opcoesStatus"
            :include-empty-option="true"
            empty-option-label="Todos"
            input-id="filtro-status"
          />
        </div>
      </div>

      <div class="acoes-filtro">
        <button class="br-button secondary" type="button" :disabled="carregando" @click="limparFiltros">
          Limpar filtros
        </button>
        <button class="br-button primary" type="button" :disabled="carregando" @click="pesquisar">
          <i v-if="carregando" class="fas fa-spinner fa-spin" aria-hidden="true"></i>
          Pesquisar
        </button>
      </div>
    </Card>

    <Card custom-class="mb-4">
      <div v-if="carregando" class="estado-resultado">
        <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
        Carregando planos de ação...
      </div>

      <div v-else-if="planos.length === 0" class="estado-resultado">
        {{ jaPesquisou ? 'Nenhum plano de ação encontrado para os filtros selecionados.' : 'Nenhum plano encontrado.' }}
      </div>

      <div v-else class="table-responsive">
        <table class="br-table tabela-planos" role="table">
          <thead>
            <tr>
              <th scope="col">Tipo</th>
              <th scope="col">Esfera</th>
              <th scope="col">UF</th>
              <th scope="col">Município</th>
              <th scope="col">Órgão Gestor</th>
              <th scope="col">Data de Envio</th>
              <th scope="col">Status</th>
              <th scope="col" class="col-acao">Ação</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="plano in planos" :key="plano.id">
              <td>{{ plano.estado_ou_municipio }}</td>
              <td>{{ plano.esfera ?? '—' }}</td>
              <td>{{ plano.uf ?? '—' }}</td>
              <td>{{ plano.municipio ?? '—' }}</td>
              <td>{{ plano.orgao_gestor ?? '—' }}</td>
              <td>{{ formatarData(plano.data_envio) }}</td>
              <td>
                <span :class="['badge-status', badgeClass(plano.status)]">
                  {{ plano.status_label }}
                </span>
              </td>
              <td>
                <div class="acoes-linha">
                  <button class="br-button secondary small" type="button" @click="detalhar(plano.id)">
                    Detalhar
                  </button>
                  <button class="br-button tertiary small" type="button" @click="abrirHistorico(plano)">
                    Histórico
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </Card>

    <div
      v-if="modalHistoricoVisivel"
      class="modal-overlay"
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-historico-titulo"
      @click.self="fecharHistorico"
    >
      <div class="modal-historico-box">
        <div class="modal-historico-head">
          <h2 id="modal-historico-titulo" class="modal-historico-titulo">Histórico do plano de ação</h2>
          <button class="br-button circle small" type="button" aria-label="Fechar histórico" @click="fecharHistorico">
            <i class="fas fa-times" aria-hidden="true"></i>
          </button>
        </div>

        <div v-if="carregandoHistorico" class="estado-resultado">
          <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
          Carregando histórico...
        </div>

        <div v-else-if="historicoPlano.length === 0" class="estado-resultado">
          Nenhum evento registrado para este plano.
        </div>

        <div v-else class="table-responsive modal-historico-tabela-wrap">
          <table class="br-table" role="table" aria-label="Tabela de histórico do plano de ação">
            <thead>
              <tr>
                <th scope="col">Data/Hora</th>
                <th scope="col">Usuário</th>
                <th scope="col">Perfil</th>
                <th scope="col">Evento</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in historicoPlano" :key="item.id">
                <td>{{ formatarDataHora(item.data_hora) }}</td>
                <td>{{ item.usuario || '—' }}</td>
                <td>{{ item.perfil || '—' }}</td>
                <td>{{ item.evento || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="modal-historico-acoes">
          <button class="br-button secondary" type="button" @click="fecharHistorico">Cancelar</button>
        </div>
      </div>
    </div>
  </DefaultLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import HeaderPage from '@/core/components/HeaderPage/HeaderPage.vue'
import Card from '@/core/components/Card/Card.vue'
import Contexto from '@/core/components/Contexto/Contexto.vue'
import SelectAutocomplete from '@/core/components/SelectAutocomplete/SelectAutocomplete.vue'
import type { SelectAutocompleteOption } from '@/core/components/SelectAutocomplete/SelectAutocomplete.vue'
import { useNotification } from '@/core/composables/useNotification'
import { useAuthStore } from '@/stores/authStore'
import { listarMunicipios, listarUfs } from '@/services/LocalidadeService'
import { historicoPlanoGestao, listarPlanosGestao } from '@/features/plano-acao/services/PlanoAcaoGestaoService'
import type {
  FiltrosPlanoAcaoGestao,
  PlanoAcaoGestaoHistoricoItem,
  PlanoAcaoGestaoItem,
} from '@/features/plano-acao/types/PlanoAcaoGestaoInterface'
import type { Municipio, uf } from '@/core/types/localidades/LocalidadeInterface'

defineOptions({ name: 'PlanoAcaoPage' })

const { error } = useNotification()
const router = useRouter()
const authStore = useAuthStore()

const carregando = ref(false)
const jaPesquisou = ref(false)
const planos = ref<PlanoAcaoGestaoItem[]>([])
const modalHistoricoVisivel = ref(false)
const carregandoHistorico = ref(false)
const planoHistoricoSelecionado = ref<PlanoAcaoGestaoItem | null>(null)
const historicoPlano = ref<PlanoAcaoGestaoHistoricoItem[]>([])
const ufs = ref<uf[]>([])
const municipios = ref<Municipio[]>([])

// ── Contexto do usuário logado ─────────────────────────────────────────────
const nomeEsferaContexto = computed(() =>
  String(authStore.user?.contexto?.esfera?.nome ?? '').toLowerCase().trim(),
)
const isEstadual = computed(() => nomeEsferaContexto.value === 'estadual')
const isMunicipal = computed(() => nomeEsferaContexto.value === 'municipal')

const ufContextoLabel = computed(() => {
  const ufId = authStore.user?.contexto?.uf_id
  if (!ufId) return ''
  const encontrada = ufs.value.find((u) => u.id === ufId)
  return encontrada ? `${encontrada.sigla} — ${encontrada.nome}` : ''
})

const municipioContextoLabel = computed(() => {
  const mId = authStore.user?.contexto?.municipio_id
  if (!mId) return ''
  return municipios.value.find((m) => m.id === mId)?.nome ?? ''
})

// ── Filtros ────────────────────────────────────────────────────────────────
const opcoesEsfera: SelectAutocompleteOption[] = [
  { label: 'Estadual', value: 'Estadual' },
  { label: 'Municipal', value: 'Municipal' },
]

const opcoesUf = computed<SelectAutocompleteOption[]>(() =>
  ufs.value.map((u) => ({ label: `${u.sigla} — ${u.nome}`, value: u.id })),
)

const opcoesMunicipio = computed<SelectAutocompleteOption[]>(() =>
  municipios.value.map((m) => ({ label: m.nome, value: m.id })),
)

const opcoesStatus: SelectAutocompleteOption[] = [
  { label: 'Não enviado', value: 'Não enviado' },
  { label: 'Enviado para análise', value: 'Enviado para análise' },
  { label: 'Em análise pela Comissão Técnica', value: 'Em análise pela Comissão Técnica' },
  { label: 'Devolvido para ajustes', value: 'Devolvido para ajustes' },
  { label: 'Aprovado', value: 'Aprovado' },
]

const filtros = ref<FiltrosPlanoAcaoGestao>({
  esfera: undefined,
  status: undefined,
  uf_id: undefined,
  municipio_id: undefined,
})

watch(
  () => filtros.value.uf_id,
  async (ufId) => {
    if (isMunicipal.value) return // contexto municipal: municipio já vem do contexto
    filtros.value.municipio_id = undefined
    if (!ufId) {
      municipios.value = []
      return
    }
    municipios.value = await listarMunicipios(String(ufId))
  },
)

// ── Inicialização ──────────────────────────────────────────────────────────
onMounted(async () => {
  ufs.value = await listarUfs()

  const ufId = authStore.user?.contexto?.uf_id
  const municipioId = authStore.user?.contexto?.municipio_id

  if (isEstadual.value) {
    filtros.value.esfera = 'Estadual'
    filtros.value.uf_id = ufId
  } else if (isMunicipal.value) {
    filtros.value.esfera = 'Municipal'
    filtros.value.uf_id = ufId
    if (ufId) municipios.value = await listarMunicipios(String(ufId))
    filtros.value.municipio_id = municipioId
  }

  await pesquisar()
})

// ── Pesquisar ──────────────────────────────────────────────────────────────
async function pesquisar() {
  carregando.value = true
  jaPesquisou.value = true

  try {
    planos.value = await listarPlanosGestao({
      esfera: filtros.value.esfera || undefined,
      status: filtros.value.status || undefined,
      uf_id: filtros.value.uf_id || undefined,
      municipio_id: filtros.value.municipio_id || undefined,
    })
  } catch {
    error('Não foi possível carregar a gestão de planos de ação.')
    planos.value = []
  } finally {
    carregando.value = false
  }
}

function limparFiltros() {
  // Preserva os campos bloqueados pelo contexto
  filtros.value = {
    esfera: isEstadual.value ? 'Estadual' : isMunicipal.value ? 'Municipal' : undefined,
    status: undefined,
    uf_id: (isEstadual.value || isMunicipal.value) ? authStore.user?.contexto?.uf_id : undefined,
    municipio_id: isMunicipal.value ? authStore.user?.contexto?.municipio_id : undefined,
  }
  if (!isEstadual.value && !isMunicipal.value) municipios.value = []
  planos.value = []
  jaPesquisou.value = false
}

function detalhar(id: number) {
  router.push({ name: 'gestao-planos-acao-detalhar', params: { id } })
}

async function abrirHistorico(plano: PlanoAcaoGestaoItem) {
  modalHistoricoVisivel.value = true
  planoHistoricoSelecionado.value = plano
  historicoPlano.value = []
  carregandoHistorico.value = true

  try {
    historicoPlano.value = await historicoPlanoGestao(plano.id)
  } catch {
    error('Não foi possível carregar o histórico do plano.')
    historicoPlano.value = []
  } finally {
    carregandoHistorico.value = false
  }
}

function fecharHistorico() {
  modalHistoricoVisivel.value = false
  planoHistoricoSelecionado.value = null
  historicoPlano.value = []
}

function formatarData(valor: string | null): string {
  if (!valor) return '—'
  const data = new Date(`${valor}T00:00:00`)
  if (Number.isNaN(data.getTime())) return '—'
  return data.toLocaleDateString('pt-BR')
}

function formatarDataHora(valor: string | null): string {
  if (!valor) return '—'
  const data = new Date(valor)
  if (Number.isNaN(data.getTime())) return '—'
  return data.toLocaleString('pt-BR')
}

function badgeClass(status: string): string {
  const map: Record<string, string> = {
    em_analise: 'badge-azul',
    enviado_para_analise: 'badge-azul',
    em_analise_comissao_tecnica: 'badge-azul',
    aprovado: 'badge-verde',
    conclusao: 'badge-verde',
    devolvido_ajustes: 'badge-amarelo',
    em_ajustes: 'badge-amarelo',
    rascunho: 'badge-cinza',
    nao_iniciado: 'badge-cinza',
    sei_abertura_processo: 'badge-roxo',
    acordo_adesao: 'badge-roxo',
    despacho: 'badge-roxo',
    extrato: 'badge-roxo',
    publicacao_dou: 'badge-roxo',
  }
  return map[status] ?? 'badge-cinza'
}
</script>

<style scoped>
.filtros-wrap {
  align-items: end;
}

.acoes-filtro {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin-top: 1rem;
}

.acoes-filtro .br-button {
  min-width: 10rem;
}

/* Campo bloqueado pelo contexto */
.filtro-readonly-field :deep(input[readonly]),
.filtro-readonly-field :deep(input[disabled]) {
  background-color: var(--gray-5, #f0f0f0) !important;
  color: var(--secondary-text-color, #555) !important;
  border-color: var(--gray-30, #d9d9d9) !important;
  cursor: not-allowed;
  opacity: 1;
}

/* Estado vazio / carregando */
.estado-resultado {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1.25rem 1rem;
  color: var(--color-secondary-08, #555);
  font-size: 0.9375rem;
}

/* Cabeçalho fixo para coluna ação */
.col-acao {
  white-space: nowrap;
  width: 1%;
}

.acoes-linha {
  display: inline-flex;
  gap: 0.4rem;
  flex-wrap: wrap;
}

.tabela-planos th {
  font-weight: 600;
}

/* Badges de status */
.badge-status {
  display: inline-block;
  padding: 0.2rem 0.65rem;
  border-radius: 1rem;
  font-size: 0.8125rem;
  font-weight: 500;
  white-space: nowrap;
}

.badge-azul  { background: #dbeafe; color: #1e40af; }
.badge-verde { background: #dcfce7; color: #15803d; }
.badge-amarelo { background: #fef9c3; color: #854d0e; }
.badge-cinza  { background: #f3f4f6; color: #4b5563; }
.badge-roxo   { background: #ede9fe; color: #5b21b6; }

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 1rem;
  z-index: 9999;
}

.modal-historico-box {
  width: min(66rem, 100%);
  max-height: 90vh;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  padding: 1rem 1.25rem 1.25rem;
}

.modal-historico-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.modal-historico-titulo {
  margin: 0;
  font-size: 1.15rem;
}

.modal-historico-tabela-wrap {
  overflow: auto;
  max-height: 58vh;
  border: 1px solid #ebebeb;
  border-radius: 6px;
}

.modal-historico-acoes {
  display: flex;
  justify-content: flex-end;
  margin-top: 1rem;
}

@media (max-width: 767px) {
  .acoes-filtro {
    flex-direction: column;
  }

  .acoes-filtro .br-button {
    width: 100%;
  }

  .modal-historico-box {
    padding: 0.9rem;
  }
}
</style>
