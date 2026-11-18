# Rencana Task Bertahap — Inventory Technolife

> **Jenis dokumen:** rencana lanjutan berbasis audit codebase dan keputusan pengguna  
> **Status:** Implementasi bertahap; migration dan UAT masih pending  
> **Tanggal:** 2026-10-15  
> **Tujuan:** Menyelesaikan gap inventory secara bertahap sambil menjaga fitur yang sudah berjalan tetap utuh.

## 1. Cara membaca dokumen ini

Dokumen ini adalah rencana tambahan setelah `technolife-inventory-roadmap-v3.md`. Roadmap utama tetap menjadi sumber rencana implementasi umum. Dokumen ini tidak mengganti roadmap tersebut dan tidak mengubah status fitur lama.

Label yang digunakan:

- **[SUDAH ADA]** — didukung route, controller/service, model, atau migration yang ditemukan saat audit.
- **[DIKONFIRMASI]** — keputusan/kebutuhan yang disampaikan pemilik proyek.
- **[REKOMENDASI]** — saran proses yang masuk akal, tetapi bukan aturan resmi sampai disetujui Technolife.
- **[PERLU KONFIRMASI]** — detail yang belum tersedia dan tidak boleh dipaksakan menjadi business rule.

Audit baseline sebelumnya bersifat read-only. Pemilik proyek kemudian meminta implementasi task plan dan mengonfirmasi keputusan inti di bagian 8; perubahan kode additive sudah disiapkan. Migration belum dijalankan dan UAT belum dilakukan.

## 2. Aturan utama: pertahankan sistem yang sudah dibangun

Pakai aturan ini untuk seluruh task di dokumen:

1. **Jangan rewrite atau mengganti total fitur yang sudah berjalan.** Pertahankan route bernama, model, tabel, menu, dan alur yang saat ini digunakan kecuali ada alasan yang disetujui dan rencana transisi yang jelas.
2. **Dashboard existing berstatus LOCKED.** Jangan mengubah tampilan atau kodenya dalam pekerjaan task ini.
3. **Tambahkan kemampuan secara incremental.** Perubahan sebaiknya menambah field/status/relasi atau service kecil yang kompatibel dengan data lama, bukan membuat ulang modul.
4. **Jaga sumber saldo yang ada.** `stock_movements` tetap menjadi ledger/sumber saldo. Jangan membuat saldo kedua yang dapat berbeda. Setiap transaksi baru harus menghasilkan movement melalui jalur layanan yang konsisten.
5. **Jangan hard-delete transaksi inventory.** Gunakan transaksi koreksi, retur, write-off, atau reversal yang memiliki alasan dan jejak audit.
6. **Jaga kompatibilitas data lama.** Migration baru harus mempertahankan record existing, memiliki nilai default/backfill yang aman, dan memiliki strategi rollback. Jangan menjalankan migration pada production tanpa review dan persetujuan terpisah.
7. **Pertahankan batas modul.** Perluas service/controller yang ada secara terarah. Hindari memindahkan seluruh logika atau mengganti teknologi UI hanya untuk menyelesaikan satu gap.
8. **Jangan mengubah permission secara massal.** Ubah hanya permission yang terkait task, verifikasi akses per role, dan pertahankan akses Super Admin. Jangan mengasumsikan permission pada database sama dengan seeder tanpa verifikasi environment.
9. **Pecah perubahan menjadi PR/commit kecil per task.** Setiap task menyebutkan route, tabel, service, dampak data, dan kriteria selesai sebelum mulai implementasi.
10. **Gunakan feature flag atau rollout bertahap** bila perubahan mengubah perilaku transaksi yang telah dipakai user.
11. **Laporan dan audit harus tetap bisa membaca transaksi lama** yang belum memiliki field baru. Untuk record lama, tampilkan status legacy yang jelas; jangan mengarang nilai historis.
12. **Selesaikan requirement discovery sebelum perubahan domain inti.** Jika aturan yang memengaruhi saldo, harga, approval, atau kewajiban Finance belum jelas, tandai `[BLOCKED: BUSINESS CONFIRMATION]`; jangan menebak di kode.
13. Permintaan implementasi mencakup perubahan kode dan migration additive, tetapi bukan izin untuk menjalankan migration pada database, deployment, atau mengirim WhatsApp. Perubahan tersebut memerlukan langkah rilis/UAT tersendiri.

## 3. Kondisi awal yang perlu dipertahankan

- **[SUDAH ADA]** Enam role, autentikasi, permission, master data, dashboard, Data Stock, Stock In/Out, Stock Opname, adjustment, transfer, purchasing, invoice review, laporan, low-stock alert, audit dasar, dan konfigurasi memiliki implementasi di codebase.
- **[SUDAH ADA]** Saldo dihitung dari `stock_movements`; Stock Out memiliki alokasi FEFO; penerimaan Stock In ke outlet menunggu PIC sebelum membuat movement.
- **[SUDAH ADA]** Purchase Request otomatis berstatus approved; PO diarahkan ke warehouse; penerimaan PO parsial menyimpan jumlah datang/diterima dan catatan selisih.
- **[SUDAH ADA]** Invoice memiliki item dan `unit_price`, tetapi layar valuation sekarang menampilkan kuantitas, bukan nominal persediaan.
- **[DIKONFIRMASI]** WRT/Main Kitchen dapat mengirim barang langsung ke gudang Technolife.
- **[DIKONFIRMASI]** Staff Gudang boleh mencatat Stock Out yang mengambil stok dari outlet karena Staff mengetahui jumlah barang. Aturan ini perlu dibedakan dari transfer gudang ke outlet agar riwayat menjelaskan konteks pengeluaran.
- **[DIKONFIRMASI]** PIC bertanggung jawab atas satu outlet.
- **[DIKONFIRMASI]** Stok boleh bertambah sebelum Finance menyetujui invoice, dengan syarat riwayatnya jelas.
- **[DIKONFIRMASI]** Harga satuan yang dimasukkan Purchasing pada faktur menjadi sumber harga yang dipakai Finance untuk valuasi.
- **[DIKONFIRMASI]** Status pembayaran perlu dicatat terpisah dari status review invoice.
- **[DIKONFIRMASI]** Finance membutuhkan laporan nilai persediaan dalam rupiah, selain kuantitas.
- **[DIKONFIRMASI]** Alert stok minimum diharapkan dikirim otomatis ke WhatsApp PIC outlet yang terdaftar, selain alert aplikasi yang sudah ada.
- **[PERLU KONFIRMASI]** PIC pengganti saat PIC utama tidak ada; pengiriman supplier langsung ke outlet; perlakuan pajak/biaya invoice; cost formula akuntansi; penerima alert jika nomor PIC tidak tersedia.

## 4. Keputusan/rekomendasi proses yang disarankan

### 4.1 Faktur, penerimaan barang, dan waktu dokumen

**[REKOMENDASI]** Jangan mensyaratkan Finance menyetujui faktur sebelum barang fisik diterima. Alur yang praktis:

1. Purchasing membuat/menerima invoice dari supplier dan mencatat nomor, tanggal, harga per item, jumlah, serta file bukti.
2. Staff boleh mencatat penerimaan fisik sesuai PO segera saat barang datang, walaupun invoice belum direview Finance.
3. Penerimaan membuat saldo kuantitas dan movement; record receipt menyimpan referensi PO, invoice jika sudah ada, petugas, waktu, dan selisih.
4. Jika dokumen invoice datang belakangan, invoice ditautkan ke PO/receipt sesudahnya. Selama Finance belum menerima, nilai persediaan pada laporan ditandai **sementara/provisional**.
5. Saat Finance mereview, keputusan invoice dicatat terpisah. Jika harga berubah, simpan riwayat harga/status lama-baru dan hitung ulang valuasi periode sesuai kebijakan Finance; jangan mengedit jejak penerimaan.

Untuk praktik vendor, invoice dapat dikirim sebelum, bersama, atau setelah barang sesuai proses supplier. Sistem sebaiknya mengizinkan pencatatan penerimaan lebih dahulu dan pengaitan dokumen kemudian. **[PERLU KONFIRMASI]** apakah Technolife ingin mengizinkan receipt tanpa invoice pada semua supplier atau hanya keadaan tertentu.

### 4.2 Harga faktur dan metode valuasi

**[DIKONFIRMASI]** Harga per unit pada invoice Purchasing adalah sumber harga yang diminta.  
**[REKOMENDASI]** Tambahkan klasifikasi nilai pada laporan: `provisional` untuk invoice belum accepted/review; `confirmed` setelah diterima Finance; jangan tampilkan keduanya seolah sama-sama final.

Untuk barang interchangeable, IAS 2 mengenali FIFO atau weighted average sebagai rumus biaya; aliran fisik FEFO tidak dengan sendirinya menetapkan rumus akuntansi. Karena sistem telah menggunakan FEFO secara fisik dan harga diterima per invoice, **moving weighted average per produk dan lokasi** adalah kandidat awal yang mudah dijelaskan untuk laporan operasional. Ini tetap **[REKOMENDASI, PERLU PERSETUJUAN Finance]**, bukan keputusan akuntansi. Minta Finance mengesahkan cost formula dan kebijakan untuk biaya angkut, pajak yang dapat/tidak dapat dikreditkan, diskon, retur, dan koreksi harga sebelum nominal resmi dihitung. Referensi prinsip: [IFRS IAS 2 — Inventories](https://www.ifrs.org/issued-standards/list-of-standards/ias-2-inventories/).

Rumus dasar kandidat moving average (tanpa biaya tambahan) adalah:

```text
nilai_saldo_baru = nilai_saldo_sebelum + (qty_diterima × harga_unit_terkonfirmasi)
harga_rata_baru = nilai_saldo_baru / qty_saldo_baru
nilai_keluar = qty_keluar × harga_rata_berlaku
```

Transaksi dengan invoice provisional perlu dilaporkan sebagai estimasi atau dikeluarkan dari subtotal confirmed; perlakuan pastinya diputuskan Finance. Transfer antar lokasi sebaiknya membawa cost perolehan yang sama, bukan dianggap pembelian baru.

### 4.3 Rekonsiliasi total faktur

**[REKOMENDASI]** Jangan memaksa `document_total` selalu sama dengan jumlah `qty × harga_unit` sebelum komponen nilai lain didukung. Simpan subtotal item, diskon, pajak, biaya angkut/biaya lain, dan total dokumen sebagai komponen terpisah bila memang ada di dokumen supplier. Hitung ulang total yang diharapkan dari komponen tersebut; jika berbeda dari total pada faktur, minta catatan/konfirmasi Purchasing dan Finance. Toleransi pembulatan harus kecil dan eksplisit. Harga/kuantitas item tetap dapat dibandingkan dengan PO dan receipt; selisih tidak boleh hilang hanya karena total invoice cocok. Jika faktur Technolife selalu hanya berisi harga barang tanpa komponen tambahan, Finance dapat menetapkan aturan total harus sama dengan subtotal item.

**Kriteria:** user bisa melihat komponen pembentuk total, nilai faktur tidak ditimpa hasil hitung sistem, dan setiap perbedaan memiliki penjelasan/audit.
### 4.3 Pembayaran invoice

**[DIKONFIRMASI]** Perlu ada status pembayaran. **[REKOMENDASI]** Simpan status pembayaran terpisah dari status review Finance:

- `unpaid` — belum ada pembayaran tercatat;
- `partially_paid` — baru sebagian dibayar;
- `paid` — saldo terutang menjadi nol;
- `void/cancelled` hanya jika proses bisnis mengizinkan pembatalan dan tetap ada audit.

Catat pembayaran sebagai record (tanggal, jumlah, metode/referensi, pencatat, catatan) agar pembayaran cicilan dapat ditelusuri. Jangan mengubah invoice `accepted` menjadi `paid` sebagai satu status tunggal karena keduanya menjawab pertanyaan berbeda. **[PERLU KONFIRMASI]** siapa yang memasukkan pembayaran, apakah pembayaran parsial diperbolehkan, dan apakah integrasi Finance eksternal menjadi sumber resmi.

### 4.4 Stok rusak, expired, hilang, dan retur

**[DIKONFIRMASI]** Kejadian ini perlu dicatat. **[REKOMENDASI]** Buat transaksi disposal/write-off terpisah yang menunjuk produk, lokasi, batch, kuantitas, jenis alasan, bukti/catatan, pelapor, reviewer, waktu, serta movement keluar. Untuk barang rusak/expired, pindahkan secara logis ke status karantina jika barang masih menunggu keputusan; saat benar-benar dibuang, catat write-off. Untuk retur supplier, catat dokumen retur dan movement keluar yang menghubungkan ke invoice/PO asal. Tidak boleh menghapus Stock In atau mengedit transaksi lama.

Kategori alasan minimum: `expired`, `damaged`, `lost`, `quality_rejected`, `supplier_return`, `count_variance`, `other` dengan teks penjelas. Finance memutuskan apakah approval wajib berdasarkan nominal/jenis.

### 4.5 Partial receiving transfer gudang-outlet

**[REKOMENDASI]** Izinkan penerimaan sebagian dengan receipt per kejadian. Saldo outlet bertambah hanya sejumlah yang benar-benar diterima; sisa transfer tetap `in_transit/partially_received`. PIC dapat menyelesaikan penerimaan berikutnya atau menandai selisih final dengan alasan. Status akhir dan audit menunjukkan dispatched, diterima sebagian, discrepancy, atau selesai. Rekomendasi ini sesuai praktik barang datang tidak selalu sama dengan jumlah yang dikirim dan menghindari menganggap barang yang belum diterima sebagai stok outlet.

### 4.6 Opname berkala dan petugas

**[REKOMENDASI]** Gunakan dua ritme:

- **Cycle count mingguan:** lokasi menghitung kelompok barang bergiliran (misalnya barang high value/high movement lebih sering), agar operasional tidak berhenti untuk menghitung semua barang.
- **Full count akhir bulan:** hitung semua produk aktif per lokasi untuk rekonsiliasi periode Finance.

Saran pemisahan tugas: petugas yang menguasai stok lokasi menghitung (Staff Gudang untuk warehouse; PIC untuk outlet bila disetujui), Purchasing memeriksa selisih dan menyetujui, Finance menerima ringkasan selisih akhir bulan. Penghitung tidak menyetujui hitungannya sendiri. Mulai dengan kalender/reminder dan checklist, bukan auto-adjustment otomatis. **[PERLU KONFIRMASI]** apakah PIC diberi `opname.manage` untuk outlet dan apakah cycle count parsial diterima.

Untuk PIC absen, saran paling aman adalah Super Admin menetapkan PIC pengganti sementara pada outlet dengan tanggal mulai/akhir dan audit perubahan; jangan memberi PIC akses ke outlet lain secara permanen. Untuk kondisi darurat dapat ada override Super Admin dengan alasan wajib. **[REKOMENDASI, PERLU KONFIRMASI].**

### 4.7 Alasan penolakan

**[REKOMENDASI]** Gunakan kategori + catatan bebas, bukan teks bebas saja:

- jumlah tidak sesuai;
- produk salah;
- batch/expiry tidak memenuhi;
- rusak/kualitas tidak layak;
- dokumen/harga tidak cocok;
- lokasi/penerima tidak sesuai;
- lainnya.

Simpan actor, waktu, status sebelum/sesudah, kategori, alasan, dan bukti. Revisi invoice tetap mengembalikan ke Purchasing tanpa menghapus pengajuan sebelumnya.

## 5. Backlog per task

Urutan ini mengikuti dependensi domain. Tiap task harus diestimasi dan disetujui sebelum coding; jangan menggabungkan semuanya menjadi satu perubahan besar.

### TASK 0 — Baseline, kompatibilitas, dan keputusan domain

**Priority:** P0 · **Status:** In Progress (audit read-only tersedia)  
**Tujuan:** Membekukan baseline agar pekerjaan baru tidak merusak fitur yang sudah ada.

- Catat route/model/table/permission untuk setiap fitur yang akan disentuh.
- Tandai halaman Dashboard sebagai LOCKED.
- Konfirmasi lokasi mana yang boleh dipilih untuk Stock Out, metode cost formula, komponen invoice, pembayaran, dan receipt tanpa invoice.
- Tentukan data lama yang perlu ditampilkan sebagai `legacy/unknown`, bukan melakukan backfill spekulatif.

**Kriteria selesai:** keputusan penting diberi `[DIKONFIRMASI]` atau `[PERLU KONFIRMASI]`; perubahan telah dipecah menjadi task kecil dengan daftar file/tabel terdampak. Tidak ada perubahan code/database dalam task discovery.

### TASK 1 — Aturan transaksi lokasi dan jejak Stock Out

**Priority:** P0/P1 · **Dependency:** Task 0  
**Tujuan:** Mempertahankan kemampuan Staff mencatat Stock Out dari outlet sambil membuat konteks pengeluaran jelas.

- Jangan memblokir Stock Out outlet secara umum; hal itu sesuai arahan pemilik proyek.
- Tambahkan/standarkan jenis tujuan/transaksi (misalnya konsumsi internal, waste, retur, koreksi, transfer) bila disetujui, tanpa menghapus kolom `destination` atau route lama.
- Validasi produk, lokasi, saldo, role, dan alasan sesuai jenis transaksi.
- Pastikan transfer antar lokasi memakai workflow transfer yang sudah ada dan tidak menggandakan movement.
- Riwayat harus menampilkan lokasi asal, tujuan/konteks, batch bila ada, actor, dan alasan.

**Kriteria selesai:** Staff dapat melakukan transaksi yang memang diizinkan; setiap pengurangan saldo dapat dijelaskan dari detail dan audit; tidak ada transaksi ganda; akses role/lokasi terverifikasi. **[PERLU KONFIRMASI]** apakah Staff dibolehkan memilih semua outlet atau hanya lokasi yang ditetapkan.

### TASK 2 — Invoice, receipt independen, dan status pembayaran

**Priority:** P0 · **Dependency:** Task 0  
**Tujuan:** Memisahkan penerimaan fisik, review faktur, dan pembayaran tanpa mengubah alur Stock In lama.

- Pertahankan `purchase_invoices` dan relasinya ke supplier/PO/receipt.
- Izinkan receipt PO tercatat sebelum Finance menerima invoice; receipt harus memiliki jejak invoice pending atau referensi yang dapat ditautkan kemudian.
- Tambahkan lifecycle pembayaran terpisah. Untuk pembayaran parsial, gunakan record pembayaran, bukan hanya boolean `paid`.
- Status existing (`draft`, `submitted`, `accepted`, `rejected`, `revision_required`) tetap menjadi status review dokumen; jangan dipakai ulang sebagai status penerimaan atau pembayaran.
- Pertahankan riwayat sebelum/sesudah pada perubahan supplier, item, kuantitas, harga, dokumen, review, dan pembayaran.
- Harga unit dan unit snapshot tetap tersimpan; validasi hubungan supplier/PO/item tetap aktif.

**Kriteria selesai:** receipt boleh masuk ledger walau invoice belum di-review; receipt dan invoice bisa direkonsiliasi tanpa menulis ulang riwayat; pembayaran penuh/parsial dapat dilacak; invoice accepted belum otomatis berarti dibayar.

### TASK 3 — Valuasi rupiah dan laporan Finance

**Priority:** P0 · **Dependency:** Task 2 + persetujuan cost formula Finance  
**Tujuan:** Memberi laporan nilai rupiah per produk/lokasi/periode berdasarkan harga faktur Purchasing.

- Gunakan harga unit invoice Purchasing sebagai sumber harga sesuai arahan pemilik proyek.
- Sebelum nominal menjadi resmi, Finance menetapkan moving weighted average/FIFO atau kebijakan lain serta perlakuan biaya/pajak/retur.
- Simpan nilai/harga yang dipakai pada snapshot/pergerakan valuasi agar laporan lama tidak berubah diam-diam ketika faktur diedit.
- Bedakan `provisional` dan `confirmed` cost; tampilkan jumlah item tanpa harga terkonfirmasi sebagai nilai yang belum lengkap, bukan Rp0.
- Saat invoice direvisi/diterima setelah receipt, lakukan adjustment/revaluation yang dapat ditelusuri; jangan mengubah movement kuantitas asal.
- Tambahkan export rupiah per produk, lokasi, tanggal, sumber harga/invoice, status review, kuantitas, unit cost, nilai, dan subtotal.

**Kriteria selesai:** Finance dapat menelusuri nilai sampai ke invoice; saldo kuantitas tetap berasal dari movement; nilai rupiah dapat direkonsiliasi; formula disetujui Finance dan dokumentasi menjelaskan transaksi pending.

### TASK 4 — Waste, expired, damage, loss, dan retur supplier

**Priority:** P1 · **Dependency:** Task 1 + Task 3 (untuk nilai kerugian)  
**Tujuan:** Mencatat stok tidak layak pakai sebagai transaksi eksplisit.

- Tambahkan workflow karantina bila perlu, lalu disposal/write-off dengan reason category dan batch.
- Pengajuan disposal `pending_approval` adalah hold karantina kuantitas; kurangi nilai available/FEFO, bukan saldo ledger. Rejection melepas hold; approval menulis satu movement out.
- Aturan batch wajib berlaku per SKU yang ditandai expiry-tracked; jangan backfill/force-kan record lama.
- Near-expiry alert awal menggunakan laporan expiry 30 hari yang sudah ada; perubahan WhatsApp/dashboard memerlukan task dan persetujuan kanal tersendiri.
- Tambahkan workflow retur supplier yang menunjuk invoice/PO/receipt asal.
- Movement keluar terjadi sekali saat keputusan final; jika menunggu approval, saldo tidak berubah sebelum approval.
- Pengajuan pending mengarantina kuantitas pada lapisan availability/FEFO; jangan menulis movement sampai disetujui. Approval memeriksa ulang balance karena stok dapat berubah.
- Retur supplier wajib mengarah ke invoice accepted dan receipt approved yang tertaut, batch receipt, supplier, SKU, lokasi, serta PO asal. Batasi total pending+approved pada kuantitas yang benar-benar diterima.
- Catat hasil klaim bertahap sebagai record audit terpisah: credit note/refund menyimpan nominal dan bukti; penggantian merujuk Stock In approved; penolakan klaim menyimpan alasan/bukti.
- Simpan bukti, actor, reviewer, lokasi, tanggal, alasan, kuantitas, dan dampak valuasi.
- Gunakan permission baru/secara terbatas; jangan memberi semua role hak adjustment.

**Kriteria selesai:** barang rusak/expired/retur tidak hilang dari history; kuantitas dan nilai turun dengan alasan yang dapat diaudit; koreksi dapat dibuat tanpa hard delete.

### TASK 5 — Partial receiving pada transfer outlet

**Priority:** P1 · **Dependency:** Task 1  
**Tujuan:** Menangani jumlah diterima outlet yang berbeda atau datang bertahap.

- Simpan setiap penerimaan PIC sebagai receipt terpisah dan tautkan ke transfer/item/batch.
- Terima hanya jumlah aktual; saldo outlet bertambah untuk quantity accepted.
- Pertahankan sisa transfer sampai receipt berikutnya atau penyelesaian discrepancy final.
- Alasan wajib untuk kurang/rusak/lebih; final close memerlukan keputusan yang dapat ditelusuri.
- Pertahankan data transfer lama yang sudah full receipt.

**Kriteria selesai:** total dispatched, received, dan discrepancy bisa direkonsiliasi; tidak ada penerimaan melebihi sisa kiriman tanpa alasan/keputusan; transfer history menunjukkan semua receipt.

### TASK 6 — Jadwal Opname dan delegasi PIC

**Priority:** P1 · **Dependency:** Task 0 + Task 1  
**Tujuan:** Mendukung cycle count dan full count tanpa mengubah mekanisme snapshot/approval yang sudah tersedia.

- Tambahkan jadwal/reminder mingguan dan akhir bulan setelah periode disepakati.
- Usulkan cycle count parsial mingguan dan full count akhir bulan.
- Putuskan peran penghitung per lokasi; evaluasi `opname.manage` untuk PIC hanya pada outlet yang ditugaskan.
- Tambahkan assignment PIC sementara dengan masa berlaku dan audit jika ada kebutuhan pengganti.
- Pertahankan aturan maker-checker dan mekanisme cutoff movement yang sudah ada.

**Kriteria selesai:** jadwal/reminder tidak mengubah saldo; item yang dihitung dan periode jelas; penghitung tidak dapat menyetujui sendiri; penugasan PIC sementara tidak membuka akses lokasi lain.

### TASK 7 — Alert minimum stock ke PIC WhatsApp

**Priority:** P1 · **Dependency:** validasi nomor PIC dan persetujuan provider/operasional  
**Tujuan:** Mengirim alert minimum stock ke PIC outlet yang benar.

- Pertahankan low-stock alert dan notification history saat ini.
- Hubungkan outlet → PIC aktif satu outlet → nomor WhatsApp yang tervalidasi.
- Kirim otomatis untuk lokasi yang threshold-nya tercapai; cegah notifikasi duplikat berulang sampai status pulih/ambang siklus baru.
- Catat penerima, produk, lokasi, threshold, waktu, hasil kirim/gagal, dan retry.
- In-app alert tetap menjadi fallback jika nomor kosong atau provider tidak tersedia.
- Hanya Super Admin mengelola konfigurasi provider; jangan mengirim pesan nyata selama pengembangan/verifikasi tanpa persetujuan operasional.

**Kriteria selesai:** simulasi/test menggunakan provider mock atau mode non-delivery; pesan hanya menuju PIC lokasi yang cocok; log tidak menyimpan rahasia/credential.

### TASK 8 — Konsolidasi Riwayat, laporan, dan UAT

**Priority:** P1 · **Dependency:** Tasks 1–7 sesuai modul terkait  
**Tujuan:** Memastikan empat halaman utama memberi pandangan yang konsisten setelah fitur domain ditambah.

- Perluas Riwayat agar transfer receipt, pembayaran, waste/retur, dan keputusan review dapat ditelusuri sesuai kebutuhan; jangan mengganti halaman utama.
- Laporan Finance menampilkan rupiah dan status provisional/confirmed; laporan operasional mempertahankan filter lokasi/produk/tanggal.
- UAT per role mencakup PIC satu outlet, Staff, Purchasing, Finance, Management, dan Super Admin.
- Verifikasi data lama, export, permission, audit, dan tidak ada movement ganda.
- Dashboard tidak masuk scope perubahan.

**Kriteria selesai:** skenario UAT disetujui role terkait; hasil laporan direkonsiliasi dengan transaksi; tidak ada regression blocker; panduan operasional diperbarui.

## 6. Pola rilis aman untuk setiap task

Sebelum implementasi task:

1. Baca roadmap utama dan task terkait.
2. Catat perilaku existing yang harus tetap sama.
3. Tulis keputusan bisnis yang diperlukan; stop hanya task yang terblokir, lanjutkan pekerjaan independen.
4. Buat perubahan kecil pada satu modul; dokumentasikan route, permission, migration, dan data terdampak.
5. Siapkan strategi migration kompatibel: nullable/default aman, backfill deterministik, verifikasi hasil, rollback yang jelas.
6. Verifikasi jalur lama dan baru berdasarkan acceptance criteria sebelum rilis.
7. UAT oleh role terkait sebelum perilaku transaksi dipakai operasional.
8. Perbarui roadmap dan dokumen ini setelah task benar-benar selesai; jangan tandai Done hanya karena halaman dibuat.

## 7. File codebase yang menjadi titik awal

- Route: `routes/web.php`
- Role/permission baseline: `database/seeders/RolePermissionSeeder.php`
- Stock ledger dan transaksi: `app/Services/StockInService.php`, `StockInApprovalService.php`, `StockOutService.php`, `StockTransferService.php`, `StockOpnameService.php`
- Purchasing dan invoice: `app/Services/PurchasingService.php`, `PurchaseInvoiceService.php`
- Valuasi quantity saat ini: `app/Services/InventoryValuationService.php`
- Report: `app/Services/InventoryReportService.php`, `app/Http/Controllers/ReportController.php`
- Riwayat: `app/Http/Controllers/TransactionHistoryController.php`
- Database: migrations `2026_10_08_*`, `2026_10_14_*`
- Roadmap utama: `docs/technolife-inventory-roadmap-v3.md`

## 8. Keputusan dan rekomendasi operasional

Keputusan yang sudah diberikan pemilik proses:

1. Valuasi persediaan memakai **moving weighted average per produk-lokasi**.
2. Harga unit pada invoice yang dibuat Purchasing adalah sumber harga pembelian.
3. Barang boleh diterima dan menambah ledger sebelum dokumen invoice tersedia; receipt harus berstatus/link **invoice pending** sampai dokumen ditautkan. Receipt tidak boleh dihapus atau disamarkan setelah ditautkan.
4. Finance perlu status pembayaran. Rekomendasi: catat pembayaran aktual sebagai beberapa baris pembayaran parsial, dengan tanggal, jumlah, metode, referensi, dan pengguna pencatat. Status invoice (review) dan status pembayaran tetap terpisah. Pembayaran baru boleh dicatat sesudah invoice diterima Finance.
5. Staff boleh mencatat Stock Out di lokasi; jenis pengeluaran dibuat opsional agar alur lama tetap dapat digunakan.
6. Setiap PIC bertanggung jawab pada satu outlet. Jika PIC tidak aktif/tersedia, operasional harus menunjuk PIC pengganti aktif melalui administrasi user. Selama belum ditunjuk, alert tetap masuk ke Staff Gudang/Super Admin sebagai fallback operasional.
7. Invoice receipt parsial disarankan menjadi praktik standar: simpan kuantitas yang benar-benar diterima/layak, beri alasan atas selisih atau barang rusak, dan tautkan setiap receipt ke invoice saat tersedia.
8. Write-off dan retur supplier dibuat sebagai pengajuan terpisah; tidak mengubah saldo sebelum disetujui Purchasing. Retur harus terkait invoice yang diterima Finance serta batch penerimaan yang benar. Pengaju tidak boleh menyetujui pengajuannya sendiri.
9. Transfer antarlokasi boleh diterima bertahap. Tutup dengan jumlah aktual; selisih final wajib diberi alasan dan hanya barang yang diterima menambah stok tujuan.
10. Stock Opname mingguan disarankan sebagai cycle count bergilir per kelompok produk (ABC/risk), sedangkan akhir bulan dilakukan full count semua saldo material. Petugas outlet menghitung lokasi sendiri; approver berbeda melakukan review selisih. Jadwal/reminder otomatis belum dibangun.
11. Laporan Finance perlu menampilkan saldo, harga rata-rata, nilai rupiah, kuantitas berharga provisional, dan kuantitas tanpa harga; sediakan CSV. Sampai kebijakan pajak/ongkir/diskon disahkan, biaya tersebut jangan dialokasikan diam-diam dan nilai harus diberi status operasional, bukan angka buku final.
12. Untuk rekonsiliasi invoice, rekomendasi terbaik adalah document total = subtotal item setelah diskon ditambah pajak/ongkir/biaya yang ditagih, dengan tiap komponen dicatat terpisah. Valuasi persediaan memasukkan hanya komponen yang Finance tetapkan sebagai biaya perolehan; pajak yang dapat dikreditkan tidak otomatis masuk biaya persediaan. Komponen terpisah belum tersedia pada form sekarang dan menjadi pekerjaan lanjutan sebelum laporan dipakai sebagai angka akuntansi final.
13. Alasan penolakan wajib berupa catatan bebas dan kategori agar bisa dianalisis: kuantitas, harga, supplier/PO, dokumen tidak valid, barang rusak/kedaluwarsa, atau lainnya. Status penolakan tetap dapat diajukan ulang lewat revisi, bukan menghapus riwayat.

## 9. Progress implementasi — 2026-10-15

Status membedakan fitur yang telah tersedia sebelum pekerjaan ini, perubahan yang ditambahkan, dan langkah rilis yang masih diperlukan. Migration belum dijalankan dan UAT belum dilakukan.

| Task | Status | Implementasi / batas yang tersisa |
|---|---|---|
| Task 0 — Baseline dan keputusan | **SELESAI untuk keputusan lingkup** | Baseline repository dan perilaku yang harus dipertahankan sudah didokumentasikan. UAT dan kebijakan komponen invoice masih prasyarat rilis Finance. |
| Task 1 — Stock Out dan jejak lokasi | **IMPLEMENTED, UAT pending** | Permission/alur Stock Out Staff yang ada dipertahankan; tujuan pengeluaran opsional ditambahkan dan ikut ke export laporan. |
| Task 2 — Invoice, receipt, pembayaran | **IMPLEMENTED, UAT pending** | Receipt PO boleh tercatat tanpa invoice dan tetap menambah ledger; receipt bisa ditautkan kemudian oleh Purchasing. Form mencatat harga item. Pembayaran parsial tercatat terpisah, hanya setelah Finance menerima invoice. Pajak/ongkir/diskon rinci masih belum tersedia. |
| Task 3 — Valuasi rupiah | **IMPLEMENTED operasional, belum final akuntansi** | Snapshot replay ledger memakai moving weighted average per produk-lokasi; harga invoice yang belum diterima ditandai provisional, kuantitas tanpa sumber harga ditandai incomplete; halaman dan CSV menampilkan rupiah. Belum mengalokasikan komponen tambahan invoice. |
| Task 4 — Waste/expired/return | **IMPLEMENTED, UAT pending** | Pengajuan write-off/retur memakai status dan audit trail; saldo baru berkurang saat Purchasing menyetujui. Retur dibatasi pada invoice accepted dan batch dari PO terkait. Pastikan alur barang karantina fisik dijalankan SOP gudang. |
| Task 5 — Partial transfer receipt | **SUDAH ADA, dipertahankan** | Backend/UI lama mendukung receipt parsial dan discrepancy final; tidak ditulis ulang. UAT dan rekonsiliasi laporan tetap diperlukan. |
| Task 6 — Opname jadwal/delegasi | **PARTIAL** | Cycle count mingguan parsial dengan daftar produk manual, full count akhir bulan, cutoff, lock movement per produk selama hitung, maker-checker, dan PIC outlet dengan scope satu lokasi telah diimplementasikan. Reminder otomatis serta assignment PIC pengganti sementara masih pending. |
| Task 7 — PIC WhatsApp alert | **IMPLEMENTED, konfigurasi/UAT pending** | Nomor WA opsional per user PIC dan alert outlet memasukkan PIC terdaftar; penerima global yang sudah dikonfigurasi tetap dipertahankan. Provider dan nomor harus diuji sebelum operasional. |
| Task 8 — History/report/UAT | **PARTIAL** | History/report mencakup transaksi disposal dan export valuasi tersedia; kategori penolakan Stock In/invoice tercatat bersama catatan bebas. Belum ada rekonsiliasi UAT Finance, uji provider WA, serta report komponen biaya invoice. |

### Catatan kompatibilitas dan rilis

- Tidak ada penggantian ledger `stock_movements`, role inti, halaman Dashboard, atau alur transfer parsial.
- Migration yang ditambahkan bersifat additive. Receipt tanpa invoice tetap menyimpan `purchase_invoice_id = null`; linking kemudian mencatat audit event dan tidak membuat movement kedua.
- Jangan menjalankan migration/seed di lingkungan produksi sebelum backup, tinjauan schema, dan UAT per role. Jalankan migration di staging dahulu dan verifikasi saldo sebelum/sesudah untuk receipt, payment, transfer, Stock Out, dan disposal.
- Stock Out purpose nullable; data transaksi lama tetap terbaca sebagai belum dikategorikan.
- Penerimaan invoice sebelum review tetap provisional dalam laporan. Jangan menggunakan nilai incomplete/provisional sebagai angka tutup buku tanpa review Finance.
