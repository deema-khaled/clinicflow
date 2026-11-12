<script setup lang="ts">
import StatusBadge from '@/components/appointments/StatusBadge.vue';
import PageHeading from '@/components/PageHeading.vue';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { AppointmentStatus, CalendarAppointment, CalendarDay, Option, Person } from '@/types/appointment';
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarCheck, CalendarX, ChevronLeft, ChevronRight, MoreHorizontal, Pencil, Plus, Stethoscope, UserX } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    days: CalendarDay[];
    week: { start: string; label: string; previous: string; next: string };
    doctors: Person[];
    filters: { doctor: number | null };
    statuses: Option<AppointmentStatus>[];
    can: { delete: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Appointments', href: '/appointments' }];

const doctor = ref<number | ''>(props.filters.doctor ?? '');

const visit = (params: Record<string, unknown>) =>
    router.get(route('appointments.index'), params, { preserveState: true, preserveScroll: true, replace: true });

const query = computed(() => (doctor.value ? { doctor: doctor.value } : {}));
const goToWeek = (week?: string) => visit({ ...query.value, ...(week ? { week } : {}) });

watch(doctor, () => visit({ week: props.week.start, ...query.value }));

const total = computed(() => props.days.reduce((n, d) => n + d.appointments.filter((a) => a.status !== 'cancelled').length, 0));

const setStatus = (appointment: CalendarAppointment, status: AppointmentStatus) =>
    router.patch(route('appointments.status', appointment.id), { status }, { preserveScroll: true, preserveState: true });

const accent: Record<AppointmentStatus, string> = {
    scheduled: 'border-l-sky-500',
    completed: 'border-l-emerald-500',
    cancelled: 'border-l-zinc-300 opacity-60',
    no_show: 'border-l-amber-500',
};

const selectClass =
    'h-9 rounded-md border border-input bg-background px-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';
</script>

<template>
    <Head title="Appointments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <PageHeading title="Appointments" :description="`${total} appointment${total === 1 ? '' : 's'} this week`" />
                <Button as-child>
                    <Link :href="route('appointments.create')"><Plus class="h-4 w-4" /> Book appointment</Link>
                </Button>
            </div>

            <!-- Toolbar -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center rounded-lg border">
                    <Button variant="ghost" size="icon" class="rounded-r-none" aria-label="Previous week" @click="goToWeek(week.previous)">
                        <ChevronLeft class="h-4 w-4" />
                    </Button>
                    <span class="min-w-44 px-2 text-center text-sm font-medium tabular-nums">{{ week.label }}</span>
                    <Button variant="ghost" size="icon" class="rounded-l-none" aria-label="Next week" @click="goToWeek(week.next)">
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                </div>
                <Button variant="outline" size="sm" @click="goToWeek()">Today</Button>

                <select v-model="doctor" :class="selectClass" class="ml-auto" aria-label="Filter by doctor">
                    <option value="">All doctors</option>
                    <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
            </div>

            <!-- Week grid -->
            <div class="grid gap-3 md:grid-cols-7">
                <section
                    v-for="day in days"
                    :key="day.date"
                    class="flex min-h-40 flex-col rounded-xl border bg-card md:min-h-[28rem]"
                    :class="day.is_today ? 'border-primary/60 ring-1 ring-primary/20' : 'border-sidebar-border/70'"
                >
                    <header class="flex items-baseline justify-between border-b px-3 py-2">
                        <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ day.weekday }}</span>
                        <span
                            class="flex h-7 min-w-7 items-center justify-center rounded-full px-1 text-sm font-semibold tabular-nums"
                            :class="day.is_today ? 'bg-primary text-primary-foreground' : ''"
                        >
                            {{ day.day }}
                        </span>
                    </header>

                    <div class="flex flex-1 flex-col gap-2 p-2">
                        <article
                            v-for="appointment in day.appointments"
                            :key="appointment.id"
                            class="group relative rounded-lg border border-l-4 bg-background p-2 text-xs shadow-sm transition hover:shadow-md"
                            :class="accent[appointment.status]"
                        >
                            <div class="flex items-start justify-between gap-1">
                                <p class="whitespace-nowrap font-semibold tabular-nums" :title="`${appointment.start}–${appointment.end}`">
                                    {{ appointment.start }}<span class="font-normal text-muted-foreground"> – {{ appointment.end }}</span>
                                </p>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <button
                                            type="button"
                                            class="-m-1 rounded p-1 text-muted-foreground opacity-70 hover:bg-muted hover:opacity-100"
                                            :aria-label="`Actions for ${appointment.patient.name}`"
                                        >
                                            <MoreHorizontal class="h-4 w-4" />
                                        </button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-44">
                                        <DropdownMenuItem as-child>
                                            <Link :href="route('appointments.edit', appointment.id)" class="w-full"
                                                ><Pencil class="mr-2 h-4 w-4" /> Edit</Link
                                            >
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem :disabled="appointment.status === 'completed'" @click="setStatus(appointment, 'completed')">
                                            <CalendarCheck class="mr-2 h-4 w-4" /> Mark completed
                                        </DropdownMenuItem>
                                        <DropdownMenuItem :disabled="appointment.status === 'no_show'" @click="setStatus(appointment, 'no_show')">
                                            <UserX class="mr-2 h-4 w-4" /> Mark no-show
                                        </DropdownMenuItem>
                                        <DropdownMenuItem v-if="appointment.status === 'cancelled'" @click="setStatus(appointment, 'scheduled')">
                                            <CalendarCheck class="mr-2 h-4 w-4" /> Restore
                                        </DropdownMenuItem>
                                        <DropdownMenuItem v-else class="text-destructive" @click="setStatus(appointment, 'cancelled')">
                                            <CalendarX class="mr-2 h-4 w-4" /> Cancel
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                            <Link :href="route('appointments.edit', appointment.id)" class="mt-1 block">
                                <p class="truncate text-sm font-medium" :class="appointment.status === 'cancelled' ? 'line-through' : ''">
                                    {{ appointment.patient.name }}
                                </p>
                                <p v-if="appointment.reason" class="truncate text-muted-foreground">{{ appointment.reason }}</p>
                                <p class="mt-1 flex items-center gap-1 truncate text-muted-foreground">
                                    <Stethoscope class="h-3 w-3 shrink-0" /> {{ appointment.doctor.name }}
                                </p>
                            </Link>
                            <div v-if="appointment.status !== 'scheduled'" class="mt-1.5"><StatusBadge :status="appointment.status" /></div>
                        </article>

                        <Link
                            :href="route('appointments.create', { date: day.date })"
                            class="mt-auto flex items-center justify-center gap-1 rounded-lg border border-dashed py-2 text-xs text-muted-foreground opacity-60 transition hover:border-primary hover:text-primary hover:opacity-100"
                            :aria-label="`Book on ${day.weekday} ${day.day} ${day.month}`"
                        >
                            <Plus class="h-3 w-3" /> Book
                        </Link>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
