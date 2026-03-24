<template>
    <section class="scrim">
        <div class="container">
            <div class="div br-modal medium" aria-modal="true" role="dialog" aria-labelledby="modalalerttitle">
                <div class="br-modal-header">
                    <div class="modal-title" id="modalalerttitle" v-if="title">{{ title }}</div>
                    <button class="br-button close circle" type="button" data-dismiss="br-modal" aria-label="Fechar" @click="closeModal"><i
                            class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="br-modal-body">
                    <p v-if="content">{{ content }}</p>
                    <slot v-else></slot>
                </div>
                <div v-if="showActions" class="br-modal-footer justify-content-end">
                    <button class="br-button secondary" type="button" @click="closeModal">Cancelar
                    </button>
                    <button class="br-button primary ml-2" type="button" @click="confirmAction">OK
                    </button>
                </div>
                <div v-else class="br-modal-footer">
                    <slot name="actions"></slot>
                </div>
            </div>
        </div>
    </section>

</template>
<script setup lang="ts">
defineProps({
    title: {
        type: String,
        required: false,
    },
    content: {
        type: String,
        required: false,
    },
    showActions: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['close', 'confirm']);

const closeModal = () => {
    // Emit close event
    emit('close');
};
const confirmAction = () => {
    // Emit confirm event
    emit('confirm');
};

</script>
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