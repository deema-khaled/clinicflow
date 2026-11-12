<script setup lang="ts">
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const page = usePage<SharedData>();
const dismissed = ref(false);

const success = computed(() => page.props.flash?.success);
// Errors that no form field displays (e.g. a blocked status change).
const error = computed(() => (page.props.errors as Record<string, string>)?.status);

// Show again whenever a new message arrives.
watch([success, error], () => (dismissed.value = false));

let timer: ReturnType<typeof setTimeout> | undefined;
watch(
    success,
    (value) => {
        clearTimeout(timer);
        if (value) timer = setTimeout(() => (dismissed.value = true), 5000);
    },
    { immediate: true },
);
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        leave-active-class="transition duration-150 ease-in"
        leave-to-class="translate-y-2 opacity-0"
    >
        <div
            v-if="!dismissed && (success || error)"
            class="fixed bottom-4 right-4 z-50 flex max-w-sm items-start gap-3 rounded-xl border bg-background px-4 py-3 text-sm shadow-lg"
            :class="error ? 'border-red-200 dark:border-red-900' : 'border-emerald-200 dark:border-emerald-900'"
            role="status"
        >
            <AlertCircle v-if="error" class="mt-0.5 h-4 w-4 shrink-0 text-red-600" />
            <CheckCircle2 v-else class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
            <p class="flex-1">{{ error ?? success }}</p>
            <button type="button" class="text-muted-foreground hover:text-foreground" aria-label="Dismiss" @click="dismissed = true">
                <X class="h-4 w-4" />
            </button>
        </div>
    </Transition>
</template>
