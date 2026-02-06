export interface Room {
    id: number;
    code: string;
    name: string;
}

export interface Building {
    name: string;
    rooms: Room[];
}

export interface Period {
    id: number;
    code: string;
    label: string;
    start_time?: string;
    end_time?: string;
}

export interface WeekDate {
    date: string;
    dayName: string;
    fullDate: string;
}

export type OccupiedStatus = 'approved' | 'pending' | 'course' | 'holiday';

export type OccupiedData = Record<string, Record<string, OccupiedStatus>>;

export interface HighlightInfo {
    date: string;
    slots: string[];
}

export interface SelectedSlot {
    date: string;
    period: string;
    id: number;
    label: string;
}

export type Step = 1 | 2;

export interface ApplicantForm {
    name: string;
    identity_code: string;
    email: string;
    phone: string;
    department: string;
    teacher: string;
    reason: string;
}

export interface Booking {
    id: number;
    date: string;
    status: number;
    borrower?: { name: string; identity_code: string };
    classroom?: { name: string };
    start_slot?: { name: string };
    end_slot?: { name: string };
}