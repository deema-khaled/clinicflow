<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { PatientFormData } from '@/types/patient';
import type { InertiaForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const props = defineProps<{
    form: InertiaForm<PatientFormData>;
    canEditNotes: boolean;
    submitLabel: string;
}>();

const emit = defineEmits<{ submit: [] }>();

// The parent owns this Inertia form object; we edit its fields in place.
const model = props.form;

const today = new Date().toISOString().slice(0, 10);
const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';
</script>

<template>
    <form class="space-y-6" @submit.prevent="emit('submit')">
        <div class="grid gap-6 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="first_name">First name</Label>
                <Input id="first_name" v-model="model.first_name" required autofocus autocomplete="off" />
                <InputError :message="model.errors.first_name" />
            </div>

            <div class="grid gap-2">
                <Label for="last_name">Last name</Label>
                <Input id="last_name" v-model="model.last_name" required autocomplete="off" />
                <InputError :message="model.errors.last_name" />
            </div>

            <div class="grid gap-2">
                <Label for="phone">Phone</Label>
                <Input id="phone" v-model="model.phone" type="tel" required placeholder="+962 79 000 0000" dir="ltr" />
                <InputError :message="model.errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email <span class="text-muted-foreground">(optional)</span></Label>
                <Input id="email" v-model="model.email" type="email" placeholder="patient@example.com" />
                <InputError :message="model.errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="date_of_birth">Date of birth</Label>
                <Input id="date_of_birth" v-model="model.date_of_birth" type="date" :max="today" />
                <InputError :message="model.errors.date_of_birth" />
            </div>

            <div class="grid gap-2">
                <Label for="gender">Gender</Label>
                <select id="gender" v-model="model.gender" :class="selectClass">
                    <option value="">Not specified</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
                <InputError :message="model.errors.gender" />
            </div>
        </div>

        <div v-if="canEditNotes" class="grid gap-2">
            <Label for="notes">Medical notes</Label>
            <textarea
                id="notes"
                v-model="model.notes"
                rows="5"
                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                placeholder="Allergies, chronic conditions, current medication…"
            />
            <InputError :message="model.errors.notes" />
        </div>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="model.processing">
                <LoaderCircle v-if="model.processing" class="h-4 w-4 animate-spin" />
                {{ submitLabel }}
            </Button>
            <Button variant="ghost" as-child>
                <Link :href="route('patients.index')">Cancel</Link>
            </Button>
        </div>
    </form>
</template>
