# FINAL AI CODING ROADMAP — TECHNOLIFE INVENTORY MANAGEMENT SYSTEM

> **Project Type:** Web-based Inventory Management System  
> **Target:** MVP Production Ready, extensible, configurable, user-friendly  
> **Primary Stack:** Laravel + Blade + Tailwind CSS + Filament  
> **Development Method:** Agile / iterative development  
> **Architecture:** Modular Laravel application with clear separation between UI, application logic, domain/business logic, and persistence

---

# 0. SOURCE OF TRUTH

File ini adalah **source of truth utama untuk pengerjaan website**.

AI wajib membaca file ini sebelum mengerjakan task.

## Rules

1. Fokus utama roadmap adalah **website dan implementasi fitur**.
2. Task dibuat **per fitur**, bukan hanya per layer teknis.
3. Setiap fitur harus memiliki gambaran: tujuan, role, UI, workflow, backend, database, validation, testing, dan status.
4. Jangan mengarang business rule Technolife.
5. Requirement yang belum diketahui diberi `[UNKNOWN]` atau `[FOLLOW-UP]`.
6. Asumsi teknis/desain diberi `[ASSUMPTION]`.
7. Requirement yang sudah dikonfirmasi diberi `[CONFIRMED]`.
8. Jangan membuat migration/model final sebelum domain dan business process cukup tervalidasi.
9. Jangan membuat UI final sebelum workflow operasional dipahami.
10. Business logic tidak boleh diletakkan sembarangan di Controller/Page.
11. Transaksi inventory tidak boleh di-hard-delete tanpa mekanisme correction/reversal/audit.
12. Setiap task selesai wajib memperbarui checklist di file ini.
13. Jika task terblokir, tandai `[BLOCKED]` dan jelaskan blocker.
14. Jangan mengubah business rule hanya karena lebih mudah secara teknis.
15. Utamakan UX yang cepat, jelas, sederhana, dan cocok untuk user operasional.
16. Sistem harus configurable/customizable sejauh tidak mengorbankan integritas data.

---

# 1. CURRENT BUSINESS CONTEXT

Technolife memiliki warehouse/gudang internal yang menyimpan dan mendistribusikan inventory ke beberapa unit/outlet.

## Internal Units / Outlets

- Cafe
- Billiard
- Resto

## External Supplier / Partner

- WRT / Main Kitchen
- WRT adalah pihak eksternal.
- WRT berperan sebagai supplier/partner yang mengirim bahan/barang ke Technolife.
- Detail workflow WRT masih perlu divalidasi.

## Confirmed Application Roles — Updated 2026-10-08

Struktur role aplikasi terbaru berjumlah enam role. Jobdesk berikut adalah requirement terkonfirmasi; akses rinci tetap menunggu permission matrix.

| Role | Fokus / jobdesk terkonfirmasi |
|---|---|
| **Super Admin** | Kendali penuh sistem, user, role/permission, master data, konfigurasi, monitoring, dan administrasi. |
| **Staff Gudang** | Input barang pembelian, menerima dan memeriksa fisik barang, mencatat Stock In, menyerahkan barang kepada PIC Outlet, dan mencatat Stock Out. |
| **PIC Outlet** | Memeriksa stok outlet dan menerima barang dari Staff Gudang. |
| **Management** | Melihat dashboard umum, kondisi stok keseluruhan, dan stok gudang untuk monitoring; tidak menjalankan transaksi operasional. |
| **Purchasing** | Melihat stok, mengawasi aktivitas Staff Gudang, serta meninjau/menyetujui Stock Opname mingguan dan akhir bulan. |
| **Finance** | Melihat stok seluruh gudang dan menggunakan informasi stok untuk valuasi persediaan. |

> **[CONFIRMED]** Enam role dan jobdesk di atas menggantikan model empat role sementara. Implementasi seeder sekarang mencakup keenam role dan memberi baseline `app.access`; `users.manage` tetap hanya untuk Super Admin. Detail permission matrix, cakupan akses PIC Outlet, dan metode valuasi tetap `[FOLLOW-UP]`.

### Role-Specific Dashboard & Feature Access — CONFIRMED

Setiap role harus memiliki dashboard dan fitur yang sesuai jobdesk, bukan satu dashboard/fitur seragam untuk semua role. Dashboard Super Admin harus berbeda dan lebih lengkap untuk administrasi sistem, termasuk akses cepat untuk menambah/mengelola user.

| Role | Dashboard/fitur yang harus ditonjolkan |
|---|---|
| **Super Admin** | Dashboard administrasi dan monitoring sistem; tambah/kelola user, role/permission, master data, serta konfigurasi. |
| **Staff Gudang** | Operasional gudang: penerimaan/pemeriksaan barang, Stock In, Stock Out, dan pekerjaan gudang lain setelah workflow disepakati. |
| **PIC Outlet** | Stok outlet dan penerimaan barang dari gudang. |
| **Management** | Ringkasan kondisi stok keseluruhan dan per gudang untuk monitoring. |
| **Purchasing** | Monitoring stok/aktivitas gudang serta antrian review dan approval Stock Opname mingguan/akhir bulan. |
| **Finance** | Stok seluruh gudang dan informasi valuasi persediaan. |

> Keberadaan role dan arah dashboard ini `[CONFIRMED]`. Angka/KPI persis, detail menu, akses per lokasi, dan permission untuk setiap action tetap `[FOLLOW-UP]`. Menu tambah user dan administrasi user hanya untuk Super Admin.

---

# 2. WEBSITE INFORMATION ARCHITECTURE

Website memiliki **4 primary pages**:

```text
Dashboard
Data Stock
Riwayat
Laporan
```

Feature workflow tidak harus menjadi top-level page. Contoh:

```text
Data Stock
   ├── Detail Stock
   ├── Stock In
   ├── Stock Out
   ├── Stock Opname
   └── Stock Movement
```

Menu dan action ditampilkan berdasarkan role/permission.

---

# 3. TARGET USER EXPERIENCE

## 3.1 General UX

- [ ] User langsung memahami kondisi inventory.
- [ ] Action utama mudah ditemukan.
- [ ] Form tidak terlalu panjang.
- [ ] Search/filter cepat.
- [ ] Status transaksi jelas.
- [ ] Error message jelas.
- [ ] Success feedback jelas.
- [ ] Empty state tersedia.
- [ ] Loading state tersedia.
- [ ] Confirmation untuk action penting.
- [ ] Responsive desktop/tablet.
- [ ] Workflow operasional nyaman digunakan dari tablet/mobile bila dibutuhkan.

## 3.2 Inventory UX Principle

User harus dapat menjawab:

> "Stock item ini sekarang berapa, ada di mana, batch apa, kapan masuk, kapan expired, dan pergerakannya bagaimana?"

---

# 4. TARGET ARCHITECTURE

```text
Users
  ↓
Web UI
  ├── Blade + Tailwind
  └── Filament
  ↓
Laravel
  ├── Controllers / Livewire / Actions
  ├── Services
  ├── Policies
  ├── Events / Jobs
  └── Notifications
  ↓
Database
  └── PostgreSQL / MySQL [FINAL DECISION PENDING]

External Notification
  Laravel Queue
       ↓
  Node.js WhatsApp Service
       ↓
  whatsapp-web.js
```

## UI Strategy

### Blade + Tailwind

Prioritas untuk workflow operasional yang membutuhkan UX custom:

- Dashboard
- Data Stock
- Stock In
- Stock Out
- Stock Opname
- Picking
- Receiving
- Request

### Filament

Prioritas untuk administrasi/configuration:

- User
- Role
- Permission
- Product
- Category
- Unit
- Supplier
- Warehouse
- Outlet
- Purchasing
- Configuration
- Administration

Pembagian ini dapat berubah setelah UX testing.

---

# 5. DEVELOPMENT ORDER

```text
DISCOVERY
    ↓
FEATURE REQUIREMENTS
    ↓
FEATURE BACKLOG
    ↓
AUTHENTICATION
    ↓
MASTER DATA
    ↓
DASHBOARD
    ↓
DATA STOCK
    ↓
STOCK IN
    ↓
STOCK OUT
    ↓
STOCK OPNAME
    ↓
RIWAYAT
    ↓
PURCHASING
    ↓
APPROVAL
    ↓
BATCH / FIFO / EXPIRY
    ↓
LAPORAN
    ↓
WHATSAPP ALERT
    ↓
AUDIT / SECURITY
    ↓
TESTING
    ↓
UAT
    ↓
DEPLOYMENT
```

Urutan dapat berubah berdasarkan prioritas stakeholder.

---

# 6. PHASE 0 — DISCOVERY & VALIDATION

## Task 0.1 — Organization

- [ ] Confirm warehouse structure
- [ ] Confirm Cafe
- [ ] Confirm Billiard
- [ ] Confirm Resto
- [x] Confirm Super Admin
- [x] Confirm Staff Gudang
- [x] Confirm PIC Outlet
- [x] Confirm Management application role
- [x] Confirm Purchasing
- [x] Confirm Finance
- [x] Confirm six application roles and their initial jobdesk
- [ ] Confirm WRT as external supplier/partner

## Task 0.2 — Business Process

- [ ] Observe procurement
- [ ] Observe receiving
- [ ] Observe Stock In
- [ ] Observe stock request
- [ ] Observe picking
- [ ] Observe Stock Out
- [ ] Observe outlet receiving
- [ ] Observe Stock Opname
- [ ] Observe adjustment
- [ ] Observe expired/waste handling

## Task 0.3 — Business Rules

- [ ] FIFO rule
- [ ] Batch rule
- [ ] Expiry rule
- [ ] Durability rule
- [ ] Stock threshold rule
- [ ] Approval rule
- [ ] Correction/reversal rule
- [ ] Outlet distribution rule
- [ ] WRT workflow

## Task 0.4 — Current System

- [ ] Identify Excel/Spreadsheet
- [ ] Identify paper documents
- [ ] Identify WhatsApp/manual workflow
- [ ] Identify existing software
- [ ] Collect sample documents if permitted
- [ ] Record current problems
- [ ] Record duplicate data entry
- [ ] Record common errors
- [ ] Record reporting problems

---

# 7. FEATURE DEVELOPMENT FORMAT

**WAJIB digunakan untuk setiap feature.**

```text
Feature:
Status: Planned / In Progress / Done / Partial / Blocked
Priority: Critical / High / Medium / Low

Purpose:
...

Roles:
- ...

Pages / UI:
- ...

Workflow:
1. ...
2. ...
3. ...

Backend:
- ...

Database:
- ...

Validation:
- ...

Permission:
- ...

Audit:
- ...

Testing:
- ...

Checklist:
- [ ] ...

Blocker:
- None / ...

Assumption:
- None / ...

Next:
- ...
```

---

# 8. FEATURE BACKLOG

# FEATURE 01 — AUTHENTICATION & ACCESS

**Status:** Done  
**Priority:** Critical


**Blocker:** None for the current baseline. The detailed per-role permission matrix remains `[FOLLOW-UP]` and should be revised when business rules are confirmed.

**Original implementation assumption (superseded 2026-10-08):** `[ASSUMPTION]` The initial implementation granted `app.access` to four provisional roles. After the role update, the seeder now includes all six confirmed roles and grants each the same baseline `app.access`. This shared baseline does not define detailed role-specific permissions.

**Implementation summary:** Added a login form, session-based login/logout, a basic account screen, role and permission tables, and permission middleware. Login validates credentials, regenerates the session, supports remember-me, and is throttled. Logout invalidates the session and regenerates the CSRF token. The seeder grants the shared `app.access` permission to all six confirmed roles. Current application routes require authentication and that permission. The existing dashboard appearance is unchanged; its profile controls now open the account screen. Friendly Indonesian login error copy was updated on 2026-10-08.

**Files changed:**
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Middleware/EnsureUserHasPermission.php`
- `app/Models/Role.php`
- `app/Models/Permission.php`
- `app/Models/User.php`
- `bootstrap/app.php`
- `database/migrations/2026_10_07_000001_create_roles_and_permissions_tables.php`
- `database/seeders/RolePermissionSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `resources/views/auth/account.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/errors/403.blade.php`
- `resources/views/dashboard.blade.php`
- `resources/views/inventory/dashboard.blade.php`
- `routes/web.php`
- `tests/Feature/AuthenticationTest.php`
- `tests/Feature/ExampleTest.php`
- `docs/technolife-inventory-roadmap-v3.md`

**Tests:** `php artisan test` — 12 passed (39 assertions). Pint check passed. `npm.cmd run build` — passed. Migration applied successfully to the configured SQLite database.

**Verification:** Login, invalid credentials, validation, session handling, logout, guest redirection, forbidden access, all six role assignments, account page, route middleware, migration, and asset build verified. Dashboard layout and styling remain unchanged.

**Next:** Feature 02 is complete; Feature 03 and Feature 04 are implemented. Feature 05 is partial; lanjut Feature 06 — Stock In setelah saldo mulai dicatat.

## Purpose

Menyediakan login dan pembatasan akses berdasarkan role/permission.

## Roles

- Super Admin
- Staff Gudang
- PIC Outlet
- Management
- Purchasing
- Finance

## UI

- [x] Login page
- [x] Logout
- [x] Session handling
- [x] Unauthorized page
- [x] User profile/basic account page jika diperlukan

## Workflow

```text
Login
  ↓
Validate credential
  ↓
Load user + role + permission
  ↓
Dashboard
```

## Backend

- [x] Authentication
- [x] Authorization middleware/policy
- [x] Role checking
- [x] Permission checking

## Database

- [x] users
- [x] roles
- [x] permissions
- [x] role_user / equivalent
- [x] permission_role / equivalent

## Testing

- [x] Valid login
- [x] Invalid login
- [x] Logout
- [x] Unauthorized route
- [x] Role restriction
- [x] Permission restriction

---

# FEATURE 02 — USER & ROLE MANAGEMENT

**Status:** Done  
**Priority:** High  
**Access:** Super Admin

**Implementation summary:** Added Super Admin user management, role assignment, activation/deactivation, soft delete/restore, a permission matrix, and administration audit records. The admin routes require `users.manage`; this permission is reserved for the Super Admin role. Existing application routes continue to use the `app.access` permission.

**Access boundary:** Create/edit/activate/deactivate users, role assignment, and permission administration are Super Admin features. Other roles must not see or open these actions.

**Assumptions:**
- `[ASSUMPTION]` Deleting a user is recoverable soft deletion so the account and audit history remain available.
- `[ASSUMPTION]` The six confirmed role records are fixed; role creation/deletion is not included. Super Admin can assign these roles to users.
- `[ASSUMPTION]` `users.manage` remains exclusive to Super Admin and cannot be changed in the permission matrix.
- `[ASSUMPTION]` Self role changes, self deactivation/deletion, and removing the last active Super Admin are blocked to prevent administrative lockout.
- `[ASSUMPTION]` New and reset passwords require at least 10 characters.

**Files changed:**
- `app/Http/Controllers/Admin/UserController.php`
- `app/Http/Controllers/Admin/RolePermissionController.php`
- `app/Http/Requests/Admin/StoreUserRequest.php`
- `app/Http/Requests/Admin/UpdateUserRequest.php`
- `app/Http/Requests/Admin/UpdateRolePermissionsRequest.php`
- `app/Models/AdministrationAuditLog.php`
- `app/Models/User.php`
- `app/Services/AdminAuditLogger.php`
- `app/Services/UserManagementService.php`
- `database/migrations/2026_10_07_000002_add_status_and_soft_deletes_to_users_table.php`
- `database/migrations/2026_10_07_000003_create_administration_audit_logs_table.php`
- `database/seeders/RolePermissionSeeder.php`
- `database/factories/UserFactory.php`
- `resources/views/admin/users/*`
- `resources/views/admin/roles/index.blade.php`
- `resources/views/auth/account.blade.php`
- `routes/web.php`
- `tests/Feature/UserManagementTest.php`
- `tests/Feature/AuthenticationTest.php`
- `docs/technolife-inventory-roadmap-v3.md`

**Tests:** `php artisan test` — 22 passed (104 assertions). Pint check passed. `npm.cmd run build` passed. Migrations and seeder ran against configured SQLite.

**Verification:** Super Admin-only access, create/edit, duplicate email and role validation, role assignment, status changes, inactive login/session denial, soft delete/restore, permission configuration, admin safeguards, audit records, routes, migrations, and build verified.

**Blocker:** None for this feature. The detailed business permission matrix remains subject to stakeholder review; the current permission matrix contains `app.access` and reserved `users.manage`.

**Requirement update (2026-10-08 — implemented):** Added PIC Outlet and Management to the role seeder and dynamic user assignment form. Both receive the shared `app.access` baseline; `users.manage` remains exclusive to Super Admin. The configured database was seeded with the six roles. Detailed per-role permission boundaries remain `[FOLLOW-UP]` and are not implied by this baseline.

**Next:** Feature 03 and Feature 04 are implemented. Lanjut Feature 05 — Data Stock.

## Purpose

Super Admin dapat mengelola pengguna dan akses sistem.

## UI

- [x] User list
- [x] Create user
- [x] Edit user
- [x] Activate/deactivate user
- [x] Assign role
- [x] Role list
- [x] Permission matrix

## Checklist

- [x] User CRUD (deletion uses recoverable soft delete)
- [x] Role assignment
- [x] Active/inactive status
- [x] Permission configuration
- [x] Prevent unauthorized self/escalation if required
- [x] Audit user changes

---

# FEATURE 03 — MASTER DATA

**Status:** Done (scope MVP, with documented technical assumptions)  
**Priority:** Critical  
**Access:** Super Admin (`users.manage`)

**Implementation summary:** Added CRUD screens for Product, Category, Unit, Supplier, and combined Warehouse/Outlet locations. Lists support search and active/inactive filtering; locations also filter by type. Product records require an active category and unit. Deletion is soft delete and create/update/delete actions are recorded in the existing administration audit log. The dashboard was not changed.

**Assumptions:**
- `[ASSUMPTION]` Minimal fields are used pending domain validation: product SKU/name/category/unit/description; category name/description; unit name/symbol/description; supplier name and optional contact fields; location code/name/type/address/description.
- `[ASSUMPTION]` Warehouse and outlet share a `locations` table and are distinguished by `warehouse` / `outlet` type.
- `[ASSUMPTION]` Master data records use recoverable soft deletion; unique identifiers stay reserved after deletion.
- `[UNKNOWN]` Minimum stock threshold is omitted until confirmed. Inventory-specific inactive/deletion constraints must be handled when stock transaction features are implemented.

**Files changed:**
- `app/Http/Controllers/Admin/MasterDataController.php`
- `app/Models/Category.php`
- `app/Models/Location.php`
- `app/Models/Product.php`
- `app/Models/Supplier.php`
- `app/Models/Unit.php`
- `app/Services/MasterDataService.php`
- `database/migrations/2026_10_07_000004_create_master_data_tables.php`
- `resources/views/admin/master-data/*`
- `resources/views/auth/account.blade.php`
- `routes/web.php`
- `tests/Feature/MasterDataTest.php`
- `docs/technolife-inventory-roadmap-v3.md`

**Verification:** Sebelum penyesuaian dashboard terbaru: `php artisan test` — 30 passed (207 assertions); `vendor\\bin\\pint --test` passed. Test perlu dijalankan ulang setelah penyesuaian template.

## Checklist

- [x] Product / Item CRUD, list, search, status, category/unit, and detail
- [x] Category CRUD
- [x] Unit CRUD
- [x] Supplier CRUD, detail, contact data, and status
- [x] Warehouse and Outlet CRUD, type filtering, detail, and status
- [x] Validation and duplicate identifier checks
- [x] Search and relevant filters
- [x] Super Admin access restriction
- [x] Audit records for master data create/update/delete
- [x] Automated tests for authorization, validation, create, search/filter, soft delete, and audit
- [ ] Minimum stock threshold (waiting confirmation)
- [ ] Confirm detailed business fields and location hierarchy with stakeholders

**Next:** Feature 04 — Dashboard has been implemented with a Partial status. Lanjut Feature 05 — Data Stock.

---

# FEATURE 04 — DASHBOARD

**Status:** Partial  
**Priority:** Critical  
**Access:** Dashboard mempertahankan layout yang sudah ada, dengan ringkasan dan aksi disesuaikan per role. Super Admin mendapat jumlah pengguna/role dan akses ke halaman tambah/kelola user. Menu administrasi tetap dibatasi permission `users.manage`.

**Implementation summary:** Layout, struktur, dan konsep visual dashboard lama dipertahankan. Kontennya menyesuaikan role: Super Admin melihat ringkasan pengguna dan role serta akses cepat ke halaman user terpisah; role operasional melihat aksi yang sesuai jobdesk. Navigasi utama dipindah ke sidebar; navbar mempertahankan logo, lokasi, pencarian, notifikasi, dan profil. Super Admin mendapat satu menu Pengguna yang menuju daftar akun dengan aksi tambah di halaman yang sama. Dashboard menampilkan data master yang nyata dan state kosong untuk saldo/mutasi yang belum tersedia. Gambar latar login dan badge keamanan sudah dihapus; pesan login memakai bahasa Indonesia yang lebih ramah.

**Dependencies / limits:**
- `[DEPENDENCY]` Real stock totals, low/expired stock, Stock In/Out today, movement history, pending approvals, and FEFO data require Features 05–16 and their confirmed business rules.
- `[CONFIRMED]` Every role needs dashboard content and features aligned to its jobdesk; Super Admin receives richer administration content within the existing dashboard design, including separate user-management pages.
- `[FOLLOW-UP]` Exact per-role KPI and permission matrix; actions follow confirmed jobdesk, while inventory widgets await source data.
- `[UNKNOWN]` WhatsApp delivery/configuration is not implemented; dashboard displays “Belum diatur”.

**Files changed:**
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `resources/views/dashboard.blade.php`
- `resources/views/auth/login.blade.php`
- `routes/web.php`
- `public/login.webp` (removed)
- `tests/Feature/DashboardFeatureTest.php`
- `tests/Feature/AuthenticationTest.php`
- `docs/technolife-inventory-roadmap-v3.md`

**Verification:** Sebelum penyesuaian dashboard terbaru: `php artisan test` — 30 passed (207 assertions); `vendor\\bin\\pint --test` passed. Test perlu dijalankan ulang setelah penyesuaian template.

## Checklist

- [x] Temporary shared dashboard baseline is accessible to all six roles
- [x] Dashboard greeting and active product/location counts use current user/database data
- [x] Hide fabricated stock figures and show empty states where source data is not available
- [x] Responsive mobile and desktop views retain the existing visual design
- [x] Explain unavailable quick actions instead of implying transactions were saved
- [x] Friendlier Indonesian login validation and credential alerts
- [x] Remove uploaded login background image, its reference, and the indicated login security badge
- [ ] Total stock, stock alerts, expired/near-expiry, and daily Stock In/Out metrics (depends on stock features)
- [ ] Inventory recent activity and pending action widgets (depends on transaction/approval features)
- [x] Dashboard actions and available summaries are adapted to each role within the existing layout
- [x] Preserve the dashboard design while presenting Super Admin content and actions in that layout
- [x] Keep user and role administration restricted to Super Admin
- [ ] WhatsApp alert status/configuration (depends on Feature 19)

**Next:** Feature 05 — Data Stock. Revisit dashboard as its inventory data sources become available.

---

# FEATURE 05 — DATA STOCK

**Status:** Partial  
**Priority:** Critical

**Implementation summary (updated 2026-10-08):** Daftar produk nyata tersedia di `/data-stock` dengan pencarian SKU/nama, filter kategori/status, pengurutan, pagination, dan detail produk. Foto produk menerima JPG/PNG/WebP maksimal 5 MB dan dikonversi ke WebP maksimal 1 MB. Saldo dihitung dari ledger pergerakan; dashboard dan saldo tabel Data Stock diperbarui otomatis setiap 10 detik. URL `/inventory` yang sebelumnya menampilkan demo statis diarahkan ke daftar data stok aktual. Saldo per lokasi, batch, expiry, dan Stock Out masih menyusul. Master data produk tetap dikelola Super Admin.

**Files changed:**
- app/Http/Controllers/InventoryController.php
- resources/views/inventory/stock/index.blade.php
- resources/views/inventory/stock/show.blade.php
- resources/views/dashboard.blade.php
- resources/views/inventory/dashboard.blade.php
- routes/web.php

## Purpose

Menjadi pusat informasi kondisi inventory saat ini.

## UI

```text
Data Stock
├── Search
├── Filter
├── Stock Table
│   ├── Item
│   ├── Category
│   ├── Location
│   ├── Current Stock
│   ├── Unit
│   ├── Status
│   └── Expiry
└── Detail Stock
```

## Detail Stock

- [x] Current quantity (aggregated across locations)
- [ ] Location
- [ ] Unit
- [ ] Batch
- [ ] Expiry
- [ ] Stock movement
- [x] Stock In history
- [ ] Stock Out history
- [x] Related Stock In receipt transactions

## Actions

- [ ] Stock In
- [ ] Stock Out
- [ ] Stock Opname sesuai permission
- [ ] View movement

## Checklist
- [x] Product inventory list from existing master data
- [x] Search, category/status filter, sorting, and pagination
- [x] Product detail with clear empty states for unavailable balance data
- [x] Permission-aware product create/edit links for Super Admin
- [x] Current stock balance from movement ledger
- [x] Auto-refresh visible stock balances every 10 seconds without resetting filters
- [x] Legacy `/inventory` demo route redirects to actual Data Stock
- [ ] Stock balances by location in product detail
- [ ] Batch and expiry visibility
- [x] Stock In movement and receipt history
- [ ] Stock Out movement history

**Next:** Feature 07 — Stock Out. Current balance is sourced from Stock In ledger entries; per-location detail and Stock Out remain pending.

---

# FEATURE 06 — STOCK IN

**Status:** Done (MVP; batch/expiry and receiving rules remain follow-up)  
**Priority:** Critical


## Purpose

Mencatat barang yang masuk ke warehouse/location dan menambah saldo inventory.

## Roles

- Super Admin (administration/testing access)
- Staff Gudang
- Purchasing jika workflow mengizinkan

## UI

- [x] Stock In list
- [x] Create Stock In
- [x] Reference document
- [x] Supplier/source
- [x] Date
- [x] Item selection (multiple products per receipt)
- [x] Quantity
- [x] Unit from product master
- [ ] Batch jika diperlukan
- [ ] Expiry jika diperlukan
- [x] Notes
- [x] Confirmation

## Workflow

```text
Open Stock In
   ↓
Select source/reference
   ↓
Add item + quantity
   ↓
Batch/expiry if required
   ↓
Validate
   ↓
Confirm
   ↓
Increase stock
   ↓
Create movement
   ↓
Audit
```

## Backend / Implementation

- [x] StockInService
- [x] Atomic transaction handling
- [x] Stock balance updated through ledger
- [x] Stock movement creation
- [x] Audit event
- [x] Responsive list, create, confirmation, and detail pages
- [x] Super Admin and Staff Gudang access via `stock.manage`

**Implementation assumptions:** Receipt is confirmed immediately on submission; reference is unique; supplier selection is optional when a source is provided; each product uses its configured master unit; batch and expiry are not required until their rules are confirmed. Super Admin can record receipts for administration/testing, and Staff Gudang can record them as part of the confirmed jobdesk.

**Files added/updated (2026-10-08):** `app/Http/Controllers/StockInController.php`, `app/Services/StockInService.php`, `app/Models/StockIn.php`, `app/Models/StockInItem.php`, `app/Models/StockMovement.php`, `database/migrations/2026_10_08_000003_create_stock_ins_tables.php`, `resources/views/inventory/stock-in/`, `resources/views/layouts/dashboard.blade.php`, `resources/views/dashboard.blade.php`, and `routes/web.php`.

## Validation

- [x] Item valid and active
- [x] Quantity > 0
- [x] Unit follows product master
- [x] Location valid and active
- [ ] Batch rule
- [ ] Expiry rule
- [x] Duplicate reference blocked by validation and unique database constraint

## Testing

- [ ] Normal Stock In
- [ ] Invalid quantity
- [x] Duplicate reference blocked
- [ ] Batch
- [ ] Expiry
- [ ] Audit
- [ ] Stock balance correctness

---

# FEATURE 07 — STOCK OUT

**Status:** Done (MVP; batch allocation/approval remain follow-up)  
**Priority:** Critical


## Purpose

Mengurangi stock berdasarkan pengeluaran/distribusi barang.

## Roles

- Staff Gudang
- Super Admin (administration/testing access)
- Other role only if approved by business rule

## UI

- [x] Stock Out list
- [x] Create Stock Out
- [x] Destination
- [x] Request/reference
- [x] Item (multiple products)
- [x] Quantity
- [ ] Batch allocation
- [x] Notes
- [x] Confirmation

## Workflow

```text
Stock Out Request / Action
        ↓
Check available stock
        ↓
Select/allocate eligible batch
        ↓
Confirm quantity
        ↓
Confirm Stock Out
        ↓
Decrease stock
        ↓
Create movement
        ↓
Audit
```

## Validation

- [x] Stock availability at selected source location
- [x] Destination
- [x] Quantity > 0
- [ ] Batch
- [x] Prevent negative stock; serialize issues per location
- [ ] Approval if required

## Testing

- [ ] Normal Stock Out
- [ ] Insufficient stock scenario
- [ ] Multiple items
- [ ] Multiple batches
- [ ] Unauthorized action
- [ ] Duplicate submit scenario

## Backend / Implementation

- [x] StockOutService with atomic transaction
- [x] Header, item rows, and stock movement ledger
- [x] Audit event for each Stock Out
- [x] Responsive list, create, confirmation, and detail pages
- [x] Super Admin and Staff Gudang access via `stock.manage`

**Implementation assumptions:** Stock Out is confirmed immediately and represents an issue from one selected location to a free-text destination. The system checks current balance and blocks negative stock. Batch allocation and approval are not applied until their rules are confirmed. Super Admin can record issues for administration/testing; Staff Gudang can record them per confirmed jobdesk.

**Files added/updated (2026-10-08):** `app/Http/Controllers/StockOutController.php`, `app/Services/StockOutService.php`, `app/Models/StockOut.php`, `app/Models/StockOutItem.php`, `app/Models/StockMovement.php`, `database/migrations/2026_10_08_000004_create_stock_outs_tables.php`, `resources/views/inventory/stock-out/`, `resources/views/layouts/dashboard.blade.php`, `resources/views/dashboard.blade.php`, and `routes/web.php`.

---

# FEATURE 08 — STOCK OPNAME

**Status:** Done (MVP; batch/FIFO and schedule automation remain follow-up)  
**Priority:** Critical


## Purpose

Membandingkan stock sistem dengan stock fisik.

## UI

- [x] Opname list
- [x] Create opname
- [x] Select location
- [x] Snapshot system quantity for active products at the location
- [x] Input physical quantity
- [x] Variance display
- [x] Reason required for variance
- [x] Submit for review
- [x] Purchasing/Super Admin approval or rejection
- [x] Detail/history

## Workflow

```text
Create Opname
   ↓
Snapshot System Stock
   ↓
Count Physical Stock
   ↓
Input Physical Qty
   ↓
Calculate Variance
   ↓
Review
   ↓
Approval if required
   ↓
Adjustment
   ↓
Stock Movement + Audit
```

## Validation

- [x] Physical quantity valid (zero or greater)
- [x] Variance calculated against captured snapshot
- [x] Reason required when variance exists
- [x] Submitted/approved/rejected opname locked

## Testing

- [ ] No variance
- [ ] Positive variance
- [ ] Negative variance
- [ ] Approval
- [ ] Adjustment
- [ ] Audit

## Backend / Implementation

- [x] Location snapshot with active-product system quantities
- [x] Staff Gudang creates/counts; Purchasing reviews; Super Admin can administer/count/review
- [x] Approval applies positive/negative variance through linked stock movements
- [x] Inventory transactions after count submission are carried forward when applying the approved count
- [x] Location-level locking across Stock In/Out and opname operations
- [x] Audit records for session start, submission, approval, and rejection
- [x] One open counting/approval session per location

**Implementation assumptions:** Approval is required for every submitted opname. Purchasing may approve/reject; Super Admin can perform both count and review for administration. The user selects weekly or month-end as a label only; automatic scheduling is not implemented. A rejected count is locked and requires a new session. The system snapshots all active products, including zero balances. Batch/FIFO and count freeze rules remain follow-up. At approval, the system carries forward movements recorded after submission so later transactions are not overwritten by the physical count.

**Files added/updated (2026-10-08):** `app/Http/Controllers/StockOpnameController.php`, `app/Services/StockOpnameService.php`, `app/Models/StockOpname.php`, `app/Models/StockOpnameItem.php`, `app/Models/StockMovement.php`, `database/migrations/2026_10_08_000005_create_stock_opnames_tables.php`, `database/migrations/2026_10_08_000006_add_count_snapshot_to_stock_opname_items.php`, `database/seeders/RolePermissionSeeder.php`, `resources/views/inventory/stock-opname/`, `resources/views/layouts/dashboard.blade.php`, `resources/views/dashboard.blade.php`, `app/Services/StockInService.php`, and `routes/web.php`.

---

# FEATURE 09 — STOCK ADJUSTMENT

**Status:** Done (MVP; approval requirement remains follow-up)  
**Priority:** High

## Roles

- Super Admin only (pending approval matrix clarification)

## Purpose

Melakukan koreksi saldo inventory berdasarkan alasan yang sah.

## UI

- [x] Adjustment list
- [x] Create adjustment
- [x] Item
- [x] Location
- [x] Current quantity
- [x] Adjustment quantity
- [x] Reason required
- [x] Reference
- [ ] Approval if required

## Rules

- [x] Hard delete transaction prohibited (no delete action; corrections use a new adjustment)
- [x] Reason required
- [ ] Approval requirement `[FOLLOW-UP]`
- [x] Audit old/new quantity

**Implementation summary:** Super Admin-only adjustments apply an increase/decrease to one product at one location. The service locks the location, recalculates current balance from movements, blocks negative results, stores before/after balances, writes a linked movement and audit entry, and never hard-deletes a transaction. Approval is not applied while the approval matrix remains `[FOLLOW-UP]`.

**Files added/updated (2026-10-08):** `app/Http/Controllers/StockAdjustmentController.php`, `app/Services/StockAdjustmentService.php`, `app/Models/StockAdjustment.php`, `app/Models/StockMovement.php`, `database/migrations/2026_10_08_000007_create_stock_adjustments_table.php`, `database/seeders/RolePermissionSeeder.php`, `resources/views/inventory/stock-adjustment/`, `resources/views/layouts/dashboard.blade.php`, `resources/views/dashboard.blade.php`, and `routes/web.php`.

## Testing

- [ ] Increase
- [ ] Decrease
- [ ] Invalid quantity
- [ ] Unauthorized adjustment
- [ ] Audit

---

# FEATURE 10 — STOCK MOVEMENT

**Status:** Done (MVP; transfer/waste movement types remain follow-up)  
**Priority:** High

## Purpose

Menyediakan jejak perubahan stock secara detail.

## UI

- [x] Movement list
- [x] Item filter
- [x] Location filter
- [x] Date filter
- [x] Transaction type
- [x] User
- [x] Reference number
- [x] Before quantity
- [x] Movement quantity
- [x] After quantity
- [x] Detail transaction

## Movement Types

- [x] Stock In
- [x] Stock Out
- [ ] Transfer
- [x] Adjustment
- [x] Opname
- [ ] Waste/Expired jika diperlukan

## Testing

- [ ] Every inventory transaction creates correct movement
- [ ] Before/after balance correct
- [ ] User attribution correct
- [ ] Timestamp correct

## Backend / Implementation

- [x] Read-only movement history and detail pages available to authenticated application roles
- [x] Filters for product, location, date range, transaction type, user, and reference
- [x] Before/after balances calculated from the signed ledger ordered by movement ID
- [x] Source reference and movement details link back to Stock In, Stock Out, Stock Opname, and Adjustment records
- [x] Riwayat sidebar and mobile navigation now open this history page

**Implementation assumptions:** This page is a read-only ledger view. Before/after balances are calculated in ledger insertion order so same-second transactions remain ordered; the transaction timestamp is the recorded movement timestamp. Transfer and waste/expired movement types will be added with their respective features.

**Files added/updated (2026-10-08):** `app/Http/Controllers/StockMovementHistoryController.php`, `app/Models/StockMovement.php`, `resources/views/inventory/stock-movements/`, `resources/views/layouts/dashboard.blade.php`, `resources/views/dashboard.blade.php`, and `routes/web.php`.

---

# FEATURE 11 — RIWAYAT TRANSAKSI

**Status:** Done (MVP; transfer history follows the transfer feature)  
**Priority:** High

## Primary Page

```text
Riwayat
├── Stock In
├── Stock Out
├── Stock Opname
├── Adjustment
├── Transfer
└── Activity / Audit
```

## UI

- [x] Unified history table
- [x] Transaction type filter
- [x] Date filter
- [x] Item filter
- [x] Location filter
- [x] User filter
- [x] Reference number
- [x] Detail page

## Detail

- [x] Header transaction
- [x] Items
- [x] Quantity
- [ ] Batch (not available in the current stock transaction model)
- [x] Location
- [x] User
- [x] Timestamp
- [x] Status
- [x] Audit trail for users with user administration permission

## Backend / Implementation

- [x] Unified read-only records for Stock In, Stock Out, Stock Opname, Adjustment, and administration audit logs
- [x] Filters for type, product, location, user, date range, and reference/action text
- [x] Detail pages display document header, products and quantities, current workflow status, and audit changes where available
- [x] Riwayat navigation opens the unified history; stock movement ledger remains linked from this page

**Implementation assumptions:** Stock In and Stock Out are considered completed when recorded because their current schemas do not store workflow status. Audit log rows are only visible to users with `users.manage`. Batch details and transfer records await their respective data models.

**Files added/updated (2026-10-08):** `app/Http/Controllers/TransactionHistoryController.php`, `resources/views/inventory/transaction-history/`, `resources/views/layouts/dashboard.blade.php`, and `routes/web.php`.

---

# FEATURE 12 — PURCHASING

**Status:** Done (MVP; detailed procurement, pricing, and partial-receiving rules remain follow-up)  
**Priority:** High

## Purpose

Mengelola proses pengadaan sebelum barang diterima.

## Modules

```text
Purchase Request
      ↓
Approval
      ↓
Purchase Order
      ↓
Supplier
      ↓
Receiving
      ↓
Stock In
```

## Purchase Request UI

- [x] PR list
- [x] Create PR
- [x] Add items
- [x] Quantity
- [x] Required date
- [x] Submit
- [x] Status
- [x] Approval status

## Purchase Order UI

- [x] PO list
- [x] Create PO from approved PR
- [x] Supplier
- [x] Items
- [x] Quantity
- [ ] Price if required (not included pending finance/valuation rules)
- [x] Expected delivery
- [x] Status

## Receiving

- [x] Receive against PO
- [ ] Partial receiving (workflow not confirmed; MVP requires full receipt)
- [ ] Over/under receiving handling
- [ ] Batch (batch model remains follow-up)
- [ ] Expiry (expiry rules remain follow-up)
- [x] Generate Stock In

## Important

Exact procurement workflow remains `[FOLLOW-UP]`.

## Backend / Implementation

- [x] Purchase Requests can be submitted with product quantities and required date, then reviewed by Super Admin
- [x] Purchasing role can issue one Purchase Order from each approved PR with supplier, receiving location, and expected delivery
- [x] Staff Gudang or Super Admin can receive an issued PO in full; receiving creates linked Stock In and stock ledger movements atomically
- [x] Audit events are recorded for PR submission/review, PO issue, and receipt
- [x] Purchasing menu and actions are permission-gated by role

**Implementation assumptions:** Because the detailed procurement approval matrix is still `[FOLLOW-UP]`, PR approval is restricted to Super Admin to prevent self-approval. The Purchasing role submits PRs and creates POs. PO receiving is limited to Staff Gudang and Super Admin using existing `stock.manage`. The MVP accepts only a full receipt matching PO quantities; partial, over, or under receipt, pricing, batch, and expiry are not implemented. PO generation does not create supplier communications.

**Files added/updated (2026-10-08):** `app/Http/Controllers/PurchasingController.php`, `app/Services/PurchasingService.php`, `app/Services/StockInService.php`, `app/Models/PurchaseRequest.php`, `app/Models/PurchaseRequestItem.php`, `app/Models/PurchaseOrder.php`, `app/Models/PurchaseOrderItem.php`, `app/Models/StockIn.php`, `database/migrations/2026_10_08_000008_create_purchasing_tables.php`, `database/seeders/RolePermissionSeeder.php`, `resources/views/purchasing/`, `resources/views/layouts/dashboard.blade.php`, and `routes/web.php`.

---

# FEATURE 13 — APPROVAL WORKFLOW

**Status:** Done (MVP for Purchase Request and Stock Opname; other approval candidates remain follow-up)  
**Priority:** High

## Purpose

Mengontrol transaksi yang membutuhkan persetujuan.

## Candidate Approval

- [x] Purchase Request
- [ ] Purchase Order (separate PO approval is not included; approved PR is required before PO creation)
- [ ] Stock Adjustment
- [x] Stock Opname
- [ ] Stock Out if required

## UI

- [x] Pending approval list
- [x] Detail
- [x] Approve
- [x] Reject
- [x] Reason (required for rejection)
- [x] Approval history

## Rules

- [x] Define approver per implemented transaction
- [x] Define status
- [x] Prevent unauthorized approval
- [x] Store approval timestamp
- [x] Store approval user
- [x] Store rejection reason

Exact approval matrix is `[FOLLOW-UP]`.

## Backend / Implementation

- [x] Central approval inbox combines pending Purchase Requests and Stock Opname with separate detail workflows
- [x] Purchasing approval permission is limited to Super Admin for PR; Opname approval remains Purchasing and Super Admin
- [x] Self-approval is blocked for PR requesters and Stock Opname counters
- [x] Rejection requires a reason; decisions store reviewer, timestamp, status, and review note
- [x] Existing audit logs record PR and Stock Opname approval decisions
- [x] Approval menu is visible only to roles with an approval permission

**Implementation assumptions:** Until the approval matrix is confirmed, only workflows already required by implemented features are included. A PO requires an approved PR but has no separate approval step. Stock Adjustment remains Super Admin-only without approval; Stock Out approval remains disabled pending confirmation.

**Files added/updated (2026-10-08):** `app/Http/Controllers/ApprovalController.php`, `app/Http/Controllers/PurchasingController.php`, `app/Services/PurchasingService.php`, `app/Services/StockOpnameService.php`, `resources/views/approvals/`, `resources/views/purchasing/request-show.blade.php`, `resources/views/layouts/dashboard.blade.php`, and `routes/web.php`.

---

# FEATURE 14 — BATCH & EXPIRY

**Status:** Done (MVP; 30-day warning is an assumption and expired-stock disposal remains follow-up)  
**Priority:** High

## Purpose

Menyimpan dan menampilkan informasi batch, expiry, dan status umur inventory.

## Batch

- [x] Batch number captured during Stock In and PO receiving
- [x] Received date sourced from the receipt
- [x] Quantity sourced from the receipt line
- [x] Remaining quantity calculated from batch-linked stock movements
- [x] Location shown from the receipt
- [x] Expiry date captured and shown when provided
- [x] Age in days shown for new receipt lots without an expiry date (counted from receipt date)

## Expiry

- [x] Expiry date can be recorded for Stock In and PO receiving
- [x] Near expiry status shown in batch selection and product details
- [x] Expired status shown; expired batch is blocked from Stock Out
- [x] Near-expiry warning threshold currently defaults to 30 days
- [ ] Expired stock disposal / adjustment workflow

## Important

- FEFO allocation confirmed and implemented in Feature 15
- Durability definition `[FOLLOW-UP]`
- Near-expiry threshold: 30 days is an implementation assumption until Technolife confirms the policy `[FOLLOW-UP]`
- Expired handling: expired batches cannot be issued; disposal and write-off workflow remain `[FOLLOW-UP]`

**Implementation assumptions:** Every new Stock In receipt line is tracked as a lot, even when batch number and expiry are not provided. Age for lots without an expiry date is counted from the receipt date; this is stock age, not harvest age or a freshness guarantee. Legacy receipts without batch metadata remain available under an “untracked” balance. Stock Out allocates the requested quantity automatically using FEFO as described in Feature 15. The server calculates FEFO allocation against the available batch and total product balances inside a transaction. Batch balances reflect movements allocated to that batch; Opname and Adjustment are not assigned to batches yet. Near-expiry means expiry within 30 calendar days, inclusive.

**Files added/updated (2026-10-08):** `database/migrations/2026_10_08_000009_add_batch_tracking_to_stock_movements.php`, `database/migrations/2026_10_08_000011_add_batch_received_at_to_stock_in_items.php`, `app/Services/BatchInventoryService.php`, `app/Services/StockInService.php`, `app/Services/StockOutService.php`, `app/Services/PurchasingService.php`, Stock In/Out and purchasing controllers/views, stock movement/history controllers/views, product stock detail, and `app/Models/StockInItem.php`, `StockOutItem.php`, `StockMovement.php`.

---

# FEATURE 15 — FEFO ALLOCATION

**Status:** Done (MVP; FEFO confirmed 2026-10-08)  
**Priority:** High

## Purpose

Mengalokasikan Stock Out ke batch yang tanggal kedaluwarsanya paling dekat terlebih dahulu (First Expired, First Out).

## UI

- [x] Show eligible batches in allocation preview
- [x] FEFO recommendation
- [x] Allocation preview shows partial/multiple batch split
- [ ] Manual override only if permitted
- [x] Allocation detail recorded on Stock Out

## Backend

- [x] Select eligible batch with nearest expiry first
- [x] Support partial batch
- [x] Support multiple batch allocation
- [x] Prevent negative total and batch stock
- [x] Record FEFO allocation decision in audit data and stock movements

## Verification

- [ ] One batch
- [ ] Multiple batches
- [ ] Partial batch
- [ ] Multiple batch allocation
- [ ] Insufficient stock
- [ ] Expired batch interaction

**Implementation assumptions:** FEFO is applied automatically on the server; users enter product and quantity, and the preview shows the same allocation order. Expired batches are excluded as of the transaction date. When expiry dates tie, earlier receipt date then batch ID comes first. Batches with no expiry date are allocated after dated batches and ordered by receipt date; legacy untracked stock is allocated last. Manual override is not enabled. The server recalculates allocation inside the transaction, so the browser preview is informational and cannot change the allocation rule.

**Files added/updated (2026-10-08):** `app/Services/StockOutService.php`, `app/Services/BatchInventoryService.php`, `app/Http/Controllers/StockOutController.php`, `resources/views/inventory/stock-out/create.blade.php`, and `resources/views/inventory/stock-out/_fields.blade.php`.

---
# FEATURE 16 — TRANSFER / DISTRIBUTION

**Status:** Implemented (internal outlet request, warehouse picking, assigned outlet receipt, partial receipt, and discrepancy closeout)  
**Priority:** High

## Purpose

Mencatat distribusi stok dari gudang ke outlet dan konfirmasi penerimaan oleh PIC Outlet.

## Workflow

```text
PIC Outlet submits stock request
   ↓
Staff Gudang starts picking and confirms dispatch quantities
   ↓
Transfer in transit
   ↓
PIC Outlet records one or more receipts and closes the delivery
   ↓
Stock In and outlet balance update for the actual received quantity; shortages are recorded with a reason
```

## UI

- [x] Transfer list and detail
- [x] Create warehouse-to-outlet transfer
- [x] In-transit status
- [x] Confirm received quantities and close a full receipt
- [x] Show source/destination batches, quantities, and generated Stock In/Out references
- [x] Outlet stock request list and submission
- [x] Warehouse picking from an outlet request
- [x] Partial receipt and discrepancy entry
- [x] Assigned outlet restriction for PIC Outlet

## Backend

- [x] Transfer and transfer-item records
- [x] FEFO stock deduction from warehouse at dispatch
- [x] Stock addition at outlet only for quantities actually received
- [x] Carry batch number and expiry date into outlet-local receipt lots
- [x] Audit dispatch and receipt actions
- [x] Prevent over-receipt and receipt after closeout
- [x] Preserve a receipt history for partial deliveries

## Permissions

- `transfer.manage`: Super Admin and Staff Gudang
- `transfer.receive`: Super Admin and PIC Outlet
- PIC Outlet can access only requests and transfers for their assigned outlet. Super Admin can manage all locations.

**Implementation assumptions:** Transfers are only from an active warehouse to an active outlet. Dispatch immediately reduces warehouse stock; outlet stock increases only for quantities actually recorded as received. A final receipt with outstanding quantity requires a reason and records the shortfall without adding it to outlet stock. Approval and cancellation remain out of scope because their rules are not confirmed. Batch number, expiry, and original lot receipt date are carried into outlet receipt lots. Warehouse allocations use the confirmed FEFO rule. WRT remains an external supplier/partner; direct WRT-to-outlet delivery is not implemented until the operating rule is confirmed.

**Files added/updated (2026-10-08):** transfer, transfer-request, and transfer-receipt migrations and models, `app/Services/StockTransferService.php`, `app/Http/Controllers/StockTransferController.php`, user outlet assignment management, location-scoped dashboard and stock views, `routes/web.php`, and `resources/views/inventory/transfers/`.

**Verification:** PHP lint, Feature 16 migrations on the configured database, route registration, and Blade compilation succeeded. Operational UAT remains pending.

---
# FEATURE 17 — LAPORAN

**Status:** Implemented (filterable report center and CSV export; other export formats pending decision)  
**Priority:** High

## Primary Page

```text
Laporan
├── Stock
├── Stock In
├── Stock Out
├── Stock Opname
├── Adjustment
├── Stock Movement
├── Expiry
└── Purchasing
```

## Common Filters

- [x] Date/period for transaction reports
- [x] Location (restricted to the assigned outlet for PIC Outlet)
- [x] Item
- [x] Category
- [x] Transaction type/status where applicable
- [x] User for transaction reports

## Reports

- [x] Current stock report by product and location
- [x] Stock In report
- [x] Stock Out report
- [x] Stock Opname report
- [x] Adjustment report
- [x] Stock Movement report
- [x] Expiry and stock-age report
- [x] Purchasing report (purchase orders and line totals)

## Export

- [x] CSV (Excel-compatible UTF-8, semicolon-delimited)
- [ ] Excel
- [ ] PDF

> Export format selain CSV tetap mengikuti keputusan user.

**Implementation:** One report page selects the report type and applies the relevant common filters. PIC Outlet data is restricted to the assigned outlet; purchasing reports are available to Super Admin, Management, Finance, and Purchasing. CSV export uses the same filters as the page and neutralizes spreadsheet formula input.

---

# FEATURE 18 — LOW STOCK ALERT

**Status:** Implemented  
**Priority:** High

## Purpose

Memberikan peringatan ketika stock mendekati batas minimum.

## Rule

- [x] Define minimum stock / threshold per product per location
- [x] Trigger an alert when stock reaches or drops below the configured minimum
- [x] Notify Super Admin, Staff Gudang, and the PIC assigned to the affected outlet
- [x] Keep one active alert episode per product/location and resolve it when stock recovers
- [x] Store alert history and in-app database notifications

The threshold is configured separately for each product at each location. A value of `0` disables alerts for that pair. When stock is at or below the threshold, the system creates one active alert and sends an in-app notification. Further movements update that alert rather than sending duplicates. The alert resolves automatically when stock rises above the threshold, and remains in history. Super Admin and Staff Gudang can see alerts across locations; a PIC Outlet sees only their assigned outlet. Only Super Admin can configure thresholds.

## In-App Alert

- [x] Dashboard low stock count
- [x] Data Stock status with affected location
- [x] Notification history and active alert page
- [x] Per-location threshold configuration for Super Admin

---

# FEATURE 19 — WHATSAPP ALERT

**Status:** Partial — Laravel queue/configuration is ready; WhatsApp Web activation is pending provider approval and service setup.  
**Priority:** Medium/High

## Architecture

```text
Laravel Inventory
      ↓
Low Stock Event
      ↓
Laravel Queue / Job
      ↓
Node.js WhatsApp Service
      ↓
whatsapp-web.js
      ↓
WhatsApp Account
      ↓
Recipient
```

## Node.js Service

- [x] Create `whatsapp-service` scaffold
- [x] Node.js >= 18
- [x] Authentication/session persistence scaffold
- [x] QR login and connection status endpoints
- [x] Send message endpoint
- [x] Reconnect through persisted LocalAuth session
- [x] Retry/backoff
- [x] Failure logging
- [x] Notification history
- [x] Session recovery after server restart
- [ ] Approve the unofficial provider risk and resolve dependency audit findings before activation

## Laravel Integration

- [x] Low stock event dispatch
- [x] Queue job
- [x] Provider interface
- [x] API/service communication
- [x] Timeout handling
- [x] Retry/backoff

## Configuration UI

- [x] WhatsApp connection status
- [x] QR login if required
- [x] Connected account
- [x] Recipient list
- [x] Enable/disable
- [x] Message template
- [ ] Test message (requires approved provider connection)
- [x] Last successful send
- [x] Last failed send

## Provider Abstraction

```text
NotificationProvider
    ├── WhatsAppWebJsProvider
    ├── OfficialWhatsAppProvider [FUTURE]
    └── OtherProvider [FUTURE]
```

## Risk

`whatsapp-web.js` adalah library tidak resmi yang menggunakan WhatsApp Web. Production approval and operational risk must be confirmed.

The owner approved using `whatsapp-web.js` despite the provider risk. `npm audit --omit=dev` reports 6 high-severity transitive dependency findings for the selected `whatsapp-web.js`/Puppeteer versions. The local provider approval flag is enabled; message delivery remains off until the WhatsApp account is connected and a Super Admin explicitly enables alerts with recipients.

- [x] Confirm Technolife accepts the `whatsapp-web.js` provider risk
- [ ] Prefer dedicated business/system number
- [ ] Do not use employee personal number
- [ ] Keep in-app alert as fallback

---

# FEATURE 20 — AUDIT LOG

**Status:** Planned  
**Priority:** High

## Purpose

Mencatat siapa melakukan apa, kapan, dan terhadap data apa.

## Events

- [ ] Login/security events if required
- [ ] Master data changes
- [ ] Stock In
- [ ] Stock Out
- [ ] Opname
- [ ] Adjustment
- [ ] Approval
- [ ] Purchasing
- [ ] Configuration changes
- [ ] Notification send result

## Data

- [ ] User
- [ ] Action
- [ ] Entity
- [ ] Entity ID
- [ ] Timestamp
- [ ] Old value if needed
- [ ] New value if needed
- [ ] Reference

---

# FEATURE 21 — SYSTEM CONFIGURATION

**Status:** Planned  
**Priority:** Medium

## Configurable Items

- [ ] Role/permission
- [ ] Warehouse
- [ ] Outlet
- [ ] Category
- [ ] Unit
- [ ] Supplier
- [ ] Stock status
- [ ] Expiry warning
- [ ] Minimum stock
- [ ] Approval settings
- [ ] Notification settings

## Rule

Configuration must not allow users to corrupt historical inventory transactions.

---

# 9. CROSS-FEATURE TECHNICAL TASKS

## Task T01 — Project Foundation

- [ ] Laravel setup
- [ ] Environment setup
- [ ] Tailwind
- [ ] Filament
- [ ] Authentication
- [ ] Base layout
- [ ] Navigation
- [ ] Error handling
- [ ] Logging

## Task T02 — Domain/Application Layer

- [ ] Actions
- [ ] Services
- [ ] Policies
- [ ] Events
- [ ] Jobs
- [ ] DTO/value objects if useful
- [ ] Exceptions

Example:

```text
StockOutController
      ↓
StockOutService
      ↓
FifoAllocationService
      ↓
StockBatch
      ↓
StockMovement
```

Controllers should orchestrate, not contain complex inventory business logic.

## Task T03 — Database Foundation

- [ ] Migration convention
- [ ] Foreign keys
- [ ] Indexes
- [ ] Transaction boundaries
- [ ] Seeders
- [ ] Factories

## Task T04 — UI System

- [ ] Typography
- [ ] Spacing
- [ ] Table component
- [ ] Form component
- [ ] Modal/drawer
- [ ] Status badge
- [ ] Alert
- [ ] Empty state
- [ ] Loading state
- [ ] Error state
- [ ] Confirmation dialog

---

# 10. TESTING PER FEATURE

Setiap feature wajib memiliki:

- [ ] Happy path
- [ ] Validation test
- [ ] Unauthorized access test
- [ ] Permission test
- [ ] Duplicate submission test jika relevan
- [ ] Database consistency test
- [ ] Audit test jika relevan
- [ ] UI usability test

## Critical Inventory Scenarios

### Scenario A — Stock In

```text
Receive item
→ Stock In
→ Stock increases
→ Movement created
→ Audit created
```

### Scenario B — Stock Out

```text
Request / Stock Out
→ Check stock
→ Allocate batch if required
→ Stock decreases
→ Movement created
→ Audit created
```

### Scenario C — FEFO

```text
Batch A expires sooner
Batch B expires later
Request quantity
→ Eligible batch with nearest expiry selected first
```

### Scenario D — Stock Opname

```text
System stock
→ Physical count
→ Variance
→ Approval if required
→ Adjustment
→ Movement
→ Audit
```

### Scenario E — Low Stock

```text
Stock changes
→ Threshold reached
→ In-app alert
→ Queue
→ WhatsApp alert
→ Notification log
```

---

# 11. AGILE SPRINT PLAN

Sprint is provisional and follows actual stakeholder priority.

## Sprint 0 — Discovery

- [ ] Business process
- [ ] Actors
- [ ] Requirements
- [ ] Business rules
- [ ] Initial backlog

## Sprint 1 — Foundation + Access

- [ ] Project setup
- [ ] Authentication
- [ ] User
- [ ] Role/permission
- [ ] UI system

## Sprint 2 — Master Data + Dashboard

- [ ] Master Data
- [ ] Dashboard
- [ ] Navigation

## Sprint 3 — Data Stock + Stock In

- [ ] Data Stock
- [ ] Stock detail
- [ ] Stock In
- [ ] Stock movement foundation

## Sprint 4 — Stock Out + Distribution

- [ ] Stock request
- [ ] Picking
- [ ] Stock Out
- [ ] Distribution
- [ ] Receiving

## Sprint 5 — Stock Opname + Adjustment

- [ ] Stock Opname
- [ ] Variance
- [ ] Adjustment
- [ ] Audit

## Sprint 6 — Purchasing + Approval

- [ ] Purchase Request
- [ ] Purchase Order
- [ ] Approval
- [ ] Receiving integration

## Sprint 7 — Batch + FEFO + Expiry

- [x] Batch
- [x] FEFO
- [x] Expiry
- [x] Near expiry (30-day assumption)
- [x] Expired batches blocked from Stock Out (disposal follow-up)

## Sprint 8 — Riwayat + Laporan

- [ ] Riwayat
- [ ] Reports
- [ ] Export

## Sprint 9 — Notification

- [ ] Low stock
- [ ] WhatsApp service
- [ ] Queue
- [ ] Notification history

## Sprint 10 — Hardening + UAT

- [ ] Testing
- [ ] Security
- [ ] UX refinement
- [ ] Performance
- [ ] Bug fixing
- [ ] UAT

---

# 12. DEPLOYMENT

- [ ] Production environment
- [ ] Environment variables
- [ ] Database migration
- [ ] Seeder strategy
- [ ] Queue worker
- [ ] Scheduler if needed
- [ ] WhatsApp service deployment if approved
- [ ] Backup
- [ ] Logging
- [ ] Error monitoring
- [ ] Production testing
- [ ] UAT
- [ ] Deployment documentation

---

# 13. PROJECT DOCUMENTATION

## Website / Technical

- [ ] Architecture documentation
- [ ] Database documentation
- [ ] Feature documentation
- [ ] Business logic documentation
- [ ] API/service documentation if applicable
- [ ] Deployment documentation
- [ ] User guide

## Thesis / Academic

The website roadmap is implementation-focused. Academic artifacts remain separate and are produced from the validated system:

- [ ] Use Case Diagram
- [ ] Flow Event
- [ ] Activity Diagram
- [ ] Sequence Diagram
- [ ] Class Diagram
- [ ] UI documentation
- [ ] Testing documentation

---

# 14. CURRENT REQUIREMENT STATUS

## CONFIRMED

- [x] Technolife has internal warehouse.
- [x] Warehouse distributes inventory to Cafe.
- [x] Warehouse distributes inventory to Billiard.
- [x] Warehouse distributes inventory to Resto.
- [x] WRT/Main Kitchen is external to Technolife.
- [x] WRT acts as supplier/partner.
- [x] WRT sends materials/goods to Technolife.
- [x] Staff Gudang is involved.
- [x] Purchasing is involved.
- [x] Finance is involved.
- [x] Confirmed application roles/jobdesk: Super Admin, Staff Gudang, PIC Outlet, Management, Purchasing, Finance.
- [x] Implement PIC Outlet and Management in role seeder, user role assignment, and `app.access` permission baseline.
- [x] Stock In is required.
- [x] Stock Out is required.
- [x] Stock Opname is required.
- [x] Main website has four primary pages: Dashboard, Data Stock, Riwayat, Laporan.
- [x] System should be flexible/customizable.
- [x] UX should be user-friendly.
- [x] Low-stock WhatsApp alert is a desired system capability.

## FOLLOW-UP / UNKNOWN

- [ ] Exact WRT → warehouse workflow
- [ ] Whether WRT can ship directly to outlet
- [ ] Whether WRT requires system access
- [ ] Exact procurement workflow
- [ ] Exact approval matrix
- [x] FEFO allocation rule confirmed: nearest expiry date first
- [ ] Batch rules
- [ ] Expiry rules
- [ ] Durability definition
- [ ] Near-expiry threshold
- [ ] Expired handling
- [ ] Stock Opname workflow
- [ ] Stock Adjustment workflow
- [ ] Outlet receiving workflow
- [ ] Outlet stock management
- [ ] Minimum stock threshold
- [ ] WhatsApp recipients
- [ ] WhatsApp message template
- [ ] WhatsApp operational account
- [ ] Production approval for whatsapp-web.js
- [ ] Reporting details
- [ ] Database engine
- [ ] Hosting/deployment environment

---

# 15. AI CODING WORKFLOW

Sebelum coding sebuah feature:

```text
1. Read this roadmap
2. Check feature status
3. Check requirement status
4. Check business rule
5. Check dependency feature
6. Implement feature
7. Test feature
8. Update checklist
9. Report result
```

AI **dilarang** melompat langsung ke feature yang memiliki requirement `[UNKNOWN]` apabila requirement tersebut memengaruhi business logic inti.

---

# 16. FEATURE STATUS BOARD

Gunakan bagian ini sebagai overview cepat.

| # | Feature | Priority | Status |
|---|---|---|---|
| 01 | Authentication & Access | Critical | Done (six-role shared access baseline) |
| 02 | User & Role Management | High | Done (six confirmed roles; detailed permission matrix follow-up) |
| 03 | Master Data | Critical | Done (scope MVP) |
| 04 | Dashboard | Critical | Partial |
| 05 | Data Stock | Critical | Partial (live ledger balance; per-location/batch follow-up) |
| 06 | Stock In | Critical | Done (MVP; batch/expiry follow-up) |
| 07 | Stock Out | Critical | Done (MVP; FEFO allocation implemented, approval follow-up) |
| 08 | Stock Opname | Critical | Done (MVP; batch/FIFO and schedule automation follow-up) |
| 09 | Stock Adjustment | High | Done (MVP; approval matrix follow-up) |
| 10 | Stock Movement | High | Done (MVP; transfer/waste movement types follow-up) |
| 11 | Riwayat Transaksi | High | Planned |
| 12 | Purchasing | High | Planned |
| 13 | Approval Workflow | High | Planned |
| 14 | Batch & Expiry | High | Done (MVP; expired-stock disposal workflow follow-up) |
| 15 | FEFO Allocation | High | Done (MVP; allocation verified by UAT) |
| 16 | Transfer / Distribution | High | Done (MVP; UAT pending) |
| 17 | Laporan | High | Planned |
| 18 | Low Stock Alert | High | Planned |
| 19 | WhatsApp Alert | Medium/High | Partial (queue/configuration complete; provider activation pending approval) |
| 20 | Audit Log | High | Planned |
| 21 | System Configuration | Medium | Planned |

---

# 17. FINAL PROJECT CHECKLIST

- [ ] Discovery completed
- [ ] Business process validated
- [ ] Requirements validated
- [ ] Business rules validated
- [ ] Feature backlog approved
- [ ] Authentication completed
- [ ] Master Data completed
- [ ] Dashboard completed
- [ ] Data Stock completed
- [x] Stock In completed (MVP; batch and expiry remain follow-up)
- [x] Stock Out completed (MVP; FEFO allocation implemented, approval remains follow-up)
- [x] Stock Opname completed (MVP; batch/FIFO and schedule automation remain follow-up)
- [x] Stock Adjustment completed (MVP; approval matrix follow-up)
- [x] Stock Movement completed (MVP; transfer and waste movement types remain follow-up)
- [x] Riwayat completed (MVP; transfer history and batch detail remain follow-up)
- [x] Purchasing completed (MVP; procurement workflow details remain follow-up)
- [x] Approval completed (MVP for PR and Stock Opname; other approval candidates remain follow-up)
- [x] Batch/Expiry completed (MVP; expired-stock disposal workflow remains follow-up)
- [x] FEFO allocation completed (MVP; UAT pending)
- [x] Distribution completed (MVP; full receipt only, UAT pending)
- [ ] Laporan completed
- [ ] Low Stock Alert completed
- [ ] WhatsApp Alert completed
- [ ] Audit Log completed
- [ ] Testing completed
- [ ] UAT completed
- [ ] Deployment completed
- [ ] Documentation completed

---

# CHANGELOG — ROLE REQUIREMENT UPDATE

Date: 2026-10-08

- Incorporated the latest confirmed six-role structure and jobdesk from `technolife-inventory-roadmap-v3 (new).md`.
- Replaced the previous provisional four-role context, including the earlier assumption that Management was not an application role.
- Preserved the implementation history for Features 01–04 and distinguished it from the updated requirements.
- Recorded the initial implementation gap for PIC Outlet and Management; it is resolved in the follow-up update below.
- Dashboard requirements now distinguish the originally implemented four roles from the six confirmed roles; the shared dashboard baseline is now available to all six.
- Detailed permission matrix, PIC Outlet scope, Stock Opname batch/FEFO allocation rules and schedule automation, and finance valuation method remain follow-up items. The Stock Opname MVP has a Purchasing approval flow.
- Implemented the six confirmed roles in `RolePermissionSeeder`; all receive baseline `app.access`, while `users.manage` remains Super Admin-only.
- Added tests for assigning PIC Outlet and Management and verified dashboard access for all six roles.
- Ran `php artisan db:seed --class=RolePermissionSeeder --force` against the configured database; verified all six role records and labels.
- Verified PIC Outlet and Management receive `app.access` only; `users.manage` remains exclusive to Super Admin.

# 18. PROJECT DEFINITION

**Working Name:** Technolife Inventory Management System

**Academic Direction:**

> Rancang Bangun Sistem Informasi Manajemen Persediaan Berbasis Web pada Technolife

Title is **NOT FINAL**.

Potential academic focus remains dependent on validated business rules, especially inventory management, Stock In/Out, Stock Opname, FIFO, batch, expiry, and warehouse/outlet distribution.
