<template>

    <footer class="br-footer pt-0" :class="{ 'inverted': inverted }">
        <div class="container-lg">
            <div class="info">
                <slot name="info"></slot>
            </div>
        </div>
    </footer>

</template>

<script setup lang="ts">
defineOptions({
    name: 'FooterComponent'
});

defineProps({
    inverted: {
        type: Boolean,
        required: false,
        default: false
    }
});

</script>