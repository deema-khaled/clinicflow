<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Building2, CalendarCheck, Check, Lock, ShieldCheck, Stethoscope, Users } from 'lucide-vue-next';

const features = [
    {
        icon: Building2,
        title: 'One app, many clinics',
        text: 'Every clinic gets its own private workspace. Data is isolated at the query level, so one clinic can never see another’s patients.',
    },
    {
        icon: CalendarCheck,
        title: 'No more double-booking',
        text: 'A weekly calendar per doctor. Overlapping appointments are rejected before they are saved, with a clear message about the clash.',
    },
    {
        icon: Lock,
        title: 'Role-based access',
        text: 'Owners, doctors and receptionists each see what they need. Medical notes never even reach a receptionist’s browser.',
    },
    {
        icon: Users,
        title: 'Patient records',
        text: 'Fast search by name or phone, clean records, and soft deletes so nothing is lost by accident.',
    },
];

const stack = ['Laravel 12', 'Vue 3 + TypeScript', 'Inertia.js', 'Tailwind CSS', 'PostgreSQL', 'Redis', 'Docker', 'Pest'];

const preview = [
    { time: '09:00', patient: 'Sara Haddad', reason: 'Check-up', tone: 'border-l-emerald-500', done: true },
    { time: '09:30', patient: 'Omar Khalil', reason: 'Follow-up', tone: 'border-l-sky-500', done: false },
    { time: '10:30', patient: 'Lina Nasser', reason: 'Cleaning', tone: 'border-l-sky-500', done: false },
    { time: '11:00', patient: 'Yousef Saleh', reason: 'Consultation', tone: 'border-l-amber-500', done: false },
];
</script>

<template>
    <Head title="Clinic management, simplified" />

    <div class="min-h-screen bg-background text-foreground">
        <!-- Nav -->
        <header class="sticky top-0 z-30 border-b border-border/60 bg-background/80 backdrop-blur">
            <nav class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                <Link :href="route('home')" class="flex items-center gap-2 font-semibold">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                        <AppLogoIcon class="h-4 w-4" />
                    </span>
                    ClinicFlow
                </Link>
                <div class="flex items-center gap-2">
                    <Button v-if="$page.props.auth.user" as-child>
                        <Link :href="route('dashboard')">Open dashboard <ArrowRight class="h-4 w-4" /></Link>
                    </Button>
                    <template v-else>
                        <Button variant="ghost" as-child><Link :href="route('login')">Log in</Link></Button>
                        <Button as-child><Link :href="route('register')">Start free</Link></Button>
                    </template>
                </div>
            </nav>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden">
            <div
                class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top,theme(colors.sky.100),transparent_60%)] dark:bg-[radial-gradient(ellipse_at_top,theme(colors.sky.950),transparent_60%)]"
            />
            <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-16 sm:px-6 md:py-24 lg:grid-cols-2">
                <div>
                    <span
                        class="inline-flex items-center gap-2 rounded-full border bg-background px-3 py-1 text-xs font-medium text-muted-foreground"
                    >
                        <ShieldCheck class="h-3.5 w-3.5 text-emerald-600" /> Multi-tenant SaaS for clinics
                    </span>
                    <h1 class="mt-5 text-4xl font-bold tracking-tight sm:text-5xl">
                        Run your clinic’s day
                        <span class="bg-gradient-to-r from-sky-600 to-violet-600 bg-clip-text text-transparent">without the chaos</span>
                    </h1>
                    <p class="mt-5 max-w-lg text-lg text-muted-foreground">
                        Patients, appointments and staff in one place. Book in seconds, never double-book a doctor, and give every team member exactly
                        the access they need.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <Button size="lg" as-child>
                            <Link :href="route('register')">Create your clinic <ArrowRight class="h-4 w-4" /></Link>
                        </Button>
                        <Button size="lg" variant="outline" as-child>
                            <Link :href="route('login')">Try the demo</Link>
                        </Button>
                    </div>
                    <p class="mt-3 text-sm text-muted-foreground">
                        Demo login: <code class="rounded bg-muted px-1.5 py-0.5">owner@demo.test</code> /
                        <code class="rounded bg-muted px-1.5 py-0.5">password</code>
                    </p>
                </div>

                <!-- Product preview -->
                <div class="relative">
                    <div
                        class="absolute -inset-4 -z-10 rounded-3xl bg-gradient-to-br from-sky-200/50 to-violet-200/50 blur-2xl dark:from-sky-900/30 dark:to-violet-900/30"
                    />
                    <div class="rounded-2xl border bg-card p-5 shadow-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-muted-foreground">Today</p>
                                <p class="font-semibold">Dr. Rana’s schedule</p>
                            </div>
                            <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-800 dark:bg-sky-950 dark:text-sky-300"
                                >4 appointments</span
                            >
                        </div>
                        <ul class="mt-4 space-y-2">
                            <li
                                v-for="item in preview"
                                :key="item.time"
                                class="flex items-center gap-3 rounded-lg border border-l-4 bg-background px-3 py-2.5"
                                :class="item.tone"
                            >
                                <span class="w-12 text-sm font-semibold tabular-nums">{{ item.time }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ item.patient }}</p>
                                    <p class="text-xs text-muted-foreground">{{ item.reason }}</p>
                                </div>
                                <Check v-if="item.done" class="h-4 w-4 text-emerald-600" />
                            </li>
                        </ul>
                        <div
                            class="mt-4 flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700 dark:bg-red-950 dark:text-red-300"
                        >
                            <Stethoscope class="h-3.5 w-3.5 shrink-0" /> 10:45 can’t be booked: Dr. Rana is with Lina Nasser until 11:00.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <h2 class="text-center text-3xl font-bold tracking-tight">Built for how clinics actually work</h2>
            <p class="mx-auto mt-3 max-w-2xl text-center text-muted-foreground">
                Front desk, doctors and owners share one system, each with the right view.
            </p>
            <div class="mt-12 grid gap-6 sm:grid-cols-2">
                <div v-for="f in features" :key="f.title" class="rounded-2xl border bg-card p-6 transition hover:shadow-md">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <component :is="f.icon" class="h-5 w-5" />
                    </span>
                    <h3 class="mt-4 font-semibold">{{ f.title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ f.text }}</p>
                </div>
            </div>
        </section>

        <!-- Stack -->
        <section class="border-y bg-muted/30">
            <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
                <p class="text-center text-xs font-medium uppercase tracking-widest text-muted-foreground">Built with</p>
                <ul class="mt-4 flex flex-wrap justify-center gap-2">
                    <li v-for="s in stack" :key="s" class="rounded-full border bg-background px-3 py-1 text-sm">{{ s }}</li>
                </ul>
            </div>
        </section>

        <!-- CTA -->
        <section class="mx-auto max-w-6xl px-4 py-20 text-center sm:px-6">
            <h2 class="text-3xl font-bold tracking-tight">Set up your clinic in under a minute</h2>
            <p class="mt-3 text-muted-foreground">Register, add your team, and book the first appointment.</p>
            <Button size="lg" class="mt-8" as-child>
                <Link :href="route('register')">Get started <ArrowRight class="h-4 w-4" /></Link>
            </Button>
        </section>

        <footer class="border-t">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-6 text-sm text-muted-foreground sm:px-6">
                <span>© {{ new Date().getFullYear() }} ClinicFlow</span>
                <a href="https://github.com/deema-khaled/clinicflow" class="hover:text-foreground" target="_blank" rel="noopener">Source on GitHub</a>
            </div>
        </footer>
    </div>
</template>
