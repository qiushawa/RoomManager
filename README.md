# RoomManager 教室借用系統

RoomManager 是一套以 Laravel + Inertia + Vue 3 建置的教室借用管理系統，提供前台借用申請與後台審核管理流程。

- 後端：Laravel 12（PHP 8.2+）
- 前端：Vue 3 + Inertia.js + Vite
- 樣式：Tailwind CSS
- 測試：PHPUnit / Pest
- 資料庫：MySQL（或其他 Laravel 支援）


## 功能概覽

### 前台功能
- 教室借用申請與時段選擇
- 借用申請取消（簽章連結）
- 借用紀錄查詢

### 後台管理
- 管理員登入與權限控管
- 借用申請審核
- 教室管理
- 使用者管理
- 長期借用與課表匯入

## 安裝與執行

### 環境需求

- PHP 8.2 以上
- Composer
- Node.js 18 以上（含 npm）
- MySQL 或其他資料庫


### 1. 取得專案

```bash
git clone https://github.com/qiushawa/RoomManager.git
cd RoomManager
````


### 2. 安裝相依套件

```bash id="clean002"
composer install
npm install
```


### 3. 環境設定

```bash id="clean003"
cp .env.example .env
```

需設定：

* 資料庫（DB_*）
* 郵件（MAIL_*）


### 4. 初始化 Laravel

```bash id="clean004"
php artisan key:generate
php artisan migrate --seed
```



### 5. 啟動服務

```bash id="clean005"
php artisan serve
npm run dev
```



## 系統規則

### 學期資料檢查

當目前日期不屬於任何學期時：

* 系統將禁止後台操作
* 管理員需先建立學期資料


## 操作教學

<p>
  <a href="./docs/user-guide.md">
    <img src="https://img.shields.io/badge/使用者操作-借用流程-green?style=for-the-badge&logo=bookstack" />
  </a>
  <a href="./docs/admin-guide.md">
    <img src="https://img.shields.io/badge/管理員操作-後台管理-blue?style=for-the-badge&logo=readthedocs" />
  </a>  

</p>
