export type AppointmentStatus = 'scheduled' | 'completed' | 'cancelled' | 'no_show';

export interface Option<T = string> {
    value: T;
    label: string;
}

export interface Person {
    id: number;
    name: string;
    phone?: string | null;
}

export interface CalendarAppointment {
    id: number;
    date: string;
    start: string;
    end: string;
    status: AppointmentStatus;
    reason: string | null;
    patient: Person;
    doctor: Person;
}

export interface CalendarDay {
    date: string;
    weekday: string;
    day: string;
    month: string;
    is_today: boolean;
    appointments: CalendarAppointment[];
}

export interface AppointmentFormData {
    patient_id: number | null;
    doctor_id: number | null;
    date: string;
    start_time: string;
    duration: number;
    status: AppointmentStatus;
    reason: string;
    notes: string;
}

export interface AppointmentFormOptions {
    patients: Person[];
    doctors: Person[];
    durations: number[];
    statuses: Option<AppointmentStatus>[];
}
