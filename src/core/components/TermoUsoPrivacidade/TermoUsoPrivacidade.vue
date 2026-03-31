<template>
  <div class="termo-uso-privacidade" role="region" :aria-labelledby="tituloId">
    <h3 :id="tituloId" class="termo-uso-privacidade__titulo">Declaração de ciência</h3>
    <p class="termo-uso-privacidade__texto">
      Ao confirmar, você declara ter lido e aceitado o tratamento dos dados conforme a finalidade do NVSL: os dados
      informados serão utilizados exclusivamente para análise, habilitação e gestão de acesso ao sistema.
    </p>
    <p class="termo-uso-privacidade__texto termo-uso-privacidade__texto--muted">
      O envio implica ciência quanto ao tratamento de dados pessoais e uso institucional, em conformidade com a
      legislação aplicável.
    </p>
  </div>
</template>
<script setup lang="ts">
import { useId } from 'vue'
defineOptions({ name: 'TermoUsoPrivacidade' })
const tituloId = useId()
</script>
<style scoped>
.termo-uso-privacidade {
  padding: 1rem 1.125rem;
  border-radius: 8px;
  border: none;
  background: var(--color-primary-pastel-01, #e8f0ff);
}
.termo-uso-privacidade__titulo {
  margin: 0 0 0.75rem;
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-primary-darken-02, #0c326f);
}
.termo-uso-privacidade__texto {
  margin: 0 0 0.75rem;
  font-size: 0.875rem;
  line-height: 1.55;
  color: var(--color-secondary-09, #333);
}
.termo-uso-privacidade__texto:last-child {
  margin-bottom: 0;
}
.termo-uso-privacidade__texto--muted {
  color: var(--color-secondary-07, #555);
  font-size: 0.8125rem;
}
</style>
