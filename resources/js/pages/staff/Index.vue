<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeading from '@/components/PageHeading.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Role } from '@/types';
import type { Option } from '@/types/appointment';
import { Head, router, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Trash2, UserPlus } from 'lucide-vue-next';
import { ref } from 'vue';

interface StaffMember {
    id: number;
    name: string;
    email: string;
    role: Role;
    is_me: boolean;
    upcoming_appointments: number;
    joined: string;
}

defineProps<{ staff: StaffMember[]; roles: Option<Role>[] }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Staff', href: '/staff' }];

const addOpen = ref(false);
const form = useForm({ name: '', email: '', role: 'doctor' as Role, password: '' });

const add = () =>
    form.post(route('staff.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            addOpen.value = false;
        },
    });

const changeRole = (member: StaffMember, role: string) => router.patch(route('staff.update', member.id), { role }, { preserveScroll: true });
const remove = (member: StaffMember) => router.delete(route('staff.destroy', member.id), { preserveScroll: true });

const roleStyle: Record<Role, string> = {
    owner: 'bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300',
    doctor: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
    receptionist: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
};

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';
</script>

<template>
    <Head title="Staff" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <PageHeading title="Staff" description="Doctors and receptionists who work in your clinic" />

                <Dialog v-model:open="addOpen">
                    <DialogTrigger as-child>
                        <Button><UserPlus class="h-4 w-4" /> Add team member</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <form class="space-y-5" @submit.prevent="add">
                            <DialogHeader>
                                <DialogTitle>Add team member</DialogTitle>
                                <DialogDescription>They can sign in right away with this email and password.</DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-2">
                                <Label for="staff_name">Full name</Label>
                                <Input id="staff_name" v-model="form.name" required />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="staff_email">Email</Label>
                                <Input id="staff_email" v-model="form.email" type="email" required />
                                <InputError :message="form.errors.email" />
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="staff_role">Role</Label>
                                    <select id="staff_role" v-model="form.role" :class="selectClass">
                                        <option value="doctor">Doctor</option>
                                        <option value="receptionist">Receptionist</option>
                                    </select>
                                    <InputError :message="form.errors.role" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="staff_password">Temporary password</Label>
                                    <Input id="staff_password" v-model="form.password" type="password" required autocomplete="new-password" />
                                    <InputError :message="form.errors.password" />
                                </div>
                            </div>

                            <DialogFooter class="gap-2">
                                <DialogClose as-child><Button type="button" variant="secondary">Cancel</Button></DialogClose>
                                <Button type="submit" :disabled="form.processing">
                                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" /> Add
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <article v-for="member in staff" :key="member.id" class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 bg-card p-4">
                    <div class="flex items-center gap-3">
                        <Avatar class="h-11 w-11">
                            <AvatarFallback class="bg-primary/10 font-semibold text-primary">{{ getInitials(member.name) }}</AvatarFallback>
                        </Avatar>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">
                                {{ member.name }} <span v-if="member.is_me" class="text-xs font-normal text-muted-foreground">(you)</span>
                            </p>
                            <p class="truncate text-sm text-muted-foreground">{{ member.email }}</p>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="roleStyle[member.role]">{{ member.role }}</span>
                    </div>

                    <dl class="grid grid-cols-2 gap-2 text-sm">
                        <div class="rounded-lg bg-muted/50 px-3 py-2">
                            <dt class="text-xs text-muted-foreground">Upcoming</dt>
                            <dd class="font-semibold tabular-nums">{{ member.upcoming_appointments }}</dd>
                        </div>
                        <div class="rounded-lg bg-muted/50 px-3 py-2">
                            <dt class="text-xs text-muted-foreground">Joined</dt>
                            <dd class="font-semibold">{{ member.joined }}</dd>
                        </div>
                    </dl>

                    <div v-if="!member.is_me" class="mt-auto flex items-center gap-2">
                        <select
                            :value="member.role"
                            :class="selectClass"
                            :aria-label="`Role of ${member.name}`"
                            @change="changeRole(member, ($event.target as HTMLSelectElement).value)"
                        >
                            <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                        </select>

                        <Dialog>
                            <DialogTrigger as-child>
                                <Button variant="outline" size="icon" class="shrink-0 text-destructive" :aria-label="`Remove ${member.name}`">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Remove {{ member.name }}?</DialogTitle>
                                    <DialogDescription>
                                        Their account is deleted and they can no longer sign in. Appointments assigned to them are deleted too.
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter class="gap-2">
                                    <DialogClose as-child><Button variant="secondary">Cancel</Button></DialogClose>
                                    <Button variant="destructive" @click="remove(member)">Remove</Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                </article>
            </div>
        </div>
    </AppLayout>
</template>
