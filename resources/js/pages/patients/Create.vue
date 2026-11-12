<script setup lang="ts">
import PageHeading from '@/components/PageHeading.vue';
import PatientForm from '@/components/patients/PatientForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { PatientFormData } from '@/types/patient';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{ canEditNotes: boolean }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Patients', href: '/patients' },
    { title: 'Add patient', href: '/patients/create' },
];

const form = useForm<PatientFormData>({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
    date_of_birth: '',
    gender: '',
    notes: '',
});

const submit = () => form.post(route('patients.store'));
</script>

<template>
    <Head title="Add patient" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-3xl space-y-6 p-4 md:p-6">
            <PageHeading title="Add patient" description="Create a new patient record for your clinic" />
            <PatientForm :form="form" :can-edit-notes="canEditNotes" submit-label="Save patient" @submit="submit" />
        </div>
    </AppLayout>
</template>
