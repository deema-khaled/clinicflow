<script setup lang="ts">
import AppointmentForm from '@/components/appointments/AppointmentForm.vue';
import PageHeading from '@/components/PageHeading.vue';
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
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { AppointmentFormData, AppointmentFormOptions, AppointmentStatus } from '@/types/appointment';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';

const props = defineProps<
    AppointmentFormOptions & {
        appointment: {
            id: number;
            patient_id: number;
            doctor_id: number;
            date: string;
            start_time: string;
            duration: number;
            status: AppointmentStatus;
            reason: string | null;
            notes: string | null;
        };
        canDelete: boolean;
    }
>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Appointments', href: '/appointments' },
    { title: 'Edit', href: `/appointments/${props.appointment.id}/edit` },
];

const form = useForm<AppointmentFormData>({
    patient_id: props.appointment.patient_id,
    doctor_id: props.appointment.doctor_id,
    date: props.appointment.date,
    start_time: props.appointment.start_time,
    duration: props.appointment.duration,
    status: props.appointment.status,
    reason: props.appointment.reason ?? '',
    notes: props.appointment.notes ?? '',
});

const submit = () => form.put(route('appointments.update', props.appointment.id));
const destroy = () => router.delete(route('appointments.destroy', props.appointment.id));
</script>

<template>
    <Head title="Edit appointment" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-3xl space-y-6 p-4 md:p-6">
            <PageHeading title="Edit appointment" description="Reschedule, change the doctor, or update the status" />
            <AppointmentForm
                :form="form"
                :patients="patients"
                :doctors="doctors"
                :durations="durations"
                :statuses="statuses"
                show-status
                submit-label="Save changes"
                @submit="submit"
            />

            <div v-if="canDelete" class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-red-200 p-4 dark:border-red-900">
                <div>
                    <p class="font-medium">Delete appointment</p>
                    <p class="text-sm text-muted-foreground">To keep a record, set the status to “Cancelled” instead.</p>
                </div>
                <Dialog>
                    <DialogTrigger as-child>
                        <Button variant="destructive"><Trash2 class="h-4 w-4" /> Delete</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete this appointment?</DialogTitle>
                            <DialogDescription>This permanently removes it. This can’t be undone.</DialogDescription>
                        </DialogHeader>
                        <DialogFooter class="gap-2">
                            <DialogClose as-child><Button variant="secondary">Keep it</Button></DialogClose>
                            <Button variant="destructive" @click="destroy">Delete</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    </AppLayout>
</template>
