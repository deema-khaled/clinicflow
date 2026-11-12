export type Gender = 'male' | 'female';

export interface PatientListItem {
    id: number;
    full_name: string;
    phone: string;
    email: string | null;
    age: number | null;
    gender: Gender | null;
}

export interface PatientFormData {
    first_name: string;
    last_name: string;
    phone: string;
    email: string;
    date_of_birth: string;
    gender: Gender | '';
    notes: string;
}

export interface PatientDetails extends Omit<PatientFormData, 'email' | 'date_of_birth' | 'gender' | 'notes'> {
    id: number;
    email: string | null;
    date_of_birth: string | null;
    gender: Gender | null;
    notes: string | null;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
    current_page: number;
    last_page: number;
}
