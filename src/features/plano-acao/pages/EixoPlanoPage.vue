<template>
  <DefaultLayout>
    <HeaderPage
      :title="config.titulo"
      :subtitle="config.subtitulo"
      customClass="mb-4"
    >
      <template #actions>
        <button
          class="br-button secondary"
          type="button"
          @click="router.push('/enviar-plano-acao')"
        >
          <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
          Voltar para seções
        </button>
      </template>
    </HeaderPage>

    <form novalidate @submit.prevent>

      <!-- Card do eixo -->
      <Card :title="config.tituloCard" :subtitle="config.subtituloCard" custom-class="mb-4">

        <div class="eixo-toolbar">
          <p class="eixo-toolbar__info">Adicione quantas ações forem necessárias para este eixo.</p>
          <button
            class="br-button primary eixo-toolbar__btn"
            type="button"
            @click="adicionarAcao"
          >
            <i class="fas fa-plus" aria-hidden="true"></i>
            Adicionar ação
          </button>
        </div>
        <span class="br-divider my-3" aria-hidden="true"></span>

        <!-- Lista de ações -->
        <div
          v-for="(acao, index) in acoes"
          :key="acao.id"
          class="acao-item"
        >
          <!-- Cabeçalho da ação -->
          <div class="acao-item__header">
            <div>
              <span class="acao-item__numero">Ação {{ index + 1 }}</span>
              <span class="acao-item__nota">
                Campos variáveis podem usar placeholders como <code>[X]</code> e <code>[R$ XXX.XXX,00]</code>.
              </span>
            </div>
            <button
              class="br-button secondary small"
              type="button"
              :disabled="acoes.length === 1"
              :title="acoes.length === 1 ? 'Mantenha ao menos 1 ação cadastrada.' : 'Remover ação'"
              @click="removerAcao(index)"
            >
              <i class="fas fa-trash-alt" aria-hidden="true"></i>
              Remover ação
            </button>
          </div>

          <div class="row g-3 mt-1">

            <!-- Nome da ação -->
            <div class="col-12 col-md-6">
              <div class="br-input">
                <label :for="`nome-${acao.id}`">
                  Nome da ação
                  <span class="text-red-50 text-up-01"> *</span>
                </label>
                <input
                  :id="`nome-${acao.id}`"
                  type="text"
                  v-model="acao.nome"
                  maxlength="500"
                  placeholder="Ex.: Fortalecimento da Governança..."
                />
              </div>
            </div>

            <!-- Ação NVSL -->
            <div class="col-12 col-md-6">
              <div class="br-input">
                <label :for="`nvsl-${acao.id}`">
                  Ação correspondente no NVSL
                  <span class="text-red-50 text-up-01"> *</span>
                </label>
                <select
                  :id="`nvsl-${acao.id}`"
                  v-model="acao.acaoNvsl"
                  class="br-select-native"
                  @change="onAcaoNvslChange(acao)"
                >
                  <option value="">Selecione</option>
                  <option
                    v-for="nvslAcao in config.acoes"
                    :key="nvslAcao.label"
                    :value="nvslAcao.label"
                  >
                    {{ nvslAcao.label }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Campo texto para "Outras ações" -->
            <div v-if="acao.acaoNvsl === 'Outras ações'" class="col-12">
              <div class="br-input">
                <label :for="`nvsl-outra-${acao.id}`">Descrição da ação</label>
                <input
                  :id="`nvsl-outra-${acao.id}`"
                  type="text"
                  v-model="acao.acaoNvslOutra"
                  maxlength="500"
                  placeholder="Descreva a ação..."
                />
              </div>
            </div>

            <!-- Meta -->
            <div class="col-12">
              <div class="br-input">
                <label :for="`meta-${acao.id}`">
                  Meta
                  <span class="text-red-50 text-up-01"> *</span>
                </label>
                <textarea
                  :id="`meta-${acao.id}`"
                  v-model="acao.meta"
                  rows="3"
                  placeholder="Ex.: Estruturar e consolidar..."
                ></textarea>
              </div>
            </div>

            <!-- Metas por ano -->
            <div class="col-12">
              <div class="br-card nvsl-subcard">
                <p class="nvsl-subcard__titulo">Descrição da meta por ano</p>
                <div
                  v-for="(mp, mi) in acao.metasPorAno"
                  :key="mi"
                  class="meta-ano-row"
                >
                  <div class="meta-ano-row__fields">
                    <div class="br-input meta-ano-row__ano">
                      <label :for="`ano-${acao.id}-${mi}`">Ano</label>
                      <input
                        :id="`ano-${acao.id}-${mi}`"
                        type="text"
                        v-model="mp.ano"
                        maxlength="10"
                      />
                    </div>
                    <div class="br-input meta-ano-row__desc">
                      <label :for="`desc-${acao.id}-${mi}`">Descrição</label>
                      <textarea
                        :id="`desc-${acao.id}-${mi}`"
                        v-model="mp.descricao"
                        rows="3"
                        placeholder="Ex.: [Instituir comitê intersetorial...]"
                      ></textarea>
                    </div>
                  </div>
                  <div class="meta-ano-row__actions">
                    <button
                      class="br-button secondary small"
                      type="button"
                      @click="acao.metasPorAno.splice(mi, 1)"
                    >
                      Remover ano
                    </button>
                  </div>
                </div>
                <div class="mt-3">
                  <button
                    class="br-button secondary small"
                    type="button"
                    @click="adicionarMetaAno(acao)"
                  >
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    Adicionar ano
                  </button>
                </div>
              </div>
            </div>

            <!-- Órgão responsável local -->
            <div class="col-12 col-md-6">
              <label class="br-label">Órgão responsável local</label>
              <div class="orgao-input-row mt-1">
                <div class="br-input" style="flex:1; margin-bottom:0">
                  <input
                    type="text"
                    v-model="acao.novoOrgaoLocal"
                    placeholder="Digite o órgão e clique em Adicionar"
                    @keydown.enter.prevent="adicionarOrgaoLocal(acao)"
                  />
                </div>
                <button
                  class="br-button primary"
                  type="button"
                  @click="adicionarOrgaoLocal(acao)"
                >
                  Adicionar
                </button>
              </div>
              <ul
                v-if="acao.orgaosLocais.length"
                class="orgao-lista mt-2"
                aria-label="Órgãos locais adicionados"
              >
                <li
                  v-for="(o, i) in acao.orgaosLocais"
                  :key="i"
                  class="orgao-lista__item"
                >
                  <span>{{ o }}</span>
                  <button
                    class="orgao-lista__remover"
                    type="button"
                    :aria-label="`Remover ${o}`"
                    @click="acao.orgaosLocais.splice(i, 1)"
                  >
                    <i class="fas fa-times" aria-hidden="true"></i>
                  </button>
                </li>
              </ul>
            </div>

            <!-- Órgão federal vinculado -->
            <div class="col-12 col-md-6">
              <label class="br-label">Órgão federal vinculado</label>
              <div class="orgao-input-row mt-1">
                <div class="br-input" style="flex:1; margin-bottom:0">
                  <input
                    type="text"
                    v-model="acao.novoOrgaoFederal"
                    placeholder="Digite o órgão federal e clique em Adicionar"
                    @keydown.enter.prevent="adicionarOrgaoFederal(acao)"
                  />
                </div>
                <button
                  class="br-button primary"
                  type="button"
                  @click="adicionarOrgaoFederal(acao)"
                >
                  Adicionar
                </button>
              </div>
              <ul
                v-if="acao.orgaosFederais.length"
                class="orgao-lista mt-2"
                aria-label="Órgãos federais adicionados"
              >
                <li
                  v-for="(o, i) in acao.orgaosFederais"
                  :key="i"
                  class="orgao-lista__item orgao-lista__item--federal"
                >
                  <span>{{ o }}</span>
                  <button
                    class="orgao-lista__remover"
                    type="button"
                    :aria-label="`Remover ${o}`"
                    @click="acao.orgaosFederais.splice(i, 1)"
                  >
                    <i class="fas fa-times" aria-hidden="true"></i>
                  </button>
                </li>
              </ul>
            </div>

            <!-- Indicadores -->
            <div class="col-12 col-md-6">
              <div class="br-card nvsl-subcard nvsl-subcard--indicador">
                <p class="nvsl-subcard__titulo">Indicadores previstos — <strong>Produto</strong></p>
                <p class="nvsl-subcard__desc">
                  São formas de medir o que foi efetivamente
                  <em>entregue</em> ou <em>realizado</em>
                  por uma política pública ou projeto.
                </p>
                <div class="br-input">
                  <textarea
                    v-model="acao.indicadorProduto"
                    rows="4"
                    placeholder="Por exemplo, na educação, um indicador de produto pode ser quantas rampas foram construídas nas escolas; na saúde, quantos exames acessíveis foram realizados; e no transporte, quantos ônibus adaptados foram disponibilizados para pessoas com deficiência."
                  ></textarea>
                </div>
              </div>
            </div>

            <div class="col-12 col-md-6">
              <div class="br-card nvsl-subcard nvsl-subcard--indicador">
                <p class="nvsl-subcard__titulo">Indicadores previstos — <strong>Resultado</strong></p>
                <p class="nvsl-subcard__desc">
                  São formas de medir se os objetivos do plano de ação estão realmente
                  <em>beneficiando pessoas com deficiência</em> e
                  melhorando sua <em>inclusão na sociedade</em>.
                </p>
                <div class="br-input">
                  <textarea
                    v-model="acao.indicadorResultado"
                    rows="4"
                    placeholder="Por exemplo, na educação, um indicador pode ser quantos alunos com deficiência concluíram a escola regular; na saúde, quantas pessoas com deficiência receberam atendimento acessível; e no transporte, quantas têm acesso a ônibus, metrôs ou calçadas adaptadas."
                  ></textarea>
                </div>
              </div>
            </div>

            <!-- Vigência -->
            <div class="col-12">
              <div class="br-input">
                <label :for="`vigencia-${acao.id}`">Vigência / Ano da entrega</label>
                <input
                  :id="`vigencia-${acao.id}`"
                  type="text"
                  v-model="acao.vigencia"
                  placeholder="2026–2028"
                  maxlength="100"
                />
              </div>
            </div>

            <!-- Orçamento e Fonte de recursos -->
            <div class="col-12">
              <div class="br-card nvsl-subcard">
                <div class="nvsl-subcard__header">
                  <p class="nvsl-subcard__titulo mb-0">Orçamento <em>estimado</em> e <strong>Fonte de recursos</strong></p>
                  <button
                    class="br-button secondary small"
                    type="button"
                    @click="adicionarOrcamento(acao)"
                  >
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    Adicionar recurso
                  </button>
                </div>
                <div
                  v-for="(orc, oi) in acao.orcamentos"
                  :key="oi"
                  class="orcamento-item"
                >
                  <div class="row g-2">
                    <div class="col-12 col-md-5">
                      <div class="br-input">
                        <label :for="`orc-est-${acao.id}-${oi}`">Orçamento estimado</label>
                        <input
                          :id="`orc-est-${acao.id}-${oi}`"
                          type="text"
                          v-model="orc.orcamentoEstimado"
                          placeholder="R$ XXX.XXX,00"
                        />
                      </div>
                    </div>
                    <div class="col-12 col-md-7">
                      <div class="br-input">
                        <label :for="`orc-fonte-${acao.id}-${oi}`">Fonte de recursos</label>
                        <input
                          :id="`orc-fonte-${acao.id}-${oi}`"
                          type="text"
                          v-model="orc.fonteRecurso"
                          placeholder="Orçamento municipal ou emenda parlamentar"
                        />
                      </div>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                      <button
                        class="br-button secondary small"
                        type="button"
                        @click="acao.orcamentos.splice(oi, 1)"
                      >
                        Remover recurso
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>


        </div>

        <!-- Vazio -->
        <div v-if="acoes.length === 0" class="eixo-vazio">
          <i class="fas fa-inbox fa-2x mb-3" aria-hidden="true"></i>
          <p>Nenhuma ação cadastrada. Clique em "<strong>Adicionar ação</strong>" para começar.</p>
        </div>

      </Card>

      <!-- Barra de ações -->
      <div class="painel-acoes">
        <button
          class="br-button secondary painel-acoes__btn painel-acoes__btn--com-icone"
          type="button"
          @click="router.push('/enviar-plano-acao')"
        >
          <i class="fas fa-arrow-left" aria-hidden="true"></i>
          <span>Voltar</span>
        </button>
        <button
          class="br-button primary painel-acoes__btn painel-acoes__btn--com-icone"
          type="button"
          :disabled="salvando"
          @click="salvarRascunho"
        >
          <i v-if="!salvando" class="fas fa-save" aria-hidden="true"></i>
          <i v-else class="fas fa-spinner fa-spin" aria-hidden="true"></i>
          <span>{{ salvando ? 'Salvando...' : 'Salvar Rascunho' }}</span>
        </button>
      </div>

    </form>
  </DefaultLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import HeaderPage from '@/core/components/HeaderPage/HeaderPage.vue'
import { obterEixo, salvarEixo } from '@/features/plano-acao/services/PlanoAcaoEixoService'
import { useNotification } from '@/core/composables/useNotification'
import Card from '@/core/components/Card/Card.vue'

defineOptions({ name: 'EixoPlanoPage' })

const router = useRouter()
const route = useRoute()
const { success: notifySuccess, error: notifyError } = useNotification()

// ── Config por eixo ──────────────────────────────────────────────────────────

interface NvslAcao {
  label: string
  orgaosFederais: string[]
}

interface EixoConfig {
  titulo: string
  subtitulo: string
  tituloCard: string
  subtituloCard: string
  acoes: NvslAcao[]
}

const CONFIGS: Record<number, EixoConfig> = {
  1: {
    titulo: 'Eixo 1 – Gestão e Participação Social',
    subtitulo: 'Cadastre as ações voltadas à gestão pública e participação social.',
    tituloCard: 'Eixo 1 – Gestão e Participação Social',
    subtituloCard: 'Aprimorar a gestão pública para garantir plena participação e exercício da cidadania das pessoas com deficiência.',
    acoes: [
      { label: 'Criação ou fortalecimento do Conselho Municipal dos Direitos da Pessoa com Deficiência', orgaosFederais: ['SNPD/MDH'] },
      { label: 'Elaboração ou revisão do Plano Municipal de Direitos da Pessoa com Deficiência', orgaosFederais: ['SNPD/MDH'] },
      { label: 'Realização de diagnóstico municipal sobre a situação das pessoas com deficiência', orgaosFederais: ['SNPD/MDH', 'IBGE'] },
      { label: 'Capacitação de gestores e servidores públicos sobre direitos da pessoa com deficiência', orgaosFederais: ['SNPD/MDH', 'ENAP'] },
      { label: 'Articulação intersetorial para políticas de inclusão', orgaosFederais: ['SNPD/MDH', 'MDS'] },
      { label: 'Participação em redes e fóruns nacionais e regionais de pessoas com deficiência', orgaosFederais: ['SNPD/MDH'] },
      { label: 'Implementação de serviços de informação e orientação sobre direitos', orgaosFederais: ['SNPD/MDH'] },
      { label: 'Criação de canal de atendimento prioritário para pessoas com deficiência', orgaosFederais: ['SNPD/MDH'] },
      { label: 'Realização de conferências ou audiências públicas sobre direitos da pessoa com deficiência', orgaosFederais: ['SNPD/MDH'] },
      { label: 'Desenvolvimento de legislação ou normativas municipais de acessibilidade e inclusão', orgaosFederais: ['SNPD/MDH'] },
      { label: 'Outras ações', orgaosFederais: [] },
    ],
  },
  2: {
    titulo: 'Eixo 2 – Enfrentamento ao Capacitismo e à Violência',
    subtitulo: 'Cadastre as ações voltadas ao enfrentamento do capacitismo e da violência.',
    tituloCard: 'Eixo 2 – Enfrentamento ao Capacitismo e à Violência',
    subtituloCard: 'Enfrentar a violência contra pessoas com deficiência e o capacitismo.',
    acoes: [
      { label: 'Capacitação de profissionais da rede de proteção sobre capacitismo e violência', orgaosFederais: ['SNPD/MDH', 'MMFDH'] },
      { label: 'Implementação de serviço de proteção a pessoas com deficiência em situação de violência', orgaosFederais: ['SNPD/MDH', 'MMFDH'] },
      { label: 'Desenvolvimento de campanhas de conscientização e combate ao capacitismo', orgaosFederais: ['SNPD/MDH'] },
      { label: 'Criação de protocolos de atendimento humanizado para pessoas com deficiência', orgaosFederais: ['SNPD/MDH', 'MS'] },
      { label: 'Fortalecimento da rede de proteção à criança e ao adolescente com deficiência', orgaosFederais: ['SNPD/MDH', 'MMFDH', 'MDS'] },
      { label: 'Promoção de formação continuada sobre direitos e proteção da pessoa com deficiência', orgaosFederais: ['SNPD/MDH'] },
      { label: 'Inclusão da temática capacitismo na formação de profissionais de segurança pública', orgaosFederais: ['SNPD/MDH', 'MJSP'] },
      { label: 'Implementação de medidas de proteção para mulheres com deficiência em situação de violência', orgaosFederais: ['SNPD/MDH', 'MMFDH'] },
      { label: 'Outras ações', orgaosFederais: [] },
    ],
  },
  3: {
    titulo: 'Eixo 3 – Acessibilidade e Tecnologia Assistiva',
    subtitulo: 'Cadastre as ações voltadas à acessibilidade e ao uso de tecnologias assistivas.',
    tituloCard: 'Eixo 3 – Acessibilidade e Tecnologia Assistiva',
    subtituloCard: 'Ampliar o acesso a tecnologias assistivas e eliminar barreiras, promovendo autonomia e inclusão das pessoas com deficiência.',
    acoes: [
      { label: 'Implementação de rotas acessíveis em espaços públicos', orgaosFederais: ['SNPD/MDH', 'MCidades'] },
      { label: 'Adequação arquitetônica de prédios públicos municipais', orgaosFederais: ['SNPD/MDH', 'MCidades'] },
      { label: 'Criação ou fortalecimento de serviço de empréstimo de tecnologia assistiva', orgaosFederais: ['SNPD/MDH', 'MS'] },
      { label: 'Capacitação sobre uso e manutenção de tecnologias assistivas', orgaosFederais: ['SNPD/MDH', 'MEC'] },
      { label: 'Implementação de transporte coletivo acessível', orgaosFederais: ['SNPD/MDH', 'MT'] },
      { label: 'Promoção de acessibilidade digital em sistemas e portais municipais', orgaosFederais: ['SNPD/MDH', 'MGI'] },
      { label: 'Adaptação de sinalização urbana (tátil, sonora e visual)', orgaosFederais: ['SNPD/MDH', 'MCidades'] },
      { label: 'Aquisição e distribuição de equipamentos de tecnologia assistiva', orgaosFederais: ['SNPD/MDH', 'MS'] },
      { label: 'Implementação de programa de acessibilidade nas escolas municipais', orgaosFederais: ['SNPD/MDH', 'MEC'] },
      { label: 'Outras ações', orgaosFederais: [] },
    ],
  },
  4: {
    titulo: 'Eixo 4 – Promoção de Direitos (Educação, Saúde, Trabalho e Assistência Social)',
    subtitulo: 'Cadastre as ações voltadas à promoção de direitos nas áreas de educação, saúde, trabalho e assistência social.',
    tituloCard: 'Eixo 4 – Promoção de Direitos (Educação, Saúde, Trabalho e Assistência Social)',
    subtituloCard: 'Promover e garantir o acesso aos direitos fundamentais das pessoas com deficiência nas áreas de educação, saúde, trabalho e assistência social.',
    acoes: [
      { label: 'Implementação de educação inclusiva nas redes municipais de ensino', orgaosFederais: ['SNPD/MDH', 'MEC'] },
      { label: 'Capacitação de professores para o Atendimento Educacional Especializado (AEE)', orgaosFederais: ['SNPD/MDH', 'MEC'] },
      { label: 'Implementação de salas de recursos multifuncionais', orgaosFederais: ['SNPD/MDH', 'MEC'] },
      { label: 'Ampliação do acesso a serviços de saúde para pessoas com deficiência', orgaosFederais: ['SNPD/MDH', 'MS'] },
      { label: 'Implementação de programas de reabilitação e habilitação', orgaosFederais: ['SNPD/MDH', 'MS'] },
      { label: 'Promoção de políticas de inclusão no mercado de trabalho', orgaosFederais: ['SNPD/MDH', 'MTE'] },
      { label: 'Implementação de programa de qualificação profissional para pessoas com deficiência', orgaosFederais: ['SNPD/MDH', 'MTE'] },
      { label: 'Fortalecimento dos serviços de assistência social para pessoas com deficiência', orgaosFederais: ['SNPD/MDH', 'MDS'] },
      { label: 'Apoio a programas de transferência de renda para pessoas com deficiência', orgaosFederais: ['SNPD/MDH', 'MDS'] },
      { label: 'Promoção de acesso à cultura, esporte e lazer para pessoas com deficiência', orgaosFederais: ['SNPD/MDH', 'MinC', 'ME'] },
      { label: 'Outras ações', orgaosFederais: [] },
    ],
  },
}

const eixoNumero = Number(route.meta.eixo ?? 1)
const config = (CONFIGS[eixoNumero] ?? CONFIGS[1]) as EixoConfig

// ── Tipos de formulário ──────────────────────────────────────────────────────

interface MetaAnoForm {
  ano: string
  descricao: string
}

interface OrcamentoForm {
  orcamentoEstimado: string
  fonteRecurso: string
}

interface AcaoForm {
  id: string
  nome: string
  acaoNvsl: string
  acaoNvslOutra: string
  meta: string
  metasPorAno: MetaAnoForm[]
  orgaosLocais: string[]
  novoOrgaoLocal: string
  orgaosFederais: string[]
  novoOrgaoFederal: string
  indicadorProduto: string
  indicadorResultado: string
  vigencia: string
  orcamentos: OrcamentoForm[]
}

// ── Auxiliares ───────────────────────────────────────────────────────────────

function gerarId(): string {
  return String(Date.now()) + String(Math.random()).slice(2, 8)
}

function anoAtual(): string {
  return String(new Date().getFullYear())
}

function criarNovaAcao(): AcaoForm {
  return {
    id: gerarId(),
    nome: '',
    acaoNvsl: '',
    acaoNvslOutra: '',
    meta: '',
    metasPorAno: [{ ano: anoAtual(), descricao: '' }],
    orgaosLocais: [],
    novoOrgaoLocal: '',
    orgaosFederais: [],
    novoOrgaoFederal: '',
    indicadorProduto: '',
    indicadorResultado: '',
    vigencia: '',
    orcamentos: [{ orcamentoEstimado: '', fonteRecurso: '' }],
  }
}

// ── Estado ───────────────────────────────────────────────────────────────────

const inicial: AcaoForm[] = [criarNovaAcao()]
const acoes = ref(inicial)
const salvando = ref(false)

// ── Handlers ─────────────────────────────────────────────────────────────────

function adicionarAcao(): void {
  acoes.value.push(criarNovaAcao())
}

function removerAcao(index: number): void {
  acoes.value.splice(index, 1)
}

function onAcaoNvslChange(acao: AcaoForm): void {
  acao.acaoNvslOutra = ''
  if (!acao.acaoNvsl || acao.acaoNvsl === 'Outras ações') {
    return
  }
  const nvslAcao = config.acoes.find(a => a.label === acao.acaoNvsl)
  if (nvslAcao) {
    acao.orgaosFederais = [...nvslAcao.orgaosFederais]
  }
}

function adicionarMetaAno(acao: AcaoForm): void {
  acao.metasPorAno.push({ ano: anoAtual(), descricao: '' })
}

function adicionarOrgaoLocal(acao: AcaoForm): void {
  const val = acao.novoOrgaoLocal.trim()
  if (val && !acao.orgaosLocais.includes(val)) {
    acao.orgaosLocais.push(val)
  }
  acao.novoOrgaoLocal = ''
}

function adicionarOrgaoFederal(acao: AcaoForm): void {
  const val = acao.novoOrgaoFederal.trim()
  if (val && !acao.orgaosFederais.includes(val)) {
    acao.orgaosFederais.push(val)
  }
  acao.novoOrgaoFederal = ''
}

function adicionarOrcamento(acao: AcaoForm): void {
  acao.orcamentos.push({ orcamentoEstimado: '', fonteRecurso: '' })
}

// ── Serialização ─────────────────────────────────────────────────────────────

interface AcaoPayload {
  id: string
  nome: string
  acao_nvsl: string
  acao_nvsl_outra: string
  meta: string
  metas_por_ano: MetaAnoForm[]
  orgaos_locais: string[]
  orgaos_federais: string[]
  indicador_produto: string
  indicador_resultado: string
  vigencia: string
  orcamentos: { orcamento_estimado: string; fonte_recurso: string }[]
}

function toPayload(acao: AcaoForm): AcaoPayload {
  return {
    id: acao.id,
    nome: acao.nome,
    acao_nvsl: acao.acaoNvsl,
    acao_nvsl_outra: acao.acaoNvslOutra,
    meta: acao.meta,
    metas_por_ano: acao.metasPorAno,
    orgaos_locais: acao.orgaosLocais,
    orgaos_federais: acao.orgaosFederais,
    indicador_produto: acao.indicadorProduto,
    indicador_resultado: acao.indicadorResultado,
    vigencia: acao.vigencia,
    orcamentos: acao.orcamentos.map(o => ({
      orcamento_estimado: o.orcamentoEstimado,
      fonte_recurso: o.fonteRecurso,
    })),
  }
}

function fromPayload(p: AcaoPayload): AcaoForm {
  return {
    id: p.id || gerarId(),
    nome: p.nome ?? '',
    acaoNvsl: p.acao_nvsl ?? '',
    acaoNvslOutra: p.acao_nvsl_outra ?? '',
    meta: p.meta ?? '',
    metasPorAno: p.metas_por_ano?.length
      ? p.metas_por_ano
      : [{ ano: anoAtual(), descricao: '' }],
    orgaosLocais: p.orgaos_locais ?? [],
    novoOrgaoLocal: '',
    orgaosFederais: p.orgaos_federais ?? [],
    novoOrgaoFederal: '',
    indicadorProduto: p.indicador_produto ?? '',
    indicadorResultado: p.indicador_resultado ?? '',
    vigencia: p.vigencia ?? '',
    orcamentos: p.orcamentos?.length
      ? p.orcamentos.map(o => ({ orcamentoEstimado: o.orcamento_estimado, fonteRecurso: o.fonte_recurso }))
      : [{ orcamentoEstimado: '', fonteRecurso: '' }],
  }
}

// ── Salvar / carregar ─────────────────────────────────────────────────────────

async function salvarRascunho(): Promise<void> {
  const erros = validarFormulario()
  if (erros.length) {
    notifyError(erros[0] ?? 'Revise os dados preenchidos e tente novamente.')
    return
  }

  salvando.value = true
  try {
    await salvarEixo(eixoNumero, { acoes: acoes.value.map(toPayload) })
    notifySuccess('Rascunho salvo com sucesso!')
  } catch (err: unknown) {
    const apiErr = err as { response?: { data?: { message?: string } } }
    notifyError(apiErr?.response?.data?.message ?? 'Ocorreu um erro ao salvar. Tente novamente.')
  } finally {
    salvando.value = false
  }
}

onMounted(async () => {
  try {
    const dados = await obterEixo(eixoNumero)
    if (dados?.acoes?.length) {
      acoes.value = dados.acoes.map(a => fromPayload(a as unknown as AcaoPayload))
    }
  } catch {
    // sem dados — manter ação inicial
  }
})

function validarFormulario(): string[] {
  const erros: string[] = []

  if (!acoes.value.length) {
    erros.push('Adicione ao menos 1 ação para salvar o eixo.')
    return erros
  }

  for (const [i, acao] of acoes.value.entries()) {
    const indice = i + 1

    if (!acao.nome.trim()) {
      erros.push(`Preencha o nome da ação ${indice}.`)
      break
    }

    if (!acao.acaoNvsl.trim()) {
      erros.push(`Selecione a ação NVSL da ação ${indice}.`)
      break
    }

    if (acao.acaoNvsl === 'Outras ações' && !acao.acaoNvslOutra.trim()) {
      erros.push(`Descreva a ação NVSL "Outras ações" da ação ${indice}.`)
      break
    }

    if (!acao.meta.trim()) {
      erros.push(`Preencha a meta da ação ${indice}.`)
      break
    }
  }

  return erros
}
</script>

<style scoped>
/* ── Textareas: preencher espaço disponível ──────────────────────────────── */
.br-input textarea {
  width: 100%;
  box-sizing: border-box;
  resize: vertical;
  min-height: 4.5rem;
}

/* ── Espaçamento entre campos ────────────────────────────────────── */
.acao-item .row > [class*='col'] {
  margin-bottom: 0.5rem;
}

/* ── Select nativo padrão Gov.br DS ─────────────────────────────── */
.br-select-native {
  width: 100%;
  min-height: 2.5rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-family: inherit;
  border: 1px solid var(--color-secondary-05, #bbb);
  border-radius: 6px;
  outline: none;
  background: #fff;
  cursor: pointer;
  appearance: auto;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.br-select-native:focus {
  border-color: var(--color-primary-default, #1351b4);
  box-shadow: 0 0 0 3px rgba(19, 81, 180, 0.12);
}

/* ── Toolbar do eixo ─────────────────────────────────────────────────────── */
.eixo-toolbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.eixo-toolbar__info {
  margin: 0;
  font-size: 0.875rem;
  color: var(--secondary-text-color);
  line-height: 1.5;
  flex: 1;
}

.eixo-toolbar__btn {
  flex-shrink: 0;
  white-space: nowrap;
}

/* ── Sub-cards internos (meta por ano, indicadores, orçamento) ─────────── */
.nvsl-subcard {
  padding: 1rem 1.125rem;
  border-radius: 6px;
  border: 1px solid var(--color-secondary-03, #e8e8e8);
  background: var(--gray-2, #f8f9fb);
}

.nvsl-subcard__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-bottom: 0.75rem;
}

.nvsl-subcard__titulo {
  margin: 0 0 0.625rem;
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--primary-text-color, #1c1c1e);
  line-height: 1.3;
}

.nvsl-subcard__titulo.mb-0 {
  margin-bottom: 0;
}

.nvsl-subcard__desc {
  margin: 0 0 0.625rem;
  font-size: 0.8125rem;
  color: var(--secondary-text-color, #555);
  line-height: 1.45;
}

.nvsl-subcard--indicador {
  height: 100%;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
}

.nvsl-subcard--indicador .br-input {
  flex: 1;
  display: flex;
  flex-direction: column;
  margin-bottom: 0;
}

.nvsl-subcard--indicador .br-input textarea {
  flex: 1;
  min-height: 8rem;
}

/* ── Card de cada ação ──────────────────────────────────────────────────── */
.acao-item {
  padding: 1.25rem;
  background: #f9fbff;
  border: 1px solid #dde4ee;
  border-radius: 8px;
  margin-top: 0.875rem;
}

.acao-item:first-child {
  margin-top: 0.25rem;
}

.acao-item__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  padding-bottom: 0.875rem;
  border-bottom: 2px solid #c5d4f0;
  margin-bottom: 0.75rem;
}

.acao-item__numero {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1351b4;
  display: block;
  margin-bottom: 0.15rem;
}

.acao-item__nota {
  font-size: 0.8rem;
  color: #636363;
  display: block;
}

.acao-item__nota code {
  background: #f0f0f0;
  padding: 0.05rem 0.3rem;
  border-radius: 3px;
  font-size: 0.8rem;
}

/* ── Metas por ano ───────────────────────────────────────────────────────── */
.meta-ano-row {
  margin-bottom: 0.75rem;
}

.meta-ano-row + .meta-ano-row {
  border-top: 1px solid var(--color-secondary-03, #e8e8e8);
  padding-top: 0.75rem;
}

.meta-ano-row__fields {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
}

.meta-ano-row__ano {
  width: 120px;
  flex-shrink: 0;
  margin-bottom: 0 !important;
}

.meta-ano-row__desc {
  flex: 1;
  margin-bottom: 0 !important;
}

.meta-ano-row__actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 0.5rem;
}

/* ── Órgãos (chips) ────────────────────────────────────────────────────────── */
.orgao-input-row {
  display: flex;
  gap: 0.75rem;
  align-items: flex-end;
}

.orgao-input-row .br-button {
  white-space: nowrap;
  flex-shrink: 0;
}

.orgao-lista {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.orgao-lista__item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: #e8f0fb;
  border: 1px solid #1351b4;
  border-radius: 20px;
  padding: 0.25rem 0.75rem;
  font-size: 0.875rem;
  color: #1351b4;
}

.orgao-lista__item--federal {
  background: #eaf4ea;
  border-color: #168821;
  color: #168821;
}

.orgao-lista__remover {
  background: none;
  border: none;
  cursor: pointer;
  color: inherit;
  padding: 0;
  line-height: 1;
  font-size: 0.875rem;
  opacity: 0.7;
}

.orgao-lista__remover:hover {
  opacity: 1;
  color: #c0345b;
}



.orcamento-item {
  border-top: 1px solid #e4e7ee;
  padding-top: 0.75rem;
  margin-top: 0.75rem;
}

.orcamento-item:first-of-type {
  border-top: none;
  padding-top: 0;
  margin-top: 0;
}

/* ── Estado vazio ────────────────────────────────────────────────────────── */
.eixo-vazio {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2.5rem 1rem;
  color: #888;
  text-align: center;
}

/* ── Painel de ações (padrão do projeto) ─────────────────────────────────── */
.painel-acoes {
  display: flex;
  flex-direction: column-reverse;
  gap: 0.75rem;
  padding: 1.25rem 0 0;
  border-top: 1px solid var(--color-secondary-03, #e8e8e8);
}

.painel-acoes__btn {
  width: 100%;
  min-height: 2.75rem;
  justify-content: center;
}

@media (min-width: 576px) {
  .painel-acoes {
    flex-direction: row;
    justify-content: flex-end;
    align-items: center;
  }

  .painel-acoes__btn {
    width: auto;
    min-width: 10rem;
  }
}

.painel-acoes__btn--com-icone {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
}

.painel-acoes__btn--com-icone i {
  font-size: 0.875rem;
}

/* ── Responsividade mobile ───────────────────────────────────────────────── */
@media (max-width: 575px) {
  .eixo-toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .meta-ano-row__fields {
    flex-direction: column;
  }

  .meta-ano-row__ano {
    width: 100%;
  }

  .orgao-input-row {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
