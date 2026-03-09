<template>
    <span class="feedback" :class="[type, { block }, customClass]" role="alert" id="danger"><i class="fas fa-times-circle" aria-hidden="true"></i>
        <span class="message-body" v-html="message" v-if="message"></span>
        <slot v-else></slot>
    </span>
</template>

<script setup lang="ts">
defineProps({
    message: { type: String, required: false },
    type: { type: String, default: 'info' },
    block: { type: Boolean, default: false },
    customClass: { type: String, default: '' },
});
</script>
<style scoped>
.block {
    display: block;
    width: 100%;
}


</style>