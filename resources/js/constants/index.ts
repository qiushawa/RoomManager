/**
 * 應用程式全域常數配置
 */

/** 是否顯示垂直步驟進度 (開發/除錯用) */
export const SHOW_STEP_PROGRESS_VERTICAL = 0;

/**
 * 時段狀態顏色配置
 *
 * 包含兩類狀態：
 * - 後端持久化狀態 (OccupiedStatus): approved, pending, course, holiday
 * - 前端 UI 狀態: default (無資料時的預設), selected (使用者選取中)
 */
export const STATUS_COLORS = {
    // 後端持久化狀態 (對應 OccupiedStatus 型別)
    approved: 'bg-warning/90',    // 橘黃色 - 已借出
    pending: 'bg-info/90',        // 水藍色 - 申請中
    course: 'bg-neutral/90',      // 深灰色 - 課程使用
    holiday: 'bg-danger/90',      // 紅色 - 假日
    conflict_short_term_pending: 'bg-amber-500/90',
    conflict_short_term_approved: 'bg-purple-500/90',
    conflict_schedule: 'bg-red-500/90',
    // 前端 UI 狀態
    default: 'bg-gray-400/90',    // 灰色 - 無資料時的預設
    selected: 'bg-success',       // 綠色 - 使用者選取中
} as const;

/**
 * 時段狀態說明文字
 *
 * 包含兩類狀態：
 * - 後端持久化狀態 (OccupiedStatus): approved, pending, course, holiday
 * - 前端 UI 狀態: available (可申請), selected (使用者選取中)
 */
export const STATUS_LABELS = {
    // 後端持久化狀態 (對應 OccupiedStatus 型別)
    approved: '已借出',
    pending: '申請中',
    course: '課程使用',
    holiday: '假日',
    conflict_short_term_pending: '與未審核短期借用衝突',
    conflict_short_term_approved: '與已審核短期借用衝突',
    conflict_schedule: '與課表衝突',
    // 前端 UI 狀態
    available: '可申請',
    selected: '選取中',
} as const;

/** 動畫時長設定 (毫秒) */
export const ANIMATION_DURATION = {
    highlight: 5000,      // 高亮持續時間
    fadeIn: 300,          // 淡入動畫
    modal: 200,           // 模態框動畫
} as const;

/** API 端點 */
export const API_ENDPOINTS = {
    home: '/Home',
    bookings: '/bookings',
    adminLogin: '/admin/login',
    bookingsSearch: '/bookings/search',
} as const;

export {
    CLASSROOM_STATUS_TABS,
    BOOKING_TABLE_HEADERS,
    BOOKING_STATUS_TABS,
    BORROWING_RECORD_STATUS_TABS,
    RECORD_TABLE_HEADERS,
    REVIEW_TABLE_HEADERS,
} from './admin';
export {
    LONG_TERM_BUILDING_LABELS,
    LONG_TERM_BUILDING_ORDER,
    LONG_TERM_WEEKDAY_OPTIONS,
} from './longTermBorrowing';
export type { AdminTableHeader, StatusTabOption } from './admin';
