# 前端專案架構說明

> 教室借用系統前端專案文件  
> 最後更新：2026-02-05

## 目錄結構總覽

```
resources/
├── js/
│   ├── app.ts                    # 應用程式入口
│   ├── bootstrap.ts              # 初始化腳本 (axios 等)
│   ├── components/               # Vue 元件
│   │   ├── booking/              # 預約流程模組 (5 元件)
│   │   ├── navigation/           # 導航模組 (2 元件)
│   │   ├── schedule/             # 課表時段模組 (3 元件)
│   │   ├── ui/                   # 通用 UI 元件 (5 元件)
│   │   └── index.ts              # 元件統一匯出
│   ├── composables/              # Vue Composables (4 個)
│   ├── constants/                # 常數配置
│   ├── layouts/                  # 佈局模板 (3 元件)
│   ├── Pages/                    # Inertia 頁面
│   ├── types/                    # TypeScript 型別
│   └── utils/                    # 工具函式
│
├── css/
│   ├── app.css                   # 主入口樣式
│   ├── base/                     # 基礎樣式
│   │   ├── animations.css        # 動畫定義
│   │   ├── index.css             # 基礎匯入
│   │   └── theme.css             # 主題變數
│   └── components/               # 元件樣式
│       ├── index.css             # 元件匯入
│       ├── modal.css             # 模態框樣式
│       └── timetable.css         # 課表樣式
│
└── views/                        # Blade 模板
```

---

## 元件模組說明

### Booking 模組 (`components/booking/`)

預約流程相關的所有元件。

| 元件名稱 | 用途說明 |
|---------|---------|
| `BookingForm` | 借用申請表單（內嵌版），包含申請人資訊欄位 |
| `BookingFormModal` | 借用申請表單彈窗，完整表單流程 |
| `BookingProgressStepper` | 垂直步驟進度指示器，顯示多步驟流程 |
| `GuidelinesModal` | 借用須知彈窗，使用者需確認後才能繼續 |
| `SelectionSummaryPanel` | 右側摘要面板，顯示已選時段與下一步按鈕 |

```typescript
import {
    BookingForm,
    BookingFormModal,
    BookingProgressStepper,
    GuidelinesModal,
    SelectionSummaryPanel,
} from '@/components/booking';
```

---

### Navigation 模組 (`components/navigation/`)

應用程式導航與引導相關元件。

| 元件名稱 | 用途說明 |
|---------|---------|
| `ClassroomNavigator` | 左側教室選單，按建築分類，支援選中高亮 |
| `WelcomeGuide` | 歡迎頁面，未選擇教室時顯示，含操作說明 |

```typescript
import { ClassroomNavigator, WelcomeGuide } from '@/components/navigation';
```

---

### Schedule 模組 (`components/schedule/`)

課表顯示與時段選取相關元件。

| 元件名稱 | 用途說明 |
|---------|---------|
| `ScheduleGrid` | 週課表表格，支援時段選取與狀態顯示 |
| `ScheduleToolbar` | 底部工具列，含週次切換、日期選擇、圖例 |
| `DatePicker` | 日期選擇器，點擊開啟原生日曆 |

```typescript
import { DatePicker, ScheduleGrid, ScheduleToolbar } from '@/components/schedule';
```

---

### UI 模組 (`components/ui/`)

可複用的基礎 UI 元件，不包含業務邏輯。

| 元件名稱 | 用途說明 |
|---------|---------|
| `BaseButton` | 按鈕元件，多種樣式與尺寸 |
| `BaseModal` | 模態框基礎元件，封裝動畫與遮罩 |
| `FormInput` | 表單輸入框，統一樣式 |
| `FormTextarea` | 表單文字區域 |
| `StatusBadge` | 狀態標籤 |

```typescript
import { BaseButton, BaseModal, FormInput, FormTextarea, StatusBadge } from '@/components/ui';
```

---

## Composables

業務邏輯抽取為可複用函式。

| Composable | 用途說明 |
|------------|---------|
| `useDateSelection` | 日期選擇與週次管理 |
| `useSlotSelection` | 時段選取狀態管理 |
| `useBookingFlow` | 預約流程狀態機 |
| `useHighlight` | 時段高亮效果（用於顯示剛申請的時段） |

```typescript
import {
    useDateSelection,
    useSlotSelection,
    useBookingFlow,
    useHighlight,
} from '@/composables';
```

---

## 佈局元件 (`layouts/`)

| 元件 | 說明 |
|-----|------|
| `AppLayout` | 應用程式主佈局，三欄式結構 |
| `SidebarBase` | 通用側邊欄容器，支援背景圖 |
| `PanelBase` | 通用面板容器 |

```vue
<AppLayout>
    <template #left-sidebar><!-- 左側欄 --></template>
    <template #main><!-- 主內容 --></template>
    <template #right-sidebar><!-- 右側欄 --></template>
    <template #extra><!-- 浮動元素 --></template>
</AppLayout>
```

---

## 工具函式 (`utils/`)

| 檔案 | 函式 | 說明 |
|-----|------|------|
| `date.ts` | `formatDateToYYYYMMDD` | Date → `"2026-02-05"` |
| | `formatDateForDisplay` | Date → `"2026/02/05 (四)"` |
| | `formatHeaderDate` | `"2026-02-05"` → `"2026/2/5"` |
| | `formatTime` | `"08:10:00"` → `"08:10"` |
| | `getWeekDates` | 取得一週日期陣列 |
| `period.ts` | `formatPeriodLabel` | `"一"` → `"第一節"` |
| | `formatSlotLabel` | 產生完整時段標籤 |
| | `checkSlotsConsecutive` | 檢查是否連續時段 |
| `room.ts` | `findRoomByCode` | 根據代碼查找教室 |

```typescript
import { formatDateToYYYYMMDD, formatTime, getWeekDates } from '@/utils/date';
import { formatPeriodLabel, checkSlotsConsecutive } from '@/utils/period';
import { findRoomByCode } from '@/utils/room';
```

---

## 型別定義 (`types/`)

```typescript
// 教室
interface Room { id: number; code: string; name: string; }
interface Building { name: string; rooms: Room[]; }

// 時段
interface Period { id: number; code: string; label: string; start_time?: string; end_time?: string; }
interface WeekDate { date: string; dayName: string; fullDate: string; }
interface SelectedSlot { date: string; period: string; id: number; label: string; }

// 狀態
type OccupiedStatus = 'approved' | 'pending' | 'course' | 'holiday';
type OccupiedData = Record<string, Record<string, OccupiedStatus>>;
type Step = 1 | 2;

// 高亮
interface HighlightInfo { date: string; slots: string[]; }

// 表單
interface ApplicantForm {
    name: string;
    identity_code: string;
    email: string;
    phone: string;
    department: string;
    teacher: string;
    reason: string;
}
```

---

## 常數配置 (`constants/`)

```typescript
/** 是否顯示垂直步驟進度 (開發用) */
SHOW_STEP_PROGRESS_VERTICAL: boolean;

/** 時段狀態顏色配置 */
STATUS_COLORS: {
    approved: 'bg-warning/90',    // 橘黃色 - 已借出
    pending: 'bg-info/90',        // 水藍色 - 申請中
    course: 'bg-neutral/90',      // 深灰色 - 課程使用
    holiday: 'bg-danger/90',      // 紅色 - 假日
    default: 'bg-gray-400/90',
    selected: 'bg-success',       // 綠色 - 選取中
};

/** 時段狀態說明 */
STATUS_LABELS: {
    approved: '已借出',
    pending: '申請中',
    course: '課程使用',
    holiday: '假日',
    available: '可申請',
    selected: '選取中',
};

/** 動畫時長設定 (毫秒) */
ANIMATION_DURATION: { highlight: 5000, fadeIn: 300, modal: 200 };

/** API 端點 */
API_ENDPOINTS: { home: '/Home', bookings: '/bookings', adminLogin: '/admin/login', bookingsSearch: '/bookings/search' };
```

---

## 匯入方式

### 推薦：模組化匯入

```typescript
// 從特定模組匯入
import { ScheduleGrid, ScheduleToolbar } from '@/components/schedule';
import { ClassroomNavigator } from '@/components/navigation';
import { BookingFormModal } from '@/components/booking';
import { BaseButton, FormInput } from '@/components/ui';
```

### 統一入口匯入

```typescript
// 從統一入口匯入所有元件
import { ScheduleGrid, ClassroomNavigator, BookingFormModal, BaseButton } from '@/components';

// Composables
import { useDateSelection, useBookingFlow } from '@/composables';

// 工具函式
import { formatDateToYYYYMMDD, findRoomByCode } from '@/utils';

// 常數
import { STATUS_COLORS, API_ENDPOINTS } from '@/constants';

// 型別
import type { Room, Period, SelectedSlot } from '@/types';

// 佈局
import { AppLayout, SidebarBase, PanelBase } from '@/layouts';
```

---

## 開發指南

### 新增元件

1. **決定模組歸屬**：
   - 預約相關 → `components/booking/`
   - 導航相關 → `components/navigation/`
   - 課表相關 → `components/schedule/`
   - 通用 UI → `components/ui/`

2. **建立元件檔案**，撰寫 JSDoc 註解說明用途

3. **在模組 `index.ts` 中匯出**

4. **若為新模組，在 `components/index.ts` 中加入重新匯出**

### 命名規範

| 類型 | 命名規範 | 範例 |
|-----|---------|------|
| 元件檔案 | PascalCase | `BookingForm.vue` |
| Composable | camelCase + use 前綴 | `useBookingFlow.ts` |
| 工具函式 | camelCase | `formatDateToYYYYMMDD` |
| 型別 | PascalCase | `SelectedSlot` |
| 常數 | SCREAMING_SNAKE_CASE | `STATUS_COLORS` |

### 程式碼風格

- 使用 TypeScript 嚴格模式
- Props 使用 `defineProps<T>()` 泛型定義
- Emits 使用 `defineEmits<T>()` 泛型定義
- 優先使用 Composition API
- 元件必須包含 JSDoc 說明

---

## 維護注意事項

| 項目 | 維護位置 |
|-----|---------|
| 時段狀態顏色 | `constants/index.ts` → `STATUS_COLORS` |
| 時段狀態說明 | `constants/index.ts` → `STATUS_LABELS` |
| 動畫時長設定 | `constants/index.ts` → `ANIMATION_DURATION` |
| 日期格式化 | `utils/date.ts` |
| API 端點 | `constants/index.ts` → `API_ENDPOINTS` |
| 表單預設值 | `composables/useBookingFlow.ts` |
