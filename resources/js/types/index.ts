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
    code: string;
    label: string;
}

export interface WeekDate {
    date: string;
    dayName: string;
    fullDate: string;
}

export type OccupiedData = Record<string, string[]>;

export interface SelectedSlot {
    date: string;
    period: string;
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
