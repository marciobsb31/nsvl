<template>
  <DefaultLayout>
    <HeaderPage title="Cadastrar Usuário" customClass="mb-3">
      <template v-slot:actions>
        <br-button :color-mode="$appTheme ==='dark' ? $appTheme : undefined" emphasis="primary" aria-label="Cadastrar usuário" @click="$router.push('/gerenciar-cadastros')">
          Voltar
        </br-button>
      </template>
    </HeaderPage>
    <Card>
      <FormularioCadastrarUsuario
              :usuario-logado="user ?? undefined"
              @sucesso="onCadastroSucesso"
            />

    </Card>


  </DefaultLayout>
</template>

<script setup lang="ts">
defineOptions({ name: 'CadastrarUsuarioPage' })
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import HeaderPage from '@/core/components/HeaderPage/HeaderPage.vue'
import Card from '@/core/components/Card/Card.vue'
import { BrButton } from '@govbr-ds/webcomponents-vue'
import FormularioCadastrarUsuario from '../components/FormularioCadastrarUsuario.vue'
import { useAuth } from '@/core/composables/useAuth'
import { useRouter } from 'vue-router'

const { user } = useAuth()
const router = useRouter()

function onCadastroSucesso() {
  router.push('/gerenciar-cadastros')
}

</script>