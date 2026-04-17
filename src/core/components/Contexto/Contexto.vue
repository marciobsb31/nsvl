<template>
  <div v-if="contextoAtualLabel" class="contexto-banner" role="status" aria-live="polite">
    <i class="fas fa-shield-alt contexto-banner__icon" aria-hidden="true"></i>
    <span class="contexto-banner__label">Contexto ativo:</span>
    <strong class="contexto-banner__valor">{{ contextoAtualLabel }}</strong>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuth } from '@/core/composables/useAuth'

const { user, perfilAtivo } = useAuth()

const esferaMap: Record<string, string> = {
  federal: 'Federal',
  estadual: 'Estadual',
  municipal: 'Municipal',
}

const contextoAtualLabel = computed(() => {
  const perfil = perfilAtivo.value
  const esfera = user.value?.contexto.esfera
  const uf = user.value?.contexto.localidade
  const municipio = user.value?.contexto.localidade
  if (!perfil) {
    return esfera ? (esferaMap[esfera] ?? esfera) : ''
  }
  const partes: string[] = []
  if (perfil.nome) partes.push(perfil.nome)
  if (esfera) partes.push(esferaMap[esfera] ?? esfera)
  if (uf) partes.push(uf)
  // if (municipio) partes.push(municipio)
  return partes.join(' — ')
})
</script>

<style scoped>
.contexto-banner {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  margin-bottom: 1rem;
  background: var(--color-primary-pastel, #dbe8fb);
  border-left: 4px solid var(--color-primary-default, #1351b4);
  border-radius: 4px;
  font-size: 0.875rem;
  color: var(--color-secondary-08, #333);
  transition: all 0.3s ease;
}
.contexto-banner__icon {
  color: var(--color-primary-default, #1351b4);
  font-size: 1rem;
  flex-shrink: 0;
}

.contexto-banner__label {
  color: var(--color-secondary-06, #888);
  white-space: nowrap;
}

.contexto-banner__valor {
  color: var(--color-primary-default, #1351b4);
}

[data-theme='dark'] .contexto-banner {
  background: rgba(19, 81, 180, 0.15);
  border-left-color: var(--color-primary-lighten-01, #4d7fd6);
}

[data-theme='dark'] .contexto-banner__icon,
[data-theme='dark'] .contexto-banner__valor {
  color: var(--color-primary-lighten-01, #4d7fd6);
}

[data-theme='dark'] .contexto-banner__label {
  color: rgba(255, 255, 255, 0.6);
}

[data-theme='light'] .contexto-banner {
  background: var(--gray-warm-10);
}
</style>
