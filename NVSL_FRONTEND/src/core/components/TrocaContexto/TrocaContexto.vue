<template>
  <div v-if="possuiMultiplosPerfis" class="tc-dropdown" ref="dropdownRef">
    <button
      type="button"
      class="tc-dropdown__trigger"
      :disabled="trocandoContexto"
      @click="aberto = !aberto"
      aria-haspopup="listbox"
      :aria-expanded="aberto"
      aria-label="Trocar contexto de perfil"
    >
      <i class="fas fa-exchange-alt tc-dropdown__icone" aria-hidden="true"></i>
      <span class="tc-dropdown__label">
        {{ perfilAtivo?.nome ?? 'Selecionar perfil' }}
        <small v-if="perfilAtivo?.esfera" class="tc-dropdown__esfera">{{ labelEsfera(perfilAtivo.esfera) }}</small>
      </span>
      <i class="fas fa-chevron-down tc-dropdown__seta" :class="{ 'tc-dropdown__seta--aberto': aberto }" aria-hidden="true"></i>
    </button>

    <Transition name="tc-slide">
      <ul v-if="aberto" class="tc-dropdown__menu" role="listbox" aria-label="Perfis disponíveis">
        <li
          v-for="perfil in perfisAtivos"
          :key="perfil.perfil_usuario_id"
          class="tc-dropdown__item"
          :class="{
            'tc-dropdown__item--ativo': isPerfilAtivo(perfil),
            'tc-dropdown__item--selecionando': selecionandoId === perfil.perfil_usuario_id,
          }"
          role="option"
          :aria-selected="isPerfilAtivo(perfil)"
          @click="selecionarPerfil(perfil)"
        >
          <div class="tc-dropdown__item-info">
            <span class="tc-dropdown__item-nome">
              {{ perfil.nome }}
              <span v-if="isPerfilAtivo(perfil)" class="tc-dropdown__badge">Ativo</span>
            </span>
            <span class="tc-dropdown__item-detalhes">
              {{ montarDetalhes(perfil) }}
            </span>
          </div>
          <i v-if="selecionandoId === perfil.perfil_usuario_id" class="fas fa-spinner fa-spin tc-dropdown__item-loading" aria-hidden="true"></i>
          <i v-else-if="isPerfilAtivo(perfil)" class="fas fa-check-circle tc-dropdown__item-check" aria-hidden="true"></i>
        </li>

        <li v-if="erroTroca" class="tc-dropdown__erro" role="alert">
          <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
          {{ erroTroca }}
        </li>
      </ul>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useAuth } from '@/core/composables/useAuth'
import type { PerfilVigente } from '@/stores/authStore'

defineOptions({ name: 'TrocaContexto' })

const emit = defineEmits<{
  (e: 'contexto-alterado'): void
}>()

const { perfisAtivos, perfilAtivo, possuiMultiplosPerfis, trocandoContexto, trocarContexto } = useAuth()

const dropdownRef = ref<HTMLElement | null>(null)
const aberto = ref(false)
const erroTroca = ref<string | null>(null)
const selecionandoId = ref<number | null>(null)

function labelEsfera(esfera: string) {
  const map: Record<string, string> = { federal: 'Federal', estadual: 'Estadual', municipal: 'Municipal' }
  return map[esfera] ?? esfera
}

function isPerfilAtivo(perfil: PerfilVigente) {
  return perfilAtivo.value?.perfil_usuario_id === perfil.perfil_usuario_id
}

function valorCampo(valor?: string | null): string {
  return (valor ?? '').trim()
}

function montarDetalhes(perfil: PerfilVigente): string {
  const partes: string[] = []
  if (perfil.esfera) partes.push(labelEsfera(perfil.esfera))
  if (valorCampo(perfil.uf)) partes.push(perfil.uf!)
  if (valorCampo(perfil.municipio)) partes.push(perfil.municipio!)
  if (valorCampo(perfil.orgao)) partes.push(perfil.orgao!)
  return partes.join(' · ') || 'Sem informações adicionais'
}

async function selecionarPerfil(perfil: PerfilVigente) {
  if (isPerfilAtivo(perfil) || trocandoContexto.value) return
  erroTroca.value = null
  selecionandoId.value = perfil.perfil_usuario_id
  try {
    await trocarContexto(perfil.perfil_usuario_id)
    aberto.value = false
    emit('contexto-alterado')
  } catch {
    erroTroca.value = 'Não foi possível trocar o contexto. Tente novamente.'
  } finally {
    selecionandoId.value = null
  }
}

function handleClickOutside(e: MouseEvent) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
    aberto.value = false
  }
}

function handleEsc(e: KeyboardEvent) {
  if (e.key === 'Escape') aberto.value = false
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside, true)
  document.addEventListener('keydown', handleEsc)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside, true)
  document.removeEventListener('keydown', handleEsc)
})
</script>

<style scoped>
.tc-dropdown {
  position: relative;
}

.tc-dropdown__trigger {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.6rem;
  font-size: 0.75rem;
  font-weight: 600;
  font-family: inherit;
  border: 1px solid var(--color-primary-default, #1351b4);
  border-radius: 4px;
  background: transparent;
  color: var(--color-primary-default, #1351b4);
  cursor: pointer;
  transition: background-color 0.2s, color 0.2s;
  white-space: nowrap;
  max-width: 260px;
}

.tc-dropdown__trigger:hover {
  background: var(--color-primary-default, #1351b4);
  color: #fff;
}

.tc-dropdown__trigger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.tc-dropdown__icone {
  font-size: 0.7rem;
  flex-shrink: 0;
}

.tc-dropdown__label {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.tc-dropdown__esfera {
  font-weight: 400;
  opacity: 0.75;
  font-size: 0.7rem;
}

.tc-dropdown__seta {
  font-size: 0.55rem;
  flex-shrink: 0;
  transition: transform 0.2s;
}

.tc-dropdown__seta--aberto {
  transform: rotate(180deg);
}

/* ---- Menu ---- */
.tc-dropdown__menu {
  position: absolute;
  top: calc(100% + 4px);
  right: 0;
  z-index: 9000;
  min-width: 300px;
  max-width: 420px;
  max-height: 360px;
  overflow-y: auto;
  margin: 0;
  padding: 0.35rem 0;
  list-style: none;
  background: var(--color-secondary-01, #fff);
  border: 1px solid var(--color-secondary-04, #ddd);
  border-radius: 8px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.tc-dropdown__item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.65rem 1rem;
  cursor: pointer;
  transition: background-color 0.15s;
}

.tc-dropdown__item:hover:not(.tc-dropdown__item--ativo):not(.tc-dropdown__item--selecionando) {
  background: var(--color-primary-pastel, #e8f4fc);
}

.tc-dropdown__item--ativo {
  background: var(--color-primary-pastel, #e8f4fc);
  cursor: default;
}

.tc-dropdown__item--selecionando {
  opacity: 0.6;
  pointer-events: none;
}

.tc-dropdown__item-info {
  flex: 1;
  min-width: 0;
}

.tc-dropdown__item-nome {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-secondary-08, #333);
}

.tc-dropdown__badge {
  display: inline-block;
  padding: 0.1rem 0.4rem;
  font-size: 0.625rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #fff;
  background: var(--color-success, #168821);
  border-radius: 3px;
  white-space: nowrap;
}

.tc-dropdown__item-detalhes {
  display: block;
  font-size: 0.725rem;
  color: var(--color-secondary-06, #888);
  margin-top: 0.15rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.tc-dropdown__item-check {
  color: var(--color-success, #168821);
  font-size: 1rem;
  flex-shrink: 0;
}

.tc-dropdown__item-loading {
  color: var(--color-primary-default, #1351b4);
  font-size: 0.875rem;
  flex-shrink: 0;
}

.tc-dropdown__erro {
  padding: 0.5rem 1rem;
  font-size: 0.75rem;
  color: #b71c1c;
  background: #fdecea;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

/* Dark theme */
[data-theme="dark"] .tc-dropdown__trigger {
  border-color: var(--color-primary-lighten-01, #4d7fd6);
  color: var(--color-primary-lighten-01, #4d7fd6);
}
[data-theme="dark"] .tc-dropdown__trigger:hover {
  background: var(--color-primary-lighten-01, #4d7fd6);
  color: #fff;
}
[data-theme="dark"] .tc-dropdown__menu {
  background: var(--color-secondary-02, #1a1a1a);
  border-color: rgba(255, 255, 255, 0.12);
}
[data-theme="dark"] .tc-dropdown__item:hover:not(.tc-dropdown__item--ativo) {
  background: rgba(19, 81, 180, 0.15);
}
[data-theme="dark"] .tc-dropdown__item--ativo {
  background: rgba(19, 81, 180, 0.15);
}
[data-theme="dark"] .tc-dropdown__item-nome {
  color: rgba(255, 255, 255, 0.95);
}
[data-theme="dark"] .tc-dropdown__item-detalhes {
  color: rgba(255, 255, 255, 0.55);
}
[data-theme="dark"] .tc-dropdown__erro {
  background: rgba(183, 28, 28, 0.15);
  color: #ef9a9a;
}

/* Transition */
.tc-slide-enter-active,
.tc-slide-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.tc-slide-enter-from,
.tc-slide-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

/* Responsive */
@media (max-width: 575px) {
  .tc-dropdown__label,
  .tc-dropdown__esfera,
  .tc-dropdown__seta {
    display: none;
  }
  .tc-dropdown__trigger {
    padding: 0.4rem 0.6rem;
  }
  .tc-dropdown__menu {
    min-width: 260px;
    right: -0.5rem;
  }
}
</style>
