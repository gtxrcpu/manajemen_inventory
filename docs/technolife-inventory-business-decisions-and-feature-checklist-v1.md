# Keputusan Bisnis & Checklist Pengembangan Inventory Technolife

> **Versi:** 1.0  
> **Tanggal:** 2026-10-15  
> **Status:** Rekomendasi operasional; checklist implementasi perlu ditinjau sebelum rollout  
> **Tujuan:** Menetapkan alur inventory yang praktis dan dapat diaudit, lalu memecah pekerjaan menjadi fitur, task, dan subtask yang bisa dicentang.

Dokumen ini melengkapi `technolife-inventory-roadmap-v3.md`, `technolife-inventory-incremental-task-plan-v1.md`, dan `konsep system.md`. Dokumen ini tidak mengganti semuanya. Bila ada perbedaan, keputusan bisnis yang sudah dikonfirmasi pemilik proses berlaku; aturan yang masih berupa rekomendasi perlu disetujui sebelum mengubah perilaku transaksi.

## 1. Cara menggunakan checklist

- `[x]` berarti sudah diverifikasi selesai dengan bukti; keberadaan halaman saja tidak cukup.
- `[ ]` berarti perlu dikerjakan, diverifikasi, atau disetujui.
- Setiap task harus tetap berada dalam scope fiturnya dan tidak menjadi alasan untuk rewrite modul lain.
- Perubahan transaksi saldo wajib menyebut movement yang dibuat, actor, lokasi, referensi, waktu, alasan, serta cara koreksi/reversal.
- Waktu adalah estimasi awal **hari kerja satu developer**; belum termasuk antrean keputusan, migrasi staging, dan UAT operasional.

## 2. Aturan kompatibilitas wajib

- Dashboard utama **LOCKED**. Jangan ubah layout, visual, atau perilaku dashboard sebagai bagian task ini.
- `stock_movements` tetap menjadi satu-satunya ledger saldo. Jangan menambahkan tabel saldo kedua atau mengubah saldo lama secara langsung.
- Pertahankan route, model, tabel, menu, alur Stock In/Out, Stock Opname, transfer parsial, purchasing, dan permission yang masih digunakan.
- Tambahkan field/tabel/status secara additive; untuk data lama gunakan nilai nullable atau status `legacy/belum diketahui`, jangan mengarang sejarah.
- Jangan hard-delete transaksi. Koreksi stok/pembayaran menggunakan reversal atau transaksi koreksi dengan referensi ke transaksi awal.
- Perubahan permission hanya untuk permission terkait task. Pastikan cek dilakukan di backend, bukan hanya menyembunyikan tombol.
- Jangan menjalankan migration, seeder, deployment, atau pengiriman WhatsApp ke operasional sebagai bagian checklist coding tanpa rencana rilis terpisah.
- Audit awal menemukan migration tanggal 2026-10-15 Pending. Pada 2026-10-15, sebelas migration awal terkait fitur 1, 2, 3, 4, 7, dan 10 serta migration ke-12 untuk akses opname PIC berhasil dijalankan pada database lokal `inventaris`; migration resolusi discrepancy memakai nama foreign key MySQL yang eksplisit agar sesuai batas panjang identifier. Status staging/production tetap perlu dicek sebelum rollout.
- Pecah pengerjaan menjadi PR/commit kecil per fitur; sertakan rencana backout dan hasil UAT.

## 3. Keputusan proses

### 3.1 Telah dikonfirmasi pemilik proses

- [x] Purchasing memasukkan harga barang pada invoice; harga invoice menjadi sumber harga pembelian.
- [x] Metode valuasi operasional adalah **moving weighted average per produk-lokasi**.
- [x] Barang boleh diterima dan menambah kuantitas ledger sebelum invoice tersedia/disetujui, dengan riwayat receipt dan invoice-link yang jelas.
- [x] Finance memerlukan pencatatan status pembayaran terpisah dari status review invoice.
- [x] Staff Gudang boleh mencatat Stock Out dari outlet karena petugas mengetahui barang yang dikeluarkan.
- [x] Setiap PIC bertanggung jawab pada satu outlet.
- [x] Finance membutuhkan laporan nilai persediaan dalam Rupiah.
- [x] Sistem perlu mencatat barang expired/rusak/hilang/retur dan mengirim alert stok minimum ke PIC melalui WhatsApp.
- [x] Transfer parsial, kategori penolakan, dan rekomendasi opname perlu ditangani secara realistis.

### 3.2 Rekomendasi operasional untuk disetujui

| Topik | Rekomendasi | Alasan dan batas |
|---|---|---|
| Purchase Request | Pertahankan PR yang sudah ada sebagai bukti kebutuhan; PO menjadi komitmen ke supplier. Pertahankan perilaku auto-approve saat ini sampai otorisasi budget dipastikan. | Jangan menambah approval PR baru tanpa kebutuhan kontrol budget yang jelas. Auto-approve perlu ditinjau karena sekarang belum menjadi kontrol persetujuan nyata. |
| Penerimaan supplier | Catat jumlah datang dan jumlah layak diterima saat pemeriksaan fisik di gudang. Hanya jumlah layak yang menambah saldo. Receipt PO tanpa invoice berstatus turunan **invoice pending** selama `purchase_invoice_id` kosong, lalu ditautkan Purchasing saat dokumen tersedia. | Ini status kelengkapan dokumen, terpisah dari status Stock In. Stock tidak tertahan menunggu administrasi invoice; barang ditolak/rusak tetap terlacak tetapi tidak menjadi saldo tersedia. |
| Faktur dan harga | Invoice memuat item, kuantitas, harga satuan, diskon, pajak, ongkir/biaya, dan total dokumen secara terpisah. Finance melakukan pencocokan dengan PO dan seluruh receipt terkait. | Item subtotal saja belum tentu sama dengan total tagihan. Sistem perlu menandai selisih dan merekam alasan Finance menerima pengecualian. |
| Biaya untuk valuasi | WAC memakai harga faktur setelah diskon dokumen dialokasikan proporsional ke item. Tampilkan pajak/ongkir/biaya lain terpisah sebagai caveat; jangan masukkan ke biaya persediaan sampai Finance menetapkan pemetaannya. | Pajak dapat dikreditkan dan perlakuan biaya angkut/biaya lain bergantung kebijakan akuntansi; tidak boleh dikapitalisasi diam-diam. |
| Pembayaran invoice | Catat pembayaran aktual parsial atau penuh dengan tanggal, jumlah, metode, referensi transfer/bukti, pencatat, serta saldo tersisa. Izinkan setelah invoice accepted. Pembetulan dilakukan dengan reversal beralasan. | Status invoice (`draft/submitted/accepted/rejected/revision`) dan status pembayaran (`unpaid/partial/paid`) adalah dua hal berbeda. |
| Transfer gudang → outlet | Saat dispatch, stok sumber berkurang dan sisa kuantitas dihitung sebagai persediaan perusahaan dalam perjalanan, terpisah dari stok on-hand outlet/gudang. PIC mencatat penerimaan parsial berulang; hanya jumlah yang tiba layak yang menambah saldo outlet. Penerimaan final yang kurang membuka discrepancy sampai PIC menerima sisanya atau Purchasing mencatat keputusan hilang/rusak/lainnya dengan jumlah dan alasan. Nilai in-transit mengikuti biaya WAC sumber saat dispatch. | Jangan menutup discrepancy dengan alasan bebas saja atau menghilangkan nilai dari persediaan sebelum ada penerimaan/keputusan terdokumentasi. Status transit adalah register turunan dari transfer, bukan ledger saldo kedua. |
| Stock Out outlet | Staff dengan permission `stock.manage` mencatat lokasi sumber, tujuan, referensi, tanggal, item, jumlah, dan kategori. PIC tetap terbatas ke outlet penugasannya dan tidak diberi izin Stock Out secara default. Kategori `sale`, `internal_use`, `sample`, dan `other`; `other` mewajibkan penjelasan. | Distribusi ke lokasi lain harus memakai Stock Transfer. Permission Staff yang sekarang berlaku lintas lokasi aktif dipertahankan agar tidak memutus tugas yang sudah berjalan; pemilik proses masih perlu mengonfirmasi apakah kelak perlu pembatasan Staff per lokasi. |
| Stock Opname | Cycle count mingguan bergilir menurut risiko/ABC; full count akhir bulan. Petugas menghitung, approver berbeda memeriksa selisih. Sesi dapat parsial per kategori/batch/lokasi. | Periode weekly/month-end saja belum berarti ada jadwal otomatis. Perlu prosedur menangani transaksi yang terjadi ketika hitung berlangsung. |
| Batch dan rotasi | Gunakan FEFO untuk barang yang memiliki expiry; bila expiry sama/tidak tersedia, gunakan FIFO fisik berdasarkan tanggal penerimaan. Catat batch/expiry untuk barang yang memerlukannya. | FEFO/FIFO adalah rotasi fisik, bukan metode valuasi akuntansi. Alert expiry terpisah dari alert stok minimum. |
| Expired/rusak/retur | Tandai barang sebagai hold/quarantine agar tidak bisa dipilih Stock Out normal. Staff mengajukan disposal/retur; approver independen menyetujui. Retur perlu referensi invoice/receipt/batch dan hasil supplier (credit note, replacement, atau ditolak). | Mengurangi saldo tanpa mencatat hasil klaim supplier menghilangkan informasi nilai/utang yang perlu ditindaklanjuti Finance. |
| PIC pengganti dan alert | Satu PIC aktif per outlet. Admin menunjuk pengganti sementara dengan tanggal berlaku dan audit. Jika belum ada nomor PIC, alert aplikasi tetap ke PIC yang ditugaskan dan Staff Gudang; kirim WhatsApp hanya ke PIC tujuan yang valid serta fallback operasional yang disetujui. | Hindari menyebarkan data outlet ke seluruh nomor global tanpa kebijakan penerima yang eksplisit. |
| Laporan | Finance: Rupiah per SKU/lokasi, harga rata-rata, status confirmed/provisional/incomplete, receipt dan invoice asal, pembayaran. Gudang/PIC: saldo, batch/expiry, movement, discrepancy, low stock. Management: ringkasan stok, risiko, dan nilai yang sudah diberi caveat. | Jangan menyatukan provisional dengan nilai buku final. Export harus mengikuti filter dan scope role yang sama seperti halaman. |

### 3.2.1 SOP ringkas penerimaan supplier di gudang

1. Cocokkan PO, supplier, produk, satuan, dan gudang tujuan sebelum membongkar barang.
2. Hitung jumlah fisik datang per item; pisahkan barang rusak/tidak layak dan catat jumlah layak yang benar-benar masuk stok.
3. Catat batch dan tanggal kedaluwarsa jika tercantum/berlaku. Jangan mengarang data batch yang tidak ada pada kemasan.
4. Setiap selisih jumlah, kondisi, PO, atau faktur harus diberi catatan pada baris item. Barang rusak/ditolak tidak masuk saldo.
5. Jangan terima kelebihan dari PO ke saldo hanya atas keputusan penerima; minta Purchasing mengonfirmasi, lalu catat nama pemberi persetujuan dan waktu pada catatan selisih.
6. Receipt boleh disimpan tanpa faktur; layar menandainya sebagai invoice pending. Purchasing menautkan dokumen setelah tersedia, tanpa membuat Stock In/movement baru.
7. Setelah submit, gunakan bukti Stock In yang sama jika halaman mengulang karena jaringan. Receipt baru untuk kedatangan lain harus dimulai dari halaman yang dimuat ulang agar memakai kunci submit baru.

### 3.3 Keputusan yang masih perlu pemilik proses

- [ ] Apakah barang supplier boleh dikirim langsung ke outlet; bila ya, siapa memeriksa dan apakah selalu melalui PIC approval?
- [ ] Apakah Staff Gudang menangani seluruh lokasi atau hanya lokasi/shift yang ditugaskan?
- [ ] Apakah operasi memiliki beberapa gudang dan perlu transfer gudang-ke-gudang?
- [ ] Siapa PIC pengganti, siapa yang mengaktifkan, dan berapa lama assignment berlaku?
- [ ] Biaya invoice mana yang Finance masukkan ke biaya perolehan, serta bagaimana pajak yang dapat dikreditkan diperlakukan?
- [ ] Siapa yang mengesahkan discrepancy transfer dan bukti penyelesaian untuk hilang/rusak/retur?
- [ ] Apakah opname perlu approval berbasis ambang nilai/kuantitas, selain pemisahan petugas dan approver?
- [ ] Siapa penerima WhatsApp fallback dan siapa yang mengesahkan akun/provider aktif?
- [ ] Periode tutup nilai Finance dan perlakuan transaksi backdated setelah periode ditutup?

## 4. Baseline audit dan risiko implementasi

- [x] Enam role ada pada `RolePermissionSeeder`; konfigurasi role dapat diedit Super Admin sehingga seeder bukan bukti permission akun aktif.
- [x] Ledger `stock_movements`, Stock In/Out, FEFO batch, transfer parsial, opname maker-checker, PR/PO, invoice, laporan, dan audit log ditemukan di source.
- [x] PIC dibatasi ke satu `location_id`; beberapa endpoint memaksa scope lokasi tersebut.
- [x] Baseline sebelum fitur 1: `php artisan migrate:status` membaca migration sampai `2026_10_14_000009` berstatus Ran; tujuh migration `2026_10_15_000001` s.d. `000007` Pending. Fitur 1/2 menambahkan migration `000008` s.d. `000010`, yang masih Pending pada database lokal.
- [x] Suite target fitur 1/2: 25 test, 184 assertion lulus. Seluruh suite: 55 test lulus dan 3 gagal di `DashboardFeatureTest` (ekspektasi dashboard role/admin dan badge keamanan login). Area Dashboard tetap tidak diubah sesuai aturan LOCKED; kegagalan ini dicatat untuk tindak lanjut terpisah.

**Risiko schema/source:** code saat ini telah mereferensikan tabel/kolom yang termasuk migration Pending. Sebelum rollout, pastikan migration terpasang sesuai urutan dan jalankan smoke test jalur lama serta baru pada staging. Hindari membuka halaman invoice, Stock Out, approval Stock In, transaction history, dan WhatsApp baru pada database yang schema-nya belum cocok.

## 5. Checklist per fitur

### Fitur 0 — Stabilitas, schema, dan akses

**Tujuan:** memastikan code, schema, role, dan alur lama selaras sebelum menambah pemakaian operasional. **Role:** Super Admin, Developer, seluruh role UAT. **Prioritas:** P0. **Estimasi:** 3–5 hari. **Dampak stok:** tidak mengubah saldo; validasi saja.

- [x] Inventarisasi route, service, migration, permission baseline, dan fitur yang sudah ada.
- [ ] Cocokkan sebelas migration Pending dengan schema, urutan dependency, backup, dan rollback pada staging.
- [ ] Periksa seluruh halaman/route yang sekarang memakai tabel/kolom hasil migration Pending.
- [ ] Verifikasi role-permission aktual pada environment; jangan mengandalkan seeder saja.
- [ ] Jalankan suite test dan perbarui assertion/payload yang sudah berubah.
- [ ] Smoke test route lama: Stock In gudang/outlet, Stock Out, transfer, opname, report, history, dan login.
- [ ] Buat catatan saldo sebelum/sesudah untuk transaksi contoh; buktikan tidak ada duplicate movement.

**Kriteria selesai:** schema/source cocok; jalur lama tidak mengalami regression; tiap role hanya mengakses fitur dan data yang diizinkan; UAT staging ditandatangani role terkait.

### Fitur 1 — Supplier, PR, PO, dan penerimaan gudang

**Tujuan:** merekam kebutuhan, komitmen pembelian, barang fisik, dan selisih. **Role:** Purchasing, Staff Gudang, Finance. **Prioritas:** P0/P1. **Estimasi:** 3–6 hari. **Dampak stok:** accepted quantity receipt membuat movement in satu kali.

- [x] Pertahankan PR/PO yang telah ada dan PO yang menargetkan gudang.
- [x] Pertahankan PR auto-approve untuk saat ini; perubahan approval budget ditunda sampai pemilik budget menetapkan batas dan approver. Jangan membuat approval baru tanpa keputusan tersebut.
- [x] Tetapkan SOP hitung datang, layak diterima, batch, expiry, barang lebih/kurang/rusak; kelebihan PO hanya masuk saldo setelah Purchasing menyetujui dan persetujuan dicatat.
- [x] Receipt invoice-pending dapat ditautkan ke PO/invoice yang tepat tanpa membuat movement baru.
- [x] Cegah retry receipt ganda dengan idempotency key per halaman submit, ditambah unique movement per baris receipt.
- [x] Uji fitur otomatis lokal: receipt penuh/parsial, lebih PO dengan persetujuan tercatat, barang rusak ditolak tanpa saldo, invoice ditautkan tanpa movement baru, serta retry submit tanpa receipt/movement ganda.
- [ ] UAT manual per role pada staging sebelum rollout; cek form gudang, otorisasi Purchasing untuk kelebihan PO, saldo sebelum/sesudah, dan bukti audit.

**Kriteria implementasi selesai:** skenario receipt dan integrasi fitur otomatis lulus; saldo hanya bertambah sebesar kuantitas layak diterima; sisa PO dan discrepancy dapat dijelaskan; link invoice tidak menduplikasi saldo. **Gate rollout:** checklist UAT manual staging tetap harus ditandatangani sebelum fitur dipakai operasional.

### Fitur 2 — Invoice, pencocokan tiga arah, dan pembayaran

**Tujuan:** menjaga kecocokan PO, receipt, invoice, dan pelunasan. **Role:** Purchasing, Finance, Staff Gudang terbatas untuk bukti gudang. **Prioritas:** P0. **Estimasi:** 4–8 hari. **Dampak stok:** review harga dapat mengubah snapshot valuasi, bukan kuantitas ledger.

- [x] Pertahankan invoice number unik per supplier, file dokumen privat, item harga/kuantitas, status review, dan audit perubahan.
- [x] Cocokkan PO, seluruh receipt pada PO, dan invoice per item; tampilkan selisih kuantitas/harga serta ringkasan total.
- [x] Simpan diskon, pajak, ongkir, dan biaya lain terpisah; total dokumen harus cocok dalam toleransi Rp0,01 atau memiliki alasan, lalu Finance mencatat keputusan atas selisih.
- [x] Wajibkan kategori dan alasan saat Finance meminta revisi/menolak; pertahankan riwayat review.
- [x] Tautkan invoice-pending receipt hanya jika receipt approved dan supplier/PO sama, tanpa membuat movement baru.
- [x] Catat pembayaran parsial/penuh hanya untuk invoice accepted; sistem menolak overpayment.
- [x] Catat reversal sebagai entri kontra yang merujuk pembayaran asli dengan alasan, actor, dan audit; jangan hapus transaksi lama.
- [x] Jalankan suite fitur lokal: invoice duplicate, selisih receipt, koreksi harga, rekonsiliasi komponen, payment partial/full, overpayment, dan reversal.
- [ ] UAT Finance/Purchasing di staging; Finance menyetujui perlakuan pajak dan biaya yang boleh dikapitalisasi sebelum valuasi dipakai sebagai nilai buku resmi.

**Kriteria implementasi selesai:** total faktur dapat direkonsiliasi; status invoice terpisah dari receipt/payment; net payment tidak melebihi saldo; koreksi dan reversal tercatat. **Gate rollout:** UAT Finance/Purchasing dan persetujuan kapitalisasi pajak/biaya tetap diperlukan sebelum migration dipasang dan nilai dipakai sebagai angka resmi.

### Fitur 3 — Stock Out outlet dan tujuan penggunaan

**Tujuan:** pencatatan pengeluaran outlet dapat ditelusuri tanpa menggantikan transfer. **Role:** Staff Gudang; PIC sesuai keputusan tambahan. **Prioritas:** P0/P1. **Estimasi:** 2–4 hari. **Dampak stok:** movement out dari lokasi yang dipilih.

- [x] Pertahankan kemampuan Staff mencatat Stock Out pada lokasi outlet sesuai kebutuhan yang dikonfirmasi.
- [x] Pertahankan permission `stock.manage` Staff yang sudah berlaku lintas lokasi aktif; PIC yang memiliki assignment lokasi dipaksa hanya mencatat pada outletnya. Konfirmasi pembatasan Staff per lokasi tetap menjadi keputusan rollout.
- [x] Wajibkan kategori `sale`, `internal_use`, `sample`, atau `other` untuk transaksi baru; kategori `other` mewajibkan penjelasan pada catatan. Data lama tanpa kategori tampil sebagai belum diketahui.
- [x] Tolak tujuan Stock Out yang sama dengan nama/kode lokasi aktif; distribusi antar lokasi diarahkan ke workflow Stock Transfer.
- [x] Tampilkan kategori di form, daftar/detail Stock Out, riwayat transaksi, dan export laporan Stock Out; jangan mengarang kategori untuk transaksi lama.
- [x] Tambahkan tes otomatis kategori wajib, alasan `other`, pemisahan transfer, ledger movement, audit, detail, riwayat, dan laporan.
- [ ] UAT saldo, FEFO, lokasi sumber, tujuan, audit, dan izin role pada staging.

**Kriteria implementasi selesai:** transaksi baru punya referensi, lokasi, tujuan, kategori, actor, waktu, alasan untuk kategori `other`, dan movement yang cocok; transaksi lama tetap dapat ditelusuri dengan kategori belum diketahui. Koreksi transaksi tidak dilakukan dengan mengedit/menghapus Stock Out; gunakan transaksi koreksi/adjustment yang berizin serta beralasan. **Gate rollout:** UAT saldo/FEFO dan konfirmasi cakupan lokasi Staff masih perlu dilakukan pada staging.

### Fitur 4 — Distribusi gudang ke outlet dan discrepancy

**Tujuan:** barang tidak hilang dari kontrol antara dispatch dan penerimaan. **Role:** PIC Outlet, Staff Gudang, Purchasing untuk discrepancy. **Prioritas:** P0. **Estimasi:** 3–6 hari. **Dampak stok:** out di sumber saat dispatch; in di tujuan sebesar penerimaan aktual.

- [x] Pertahankan permintaan, picking, dispatch, penerimaan parsial, dan riwayat receipt transfer yang sudah ada.
- [x] Tetapkan sisa dispatch sebagai persediaan perusahaan dalam perjalanan, terpisah dari on-hand lokasi; nilai transfer membawa WAC sumber ke baris in-transit Finance.
- [x] Penerimaan final yang kurang membuat `discrepancy_open`; teks alasan saja tidak menutupnya. Penerimaan berikutnya tetap dapat dicatat.
- [x] Tambahkan resolusi parsial/penuh bertipe hilang, rusak, atau lainnya oleh Purchasing/Super Admin, dengan jumlah, alasan, actor, waktu, dan audit; resolusi tidak membuat movement stok ganda.
- [x] Transfer gudang-ke-gudang tidak ditambahkan pada scope ini; workflow yang tersedia tetap gudang-ke-outlet sampai proses bisnis multi-gudang disetujui.
- [x] Tambahkan tes otomatis untuk penerimaan parsial, final discrepancy, resolusi parsial, late receipt, saldo movement, permission Purchasing, dan nilai/kuantitas transit.
- [ ] UAT parsial beberapa kali, final kurang, penerimaan penuh, approval PIC, resolusi, dan retry pada staging.

**Kriteria implementasi selesai:** dispatch = diterima + dalam perjalanan + discrepancy terbuka + selisih hilang/rusak yang telah diputuskan; setiap jumlah hanya diposting sekali. Resolusi hilang/rusak menutup sisa persediaan transit setelah keputusan Purchasing tercatat. **Gate rollout:** UAT staging dan kecocokan migration kesebelas tetap diperlukan.

### Fitur 5 — Stock Opname dan Adjustment

**Tujuan:** koreksi saldo berdasarkan hitung fisik yang dapat diaudit. **Role:** Staff Gudang/PIC sebagai penghitung; Purchasing atau checker yang ditunjuk sebagai approver; Super Admin untuk emergency sesuai SOP. **Prioritas:** P0/P1. **Estimasi:** 4–7 hari. **Dampak stok:** adjustment movement hanya saat hasil disetujui.

- [x] Pertahankan sesi weekly/month-end, reason variance, movement cutoff, dan maker-checker yang ada.
- [x] Beri PIC permission opname.manage untuk menghitung hanya outlet yang ditugaskan; pemeriksaan scope diterapkan pada daftar, buat, detail, dan submit.
- [x] Tetapkan rekomendasi cycle count parsial mingguan dan full count semua produk akhir bulan; pilih produk mingguan secara manual berdasar nilai/pergerakan tinggi, expiry dekat, dan riwayat selisih. ABC otomatis ditunda sampai data mendukung klasifikasi yang konsisten.
- [x] Kunci pergerakan stok untuk produk yang sedang dihitung sampai hasil dikirim; setelah submit, cutoff movement yang ada tetap mengakomodasi transaksi baru sebelum approval.
- [x] Pertahankan maker-checker untuk variance Stock Opname: penghitung tidak menyetujui sesi sendiri dan Purchasing meninjau seluruh hasil. Adjustment manual darurat tetap hanya untuk Super Admin, wajib beralasan dan diaudit; ambang eskalasi nilai belum ditambah.
- [x] Rekomendasikan pengingat kalender manual mingguan dan akhir bulan; Super Admin memelihara jadwal bersama dan pemilik lokasi mengonfirmasi hitung. Otomatisasi reminder ditunda.
- [ ] UAT count benar, selisih plus/minus, transaksi berjalan saat hitung, ditolak, dan persetujuan ulang.

**Kriteria selesai:** perubahan fisik konsisten dengan movement cutoff; koreksi punya alasan dan approver; jadwal, lokasi, serta kelompok yang dihitung diketahui.

### Fitur 6 — Batch, FEFO, expiry, dan karantina

**Tujuan:** rotasi fisik aman dan batch bermasalah tidak ikut saldo tersedia. **Role:** Staff Gudang dan PIC Outlet. **Prioritas:** P0 untuk kategori barang ber-expiry. **Estimasi:** 3–6 hari. **Dampak stok:** karantina tetap terlacak, tetapi tidak available untuk issue normal.

- [x] Pertahankan batch, tanggal diterima, expiry, dan FEFO untuk batch terlacak.
- [x] Tetapkan aturan per SKU: hanya produk yang ditandai “Wajib nomor batch & expiry” wajib mengisi keduanya; default nonaktif untuk kompatibilitas data lama.
- [x] Gunakan alert laporan expiry 30 hari (aman/segera kedaluwarsa/kedaluwarsa); rekomendasi awal operasional adalah cek harian pada 30 hari dan naikkan prioritas pada 7 hari. WhatsApp expiry belum dikirim otomatis.
- [x] Bedakan saldo fisik, jumlah karantina, dan saldo tersedia pada laporan stok; laporan expiry memberi rincian sama per batch tanpa menggandakan ledger.
- [x] Pending disposal/retur mengarantina jumlah yang diajukan; Stock Out/transfer yang memakai Stock Out tidak dapat mengambilnya. Batch expired tetap terlihat sebagai saldo fisik, tetapi dilewati oleh FEFO normal. Tidak ada override expired/quarantine pada jalur Stock Out normal.
- [ ] UAT FEFO, batch tanpa expiry, batch kedaluwarsa, transfer lot, dan expiry alert.

**Aturan implementasi Fitur 6:** karantina adalah hold dari disposal pending, bukan movement kuantitas kedua. Hold dilepas otomatis jika pengajuan ditolak; saat disetujui, satu movement out dibuat lalu status berubah approved. Alert mendekati expiry memakai laporan expiry yang sudah tersedia agar Dashboard LOCKED dan kanal WhatsApp low-stock tidak diubah.

**Kriteria selesai:** barang tidak layak tidak terambil; batch dan lokasi dapat ditelusuri sampai disposal/return.

### Fitur 7 — Write-off, retur supplier, dan klaim

**Tujuan:** barang hilang/rusak/expired dan retur supplier dicatat sebagai alur resmi. **Role:** Staff Gudang mengajukan; Purchasing menyetujui retur; Finance memonitor nilai/kredit; approver tidak boleh maker. **Prioritas:** P0. **Estimasi:** 3–5 hari. **Dampak stok:** out movement setelah approval; karantina sebelum itu.

- [x] Pertahankan pengajuan dan approval independen yang telah disiapkan.
- [x] Verifikasi tabel/migration/permission disposal dan lindungi endpoint dengan permission backend; Finance mendapat akses lihat klaim, Purchasing/Finance dapat mencatat penyelesaian.
- [x] Lengkapi status karantina sebelum approval dan cegah item dikeluarkan melalui jalur biasa (implementasi hold diselesaikan bersama Fitur 6).
- [x] Batasi retur pada invoice accepted, receipt approved yang sudah tertaut ke invoice, batch/SKU/lokasi/supplier/PO yang sama, dan kuantitas receipt yang belum dicadangkan pada retur pending/approved.
- [x] Catat hasil supplier secara append-only: credit note/refund dengan nominal dan bukti, penggantian dengan receipt Stock In approved, atau klaim ditolak dengan alasan/bukti. Jumlah dapat diselesaikan bertahap dan tidak boleh melebihi klaim.
- [ ] UAT expired, rusak, hilang tanpa batch diketahui, retur sebagian, stok berubah sebelum approval, dan maker-checker.

**Aturan implementasi Fitur 7:** hanya approval retur/write-off menulis satu movement out. Pengajuan pending menahan available quantity; penolakan melepas hold. Receipt pengganti tetap transaksi Stock In independen dan ditautkan sebagai bukti hasil klaim, tanpa membuat movement duplikat dari catatan klaim.

**Kriteria selesai:** tidak ada penghapusan transaksi; perubahan saldo, barang fisik, dan klaim supplier dapat direkonsiliasi.

### Fitur 8 — Valuasi WAC dan laporan Finance

**Tujuan:** laporan Rupiah per produk-lokasi dapat direkonsiliasi dan tidak menyesatkan. **Role:** Finance, Management (baca/export), Super Admin. **Prioritas:** P0 sebelum menggunakan sebagai nilai buku. **Dampak stok:** kuantitas tidak berubah; perubahan penerimaan/review harga dapat mengubah snapshot valuasi.

**Keputusan implementasi yang dipakai:**

- Replay WAC memakai waktu posting dokumen transaksi (`created_at` Stock In/Out, Opname, atau Adjustment), lalu ID movement sebagai tie-break deterministik. Tanggal operasional `received_at`/`issued_at` tetap disimpan untuk riwayat kerja, tetapi tidak mengurutkan ulang biaya secara backdated.
- Laporan adalah snapshot operasional saat dibuka, bukan laporan historis/cutoff dan bukan nilai buku penutupan periode. Posting koreksi kemudian mengubah snapshot saat ini; periode lama belum dapat direkonstruksi.
- Harga unit invoice Purchasing menjadi sumber biaya. Diskon dokumen dialokasikan proporsional terhadap subtotal item; pajak, ongkir, biaya lain dan selisih total dokumen ditampilkan terpisah, tidak otomatis dikapitalisasi.
- Jika ada kuantitas tanpa sumber harga, biaya belum dialokasikan, atau selisih dokumen, baris berstatus `incomplete`; nilai Rupiah yang tampil hanya subtotal komponen yang dapat dihitung.
- Penerimaan tanpa invoice tetap menambah ledger kuantitas dan tampil tanpa nilai sampai invoice ditautkan. Transfer membawa WAC dan status sumber biaya lokasi asal.

- [x] Gunakan moving weighted average per produk-lokasi sesuai keputusan pemilik.
- [x] Bedakan nilai confirmed, provisional, dan incomplete; harga unit invoice Purchasing adalah sumber harga.
- [x] Tentukan urutan replay dan batas tanggal: posting timestamp + ID movement; hanya snapshot terkini, tidak ada cutoff historis.
- [x] Tetapkan perlakuan diskon, pajak, ongkir/biaya lain dan mismatch: diskon proporsional; komponen lain diekspos, tidak dikapitalisasi, status incomplete.
- [x] Verifikasi transfer mempertahankan WAC biaya sumber; nilai in-transit dan discrepancy terbuka ditampilkan di baris transit Finance.
- [x] Sediakan laporan dan CSV filter per lokasi/produk, saldo, WAC, nilai Rupiah, sumber invoice/receipt, biaya belum dialokasikan, selisih dokumen, dan status.
- [ ] UAT dengan spreadsheet Finance yang disepakati dan rekonsiliasi receipt/issue/transfer/invoice.

**Kriteria selesai:** implementasi dan export tersedia; angka dapat direproduksi dari ledger untuk transaksi yang didukung; subtotal yang belum lengkap/provisional selalu diberi label. UAT Finance tetap menjadi gerbang sebelum angka dipakai sebagai nilai buku resmi.
### Fitur 9 — Role, scope lokasi, PIC pengganti, dan audit

**Tujuan:** data dan aksi hanya tersedia untuk petugas yang tepat serta dapat ditelusuri. **Role:** seluruh role, Super Admin mengelola assignment. **Prioritas:** P0. **Estimasi:** 3–6 hari. **Dampak stok:** hanya pengguna sah dapat menulis movement.

- [x] Pertahankan enam role, backend permission checks, single-outlet PIC utama, audit log, dan scope PIC pada endpoint yang sudah ada.
- [x] Audit dan perketat scope outlet langsung untuk laporan, riwayat movement/transaksi, stok, penerimaan, transfer, disposal, dan pencarian. PIC tidak dapat membaca outlet lain dengan mengubah ID URL/filter; akun tanpa assignment ditolak.
- [x] Tambahkan assignment PIC pengganti yang hanya efektif pada rentang waktu, satu pengganti aktif per outlet, tanpa jadwal tumpang tindih untuk PIC/outlet, dapat dicabut dengan alasan, dan seluruh perubahan masuk audit log.
- [x] Perubahan role/permission, profil, status aktif, dan assignment mencatat actor serta before/after yang relevan.
- [ ] UAT seluruh role untuk view/create/edit/submit/approve/reject/export dan akses lintas outlet.

**Keputusan assignment:** akun pengganti tetap harus memiliki role `pic_outlet`; assignment hanya menambah akses outlet selama jadwalnya. PIC utama tetap menjadi PIC utama dan satu PIC tidak dapat ditugaskan menggantikan dua outlet secara bersamaan. Perubahan atau pencabutan role PIC utama langsung menghentikan scope assignment yang terkait.

**Kriteria implementasi:** direct URL dan request backend memakai scope assignment efektif; perubahan assignment dapat ditelusuri. UAT lintas role/outlet masih menjadi gerbang sebelum rollout.

### Fitur 10 — Alert stok minimum dan WhatsApp

**Tujuan:** alert tepat kepada pemilik lokasi dengan delivery yang dapat dipantau. **Role:** PIC, Staff Gudang, Super Admin; Super Admin mengelola konfigurasi/provider. **Prioritas:** P1. **Estimasi:** 2–4 hari. **Dampak stok:** tidak mengubah saldo.

- [x] Pertahankan alert aplikasi dan low-stock threshold per produk-lokasi.
- [x] Nomor WhatsApp PIC outlet dikirim otomatis hanya untuk lokasi assignment aktif; admin dapat mengatur nomor tambahan per lokasi. Daftar global lama tidak dirutekan dan ditampilkan untuk dipetakan ulang. Schema additive: `location_recipients` (migration `2026_11_04_000001`); migration dijalankan pada DB lokal `inventaris`.
- [ ] UAT pastikan pesan outlet hanya ke PIC yang ditugaskan dan nomor tambahan pada lokasi yang sama; gudang memakai nomor khusus lokasi.
- [ ] Tambahkan retry terbatas/backoff, idempotency, status final, dan cara reprocess alert gagal.
- [x] Queue worker database dan WhatsApp service dijaga oleh watchdog Windows, dijalankan saat user login, dan otomatis direstart jika proses mati atau status WhatsApp lama tidak pulih (`whatsapp-service/watchdog.ps1`).
- [ ] UAT nomor tidak valid, provider offline, retry, alert duplikat, PIC berubah, dan kebijakan enable/disable.

**Kriteria selesai:** hanya penerima berotorisasi yang menerima data; kegagalan dapat terlihat dan ditangani tanpa pesan ganda.

### Fitur 11 — AI chatbot inventory (ditunda)

**Tujuan:** menjawab pertanyaan inventory tanpa mengubah transaksi. **Role:** sesuai authorization data pengguna. **Prioritas:** P2, setelah P0 stabil. **Estimasi:** discovery/POC 5–10 hari setelah service data siap. **Dampak stok:** read-only.

- [ ] Pastikan saldo, historis, scope outlet, dan definisi laporan sudah disetujui.
- [ ] Definisikan pertanyaan v1: saldo per lokasi, low stock, expiry, status PO/transfer, ringkasan historis yang telah diizinkan.
- [ ] Sediakan backend service/tool berizin; model tidak boleh membuka koneksi database langsung.
- [ ] Integrasikan Gemini hanya melalui backend, simpan secret di environment/server, dan batasi domain.
- [ ] Tolak mutasi stok/approval, prompt injection, pertanyaan di luar inventory, serta angka tanpa sumber.
- [ ] UAT authorization lintas role/outlet, jawaban tidak tersedia, kegagalan provider, dan audit permintaan.

**Kriteria selesai:** chatbot hanya menjawab data terotorisasi dengan sumber/waktu data; tidak pernah mengubah stok atau menyetujui transaksi.

## 6. Roadmap rilis berdasarkan dependensi

1. **Stabilitas dan kontrol data (P0, 3–5 hari):** schema/source, test, permission scope, backup staging. Output: smoke test dan saldo rekonsiliasi.
2. **Workflow inventory (P0, 6–10 hari):** penerimaan, transfer discrepancy/in-transit, Stock Out, opname/adjustment, batch/quarantine. Output: UAT Staff Gudang/PIC.
3. **Purchasing dan Finance (P0, 4–8 hari):** invoice matching, komponen biaya, payment/reversal, WAC timestamp. Output: rekonsiliasi Rupiah Finance.
4. **Laporan dan auditability (P1, 3–5 hari):** scope report, export, expiry/disposal/history. Output: laporan sample disetujui tiap role.
5. **Alert operasional (P1, 2–4 hari):** PIC assignment, provider queue/retry. Output: hasil delivery terverifikasi dalam mode UAT.
6. **AI chatbot (P2, 5–10 hari):** hanya setelah sumber inventory dan authorization stabil. Output: POC read-only per role.

## 7. UAT checklist minimum sebelum operasional

- [ ] Staff Gudang menerima barang lengkap, parsial, lebih, rusak, dan receipt tanpa invoice.
- [ ] Purchasing menautkan receipt ke invoice supplier/PO yang benar dan tidak dapat menautkan silang.
- [ ] Finance menolak/minta revisi dengan kategori; menerima invoice; mencatat partial/full payment; menolak overpayment.
- [ ] WAC setelah receipt, issue, transfer, invoice diterima/revisi, dan transaksi backdated cocok dengan contoh Finance.
- [ ] Staff melakukan Stock Out pada outlet sesuai kebijakan lokasi yang disetujui; transfer tetap memiliki dispatch dan receipt.
- [ ] PIC outlet yang benar menerima transfer; PIC outlet lain mendapat 403; partial receipt dan discrepancy terjaga.
- [ ] Opname maker tidak bisa menyetujui hasil sendiri; movement concurrent tidak menggandakan variance.
- [ ] Batch expired/quarantine tidak bisa dikeluarkan normal; write-off/retur tercatat sampai penyelesaian supplier.
- [ ] Semua transaksi menampilkan actor, referensi, tanggal, status, lokasi, alasan, dan link audit yang sesuai.
- [ ] Management, Finance, Purchasing, Staff Gudang, dan PIC hanya mengekspor laporan dalam scope aksesnya.
- [ ] Alert WhatsApp diuji tanpa mengirim ke nomor operasional yang belum menyetujui UAT.

## 8. Catatan implementasi untuk AI/developer berikutnya

- Sebelum mengerjakan task, baca roadmap utama dan task plan incremental; periksa lagi status DB/migration pada environment tujuan.
- Pilih satu fitur dan satu set acceptance criteria; jangan menandai task selesai sebelum service, permission, migration, history/report, test, dan UAT terkait beres.
- Jika keputusan bisnis belum ada, implementasikan hanya bagian independen dan tinggalkan task inti sebagai `BLOCKED: BUSINESS DECISION`.
- Setelah fitur selesai, perbarui checkbox, bukti file/route/migration/test, tanggal verifikasi, dan risiko tersisa di dokumen ini.
- Jangan menjalankan atau mengubah Dashboard.
