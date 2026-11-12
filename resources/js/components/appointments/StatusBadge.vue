<script setup lang="ts">
import type { AppointmentStatus } from '@/types/appointment';
import { computed } from 'vue';

const props = defineProps<{ status: AppointmentStatus }>();

const styles: Record<AppointmentStatus, { label: string; class: string }> = {
    scheduled: { label: 'Scheduled', class: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300' },
    completed: { label: 'Completed', class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' },
    cancelled: { label: 'Cancelled', class: 'bg-zinc-100 text-zinc-600 line-through dark:bg-zinc-800 dark:text-zinc-400' },
    no_show: { label: 'No-show', class: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' },
};

const style = computed(() => styles[props.status]);
</script>

<template>
    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" :class="style.class">{{ style.label }}</span>
</template>
