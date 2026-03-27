<template>
    <div :class="props.customClass">
     <br-breadcrumb :crumbs="items"></br-breadcrumb>
    </div>
</template>

<script setup lang="ts">
import { onMounted, watch } from 'vue';
import { useBreadcrumb } from '@/core/composables/useBreadcrumb';
import { useTheme } from '@/core/composables/useTheme';

const { items } = useBreadcrumb();
const { mode } = useTheme();

const props = defineProps({
  customClass: {
    type: String,
    default: '',
  },
});

let styleEl: HTMLStyleElement | null = null;

const updateStyle = () => {
  if (!styleEl) return;

  styleEl.textContent = `
      .crumb-list {
        padding: 0px !important;
      }
    `;
};
const darkStyle = () => {
    if (!styleEl) return;
    styleEl.textContent = `
      .crumb-list {
        padding: 0px !important;
      }
        .crumb {
          color: var(--pure-0) !important;
        }
    `;
};

watch(mode, () => {
    if (mode.value === 'dark') {
        darkStyle();
    } else {
        updateStyle();
    }
});

onMounted(() => {
  setTimeout(() => {
    const breadcrumb = document.querySelector('br-breadcrumb');
    const shadow = breadcrumb?.shadowRoot;

    if (!shadow) return;

    styleEl = document.createElement('style');
    updateStyle();
    shadow.appendChild(styleEl);
  }, 0);
});

</script>

<style scoped>
.breadcrumb-custom {
  padding: 0px !important;
}
</style>

