# Panduan Memahami dan Mencoba Sistem Inventory Technolife

> **Untuk:** pengguna, tester, dan pemilik proses  
> **Versi:** 1.0  
> **Tanggal:** 17 November 2026  
> **Ruang lingkup:** proses bisnis, cara kerja sistem, serta skenario latihan manual di lingkungan lokal/staging.

## 1. Tujuan panduan

Dokumen ini membantu Anda memahami alur barang dan dokumen di aplikasi, lalu mencoba fitur dengan data uji yang aman. Panduan ini disusun dari route, service, seeder role, dan dokumen keputusan bisnis di repository. Ia bukan bukti bahwa seluruh alur sudah lulus UAT. Hasil akhir tetap perlu dicatat setelah percobaan manual.

Gunakan akun dan data khusus pengujian di lokal atau staging. Jangan mencoba skenario yang sengaja gagal, mengubah permission, menerima/menolak transaksi, atau mengirim alert WhatsApp pada data operasional tanpa rencana UAT dan persetujuan pemilik data.

## 2. Gambaran sederhana sistem

Aplikasi ini mencatat persediaan per **produk dan lokasi**. Lokasi dapat berupa gudang atau outlet. Transaksi seperti penerimaan, pengeluaran, penerimaan transfer, opname yang disetujui, dan koreksi membuat catatan pergerakan stok. Catatan tersebut tersimpan di `stock_movements` dan menjadi dasar saldo; saldo tidak seharusnya diedit langsung.

Secara sederhana:

```text
Produk + lokasi
      |
      +-- transaksi disetujui/diposting --> stock_movements --> saldo tersedia
      |
      +-- transaksi menunggu approval --> belum menambah/mengurangi saldo
      |
      +-- riwayat, audit, laporan, dan alert membaca catatan transaksi
```

Beberapa modul mempunyai dokumen proses sendiri (PO, transfer, opname, disposal), tetapi perubahan kuantitas tetap perlu dapat ditelusuri ke movement dan transaksi asalnya. Approval bukan sekadar tombol: izin dicek di server, dan beberapa transaksi menerapkan pemisahan pembuat dengan penyetuju.

## 3. Peran pengguna

Nama role yang disediakan aplikasi: **Super Admin, Staff Gudang, Staff Outlet, PIC Outlet, PIC Gudang, Purchasing, Finance, dan Management**. Hak akses aktual dapat berubah di menu role/permission, sehingga nama role saja tidak menjamin akun uji memiliki akses tertentu. Super Admin mengelola akun, role/permission, master data, assignment PIC pengganti, dan audit administrasi.

| Peran        | Peran operasional utama                                                                                                         | Contoh pengujian                                                                           |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| Super Admin  | Administrasi pengguna, permission, master data, pengaturan, dan audit; memiliki akses operasional luas sesuai permission aktif  | Memeriksa akun uji, master lokasi/produk, dan audit log                                    |
| Staff Gudang | Mencatat penerimaan/pengeluaran gudang, menyiapkan proses transfer, serta mengerjakan opname/disposal sesuai izin               | Penerimaan PO, Stock Out, picking/dispatch transfer, hitung stok                           |
| Staff Outlet | Membantu proses outlet dan opname sesuai scope/permission yang aktif                                                            | Mengisi hitungan opname outlet; uji akses lokasi yang ditugaskan                           |
| PIC Gudang   | Persetujuan transfer keluar dari gudang sesuai permission aktif                                                                 | Menyetujui transfer yang dibuat Staff Gudang                                               |
| PIC Outlet   | Bertanggung jawab pada outlet yang ditugaskan; menerima/menyetujui penerimaan tertentu dan dapat mengerjakan opname sesuai izin | Menerima kiriman parsial; mencoba akses outlet sendiri dan memastikan outlet lain tertutup |
| Purchasing   | Purchase Request/Order, tindak lanjut supplier, invoice, dan penyelesaian klaim supplier sesuai izin                            | Membuat PO, menautkan receipt ke invoice, uji mismatch dan follow-up                       |
| Finance      | Review invoice, pembayaran, dan laporan valuasi sesuai permission aktif                                                         | Review invoice dan catat pembayaran uji; bandingkan status pembayaran dengan status review |
| Management   | Membaca ringkasan dan laporan sesuai izin aktif                                                                                 | Membuka laporan tanpa melakukan transaksi stok                                             |

PIC Outlet dibatasi pada outlet yang ditugaskan oleh beberapa endpoint. Pengujian akses harus memastikan pembatasan dilakukan di server, bukan hanya lewat menu yang disembunyikan.

## 4. Proses bisnis inti

### 4.1 Data awal

Sebelum simulasi, siapkan produk aktif, satuan, kategori, supplier, satu gudang, dan satu outlet aktif. Pastikan user uji memiliki role, permission, dan lokasi yang sesuai. Gunakan kode produk dan referensi transaksi bertanda `UAT` atau `TEST` jika form mengizinkan.

### 4.2 Pembelian dan penerimaan barang supplier

1. Purchasing membuat **Purchase Request (PR)** untuk mencatat kebutuhan. PR berstatus **Menunggu Persetujuan** dan belum dapat dibuat menjadi PO.
2. Management meninjau item, jumlah, dan alasan kebutuhan di menu **Persetujuan**. Management dapat menyetujui atau menolak PR; keputusan dan catatan tersimpan di riwayat.
3. Jika disetujui, Purchasing membuka PR tersebut dan membuat **Purchase Order (PO)** kepada supplier. PR yang ditolak tidak dapat diterbitkan menjadi PO.
4. Staff Gudang membuka daftar PO/penerimaan, memeriksa supplier, item, satuan, dan gudang tujuan terhadap barang fisik.
5. Catat kuantitas datang dan kuantitas layak diterima secara benar. Catat selisih/kerusakan dan batch/kedaluwarsa bila relevan.
6. Simpan penerimaan. Untuk penerimaan gudang yang langsung disetujui, jumlah diterima membuat Stock In movement. Penerimaan outlet dapat menunggu persetujuan PIC sesuai konfigurasi/alurnya.
7. **Jangan buat Stock In kedua untuk barang yang sudah dicatat lewat “Validasi Penerimaan” PO.** Halaman PO adalah jalur penerimaan tersebut.
8. Receipt boleh ada sebelum invoice. Purchasing/Finance kemudian menautkan dokumen invoice. Status dokumen invoice dan saldo stok adalah konsep terpisah.

Barang datang yang rusak/ditolak tidak boleh masuk ke kuantitas layak yang menambah saldo. Jangan mengisi nilai batch/expiry yang tidak diketahui seolah-olah benar.

### 4.3 Stock In manual

Gunakan Stock In manual untuk penerimaan yang memang bukan receipt PO (misalnya saldo awal/jenis sumber yang diizinkan proses). Staff dengan permission `stock.manage` mengisi lokasi, sumber/referensi, tanggal, produk, jumlah, dan informasi lot yang tersedia. Pastikan sebelum dan sesudahnya saldo serta riwayat movement cocok. Jangan memakai Stock In manual untuk menggandakan receipt PO.

### 4.4 Pengeluaran stok

Staff dengan `stock.manage` mencatat Stock Out dari lokasi, tujuan/pemakaian, referensi, tanggal, item, jumlah, dan kategori bila diminta. Saldo yang tersedia harus cukup. Item expired/karantina tidak boleh diambil melalui jalur normal. Untuk memindahkan barang antar lokasi gunakan transfer, bukan Stock Out biasa tanpa tujuan yang dapat direkonsiliasi.

### 4.5 Transfer gudang ke outlet

1. Outlet dapat mengajukan permintaan transfer.
2. Gudang melakukan picking dan menyiapkan transfer sesuai jumlah yang diminta dan stok yang tersedia.
3. Sistem memvalidasi ketersediaan saat transfer disiapkan; permintaan yang jelas melebihi stok tidak seharusnya menjadi approval pending.
4. PIC Gudang/penyetuju yang berwenang menyetujui dispatch. Pembuat tidak menyetujui transfernya sendiri (pengecualian Super Admin pada implementasi).
5. Setelah dispatch, stok sumber berkurang dan transfer berstatus dalam perjalanan. Stok outlet belum bertambah.
6. PIC Outlet mencatat jumlah yang benar-benar sampai. Penerimaan boleh parsial; hanya jumlah yang diterima yang menambah stok outlet.
7. Jika penerimaan ditutup dengan kekurangan, discrepancy perlu alasan dan diselesaikan dengan penerimaan lanjutan atau keputusan terdokumentasi oleh pihak berwenang.

### 4.6 Stock Opname dan koreksi

Petugas memulai sesi opname untuk lokasi/cakupan tertentu, menghitung fisik, lalu mengirim hasil. Penyetuju yang berbeda memeriksa selisih. Hanya persetujuan yang seharusnya memposting penyesuaian ke ledger. Penolakan tidak boleh mengubah saldo. Uji juga apa yang terjadi bila movement baru masuk saat sesi berjalan; catat perilaku yang terlihat untuk UAT.

Stock Adjustment adalah jalur koreksi berizin dan harus memiliki alasan serta jejak referensi. Jangan menghapus atau mengedit sejarah transaksi untuk “membetulkan” angka.

### 4.7 Barang rusak, hilang, expired, dan retur supplier

Alur disposal/retur mencatat pengajuan, item, alasan, dan approval. Pengajuan pending menahan barang dari penggunaan normal sesuai implementasi; approval membuat movement keluar. Retur supplier perlu tindak lanjut hasilnya (misalnya credit note/refund, penggantian melalui receipt tersendiri, atau klaim ditolak). Uji kuantitas parsial dan pastikan jumlah penyelesaian tidak melebihi klaim.

### 4.8 Invoice, review, pembayaran, dan valuasi

Invoice Purchasing menyimpan harga yang menjadi sumber biaya pembelian. Status review invoice (misalnya submitted/accepted/rejected/revision) berbeda dari status pembayaran (unpaid/partial/paid). Finance mencatat pembayaran sebagai transaksi tersendiri dan pembetulan pembayaran menggunakan reversal beralasan.

Laporan valuasi menggunakan moving weighted average per produk-lokasi menurut keputusan bisnis. Snapshot yang tersedia adalah kondisi saat ini, bukan laporan historis pada tanggal cutoff. Receipt tanpa invoice masih dapat menambah kuantitas, tetapi nilai biayanya belum lengkap. Diskon dialokasikan ke item; pajak, ongkir, biaya lain, dan selisih dokumen ditampilkan terpisah sesuai batas implementasi. Jangan memperlakukan angka provisional/incomplete sebagai nilai buku resmi sebelum rekonsiliasi dan UAT Finance.

### 4.9 Stok minimum dan WhatsApp

Ambang stok dapat diatur per produk-lokasi jika akun memiliki izin. Alert perlu diperiksa melalui daftar alert dan status integrasi WhatsApp. Status QR/linked harus mencerminkan keadaan layanan, bukan sekadar tampilan halaman. Di lingkungan UAT, gunakan nomor uji yang telah disetujui dan pastikan penerima per lokasi/role sebelum mengaktifkan pengiriman.

## 5. Cara memulai sesi latihan

1. Pastikan aplikasi lokal/staging dan database memang lingkungan uji; periksa URL dan label lingkungan sebelum login.
2. Minta/siapkan akun uji untuk role yang terlibat. Catat role, permission, lokasi scope, serta data master yang tersedia.
3. Catat saldo awal produk uji di gudang dan outlet, unitnya, serta batch/expiry yang ada. Ambil nomor referensi transaksi awal.
4. Jalankan satu skenario pada satu waktu dan catat ID/referensi setiap transaksi.
5. Setelah setiap aksi, cek tiga hal: status dokumen, saldo di lokasi yang terpengaruh, dan baris riwayat movement/audit yang terkait.
6. Jangan mengulang submit karena halaman tampak lambat tanpa memeriksa apakah transaksi pertama telah tersimpan. Penerimaan menggunakan mekanisme perlindungan submit berulang; tetap cek referensi/riwayat terlebih dahulu.
7. Untuk tes negatif, gunakan data uji dan catat pesan validasi. Jangan memaksa request atau mengubah saldo database secara langsung.

## 6. Skenario uji manual

Untuk tiap skenario, tulis: **Prasyarat, langkah, hasil yang diharapkan, hasil aktual, referensi transaksi, bukti/screenshot, status (Lulus/Gagal/Belum diuji), dan catatan**. Kolom hasil aktual tidak diisi terlebih dahulu.

### Skenario A — Login dan role

- **Prasyarat:** akun uji aktif untuk dua role berbeda.
- **Langkah:** login role pertama, periksa menu/akses; logout; login role kedua; coba halaman yang tidak diizinkan role kedua.
- **Harapan:** akses mengikuti permission server; konteks lokasi lama tidak terbawa; halaman role sebelumnya tidak muncul dari cache.

### Skenario B — Penerimaan PO, selisih, dan invoice pending

- **Prasyarat:** PO terbuka dengan produk uji; akun Purchasing dan Staff Gudang; gudang aktif.
- **Langkah:** masukkan jumlah datang lebih tinggi dari jumlah layak bila ada barang rusak; tulis selisih; simpan Validasi Penerimaan; buka receipt dan saldo; tautkan invoice kemudian.
- **Harapan:** hanya jumlah layak menambah saldo; status/riwayat receipt jelas; invoice pending tidak membuat movement kedua; menautkan invoice tidak menambah kuantitas lagi.

### Skenario C — Penerimaan outlet memerlukan approval

- **Prasyarat:** PO atau sumber penerimaan outlet dan PIC Outlet berizin.
- **Langkah:** Staff Gudang membuat penerimaan untuk outlet; periksa status dan saldo; PIC menyetujui atau menolak dari antrean approval.
- **Harapan:** sebelum approval status pending dan saldo belum bertambah; approval membuat satu receipt movement; rejection tidak menambah saldo dan menyimpan alasan.

### Skenario D — Stock Out tersedia/tidak tersedia

- **Prasyarat:** saldo tersedia pada lokasi uji.
- **Langkah:** buat Stock Out valid; cek saldo dan movement. Lalu coba jumlah melebihi saldo pada transaksi uji terpisah.
- **Harapan:** transaksi valid mengurangi saldo sesuai jumlah; jumlah berlebih ditolak saat dibuat dan tidak meninggalkan transaksi pending yang seolah bisa disetujui.

### Skenario E — Transfer parsial dan kekurangan

- **Prasyarat:** saldo gudang memadai; outlet aktif; akun Staff Gudang, PIC Gudang, PIC Outlet.
- **Langkah:** buat permintaan/picking; siapkan transfer; minta PIC Gudang menyetujui; pastikan stok gudang berkurang namun stok outlet belum bertambah; terima sebagian; kemudian terima sisanya atau tutup dengan discrepancy dan alasan.
- **Harapan:** transfer berstatus in transit/partially received/discrepancy sesuai langkah; hanya penerimaan menambah saldo outlet; keputusan discrepancy mencatat jumlah dan alasan. Uji pula jumlah dispatch melebihi stok dan pembuat mencoba approve sendiri.

### Skenario F — Opname maker-checker

- **Prasyarat:** akun petugas dan approver berbeda; stok uji diketahui.
- **Langkah:** buat sesi, isi hitungan berbeda dari sistem, submit, lalu login approver untuk approve; ulangi sesi uji terpisah dan reject.
- **Harapan:** submit saja tidak mengubah saldo; approval mencatat adjustment/movement sekali; rejection tidak mem-posting selisih; pembuat tidak menyetujui sesi sendiri jika aturan tersebut diberlakukan.

### Skenario G — Disposal/retur

- **Prasyarat:** batch uji yang dapat dipakai untuk klaim dan role pengaju/approver.
- **Langkah:** ajukan disposal/retur sebagian; cek apakah jumlah tertahan dari Stock Out normal; approve pada akun berbeda; isi penyelesaian supplier bila relevan.
- **Harapan:** pending belum mengurangi ledger; approval membuat satu movement keluar; jumlah retur dibatasi receipt/invoice/batch yang relevan; hasil supplier dapat ditelusuri.

### Skenario H — Invoice dan pembayaran

- **Prasyarat:** invoice uji dan akun Purchasing/Finance.
- **Langkah:** masukkan harga dan komponen invoice; submit; Finance review; catat pembayaran sebagian, lalu pembayaran sisa; buka invoice dan valuasi. Pada invoice uji lain, coba reversal pembayaran dengan alasan.
- **Harapan:** review dan pembayaran memiliki status terpisah; saldo pembayaran tersisa benar; reversal tercatat, bukan menghapus transaksi; nilai incomplete/provisional diberi label.

### Skenario I — Scope outlet dan permission

- **Prasyarat:** PIC Outlet A dan B serta dua outlet aktif.
- **Langkah:** login PIC A; buka data outlet A; coba memilih/membuka URL atau filter outlet B; coba aksi yang tak sesuai role.
- **Harapan:** backend menolak/menyaring outlet yang tidak ditugaskan dan aksi yang tak berizin. Catat respons HTTP/pesan tanpa membocorkan data outlet B.

### Skenario J — Low stock dan WhatsApp

- **Prasyarat:** produk uji dengan ambang outlet; nomor penerima uji yang disetujui; integrasi layanan dapat diakses.
- **Langkah:** atur ambang, ubah saldo melalui transaksi sah hingga di bawah ambang, buka daftar alert/status WhatsApp; jika pengiriman UAT disetujui, verifikasi pesan pada penerima uji.
- **Harapan:** alert terkait produk-lokasi benar; penerima sesuai konfigurasi; status service/QR/linked sesuai kondisi backend; kegagalan pengiriman tercatat.

## 7. Checklist sebelum menyebut fitur lulus

- [ ] Peran dan scope lokasi yang benar telah digunakan.
- [ ] Status dokumen sebelum/sesudah aksi dicatat.
- [ ] Saldo produk-lokasi sebelum/sesudah direkonsiliasi dengan movement.
- [ ] Aksi pending tidak mengubah saldo sebelum approval.
- [ ] Approval/rejection dan pemisahan pembuat-penyetuju sudah dicoba bila berlaku.
- [ ] Jumlah parsial, sisa kuantitas, dan discrepancy diperiksa.
- [ ] Riwayat/audit memuat actor, waktu, referensi, alasan, dan hubungan transaksi.
- [ ] Pengguna tidak dapat membaca/menulis lokasi di luar scope.
- [ ] Bukti UAT dan hasil aktual dicatat; kegagalan dilaporkan dengan referensi, role, langkah, dan screenshot.

## 8. Batasan dan hal yang perlu dipastikan

- Permission akun aktif dapat berbeda dari konfigurasi default pada seeder; cek halaman Role/Permission dengan Super Admin.
- Rekomendasi dalam dokumen keputusan bisnis tidak otomatis menjadi kebijakan final. Pastikan keputusan yang masih terbuka dikonfirmasi pemilik proses.
- Tidak semua skenario di atas telah dibuktikan lewat UAT. Panduan ini menjelaskan apa yang perlu dicoba, bukan menyatakan hasilnya lulus.
- Status migration dan konfigurasi lokal/staging bisa berbeda. Sebelum sesi, tim teknis harus memastikan migration yang diperlukan tersedia pada lingkungan uji; jangan menjalankan migration pada produksi sebagai bagian dari latihan.
- Jangan memasukkan data pelanggan/operasional nyata atau mengirim pesan eksternal dalam UAT tanpa persetujuan dan penerima yang disepakati.

## 9. Rujukan di repository

- `routes/web.php` — halaman dan aksi utama aplikasi.
- `database/seeders/RolePermissionSeeder.php` — role dan permission awal; permission dapat berubah setelah aplikasi berjalan.
- `app/Services/StockInService.php`, `StockTransferService.php`, `StockOutService.php`, `StockOpnameService.php`, `PurchaseInvoiceService.php`, `StockDisposalService.php` — aturan transaksi utama.
- `docs/technolife-inventory-business-decisions-and-feature-checklist-v1.md` — keputusan bisnis, checklist implementasi, dan item UAT.
- `docs/konsep system.md` dan `docs/technolife-inventory-roadmap-v3.md` — latar konsep/roadmap.
