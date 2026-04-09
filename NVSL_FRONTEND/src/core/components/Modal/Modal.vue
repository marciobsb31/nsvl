<template>
    <section class="scrim">
        <div class="container">
            <div
                ref="dialogEl"
                class="div br-modal medium"
                aria-modal="true"
                role="dialog"
                tabindex="-1"
                :aria-labelledby="title ? titleId : undefined"
                :aria-label="title ? undefined : 'Modal'"
                @keydown.esc="closeModal"
                @keydown.tab.prevent="onTab"
            >
                <div class="br-modal-header">
                    <div class="modal-title" :id="titleId" v-if="title">{{ title }}</div>
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
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

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

const dialogEl = ref<HTMLElement | null>(null)
const titleId = `modal-title-${Math.random().toString(36).slice(2, 10)}`
const previouslyFocused = ref<HTMLElement | null>(null)

function getFocusableElements() {
    const root = dialogEl.value
    if (!root) return [] as HTMLElement[]
    const selector = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(',')
    return Array.from(root.querySelectorAll<HTMLElement>(selector)).filter((el) => {
        const style = window.getComputedStyle(el)
        return style.display !== 'none' && style.visibility !== 'hidden'
    })
}

function onTab(e: KeyboardEvent) {
    const focusables = getFocusableElements()
    if (!focusables.length) {
        dialogEl.value?.focus()
        return
    }
    const first = focusables[0]!
    const last = focusables[focusables.length - 1]!
    const active = document.activeElement as HTMLElement | null

    if (e.shiftKey) {
        if (!active || active === first || active === dialogEl.value) {
            last.focus()
        } else {
            const idx = focusables.indexOf(active)
            focusables[Math.max(idx - 1, 0)]?.focus()
        }
        return
    }

    if (!active || active === last) {
        first.focus()
        return
    }
    const idx = focusables.indexOf(active)
    focusables[Math.min(idx + 1, focusables.length - 1)]?.focus()
}

onMounted(async () => {
    previouslyFocused.value = (document.activeElement as HTMLElement) ?? null
    await nextTick()
    dialogEl.value?.focus()
})

onBeforeUnmount(() => {
    previouslyFocused.value?.focus?.()
})

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