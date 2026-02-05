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
