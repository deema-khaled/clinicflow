<script setup lang="ts">
import PageHeading from '@/components/PageHeading.vue';
import DeletePatientDialog from '@/components/patients/DeletePatientDialog.vue';
import PatientForm from '@/components/patients/PatientForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { PatientDetails, PatientFormData } from '@/types/patient';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    patient: PatientDetails;
    canEditNotes: boolean;
    canDelete: boolean;
}>();

const fullName = `${props.patient.first_name} ${props.patient.last_name}`;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Patients', href: '/patients' },
    { title: fullName, href: `/patients/${props.patient.id}/edit` },
];

const form = useForm<PatientFormData>({
    first_name: props.patient.first_name,
    last_name: props.patient.last_name,
    phone: props.patient.phone,
    email: props.patient.email ?? '',
    date_of_birth: props.patient.date_of_birth ?? '',
    gender: props.patient.gender ?? '',
    notes: props.patient.notes ?? '',
});

const submit = () => form.put(route('patients.update', props.patient.id));
</script>

<template>
    <Head :title="`Edit ${fullName}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-3xl space-y-6 p-4 md:p-6">
            <PageHeading :title="fullName" description="Update this patient's details" />
            <PatientForm :form="form" :can-edit-notes="canEditNotes" submit-label="Save changes" @submit="submit" />

            <div v-if="canDelete" class="space-y-3 rounded-lg border border-red-200 p-4 dark:border-red-900">
                <div>
                    <p class="font-medium">Delete patient</p>
                    <p class="text-sm text-muted-foreground">Only clinic owners can delete patients.</p>
                </div>
                <DeletePatientDialog :patient-id="patient.id" :patient-name="fullName" />
            </div>
        </div>
    </AppLayout>
</template>
