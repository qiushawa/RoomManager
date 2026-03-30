# 前端架構說明
> RoomManager 前端（Vue + Inertia）
> 最後更新：2026-03-30

## 1. 目前分層

```
resources/
├── js/
│   ├── app.ts
│   ├── bootstrap.ts
│   ├── Pages/                     # 頁面層（流程與頁面編排）
│   │   ├── Home.vue
│   │   ├── BookingCancellation.vue
│   │   └── Admin/*.vue
│   ├── components/                # 可重用 UI 區塊
│   │   ├── admin/
│   │   │   ├── charts/
│   │   │   ├── filters/
│   │   │   ├── long-term-borrowing/
│   │   │   ├── preview/
│   │   │   │   └── request-preview/
│   │   │   ├── table/
│   │   │   └── index.ts
│   │   ├── booking/
│   │   ├── navigation/
│   │   ├── schedule/
│   │   ├── ui/
│   │   └── index.ts
│   ├── composables/               # 可重用狀態與業務邏輯
│   │   └── index.ts
│   ├── constants/                 # 常數與設定
│   │   └── index.ts
│   ├── types/                     # 型別定義（集中）
│   │   └── index.ts
│   ├── utils/                     # 純工具函式
│   │   └── index.ts
│   └── layouts/
└── css/
```

## 2. 模組對照

### components
- `components/booking`: 前台借用流程元件
- `components/navigation`: 前台導覽與歡迎頁
- `components/schedule`: 課表與時段顯示
- `components/ui`: 通用 UI（Modal、Input、Dialog 等）
- `components/admin`: Admin 共用區塊（表格、分頁、圖表卡片、預覽彈窗等）
- `components/admin/charts`: 圖表與圖卡（Bar、Doughnut、DashboardChartCard、MetricCard）
- `components/admin/filters`: 清單篩選 UI（AdminSearchBar、AdminStatusTabs）
- `components/admin/table`: 清單表格相關（AdminDataTable、AdminPagination、BookingTableRow）
- `components/admin/preview`: 申請預覽彈窗主體與排程格（RequestPreviewModal、PreviewScheduleGrid）
- `components/admin/long-term-borrowing`: 長期借用子領域元件（含 `ConflictActionModal`、`ImportBuildingPanel`、`ImportPreviewTable`、`ManualRecordList`）
- `components/admin/preview/request-preview`: 申請預覽彈窗子區塊（Header、借用人資訊、基本資訊格、時段列表）

### composables
- 前台：`useBookingFlow`, `useDateSelection`, `useSlotSelection`, `useHighlight`, `useSyncedApplicantForm`
- Admin：`useTableFilters`, `useBookingStatus`, `usePreviewModal`, `useScheduleStatus`, `useConfirmDelete`, `useAdminTheme`

### types
- 基礎型別：`types/index.ts`
- Admin 領域：`types/admin.ts`
- 長期借用：`types/longTermBorrowing.ts`
- 前台取消頁：`types/bookingCancellation.ts`
- 前台首頁：`types/home.ts`
- Inertia 補充：`types/inertia.d.ts`

### constants
- 全域：`constants/index.ts`
- Admin 清單頁：`constants/admin.ts`
- 長期借用：`constants/longTermBorrowing.ts`

### utils
- 日期：`utils/date.ts`
- 節次：`utils/period.ts`
- 教室：`utils/room.ts`
- 長期借用：`utils/longTermBorrowing.ts`

## 3. 匯入規範

### 優先使用 barrel

```ts
import { BookingFormModal, WelcomeGuide } from '@/components';
import { AdminDataTable, RequestPreviewModal } from '@/components/admin';
import { AdminLayout, AppLayout } from '@/layouts';
import { useTableFilters, useBookingFlow } from '@/composables';
import { BOOKING_STATUS_TABS } from '@/constants';
import type { AdminBookingItem, HomePageProps } from '@/types';
import { formatPeriodLabel } from '@/utils';
```

### 原則
1. 頁面層避免直接 `from '@/components/.../*.vue'`。
2. 頁面層 `layouts` 亦優先透過 `@/layouts` 匯入。
3. 新增元件/邏輯後，必須同步更新對應 `index.ts`。
4. 頁面內型別優先外移到 `types/`。

## 4. 開發規則

1. `Pages/` 只放頁面流程與 API 觸發。
2. 重複 UI 區塊放 `components/`。
3. 重複業務邏輯放 `composables/`。
4. 純函式放 `utils/`。
5. 常數放 `constants/`。
6. 型別放 `types/`。

## 5. 既有標準實作

1. Admin 清單頁
   - 搜尋：`AdminSearchBar`
   - 狀態切換：`AdminStatusTabs`
   - 表格骨架：`AdminDataTable`
   - 分頁：`AdminPagination`
   - 列內容：`BookingTableRow`

2. 刪除流程
   - 對話框：`ConfirmDeleteDialog`
   - 狀態與送出：`useConfirmDelete`

3. 長期借用
   - 型別/常數/工具：集中在 `types|constants|utils/longTermBorrowing`
   - 可重用 UI：`components/admin/long-term-borrowing`

## 6. 變更驗證清單

每次前端重構至少完成：

1. 受影響檔案型別檢查無錯誤。
2. `npm run build` 成功。
3. 主要流程手動回歸：
   - 前台：選教室、選時段、填單、取消申請頁
   - Admin：清單篩選/分頁/預覽、Rooms 刪除、LongTermBorrowing 手動/預覽/匯入/撤回

## 7. 文件維護

發生以下任一情況，請更新本文件：

1. 新增或調整模組資料夾。
2. 新增共用元件或 composable。
3. 調整 barrel 匯入規範。
4. 新增或搬移核心型別/常數/工具。
