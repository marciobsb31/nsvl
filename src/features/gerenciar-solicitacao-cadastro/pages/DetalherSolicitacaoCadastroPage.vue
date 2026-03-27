<template>
  <DefaultLayout>
    <HeaderPage title="Detalhar/Avaliar Solicitação de Cadastros" customClass="mb-3">
      <template v-slot:actions>
        <br-button :color-mode="$appTheme" emphasis="primary" aria-label="Cadastrar usuário">
          Voltar
        </br-button>
      </template>
    </HeaderPage>
    <Card>

    </Card>


  </DefaultLayout>
</template>

<script setup lang="ts">
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import HeaderPage from '@/core/components/HeaderPage/HeaderPage.vue'
import Card from '@/core/components/Card/Card.vue'
import type { SolicitacaoCadastroDetalhe } from '@/services/SolicitacaoCadastroService';
import { ref } from 'vue';

const detalheSelecionado = ref<(SolicitacaoCadastroDetalhe & { cpf?: string }) | null>(null)
const avaliando = ref(false)
</script>
