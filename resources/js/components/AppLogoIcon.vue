<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <!-- ClinicFlow mark: a medical cross -->
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" :class="className" v-bind="$attrs">
        <path
            fill="currentColor"
            d="M9.5 2h5a1 1 0 0 1 1 1v5.5H21a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1h-5.5V21a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1v-5.5H3a1 1 0 0 1-1-1v-5a1 1 0 0 1 1-1h5.5V3a1 1 0 0 1 1-1Z"
        />
    </svg>
</template>
