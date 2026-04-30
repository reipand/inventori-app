# Dokumen Persyaratan

## Pendahuluan

Fitur ini menambahkan tiga modul baru ke sistem inventori Cahaya Prima yang sudah berjalan: **Purchase Module** (pembelian dari supplier berbasis invoice), **POS/Kasir** (point of sale dengan keranjang belanja, pembayaran, dan struk), serta **Profit Tracking** yang akurat berbasis COGS nyata dari invoice pembelian.

Masalah utama yang diselesaikan: sistem lama meminta kasir menginput "harga beli" secara manual saat transaksi keluar, yang menyebabkan perhitungan profit tidak akurat. Solusinya adalah COGS (Cost of Goods Sold) diambil otomatis dari invoice pembelian supplier, bukan dari input manual.

Stack: Vue 3 + TypeScript + Pinia + TailwindCSS (frontend), Laravel API (backend), database MySQL/SQLite.

---

## Glosarium

- **Purchase_Module**: Modul pencatatan pembelian barang dari supplier berbasis invoice.
- **Invoice**: Dokumen pembelian dari supplier yang berisi daftar item, harga, dan diskon.
- **Invoice_Item**: Satu baris item dalam sebuah Invoice, berisi produk, qty, harga input, dan diskon per item.
- **COGS**: Cost of Goods Sold — harga pokok penjualan nyata per unit, dihitung dari Invoice setelah distribusi diskon.
- **POS**: Point of Sale — antarmuka kasir untuk melakukan transaksi penjualan.
- **Cart**: Keranjang belanja sementara di POS yang berisi item-item yang akan dibeli pelanggan.
- **Cart_Item**: Satu baris produk di dalam Cart, berisi produk, qty, harga jual, dan diskon per item.
- **Receipt**: Struk/bukti transaksi penjualan yang dicetak atau ditampilkan ke pelanggan.
- **Sale**: Transaksi penjualan yang sudah selesai, terdiri dari satu atau lebih Sale_Item.
- **Sale_Item**: Satu baris produk dalam sebuah Sale, menyimpan harga jual dan COGS saat transaksi terjadi.
- **Profit**: Selisih antara harga jual dan COGS per unit, dikalikan qty terjual.
- **Supplier**: Pihak yang menjual barang ke toko, dicatat dalam Invoice.
- **Price_Mode**: Pilihan cara input harga di Purchase Module — "harga dari struk (final/sudah diskon)" atau "harga sebelum diskon".
- **Thermal_Printer**: Printer struk berukuran 58mm atau 80mm yang digunakan di kasir.
- **Pengelola**: Peran pengguna dengan akses penuh ke semua modul termasuk Purchase dan Laporan Profit.
- **Kasir**: Peran pengguna dengan akses ke POS dan melihat produk.
- **Breakpoint_Mobile**: Lebar layar ≤ 767px.
- **Breakpoint_Tablet**: Lebar layar 768px – 1365px.
- **Breakpoint_Desktop**: Lebar layar ≥ 1366px.

---

## Persyaratan

---

### Persyaratan 1: Input Invoice Pembelian

**User Story:** Sebagai Pengelola, saya ingin mencatat invoice pembelian dari supplier beserta item-itemnya, sehingga sistem dapat menghitung COGS yang akurat untuk setiap produk.

#### Kriteria Penerimaan

1. THE Purchase_Module SHALL menyediakan form input Invoice dengan field: nomor invoice, nama supplier, tanggal invoice, dan diskon global (opsional, dalam persen atau nominal).
2. WHEN Pengelola menambahkan Invoice_Item ke Invoice, THE Purchase_Module SHALL menyediakan field: pilih produk, qty, harga input, Price_Mode, dan diskon per item (opsional, dalam persen atau nominal).
3. WHEN Pengelola memilih Price_Mode "harga dari struk (final)", THE Purchase_Module SHALL menggunakan harga input langsung sebagai harga satuan final setelah diskon per item.
4. WHEN Pengelola memilih Price_Mode "harga sebelum diskon", THE Purchase_Module SHALL menghitung harga satuan final dengan rumus: `harga_satuan_final = harga_input × (1 - diskon_per_item_persen / 100)` atau `harga_satuan_final = harga_input - diskon_per_item_nominal`.
5. WHEN Invoice memiliki diskon global, THE Purchase_Module SHALL mendistribusikan diskon global secara proporsional ke setiap Invoice_Item berdasarkan nilai subtotal masing-masing item terhadap total Invoice sebelum diskon global.
6. THE Purchase_Module SHALL menghitung COGS per unit untuk setiap Invoice_Item dengan rumus: `COGS_per_unit = (harga_satuan_final × qty - porsi_diskon_global) / qty`.
7. WHEN Pengelola menyimpan Invoice, THE Purchase_Module SHALL memperbarui field `cogs` pada setiap produk yang ada di Invoice_Item dengan nilai COGS_per_unit terbaru (weighted average atau last purchase price, sesuai konfigurasi).
8. WHEN Pengelola menyimpan Invoice, THE Purchase_Module SHALL menambah `current_stock` setiap produk sesuai qty di Invoice_Item secara atomik dalam satu database transaction.
9. IF nomor invoice yang sama sudah ada di database, THEN THE Purchase_Module SHALL menampilkan pesan error "Nomor invoice sudah digunakan" dan tidak menyimpan data.
10. IF qty di Invoice_Item bernilai 0 atau negatif, THEN THE Purchase_Module SHALL menampilkan pesan validasi "Qty harus lebih dari 0" dan tidak menyimpan Invoice.

---

### Persyaratan 2: Kalkulasi COGS Otomatis

**User Story:** Sebagai Pengelola, saya ingin sistem menghitung COGS secara otomatis dari invoice tanpa input manual, sehingga profit yang dilaporkan selalu akurat.

#### Kriteria Penerimaan

1. THE Purchase_Module SHALL menampilkan preview kalkulasi secara real-time saat Pengelola mengisi form Invoice, menunjukkan: subtotal per item, total sebelum diskon global, total diskon global, dan total akhir Invoice.
2. WHEN Pengelola mengubah nilai harga input, qty, diskon per item, atau diskon global, THE Purchase_Module SHALL memperbarui preview kalkulasi dalam waktu kurang dari 100ms tanpa request ke server.
3. THE Purchase_Module SHALL menampilkan COGS per unit yang akan disimpan untuk setiap Invoice_Item di preview kalkulasi sebelum Pengelola menyimpan Invoice.
4. WHEN Invoice disimpan, THE Backend SHALL menyimpan snapshot COGS per unit ke tabel `invoice_items` agar riwayat pembelian tidak berubah meskipun COGS produk diperbarui di kemudian hari.
5. THE Purchase_Module SHALL mendukung input harga dalam format angka bulat (tanpa desimal) maupun desimal dua angka di belakang koma.

---

### Persyaratan 3: Manajemen Produk dengan COGS

**User Story:** Sebagai Pengelola, saya ingin setiap produk menyimpan COGS terkini yang berasal dari invoice pembelian, sehingga sistem POS dapat menggunakan COGS yang benar saat menghitung profit.

#### Kriteria Penerimaan

1. THE Product_Module SHALL menyimpan field `cogs` (decimal 15,2) pada setiap produk yang diperbarui otomatis setiap kali Invoice yang mengandung produk tersebut disimpan.
2. THE Product_Module SHALL menampilkan nilai `cogs` terkini di halaman daftar produk dan halaman detail produk.
3. WHEN Pengelola membuat produk baru, THE Product_Module SHALL mengizinkan `cogs` bernilai 0 (nol) sebagai nilai awal sebelum ada invoice pembelian.
4. THE Product_Module SHALL mempertahankan field `sell_price` (harga jual) yang dapat diatur secara independen dari `cogs`.
5. WHEN `cogs` sebuah produk diperbarui melalui Invoice, THE Product_Module SHALL mencatat perubahan `cogs` di audit log dengan nilai lama dan nilai baru.

---

### Persyaratan 4: Antarmuka POS (Kasir)

**User Story:** Sebagai Kasir, saya ingin antarmuka kasir yang cepat dan mudah digunakan untuk memproses transaksi penjualan, sehingga antrean pelanggan tidak menumpuk.

#### Kriteria Penerimaan

1. THE POS SHALL menyediakan search bar di bagian atas halaman untuk mencari produk berdasarkan nama atau SKU/barcode.
2. WHEN Kasir mengetik di search bar, THE POS SHALL menampilkan hasil pencarian produk dalam waktu kurang dari 300ms menggunakan debounce.
3. WHEN Kasir memilih produk dari hasil pencarian, THE POS SHALL menambahkan produk tersebut ke Cart dengan qty 1, atau menambah qty sebesar 1 jika produk sudah ada di Cart.
4. THE POS SHALL menampilkan Cart di sisi kiri (2/3 lebar layar pada Breakpoint_Desktop) dengan daftar Cart_Item yang berisi: nama produk, qty, harga satuan, diskon per item (opsional), dan subtotal per item.
5. THE POS SHALL menampilkan panel Summary di sisi kanan (1/3 lebar layar pada Breakpoint_Desktop) yang berisi: subtotal Cart, total diskon, total yang harus dibayar, input nominal bayar, dan tombol Checkout.
6. WHEN Kasir mengubah qty Cart_Item menjadi 0, THE POS SHALL menghapus Cart_Item tersebut dari Cart secara otomatis.
7. WHEN Kasir mengklik tombol hapus pada Cart_Item, THE POS SHALL menghapus Cart_Item tersebut dari Cart.
8. WHILE Cart kosong, THE POS SHALL menonaktifkan tombol Checkout.
9. WHEN Kasir memasukkan nominal bayar, THE POS SHALL menghitung dan menampilkan kembalian secara real-time dengan rumus: `kembalian = nominal_bayar - total`.
10. IF nominal bayar kurang dari total, THEN THE POS SHALL menampilkan pesan "Pembayaran kurang" dan menonaktifkan tombol Checkout.
11. WHILE Aplikasi berjalan di Breakpoint_Mobile, THE POS SHALL menampilkan layout vertikal: search bar → daftar Cart_Item → panel Summary → tombol Checkout.

---

### Persyaratan 5: Proses Checkout dan Penyimpanan Sale

**User Story:** Sebagai Kasir, saya ingin proses checkout yang cepat dan andal, sehingga transaksi tersimpan dengan benar dan struk dapat dicetak segera.

#### Kriteria Penerimaan

1. WHEN Kasir mengklik tombol Checkout dengan Cart yang valid dan nominal bayar yang cukup, THE POS SHALL mengirim data Sale ke backend dan menampilkan indikator loading.
2. THE Backend SHALL menyimpan Sale dengan field: tanggal transaksi, total, diskon total, nominal bayar, kembalian, metode pembayaran, dan `recorded_by`.
3. THE Backend SHALL menyimpan setiap Sale_Item dengan field: `product_id`, `qty`, `sell_price` (snapshot harga jual saat transaksi), `cogs` (snapshot COGS saat transaksi), dan `discount_per_item`.
4. WHEN Sale berhasil disimpan, THE Backend SHALL mengurangi `current_stock` setiap produk sesuai qty Sale_Item secara atomik dalam satu database transaction.
5. IF stok produk tidak mencukupi saat checkout, THEN THE Backend SHALL mengembalikan error dengan pesan "Stok [nama produk] tidak mencukupi (tersedia: X)" dan tidak menyimpan Sale.
6. WHEN Sale berhasil disimpan, THE POS SHALL mengosongkan Cart dan menampilkan halaman/modal Receipt.
7. WHEN Sale berhasil disimpan, THE POS SHALL menampilkan Toast sukses "Transaksi berhasil".

---

### Persyaratan 6: Sistem Receipt (Struk)

**User Story:** Sebagai Kasir, saya ingin struk transaksi yang rapi dan dapat dicetak, sehingga pelanggan mendapat bukti pembelian yang jelas.

#### Kriteria Penerimaan

1. THE Receipt SHALL menampilkan informasi: nama toko, alamat toko, tanggal dan waktu transaksi, nomor transaksi, daftar item (nama, qty, harga satuan, subtotal), subtotal, total diskon, total, metode pembayaran, nominal bayar, dan kembalian.
2. THE Receipt SHALL menggunakan layout yang dioptimalkan untuk Thermal_Printer dengan lebar 58mm atau 80mm.
3. WHEN Kasir mengklik tombol "Cetak Struk", THE Receipt SHALL memicu dialog cetak browser (`window.print()`) dengan CSS print yang menyembunyikan elemen non-struk.
4. THE Receipt SHALL menggunakan font monospace dan lebar kolom yang sesuai untuk tampilan Thermal_Printer.
5. WHEN Sale berhasil disimpan, THE POS SHALL menampilkan Receipt secara otomatis dalam modal atau halaman terpisah sebelum Kasir mengklik cetak.
6. THE Receipt SHALL menyediakan tombol "Cetak" dan tombol "Tutup / Transaksi Baru" untuk kembali ke halaman POS dengan Cart kosong.

---

### Persyaratan 7: Metode Pembayaran

**User Story:** Sebagai Kasir, saya ingin mendukung pembayaran tunai dan QR (mock), sehingga sistem siap untuk berbagai metode pembayaran.

#### Kriteria Penerimaan

1. THE POS SHALL menyediakan pilihan metode pembayaran: "Tunai" dan "QR / Transfer".
2. WHEN Kasir memilih metode "Tunai", THE POS SHALL menampilkan input nominal bayar dan kalkulasi kembalian.
3. WHEN Kasir memilih metode "QR / Transfer", THE POS SHALL menampilkan nominal yang harus dibayar dan menganggap pembayaran lunas (kembalian = 0) tanpa input nominal bayar.
4. THE Backend SHALL menyimpan metode pembayaran (`cash` atau `qr`) pada setiap Sale.
5. WHEN Kasir memilih metode "QR / Transfer", THE POS SHALL mengizinkan Checkout tanpa input nominal bayar.

---

### Persyaratan 8: Kalkulasi Profit yang Akurat

**User Story:** Sebagai Pengelola, saya ingin laporan profit yang akurat berdasarkan COGS nyata dari invoice pembelian, sehingga saya dapat membuat keputusan bisnis yang tepat.

#### Kriteria Penerimaan

1. THE Profit_Report SHALL menghitung profit per Sale_Item dengan rumus: `profit = (sell_price - cogs) × qty`.
2. THE Profit_Report SHALL menampilkan ringkasan: total pendapatan (revenue), total COGS, total profit kotor, dan margin profit (%) untuk periode yang dipilih.
3. THE Profit_Report SHALL memisahkan tampilan antara: pendapatan dari penjualan, COGS dari invoice pembelian, dan profit bersih.
4. WHEN Pengelola memfilter laporan berdasarkan rentang tanggal, THE Profit_Report SHALL menampilkan data yang sesuai dengan filter tersebut.
5. THE Profit_Report SHALL menampilkan tabel detail per produk: nama produk, total qty terjual, total revenue, total COGS, dan total profit.
6. THE Backend SHALL menggunakan nilai `cogs` yang tersimpan di `sale_items` (snapshot saat transaksi) untuk kalkulasi profit, bukan nilai `cogs` terkini di tabel `products`.

---

### Persyaratan 9: Riwayat Invoice Pembelian

**User Story:** Sebagai Pengelola, saya ingin melihat riwayat semua invoice pembelian, sehingga saya dapat melacak pengeluaran dan memverifikasi COGS.

#### Kriteria Penerimaan

1. THE Purchase_Module SHALL menampilkan daftar Invoice dengan kolom: nomor invoice, nama supplier, tanggal, jumlah item, total nilai, dan status.
2. WHEN Pengelola mengklik sebuah Invoice di daftar, THE Purchase_Module SHALL menampilkan detail Invoice beserta semua Invoice_Item, harga, diskon, dan COGS yang dihitung.
3. THE Purchase_Module SHALL mendukung filter daftar Invoice berdasarkan: rentang tanggal dan nama supplier.
4. THE Purchase_Module SHALL mendukung pencarian Invoice berdasarkan nomor invoice.
5. WHEN Pengelola mengklik tombol "Hapus" pada Invoice, THE Purchase_Module SHALL menampilkan konfirmasi dan, jika dikonfirmasi, menghapus Invoice beserta Invoice_Item-nya serta mengembalikan stok produk yang terpengaruh.
6. IF Invoice yang akan dihapus memiliki produk yang sudah terjual melalui POS, THEN THE Purchase_Module SHALL menampilkan peringatan bahwa penghapusan akan mempengaruhi akurasi laporan profit, namun tetap mengizinkan penghapusan setelah konfirmasi eksplisit.

---

### Persyaratan 10: Desain Responsif dan UX

**User Story:** Sebagai pengguna, saya ingin antarmuka yang responsif dan nyaman digunakan di desktop, tablet, maupun mobile, sehingga saya dapat bekerja dari perangkat apapun.

#### Kriteria Penerimaan

1. THE POS SHALL menggunakan layout dua kolom (Cart kiri 2/3, Summary kanan 1/3) pada Breakpoint_Desktop dan Breakpoint_Tablet.
2. WHILE Aplikasi berjalan di Breakpoint_Mobile, THE POS SHALL menggunakan layout satu kolom vertikal dengan urutan: search bar, daftar Cart_Item, panel Summary, tombol Checkout.
3. THE Purchase_Module SHALL menggunakan layout form yang responsif, dengan field-field yang tersusun dalam grid dua kolom pada Breakpoint_Desktop dan satu kolom pada Breakpoint_Mobile.
4. THE Aplikasi SHALL memastikan semua elemen interaktif memiliki touch target minimal 44×44px untuk kemudahan penggunaan di layar sentuh.
5. THE POS SHALL mendukung input barcode melalui keyboard (scanner barcode yang mengemulasi keyboard) dengan cara mendeteksi input cepat pada search bar dan langsung menambahkan produk ke Cart.
6. WHEN Kasir menekan tombol Enter setelah mengetik di search bar dan hanya ada satu hasil pencarian, THE POS SHALL langsung menambahkan produk tersebut ke Cart.
7. THE Aplikasi SHALL menggunakan desain SaaS modern yang konsisten dengan tampilan yang sudah ada (warna, tipografi, komponen dari TailwindCSS).

---

### Persyaratan 11: Navigasi dan Akses Peran

**User Story:** Sebagai pengguna, saya ingin menu navigasi yang sesuai dengan peran saya, sehingga saya hanya melihat fitur yang relevan.

#### Kriteria Penerimaan

1. THE Sidebar SHALL menampilkan menu "Pembelian" (Purchase) hanya untuk peran Pengelola.
2. THE Sidebar SHALL menampilkan menu "Kasir" (POS) untuk peran Pengelola dan Kasir.
3. THE Sidebar SHALL menampilkan menu "Laporan Profit" hanya untuk peran Pengelola.
4. WHEN Kasir mencoba mengakses halaman Purchase atau Laporan Profit secara langsung melalui URL, THE Aplikasi SHALL mengarahkan Kasir ke halaman `/pos` dengan Toast "Akses tidak diizinkan".
5. THE Backend SHALL memvalidasi peran pengguna pada setiap endpoint Purchase dan Profit Report, mengembalikan HTTP 403 jika peran tidak sesuai.

---

### Persyaratan 12: Integrasi dengan Sistem yang Ada

**User Story:** Sebagai pengembang, saya ingin modul baru terintegrasi dengan baik dengan sistem yang sudah ada, sehingga tidak ada data yang inkonsisten.

#### Kriteria Penerimaan

1. THE Purchase_Module SHALL menggunakan tabel `products` yang sudah ada dan hanya menambahkan kolom `cogs` melalui migration baru.
2. THE Purchase_Module SHALL mencatat setiap penyimpanan Invoice di `audit_logs` dengan `entity_type = 'invoice'`.
3. THE POS SHALL mencatat setiap Sale di `audit_logs` dengan `entity_type = 'sale'`.
4. WHEN Invoice disimpan dan stok produk bertambah, THE Backend SHALL memeriksa apakah ada produk yang sebelumnya di bawah `min_stock` dan kini sudah di atas `min_stock`, lalu memperbarui status notifikasi terkait.
5. THE Purchase_Module SHALL menggunakan sistem autentikasi JWT yang sudah ada tanpa perubahan.
6. THE POS SHALL menggunakan sistem autentikasi JWT yang sudah ada tanpa perubahan.
