export type BuildingCode = 'CB' | 'GC' | 'RA';

export interface ImportConfig {
    year: number;
    seme: number;
    category: string;
    building: string;
}

export interface ClassroomOption {
    id: number;
    code: string;
    name: string;
    has_imported?: boolean;
    building_code?: BuildingCode | null;
}

export interface TimeSlotOption {
    id: number;
    name: string;
}

export interface ManualRecord {
    id: number;
    classroom_code: string;
    classroom_name: string;
    borrow_type: number;
    teacher_name: string;
    course_name: string;
    day_of_week: number;
    start_slot: string;
    end_slot: string;
    start_date: string | null;
    end_date: string | null;
}

export interface PreviewSchedule {
    classroom_id: number;
    start_slot_id: number;
    end_slot_id: number;
    day_of_week: number;
    course_name: string;
}

export interface BorrowTypeOption {
    value: number;
    label: string;
}

export interface WeekdayOption {
    value: number;
    label: string;
}

export interface ManualFormData {
    semester?: string;
    borrow_type: number;
    classroom_id: number | '';
    teacher_name: string;
    course_name: string;
    day_of_week: number[];
    start_date: string;
    end_date: string;
    periods: number[];
}

export interface ManualConflictItem {
    id: number;
    day_of_week: number;
    start_slot: string;
    end_slot: string;
    start_date: string | null;
    end_date: string | null;
    borrow_type: number | null;
    source_label: string;
    course_name: string;
    teacher_name: string;
    is_protected: boolean;
    overlap_periods: number[];
}

export interface ManualConflictSummary {
    total: number;
    protected: number;
    overridable: number;
}
