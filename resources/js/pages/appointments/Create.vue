<script setup lang="ts">
import AppointmentForm from '@/components/appointments/AppointmentForm.vue';
import PageHeading from '@/components/PageHeading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { AppointmentFormData, AppointmentFormOptions } from '@/types/appointment';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<AppointmentFormOptions & { defaults: { patient_id: number | null; date: string } }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Appointments', href: '/appointments' },
    { title: 'Book', href: '/appointments/create' },
];

const form = useForm<AppointmentFormData>({
    patient_id: props.defaults.patient_id,
    // Preselect when the clinic has a single doctor.
    doctor_id: props.doctors.length === 1 ? props.doctors[0].id : null,
    date: props.defaults.date,
    start_time: '09:00',
    duration: 30,
    status: 'scheduled',
    reason: '',
    notes: '',
});

const submit = () => form.post(route('appointments.store'));
</script>

<template>
    <Head title="Book appointment" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-3xl space-y-6 p-4 md:p-6">
            <PageHeading title="Book appointment" description="Double-booking is blocked automatically" />
            <AppointmentForm
                :form="form"
                :patients="patients"
                :doctors="doctors"
                :durations="durations"
                :statuses="statuses"
                submit-label="Book appointment"
                @submit="submit"
            />
        </div>
    </AppLayout>
</template>
