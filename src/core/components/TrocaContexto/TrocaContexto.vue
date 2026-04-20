<template>
  <Teleport to="body">
    <Transition name="tc-fade">
      <div
        v-if="visivel"
        class="tc-overlay"
        @click.self="fechar"
        role="dialog"
        aria-modal="true"
        aria-label="Troca de contexto"
      >
        <div class="tc-modal" ref="modalRef">
          <div class="tc-modal__header">
            <h2 class="tc-modal__titulo">Troca de Contexto</h2>
            <button type="button" class="tc-modal__fechar" aria-label="Fechar" @click="fechar">
              <i class="fas fa-times" aria-hidden="true"></i>
            </button>
          </div>
          <p class="tc-modal__descricao">
            Selecione o perfil que deseja utilizar. Menus, permissões e dados serão atualizados
            conforme o perfil escolhido.
          </p>
          <div class="tc-lista" role="radiogroup" aria-label="Perfis disponíveis">
            <button
              v-for="perfil in perfisAtivos"
              :key="perfil.id"
              type="button"
              class="tc-card"
              :class="{
                'tc-card--ativo': isPerfilAtivo(perfil),
                'tc-card--selecionando': selecionandoId === perfil.id,
              }"
              :aria-pressed="isPerfilAtivo(perfil)"
              :disabled="trocandoContexto"
              @click="selecionarPerfil(perfil)"
            >
              <div class="tc-card__indicador">
                <span v-if="isPerfilAtivo(perfil)" class="tc-card__badge-ativo">Em uso</span>
              </div>
              <div class="tc-card__conteudo">
                <span class="tc-card__nome">{{ perfil.nome }}</span>
                <div class="tc-card__detalhes">
                  <span class="tc-card__detalhe" v-if="detalhePerfil(perfil)">
                    <i class="fas fa-layer-group" aria-hidden="true"></i>
                    {{ detalhePerfil(perfil) }}
                  </span>
                  <!-- <span class="tc-card__detalhe" v-if="user?.uf_id">
                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                    {{ user.uf_id }}
                  </span>
                  <span class="tc-card__detalhe" v-if="user?.municipio_id">
                    <i class="fas fa-city" aria-hidden="true"></i>
                    {{ user.municipio_lotacao }}
                  </span> -->
                </div>
              </div>
              <div class="tc-card__acao">
                <i
                  v-if="isPerfilAtivo(perfil)"
                  class="fas fa-check-circle tc-card__icone-ativo"
                  aria-hidden="true"
                ></i>
                <i
                  v-else
                  class="fas fa-arrow-right tc-card__icone-selecionar"
                  aria-hidden="true"
                ></i>
              </div>
            </button>
          </div>
          <div v-if="trocandoContexto" class="tc-modal__loading">
            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
            Atualizando contexto...
          </div>
          <div v-if="erroTroca" class="tc-modal__erro" role="alert">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            {{ erroTroca }}
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useAuth } from '@/core/composables/useAuth'
import type { perfis } from '@/core/types/usuario/UsuarioInterface'
defineOptions({ name: 'TrocaContexto' })
const props = defineProps<{ visivel: boolean }>()
const emit = defineEmits<{
  (e: 'fechar'): void
  (e: 'contexto-alterado'): void
}>()
const { perfisAtivos, perfilAtivo, trocandoContexto, trocarContexto, user } = useAuth()
const modalRef = ref<HTMLElement | null>(null)
const erroTroca = ref<string | null>(null)
const selecionandoId = ref<number | null>(null)
function labelEsfera(esfera: string) {
  const map: Record<string, string> = {
    federal: 'Federal',
    estadual: 'Estadual',
    municipal: 'Municipal',
  }
  return map[String(esfera).toLowerCase()] ?? esfera
}

function detalhePerfil(perfil: perfis) {
  const esfera = String(perfil.esfera ?? '').toLowerCase().trim()
  if (esfera === 'municipal' && perfil.municipio) {
    return perfil.municipio
  }
  if (perfil.esfera) {
    return labelEsfera(perfil.esfera)
  }
  return ''
}
function isPerfilAtivo(perfil: perfis) {
  return perfilAtivo.value?.id === perfil.id
}
async function selecionarPerfil(perfil: perfis) {
  if (isPerfilAtivo(perfil) || trocandoContexto.value) return
  erroTroca.value = null
  selecionandoId.value = perfil.id
  try {
    await trocarContexto(perfil.id)
    emit('contexto-alterado')
    emit('fechar')
  } catch {
    erroTroca.value = 'Não foi possível trocar o contexto. Tente novamente.'
  } finally {
    selecionandoId.value = null
  }
}
function fechar() {
  if (!trocandoContexto.value) {
    erroTroca.value = null
    emit('fechar')
  }
}
function handleEsc(e: KeyboardEvent) {
  if (e.key === 'Escape') fechar()
}
watch(
  () => props.visivel,
  (val) => {
    if (val) {
      document.addEventListener('keydown', handleEsc)
      document.body.style.overflow = 'hidden'
    } else {
      document.removeEventListener('keydown', handleEsc)
      document.body.style.overflow = ''
    }
  },
)
onUnmounted(() => {
  document.removeEventListener('keydown', handleEsc)
  document.body.style.overflow = ''
})
</script>
<style scoped>
.tc-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(2px);
  padding: 1rem;
}
.tc-modal {
  background: var(--color-secondary-01, #fff);
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
  width: 100%;
  max-width: 560px;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.tc-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-secondary-04, #ddd);
}
.tc-modal__titulo {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--color-primary-default, #1351b4);
  margin: 0;
}
.tc-modal__fechar {
  width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  color: var(--color-secondary-06, #888);
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  transition:
    background-color 0.2s,
    color 0.2s;
}
.tc-modal__fechar:hover {
  background: var(--color-secondary-03, #e8e8e8);
  color: var(--color-secondary-08, #333);
}
.tc-modal__descricao {
  padding: 1rem 1.5rem 0;
  margin: 0;
  font-size: 0.875rem;
  color: var(--color-secondary-06, #888);
  line-height: 1.5;
}
.tc-lista {
  padding: 1rem 1.5rem 1.5rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.tc-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.25rem;
  border: 2px solid var(--color-secondary-04, #ddd);
  border-radius: 8px;
  background: var(--color-secondary-01, #fff);
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: left;
  width: 100%;
  font-family: inherit;
  font-size: inherit;
}
.tc-card:hover:not(:disabled):not(.tc-card--ativo) {
  border-color: var(--color-primary-default, #1351b4);
  background: var(--color-primary-pastel, #e8f4fc);
}
.tc-card--ativo {
  border-color: var(--color-primary-default, #1351b4);
  background: var(--color-primary-pastel, #e8f4fc);
  cursor: default;
}
.tc-card--selecionando {
  opacity: 0.7;
  pointer-events: none;
}
.tc-card:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.tc-card__badge-ativo {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #fff;
  background: var(--color-primary-default, #1351b4);
  border-radius: 4px;
  white-space: nowrap;
}
.tc-card__conteudo {
  flex: 1;
  min-width: 0;
}
.tc-card__nome {
  font-weight: 600;
  font-size: 0.9375rem;
  color: var(--color-secondary-08, #333);
  display: block;
  margin-bottom: 0.35rem;
}
.tc-card__detalhes {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 1rem;
}
.tc-card__detalhe {
  font-size: 0.8125rem;
  color: var(--color-secondary-06, #888);
  display: flex;
  align-items: center;
  gap: 0.35rem;
}
.tc-card__detalhe i {
  font-size: 0.75rem;
  color: var(--color-primary-default, #1351b4);
  opacity: 0.7;
}
.tc-card__acao {
  flex-shrink: 0;
}
.tc-card__icone-ativo {
  font-size: 1.25rem;
  color: var(--color-primary-default, #1351b4);
}
.tc-card__icone-selecionar {
  font-size: 1rem;
  color: var(--color-secondary-05, #bbb);
  transition: color 0.2s;
}
.tc-card:hover:not(:disabled):not(.tc-card--ativo) .tc-card__icone-selecionar {
  color: var(--color-primary-default, #1351b4);
}
.tc-modal__loading {
  padding: 0 1.5rem 1rem;
  font-size: 0.875rem;
  color: var(--color-primary-default, #1351b4);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.tc-modal__erro {
  padding: 0.75rem 1.5rem;
  margin: 0 1.5rem 1rem;
  font-size: 0.875rem;
  color: #b71c1c;
  background: #fdecea;
  border-radius: 6px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
/* Dark theme */
[data-theme='dark'] .tc-modal {
  background: var(--color-secondary-02, #1a1a1a);
}
[data-theme='dark'] .tc-modal__header {
  border-color: rgba(255, 255, 255, 0.12);
}
[data-theme='dark'] .tc-card {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.12);
}
[data-theme='dark'] .tc-card:hover:not(:disabled):not(.tc-card--ativo) {
  background: rgba(19, 81, 180, 0.15);
  border-color: var(--color-primary-lighten-01, #4d7fd6);
}
[data-theme='dark'] .tc-card--ativo {
  background: rgba(19, 81, 180, 0.15);
  border-color: var(--color-primary-lighten-01, #4d7fd6);
}
[data-theme='dark'] .tc-card__nome {
  color: rgba(255, 255, 255, 0.95);
}
[data-theme='dark'] .tc-card__detalhe {
  color: rgba(255, 255, 255, 0.6);
}
[data-theme='dark'] .tc-modal__descricao {
  color: rgba(255, 255, 255, 0.55);
}
[data-theme='dark'] .tc-modal__fechar {
  color: rgba(255, 255, 255, 0.6);
}
[data-theme='dark'] .tc-modal__fechar:hover {
  background: rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.9);
}
[data-theme='dark'] .tc-modal__erro {
  background: rgba(183, 28, 28, 0.15);
  color: #ef9a9a;
}
/* Transição */
.tc-fade-enter-active,
.tc-fade-leave-active {
  transition: opacity 0.25s ease;
}
.tc-fade-enter-active .tc-modal,
.tc-fade-leave-active .tc-modal {
  transition: transform 0.25s ease;
}
.tc-fade-enter-from,
.tc-fade-leave-to {
  opacity: 0;
}
.tc-fade-enter-from .tc-modal {
  transform: scale(0.95) translateY(10px);
}
.tc-fade-leave-to .tc-modal {
  transform: scale(0.95) translateY(10px);
}
@media (max-width: 575px) {
  .tc-modal {
    max-height: 90vh;
    border-radius: 12px 12px 0 0;
    align-self: flex-end;
  }
  .tc-card__detalhes {
    flex-direction: column;
    gap: 0.25rem;
  }
  .tc-card__indicador {
    min-width: auto;
  }
}
</style>
