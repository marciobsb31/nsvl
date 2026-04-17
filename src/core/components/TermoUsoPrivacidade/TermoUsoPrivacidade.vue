<template>
  <div class="termo-uso-privacidade" role="region" :aria-labelledby="tituloId">
    <h3 :id="tituloId" class="termo-uso-privacidade__titulo">Declaração de ciência</h3>
    <p class="termo-uso-privacidade__texto">
      Os dados pessoais no NVSL são tratados em conformidade com a Lei nº 13.709/2018 (LGPD). São
      coletados apenas dados de identificação (CPF, nome, órgão de atuação e cargo) e de contato
      (telefone e e-mail), com a finalidade de acesso à plataforma e de gestão do Plano Nacional dos
      Direitos da Pessoa com Deficiência. <br /><br />
      O NVSL adota medidas de segurança para proteger as informações e garantir a privacidade dos
      usuários.
      <br /><br />
      Ao confirmar a solicitação, você concorda com os termos indicados.
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
