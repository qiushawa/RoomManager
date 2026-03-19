export type BookingCancellationMode = 'confirm' | 'result';

export type BookingCancellationState = 'confirm' | 'cancelled' | 'locked' | 'missing';

export interface BookingCancellationSummary {
    borrower_name: string;
    classroom_name: string;
    date: string;
    teacher: string;
    reason: string;
    time_slots: string[];
}

export interface BookingCancellationPageProps {
    mode: BookingCancellationMode;
    state: BookingCancellationState;
    summary: BookingCancellationSummary | null;
    cancelActionUrl: string | null;
    homeUrl: string;
}

export interface BookingCancellationSummaryItem {
    label: string;
    value?: string;
    list?: string[];
}
