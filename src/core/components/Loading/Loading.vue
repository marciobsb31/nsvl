<template>
    <section class="scrim">
        <div class="container">
            <div class="loading-container">
                <div class="br-loading medium" role="progressbar" aria-label="carregando exemplo medium exemplo"></div>
            </div>
        </div>
    </section>

</template>
<script setup lang="ts"></script>
<style scoped>
.scrim {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    animation: fade-in 0.3s ease-in-out;
}
.container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%;
}

.loading-container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 10rem;
    width: 16rem;
    background-color: white;
    border-radius: 1rem;
    animation: introduce-box 0.3s ease-in-out;
}

@keyframes fade-in {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
@keyframes introduce-box {
    from {
        transform: scale(0.8);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}
</style>