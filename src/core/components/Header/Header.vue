<template>

    <header class="br-header">
        <div class="container-lg">
            <div class="header-bottom">
                <div class="header-menu menu" style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="header-info">
                        <div class="header-title text-blue-warm-vivid-70" @click="goToHome">{{ title }}</div>
                        <div class="header-subtitle text-gray-80">{{ subtitle }}</div>
                    </div>
                    <div class="logo">
                        <img v-if="logo" :src="logo" alt="Logo" height="70" />
                        <p>NVSL</p>

                    </div>
                </div>

            </div>
        </div>
    </header>

</template>

<script setup lang="ts">
defineProps({
    title: String,
    subtitle: String,
    logo: {
        type: String,
        required: false
    }
});
const goToHome = () => {
    window.location.href = '/';
};

</script>

<style scoped>
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
@media (max-width: 480px) {
    .logo {
        display: none;
    }
    
}
</style>