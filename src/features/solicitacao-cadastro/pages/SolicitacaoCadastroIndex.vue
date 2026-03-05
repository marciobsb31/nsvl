<template>
  <DefaultLayout>
  <section class="container-solicitacao">
    <h1 class="text-blue-warm-vivid-70 text-weight-semi-bold">Solicitação de cadastro</h1>
    <Card title="Dados do solicitante" subtitle="Nome e CPF são obtidos pelo GOV.BR">
      <section class="row">
        <div class="col-6">
          <div class="br-input">
            <label for="input-default">Nome<span class="text-red-50 text-up-01"> *</span></label>
            <input id="input-default" type="text" placeholder="Nome" disabled />
          </div>
        </div>
        <div class="col-6">
          <div class="br-input">
            <label for="input-default">CPF<span class="text-red-50 text-up-01">*</span></label>
            <input id="input-default" type="text" placeholder="CPF" disabled />
          </div>
        </div>
        <div class="col-6">
          <div class="br-input">
            <label for="input-default">E-mail Institucional<span class="text-red-50 text-up-01">*</span></label>
            <input id="input-default" type="text" placeholder="E-mail comporativo" />
          </div>
        </div>
        <div class="col-6">
          <div class="br-input">
            <label for="input-default">Telefone institucional<span class="text-red-50 text-up-01">*</span></label>
            <input id="input-default" type="text" placeholder="Telefone corporativo de contato do solicitante." />
          </div>
        </div>
        <div class="col-6">
          <div class="br-input">
            <label for="input-default">Telefone pessoal<span class="text-red-50 text-up-01">*</span></label>
            <input id="input-default" type="text" placeholder="Telefone de pessoal do solicitante." />
          </div>
        </div>
      </section>
    </Card>
    <Card title="Informação do(a) solicitante" subtitle="Informações de atuação institucional do solicitante"
      custom-class="mt-4">
      <section class="row">
        <div class="col-6">
          <div class="br-input">
            <label for="input-default">Esfera de atuação<span class="text-red-50 text-up-01"> *</span></label>
            <input id="input-default" type="text" placeholder="Esfera de atuação"  />
          </div>
        </div>
        <div class="col-6">
          <div class="br-input">
            <label for="input-default">UF*<span class="text-red-50 text-up-01">*</span></label>
            <input id="input-default" type="text" placeholder="CPF"  />
          </div>
        </div>
        <div class="col-6">
          <div class="br-input">
            <label for="input-default">Município<span class="text-red-50 text-up-01">*</span></label>
            <input id="input-default" type="text" placeholder="Município" />
          </div>
        </div>
        <div class="col-6">
          <div class="br-input">
            <label for="input-default">Orgão de atuação<span class="text-red-50 text-up-01">*</span></label>
            <input id="input-default" type="text" placeholder="Telefone corporativo de contato do solicitante." />
          </div>
        </div>
        <div class="col-6">
          <div class="br-input">
            <label for="input-default">Cargo /Função<span class="text-red-50 text-up-01">*</span></label>
            <input id="input-default" type="text" placeholder="Telefone de pessoal do solicitante." />
          </div>
        </div>
      </section>
    </Card>
    <Card title="Aceite do Termoe uso e Privacidade"
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
   <button class="br-button primary mr-3" type="button">Confirmar/Enviar solicitação
  </button>
    </div>
  </section>
  </DefaultLayout>
</template>
<script setup lang="ts">
import Card from '@/core/components/Card/Card.vue';
import SolicitacaoFormulario from '../components/SolicitacaoFormulario.vue';
import DefaultLayout from '@/layouts/DefaultLayout.vue';

defineOptions({
  name: 'SolicitacaoCadastroIndex'
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
</style>
