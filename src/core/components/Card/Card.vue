<template>
    <div class="br-card m-0 p-3" :class="customClass">
        <div class="card-header" v-if="title">
            <div class="d-flex">
                <div class="">
                    <div class="text-weight-semi-bold text-up-02">{{ title }}</div>
                    <div>{{ subtitle }}</div>
                </div>
            </div>
            <span class="br-divider my-3"></span>
        </div>
        <div class="card-content">
            <slot></slot>

        </div>
    </div>
</template>
<script setup lang="ts">
defineProps({
    customClass: { type: String, required: false },
    title: { type: String, required: false },
    subtitle: { type: String, required: false },
})
</script>
<style scoped></style>