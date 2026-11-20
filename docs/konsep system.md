Laporan Audit Sistem Inventory Technolife
=========================================

Audit ini membaca roadmap dan snapshot codebase yang tersedia, tanpa mengubah file, menjalankan migration, atau menjalankan test. Dashboard saya perlakukan sebagai **LOCKED** sesuai instruksi. Workspace memiliki banyak perubahan lokal yang belum di-commit; temuan di bawah menggambarkan file yang ada saat audit, bukan jaminan bahwa semuanya sudah aktif di database atau production.

1\. Ringkasan eksekutif
-----------------------

Codebase sudah memiliki alur inventory yang nyata: saldo dihitung dari ledger pergerakan stok, penerimaan dan pengeluaran membuat transaksi stok, Stock Opname memiliki approval, distribusi gudang ke outlet mempunyai tahap permintaan dan penerimaan, dan pembelian memiliki PR, PO, penerimaan parsial, serta review faktur.

Beberapa batas penting:

*   **Penerimaan supplier ke gudang dan distribusi gudang ke outlet adalah dua alur berbeda.** Penerimaan PO menambah stok gudang saat Staff Gudang mencatat jumlah yang diterima. Distribusi menurunkan stok gudang saat dikirim dan menambah stok outlet saat PIC mencatat penerimaan.
    
*   **Stock In langsung ke outlet ditahan sampai PIC outlet menyetujui.** Pergerakan stok baru dibuat saat approval.
    
*   **Faktur memiliki review Finance, tetapi tidak memiliki status pembayaran.** Halaman “valuasi” saat ini menunjukkan kuantitas dari ledger, bukan nilai uang.
    
*   **FEFO memang diterapkan pada Stock Out untuk batch yang dilacak.** FEFO mengeluarkan stok dengan tanggal kedaluwarsa terdekat lebih dahulu. Itu sendiri bukan bukti bahwa metode valuasi akuntansi adalah FIFO atau FEFO.
    
*   **Permission sudah mulai dibedakan, tetapi batas sumber/lokasi transaksi belum selalu ketat.** Contohnya, Staff Gudang yang memiliki stock.manage dapat mengirim Stock Out dari lokasi yang dipilih; validasi layanan Stock Out tidak memastikan lokasi asal harus gudang.
    
*   Fitur bukan hanya UI. Namun status “Done” pada roadmap tetap perlu dibaca sebagai **MVP**, sebab UAT, test keseluruhan, dan beberapa aturan bisnis masih terbuka.
    

2\. Kondisi aktual codebase
---------------------------

**Implemented** berarti ada dukungan route/backend/database yang tampak pada code. Itu tidak berarti sudah diuji dalam audit ini atau siap production.

AreaStatusTemuanLogin dan akses aplikasi**IMPLEMENTED**Route aplikasi dilindungi auth dan permission:app.access; ada throttle login. Role/permission memakai model dan tabel sendiri.Enam role**IMPLEMENTED / PARTIAL**Seeder mendefinisikan enam role dan permission dasar. Hak tiap role belum menggambarkan seluruh perbedaan jobdesk; role juga dapat diubah Super Admin.Master data**IMPLEMENTED**CRUD produk, kategori, satuan, lokasi, supplier tersedia di controller admin. Route CRUD berada di grup users.manage.Dashboard**PARTIAL**Dashboard dan live stock tersedia. Roadmap juga mencatat tindak lanjut KPI/aktivitas. Dashboard tidak saya ubah.Data Stock dan saldo**IMPLEMENTED / PARTIAL**Daftar dan detail produk menghitung saldo dari stock\_movements; detail menampilkan pergerakan dan batch. Cakupan lokasi berbeda untuk PIC dan user lainnya.Stock In**IMPLEMENTED**Penerimaan umum membuat Stock In dan movement dalam transaksi database. Penerimaan ke outlet berstatus menunggu approval dan belum menambah saldo.Stock Out dan FEFO**IMPLEMENTED**Validasi saldo, alokasi batch, movement, dan audit dibuat dalam satu transaksi. Batch kedaluwarsa tidak dialokasikan.Stock Opname**IMPLEMENTED / PARTIAL**Snapshot saldo, hitung fisik, alasan selisih, status approval, dan koreksi movement tersedia. Jadwal berkala otomatis dan aturan batch opname belum terbukti.Stock Adjustment**IMPLEMENTED**Koreksi langsung menulis movement dan audit, memeriksa saldo agar tidak negatif. Hanya Super Admin diberi adjust.manage oleh seeder baseline.Distribusi gudang ke outlet**IMPLEMENTED / PARTIAL**Request outlet, picking, dispatch, dan receiving tersedia. Roadmap menyebut penerimaan distribusi saat ini full receipt; UAT masih menunggu.Purchasing PR/PO**IMPLEMENTED / PARTIAL**PR menunggu persetujuan Management; hanya PR approved yang dapat dibuat menjadi PO; satu PR dapat memiliki PO; PO menuju warehouse. Penerimaan parsial mencatat kuantitas datang, diterima, dan catatan selisih.Faktur dan Finance**IMPLEMENTED / PARTIAL**Faktur, item, dokumen, draft/submission/review, perbandingan kuantitas tersedia. Pembayaran dan nilai persediaan uang belum dimodelkan.Riwayat dan laporan**IMPLEMENTED / PARTIAL**Riwayat mencakup Stock In, Stock Out, Opname, Adjustment, dan audit. Laporan memiliki filter/export; beberapa jenis alur transfer belum tampak sebagai entri terpisah di riwayat utama.API**NOT FOUND**Tidak ditemukan routes/api.php dalam daftar route yang diperiksa. Integrasi WhatsApp mempunyai service terpisah, bukan API inventory publik yang ditemukan di audit ini.Test**IMPLEMENTED sebagai artefak; belum diverifikasi**Ada test untuk login, dashboard, master data, history, PIC approval, invoice, dan lainnya. Saya tidak menjalankan test. Roadmap mencatat UAT dan testing keseluruhan belum selesai.

Route utama berada di \[routes/web.php (line 47)\](C:/laragon/www/manajemen\_inventory/routes/web.php:47). Hak dasar role berada di \[RolePermissionSeeder.php (line 11)\](C:/laragon/www/manajemen\_inventory/database/seeders/RolePermissionSeeder.php:11). Alur transaksi terdapat di service seperti \[StockInService.php (line 17)\](C:/laragon/www/manajemen\_inventory/app/Services/StockInService.php:17), \[StockOutService.php (line 16)\](C:/laragon/www/manajemen\_inventory/app/Services/StockOutService.php:16), dan \[StockOpnameService.php (line 18)\](C:/laragon/www/manajemen\_inventory/app/Services/StockOpnameService.php:18).

**Fakta dari roadmap:** enam role dan jobdesk awal telah dikonfirmasi; detail permission, aturan procurement, penerimaan outlet, adjustment, dan metode valuasi masih memerlukan konfirmasi. Roadmap juga mencatat migration Stock In PIC dan invoice sudah diterapkan ke database lokal, tetapi status tersebut berasal dari dokumentasi proyek, bukan pemeriksaan database dalam audit read-only ini.

3\. Enam role dan tanggung jawab aktual
---------------------------------------

RoleYang tampak dapat dilakukan menurut baseline permissionBatas atau catatan**Super Admin**Akses aplikasi, user/role, master data, Stock In/Out, Opname, adjustment, purchasing, invoice, laporan, konfigurasi WhatsApp.Permission administratif tidak otomatis berarti semua alur bisnis sudah dibatasi per lokasi. Dapat mengubah permission role.**Staff Gudang**Stock In/Out, mulai dan menghitung Opname, mengelola transfer, menerima barang PO ke gudang.Tidak memiliki approval Opname maupun Stock In dalam baseline. Validasi jenis lokasi sumber Stock Out belum ketat.**PIC Outlet**Melihat stok dengan konteks outlet, meminta transfer, menerima transfer, menyetujui/menolak Stock In outlet yang ditugaskan.users.location\_id hanya menunjuk satu outlet per user. Cakupan satu PIC untuk beberapa outlet belum didukung skema saat ini.**Management**Dashboard dan laporan; dapat melihat stok melalui halaman inventory umum.Tidak diberi hak transaksi operasional di seeder baseline. Tidak ada permission khusus “monitoring” yang lebih sempit daripada akses aplikasi umum.**Purchasing**PR/PO, supplier dalam proses pembelian, meninjau Opname, melihat faktur.PR menunggu keputusan Management; Purchasing membuat PO setelah PR disetujui. Hak purchasing dan Opname mencakup data semua lokasi.**Finance**Melihat dan mereview faktur, melihat halaman snapshot kuantitas inventory, laporan.Halaman valuasi belum menghitung nilai uang. Tidak ada workflow pembayaran atau akun hutang yang ditemukan.

Akses aplikasi dasar app.access ditambahkan oleh seeder hanya ketika role baru dibuat (wasRecentlyCreated). Roadmap melaporkan database lokal sudah diverifikasi untuk enam role, tetapi pola seeder ini berarti menjalankan ulang seeder tidak otomatis memperbaiki role lama yang kehilangan app.access.

4\. Role-permission matrix
--------------------------

Matriks berikut menunjukkan **baseline seeder dan route/backend**, bukan rekomendasi atau aturan bisnis resmi. View menyatakan halaman/data dapat diakses; Manage menggabungkan tindakan yang dikelompokkan oleh satu permission. Unknown berarti code tidak menyediakan permission terpisah atau kontrol granular yang cukup untuk menyimpulkan hak CRUD spesifik.

FiturSuper AdminStaff GudangPIC OutletManagementPurchasingFinanceDashboardViewViewViewViewViewViewMaster barangManageNo AccessNo AccessNo AccessNo AccessNo AccessGudang dan outletManageNo AccessNo AccessNo AccessNo AccessNo AccessSupplierManageNo AccessNo AccessNo AccessView\*View\*Stock InCreate, ViewCreate, ViewView, Approve/Reject outletNo AccessNo AccessNo AccessStock OutCreate, ViewCreate, ViewNo AccessNo AccessNo AccessNo AccessStock OpnameCreate, Edit, Approve/RejectCreate, EditNo AccessNo AccessView, Approve/RejectNo AccessStock AdjustmentManageNo AccessNo AccessNo AccessNo AccessNo AccessFEFO/batch/expiryOtomatis pada transaksi; ViewOtomatis pada transaksi; ViewView stok/batch outletView stok umumView stok umumView stok umumApprovalOpname dan Stock InNo AccessStock In outletNo AccessOpnameReview fakturFaktur pembelianManage, ReviewNo Access\*\*No AccessNo AccessCreate, Edit, Submit, ViewView, ReviewValuasi persediaanView kuantitasNo AccessNo AccessNo AccessNo AccessView kuantitasRiwayatViewViewView sesuai outletViewViewViewLaporanViewViewView sesuai outletViewViewViewUser dan roleManageNo AccessNo AccessNo AccessNo AccessNo AccessKonfigurasi sistemManage\*No AccessNo AccessNo AccessNo AccessNo Access

\* Purchasing dan Finance dapat melihat supplier/invoice yang dipakai dalam fitur mereka, tetapi CRUD master supplier hanya ditemukan pada area admin. Konfigurasi yang ada belum mencakup seluruh aturan bisnis.\*\* Controller dokumen faktur mengizinkan Staff Gudang mengunduh dokumen invoice yang terhubung ke PO warehouse; Staff tidak mendapat akses umum ke daftar faktur.

5\. Alur bisnis end-to-end
--------------------------

### A. Penerimaan barang dari supplier

1.  Purchasing membuat PR. Status awalnya menunggu persetujuan Management; PR yang ditolak tidak dapat dibuat menjadi PO.
    
2.  Purchasing menerbitkan PO ke supplier dan memilih lokasi bertipe warehouse.
    
3.  Faktur dapat dibuat dan ditautkan ke PO, lalu dikirim ke Finance. Sistem menyimpan nomor/tanggal faktur, total dokumen, item, kuantitas, harga, dan file dokumen.
    
4.  Saat barang datang, Staff Gudang mengisi jumlah yang datang dan jumlah yang layak diterima. Selisih/kerusakan perlu catatan.
    
5.  Layanan membuat Stock In terkait PO dan invoice. Jumlah diterima menjadi movement stok gudang; barang yang datang tetapi tidak diterima dicatat sebagai selisih, bukan saldo.
    
6.  PO tetap issued jika masih ada sisa; berubah received saat semua kuantitas PO dianggap terpenuhi.
    

**Status invoice dan penerimaan adalah terpisah.** Kode penerimaan memeriksa invoice terkait PO dan supplier, tetapi tidak tampak mensyaratkan status invoice sudah accepted Finance sebelum barang diterima. Kebijakan ini perlu dikonfirmasi. Tidak ada status pembayaran yang ditemukan.

Dasar: \[PurchasingService.php (line 57)\](C:/laragon/www/manajemen\_inventory/app/Services/PurchasingService.php:57), \[PurchaseInvoiceService.php (line 84)\](C:/laragon/www/manajemen\_inventory/app/Services/PurchaseInvoiceService.php:84).

### B. Stock In dan penerimaan outlet

*   **Penerimaan supplier di gudang:** Stock In berstatus approved; sistem langsung membuat movement in dan saldo gudang bertambah.
    
*   **Stock In langsung ke outlet:** status awal pending\_approval; item disimpan, tetapi movement belum dibuat. PIC yang location\_id-nya cocok dapat menyetujui atau menolak. Saat disetujui, movement dibuat sekali dan saldo outlet bertambah. Jika ditolak, saldo tidak berubah.
    
*   **Distribusi dari gudang ke outlet:** alur berbeda. PIC meminta barang; Staff memulai picking dan mengirim transfer. Stock Out menurunkan stok gudang saat dispatch. Stok outlet baru bertambah ketika PIC mencatat jumlah penerimaan.
    

Karena penerimaan gudang PO sudah menambah saldo, barang yang kemudian dikirim ke outlet tidak boleh dicatat lagi sebagai supplier Stock In. Penerimaan transfer merupakan pergerakan stok yang terhubung ke transfer.

Dasar: \[StockInApprovalService.php (line 17)\](C:/laragon/www/manajemen\_inventory/app/Services/StockInApprovalService.php:17), \[StockTransferService.php (line 41)\](C:/laragon/www/manajemen\_inventory/app/Services/StockTransferService.php:41).

### C. Stock Out

Staff Gudang membuat Stock Out dengan lokasi, tujuan, tanggal, produk, dan jumlah. Service mengunci baris lokasi, menghitung saldo dari movement, menolak jumlah melebihi stok yang tersedia, memilih batch menurut FEFO, lalu membuat Stock Out, item, movement out, dan audit.

**Batas penting:** service tidak memvalidasi bahwa location\_id yang dipilih harus bertipe warehouse. Karena route mengizinkan Staff dengan stock.manage, ada kemungkinan Stock Out langsung mengurangi stok dari lokasi outlet. Ini berpotensi melewati alur transfer dan penerimaan PIC, tetapi apakah tindakan tersebut merupakan bug bisnis perlu dikonfirmasi.

### D. Stock Opname

1.  Staff membuat sesi dengan periode weekly atau month\_end.
    
2.  Sistem menyimpan snapshot saldo awal dan daftar produk aktif.
    
3.  Staff mengisi jumlah fisik untuk seluruh item. Jika ada selisih, alasan wajib diisi.
    
4.  Setelah disubmit, status menjadi pending\_approval; saldo belum dikoreksi.
    
5.  Purchasing (atau Super Admin) menyetujui/menolak. Penghitung tidak boleh menyetujui sesi sendiri.
    
6.  Saat disetujui, service menyesuaikan hitungan dengan movement yang terjadi setelah snapshot, lalu menulis movement koreksi dan audit.
    

Jadwal mingguan/akhir bulan tampil sebagai jenis periode yang dapat dipilih; otomatisasi kalender pengingat atau pembuatan sesi tidak ditemukan.

### E. Purchasing, faktur, dan Finance

*   Faktur punya supplier, nomor unik per supplier, tanggal, nilai total, file, dan detail produk/kuantitas/harga satuan.
    
*   Jika terhubung ke PO, supplier harus sama dan produk faktur harus terdapat pada PO.
    
*   Status faktur: draft → submitted → keputusan Finance accepted, rejected, atau revision\_required.
    
*   Penerimaan barang punya status sendiri pada stock\_ins dan PO. Faktur yang sudah accepted tidak otomatis menambah stok; penerimaan barang yang membuat movement.
    
*   Pembayaran tidak tercatat sebagai status tersendiri.
    

**Risiko rekonsiliasi:** controller menerima document\_total dan harga per item, tetapi service yang diperiksa tidak menghitung ulang atau menolak jika total dokumen berbeda dari jumlah kuantitas × harga. Ini belum membuktikan bug karena pajak/biaya lain mungkin berlaku, tetapi aturan rekonsiliasinya perlu dipastikan.

### F. Valuasi persediaan

Saldo kuantitas berasal dari penjumlahan movement masuk dikurangi movement keluar untuk produk dan lokasi. Harga satuan tersimpan pada item faktur/PO, tetapi service valuasi secara eksplisit hanya menyajikan kuantitas dan tidak menghitung nilai uang.

Karena itu:

*   halaman Finance saat ini adalah snapshot **kuantitas**, bukan valuasi moneter;
    
*   sumber harga resmi, biaya tambahan, pajak, dan metode akuntansi belum diputuskan;
    
*   FEFO menentukan batch fisik yang dikeluarkan, tetapi tidak menetapkan metode valuasi keuangan.
    

### G. Riwayat dan laporan

Riwayat utama menggabungkan Stock In, Stock Out, Opname, Adjustment, dan audit. Detail transaksi membaca tabel transaksi serta itemnya. Laporan dihitung dari tabel transaksi/movement, bisa difilter, dan mendukung export CSV serta respons ekspor XLSX/PDF melalui UI.

PIC dibatasi ke lokasi yang ditugaskan pada laporan dan beberapa tampilan transaksi. Non-PIC dapat menggunakan konteks lokasi terpilih, atau melihat semua lokasi jika tidak ada filter lokasi. Risiko ruang lingkup akses paling perlu diperhatikan pada operasi tulis Stock Out, bukan sekadar tampilan saldo.

6\. Diagram Mermaid
-------------------

### Gambaran besar

Plain textANTLR4BashCC#CSSCoffeeScriptCMakeDartDjangoDockerEJSErlangGitGoGraphQLGroovyHTMLJavaJavaScriptJSONJSXKotlinLaTeXLessLuaMakefileMarkdownMATLABMarkupObjective-CPerlPHPPowerShell.propertiesProtocol BuffersPythonRRubySass (Sass)Sass (Scss)SchemeSQLShellSwiftSVGTSXTypeScriptWebAssemblyYAMLXML`   ```mermaid  flowchart LR    Master["Master data   produk, lokasi, supplier, user"] --> Tx["Transaksi inventory"]    Tx --> Ledger["stock_movements   ledger sumber saldo"]    Ledger --> Stock["Data Stock dan batch"]    Ledger --> History["Riwayat dan laporan"]    Purchase["PR → PO"] --> Receive["Penerimaan supplier di gudang"]    Invoice["Faktur → review Finance"] -. relasi dokumen/kuantitas .-> Receive    Receive --> Ledger    Transfer["Transfer gudang → outlet"] --> Ledger    Opname["Opname → approval Purchasing"] --> Ledger  ```   `

### Penerimaan supplier dan distribusi

Plain textANTLR4BashCC#CSSCoffeeScriptCMakeDartDjangoDockerEJSErlangGitGoGraphQLGroovyHTMLJavaJavaScriptJSONJSXKotlinLaTeXLessLuaMakefileMarkdownMATLABMarkupObjective-CPerlPHPPowerShell.propertiesProtocol BuffersPythonRRubySass (Sass)Sass (Scss)SchemeSQLShellSwiftSVGTSXTypeScriptWebAssemblyYAMLXML`   ```mermaid  flowchart TD    P["Purchasing buat PR   menunggu persetujuan"] --> Review{"Management setujui?"}    Review -->|Setujui| PO["Purchasing terbitkan PO ke warehouse"]    Review -->|Tolak| Rejected["PR ditolak"]    PO --> Arrival["Supplier mengirim barang"]    Arrival --> Check["Staff cek jumlah datang dan layak"]    Check --> GRN["Stock In penerimaan PO"]    GRN --> Ledger["Movement masuk   saldo gudang bertambah"]    Invoice["Faktur ditautkan ke PO   Finance review terpisah"] -.-> GRN    PIC["PIC membuat permintaan outlet"] --> Pick["Staff picking"]    Pick --> Dispatch["Dispatch transfer   Stock Out gudang"]    Dispatch --> OutletCheck["PIC memeriksa barang"]    OutletCheck --> Receive["PIC mencatat penerimaan"]    Receive --> OutletStock["Movement masuk   saldo outlet bertambah"]  ```   `

### Stock In langsung ke outlet

Plain textANTLR4BashCC#CSSCoffeeScriptCMakeDartDjangoDockerEJSErlangGitGoGraphQLGroovyHTMLJavaJavaScriptJSONJSXKotlinLaTeXLessLuaMakefileMarkdownMATLABMarkupObjective-CPerlPHPPowerShell.propertiesProtocol BuffersPythonRRubySass (Sass)Sass (Scss)SchemeSQLShellSwiftSVGTSXTypeScriptWebAssemblyYAMLXML`   ```mermaid  flowchart TD    Staff["Staff membuat Stock In outlet"] --> Pending["pending_approval   belum ada movement"]    Pending --> PIC{"PIC outlet yang ditugaskan"}    PIC -->|Setujui| Approved["approved"]    Approved --> Movement["Movement masuk dibuat"]    Movement --> Balance["Saldo outlet bertambah"]    PIC -->|Tolak| Rejected["rejected   saldo tetap"]  ```   `

### Faktur dan Finance

Plain textANTLR4BashCC#CSSCoffeeScriptCMakeDartDjangoDockerEJSErlangGitGoGraphQLGroovyHTMLJavaJavaScriptJSONJSXKotlinLaTeXLessLuaMakefileMarkdownMATLABMarkupObjective-CPerlPHPPowerShell.propertiesProtocol BuffersPythonRRubySass (Sass)Sass (Scss)SchemeSQLShellSwiftSVGTSXTypeScriptWebAssemblyYAMLXML`   ```mermaid  flowchart LR    Purchasing["Purchasing buat faktur   draft"] --> Submit["submitted"]    Submit --> Finance{"Finance review"}    Finance -->|accept| Accepted["accepted"]    Finance -->|reject| Rejected["rejected"]    Finance -->|revisi| Revision["revision_required"]    PO["PO"] --- Invoice["Faktur dan item"]    Invoice --- Receipt["Stock In dan kuantitas diterima"]    Ledger["Ledger memberi kuantitas"] --> Valuation["Halaman saat ini: kuantitas   nilai uang belum dihitung"]  ```   `

### Opname dan koreksi saldo

Plain textANTLR4BashCC#CSSCoffeeScriptCMakeDartDjangoDockerEJSErlangGitGoGraphQLGroovyHTMLJavaJavaScriptJSONJSXKotlinLaTeXLessLuaMakefileMarkdownMATLABMarkupObjective-CPerlPHPPowerShell.propertiesProtocol BuffersPythonRRubySass (Sass)Sass (Scss)SchemeSQLShellSwiftSVGTSXTypeScriptWebAssemblyYAMLXML`   ```mermaid  flowchart TD    Start["Staff mulai opname   snapshot saldo"] --> Count["Staff isi hitung fisik"]    Count --> Submit["pending_approval   saldo belum berubah"]    Submit --> Review{"Purchasing review"}    Review -->|Reject| Rejected["rejected   saldo tetap"]    Review -->|Approve| Adjust["Hitung delta terhadap   movement terbaru"]    Adjust --> Movement["Movement koreksi + audit"]    Movement --> Balance["Saldo ledger diperbarui"]  ```   `

7\. ERD dan database
--------------------

ERD berikut merangkum entitas yang ditemukan dalam model/migration terkait; tidak semua kolom ditampilkan.

Plain textANTLR4BashCC#CSSCoffeeScriptCMakeDartDjangoDockerEJSErlangGitGoGraphQLGroovyHTMLJavaJavaScriptJSONJSXKotlinLaTeXLessLuaMakefileMarkdownMATLABMarkupObjective-CPerlPHPPowerShell.propertiesProtocol BuffersPythonRRubySass (Sass)Sass (Scss)SchemeSQLShellSwiftSVGTSXTypeScriptWebAssemblyYAMLXML`   ```mermaid  erDiagram    USERS }o--o{ ROLES : memiliki    ROLES }o--o{ PERMISSIONS : mengizinkan    LOCATIONS ||--o{ USERS : ditugaskan    CATEGORIES ||--o{ PRODUCTS : mengelompokkan    UNITS ||--o{ PRODUCTS : mengukur    PRODUCTS ||--o{ STOCK_IN_ITEMS : diterima    STOCK_INS ||--|{ STOCK_IN_ITEMS : berisi    PRODUCTS ||--o{ STOCK_OUT_ITEMS : dikeluarkan    STOCK_OUTS ||--|{ STOCK_OUT_ITEMS : berisi    LOCATIONS ||--o{ STOCK_MOVEMENTS : lokasi    PRODUCTS ||--o{ STOCK_MOVEMENTS : saldo_dihitung_dari    STOCK_IN_ITEMS o|--o{ STOCK_MOVEMENTS : batch_dan_receipt    STOCK_OUT_ITEMS o|--o{ STOCK_MOVEMENTS : issue    STOCK_OPNAMES ||--|{ STOCK_OPNAME_ITEMS : berisi    STOCK_ADJUSTMENTS ||--o{ STOCK_MOVEMENTS : koreksi    SUPPLIERS ||--o{ PURCHASE_ORDERS : memasok    PURCHASE_REQUESTS ||--o| PURCHASE_ORDERS : diproses    PURCHASE_ORDERS ||--|{ PURCHASE_ORDER_ITEMS : berisi    PURCHASE_ORDERS o|--o{ STOCK_INS : diterima_melalui    PURCHASE_INVOICES }o--|| SUPPLIERS : diterbitkan_oleh    PURCHASE_INVOICES o|--o{ PURCHASE_ORDERS : terkait_PO    PURCHASE_INVOICES ||--|{ PURCHASE_INVOICE_ITEMS : berisi    PURCHASE_INVOICES o|--o{ STOCK_INS : referensi_penerimaan    STOCK_TRANSFERS ||--|{ STOCK_TRANSFER_ITEMS : berisi    STOCK_TRANSFER_REQUESTS ||--o| STOCK_TRANSFERS : menjadi    ADMINISTRATION_AUDIT_LOGS }o--|| USERS : aktor  ```   `

Fungsi tabel utama:

*   **users, roles, permissions**: akun, lokasi yang ditugaskan, role, dan izin.
    
*   **products, categories, units, locations, suppliers**: master barang/lokasi/supplier.
    
*   **stock\_ins, stock\_in\_items**: header penerimaan dan rincian produk, jumlah, batch, tanggal kedaluwarsa, serta relasi PO.
    
*   **stock\_outs, stock\_out\_items**: pengeluaran dan alokasi batch.
    
*   **stock\_movements**: ledger kuantitas per produk/lokasi; saldo dihitung dari tabel ini.
    
*   **stock\_opnames, stock\_opname\_items, stock\_adjustments**: snapshot hitung, hasil fisik, keputusan, dan koreksi.
    
*   **purchase\_requests, purchase\_request\_items, purchase\_orders, purchase\_order\_items**: permintaan, pesanan supplier, kuantitas PO, dan penerimaan.
    
*   **purchase\_invoices, purchase\_invoice\_items**: dokumen faktur, total dokumen, harga, dan item.
    
*   **stock\_transfers** beserta request, item, dan receipt: perpindahan lokasi serta penerimaan outlet.
    
*   **administration\_audit\_logs**: aktor, aksi, subjek, dan perubahan terstruktur.
    

Relasi utama dan saldo dapat ditelusuri dari Stock In/Out/Opname/Adjustment menuju movement, lalu ke produk dan lokasi. Movement juga menunjuk item batch untuk pelacakan umur/expiry. Tabel migration terkait berada di 2026\_10\_08\_\* dan 2026\_10\_14\_\*; invoice didefinisikan di \[migration faktur (line 11)\](C:/laragon/www/manajemen\_inventory/database/migrations/2026\_10\_14\_000004\_create\_purchase\_invoices\_tables.php:11), sedangkan ledger di \[migration stock movements (line 10)\](C:/laragon/www/manajemen\_inventory/database/migrations/2026\_10\_08\_000002\_create\_stock\_movements\_table.php:10).

**Belum ditemukan sebagai entitas:** pembayaran faktur, rekonsiliasi hutang, metode valuasi moneter, atau proses disposal barang kedaluwarsa.

8\. Pemetaan fitur ke halaman
-----------------------------

Roadmap menetapkan empat halaman utama: **Dashboard, Data Stock, Riwayat, Laporan**. Implementasi juga memiliki halaman operasional terpisah untuk form/detail dan modul admin.

Halaman utamaFungsi yang ditemukanAksi yang cocok menjadi detail/modal/tabDashboardRingkasan kondisi dan live stockApproval ringkas, notifikasi, aktivitas terbaruData StockDaftar saldo dan detail produk/batchStock In/Out, detail batch, pemilihan lokasiRiwayatDaftar transaksi dan detailFilter tanggal/jenis/lokasi, audit trailLaporanStok, Stock In/Out, Opname, Adjustment, movement, expiry, purchasingExport format dan filterAdmin/configUser, role/permission, master data, settingsEdit role, master barang/supplier/lokasi

Dashboard tetap tidak disentuh. Audit ini tidak menyarankan halaman utama baru untuk setiap action; kebutuhan yang ditemukan sebagian besar dapat berada di form/detail transaksi atau panel approval.

9\. Gap analysis dan risiko
---------------------------

KebutuhanKondisi dan jenis temuanRisikoPrioritasDependensi / tindak lanjutStock Out hanya mengambil dari warehouse untuk distribusi**Risiko terindikasi:** service menghitung stok lokasi yang dikirim tanpa memvalidasi tipe warehouse.Staff dapat membuat Stock Out langsung dari saldo outlet dan melewati proses transfer/PIC.**P1**Konfirmasi apakah Stock Out outlet untuk kebutuhan internal memang diperbolehkan; tetapkan aturan sumber dan jenis transaksi.Pembatasan lokasi menurut role**Risiko terindikasi:** PIC dibatasi ke outlet assignment, tetapi Staff dan user non-PIC dapat memilih konteks lokasi umum.Data atau transaksi lintas lokasi dapat terlihat/dibuat di luar tanggung jawab operasional.**P1**Putuskan batas lokasi per role dan apakah satu PIC boleh ditugaskan ke banyak outlet.Approval Opname menjaga pemisahan tugas**IMPLEMENTED:** penghitung dilarang menyetujui hasilnya sendiri; koreksi baru terjadi setelah approve.Jadwal opname otomatis, periode dan pemeriksaan UAT masih terbuka.**P1**Validasi jadwal, pelaksana, dan apakah semua item harus dihitung setiap sesi.Faktur sesuai rincian harga dan total dokumen**Risiko terindikasi:** total dokumen tersimpan terpisah dari kuantitas × harga; tidak ditemukan rekonsiliasi formula.Nilai faktur yang tidak cocok dapat diterima tanpa catatan alasan pajak/biaya.**P1**Finance konfirmasi komponen total, pajak, diskon, biaya, dan toleransi selisih.Metode valuasi uang**Belum diimplementasikan:** halaman Finance menampilkan kuantitas ledger saja.Pengguna dapat salah mengira quantity snapshot sebagai nilai persediaan.**P1**Konfirmasi metode akuntansi serta sumber harga yang disetujui.Pembayaran supplier**NOT FOUND:** status pembayaran/tanggal pembayaran tidak ditemukan pada alur faktur.Status invoice diterima Finance dapat tertukar dengan status sudah dibayar.**P1**Konfirmasi apakah pembayaran termasuk scope sistem inventory atau sistem Finance terpisah.Seeder menjamin akses dasar keenam role**Risiko pada code:** app.access hanya ditempel pada role yang baru dibuat.Role lama yang kehilangan akses tidak diperbaiki otomatis saat seeder dijalankan ulang.**P2**Verifikasi role permission pada setiap environment; tentukan perilaku seeder idempotent.Riwayat lengkap transfer**PARTIAL:** transfer punya tabel dan halaman sendiri; riwayat transaksi utama tidak menunjukkan transfer sebagai tipe terpisah.Pengguna dapat mencari di halaman history umum dan tidak menemukan seluruh siklus distribusi.**P2**Tentukan apakah transfer harus tampil pada Riwayat utama dan laporan movement.Approval matriks lintas proses**PARTIAL:** ada approval untuk Opname, Stock In outlet, dan review invoice; PR kini menunggu persetujuan Management sebelum PO. Hak review PR dipisahkan melalui permission purchase-request.approve.**P1**Stakeholder konfirmasi approval matrix dan status mana yang merupakan keputusan bisnis.Penghapusan transaksiTransaksi memakai FK restriktif dan movement/audit; Adjustment diarahkan dikoreksi dengan transaksi baru.Konsistensi reversal untuk transaksi salah tetap perlu aturan formal.**P2**Tetapkan koreksi/reversal untuk tiap transaksi dan aturan siapa yang menyetujui.

Prioritas pada tabel adalah penilaian risiko audit, bukan prioritas resmi Technolife.

10\. Urutan pengembangan yang disarankan
----------------------------------------

Urutan mengikuti dependensi data dan aturan bisnis:

1.  **Validasi aturan inti:** batas lokasi, alur supplier-WRT, Stock Out, approval, penerimaan parsial, pajak/harga, pembayaran, dan valuasi. Aturan ini menentukan keamanan saldo dan desain transaksi.
    
2.  **Finalisasi matriks permission dan cakupan lokasi.** Ini mendahului penyempurnaan UI karena route dan query harus sesuai wewenang.
    
3.  **Kunci ledger dan lifecycle transaksi:** receipt, dispatch, receiving, rejection, adjustment, koreksi/reversal, audit. Pastikan setiap transaksi menghasilkan movement tepat satu kali.
    
4.  **Finalisasi purchasing, invoice, dan rekonsiliasi penerimaan.** Pisahkan status PO, penerimaan fisik, review faktur, dan pembayaran.
    
5.  **Tentukan metode valuasi moneter dan sumber harga.** Baru setelah keputusan itu, implementasikan valuasi; FEFO fisik tidak cukup menjadi dasar metode akuntansi.
    
6.  **Selesaikan laporan dan riwayat berdasarkan data final**, termasuk transfer serta jejak perubahan.
    
7.  **Verifikasi test, UAT per role, keamanan akses, dan deployment.** Roadmap menunjukkan item ini masih terbuka; saya tidak menjalankannya dalam audit ini.
    

11\. Pertanyaan untuk dikonfirmasi kepada Technolife
----------------------------------------------------

1.  Apakah barang dari WRT selalu diterima di gudang, atau ada pengiriman langsung ke outlet?
    
2.  Bolehkah Staff mencatat Stock Out dari outlet, atau pengeluaran outlet hanya terjadi melalui Stock Transfer?
    
3.  Apakah PIC hanya bertanggung jawab pada satu outlet? Siapa pengganti jika PIC tidak tersedia?
    
4.  Kapan invoice biasanya diterima: sebelum barang datang, bersamaan, atau setelahnya?
    
5.  Apakah barang boleh menambah saldo sebelum Finance menerima faktur?
    
6.  Apa beda keputusan invoice “accepted” dengan invoice dibayar, dan apakah pembayaran perlu dicatat di sistem ini?
    
7.  Apakah document\_total harus sama dengan total item, atau mencakup pajak, diskon, dan biaya lain?
    
8.  Metode valuasi persediaan apa yang digunakan Finance, dan harga mana yang menjadi sumber resmi?
    
9.  Apakah Stock Opname mingguan dan akhir bulan dibuat otomatis, dan siapa yang boleh menghitung tiap lokasi?
    
10.  Bagaimana menangani barang expired, rusak, hilang, atau retur supplier?
    
11.  Apakah distribusi dapat diterima sebagian, atau satu transfer harus diterima penuh?
    
12.  Apakah Staff Gudang boleh melihat/mengelola transaksi semua lokasi?
    
13.  Apakah Finance membutuhkan export nilai rupiah per lokasi/produk atau cukup laporan kuantitas?
    
14.  Apakah status penolakan invoice dan rejection Stock In perlu alasan terstruktur atau kategori tertentu?
    

12\. Glosarium
--------------

*   **Ledger pergerakan stok:** catatan transaksi masuk/keluar yang menjadi dasar hitung saldo.
    
*   **Movement:** satu baris perubahan kuantitas stok.
    
*   **Batch:** kelompok barang yang diterima pada satu waktu, dapat dilacak nomor lot dan kedaluwarsanya.
    
*   **FEFO:** _First Expired, First Out_; batch dengan tanggal kedaluwarsa paling dekat dikeluarkan lebih dulu.
    
*   **FIFO:** _First In, First Out_; barang yang masuk lebih awal dikeluarkan lebih dulu. Ini tidak otomatis sama dengan metode valuasi keuangan.
    
*   **PR:** _Purchase Request_, permintaan pembelian.
    
*   **PO:** _Purchase Order_, pesanan resmi ke supplier.
    
*   **Stock In:** pencatatan penerimaan barang di suatu lokasi.
    
*   **Stock Out:** pencatatan pengeluaran barang dari suatu lokasi.
    
*   **Stock Opname:** perbandingan jumlah fisik dengan saldo sistem.
    
*   **Adjustment:** transaksi koreksi saldo beserta alasan.
    
*   **Approval:** keputusan menyetujui/menolak transaksi sebelum dampak tertentu diterapkan.
    
*   **UAT:** _User Acceptance Testing_, uji penerimaan oleh pengguna operasional.
    
*   **Permission:** izin teknis untuk melakukan tindakan/akses; berbeda dari jobdesk bisnis yang disepakati.
    

Audit selesai. Saya berhenti pada laporan ini dan tidak melakukan implementasi.