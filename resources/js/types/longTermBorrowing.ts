export type BuildingCode = 'CB' | 'GC' | 'RA';
export type LongTermScheduleType = 'course' | 'manual' | 'borrowed';
export type ManualConflictKind = 'schedule' | 'short_term_approved' | 'short_term_pending';
export type ShortTermResolution = 'review_pending' | 'reject_and_override';
export type ApprovedShortTermResolution = 'keep_short_term';
export type SlotResolutionAction =
    | 'cancel_slot'
    | 'review_pending'
    | 'reject_and_override'
    | 'defer_to_short_term'
    | 'override_with_long_term';

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
    type: LongTermScheduleType;
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
    time_slot_ids: number[];
    day_of_week: number;
    course_name: string;
}

export interface WeekdayOption {
    value: number;
    label: string;
}

export interface ManualFormData {
    semester?: string;
    classroom_id: number | '';
    teacher_name: string;
    course_name: string;
    day_of_week: number[];
    start_date: string;
    end_date: string;
    periods: number[];
    slot_resolutions?: Record<string, SlotResolutionAction>;
}

export interface ManualConflictItem {
    id: number;
    conflict_kind: ManualConflictKind;
    day_of_week: number;
    start_slot: string;
    end_slot: string;
    start_date: string | null;
    end_date: string | null;
    type: LongTermScheduleType;
    source_label: string;
    course_name: string;
    teacher_name: string;
    is_protected: boolean;
    overlap_periods: number[];
    conflict_dates: string[];
    booking_id: number | null;
    booking_status: 'pending' | 'approved' | 'rejected' | 'cancelled' | null;
    applicant_name: string;
    conflict_slots?: ManualConflictSlot[];
}

export interface ManualConflictSlot {
    slot_key: string;
    day_of_week: number;
    period: number;
    date: string | null;
    booking_date_id: number | null;
    time_slot_id: number | null;
}

export interface ManualConflictSummary {
    total: number;
    schedule: number;
    approved_short_term: number;
    pending_short_term: number;
}

export interface ManualConflictResolution {
    approved_short_term?: ApprovedShortTermResolution;
    pending_short_term?: ShortTermResolution;
}

export interface SlotResolutionState {
    slotKey: string;
    dayOfWeek: number;
    period: number;
    conflictKind: ManualConflictKind;
    action: SlotResolutionAction | null;
}
