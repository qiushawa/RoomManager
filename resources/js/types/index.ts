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

export interface OccupiedItem {
    status: OccupiedStatus;
    title?: string;
    instructor?: string;
    applicant?: string;
}

export type OccupiedData = Record<string, Record<string, OccupiedItem | OccupiedStatus>>;

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
    user_name: string;
    classroom_name: string;
    date: string;
    status: 0 | 1 | 2 | 3; // 0: pending, 1: approved, 2: rejected, 3: cancelled
}

export * from './admin';
export * from './bookingCancellation';
export * from './home';
export * from './longTermBorrowing';
