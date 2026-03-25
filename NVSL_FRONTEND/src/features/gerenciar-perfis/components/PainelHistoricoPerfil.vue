<template>
  <div class="painel-perfil">
    <!-- Hero header (padrão solicitacao-hero) -->
    <header class="painel-hero">
      <div class="painel-hero__topo">
        <h1 class="painel-hero__title">Histórico do perfil</h1>
        <button
          class="br-button secondary small"
          type="button"
          @click="$emit('voltar')"
          aria-label="Voltar"
        >
          Voltar
        </button>
      </div>
      <p class="painel-hero__lead">
        Registro de todas as alterações realizadas no perfil
        <strong v-if="perfil">{{ perfil.nome }}</strong>.
      </p>
    </header>

    <div class="painel-form">
      <!-- Card: Registro de alterações -->
      <Card
        title="Registro de alterações"
        :subtitle="historico.length > 0 ? `${historico.length} registro${historico.length !== 1 ? 's' : ''} encontrado${historico.length !== 1 ? 's' : ''}` : 'Histórico de modificações realizadas neste perfil.'"
      >
        <div v-if="carregando" class="estado-vazio" role="status" aria-live="polite">
          <div class="loading-spinner" aria-hidden="true"></div>
          <p>Carregando histórico...</p>
        </div>

        <div v-else-if="historico.length === 0" class="estado-vazio">
          <i class="fas fa-clock fa-2x" aria-hidden="true"></i>
          <p>Nenhum registro de histórico encontrado.</p>
        </div>

        <div v-else class="table-responsive">
          <table class="br-table tabela-historico" role="table">
            <thead>
              <tr>
                <th scope="col">Data/Hora</th>
                <th scope="col">Usuário</th>
                <th scope="col">Perfil</th>
                <th scope="col">Atualização</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in historico" :key="item.id">
                <td class="td-data">{{ item.data_hora }}</td>
                <td>{{ item.usuario }}</td>
                <td>{{ item.perfil }}</td>
                <td class="td-atualizacao">{{ item.atualizacao }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <!-- Ações (padrão solicitacao-acoes) -->
      <div class="painel-acoes">
        <button
          class="br-button secondary painel-acoes__btn"
          type="button"
          @click="$emit('voltar')"
        >
          Voltar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import Card from '@/core/components/Card/Card.vue'
import { obterHistorico, type HistoricoItem, type PerfilGerenciar } from '@/services/GerenciarPerfilService'
import { useNotification } from '@/core/composables/useNotification'

defineOptions({ name: 'PainelHistoricoPerfil' })

const props = defineProps<{
  perfil: PerfilGerenciar
}>()

defineEmits<{
  (e: 'voltar'): void
}>()

const { error } = useNotification()
const carregando = ref(false)
const historico = ref<HistoricoItem[]>([])

async function carregar(id: number) {
  carregando.value = true
  try {
    historico.value = await obterHistorico(id)
  } catch {
    error('Não foi possível carregar o histórico.')
  } finally {
    carregando.value = false
  }
}

watch(() => props.perfil?.id, (id) => {
  if (id) carregar(id)
}, { immediate: true })

onMounted(() => {
  if (props.perfil?.id) carregar(props.perfil.id)
})
</script>

<style scoped>
.painel-perfil {
  padding: 1.25rem;
}

@media (min-width: 576px) {
  .painel-perfil { padding: 1.5rem; }
}

@media (min-width: 1200px) {
  .painel-perfil { padding: 2rem; }
}

/* ── Hero (padrão solicitacao-hero) ── */
.painel-hero {
  margin-bottom: 1.5rem;
}

.painel-hero__topo {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.painel-hero__title {
  margin: 0 0 0.75rem;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.25;
  color: var(--color-primary-darken-02, #0c326f);
  letter-spacing: -0.02em;
}

@media (min-width: 768px) {
  .painel-hero__title { font-size: 1.75rem; }
}

.painel-hero__lead {
  margin: 0;
  max-width: 62rem;
  font-size: 0.9375rem;
  line-height: 1.55;
  color: var(--color-secondary-08, #333);
}

/* ── Form container ── */
.painel-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

@media (min-width: 768px) {
  .painel-form { gap: 1.5rem; }
}

/* ── Estado vazio ── */
.estado-vazio {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 2.5rem 1rem;
  color: var(--color-secondary-06, #888);
}

.estado-vazio p { margin: 0; font-size: 0.875rem; }

/* ── Tabela ── */
.tabela-historico { width: 100%; border-collapse: collapse; }

.tabela-historico thead th {
  background: var(--color-secondary-02, #f0f0f0);
  font-weight: 600;
  font-size: 0.875rem;
  padding: 0.75rem;
  border-bottom: 2px solid var(--color-secondary-04, #ccc);
  text-align: left;
  white-space: nowrap;
  color: var(--color-secondary-09, #333);
}

.tabela-historico tbody tr { transition: background 0.12s; }
.tabela-historico tbody tr:hover { background: var(--color-secondary-01, #f8f8f8); }

.tabela-historico tbody td {
  padding: 0.625rem 0.75rem;
  border-bottom: 1px solid var(--color-secondary-03, #e8e8e8);
  font-size: 0.875rem;
  vertical-align: middle;
  color: var(--color-secondary-08, #333);
}

.td-data { white-space: nowrap; font-variant-numeric: tabular-nums; }
.td-atualizacao { max-width: 350px; }

/* ── Ações (padrão solicitacao-acoes) ── */
.painel-acoes {
  display: flex;
  flex-direction: column-reverse;
  gap: 0.75rem;
  margin-top: 0.25rem;
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
    flex-wrap: wrap;
    justify-content: flex-end;
    align-items: center;
  }

  .painel-acoes__btn {
    width: auto;
    min-width: 10rem;
  }
}

/* ── Loading ── */
.loading-spinner {
  width: 32px; height: 32px;
  border: 3px solid var(--color-secondary-03, #eee);
  border-top-color: var(--color-primary-default, #1351b4);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
