import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
    clinic: Clinic | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    flash: { success: string | null };
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export type Role = 'owner' | 'doctor' | 'receptionist';

export interface Clinic {
    id: number;
    name: string;
    slug: string;
}

export interface User {
    id: number;
    clinic_id: number;
    role: Role;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
