<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <!-- Page title row: title on the left, actions (slot) on the right. -->
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="space-y-1">
            <h1 class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
            <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
        </div>
        <div v-if="$slots.default" class="flex flex-wrap gap-2">
            <slot />
        </div>
    </div>
</template>
