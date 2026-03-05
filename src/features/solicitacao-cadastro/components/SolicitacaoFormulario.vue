<template>
    <section class="row">
        <div class="col-6">
            <div class="br-input">
                <label for="input-default">Nome<span class="text-red-50 text-up-01"> *</span></label>
                <input id="input-default" type="text" placeholder="Nome" disabled/>
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

</template>
<script setup lang="ts">


defineOptions({
  name: 'SolicitacaoFormulario'
})

</script>

<style scoped>

</style>