<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AppointmentFormData, AppointmentFormOptions } from '@/types/appointment';
import type { InertiaForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { LoaderCircle, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<
    AppointmentFormOptions & {
        form: InertiaForm<AppointmentFormData>;
        submitLabel: string;
        showStatus?: boolean;
    }
>();

const emit = defineEmits<{ submit: [] }>();

// The parent owns this Inertia form object; we edit its fields in place.
const model = props.form;

// Client-side filter so long patient lists stay usable.
const patientSearch = ref('');
const filteredPatients = computed(() => {
    const q = patientSearch.value.trim().toLowerCase();
    const list = q ? props.patients.filter((p) => p.name.toLowerCase().includes(q) || p.phone?.includes(q)) : props.patients;
    // Keep the selected patient visible even when filtered out.
    const selected = props.patients.find((p) => p.id === props.form.patient_id);
    return selected && !list.includes(selected) ? [selected, ...list] : list;
});

const endTime = computed(() => {
    if (!props.form.start_time) return null;
    const [h, m] = props.form.start_time.split(':').map(Number);
    const total = h * 60 + m + Number(props.form.duration);
    return `${String(Math.floor(total / 60) % 24).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
});

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';
</script>

<template>
    <form class="space-y-6" @submit.prevent="emit('submit')">
        <div class="grid gap-2">
            <Label for="patient_id">Patient</Label>
            <div class="relative">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    v-model="patientSearch"
                    type="search"
                    placeholder="Filter patients by name or phone…"
                    class="pl-9"
                    aria-label="Filter patients"
                />
            </div>
            <select id="patient_id" v-model="model.patient_id" :class="selectClass" required size="5" class="h-auto py-1">
                <option v-for="p in filteredPatients" :key="p.id" :value="p.id" class="rounded px-2 py-1">{{ p.name }} · {{ p.phone }}</option>
            </select>
            <p v-if="patients.length === 0" class="text-sm text-muted-foreground">
                No patients yet. <Link :href="route('patients.create')" class="underline">Add one first</Link>.
            </p>
            <InputError :message="model.errors.patient_id" />
        </div>

        <div class="grid gap-2">
            <Label for="doctor_id">Doctor</Label>
            <select id="doctor_id" v-model="model.doctor_id" :class="selectClass" required>
                <option :value="null" disabled>Choose a doctor</option>
                <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
            </select>
            <InputError :message="model.errors.doctor_id" />
        </div>

        <div class="grid gap-6 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label for="date">Date</Label>
                <Input id="date" v-model="model.date" type="date" required />
                <InputError :message="model.errors.date" />
            </div>
            <div class="grid gap-2">
                <Label for="start_time">Start time</Label>
                <Input id="start_time" v-model="model.start_time" type="time" step="300" required />
                <InputError :message="model.errors.start_time" />
            </div>
            <div class="grid gap-2">
                <Label for="duration">Duration</Label>
                <select id="duration" v-model.number="model.duration" :class="selectClass">
                    <option v-for="d in durations" :key="d" :value="d">{{ d }} min</option>
                </select>
                <p v-if="endTime" class="text-xs text-muted-foreground">Ends at {{ endTime }}</p>
            </div>
        </div>

        <div class="grid gap-6" :class="showStatus ? 'sm:grid-cols-2' : ''">
            <div class="grid gap-2">
                <Label for="reason">Reason <span class="text-muted-foreground">(optional)</span></Label>
                <Input id="reason" v-model="model.reason" placeholder="Check-up, follow-up, cleaning…" />
                <InputError :message="model.errors.reason" />
            </div>
            <div v-if="showStatus" class="grid gap-2">
                <Label for="status">Status</Label>
                <select id="status" v-model="model.status" :class="selectClass">
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <InputError :message="model.errors.status" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="notes">Notes <span class="text-muted-foreground">(optional)</span></Label>
            <textarea
                id="notes"
                v-model="model.notes"
                rows="3"
                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                placeholder="Anything the doctor should know before the visit"
            />
            <InputError :message="model.errors.notes" />
        </div>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="model.processing">
                <LoaderCircle v-if="model.processing" class="h-4 w-4 animate-spin" />
                {{ submitLabel }}
            </Button>
            <Button variant="ghost" as-child>
                <Link :href="route('appointments.index')">Cancel</Link>
            </Button>
        </div>
    </form>
</template>
