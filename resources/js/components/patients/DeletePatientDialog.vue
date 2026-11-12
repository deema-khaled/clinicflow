<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useForm } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    patientId: number;
    patientName: string;
    iconOnly?: boolean;
}>();

const open = ref(false);
const form = useForm({});

const destroy = () => {
    form.delete(route('patients.destroy', props.patientId), {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button v-if="iconOnly" variant="ghost" size="sm" class="text-destructive hover:text-destructive" :aria-label="`Delete ${patientName}`">
                <Trash2 class="h-4 w-4" />
            </Button>
            <Button v-else variant="destructive"><Trash2 class="h-4 w-4" /> Delete patient</Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete {{ patientName }}?</DialogTitle>
                <DialogDescription>
                    The patient is removed from your list. The record is kept in the database (soft delete), so it can be restored later.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancel</Button>
                </DialogClose>
                <Button variant="destructive" :disabled="form.processing" @click="destroy">Delete</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
