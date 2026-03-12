<template>
  <DefaultLayout>
  <section class="container-solicitacao">
    <div class="titulo">
    <h1 class="color-text text-weight-semi-bold">Solicitação de cadastro</h1>
    </div>
    <Card title="Dados do(a) solicitante" subtitle="Nome e CPF são obtidos pelo GOV.BR">
      <FormularioDadosSolicitante :submitForm="submitForm" />
    </Card>
    <Card title="Informação do(a) solicitante" subtitle="Informações de atuação institucional do solicitante"
      custom-class="mt-4">
      <FormularioInformacaoSolicitante :submitForm="submitForm" />
    </Card>
    <Card title="Aceite do Termo e uso e Privacidade"
      subtitle="O aceite ocorre no ato da confirmação e envio da solicitação." custom-class="mt-4">
      <p><strong>Ao confirmar a solicitação você aceita o seguinte termo de uso e privacidade:</strong> os dados
        informados serão utilizados exclusivamente para fins de análise, habilitação e gestão de acesso ao sistema NVSL.
      </p>

      <p>O envio da solicitação implica ciência quanto ao tratamento dos dados pessoais e utilização para fins
      institucionais.</p>
    </Card>
    <div class="mt-3 actions">
       <button class="br-button secondary mr-3" type="button">Cancelar
  </button>
   <button class="br-button primary mr-3" type="button" @click="submit">Confirmar/Enviar solicitação
  </button>
    </div>
  </section>
  </DefaultLayout>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue';
import Card from '@/core/components/Card/Card.vue';
import FormularioDadosSolicitante from '../components/FormularioDadosSolicitante.vue';
import FormularioInformacaoSolicitante from '../components/FormularioInformacaoSolicitante.vue';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { initializeSelect } from '@/core/composables/useGov';


defineOptions({
  name: 'SolicitacaoCadastroIndex'
})
const submitForm = ref(false);

const submit = () => {
  submitForm.value = true;
  // TODO: Implementar lógica de envio
}

onMounted(() => {
  initializeSelect()
})

</script>

<style scoped>
.container-solicitacao {
  padding: 1.5rem;
}
.actions {
  display: flex;
  justify-content: flex-end;
}

.color-text{
  color: var(--color-text-h1)
}

@media (max-width: 768px) {
  .actions {
    flex-direction: column-reverse;
    justify-content: center;
    gap: 1rem;
  }
  .titulo{
    text-align: center;
  }

  .titulo h1{
    font-size: 1.5rem;
  }
}
</style>
