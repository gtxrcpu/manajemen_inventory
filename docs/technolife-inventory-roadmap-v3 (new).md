# FINAL AI CODING ROADMAP V3 — TECHNO LIFE INVENTORY MANAGEMENT SYSTEM

> **Project Type:** Web-based Inventory Management System  
> **Target:** MVP Production Ready, extensible, configurable, user-friendly  
> **Primary Stack:** Laravel + Blade + Tailwind CSS + Filament  
> **Development Method:** Agile / iterative development  
> **Architecture Approach:** Modular, service-oriented Laravel application with clear domain/business logic separation

---

# 0. SOURCE OF TRUTH

Sebelum mengerjakan task apa pun, AI wajib membaca dan mengikuti file ini.

File ini adalah **source of truth utama untuk roadmap dan status pengerjaan project**.

## Rules

1. Jangan langsung membuat fitur yang belum memiliki business requirement.
2. Jangan mengarang business rule Technolife.
3. Jika informasi belum tersedia, tandai sebagai `[UNKNOWN]`.
4. Jika AI membuat asumsi untuk kebutuhan desain sementara, tandai `[ASSUMPTION]`.
5. Jika requirement sudah dikonfirmasi oleh user/stakeholder, tandai `[CONFIRMED]`.
6. Jika requirement masih perlu dikonfirmasi, tandai `[FOLLOW-UP]`.
7. Jangan mengubah business logic hanya karena lebih mudah secara teknis.
8. Utamakan UX yang sederhana, jelas, cepat, dan ramah untuk user operasional.
9. Sistem harus dibuat configurable/customizable sejauh tidak mengorbankan integritas data.
10. Jangan melakukan destructive operation terhadap transaksi inventory tanpa mekanisme audit/correction.
11. Setiap task yang selesai wajib memperbarui checklist pada file ini.
12. Jika task terblokir, jangan memaksakan implementasi. Jelaskan blocker.
13. Jangan membuat database final sebelum domain dan business process cukup tervalidasi.
14. Jangan membuat UI final sebelum workflow operasional utama dipahami.

---

# 1. CURRENT BUSINESS CONTEXT

Technolife memiliki warehouse/gudang internal yang digunakan untuk menyimpan dan mendistribusikan inventory ke beberapa unit/outlet.

Unit/outlet yang sudah diketahui:

- Cafe
- Billiard
- Resto

Technolife juga memiliki hubungan dengan:

- WRT / Main Kitchen — **pihak eksternal**
- WRT berperan sebagai supplier/mitra eksternal yang mengirim bahan/barang ke Technolife.

## Internal Actors

- Storeman / Staff Gudang
- Purchasing
- Management
- Finance
- Unit/Outlet: Cafe
- Unit/Outlet: Billiard
- Unit/Outlet: Resto

## Application Roles — CONFIRMED

Struktur role aplikasi saat ini ditetapkan menjadi **6 role** berdasarkan konfirmasi terbaru.

| Role | Jobdesk / Tanggung Jawab Utama |
|---|---|
| **Super Admin** | Full control terhadap sistem, user, role/permission, master data, konfigurasi, monitoring, dan administrasi. |
| **Staff Gudang** | Input barang pembelian/Stock In, menerima dan mengecek fisik barang yang datang, mengelola barang masuk, serta saat barang keluar menyerahkan barang secara fisik kepada PIC Outlet dan mencatat Stock Out. |
| **PIC Outlet** | Mengecek stok outlet dan menerima barang dari Staff Gudang. |
| **Management** | Melihat dashboard umum untuk mengetahui kondisi stok secara keseluruhan dan stok di gudang. Fokus pada informasi/monitoring, bukan transaksi operasional. |
| **Purchasing** | Melihat kondisi stok, mengontrol/mengawasi aktivitas Staff Gudang, serta melakukan approval terhadap hasil Stock Opname secara mingguan dan akhir bulan. |
| **Finance** | Melihat dashboard stok seluruh gudang dan menggunakan informasi stok untuk menghitung/memonitor valuasi persediaan. |

### Role Boundary

- Staff Gudang: eksekusi operasional fisik dan pencatatan barang masuk/keluar.
- PIC Outlet: pengecekan dan penerimaan barang di outlet.
- Purchasing: kontrol aktivitas gudang dan approval Stock Opname.
- Management: pengguna informasi/dashboard umum.
- Finance: pengguna informasi stok dan valuasi persediaan.
- Super Admin: kendali administratif penuh.

> **Status:** `[CONFIRMED]`. Detail permission matrix dan workflow yang belum dijelaskan tetap menjadi task follow-up.

## External Party

- WRT / Main Kitchen

> Status hubungan WRT sebagai supplier sudah diketahui secara awal, tetapi detail workflow dan data yang dipertukarkan masih perlu dikonfirmasi.

---

# 2.1 CONFIRMED ROLE REQUIREMENTS

## Super Admin
- [x] Full system control
- [x] User, role/permission, master data, configuration, monitoring

## Staff Gudang
- [x] Input barang pembelian
- [x] Receive physical goods
- [x] Record Stock In
- [x] Check physical quantity of incoming goods
- [x] Hand outgoing goods to PIC Outlet
- [x] Record Stock Out
- [ ] Detailed Stock Opname execution flow

## PIC Outlet
- [x] Check outlet stock
- [x] Receive physical goods from Staff Gudang
- [ ] Detailed discrepancy/partial receiving rules

## Purchasing
- [x] View stock
- [x] Control/monitor Staff Gudang
- [x] Approve Stock Opname
- [x] Weekly review/approval
- [x] End-of-month review/approval
- [ ] Detailed purchasing/procurement workflow
- [ ] Detailed permission matrix

## Management
- [x] General dashboard access
- [x] View current stock
- [x] View stock by warehouse
- [ ] Additional management reports/filters

## Finance
- [x] View stock dashboard
- [x] View stock across all warehouses
- [x] Use stock data for inventory valuation
- [ ] Exact valuation method
- [ ] Required valuation reports

> Role responsibilities above are `[CONFIRMED]`. Detailed permission granularity and unresolved transaction rules remain follow-up tasks.

# 2A. PRIMARY PAGE ARCHITECTURE

Target utama website:

```text
1. Dashboard
2. Data Stock
3. Riwayat
4. Laporan
```

## Dashboard
- [ ] Total stock
- [ ] Low stock
- [ ] Near expiry
- [ ] Expired
- [ ] Stock In hari ini
- [ ] Stock Out hari ini
- [ ] Pending action
- [ ] Quick action
- [ ] WhatsApp alert status

## Data Stock
- [ ] Daftar item
- [ ] Current stock
- [ ] Location
- [ ] Unit
- [ ] Batch
- [ ] Expiry
- [ ] Search
- [ ] Filter
- [ ] Detail stock
- [ ] Stock In
- [ ] Stock Out
- [ ] Stock Opname sesuai permission
- [ ] Stock movement

## Riwayat
- [ ] Stock In history
- [ ] Stock Out history
- [ ] Stock Opname history
- [ ] Adjustment history
- [ ] Transfer history
- [ ] User
- [ ] Timestamp
- [ ] Reference number
- [ ] Search
- [ ] Date filter
- [ ] Item filter
- [ ] Location filter
- [ ] Transaction type filter
- [ ] Transaction detail
- [ ] Audit trail

## Laporan
- [ ] Stock report
- [ ] Stock In report
- [ ] Stock Out report
- [ ] Stock Opname report
- [ ] Adjustment report
- [ ] Stock Movement report
- [ ] Expiry report
- [ ] Purchasing report jika diperlukan
- [ ] Period filter
- [ ] Location filter
- [ ] Item filter
- [ ] Export jika diperlukan

> Empat page di atas adalah information architecture utama. Menu/action tetap mengikuti role dan permission.

# 2. INITIAL SYSTEM OBJECTIVE

Sistem ditujukan untuk membantu Technolife mengelola inventory secara terpusat, terutama:

- Stock In
- Stock Out
- Stock Opname
- Stock Movement
- Inventory per lokasi
- Batch
- FIFO
- Expired / Durability
- Purchasing
- Approval
- Reporting
- Audit Trail

Fitur final belum dianggap confirmed sampai business requirement divalidasi.

---

# 3. DESIGN PRINCIPLES

## 3.1 User Friendly

Sistem harus:

- Mudah dipahami user non-teknis.
- Meminimalkan jumlah klik.
- Menggunakan terminology yang familiar bagi user.
- Memberikan feedback yang jelas setelah setiap action.
- Menghindari form terlalu panjang jika tidak diperlukan.
- Menampilkan informasi penting terlebih dahulu.
- Memiliki empty state, loading state, success state, dan error state yang jelas.

## 3.2 Customizable

Sistem harus sebisa mungkin mendukung konfigurasi:

- Role
- Permission
- Warehouse
- Outlet
- Category
- Unit
- Supplier
- Approval
- Stock status
- Expiry warning
- Minimum/maximum stock jika dibutuhkan
- Jenis transaksi
- Status workflow

Namun configurable **tidak berarti semua hal harus bisa diedit oleh user**.

Data yang memengaruhi integritas transaksi harus memiliki kontrol khusus.

## 3.3 Inventory Integrity

Inventory harus dapat ditelusuri.

Sistem idealnya dapat menjawab:

> "Kenapa stock item ini sekarang berjumlah X?"

Melalui:

- Stock In
- Stock Out
- Transfer
- Adjustment
- Stock Opname
- Batch
- Expiry
- User
- Timestamp

## 3.4 Auditability

Transaksi inventory tidak boleh sembarangan dihapus.

Jika terjadi kesalahan:

- Correction
- Reversal
- Adjustment
- Audit Log

lebih diutamakan daripada hard delete transaksi.

---

# 4. ARCHITECTURE TARGET

## 4.1 High Level Architecture

```text
                         USERS
                           |
        +------------------+------------------+------------------+
        |                  |                  |                  |
   Super Admin       Staff Gudang        Purchasing          Finance
        |                  |                  |                  |
        +------------------+------------------+------------------+
                           |
                           v
              +------------------------+
              |   WEB APPLICATION      |
              |                        |
              | Blade + Tailwind       |
              | Filament               |
              +-----------+------------+
                          |
                          v
              +------------------------+
              |        LARAVEL         |
              |                        |
              | Authentication         |
              | Authorization          |
              | Business Logic          |
              | Inventory Services      |
              | Purchasing              |
              | Approval                |
              | Reporting               |
              | Notifications           |
              | Audit                   |
              +-----------+------------+
                          |
                          v
              +------------------------+
              |       DATABASE         |
              |      PostgreSQL /       |
              |       MySQL*            |
              +------------------------+

* Database engine final mengikuti keputusan project.
```

## 4.2 External Integration / Business Relationship

```text
       WRT / MAIN KITCHEN
       External Supplier
               |
               | Supply / Delivery
               v
       TECHNO LIFE WAREHOUSE
               |
       +-------+--------+
       |       |        |
       v       v        v
     CAFE   BILLIARD   RESTO
```

> Detail apakah WRT mengirim langsung ke warehouse, outlet, atau skenario lain masih `[FOLLOW-UP]`.

---

# 5. LARAVEL APPLICATION STRUCTURE

Target struktur konseptual:

```text
app/
├── Actions/
├── Console/
├── Enums/
├── Events/
├── Exceptions/
├── Filament/
│   ├── Resources/
│   ├── Pages/
│   └── Widgets/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Middleware/
├── Models/
├── Notifications/
├── Policies/
├── Services/
│   ├── Inventory/
│   ├── Purchasing/
│   ├── Approval/
│   └── Reporting/
└── Support/

resources/
├── views/
│   ├── layouts/
│   ├── components/
│   └── operational/
└── css/

database/
├── migrations/
├── seeders/
└── factories/
```

> Struktur final dapat berubah setelah domain analysis.

---

# 6. UI ARCHITECTURE

## Blade + Tailwind

Diprioritaskan untuk workflow operasional yang membutuhkan UX custom:

- Stock In
- Stock Out
- Stock Opname
- Receiving
- Picking
- Request
- Operational dashboard

## Filament

Diprioritaskan untuk:

- Master Data
- User
- Role
- Permission
- Supplier
- Product
- Warehouse
- Outlet
- Purchasing
- Approval
- Reporting
- Administration

> Pembagian Blade/Filament dapat berubah jika hasil UX testing menunjukkan kebutuhan berbeda.

---

# 7. DOMAIN MODULES

Target module:

```text
1. Authentication & Authorization
2. Master Data
3. Warehouse & Location
4. Inventory
5. Stock In
6. Stock Out
7. Stock Opname
8. Stock Adjustment
9. Batch & Expiry
10. FIFO
11. Purchasing
12. Approval
13. Outlet / Distribution
14. Reporting
15. Audit Log
16. Notification
17. Dashboard
```

---

# PHASE 0 — DISCOVERY & REQUIREMENT VALIDATION

**Target:** Sebelum coding

## Task 0.1 — Validate Organization

- [ ] Confirm Technolife warehouse structure
- [ ] Confirm Cafe
- [ ] Confirm Billiard
- [ ] Confirm Resto
- [x] Confirm Super Admin role
- [x] Confirm Staff Gudang role
- [x] Confirm PIC Outlet role
- [x] Confirm Purchasing role
- [x] Confirm Management application role
- [x] Confirm Finance role
- [x] Confirm role responsibilities
- [ ] Confirm WRT as external party
- [ ] Confirm WRT as supplier/partner

## Task 0.2 — Validate WRT Workflow

- [ ] Confirm WRT → Technolife warehouse flow
- [ ] Confirm whether WRT uses Purchase Order
- [ ] Confirm who creates order
- [ ] Confirm who receives WRT goods
- [ ] Confirm delivery document
- [ ] Confirm receiving process
- [ ] Confirm whether WRT goods always enter warehouse
- [ ] Confirm whether WRT can deliver directly to outlet
- [ ] Confirm whether WRT requires system access
- [ ] Confirm WRT-related return process

## Task 0.3 — Validate Warehouse Flow

- [ ] Map goods receiving
- [ ] Map storage
- [ ] Map stock request
- [ ] Map picking
- [ ] Map stock out
- [ ] Map distribution
- [ ] Map outlet receiving
- [ ] Map stock opname
- [ ] Map adjustment
- [ ] Map expired/waste process

## Task 0.4 — Validate Inventory Rules

- [ ] Confirm Stock In
- [ ] Confirm Stock Out
- [ ] Confirm Stock Opname
- [ ] Confirm Stock Adjustment
- [ ] Confirm Stock Transfer
- [ ] Confirm Stock Movement
- [ ] Confirm FIFO
- [ ] Confirm Batch
- [ ] Confirm Expiry
- [ ] Confirm Durability
- [ ] Confirm Near Expiry handling
- [ ] Confirm Expired handling

## Task 0.5 — Validate Approval

- [ ] Purchase Request approval
- [ ] Purchase Order approval
- [ ] Stock Adjustment approval
- [x] Stock Opname approval by Purchasing
- [x] Weekly Stock Opname approval/review
- [x] End-of-month Stock Opname approval/review
- [ ] Stock Out approval if required
- [ ] Define approval roles
- [ ] Define approval status
- [ ] Define approval history

## Task 0.6 — Validate Current System

- [ ] Identify current tools
- [ ] Identify Excel/Spreadsheet usage
- [ ] Identify paper documents
- [ ] Identify WhatsApp/manual process
- [ ] Identify existing software
- [ ] Collect sample documents if permitted
- [ ] Identify current pain points
- [ ] Identify duplicate data entry
- [ ] Identify common errors
- [ ] Identify reporting problems

---

# PHASE 1 — DOMAIN & BUSINESS PROCESS

## Task 1.1 — Actor Matrix

- [x] Define Super Admin responsibilities
- [x] Define Staff Gudang responsibilities
- [x] Define PIC Outlet responsibilities
- [x] Define Purchasing responsibilities
- [x] Define Management responsibilities
- [x] Define Finance responsibilities
- [ ] Define detailed permission matrix
- [ ] Define detailed Outlet access scope
- [ ] Define external WRT interaction
- [ ] Document actor responsibilities

## Task 1.2 — AS-IS Process

- [ ] Draw procurement flow
- [ ] Draw receiving flow
- [ ] Draw Stock In flow
- [ ] Draw request flow
- [ ] Draw picking flow
- [ ] Draw Stock Out flow
- [ ] Draw distribution flow
- [ ] Draw outlet receiving flow
- [ ] Draw Stock Opname flow
- [ ] Draw adjustment flow
- [ ] Draw expired/waste flow

## Task 1.3 — TO-BE Process

- [ ] Design improved procurement flow
- [ ] Design improved receiving flow
- [ ] Design Stock In
- [ ] Design request flow
- [ ] Design Stock Out
- [ ] Design Stock Opname
- [ ] Design adjustment
- [ ] Design expiry handling
- [ ] Validate TO-BE with stakeholder

## Task 1.4 — Business Rules

Create unique IDs:

```text
BR-001
BR-002
BR-003
...
```

- [ ] Stock In rules
- [ ] Stock Out rules
- [ ] Stock Opname rules
- [ ] Adjustment rules
- [ ] FIFO rules
- [ ] Batch rules
- [ ] Expiry rules
- [ ] Purchasing rules
- [ ] Approval rules
- [ ] Outlet distribution rules
- [ ] WRT interaction rules
- [ ] Correction/reversal rules

---

# PHASE 2 — PRODUCT REQUIREMENTS

## Task 2.1 — Functional Requirements

Create IDs:

```text
FR-001
FR-002
FR-003
...
```

- [ ] Authentication
- [ ] User management
- [ ] Role management
- [ ] Permission management
- [ ] Product management
- [ ] Category management
- [ ] Unit management
- [ ] Supplier management
- [ ] Warehouse management
- [ ] Outlet management
- [ ] Stock In
- [ ] Stock Out
- [ ] Stock Opname
- [ ] Stock Adjustment
- [ ] Stock Transfer
- [ ] Batch
- [ ] FIFO
- [ ] Expiry
- [ ] Purchasing
- [ ] Approval
- [ ] Reporting
- [ ] Audit Log
- [ ] Notification

## Task 2.2 — Non-Functional Requirements

- [ ] Performance
- [ ] Security
- [ ] Availability
- [ ] Usability
- [ ] Maintainability
- [ ] Scalability
- [ ] Auditability
- [ ] Data integrity
- [ ] Responsive design

## Task 2.3 — UX Requirements

- [ ] Simple navigation
- [ ] Clear terminology
- [ ] Responsive desktop UI
- [ ] Responsive tablet UI
- [ ] Mobile-friendly operational workflow
- [ ] Clear status indicators
- [ ] Confirmation before destructive action
- [ ] Validation messages
- [ ] Empty states
- [ ] Loading states
- [ ] Error states
- [ ] Success feedback
- [ ] Search
- [ ] Filter
- [ ] Sort
- [ ] Bulk action where appropriate
- [ ] Export where needed

---

# PHASE 3 — OBJECT MODEL / UML

## Task 3.1 — Use Case

- [ ] Identify actors
- [ ] Identify use cases
- [ ] Define actor-use case relationships
- [ ] Create Use Case Diagram
- [ ] Validate with stakeholder

## Task 3.2 — Flow Event

- [ ] Stock In Flow Event
- [ ] Stock Out Flow Event
- [ ] Stock Opname Flow Event
- [ ] Purchase Flow Event
- [ ] Approval Flow Event
- [ ] Receiving Flow Event
- [ ] Adjustment Flow Event
- [ ] Expiry Flow Event

## Task 3.3 — Activity Diagram

- [ ] Procurement Activity
- [ ] Stock In Activity
- [ ] Stock Out Activity
- [ ] Stock Opname Activity
- [ ] Approval Activity
- [ ] Receiving Activity
- [ ] Adjustment Activity
- [ ] Expiry Activity

## Task 3.4 — Sequence Diagram

- [ ] Login sequence
- [ ] Stock In sequence
- [ ] Stock Out sequence
- [ ] Stock Opname sequence
- [ ] Purchase sequence
- [ ] Approval sequence
- [ ] Receiving sequence
- [ ] Adjustment sequence
- [ ] FIFO allocation sequence

## Task 3.5 — Class Diagram

Candidate objects:

- [ ] User
- [ ] Role
- [ ] Permission
- [ ] Organization
- [ ] Location
- [ ] Warehouse
- [ ] Outlet
- [ ] Supplier
- [ ] Product
- [ ] Category
- [ ] Unit
- [ ] Purchase Request
- [ ] Purchase Order
- [ ] Stock
- [ ] Stock Batch
- [ ] Stock Movement
- [ ] Stock In
- [ ] Stock Out
- [ ] Stock Opname
- [ ] Stock Adjustment
- [ ] Approval
- [ ] Waste
- [ ] Audit Log

> Candidate objects are not automatically final classes.

---

# PHASE 4 — DATABASE DESIGN

## Task 4.1 — Database Strategy

- [ ] Select database engine
- [ ] Define naming convention
- [ ] Define primary key strategy
- [ ] Define foreign key strategy
- [ ] Define indexing strategy
- [ ] Define transaction strategy
- [ ] Define soft delete policy
- [ ] Define audit strategy

## Task 4.2 — Master Data Schema

- [ ] users
- [ ] roles
- [ ] permissions
- [ ] warehouses
- [ ] outlets
- [ ] categories
- [ ] units
- [ ] products
- [ ] suppliers

## Task 4.3 — Procurement Schema

- [ ] purchase_requests
- [ ] purchase_request_items
- [ ] purchase_orders
- [ ] purchase_order_items
- [ ] supplier references
- [ ] approval references

## Task 4.4 — Inventory Schema

- [ ] stocks
- [ ] stock_batches
- [ ] stock_movements
- [ ] stock_ins
- [ ] stock_in_items
- [ ] stock_outs
- [ ] stock_out_items
- [ ] stock_opnames
- [ ] stock_opname_items
- [ ] stock_adjustments
- [ ] waste/expired records if required

## Task 4.5 — Audit Schema

- [ ] audit_logs
- [ ] transaction history
- [ ] user attribution
- [ ] timestamp
- [ ] old/new value where required

---

# PHASE 5 — INVENTORY ENGINE

## Task 5.1 — Stock In Engine

- [ ] Validate item
- [ ] Validate quantity
- [ ] Validate unit
- [ ] Create stock batch if required
- [ ] Record received date
- [ ] Record expiry
- [ ] Update stock balance
- [ ] Create stock movement
- [ ] Create audit record

## Task 5.2 — Stock Out Engine

- [ ] Validate available stock
- [ ] Validate location
- [ ] Validate request
- [ ] Allocate stock
- [ ] Update batch quantity
- [ ] Update stock balance
- [ ] Create stock movement
- [ ] Create audit record

## Task 5.3 — FIFO Engine

- [ ] Define FIFO business rule
- [ ] Select oldest eligible batch
- [ ] Support partial batch allocation
- [ ] Support multiple batch allocation
- [ ] Prevent negative stock
- [ ] Handle insufficient stock
- [ ] Record allocated batch
- [ ] Record FIFO decision

## Task 5.4 — Expiry Engine

- [ ] Store expiry date
- [ ] Calculate durability if required
- [ ] Identify near-expired stock
- [ ] Identify expired stock
- [ ] Display expiry status
- [ ] Prevent invalid usage if required
- [ ] Record expired/waste movement if required

## Task 5.5 — Stock Opname Engine

- [ ] Create opname
- [ ] Snapshot system stock
- [ ] Input physical stock
- [ ] Calculate variance
- [ ] Require reason for variance
- [ ] Approval if required
- [ ] Generate adjustment
- [ ] Create movement
- [ ] Lock completed opname

---

# PHASE 6 — PURCHASING

## Task 6.1 — Purchase Request

- [ ] Create PR
- [ ] Add items
- [ ] Set quantity
- [ ] Set required date
- [ ] Submit PR
- [ ] Approval
- [ ] Reject PR
- [ ] Revise PR

## Task 6.2 — Purchase Order

- [ ] Create PO
- [ ] Select supplier
- [ ] Add items
- [ ] Set quantity
- [ ] Set price
- [ ] Set expected delivery
- [ ] Approval
- [ ] Send/record PO
- [ ] Track PO status

## Task 6.3 — Receiving

- [ ] Receive against PO
- [ ] Partial receiving
- [ ] Over/under receiving handling
- [ ] Batch input
- [ ] Expiry input
- [ ] Stock In generation
- [ ] Receiving document

---

# PHASE 7 — WAREHOUSE & OUTLET DISTRIBUTION

## Task 7.1 — Stock Request

- [ ] Outlet creates request
- [ ] Add requested items
- [ ] Quantity validation
- [ ] Submit request
- [ ] Approval if required
- [ ] Request status

## Task 7.2 — Picking

- [ ] Show approved request
- [ ] Show available stock
- [ ] Show eligible batches
- [ ] FIFO suggestion
- [ ] Manual override if allowed
- [ ] Picking confirmation

## Task 7.3 — Distribution / Stock Out

- [ ] Create Stock Out
- [ ] Record destination outlet
- [ ] Record batch
- [ ] Record quantity
- [ ] Record sender
- [ ] Generate delivery document if required
- [ ] Update warehouse stock

## Task 7.4 — Outlet Receiving

- [ ] Outlet sees pending delivery
- [ ] Confirm received quantity
- [ ] Partial receiving
- [ ] Reject/discrepancy handling
- [ ] Update outlet stock
- [ ] Complete transaction

---

# PHASE 8 — ADMIN & CONFIGURATION

## Task 8.1 — User Management

- [ ] User CRUD
- [ ] Role assignment
- [ ] Active/inactive status
- [ ] Password/security management

## Task 8.2 — Role & Permission

- [ ] Super Admin permission
- [ ] Staff Gudang permission
- [ ] Purchasing permission
- [ ] Finance permission
- [ ] Management role decision
- [ ] Outlet permission if required
- [ ] Permission matrix

## Task 8.3 — Configurable Master Data

- [ ] Categories
- [ ] Units
- [ ] Warehouses
- [ ] Outlets
- [ ] Suppliers
- [ ] Products
- [ ] Expiry configuration
- [ ] Approval configuration if required

---

# PHASE 9 — DASHBOARD & REPORTING

## Task 9.1 — Operational Dashboard

- [ ] Total stock
- [ ] Low stock
- [ ] Near expiry
- [ ] Expired
- [ ] Pending requests
- [ ] Pending receiving
- [ ] Pending approval

## Task 9.2 — Reporting / Management View

- [ ] Stock value if required
- [ ] Stock per warehouse
- [ ] Stock per outlet
- [ ] Stock movement
- [ ] Purchasing summary
- [ ] Expiry summary
- [ ] Adjustment summary

## Task 9.3 — Reports

- [ ] Stock report
- [ ] Stock In report
- [ ] Stock Out report
- [ ] Stock Opname report
- [ ] Stock Movement report
- [ ] Purchase report
- [ ] Expiry report
- [ ] Adjustment report
- [ ] Export CSV/Excel/PDF if required

---

# PHASE 10 — NOTIFICATION

## Task 10.1 — Low Stock Alert

- [ ] Define minimum stock / threshold
- [ ] Define recipient
- [ ] Define trigger condition
- [ ] Prevent duplicate alerts
- [ ] Store notification history

## Task 10.2 — WhatsApp Alert

Target architecture:

```text
Laravel Inventory
      |
      | Low Stock Event
      v
Laravel Queue / Job
      |
      v
Node.js WhatsApp Service
      |
      v
whatsapp-web.js
      |
      v
WhatsApp Account
      |
      v
Recipient
```

`whatsapp-web.js` memang memungkinkan pengiriman pesan WhatsApp dari Node.js; repository resminya mencantumkan Node.js >=18 dan `Send messages` sebagai fitur yang didukung.

Karena aplikasi utama menggunakan Laravel/PHP, **jangan menjalankan whatsapp-web.js langsung di Laravel**. Buat service/worker Node.js terpisah.

- [ ] Create `whatsapp-service`
- [ ] Install whatsapp-web.js
- [ ] Configure authentication/session
- [ ] QR login
- [ ] Connection status
- [ ] Send message
- [ ] Laravel → queue/job
- [ ] Laravel → WhatsApp service communication
- [ ] Retry handling
- [ ] Failed message logging
- [ ] Notification history
- [ ] Reconnect/watchdog
- [ ] Server restart recovery
- [ ] Test message

## Task 10.3 — WhatsApp Configuration UX

- [ ] Connection status
- [ ] QR login screen if required
- [ ] Connected account
- [ ] Recipient configuration
- [ ] Enable/disable alert
- [ ] Threshold configuration
- [ ] Message template
- [ ] Test message
- [ ] Last successful message
- [ ] Last failed message

## Task 10.4 — Provider Abstraction

```text
NotificationProvider
       |
       +-- WhatsAppWebJsProvider
       +-- OfficialWhatsAppProvider [FUTURE]
       +-- OtherProvider [FUTURE]
```

- [ ] Define provider interface
- [ ] Implement WhatsAppWebJsProvider
- [ ] Keep inventory business logic provider-agnostic
- [ ] Allow future provider replacement

## WhatsApp Risk

Repository resmi menyatakan bahwa whatsapp-web.js bukan produk resmi/afiliasi WhatsApp, menggunakan WhatsApp Web, dan tidak menjamin akun tidak akan diblokir.

- [ ] Confirm Technolife accepts this risk
- [ ] Prefer dedicated business/system number
- [ ] Do not use employee personal account
- [ ] Keep in-app alert as fallback
- [ ] Log every send attempt
- [ ] Add reconnect and retry mechanism

The repository issue tracker juga menunjukkan ongoing compatibility issues terkait readiness, authentication, execution context, dan session behavior.


---

# PHASE 11 — TESTING

## Task 11.1 — Unit Testing

- [ ] Stock In logic
- [ ] Stock Out logic
- [ ] FIFO logic
- [ ] Expiry logic
- [ ] Stock Opname calculation
- [ ] Adjustment logic

## Task 11.2 — Feature Testing

- [ ] Login
- [ ] Master data
- [ ] Purchasing
- [ ] Stock In
- [ ] Stock Out
- [ ] Stock Opname
- [ ] Receiving
- [ ] Approval
- [ ] Reporting

## Task 11.3 — Business Scenario Testing

### Scenario A — Normal Stock In

- [ ] Purchase
- [ ] Receive
- [ ] Stock In
- [ ] Stock updated

### Scenario B — FIFO

- [ ] Multiple batches
- [ ] Request quantity
- [ ] Correct oldest batch selected
- [ ] Partial batch handled
- [ ] Multiple batches handled

### Scenario C — Expiry

- [ ] Normal
- [ ] Near expiry
- [ ] Expired
- [ ] Correct status

### Scenario D — Stock Opname

- [ ] System stock
- [ ] Physical stock
- [ ] Variance
- [ ] Adjustment
- [ ] Audit

### Scenario E — Insufficient Stock

- [ ] Request exceeds stock
- [ ] System handles correctly
- [ ] User receives clear feedback

## Task 11.4 — Security Testing

- [ ] Unauthorized access
- [ ] Role restrictions
- [ ] Permission restrictions
- [ ] Transaction manipulation
- [ ] Audit integrity

## Task 11.5 — UX Testing

- [ ] Storeman usability test
- [ ] Purchasing usability test
- [ ] Management usability test
- [ ] Finance usability test
- [ ] PIC Outlet usability test
- [ ] Mobile/tablet check
- [ ] Error message clarity
- [ ] Form usability

---

# PHASE 12 — AGILE DELIVERY

## Sprint 0 — Discovery

- [ ] Requirement discovery
- [ ] AS-IS process
- [ ] Actor mapping
- [ ] Business rules
- [ ] Initial backlog
- [ ] Architecture baseline

## Sprint 1 — Foundation

- [ ] Laravel setup
- [ ] Authentication
- [ ] Authorization
- [ ] Layout
- [ ] Master data foundation
- [ ] Database foundation

## Sprint 2 — Warehouse Core

- [ ] Product
- [ ] Warehouse
- [ ] Stock In
- [ ] Stock balance
- [ ] Stock movement

## Sprint 3 — Stock Out

- [ ] Outlet request
- [ ] Picking
- [ ] FIFO
- [ ] Stock Out
- [ ] Distribution

## Sprint 4 — Stock Opname

- [ ] Opname
- [ ] Variance
- [ ] Adjustment
- [ ] Audit

## Sprint 5 — Purchasing

- [ ] Purchase Request
- [ ] Purchase Order
- [ ] Approval
- [ ] Receiving

## Sprint 6 — Expiry

- [ ] Batch
- [ ] Expiry
- [ ] Durability
- [ ] Near expiry
- [ ] Expired

## Sprint 7 — Reporting

- [ ] Dashboard
- [ ] Reports
- [ ] Export
- [ ] Stakeholder/management reporting views

## Sprint 8 — Hardening

- [ ] Testing
- [ ] UX refinement
- [ ] Security
- [ ] Performance
- [ ] Bug fixing
- [ ] Production preparation

> Sprint count is provisional. Final sprint planning follows actual stakeholder priorities and internship timeline.

---

# PHASE 13 — DEPLOYMENT

- [ ] Production environment
- [ ] Environment variables
- [ ] Database migration
- [ ] Seeder strategy
- [ ] Storage configuration
- [ ] Queue configuration if needed
- [ ] Scheduler if needed
- [ ] Backup strategy
- [ ] Logging
- [ ] Error monitoring
- [ ] Production testing
- [ ] User acceptance testing
- [ ] Deployment documentation

---

# PHASE 14 — DOCUMENTATION

## Technical Documentation

- [ ] Architecture documentation
- [ ] Database documentation
- [ ] API/service documentation if applicable
- [ ] Business logic documentation
- [ ] FIFO documentation
- [ ] Expiry documentation
- [ ] Deployment documentation
- [ ] User guide

## Project Documentation

- [ ] Requirement document
- [ ] Product backlog
- [ ] Sprint backlog
- [ ] Sprint result
- [ ] Testing result
- [ ] UAT result
- [ ] Change log

---

# 8. CURRENT REQUIREMENT STATUS

## CONFIRMED

- [x] Technolife has its own warehouse.
- [x] Warehouse distributes inventory to Cafe.
- [x] Warehouse distributes inventory to Billiard.
- [x] Warehouse distributes inventory to Resto.
- [x] Staff Gudang is involved.
- [x] Purchasing is involved.
- [x] Finance is involved.
- [x] Management is an application role focused on general dashboard/information monitoring.
- [x] WRT/Main Kitchen is external to Technolife.
- [x] WRT acts as supplier/partner.
- [x] WRT sends materials/goods to Technolife.
- [x] Stock In is required.
- [x] Stock Out is required.
- [x] Stock Opname is required.
- [x] System should be flexible/customizable.
- [x] UX should be user-friendly.
- [x] Main website has four primary pages: Dashboard, Data Stock, Riwayat, Laporan.
- [x] System should support low-stock WhatsApp alerts.
- [x] Confirmed application roles: Super Admin, Staff Gudang, PIC Outlet, Purchasing, Management, Finance.

## PARTIAL / FOLLOW-UP

- [ ] Exact WRT → warehouse workflow
- [ ] Whether WRT always ships to warehouse
- [ ] Whether WRT can ship directly to outlet
- [ ] Whether WRT needs system access
- [ ] Whether WRT uses Purchase Order
- [ ] Exact procurement workflow
- [ ] Exact approval workflow
- [ ] FIFO rules
- [ ] Batch rules
- [ ] Expiry rules
- [ ] Durability definition
- [ ] Stock Opname workflow
- [ ] Stock Adjustment workflow
- [ ] Outlet receiving workflow
- [ ] Outlet stock management
- [ ] Minimum/maximum stock rules
- [ ] Exact low-stock threshold
- [ ] WhatsApp alert recipients
- [ ] WhatsApp message template
- [ ] WhatsApp operational account/number
- [ ] Production approval for whatsapp-web.js
- [ ] Reporting requirements
- [ ] Database engine
- [ ] Hosting/deployment environment

---

# 9. AI WORKING RULE

Setiap kali AI menyelesaikan task, gunakan format:

```text
Status: Done / Partial / Blocked

Phase:
[Tulis phase]

Task:
[Tulis task]

Berhasil:
- ...

Belum:
- ...

Files:
- ...

Checklist:
- [x] ...
- [ ] ...

Blocker:
- None / ...

Assumption:
- None / ...

Next:
- ...
```

Jika ada informasi yang belum diketahui:

```text
[UNKNOWN]
```

Jika merupakan asumsi:

```text
[ASSUMPTION]
```

Jika sudah dikonfirmasi:

```text
[CONFIRMED]
```

Jika perlu ditanyakan kembali:

```text
[FOLLOW-UP]
```

---

# 10. IMPORTANT — DO NOT CODE YET

Sebelum:

- membuat migration final,
- membuat model final,
- membuat service FIFO,
- membuat Stock In,
- membuat Stock Out,
- membuat UI final,

AI wajib memastikan Phase 0 dan Phase 1 sudah cukup tervalidasi.

Urutan:

```text
DISCOVERY
    ↓
AS-IS
    ↓
PROBLEM
    ↓
BUSINESS RULE
    ↓
REQUIREMENT
    ↓
BACKLOG
    ↓
OBJECT MODEL
    ↓
DATABASE DESIGN
    ↓
UI/UX
    ↓
IMPLEMENTATION
    ↓
TESTING
    ↓
UAT
    ↓
DEPLOYMENT
```

---

# 11. PROJECT DEFINITION

Current working title:

> **Technolife Inventory Management System**

Possible academic direction:

> **Rancang Bangun Sistem Informasi Manajemen Persediaan Berbasis Web pada Technolife**

Title is **NOT FINAL**.

Potential research focus may include:

- Inventory Management
- Stock In / Stock Out
- Stock Opname
- FIFO
- Batch
- Expiry / Durability
- Warehouse and Outlet Distribution

Final thesis title must be determined after business process and system requirements are validated.

---

# 12. FINAL CHECKLIST

- [ ] Discovery completed
- [ ] Business process validated
- [ ] AS-IS completed
- [ ] Problems validated
- [ ] Business rules validated
- [ ] Requirements validated
- [ ] Product backlog approved
- [ ] Object model completed
- [ ] Database design approved
- [ ] UI/UX approved
- [ ] Development completed
- [ ] Testing completed
- [ ] UAT completed
- [ ] Deployment completed
- [ ] Documentation completed


---

# CHANGELOG — V3 ROLE UPDATE

Date: 2026-10-08

Role structure updated from the previous provisional 4-role model to 6 confirmed roles:

1. Super Admin
2. Staff Gudang
3. PIC Outlet
4. Purchasing
5. Management
6. Finance

Confirmed jobdesk:
- Staff Gudang inputs purchased goods, receives physical goods, records incoming stock, and hands outgoing goods to PIC Outlet.
- PIC Outlet checks outlet stock and receives goods from Staff Gudang.
- Management uses the general stock dashboard and warehouse stock information.
- Purchasing views stock, controls/monitors Staff Gudang, and approves Stock Opname weekly and at month-end.
- Finance views stock across all warehouses and uses stock information for inventory valuation.
- Super Admin retains full system control.

Still follow-up:
- Detailed permission matrix
- Exact Stock Opname execution flow
- Outlet receiving discrepancy rules
- Detailed procurement workflow
- Exact inventory valuation method
