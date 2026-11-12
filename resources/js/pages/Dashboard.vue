<script setup lang="ts">
import StatusBadge from '@/components/appointments/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import type { AppointmentStatus } from '@/types/appointment';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CalendarClock, CalendarDays, CalendarPlus, CheckCircle2, Stethoscope, UserPlus, Users } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    stats: {
        patients: number;
        new_patients_this_month: number;
        today: number;
        today_remaining: number;
        this_week: number;
        completion_rate: number | null;
    };
    week: { label: string; date: string; count: number; is_today: boolean }[];
    todayAppointments: {
        id: number;
        start: string;
        end: string;
        status: AppointmentStatus;
        reason: string | null;
        patient: string | null;
        doctor: string | null;
    }[];
    recentPatients: { id: number; name: string; added: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const page = usePage<SharedData>();
const firstName = computed(() => page.props.auth.user.name.split(' ')[0]);

const greeting = computed(() => {
    const hour = new Date().getHours();
    return hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
});

const todayLabel = new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' });

const maxCount = computed(() => Math.max(1, ...props.week.map((d) => d.count)));

const cards = computed(() => [
    {
        label: 'Today',
        value: props.stats.today,
        hint: `${props.stats.today_remaining} still to come`,
        icon: CalendarClock,
        tone: 'text-sky-600 bg-sky-100 dark:bg-sky-950',
    },
    {
        label: 'This week',
        value: props.stats.this_week,
        hint: 'appointments booked',
        icon: CalendarDays,
        tone: 'text-violet-600 bg-violet-100 dark:bg-violet-950',
    },
    {
        label: 'Patients',
        value: props.stats.patients,
        hint: `+${props.stats.new_patients_this_month} this month`,
        icon: Users,
        tone: 'text-emerald-600 bg-emerald-100 dark:bg-emerald-950',
    },
    {
        label: 'Attendance',
        value: props.stats.completion_rate === null ? '—' : `${props.stats.completion_rate}%`,
        hint: 'completed vs. no-show this month',
        icon: CheckCircle2,
        tone: 'text-amber-600 bg-amber-100 dark:bg-amber-950',
    },
]);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Greeting -->
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm text-muted-foreground">{{ todayLabel }}</p>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ greeting }}, {{ firstName }} 👋</h1>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('patients.create')"><UserPlus class="h-4 w-4" /> New patient</Link>
                    </Button>
                    <Button as-child>
                        <Link :href="route('appointments.create')"><CalendarPlus class="h-4 w-4" /> Book appointment</Link>
                    </Button>
                </div>
            </div>

            <!-- KPI cards -->
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="card in cards" :key="card.label" class="rounded-xl border border-sidebar-border/70 bg-card p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-muted-foreground">{{ card.label }}</p>
                        <span class="rounded-lg p-2" :class="card.tone"><component :is="card.icon" class="h-4 w-4" /></span>
                    </div>
                    <p class="mt-3 text-3xl font-semibold tabular-nums tracking-tight">{{ card.value }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ card.hint }}</p>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Today's schedule -->
                <section class="rounded-xl border border-sidebar-border/70 bg-card lg:col-span-2">
                    <header class="flex items-center justify-between border-b px-5 py-4">
                        <h2 class="font-semibold">Today’s schedule</h2>
                        <Link :href="route('appointments.index')" class="text-sm text-muted-foreground hover:text-foreground">View calendar →</Link>
                    </header>
                    <ul v-if="todayAppointments.length" class="divide-y">
                        <li v-for="a in todayAppointments" :key="a.id">
                            <Link :href="route('appointments.edit', a.id)" class="flex items-center gap-4 px-5 py-3 transition hover:bg-muted/40">
                                <div class="w-24 shrink-0 text-sm font-semibold tabular-nums">
                                    {{ a.start }}<span class="font-normal text-muted-foreground">–{{ a.end }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium" :class="a.status === 'cancelled' ? 'text-muted-foreground line-through' : ''">
                                        {{ a.patient }}
                                    </p>
                                    <p class="flex items-center gap-1 truncate text-xs text-muted-foreground">
                                        <Stethoscope class="h-3 w-3" /> {{ a.doctor }}<template v-if="a.reason"> · {{ a.reason }}</template>
                                    </p>
                                </div>
                                <StatusBadge :status="a.status" />
                            </Link>
                        </li>
                    </ul>
                    <div v-else class="px-5 py-12 text-center">
                        <CalendarDays class="mx-auto mb-3 h-8 w-8 text-muted-foreground" />
                        <p class="font-medium">No appointments today</p>
                        <p class="mt-1 text-sm text-muted-foreground">Enjoy the quiet, or book someone in.</p>
                    </div>
                </section>

                <div class="flex flex-col gap-4">
                    <!-- This week chart -->
                    <section class="rounded-xl border border-sidebar-border/70 bg-card p-5">
                        <h2 class="font-semibold">This week</h2>
                        <p class="text-xs text-muted-foreground">Appointments per day</p>
                        <div class="mt-4 flex h-36 items-end gap-2" role="img" :aria-label="week.map((d) => `${d.label}: ${d.count}`).join(', ')">
                            <div v-for="d in week" :key="d.date" class="flex flex-1 flex-col items-center gap-1">
                                <span class="text-xs tabular-nums text-muted-foreground">{{ d.count || '' }}</span>
                                <div
                                    class="w-full rounded-md transition-all"
                                    :class="d.is_today ? 'bg-sky-500' : 'bg-sky-200 dark:bg-sky-900'"
                                    :style="{ height: `${Math.max(4, (d.count / maxCount) * 100)}px` }"
                                />
                                <span class="text-xs" :class="d.is_today ? 'font-semibold' : 'text-muted-foreground'">{{ d.label }}</span>
                            </div>
                        </div>
                    </section>

                    <!-- Recent patients -->
                    <section class="rounded-xl border border-sidebar-border/70 bg-card">
                        <header class="flex items-center justify-between border-b px-5 py-4">
                            <h2 class="font-semibold">Newest patients</h2>
                            <Link :href="route('patients.index')" class="text-sm text-muted-foreground hover:text-foreground">All →</Link>
                        </header>
                        <ul class="divide-y">
                            <li v-for="p in recentPatients" :key="p.id">
                                <Link
                                    :href="route('patients.edit', p.id)"
                                    class="flex items-center justify-between px-5 py-2.5 text-sm hover:bg-muted/40"
                                >
                                    <span class="truncate font-medium">{{ p.name }}</span>
                                    <span class="shrink-0 text-xs text-muted-foreground">{{ p.added }}</span>
                                </Link>
                            </li>
                            <li v-if="!recentPatients.length" class="px-5 py-6 text-center text-sm text-muted-foreground">No patients yet</li>
                        </ul>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
