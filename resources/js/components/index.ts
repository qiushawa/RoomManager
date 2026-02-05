/**
 * 元件統一匯出入口
 *
 * 按功能模組分類匯出所有業務元件：
 *
 * @module booking   - 預約流程相關（表單、進度、須知）
 * @module navigation - 導航相關（教室選單、歡迎頁）
 * @module schedule   - 課表時段相關（表格、工具列、日期選擇器）
 * @module ui        - 通用 UI 元件（按鈕、彈窗、表單控制項）
 */

// ============================================================
// 預約流程模組 (Booking)
// ============================================================
export {
    BookingForm,
    BookingFormModal,
    BookingProgressStepper,
    GuidelinesModal,
    SelectionSummaryPanel,
} from './booking';

// ============================================================
// 導航模組 (Navigation)
// ============================================================
export {
    ClassroomNavigator,
    WelcomeGuide,
} from './navigation';

// ============================================================
// 課表時段模組 (Schedule)
// ============================================================
export {
    DatePicker,
    ScheduleGrid,
    ScheduleToolbar,
} from './schedule';

// ============================================================
// UI 基礎元件
// ============================================================
export * from './ui';

// ============================================================
// 相容性別名（漸進式遷移用，未來可移除）
// ============================================================
export { ClassroomNavigator as RoomSidebar } from './navigation';
export { WelcomeGuide as InstructionPanel } from './navigation';
export { SelectionSummaryPanel as RoomInfoPanel } from './booking';
export { BookingProgressStepper as StepProgressVertical } from './booking';
export { ScheduleGrid as TimeTable } from './schedule';
export { ScheduleToolbar as ActionFooter } from './schedule';
export { DatePicker as DateSelector } from './schedule';
