export interface AdminDataTableHeader {
    label: string;
    align?: 'left' | 'center';
}

export interface AdminChartData {
    labels: string[];
    data: number[];
}

export interface AdminBookingBorrower {
    name: string;
    identity_code: string;
    department: string | null;
    email: string | null;
    phone: string | null;
}

export interface AdminBookingClassroom {
    code: string;
    name: string;
}

export interface AdminBookingDateItem {
    date: string;
    time_slots: string[];
}

export interface AdminBookingItem {
    id: number;
    date: string;
    date_summary?: string;
    is_multi_day?: boolean;
    status: number;
    reason: string | null;
    teacher: string | null;
    created_at: string;
    borrower: AdminBookingBorrower | null;
    classroom: AdminBookingClassroom | null;
    time_slots: string[];
    booking_dates?: AdminBookingDateItem[];
}

export interface AdminClassroomItem {
    id: number;
    code: string;
    name: string;
    is_active: boolean;
    bookings_count: number;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    links: PaginationLink[];
}
