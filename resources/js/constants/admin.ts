export interface StatusTabOption {
    label: string;
    value: string;
}

export interface AdminTableHeader {
    label: string;
    align?: 'left' | 'center';
}

export const BOOKING_STATUS_TABS: StatusTabOption[] = [
    { label: '全部', value: 'all' },
    { label: '待審核', value: '0' },
    { label: '已核准', value: '1' },
    { label: '已拒絕', value: '2' },
    { label: '已取消', value: '3' },
];

export const BORROWING_RECORD_STATUS_TABS: StatusTabOption[] = [
    { label: '全部', value: 'all' },
    { label: '已核准', value: '1' },
    { label: '已拒絕', value: '2' },
    { label: '已取消', value: '3' },
];

export const CLASSROOM_STATUS_TABS: StatusTabOption[] = [
    { label: '全部', value: 'all' },
    { label: '啟用', value: 'enabled' },
    { label: '停用', value: 'disabled' },
];

export const BOOKING_TABLE_HEADERS: AdminTableHeader[] = [
    { label: '申請人' },
    { label: '教室' },
    { label: '日期' },
    { label: '時段' },
    { label: '申請時間' },
    { label: '操作', align: 'center' },
];

export const RECORD_TABLE_HEADERS: AdminTableHeader[] = [
    { label: '申請人' },
    { label: '教室' },
    { label: '日期' },
    { label: '時段' },
    { label: '申請時間' },
    { label: '狀態', align: 'center' },
];

export const REVIEW_TABLE_HEADERS: AdminTableHeader[] = [
    { label: '申請人' },
    { label: '教室' },
    { label: '日期' },
    { label: '時段' },
    { label: '申請時間' },
    { label: '操作', align: 'center' },
];
