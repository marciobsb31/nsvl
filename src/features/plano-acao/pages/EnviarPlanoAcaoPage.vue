<template>
  <DefaultLayout>
    <HeaderPage
      title="Enviar Plano de Ação"
      subtitle="Preencha todas as seções obrigatórias e pelo menos um eixo para habilitar o envio."
      customClass="mb-4"
    />

    <!-- Timeline removida -->

    <!-- Grid de seções -->
    <div class="secoes-grid">
      <div
        v-for="secao in secoes"
        :key="secao.id"
        class="secao-card"
        :style="{ '--cor-secao': secao.cor }"
        role="button"
        tabindex="0"
        :aria-label="`${secao.label}: ${secao.titulo}`"
        @click="abrirSecao(secao)"
        @keydown.enter="abrirSecao(secao)"
        @keydown.space.prevent="abrirSecao(secao)"
      >
        <span class="secao-card__label">{{ secao.label }}</span>
        <h3 class="secao-card__titulo">{{ secao.titulo }}</h3>
        <div class="secao-card__badges">
          <span v-if="secao.obrigatorio" class="badge badge--obrigatorio">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            Obrigatório
          </span>
          <span v-else class="badge badge--opcional">Opcional</span>

          <span
            v-if="secao.statusBadge"
            :class="['badge', secao.statusBadgeClass]"
          >
            <i
              v-if="secao.statusBadgeIcon"
              :class="['fas', secao.statusBadgeIcon]"
              aria-hidden="true"
            ></i>
            {{ secao.statusBadge }}
          </span>
        </div>
      </div>
    </div>

    <!-- Critério mínimo de envio -->
    <div class="criterio-box mt-4" role="note" aria-label="Critério mínimo de envio">
      <p class="criterio-box__titulo">
        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
        Critério Mínimo de Envio
      </p>
      <p class="criterio-box__descricao mb-1">
        O botão "Enviar Plano" só é habilitado quando:
      </p>
      <ol class="criterio-box__lista">
        <li>(1) houver pelo menos <strong>uma ação cadastrada</strong> em qualquer eixo;</li>
        <li>(2) os dados da <strong>Identificação do Plano</strong> estiverem completamente preenchidos;</li>
        <li>(3) os dados do <strong>Diagnóstico</strong> estiverem completamente preenchidos;</li>
        <li>(4) os dados do <strong>Responsável pelo Envio</strong> estiverem completamente preenchidos.</li>
      </ol>
    </div>

    <!-- Barra de ações -->
    <div class="secoes-acoes mt-4" role="group" aria-label="Ações do plano">
      <button
        class="br-button secondary"
        type="button"
        @click="cancelar"
        aria-label="Cancelar e voltar"
      >
        <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
        Voltar
      </button>
      <button
        class="br-button primary"
        type="button"
        :disabled="!podeEnviar"
        :aria-disabled="!podeEnviar"
        @click="enviarPlano"
        aria-label="Enviar plano de ação"
      >
        <i class="fas fa-paper-plane mr-2" aria-hidden="true"></i>
        Enviar Plano
      </button>
    </div>
  </DefaultLayout>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import HeaderPage from '@/core/components/HeaderPage/HeaderPage.vue'
import { obterIdentificacao } from '@/features/plano-acao/services/PlanoAcaoIdentificacaoService'
import { obterDiagnostico } from '@/features/plano-acao/services/PlanoAcaoDiagnosticoService'
import { obterEixo } from '@/features/plano-acao/services/PlanoAcaoEixoService'

defineOptions({ name: 'EnviarPlanoAcaoPage' })

const router = useRouter()

interface Secao {
  id: number
  label: string
  titulo: string
  obrigatorio: boolean
  cor: string
  totalAcoes?: number
  statusBadge?: string
  statusBadgeClass?: string
  statusBadgeIcon?: string
}

const secoesInicial: Secao[] = [
  {
    id: 1,
    label: 'SEÇÃO 1',
    titulo: 'Identificação do Plano',
    obrigatorio: true,
    cor: '#1351b4',
    statusBadge: 'Não preenchido',
    statusBadgeClass: 'badge--pendente',
    statusBadgeIcon: 'fa-times',
  },
  {
    id: 2,
    label: 'SEÇÃO 2',
    titulo: 'Diagnóstico',
    obrigatorio: true,
    cor: '#c0345b',
    statusBadge: 'Não preenchido',
    statusBadgeClass: 'badge--pendente',
    statusBadgeIcon: 'fa-times',
  },
  {
    id: 3,
    label: 'SEÇÃO 3 - EIXO 1',
    titulo: 'Gestão e Participação Social',
    obrigatorio: false,
    cor: '#168821',
    statusBadge: '0 ações cadastradas',
    statusBadgeClass: 'badge--neutro',
  },
  {
    id: 4,
    label: 'SEÇÃO 4 - EIXO 2',
    titulo: 'Enfrentamento ao Capacitismo e Violência',
    obrigatorio: false,
    cor: '#d4a000',
    statusBadge: '0 ações cadastradas',
    statusBadgeClass: 'badge--neutro',
  },
  {
    id: 5,
    label: 'SEÇÃO 5 - EIXO 3',
    titulo: 'Acessibilidade e Tecnologia Assistiva',
    obrigatorio: false,
    cor: '#e04f00',
    statusBadge: '0 ações cadastradas',
    statusBadgeClass: 'badge--neutro',
  },
  {
    id: 6,
    label: 'SEÇÃO 6 - EIXO 4',
    titulo: 'Promoção de Direitos (Educação, Saúde, etc.)',
    obrigatorio: false,
    cor: '#6b4b9a',
    statusBadge: '0 ações cadastradas',
    statusBadgeClass: 'badge--neutro',
  },
  {
    id: 7,
    label: 'SEÇÃO 7',
    titulo: 'Observações Complementares',
    obrigatorio: false,
    cor: '#b22222',
  },
  {
    id: 8,
    label: 'SEÇÃO 8',
    titulo: 'Anexos',
    obrigatorio: false,
    cor: '#007baa',
  },
  {
    id: 9,
    label: 'SEÇÃO 9',
    titulo: 'Envio do Plano',
    obrigatorio: false,
    cor: '#3d5a80',
    statusBadge: 'Informações do Responsável pelo Envio',
    statusBadgeClass: 'badge--aviso',
  },
]

const secoes = ref(secoesInicial)



const podeEnviar = computed(() => {
  const sec1 = secoes.value.find(s => s.id === 1)
  const sec2 = secoes.value.find(s => s.id === 2)
  const temAcoes = secoes.value
    .filter(s => s.id >= 3 && s.id <= 6)
    .some(s => s.statusBadge && !s.statusBadge.startsWith('0'))
  return (
    sec1?.statusBadgeClass === 'badge--pronto' &&
    sec2?.statusBadgeClass === 'badge--pronto' &&
    temAcoes
  )
})

onMounted(async () => {
  const [identificacao, diagnostico, eixo1, eixo2, eixo3, eixo4] = await Promise.allSettled([
    obterIdentificacao(),
    obterDiagnostico(),
    obterEixo(1),
    obterEixo(2),
    obterEixo(3),
    obterEixo(4),
  ])

  const sec1 = secoes.value.find(s => s.id === 1)
  if (sec1) {
    const pronto = identificacao.status === 'fulfilled' && identificacao.value?.pronto_para_envio
    sec1.statusBadge = pronto ? 'Pronto para envio' : 'Não preenchido'
    sec1.statusBadgeClass = pronto ? 'badge--pronto' : 'badge--pendente'
    sec1.statusBadgeIcon = pronto ? 'fa-check' : 'fa-times'
  }

  const sec2 = secoes.value.find(s => s.id === 2)
  if (sec2) {
    const pronto = diagnostico.status === 'fulfilled' && diagnostico.value?.pronto_para_envio
    sec2.statusBadge = pronto ? 'Pronto para envio' : 'Não preenchido'
    sec2.statusBadgeClass = pronto ? 'badge--pronto' : 'badge--pendente'
    sec2.statusBadgeIcon = pronto ? 'fa-check' : 'fa-times'
  }

  const eixosResultados = [
    { secId: 3, resultado: eixo1 },
    { secId: 4, resultado: eixo2 },
    { secId: 5, resultado: eixo3 },
    { secId: 6, resultado: eixo4 },
  ]
  for (const { secId, resultado } of eixosResultados) {
    const sec = secoes.value.find(s => s.id === secId)
    if (sec) {
      const total = resultado.status === 'fulfilled' ? (resultado.value?.total_acoes ?? 0) : 0
      sec.totalAcoes = total
      if (total > 0) {
        sec.statusBadge = total === 1 ? '1 ação cadastrada' : `${total} ações cadastradas`
        sec.statusBadgeClass = 'badge--acoes'
      } else {
        sec.statusBadge = '0 ações cadastradas'
        sec.statusBadgeClass = 'badge--neutro'
      }
    }
  }
})

function abrirSecao(secao: Secao) {
  const rotas: Record<number, string> = {
    1: '/plano-acao/identificacao',
    2: '/plano-acao/diagnostico',
    3: '/plano-acao/eixo-1',
    4: '/plano-acao/eixo-2',
    5: '/plano-acao/eixo-3',
    6: '/plano-acao/eixo-4',
    7: '/plano-acao/observacoes',
    8: '/plano-acao/anexos',
    9: '/plano-acao/envio',
  }
  const rota = rotas[secao.id]
  if (rota) {
    router.push(rota)
  }
}

function enviarPlano() {
  console.log('Enviar plano')
}

function cancelar() {
  router.push({ name: 'plano-acao' })
}
</script>

<style scoped>


.secoes-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

/* ── Card de seção ──────────────────────────────────────── */
.secao-card {
  background: #fff;
  border: 1px solid #e0e0e0;
  border-top: 4px solid var(--cor-secao, #1351b4);
  border-radius: 6px;
  padding: 1rem 1rem 0.875rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  cursor: pointer;
  transition: box-shadow 0.18s, transform 0.15s;
  outline: none;
}

.secao-card:hover,
.secao-card:focus-visible {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.secao-card:focus-visible {
  box-shadow: 0 0 0 3px rgba(19, 81, 180, 0.3);
}

.secao-card__label {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--cor-secao, #1351b4);
  margin-bottom: 0.1rem;
}

.secao-card__titulo {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1c1c1e;
  margin: 0 0 0.4rem;
  line-height: 1.35;
}

.secao-card__badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-top: auto;
}

/* ── Badges ─────────────────────────────────────────────── */
.badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 0.2rem 0.55rem;
  border-radius: 20px;
  white-space: nowrap;
}

.badge--obrigatorio {
  background: #fff4e0;
  color: #9e4c00;
  border: 1px solid #f5c26b;
}

.badge--opcional {
  background: #f2f2f2;
  color: #636363;
  border: 1px solid #d0d0d0;
}

.badge--pronto {
  background: #dde9ff;
  color: #1351b4;
  border: 1px solid #a8c2f5;
}

.badge--pendente {
  background: #fde8e8;
  color: #c92a2a;
  border: 1px solid #f5a5a5;
}

.badge--acoes {
  background: #e8f5e9;
  color: #1b6b2a;
  border: 1px solid #a5d6a7;
}

.badge--neutro {
  background: #f0f0f0;
  color: #595959;
  border: 1px solid #d0d0d0;
}

.badge--aviso {
  background: #fff8e1;
  color: #7a5900;
  border: 1px solid #ffe082;
}

/* ── Critério mínimo ────────────────────────────────────── */
.criterio-box {
  background: #fffbec;
  border: 1px solid #f5e6aa;
  border-left: 4px solid #d4a000;
  border-radius: 6px;
  padding: 1rem 1.25rem;
}

.criterio-box__titulo {
  font-size: 0.9rem;
  font-weight: 700;
  color: #7a5900;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 0.5rem;
}

.criterio-box__descricao {
  font-size: 0.875rem;
  color: #4a4a4a;
  margin: 0 0 0.4rem;
}

.criterio-box__lista {
  margin: 0;
  padding: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.criterio-box__lista li {
  font-size: 0.85rem;
  color: #4a4a4a;
  line-height: 1.5;
}

/* ── Barra de ações ─────────────────────────────────────── */
.secoes-acoes {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

/* ── Responsividade ─────────────────────────────────────── */
@media (max-width: 991px) {
  .secoes-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 575px) {
  .secoes-grid {
    grid-template-columns: 1fr;
  }

  .secoes-acoes {
    flex-direction: column-reverse;
  }

  .secoes-acoes .br-button {
    width: 100%;
    justify-content: center;
  }
}
</style>
