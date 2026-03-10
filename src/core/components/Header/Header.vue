<template>

    <header class="br-header">
        <div class="container-lg">
            <div class="header-top">
                <div class="header-menu" :class="{'menu-desktop': isDesktop, 'menu-mobile': isMobile }">
                    <div class="header-info">
                        <img v-if="logo" :src="logo" alt="Logo GOV" class="logo" @click="goToHome" />
                        <template v-else>
                            <div class="header-title text-blue-warm-vivid-70" @click="goToHome">{{ title }}</div>
                            <div class="header-subtitle text-gray-80">{{ subtitle }}</div>
                        </template>
                    </div>
      
                    <div class="header-actions">                         
                        <slot name="actions"></slot>                        
                    </div>                    

                </div>
                <div v-if="logoGov && isDesktop">
                    <img :src="logoGov" alt="Logo GOV" class="logo-gov" />
                </div>
                <div>
                     <button class="br-button circle small ml-3" type="button" aria-label="Tema Dark" ><i class="fas fa-adjust" aria-hidden="true" @click="toggleTheme" ></i>
                         </button>
                </div>
            </div>
        </div>

    </header>
</template>

<script setup lang="ts">
import logo from '@/assets/images/logo/logo_novo_viver.png'
import { useBreakpoint } from '@/core/composables/useBreakpoint'
import { useTheme } from '@/core/composables/useTheme';

const { isMobile, isDesktop } = useBreakpoint()
const { setMode, mode } = useTheme()



defineOptions({
    name: 'Header'
});
defineProps({
    title: String,
    subtitle: String,
    logoGov: {
        type: String,
        required: false
    }
});
const goToHome = () => {
    window.location.href = '/';
};

const toggleTheme = () => {
    setMode(mode.value === 'dark' ? 'light' : 'dark');
};


</script>

<style scoped>
.header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.header-title {
    cursor: pointer;
}
.menu-desktop{
    display: flex !important;
    justify-content: space-between !important;
    align-items: center;
}
.menu-mobile{
    display: flex !important;
    justify-content: center !important;
    align-items: center;
}

.logo-gov{
    height: 60px;
}

.logo{
    height: 60px;
    cursor: pointer;
}

@media (max-width: 768px) {

    .header-subtitle {
        display: block !important;
    }
    .logo-gov{
        height: 50px;
    }
}

</style>