<template>
  <DefaultLayout>
    <HeaderPage
      title="Envio do plano"
      subtitle="Responsável técnico pelo preenchimento do plano."
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

    <!-- Aviso: plano enviado (somente leitura) -->
    <div v-if="enviado" class="aviso-enviado mb-4" role="alert">
      <i class="fas fa-check-circle" aria-hidden="true"></i>
      <div>
        <strong>Plano enviado para análise</strong>
        <p>
          Enviado em {{ formatarData(enviadoEm) }}. O plano está em <em>Análise documental e Análise técnica</em>.
          Acompanhe na tela <strong>Gestão de Planos de Ação</strong>.
        </p>
      </div>
    </div>

    <form novalidate @submit.prevent>

      <!-- Responsável -->
      <Card
        title="Responsável técnico pelo preenchimento do plano"
        subtitle="Dados pré-preenchidos do perfil logado. Edite se necessário."
        custom-class="mb-4"
      >
        <fieldset :disabled="enviado" class="row g-3 responsavel-fieldset">
          <div class="col-12 col-md-6">
            <div class="br-input" :class="{ danger: erros.nome }">
              <label for="resp-nome">
                Nome <span class="text-red-50 text-up-01"> *</span>
              </label>
              <input
                id="resp-nome"
                type="text"
                v-model="form.nome"
                maxlength="255"
                placeholder="Nome do responsável"
              />
              <span v-if="erros.nome" class="feedback danger" role="alert">
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                {{ erros.nome }}
              </span>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <div class="br-input" :class="{ danger: erros.cargo }">
              <label for="resp-cargo">
                Cargo <span class="text-red-50 text-up-01"> *</span>
              </label>
              <input
                id="resp-cargo"
                type="text"
                v-model="form.cargo"
                maxlength="255"
                placeholder="Cargo ou função"
              />
              <span v-if="erros.cargo" class="feedback danger" role="alert">
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                {{ erros.cargo }}
              </span>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <div class="br-input" :class="{ danger: erros.orgao }">
              <label for="resp-orgao">
                Órgão <span class="text-red-50 text-up-01"> *</span>
              </label>
              <input
                id="resp-orgao"
                type="text"
                v-model="form.orgao"
                maxlength="255"
                placeholder="Órgão ou secretaria"
              />
              <span v-if="erros.orgao" class="feedback danger" role="alert">
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                {{ erros.orgao }}
              </span>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <div class="br-input" :class="{ danger: erros.contato }">
              <label for="resp-contato">
                E-mail e telefone <span class="text-red-50 text-up-01"> *</span>
              </label>
              <input
                id="resp-contato"
                type="text"
                v-model="form.contato"
                maxlength="255"
                placeholder="email@dominio.gov.br / (xx) xxxxx-xxxx"
              />
              <span v-if="erros.contato" class="feedback danger" role="alert">
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                {{ erros.contato }}
              </span>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <div class="br-input">
              <label for="resp-atualizado">Atualizado em</label>
              <input
                id="resp-atualizado"
                type="text"
                :value="atualizadoEm"
                readonly
                class="input-readonly"
                placeholder="dd/mm/aaaa"
              />
            </div>
          </div>
        </fieldset>
      </Card>

      <!-- Justificativas para eixos sem ações -->
      <Card
        v-if="eixosSemAcoes.length && !enviado"
        title="Justificativas obrigatórias"
        subtitle="Os eixos abaixo não possuem ações cadastradas. Preencha a justificativa para cada um."
        custom-class="mb-4"
      >
        <div
          v-for="eixo in eixosSemAcoes"
          :key="eixo.numero"
          class="justificativa-item"
          :class="{ 'mt-3': eixosSemAcoes.indexOf(eixo) > 0 }"
        >
          <div class="br-input" :class="{ danger: erros[`justificativa_${eixo.numero}`] }">
            <label :for="`just-${eixo.numero}`">
              Justificativa — {{ eixo.titulo }}
              <span class="text-red-50 text-up-01"> *</span>
            </label>
            <textarea
              :id="`just-${eixo.numero}`"
              v-model="form.justificativas[eixo.numero]"
              rows="4"
              maxlength="8000"
              :placeholder="`Justifique a ausência de ações no ${eixo.titulo}...`"
            ></textarea>
            <span
              v-if="erros[`justificativa_${eixo.numero}`]"
              class="feedback danger"
              role="alert"
            >
              <i class="fas fa-times-circle" aria-hidden="true"></i>
              {{ erros[`justificativa_${eixo.numero}`] }}
            </span>
          </div>
        </div>
      </Card>

      <!-- Aviso de pré-requisitos não atendidos -->
      <div
        v-if="avisoPreRequisitos && !enviado"
        class="aviso-prereq mb-4"
        role="note"
      >
        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
        <div>
          <strong>Requisitos para envio não atendidos:</strong>
          <ul class="mt-1">
            <li v-for="aviso in avisoPreRequisitos" :key="aviso">{{ aviso }}</li>
          </ul>
        </div>
      </div>

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
          v-if="!enviado"
          class="br-button secondary painel-acoes__btn painel-acoes__btn--com-icone"
          type="button"
          :disabled="salvando"
          @click="salvarRascunho"
        >
          <i v-if="!salvando" class="fas fa-save" aria-hidden="true"></i>
          <i v-else class="fas fa-spinner fa-spin" aria-hidden="true"></i>
          <span>{{ salvando ? 'Salvando...' : 'Salvar Rascunho' }}</span>
        </button>
        <button
          v-if="!enviado"
          class="br-button primary painel-acoes__btn painel-acoes__btn--com-icone"
          type="button"
          :disabled="!podeEnviar || enviando"
          :title="!podeEnviar ? 'Preencha todos os requisitos antes de enviar.' : ''"
          @click="abrirModal"
        >
          <i v-if="!enviando" class="fas fa-paper-plane" aria-hidden="true"></i>
          <i v-else class="fas fa-spinner fa-spin" aria-hidden="true"></i>
          <span>Enviar Plano</span>
        </button>
      </div>

    </form>

    <!-- Modal de confirmação -->
    <div v-if="mostrarModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
      <div class="modal-box">
        <h2 id="modal-titulo" class="modal-titulo">Confirmar envio do plano</h2>
        <p class="modal-desc">Revise as informações abaixo antes de confirmar o envio para análise.</p>

        <div class="modal-resumo" role="status" aria-live="polite">
          <h3 class="modal-subtitulo">Resumo do envio</h3>
          <ul>
            <li><strong>Responsável:</strong> {{ form.nome || 'Não informado' }} ({{ form.cargo || 'Cargo não informado' }})</li>
            <li><strong>Órgão:</strong> {{ form.orgao || 'Não informado' }}</li>
            <li><strong>Contato:</strong> {{ form.contato || 'Não informado' }}</li>
            <li><strong>Ações cadastradas:</strong> {{ totalAcoesCadastradas }}</li>
            <li>
              <strong>Justificativas de eixos sem ações:</strong>
              {{ totalJustificativasObrigatoriasPreenchidas }}/{{ totalJustificativasObrigatorias }} preenchidas
            </li>
          </ul>
        </div>

        <div class="modal-proximos-passos">
          <h3 class="modal-subtitulo">Após confirmar</h3>
          <ul>
            <li>O plano será encaminhado para análise.</li>
            <li>O status ficará como <strong>Enviado para análise</strong>.</li>
            <li>As edições no conteúdo do plano ficarão bloqueadas até retorno da análise.</li>
          </ul>
        </div>

        <p class="modal-aviso">
          <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
          Este envio é uma ação de confirmação final. Verifique se os dados apresentados estão corretos.
        </p>
        <div class="modal-acoes">
          <button
            class="br-button secondary"
            type="button"
            @click="mostrarModal = false"
          >
            Cancelar
          </button>
          <button
            class="br-button primary"
            type="button"
            :disabled="enviando"
            @click="confirmarEnvio"
          >
            <i v-if="!enviando" class="fas fa-paper-plane" aria-hidden="true"></i>
            <i v-else class="fas fa-spinner fa-spin" aria-hidden="true"></i>
            {{ enviando ? 'Enviando...' : 'Confirmar envio' }}
          </button>
        </div>
      </div>
    </div>

  </DefaultLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import HeaderPage from '@/core/components/HeaderPage/HeaderPage.vue'
import Card from '@/core/components/Card/Card.vue'
import { obterEnvio, salvarEnvio, enviarPlano } from '@/features/plano-acao/services/PlanoAcaoEnvioService'
import { obterEixo } from '@/features/plano-acao/services/PlanoAcaoEixoService'
import { obterIdentificacao } from '@/features/plano-acao/services/PlanoAcaoIdentificacaoService'
import { obterDiagnostico } from '@/features/plano-acao/services/PlanoAcaoDiagnosticoService'
import { useNotification } from '@/core/composables/useNotification'
import { useAuthStore } from '@/stores/authStore'

defineOptions({ name: 'EnvioPlanoPage' })

const router = useRouter()
const authStore = useAuthStore()
const { success: notifySuccess, error: notifyError } = useNotification()

// ── Estado ────────────────────────────────────────────────────────────────
interface EixoSemAcao { numero: number; titulo: string }

const form = ref({
  nome: '',
  cargo: '',
  orgao: '',
  contato: '',
  justificativas: {} as Record<number, string>,
})

const erros = ref<Record<string, string>>({})
const salvando = ref(false)
const enviando = ref(false)
const mostrarModal = ref(false)
const enviado = ref(false)
const enviadoEm = ref<string | undefined>()
const atualizadoEm = ref('')

// pré-requisitos externos
const identificacaoCompleta = ref(false)
const diagnosticoCompleto = ref(false)
const eixoTotais = ref<Record<number, number>>({ 1: 0, 2: 0, 3: 0, 4: 0 })
const eixosSemAcoes = ref<EixoSemAcao[]>([])

const TITULOS_EIXO: Record<number, string> = {
  1: 'Eixo 1 – Gestão e Participação Social',
  2: 'Eixo 2 – Enfrentamento ao Capacitismo e à Violência',
  3: 'Eixo 3 – Acessibilidade e Tecnologia Assistiva',
  4: 'Eixo 4 – Promoção de Direitos',
}

// ── Computed ─────────────────────────────────────────────────────────────
const podeEnviar = computed(() => {
  if (!form.value.nome.trim()) return false
  if (!form.value.cargo.trim()) return false
  if (!form.value.orgao.trim()) return false
  if (!form.value.contato.trim()) return false
  if (!identificacaoCompleta.value) return false
  if (!diagnosticoCompleto.value) return false
  if (!Object.values(eixoTotais.value).some(t => t > 0)) return false
  for (const { numero } of eixosSemAcoes.value) {
    if (!form.value.justificativas[numero]?.trim()) return false
  }
  return true
})

const avisoPreRequisitos = computed((): string[] => {
  const avisos: string[] = []
  if (!identificacaoCompleta.value) avisos.push('Seção 1 (Identificação do Plano) não está completa.')
  if (!diagnosticoCompleto.value) avisos.push('Seção 2 (Diagnóstico) não está completa.')
  if (!Object.values(eixoTotais.value).some(t => t > 0)) avisos.push('É necessário ao menos uma ação cadastrada em qualquer eixo.')
  return avisos.length ? avisos : null as unknown as string[]
})

const totalAcoesCadastradas = computed(() =>
  Object.values(eixoTotais.value).reduce((total, atual) => total + (atual ?? 0), 0),
)

const totalJustificativasObrigatorias = computed(() => eixosSemAcoes.value.length)

const totalJustificativasObrigatoriasPreenchidas = computed(() =>
  eixosSemAcoes.value.filter(({ numero }) => !!form.value.justificativas[numero]?.trim()).length,
)

// ── Inicialização ─────────────────────────────────────────────────────────
onMounted(async () => {
  // Pré-preencher com dados do perfil logado
  const user = authStore.user
  if (user) {
    form.value.nome = user.name ?? ''
    form.value.contato = user.email ?? ''
    form.value.cargo = user.contexto?.perfil ?? ''
    const perfilAtivo = user.perfis?.find(p => p.id === user.contexto?.perfil_usuario_id)
    form.value.orgao = perfilAtivo?.orgao ?? user.contexto?.localidade ?? ''
  }

  // Carregar dados da API em paralelo
  const [envioRes, idRes, diagRes, e1, e2, e3, e4] = await Promise.allSettled([
    obterEnvio(),
    obterIdentificacao(),
    obterDiagnostico(),
    obterEixo(1),
    obterEixo(2),
    obterEixo(3),
    obterEixo(4),
  ])

  // Rascunho existente sobrescreve o pré-preenchimento
  if (envioRes.status === 'fulfilled' && envioRes.value) {
    const d = envioRes.value
    if (d.responsavel_nome) form.value.nome = d.responsavel_nome
    if (d.responsavel_cargo) form.value.cargo = d.responsavel_cargo
    if (d.responsavel_orgao) form.value.orgao = d.responsavel_orgao
    if (d.responsavel_contato) form.value.contato = d.responsavel_contato
    form.value.justificativas[1] = d.justificativa_eixo_1 ?? ''
    form.value.justificativas[2] = d.justificativa_eixo_2 ?? ''
    form.value.justificativas[3] = d.justificativa_eixo_3 ?? ''
    form.value.justificativas[4] = d.justificativa_eixo_4 ?? ''
    enviado.value = d.status === 'em_analise'
    enviadoEm.value = d.enviado_em
    if (d.updated_at) {
      atualizadoEm.value = formatarData(d.updated_at)
    }
  }

  // Status das seções externas
  identificacaoCompleta.value =
    idRes.status === 'fulfilled' && !!idRes.value?.pronto_para_envio
  diagnosticoCompleto.value =
    diagRes.status === 'fulfilled' && !!diagRes.value?.pronto_para_envio

  // Totais dos eixos
  const eixoResultados = [e1, e2, e3, e4]
  for (let i = 0; i < 4; i++) {
    const r = eixoResultados[i]
    const n = i + 1
    const total = r?.status === 'fulfilled' ? (r.value?.total_acoes ?? 0) : 0
    eixoTotais.value[n] = total
  }

  // Montar lista de eixos sem ações
  eixosSemAcoes.value = []
  for (let n = 1; n <= 4; n++) {
    if ((eixoTotais.value[n] ?? 0) === 0) {
      eixosSemAcoes.value.push({ numero: n, titulo: TITULOS_EIXO[n] ?? `Eixo ${n}` })
    }
  }
})

// ── Salvar rascunho ────────────────────────────────────────────────────────
async function salvarRascunho(): Promise<void> {
  salvando.value = true
  try {
    const payload = {
      responsavel_nome: form.value.nome,
      responsavel_cargo: form.value.cargo,
      responsavel_orgao: form.value.orgao,
      responsavel_contato: form.value.contato,
      justificativa_eixo_1: form.value.justificativas[1] ?? '',
      justificativa_eixo_2: form.value.justificativas[2] ?? '',
      justificativa_eixo_3: form.value.justificativas[3] ?? '',
      justificativa_eixo_4: form.value.justificativas[4] ?? '',
    }
    const salvo = await salvarEnvio(payload)
    if (salvo.updated_at) {
      atualizadoEm.value = formatarData(salvo.updated_at)
    }
    notifySuccess('Rascunho salvo com sucesso!')
  } catch (err: unknown) {
    const apiErr = err as { response?: { data?: { message?: string } } }
    notifyError(apiErr?.response?.data?.message ?? 'Erro ao salvar. Tente novamente.')
  } finally {
    salvando.value = false
  }
}

// ── Envio do plano ────────────────────────────────────────────────────────
function abrirModal(): void {
  erros.value = {}
  let temErro = false

  if (!form.value.nome.trim()) { erros.value.nome = 'Informe o nome do responsável.'; temErro = true }
  if (!form.value.cargo.trim()) { erros.value.cargo = 'Informe o cargo.'; temErro = true }
  if (!form.value.orgao.trim()) { erros.value.orgao = 'Informe o órgão.'; temErro = true }
  if (!form.value.contato.trim()) { erros.value.contato = 'Informe o e-mail e telefone.'; temErro = true }

  for (const { numero } of eixosSemAcoes.value) {
    if (!form.value.justificativas[numero]?.trim()) {
      erros.value[`justificativa_${numero}`] = 'Justificativa obrigatória.'
      temErro = true
    }
  }

  if (temErro) return

  mostrarModal.value = true
}

async function confirmarEnvio(): Promise<void> {
  enviando.value = true
  try {
    // Salvar rascunho antes de enviar para garantir dados atualizados
    await salvarEnvio({
      responsavel_nome: form.value.nome,
      responsavel_cargo: form.value.cargo,
      responsavel_orgao: form.value.orgao,
      responsavel_contato: form.value.contato,
      justificativa_eixo_1: form.value.justificativas[1] ?? '',
      justificativa_eixo_2: form.value.justificativas[2] ?? '',
      justificativa_eixo_3: form.value.justificativas[3] ?? '',
      justificativa_eixo_4: form.value.justificativas[4] ?? '',
    })

    const resultado = await enviarPlano()
    enviado.value = true
    enviadoEm.value = resultado.enviado_em
    mostrarModal.value = false
    notifySuccess('Plano de ação enviado para análise com sucesso!')
  } catch (err: unknown) {
    const apiErr = err as { response?: { data?: { message?: string } } }
    notifyError(apiErr?.response?.data?.message ?? 'Erro ao enviar o plano. Verifique os dados e tente novamente.')
    mostrarModal.value = false
  } finally {
    enviando.value = false
  }
}

// ── Utilitários ───────────────────────────────────────────────────────────
function formatarData(iso?: string): string {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
</script>

<style scoped>
/* ── Inputs ─────────────────────────────────────────────────────────────── */
.br-input textarea {
  width: 100%;
  box-sizing: border-box;
  resize: vertical;
  min-height: 5rem;
}

.input-readonly {
  background: var(--color-secondary-02, #f5f5f5) !important;
  color: var(--color-secondary-07, #555) !important;
  cursor: default !important;
}

.responsavel-fieldset {
  border: none;
  padding: 0;
  margin: 0;
}

fieldset:disabled input,
fieldset:disabled textarea,
fieldset:disabled select {
  background: var(--color-secondary-02, #f5f5f5);
  cursor: not-allowed;
}

/* ── Aviso enviado ───────────────────────────────────────────────────────── */
.aviso-enviado {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  background: #e8f5e9;
  border: 1px solid #4caf50;
  border-radius: 8px;
  color: #1b5e20;
}

.aviso-enviado .fas {
  font-size: 1.5rem;
  color: #2e7d32;
  flex-shrink: 0;
}

.aviso-enviado p {
  margin: 0.25rem 0 0;
  font-size: 0.875rem;
}

/* ── Aviso pré-requisitos ────────────────────────────────────────────────── */
.aviso-prereq {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.875rem 1.25rem;
  background: #fff8e1;
  border: 1px solid #ffc107;
  border-radius: 8px;
  color: #7d5a00;
  font-size: 0.875rem;
}

.aviso-prereq .fas {
  font-size: 1.1rem;
  color: #f9a825;
  flex-shrink: 0;
  margin-top: 0.1rem;
}

.aviso-prereq ul {
  margin: 0;
  padding-left: 1.25rem;
}

/* ── Justificativas ──────────────────────────────────────────────────────── */
.justificativa-item {
  padding-top: 0;
}

/* ── Modal ───────────────────────────────────────────────────────────────── */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9000;
  padding: 1rem;
}

.modal-box {
  background: #fff;
  border-radius: 8px;
  padding: 2rem;
  max-width: 520px;
  width: 100%;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
}

.modal-titulo {
  font-size: 1.15rem;
  font-weight: 700;
  color: #1351b4;
  margin: 0 0 0.75rem;
}

.modal-desc {
  font-size: 0.95rem;
  margin: 0 0 0.75rem;
  color: var(--primary-text-color, #1c1c1e);
}

.modal-subtitulo {
  margin: 0 0 0.5rem;
  font-size: 0.95rem;
  font-weight: 600;
  color: #1351b4;
}

.modal-resumo,
.modal-proximos-passos {
  background: #f8fbff;
  border: 1px solid #d4e5ff;
  border-radius: 6px;
  padding: 0.75rem 1rem;
  margin: 0 0 0.75rem;
}

.modal-resumo ul,
.modal-proximos-passos ul {
  margin: 0;
  padding-left: 1.1rem;
  color: var(--primary-text-color, #1c1c1e);
  font-size: 0.875rem;
}

.modal-resumo li,
.modal-proximos-passos li {
  margin-bottom: 0.35rem;
}

.modal-resumo li:last-child,
.modal-proximos-passos li:last-child {
  margin-bottom: 0;
}

.modal-aviso {
  font-size: 0.875rem;
  color: #7d3c00;
  background: #fff3e0;
  border: 1px solid #ffb74d;
  border-radius: 6px;
  padding: 0.75rem 1rem;
  margin: 0 0 1.25rem;
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
}

.modal-aviso .fas {
  color: #e65100;
  flex-shrink: 0;
  margin-top: 0.15rem;
}

.modal-acoes {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  flex-wrap: wrap;
}

/* ── Painel de ações ─────────────────────────────────────────────────────── */
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
</style>
