# 後端專案架構說明

> 教室借用系統後端專案文件  
> 最後更新：2026-03-11

## 目錄結構總覽

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Controller.php            # 基礎控制器
│   │   ├── HomeController.php        # 前台：首頁與借用申請
│   │   └── AdminController.php       # 後台：管理面板
│   └── Middleware/
│       └── HandleInertiaRequests.php  # Inertia.js 中介層
├── Mail/
│   └── BookingSubmitted.php          # 借用申請通知信
├── Models/                           # Eloquent 模型 (12 個)
│   ├── Manager.php                   # 系統管理員
│   ├── Borrower.php                  # 借用人
│   ├── Classroom.php                 # 教室
│   ├── Booking.php                   # 借用紀錄
│   ├── TimeSlot.php                  # 時段
│   ├── CourseSchedule.php            # 課程排程
│   ├── Semester.php                  # 學期
│   ├── Holiday.php                   # 假日
│   ├── Blacklist.php                 # 黑名單
│   ├── BlacklistDetail.php           # 黑名單明細
│   ├── BlacklistReason.php           # 黑名單原因
│   └── Setting.php                   # 系統設定
├── Providers/
│   └── AppServiceProvider.php        # 應用程式服務提供者
└── Services/
    └── RoomAvailabilityService.php   # 教室可用性服務
```

---

## 資料模型與關聯

### 關聯總覽

```
Manager（管理員，獨立認證系統）

Semester ──1:N──→ CourseSchedule
Classroom ──1:N──→ Booking
Classroom ──1:N──→ CourseSchedule
Borrower ──1:N──→ Booking
Borrower ──1:1──→ Blacklist ──1:N──→ BlacklistDetail ──N:1──→ BlacklistReason
Booking ──N:M──→ TimeSlot（透過 booking_time_slot 樞紐表）
```

### 模型欄位與說明

#### Manager（系統管理員）

| 欄位 | 說明 |
|------|------|
| `username` | 帳號 |
| `name` | 姓名 |
| `email` | 電子信箱 |
| `password` | 密碼（hidden） |

- 繼承 `Authenticatable`，用於後台登入認證
- 無對外關聯

---

#### Borrower（借用人）

| 欄位 | 說明 |
|------|------|
| `identity_code` | 識別碼（學號／教職員編號） |
| `name` | 姓名 |
| `email` | 電子信箱 |
| `phone` | 聯絡電話 |
| `department` | 系所 |
| `is_active` | 啟用狀態 |

| 關聯 | 類型 | 目標 |
|------|------|------|
| `blacklist` | `hasOne` | `Blacklist` |
| `bookings` | `hasMany` | `Booking` |

---

#### Classroom（教室）

| 欄位 | 說明 |
|------|------|
| `code` | 教室代碼（如 `BGC-101`） |
| `name` | 教室名稱 |
| `is_active` | 是否啟用 |

| 關聯 | 類型 | 目標 |
|------|------|------|
| `bookings` | `hasMany` | `Booking` |
| `courseSchedules` | `hasMany` | `CourseSchedule` |

| 方法 | 說明 |
|------|------|
| `activeClassrooms()` | 靜態方法，回傳啟用中的教室 |

---

#### Booking（借用紀錄）

| 欄位 | 說明 |
|------|------|
| `borrower_id` | 借用人 FK |
| `classroom_id` | 教室 FK |
| `reason` | 借用原因 |
| `teacher` | 指導老師 |
| `status` | 狀態碼 |
| `date` | 借用日期 |

**狀態碼定義：**

| 值 | 意義 |
|----|------|
| `0` | 待審核（pending） |
| `1` | 已核准（approved） |
| `2` | 已駁回（rejected） |
| `3` | 已取消（cancelled） |

| 關聯 | 類型 | 目標 |
|------|------|------|
| `classroom` | `belongsTo` | `Classroom` |
| `borrower` | `belongsTo` | `Borrower` |
| `timeSlots` | `belongsToMany` | `TimeSlot`（樞紐表 `booking_time_slot`） |

| 方法 | 說明 |
|------|------|
| `pending()` | 靜態查詢 Scope，篩選 `status=0` |

---

#### TimeSlot（時段）

| 欄位 | 說明 |
|------|------|
| `name` | 時段名稱（如 `一`、`二`） |
| `start_time` | 開始時間 |
| `end_time` | 結束時間 |

| 關聯 | 類型 | 目標 |
|------|------|------|
| `bookings` | `belongsToMany` | `Booking` |

---

#### CourseSchedule（課程排程）

| 欄位 | 說明 |
|------|------|
| `semester_id` | 學期 FK |
| `classroom_id` | 教室 FK |
| `course_name` | 課程名稱 |
| `teacher_name` | 授課教師 |
| `day_of_week` | 星期幾（1=週一） |
| `start_slot_id` | 起始時段 FK |
| `end_slot_id` | 結束時段 FK |

| 關聯 | 類型 | 目標 |
|------|------|------|
| `semester` | `belongsTo` | `Semester` |
| `classroom` | `belongsTo` | `Classroom` |
| `startSlot` | `belongsTo` | `TimeSlot` |
| `endSlot` | `belongsTo` | `TimeSlot` |

---

#### Semester（學期）

| 欄位 | 說明 |
|------|------|
| `academic_year` | 學年度（如 `114`） |
| `semester` | 學期（1=上學期、2=下學期） |
| `start_date` | 開始日期（cast `date`） |
| `end_date` | 結束日期（cast `date`） |

| 關聯 | 類型 | 目標 |
|------|------|------|
| `courseSchedules` | `hasMany` | `CourseSchedule` |

| 方法 | 說明 |
|------|------|
| `findByDate($date)` | 根據日期取得所屬學期 |
| `overlapping($start, $end)` | 取得與日期範圍重疊的學期 |
| `displayName` | Accessor，回傳 `"114學年 上學期"` 格式 |

---

#### Holiday（假日）

| 欄位 | 說明 |
|------|------|
| `date` | 日期 |
| `name` | 假日名稱 |
| `is_release_slot` | 是否開放借用 |

- `is_release_slot=false`：該日所有時段標記為假日，不可借用
- `is_release_slot=true`：視為一般日，忽略課程排程，可自由借用

---

#### Blacklist / BlacklistDetail / BlacklistReason（黑名單體系）

**Blacklist**

| 欄位 | 說明 |
|------|------|
| `borrower_id` | 借用人 FK |
| `banned_until` | 停權到期日 |

**BlacklistDetail**（無時間戳）

| 欄位 | 說明 |
|------|------|
| `blacklist_id` | 黑名單 FK |
| `reason_id` | 違規原因 FK |

**BlacklistReason**

| 欄位 | 說明 |
|------|------|
| `reason` | 原因說明 |

---

#### Setting（系統設定）

| 欄位 | 說明 |
|------|------|
| `key` | 設定鍵名 |
| `value` | 設定值 |
| `description` | 說明 |

| 方法 | 說明 |
|------|------|
| `get($key, $default)` | 靜態讀取設定值 |
| `set($key, $value, $desc)` | 靜態寫入設定值（updateOrCreate） |

---

## 控制器

### HomeController（前台）

注入 `RoomAvailabilityService` 服務。

| 方法 | 路由 | 說明 |
|------|------|------|
| `index` | `GET /Home` | 主頁面：載入教室列表、時段、週課表可用性資料，回傳 Inertia `Home` 頁面 |
| `store` | `POST /bookings` | 借用申請：驗證表單 → 建立或取得 Borrower → 建立 Booking（status=0）→ 關聯 TimeSlots → 寄送通知信 → 重導回首頁 |

**`index` 查詢參數：**

| 參數 | 預設值 | 說明 |
|------|--------|------|
| `date` | 今天 | 顯示日期 |
| `room_code` | — | 教室代碼篩選 |

**`store` 驗證欄位：**
`classroom_id`、`date`、`time_slot_ids`（陣列）、`name`、`identity_code`、`email`、`phone`、`department`、`teacher`、`reason`

---

### AdminController（後台）

除 `login` / `authenticate` 外，皆受 `auth:admin` 中介層保護。

| 方法 | 路由 | 說明 |
|------|------|------|
| `login` | `GET /admin/login` | 登入頁面 |
| `authenticate` | `POST /admin/login` | 處理登入 |
| `logout` | `POST /admin/logout` | 登出 |
| `dashboard` | `GET /admin/dashboard` | 儀表板：各教室借用統計、違規原因分布、時段熱門度等圖表資料 |
| `bookings` | `GET /admin/bookings` | 借用管理：支援狀態篩選與關鍵字搜尋，分頁 15 筆 |
| `updateBookingStatus` | `PATCH /admin/bookings/{booking}/status` | 更新借用狀態（核准/駁回/取消） |
| `notifications` | `GET /admin/notifications` | JSON 端點：取得待審核借用通知（前 10 筆 + 總數） |
| `rooms` | `GET /admin/rooms` | 教室管理（待實作） |
| `users` | `GET /admin/users` | 使用者管理（待實作） |
| `settings` | `GET /admin/settings` | 系統設定（待實作） |

---

## 服務層

### RoomAvailabilityService（教室可用性服務）

核心服務，負責計算教室在指定日期範圍的佔用狀態。

#### 公開方法

| 方法 | 說明 |
|------|------|
| `getOccupiedData($classroomId, $start, $end)` | 查詢單一教室的佔用資料 |
| `getBatchOccupiedData($rooms, $start, $end)` | 批次查詢多間教室的佔用資料（效能最佳化） |

#### 可用性計算邏輯（`calculateOccupancy`）

逐日迭代日期範圍，依序判斷各時段狀態：

1. **假日判斷**：若 `is_release_slot=false` → 全部時段標記 `holiday`
2. **課程排程**：比對星期與學期，標記對應時段為 `course`
3. **借用紀錄**：排除 `rejected`(2) 與 `cancelled`(3)，依狀態標記 `pending` / `approved`

#### 回傳資料結構

```php
[
    'YYYY-MM-DD' => [
        '時段名稱' => [
            'status'     => 'course' | 'pending' | 'approved' | 'holiday',
            'title'      => '課程名稱或借用原因',
            'applicant'  => '借用人姓名',
            'instructor' => '教師姓名',
        ],
    ],
]
```

#### 效能設計

- 批次方法共用一次假日查詢
- 需預載入 `bookings.timeSlots` 與 `courseSchedules.semester` 關聯以避免 N+1

---

## 郵件

### BookingSubmitted

| 項目 | 說明 |
|------|------|
| 觸發時機 | 借用申請送出後 |
| 佇列 | 實作 `ShouldQueue`（非同步寄送） |
| 主旨 | 「【教室借用系統】您的借用申請已送出」 |
| 模板 | `emails.booking.submitted`（Markdown） |
| 資料 | `$booking`、`$timeSlots`（時段名稱陣列） |

---

## 路由

### 前台路由

| 方法 | 路徑 | 名稱 | 說明 |
|------|------|------|------|
| `GET` | `/` | — | 重導至 `/Home` |
| `GET` | `/Home` | `home.index` | 首頁 |
| `POST` | `/bookings` | `home.store` | 送出借用申請 |

### 後台路由（前綴 `/admin`）

| 方法 | 路徑 | 認證 | 說明 |
|------|------|------|------|
| `GET` | `/admin/login` | 無 | 登入頁 |
| `POST` | `/admin/login` | 無 | 處理登入 |
| `POST` | `/admin/logout` | 需要 | 登出 |
| `GET` | `/admin/dashboard` | 需要 | 儀表板 |
| `GET` | `/admin/bookings` | 需要 | 借用管理 |
| `PATCH` | `/admin/bookings/{booking}/status` | 需要 | 更新借用狀態 |
| `GET` | `/admin/notifications` | 需要 | 通知（JSON） |
| `GET` | `/admin/rooms` | 需要 | 教室管理 |
| `GET` | `/admin/users` | 需要 | 使用者管理 |
| `GET` | `/admin/settings` | 需要 | 系統設定 |

---

## 設定檔

### config/school.php

```php
'semester' => [
    'start_date' => env('SEMESTER_START_DATE', '03-01'),
    'end_date'   => env('SEMESTER_END_DATE', '06-30'),
]
```

定義學期預設日期區間，可透過 `.env` 覆寫。

---

## 資料庫

### 遷移執行順序

1. `cache`、`jobs`（Laravel 框架）
2. `managers` → `borrowers` → `classrooms` → `time_slots`
3. `blacklist_reasons` → `holidays` → `course_schedules` → `bookings`
4. `blacklists` → `blacklist_details`
5. `booking_time_slot`（樞紐表）、`settings`
6. `semesters` → `add semester_id to course_schedules`

### Seeder 執行順序

```
ManagerSeeder → SettingSeeder → SemesterSeeder → TimeSlotSeeder →
ClassroomSeeder → BorrowerSeeder → BlacklistReasonSeeder →
BlacklistSeeder → CourseScheduleSeeder → BookingSeeder
```

初始化指令：`php artisan migrate:fresh --seed`

---

## 核心業務流程

### 1. 借用申請流程

```
使用者填寫表單 → POST /bookings → 驗證資料 →
  firstOrCreate Borrower → 建立 Booking (status=0) →
  attach TimeSlots → 寄送確認信 → 重導首頁（含高亮資訊）
```

### 2. 可用性計算流程

```
載入學期資料 → 迭代日期範圍 →
  檢查假日 → 比對課程排程（按星期/學期） →
  篩選借用紀錄（排除駁回/取消） → 合併佔用資料 →
  回傳佔用狀態 Map
```

### 3. 管理員審核流程

```
管理員檢視待審借用 → 選擇操作 →
  PATCH /admin/bookings/{id}/status → 更新狀態 →
  重導回借用管理頁
```

---

## 維護注意事項

| 項目 | 維護位置 |
|------|----------|
| 管理員認證 | `config/auth.php`（guards / providers）|
| 學期日期設定 | `config/school.php` 或 `.env` |
| 借用狀態碼定義 | `Booking` 模型 / `AdminController` |
| 可用性計算邏輯 | `RoomAvailabilityService` |
| 通知信模板 | `resources/views/emails/booking/submitted.blade.php` |
| HTTPS 強制 | `AppServiceProvider@boot` |
| 資料庫初始化 | `DatabaseSeeder`（注意 Seeder 順序） |
