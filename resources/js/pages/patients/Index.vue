<script setup lang="ts">
import PageHeading from '@/components/PageHeading.vue';
import DeletePatientDialog from '@/components/patients/DeletePatientDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, PatientListItem } from '@/types/patient';
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarPlus, Pencil, Plus, Search, Users } from 'lucide-vue-next';
import { onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps<{
    patients: Paginated<PatientListItem>;
    filters: { search: string };
    can: { delete: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Patients', href: '/patients' }];

const search = ref(props.filters.search);

// Debounced server-side search; keeps the input focused and the URL shareable.
let timer: ReturnType<typeof setTimeout> | undefined;
watch(search, (value) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(route('patients.index'), value ? { search: value } : {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 300);
});
onBeforeUnmount(() => clearTimeout(timer));

const genderLabel = (gender: string | null) => (gender ? gender[0].toUpperCase() + gender.slice(1) : '—');
</script>

<template>
    <Head title="Patients" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <PageHeading
                    title="Patients"
                    :description="`${patients.total} patient${patients.total === 1 ? '' : 's'} ${filters.search ? 'matching your search' : 'in your clinic'}`"
                />
                <Button as-child>
                    <Link :href="route('patients.create')"><Plus class="h-4 w-4" /> Add patient</Link>
                </Button>
            </div>

            <div class="relative max-w-sm">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" type="search" placeholder="Search name, phone or email…" class="pl-9" aria-label="Search patients" />
            </div>

            <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Name</th>
                            <th class="px-4 py-3 font-medium">Phone</th>
                            <th class="hidden px-4 py-3 font-medium md:table-cell">Email</th>
                            <th class="hidden px-4 py-3 font-medium sm:table-cell">Age</th>
                            <th class="hidden px-4 py-3 font-medium sm:table-cell">Gender</th>
                            <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border/70">
                        <tr v-for="patient in patients.data" :key="patient.id" class="hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">{{ patient.full_name }}</td>
                            <td class="px-4 py-3 tabular-nums" dir="ltr">{{ patient.phone }}</td>
                            <td class="hidden px-4 py-3 text-muted-foreground md:table-cell">{{ patient.email ?? '—' }}</td>
                            <td class="hidden px-4 py-3 tabular-nums sm:table-cell">{{ patient.age ?? '—' }}</td>
                            <td class="hidden px-4 py-3 sm:table-cell">{{ genderLabel(patient.gender) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button variant="ghost" size="sm" as-child>
                                        <Link
                                            :href="route('appointments.create', { patient: patient.id })"
                                            :aria-label="`Book appointment for ${patient.full_name}`"
                                        >
                                            <CalendarPlus class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="sm" as-child>
                                        <Link :href="route('patients.edit', patient.id)" :aria-label="`Edit ${patient.full_name}`">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <DeletePatientDialog v-if="can.delete" :patient-id="patient.id" :patient-name="patient.full_name" icon-only />
                                </div>
                            </td>
                        </tr>

                        <tr v-if="patients.data.length === 0">
                            <td colspan="6" class="px-4 py-16 text-center">
                                <Users class="mx-auto mb-3 h-8 w-8 text-muted-foreground" />
                                <p class="font-medium">{{ filters.search ? 'No patients match your search' : 'No patients yet' }}</p>
                                <p class="mt-1 text-muted-foreground">
                                    {{ filters.search ? 'Try a different name or phone number.' : 'Add your first patient to get started.' }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav v-if="patients.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 text-sm" aria-label="Pagination">
                <p class="text-muted-foreground">Showing {{ patients.from }}–{{ patients.to }} of {{ patients.total }}</p>
                <div class="flex flex-wrap gap-1">
                    <template v-for="link in patients.links" :key="link.label">
                        <Button v-if="link.url" :variant="link.active ? 'default' : 'outline'" size="sm" as-child>
                            <Link :href="link.url" preserve-scroll preserve-state><span v-html="link.label" /></Link>
                        </Button>
                        <Button v-else variant="outline" size="sm" disabled><span v-html="link.label" /></Button>
                    </template>
                </div>
            </nav>
        </div>
    </AppLayout>
</template>
