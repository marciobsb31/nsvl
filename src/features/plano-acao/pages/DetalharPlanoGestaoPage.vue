<template>
  <DefaultLayout>
    <!-- Loading -->
    <Card v-if="carregando" custom-class="mb-4">
      <div class="estado-resultado">
        <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
        Carregando detalhamento do plano...
      </div>
    </Card>

    <!-- Erro / não encontrado -->
    <Card v-else-if="!plano" custom-class="mb-4">
      <p class="m-0">Plano de ação não encontrado.</p>
      <button class="br-button secondary mt-3" type="button" @click="voltar">Voltar</button>
    </Card>

    <template v-else>
      <!-- Cabeçalho -->
      <HeaderPage title="Detalhamento do Plano de Ação" customClass="mb-0">
        <template #actions>
          <button class="br-button secondary" type="button" @click="voltar">
            <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
            Voltar
          </button>
        </template>
      </HeaderPage>

      <!-- Card de identificação do plano -->
      <Card custom-class="mb-4 card-cabecalho-plano">
        <div class="row g-3">
          <div class="col-12 col-md-3">
            <span class="campo-label">Tipo</span>
            <p class="campo-valor">{{ plano.estado_ou_municipio }}</p>
          </div>
          <div class="col-12 col-md-3">
            <span class="campo-label">Esfera</span>
            <p class="campo-valor">{{ plano.esfera ?? '—' }}</p>
          </div>
          <div class="col-12 col-md-2">
            <span class="campo-label">UF</span>
            <p class="campo-valor">{{ plano.uf ?? '—' }}</p>
          </div>
          <div class="col-12 col-md-4">
            <span class="campo-label">Município</span>
            <p class="campo-valor">{{ plano.municipio ?? '—' }}</p>
          </div>
          <div class="col-12">
            <span class="campo-label">Status atual</span>
            <p class="campo-valor">
              <span :class="['badge-status', badgeClass(plano.status)]">{{ plano.status_label }}</span>
            </p>
          </div>
        </div>
      </Card>

      <!-- Ciclo de vida -->
      <Card title="Ciclo de vida do plano" custom-class="mb-4">
        <div class="ciclo-vida" role="list" aria-label="Etapas do ciclo de vida">
          <div
            v-for="(etapa, index) in cicloVida"
            :key="etapa.status"
            class="ciclo-etapa"
            :class="{
              'ciclo-etapa--concluida': etapaIndice > index,
              'ciclo-etapa--atual': etapaIndice === index,
              'ciclo-etapa--futura': etapaIndice < index,
            }"
            role="listitem"
          >
            <div class="ciclo-etapa__icone" :aria-label="etapa.label">
              <i v-if="etapaIndice > index" class="fas fa-check" aria-hidden="true"></i>
              <span v-else class="ciclo-etapa__numero">{{ index + 1 }}</span>
            </div>
            <div class="ciclo-etapa__label">{{ etapa.label }}</div>
            <div v-if="index < cicloVida.length - 1" class="ciclo-etapa__linha"></div>
          </div>
        </div>
      </Card>

      <!-- Seção 1: Identificação -->
      <Card title="1. Identificação do plano" custom-class="mb-4">
        <div v-if="plano.identificacao" class="row g-3">
          <div class="col-12 col-md-6">
            <span class="campo-label">Estado / Município</span>
            <p class="campo-valor">{{ plano.municipio ?? plano.uf ?? '—' }}</p>
          </div>
          <div class="col-12">
            <span class="campo-label">Órgão gestor da política da pessoa com deficiência</span>
            <p class="campo-valor">{{ plano.identificacao.orgao_gestor || '—' }}</p>
          </div>
          <div class="col-12">
            <span class="campo-label">Demais órgãos/secretarias envolvidos</span>
            <div v-if="plano.identificacao.secretarias_envolvidas?.length">
              <ul class="lista-tags">
                <li v-for="s in plano.identificacao.secretarias_envolvidas" :key="s" class="tag-item">{{ s }}</li>
              </ul>
            </div>
            <p v-else class="campo-valor">—</p>
          </div>
          <div class="col-12 col-md-6">
            <span class="campo-label">Vigência — início</span>
            <p class="campo-valor">{{ formatarData(plano.identificacao.vigencia_inicio) }}</p>
          </div>
          <div class="col-12 col-md-6">
            <span class="campo-label">Vigência — fim</span>
            <p class="campo-valor">{{ formatarData(plano.identificacao.vigencia_fim) }}</p>
          </div>
        </div>
        <p v-else class="campo-vazio">Identificação não preenchida.</p>
      </Card>

      <!-- Seção 2: Diagnóstico -->
      <Card title="2. Diagnóstico" custom-class="mb-4">
        <div v-if="plano.diagnostico" class="row g-3">
          <div class="col-12">
            <span class="campo-label">Caracterização da população com deficiência</span>
            <p class="campo-valor campo-texto-longo">{{ plano.diagnostico.caracterizacao_populacao || '—' }}</p>
          </div>
          <div v-for="barreira in barreirasColunas" :key="barreira.campo" class="col-12 col-md-6">
            <span class="campo-label">{{ barreira.titulo }}</span>
            <div v-if="barreira.valores?.length">
              <ul class="lista-tags">
                <li v-for="v in barreira.valores" :key="v" class="tag-item">{{ v }}</li>
              </ul>
            </div>
            <p v-else class="campo-valor">Nenhuma barreira identificada</p>
          </div>
          <div v-if="plano.diagnostico.outras_barreiras" class="col-12">
            <span class="campo-label">Outras barreiras</span>
            <p class="campo-valor campo-texto-longo">{{ plano.diagnostico.outras_barreiras }}</p>
          </div>
        </div>
        <p v-else class="campo-vazio">Diagnóstico não preenchido.</p>
      </Card>

      <!-- Seção 3: Eixos -->
      <template v-for="eixoNum in ([1, 2, 3, 4] as const)" :key="eixoNum">
        <Card :title="`3.${eixoNum} ${nomeEixo(eixoNum)}`" custom-class="mb-4">
          <div v-if="plano.eixos[eixoNum]?.length">
            <div
              v-for="(acao, i) in plano.eixos[eixoNum]"
              :key="acao.id"
              class="acao-detalhe"
              :class="{ 'acao-detalhe--sep': i > 0 }"
            >
              <div class="acao-detalhe__titulo">Ação {{ i + 1 }}</div>
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <span class="campo-label">Nome da ação</span>
                  <p class="campo-valor">{{ acao.nome || '—' }}</p>
                </div>
                <div class="col-12 col-md-6">
                  <span class="campo-label">Meta</span>
                  <p class="campo-valor">{{ acao.meta || '—' }}</p>
                </div>
                <div v-if="acao.metas_por_ano?.length" class="col-12">
                  <span class="campo-label">Descrição da meta por ano</span>
                  <div class="table-responsive mt-1">
                    <table class="br-table tabela-inline" role="table">
                      <thead><tr><th scope="col">Ano</th><th scope="col">Descrição</th></tr></thead>
                      <tbody>
                        <tr v-for="mp in acao.metas_por_ano" :key="mp.ano">
                          <td>{{ mp.ano }}</td>
                          <td>{{ mp.descricao || '—' }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <span class="campo-label">Órgãos locais responsáveis</span>
                  <div v-if="acao.orgaos_locais?.length">
                    <ul class="lista-tags"><li v-for="o in acao.orgaos_locais" :key="o" class="tag-item">{{ o }}</li></ul>
                  </div>
                  <p v-else class="campo-valor">—</p>
                </div>
                <div class="col-12 col-md-6">
                  <span class="campo-label">Órgão federal vinculado</span>
                  <div v-if="acao.orgaos_federais?.length">
                    <ul class="lista-tags"><li v-for="o in acao.orgaos_federais" :key="o" class="tag-item">{{ o }}</li></ul>
                  </div>
                  <p v-else class="campo-valor">—</p>
                </div>
                <div class="col-12 col-md-6">
                  <span class="campo-label">Ação correspondente no NVSL</span>
                  <p class="campo-valor">
                    {{ acao.acao_nvsl || '—' }}
                    <span v-if="acao.acao_nvsl_outra"> — {{ acao.acao_nvsl_outra }}</span>
                  </p>
                </div>
                <div class="col-12 col-md-6">
                  <span class="campo-label">Vigência / Ano de entrega</span>
                  <p class="campo-valor">{{ acao.vigencia || '—' }}</p>
                </div>
                <div class="col-12 col-md-6">
                  <span class="campo-label">Indicador de produto</span>
                  <p class="campo-valor campo-texto-longo">{{ acao.indicador_produto || '—' }}</p>
                </div>
                <div class="col-12 col-md-6">
                  <span class="campo-label">Indicador de resultado</span>
                  <p class="campo-valor campo-texto-longo">{{ acao.indicador_resultado || '—' }}</p>
                </div>
                <div v-if="acao.orcamentos?.length" class="col-12">
                  <span class="campo-label">Orçamento e fonte de recursos</span>
                  <div class="table-responsive mt-1">
                    <table class="br-table tabela-inline" role="table">
                      <thead><tr><th scope="col">Orçamento estimado</th><th scope="col">Fonte de recursos</th></tr></thead>
                      <tbody>
                        <tr v-for="(orc, oi) in acao.orcamentos" :key="oi">
                          <td>{{ orc.orcamento_estimado || '—' }}</td>
                          <td>{{ orc.fonte_recurso || '—' }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            <div v-if="justificativaEixo(eixoNum)" class="justificativa-eixo mt-3">
              <span class="campo-label">Justificativa para ausência de ações</span>
              <p class="campo-valor campo-texto-longo">{{ justificativaEixo(eixoNum) }}</p>
            </div>
          </div>
          <div v-else>
            <p class="campo-vazio">Nenhuma ação cadastrada para este eixo.</p>
            <div v-if="justificativaEixo(eixoNum)" class="justificativa-eixo mt-2">
              <span class="campo-label">Justificativa</span>
              <p class="campo-valor campo-texto-longo">{{ justificativaEixo(eixoNum) }}</p>
            </div>
          </div>
        </Card>
      </template>

      <!-- Seção 4: Observações -->
      <Card title="4. Observações complementares" custom-class="mb-4">
        <p v-if="plano.observacoes" class="campo-valor campo-texto-longo">{{ plano.observacoes }}</p>
        <p v-else class="campo-vazio">Nenhuma observação complementar.</p>
      </Card>

      <!-- Seção 5: Anexos -->
      <Card title="5. Anexos" custom-class="mb-4">
        <div v-if="plano.anexos?.length">
          <ul class="lista-anexos">
            <li v-for="anexo in plano.anexos" :key="anexo.id" class="anexo-item">
              <i :class="['fas', iconeAnexo(anexo.tipo_mime), 'anexo-item__icone']" aria-hidden="true"></i>
              <span class="anexo-item__nome">{{ anexo.nome_original }}</span>
              <span class="anexo-item__tamanho">{{ formatarBytes(anexo.tamanho_bytes) }}</span>
            </li>
          </ul>
        </div>
        <p v-else class="campo-vazio">Nenhum anexo vinculado ao plano.</p>
      </Card>

      <!-- Seção 6: Responsável técnico -->
      <Card title="6. Responsável técnico pelo preenchimento" custom-class="mb-4">
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <span class="campo-label">Nome</span>
            <p class="campo-valor">{{ plano.responsavel_nome || '—' }}</p>
          </div>
          <div class="col-12 col-md-6">
            <span class="campo-label">Cargo</span>
            <p class="campo-valor">{{ plano.responsavel_cargo || '—' }}</p>
          </div>
          <div class="col-12 col-md-6">
            <span class="campo-label">Órgão</span>
            <p class="campo-valor">{{ plano.responsavel_orgao || '—' }}</p>
          </div>
          <div class="col-12 col-md-6">
            <span class="campo-label">E-mail e telefone</span>
            <p class="campo-valor">{{ plano.responsavel_contato || '—' }}</p>
          </div>
          <div class="col-12 col-md-6">
            <span class="campo-label">Data de envio</span>
            <p class="campo-valor">{{ formatarData(plano.data_envio) }}</p>
          </div>
        </div>
      </Card>

      <!-- Ações da tela -->
      <div class="painel-acoes mb-4">
        <button class="br-button secondary painel-acoes__btn" type="button" @click="voltar">
          <i class="fas fa-arrow-left" aria-hidden="true"></i>
          Voltar
        </button>
        <template v-if="podeAprovar">
          <button
            class="br-button danger painel-acoes__btn"
            type="button"
            :disabled="processando"
            @click="abrirModalAjustes"
          >
            <i class="fas fa-undo" aria-hidden="true"></i>
            Solicitar ajustes
          </button>
          <button
            class="br-button primary painel-acoes__btn"
            type="button"
            :disabled="processando"
            @click="abrirModalAprovar"
          >
            <i v-if="!processando" class="fas fa-check" aria-hidden="true"></i>
            <i v-else class="fas fa-spinner fa-spin" aria-hidden="true"></i>
            Aprovar plano
          </button>
        </template>
      </div>
    </template>

    <!-- Modal: Aprovar plano -->
    <div
      v-if="modalAprovar"
      class="modal-overlay"
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-aprovar-titulo"
    >
      <div class="modal-box">
        <h2 id="modal-aprovar-titulo" class="modal-titulo">Aprovar plano de ação</h2>
        <p class="modal-desc">Confirma a aprovação deste plano de ação?</p>
        <div class="modal-acoes">
          <button class="br-button secondary" type="button" @click="modalAprovar = false">Cancelar</button>
          <button class="br-button primary" type="button" :disabled="processando" @click="confirmarAprovacao">
            <i v-if="processando" class="fas fa-spinner fa-spin" aria-hidden="true"></i>
            <i v-else class="fas fa-check" aria-hidden="true"></i>
            {{ processando ? 'Aprovando...' : 'Confirmar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Solicitar ajustes -->
    <div
      v-if="modalAjustes"
      class="modal-overlay"
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-ajustes-titulo"
    >
      <div class="modal-box">
        <h2 id="modal-ajustes-titulo" class="modal-titulo">Solicitar ajustes no plano</h2>
        <div class="modal-status-flow">
          <span class="modal-status-chip modal-status-chip--atual">{{ plano?.status_label ?? 'Em análise' }}</span>
          <i class="fas fa-arrow-right" aria-hidden="true"></i>
          <span class="modal-status-chip modal-status-chip--destino">Em ajustes</span>
        </div>
        <p class="modal-desc">
          O status do plano será alterado para <strong>Em ajustes</strong>.
          O responsável será notificado para realizar as correções necessárias.
        </p>
        <div class="modal-hint-box">
          <strong>Recomendação:</strong> descreva os pontos que precisam de revisão para acelerar a correção.
        </div>
        <div class="modal-sugestoes" aria-label="Sugestões rápidas de observação">
          <button
            class="br-button circle small secondary"
            type="button"
            :disabled="processando"
            @click="adicionarSugestaoAjuste('Revisar a redação da justificativa das ações por eixo.')"
          >
            Justificativa dos eixos
          </button>
          <button
            class="br-button circle small secondary"
            type="button"
            :disabled="processando"
            @click="adicionarSugestaoAjuste('Complementar os indicadores de produto e de resultado.')"
          >
            Indicadores
          </button>
          <button
            class="br-button circle small secondary"
            type="button"
            :disabled="processando"
            @click="adicionarSugestaoAjuste('Verificar dados de vigência e metas por ano.')"
          >
            Vigência e metas
          </button>
        </div>
        <div class="br-input mb-3">
          <label for="obs-ajustes">Observação (opcional)</label>
          <textarea
            id="obs-ajustes"
            v-model="observacaoAjuste"
            rows="4"
            :maxlength="LIMITE_OBSERVACAO"
            placeholder="Descreva os ajustes necessários..."
          ></textarea>
          <div class="modal-contador" :class="{ 'modal-contador--alerta': observacaoProximaDoLimite }">
            {{ observacaoAjuste.length }}/{{ LIMITE_OBSERVACAO }} caracteres
          </div>
        </div>
        <div class="modal-acoes">
          <button class="br-button secondary" type="button" @click="modalAjustes = false">Cancelar</button>
          <button class="br-button danger" type="button" :disabled="!podeConfirmarAjustes" @click="confirmarAjustes">
            <i v-if="processando" class="fas fa-spinner fa-spin" aria-hidden="true"></i>
            <i v-else class="fas fa-undo" aria-hidden="true"></i>
            {{ processando ? 'Enviando...' : 'Confirmar solicitação' }}
          </button>
        </div>
      </div>
    </div>
  </DefaultLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import HeaderPage from '@/core/components/HeaderPage/HeaderPage.vue'
import Card from '@/core/components/Card/Card.vue'
import { useNotification } from '@/core/composables/useNotification'
import {
  aprovarPlanoGestao,
  detalharPlanoGestao,
  solicitarAjustesPlanoGestao,
} from '@/features/plano-acao/services/PlanoAcaoGestaoService'
import type { PlanoAcaoGestaoDetalhe } from '@/features/plano-acao/types/PlanoAcaoGestaoInterface'

defineOptions({ name: 'DetalharPlanoGestaoPage' })

const route = useRoute()
const router = useRouter()
const { success, error } = useNotification()

const carregando = ref(true)
const processando = ref(false)
const plano = ref<PlanoAcaoGestaoDetalhe | null>(null)
const modalAprovar = ref(false)
const modalAjustes = ref(false)
const observacaoAjuste = ref('')
const LIMITE_OBSERVACAO = 4000

const observacaoProximaDoLimite = computed(() => observacaoAjuste.value.length >= 3800)
const podeConfirmarAjustes = computed(() => !processando.value && observacaoAjuste.value.length <= LIMITE_OBSERVACAO)

onMounted(async () => {
  const id = Number(route.params.id)
  if (!id) { carregando.value = false; return }
  try {
    plano.value = await detalharPlanoGestao(id)
  } catch {
    error('Não foi possível carregar os dados do plano de ação.')
  } finally {
    carregando.value = false
  }
})

const cicloVida = [
  { status: 'rascunho',              label: 'Rascunho' },
  { status: 'em_analise',            label: 'Análise documental e Análise técnica' },
  { status: 'em_ajustes',            label: 'Em ajustes' },
  { status: 'sei_abertura_processo', label: 'SEI — Abertura de processo' },
  { status: 'acordo_adesao',         label: 'Acordo / Termo de Adesão' },
  { status: 'despacho',              label: 'Despacho' },
  { status: 'extrato',               label: 'Extrato' },
  { status: 'publicacao_dou',        label: 'Publicação — DOU' },
  { status: 'conclusao',             label: 'Conclusão' },
]

const statusParaIndice: Record<string, number> = {
  rascunho: 0,
  enviado_para_analise: 1,
  em_analise: 1,
  em_analise_comissao_tecnica: 1,
  devolvido_ajustes: 2,
  em_ajustes: 2,
  sei_abertura_processo: 3,
  acordo_adesao: 4,
  despacho: 5,
  extrato: 6,
  publicacao_dou: 7,
  aprovado: 8,
  conclusao: 8,
}

const etapaIndice = computed(() => statusParaIndice[plano.value?.status ?? ''] ?? 0)

const statusQuePermitemAprovar = ['em_analise', 'enviado_para_analise', 'em_analise_comissao_tecnica']
const podeAprovar = computed(() =>
  plano.value ? statusQuePermitemAprovar.includes(plano.value.status) : false,
)

const nomesEixo: Record<number, string> = {
  1: 'Eixo 1 — Gestão e Participação Social',
  2: 'Eixo 2 — Enfrentamento ao Capacitismo e à Violência',
  3: 'Eixo 3 — Acessibilidade e Tecnologia Assistiva',
  4: 'Eixo 4 — Promoção de Direitos',
}

function nomeEixo(n: 1 | 2 | 3 | 4): string { return nomesEixo[n] ?? '' }

function justificativaEixo(n: 1 | 2 | 3 | 4): string | null {
  if (!plano.value) return null
  return plano.value[`justificativa_eixo_${n}` as keyof PlanoAcaoGestaoDetalhe] as string | null
}

const barreirasColunas = computed(() => {
  if (!plano.value?.diagnostico) return []
  const d = plano.value.diagnostico
  return [
    { campo: 'urbanisticas',   titulo: 'Barreiras urbanísticas',                  valores: d.barreiras_urbanisticas },
    { campo: 'arquitetonicas', titulo: 'Barreiras arquitetônicas',                valores: d.barreiras_arquitetonicas },
    { campo: 'transportes',    titulo: 'Barreiras nos transportes',               valores: d.barreiras_transportes },
    { campo: 'comunicacoes',   titulo: 'Barreiras nas comunicações e informação', valores: d.barreiras_comunicacoes },
    { campo: 'atitudinais',    titulo: 'Barreiras atitudinais',                   valores: d.barreiras_atitudinais },
    { campo: 'tecnologicas',   titulo: 'Barreiras tecnológicas',                  valores: d.barreiras_tecnologicas },
  ]
})

function abrirModalAprovar() { modalAprovar.value = true }
function abrirModalAjustes() { modalAjustes.value = true; observacaoAjuste.value = '' }

function adicionarSugestaoAjuste(texto: string) {
  const linha = `- ${texto}`
  if (!observacaoAjuste.value.trim()) {
    observacaoAjuste.value = linha
    return
  }

  const proximoValor = `${observacaoAjuste.value.trimEnd()}\n${linha}`
  observacaoAjuste.value = proximoValor.slice(0, LIMITE_OBSERVACAO)
}

async function confirmarAprovacao() {
  if (!plano.value) return
  processando.value = true
  try {
    await aprovarPlanoGestao(plano.value.id)
    plano.value.status = 'sei_abertura_processo'
    plano.value.status_label = 'SEI — Abertura de processo'
    success('Plano aprovado com sucesso.')
    modalAprovar.value = false
  } catch {
    error('Não foi possível aprovar o plano. Tente novamente.')
  } finally {
    processando.value = false
  }
}

async function confirmarAjustes() {
  if (!plano.value) return
  processando.value = true
  try {
    await solicitarAjustesPlanoGestao(plano.value.id, observacaoAjuste.value || undefined)
    plano.value.status = 'em_ajustes'
    plano.value.status_label = 'Em ajustes'
    success('Ajustes solicitados com sucesso.')
    modalAjustes.value = false
  } catch {
    error('Não foi possível solicitar os ajustes. Tente novamente.')
  } finally {
    processando.value = false
  }
}

function voltar() { router.push({ name: 'gestao-planos-acao' }) }

function formatarData(valor: string | null | undefined): string {
  if (!valor) return '—'
  const data = new Date(`${valor}T00:00:00`)
  if (Number.isNaN(data.getTime())) return '—'
  return data.toLocaleDateString('pt-BR')
}

function formatarBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function iconeAnexo(mime: string): string {
  if (mime.includes('pdf')) return 'fa-file-pdf'
  if (mime.includes('word') || mime.includes('document')) return 'fa-file-word'
  if (mime.includes('sheet') || mime.includes('excel')) return 'fa-file-excel'
  if (mime.includes('image')) return 'fa-file-image'
  return 'fa-file-alt'
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
.estado-resultado {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1.25rem 1rem;
  color: var(--color-secondary-08, #555);
}

.campo-label {
  display: block;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-secondary-06, #666);
  margin-bottom: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.campo-valor {
  margin: 0;
  color: var(--color-secondary-09, #111);
  font-size: 0.9375rem;
  line-height: 1.5;
}

.campo-texto-longo { white-space: pre-wrap; }

.campo-vazio {
  color: var(--color-secondary-05, #999);
  font-style: italic;
  margin: 0;
}

.lista-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  list-style: none;
  padding: 0;
  margin: 0;
}

.tag-item {
  background: #dbe8fb;
  color: #1351b4;
  border-radius: 1rem;
  padding: 0.15rem 0.65rem;
  font-size: 0.8125rem;
  font-weight: 500;
}

.badge-status {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.875rem;
  font-weight: 600;
}

.badge-azul    { background: #dbeafe; color: #1e40af; }
.badge-verde   { background: #dcfce7; color: #15803d; }
.badge-amarelo { background: #fef9c3; color: #854d0e; }
.badge-cinza   { background: #f3f4f6; color: #4b5563; }
.badge-roxo    { background: #ede9fe; color: #5b21b6; }

.ciclo-vida {
  display: flex;
  align-items: flex-start;
  flex-wrap: nowrap;
  overflow-x: auto;
  gap: 0;
  padding-bottom: 0.5rem;
}

.ciclo-etapa {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  flex: 1;
  min-width: 6.5rem;
}

.ciclo-etapa__icone {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  font-weight: 700;
  border: 2px solid #d1d5db;
  background: #fff;
  color: #9ca3af;
  z-index: 1;
  position: relative;
  flex-shrink: 0;
}

.ciclo-etapa__numero { font-size: 0.8125rem; font-weight: 700; }

.ciclo-etapa--concluida .ciclo-etapa__icone {
  background: var(--color-success, #168821);
  border-color: var(--color-success, #168821);
  color: #fff;
}

.ciclo-etapa--atual .ciclo-etapa__icone {
  background: var(--color-primary-default, #1351b4);
  border-color: var(--color-primary-default, #1351b4);
  color: #fff;
  box-shadow: 0 0 0 4px rgba(19, 81, 180, 0.18);
}

.ciclo-etapa--futura .ciclo-etapa__icone {
  background: #f3f4f6;
  border-color: #d1d5db;
  color: #9ca3af;
}

.ciclo-etapa__label {
  font-size: 0.6875rem;
  text-align: center;
  margin-top: 0.4rem;
  color: #444;
  line-height: 1.35;
  padding: 0 0.15rem;
}

.ciclo-etapa--atual .ciclo-etapa__label {
  font-weight: 700;
  color: var(--color-primary-default, #1351b4);
}

.ciclo-etapa__linha {
  position: absolute;
  top: 1.1rem;
  left: 50%;
  width: 100%;
  height: 2px;
  background: #d1d5db;
  z-index: 0;
}

.ciclo-etapa--concluida .ciclo-etapa__linha {
  background: var(--color-success, #168821);
}

.acao-detalhe { padding: 1rem 0 0.5rem; }

.acao-detalhe--sep {
  border-top: 1px solid #e5e5e5;
  margin-top: 1rem;
}

.acao-detalhe__titulo {
  font-weight: 700;
  font-size: 0.9375rem;
  color: var(--color-primary-default, #1351b4);
  margin-bottom: 0.75rem;
}

.justificativa-eixo {
  background: #fffbeb;
  border-left: 3px solid #d4a017;
  padding: 0.75rem 1rem;
  border-radius: 4px;
}

.tabela-inline { font-size: 0.875rem; }

.lista-anexos {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.anexo-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.5rem 0.75rem;
  background: #f8f8f8;
  border-radius: 6px;
  border: 1px solid #e5e5e5;
}

.anexo-item__icone {
  font-size: 1.1rem;
  color: var(--color-primary-default, #1351b4);
  flex-shrink: 0;
}

.anexo-item__nome {
  flex: 1;
  font-size: 0.875rem;
  word-break: break-all;
}

.anexo-item__tamanho {
  font-size: 0.75rem;
  color: #666;
  flex-shrink: 0;
}

.painel-acoes {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  align-items: center;
}

.painel-acoes__btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
}

.modal-box {
  background: #fff;
  border-radius: 8px;
  padding: 2rem;
  max-width: 34rem;
  width: 100%;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
}

.modal-titulo {
  font-size: 1.125rem;
  font-weight: 700;
  margin-bottom: 0.75rem;
}

.modal-desc {
  color: #555;
  margin-bottom: 1.25rem;
  font-size: 0.9375rem;
}

.modal-status-flow {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  flex-wrap: wrap;
}

.modal-status-chip {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.25rem 0.65rem;
  font-size: 0.75rem;
  font-weight: 600;
}

.modal-status-chip--atual {
  background: #e0ecff;
  color: #1f4ea3;
}

.modal-status-chip--destino {
  background: #fff4d6;
  color: #7a5200;
}

.modal-hint-box {
  background: #f8f8f8;
  border-left: 3px solid #1351b4;
  border-radius: 4px;
  padding: 0.6rem 0.75rem;
  margin-bottom: 0.9rem;
  color: #333;
  font-size: 0.875rem;
}

.modal-sugestoes {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.8rem;
}

.modal-contador {
  margin-top: 0.4rem;
  text-align: right;
  color: #666;
  font-size: 0.75rem;
}

.modal-contador--alerta {
  color: #9a3412;
  font-weight: 600;
}

.modal-acoes {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  margin-top: 1.25rem;
  flex-wrap: wrap;
}

@media (max-width: 575px) {
  .painel-acoes { flex-direction: column; }
  .painel-acoes__btn { width: 100%; justify-content: center; }
  .ciclo-etapa { min-width: 4.5rem; }
}
</style>
