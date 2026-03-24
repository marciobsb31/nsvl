<template>
  <section class="formulario-dados-perfil">
    <h3 class="secao-titulo">Dados de Perfil</h3>
    <div class="secao-perfil-linha">
      <div class="field-with-feedback">
        <SelectAutocomplete
          ref="perfilRef"
          v-model="perfil"
          label="Perfil"
          placeholder="Selecione o perfil"
          :options="opcoesPerfilFiltradas"
          input-id="cad-perfil"
          required
        />
        <Feedback v-if="errorsPerfil" :message="errorsPerfil" type="danger" />
      </div>
      <div class="br-input mb-2">
        <label for="cad-vigencia-inicio">Vigência (início)<span class="obrigatorio">*</span></label>
        <div class="input-date-wrapper">
          <input
            id="cad-vigencia-inicio"
            type="date"
            v-model="vigenciaInicio"
            :max="vigenciaFim || undefined"
            aria-label="Data de início da vigência"
            :aria-invalid="!!errorsVigenciaInicio"
            @blur="validateField('vigenciaInicio')"
          />
          <i class="fas fa-calendar-alt input-date-icon" aria-hidden="true"></i>
        </div>
        <Feedback v-if="errorsVigenciaInicio" :message="errorsVigenciaInicio" type="danger" />
      </div>
      <div class="br-input mb-2">
        <label for="cad-vigencia-fim">Vigência (fim)</label>
        <div class="input-date-wrapper">
          <input
            id="cad-vigencia-fim"
            type="date"
            v-model="vigenciaFim"
            :min="vigenciaInicio || undefined"
            aria-label="Data de fim da vigência"
            :aria-invalid="!!errorsVigenciaFim"
            @blur="validateField('vigenciaFim')"
          />
          <i class="fas fa-calendar-alt input-date-icon" aria-hidden="true"></i>
        </div>
        <Feedback v-if="errorsVigenciaFim" :message="errorsVigenciaFim" type="danger" />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useField, useForm } from 'vee-validate'
import SelectAutocomplete from '@/core/components/SelectAutocomplete/SelectAutocomplete.vue'
import Feedback from '@/core/components/Feedback/Feedback.vue'
import { usePerfis } from '@/core/composables/usePerfis'
import type { PerfilOption } from '@/services/PerfilService'

defineOptions({ name: 'FormularioDadosPerfil' })

const props = withDefaults(
  defineProps<{
    usuarioLogado?: {
      esfera_atuacao?: string
      uf_lotacao?: string
      municipio_lotacao?: string
    } | null
  }>(),
  { usuarioLogado: null }
)

const { validateField } = useForm()
const { value: perfil, errorMessage: errorsPerfil } = useField<string | number | null>('perfil')
const { value: vigenciaInicio, errorMessage: errorsVigenciaInicio } = useField<string>('vigenciaInicio')
const { value: vigenciaFim, errorMessage: errorsVigenciaFim } = useField<string>('vigenciaFim')

const { opcoesPerfil, carregarPerfis } = usePerfis()

const esferaUsuarioLogado = computed(() =>
  String(props.usuarioLogado?.esfera_atuacao ?? '').toLowerCase()
)

const opcoesPerfilFiltradas = computed(() => {
  if (esferaUsuarioLogado.value === 'estadual') {
    return opcoesPerfil.value.filter((p) =>
      String(p.label).toLowerCase().includes('estadual')
    )
  }
  if (esferaUsuarioLogado.value === 'municipal') {
    return opcoesPerfil.value.filter((p) =>
      String(p.label).toLowerCase().includes('municipal')
    )
  }
  return opcoesPerfil.value
})

onMounted(() => {
  carregarPerfis()
})
</script>

<style scoped>
.formulario-dados-perfil {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--color-secondary-04, #ddd);
}

.secao-titulo {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0 0 0.5rem;
}

.secao-perfil-linha {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.obrigatorio {
  color: var(--color-primary-default, #1351b4);
}

.field-with-feedback {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.input-date-wrapper {
  position: relative;
}

.input-date-wrapper input[type='date'] {
  padding-right: 2.5rem;
}

.input-date-icon {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-secondary-06, #888);
  pointer-events: none;
}

@media (max-width: 575px) {
  .secao-perfil-linha {
    grid-template-columns: 1fr;
  }
}

@media (min-width: 576px) and (max-width: 991px) {
  .secao-perfil-linha {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
