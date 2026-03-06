<template>

    <header class="br-header">
        <div class="container-lg">
            <div class="header-top">
                <div class="header-menu menu" style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="header-info">
                        <div class="header-title text-blue-warm-vivid-70" @click="goToHome">{{ title }}</div>
                        <div class="header-subtitle text-gray-80">{{ subtitle }}</div>
                    </div>
                    <div class="header-actions">
                        <slot name="actions"></slot>                        
                    </div>                    

                </div>
                <div class="logo" v-if="logoGOV">
                    <img :src="logoGOV" alt="Logo GOV" height="40" />
                </div>
            </div>
        </div>
    </header>

</template>

<script setup lang="ts">
defineOptions({
    name: 'Header'
});
defineProps({
    title: String,
    subtitle: String,
    logoGOV: {
        type: String,
        required: false
    }
});
const goToHome = () => {
    window.location.href = '/';
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
.menu{
    display: flex !important;
    justify-content: space-between !important;
    align-items: center;
}

@media (max-width: 768px) {

    .header-subtitle {
        display: block !important;
    }
}

</style>