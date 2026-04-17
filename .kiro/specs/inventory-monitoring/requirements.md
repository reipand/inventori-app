# Dokumen Requirements

## Pendahuluan

Sistem ini adalah aplikasi web untuk monitoring stok toko grosir agen minuman. Aplikasi berbasis website namun dapat di akses dengan aplikasi mobile friendly memungkinkan Pengelola atau Kasir toko untuk memantau ketersediaan stok produk minuman secara real-time, mencatat transaksi masuk dan keluar barang, serta mendapatkan notifikasi ketika stok mendekati batas minimum. Tujuan utamanya adalah mengurangi risiko kehabisan stok dan mempermudah pengelolaan inventaris secara efisien.

## Glosarium
- **Sistem**: Aplikasi web inventory monitoring toko agen minuman
- **Pengelola**: Pengguna dengan peran admin yang memiliki akses penuh untuk mengelola produk, kategori, stok, pengguna, dan laporan
- **Kasir**: Pengguna dengan peran terbatas yang hanya dapat mencatat Transaksi_Keluar dan melihat daftar produk
- **Pengguna_Aktif**: Pengelola atau Kasir yang sedang dalam sesi terautentikasi
- **Produk**: Item minuman yang dijual atau disimpan di toko agen, diidentifikasi secara unik oleh Kode_SKU
- **Kode_SKU**: Kode unik alfanumerik yang mengidentifikasi setiap Produk dalam Sistem
- **Stok**: Jumlah unit Produk yang tersedia, dihitung sebagai total Transaksi_Masuk dikurangi total Transaksi_Keluar
- **Stok_Minimum**: Batas jumlah stok terendah yang ditentukan per Produk; ketika Stok mencapai atau turun di bawah nilai ini, Sistem mengirimkan peringatan
- **Transaksi_Masuk**: Pencatatan penambahan Stok akibat penerimaan barang dari Supplier
- **Transaksi_Keluar**: Pencatatan pengurangan Stok akibat penjualan atau pengeluaran barang
- **Kategori**: Pengelompokan Produk berdasarkan jenis minuman (contoh: air mineral, minuman bersoda, jus)
- **Supplier**: Pihak penyedia Produk minuman kepada toko agen
- **Laporan**: Ringkasan data Stok dan transaksi dalam periode tertentu yang dapat diekspor

---

## Requirements

### Requirement 1: Manajemen Produk

**User Story:** Sebagai Pengelola, saya ingin menambah, mengubah, dan menghapus data produk, agar daftar produk di Sistem selalu akurat dan terkini.

#### Acceptance Criteria

1. THE Sistem SHALL menyimpan data setiap Produk yang mencakup nama produk, Kode_SKU, Kategori, satuan, harga beli, harga jual, dan Stok_Minimum.
2. WHEN Pengelola mengirimkan formulir tambah Produk dengan seluruh field wajib terisi dan valid, THE Sistem SHALL menyimpan Produk baru dan menampilkan Produk tersebut dalam daftar produk.
3. IF Pengelola mengirimkan formulir tambah Produk dengan Kode_SKU yang sudah terdaftar, THEN THE Sistem SHALL menolak penyimpanan dan menampilkan pesan kesalahan "Kode SKU sudah digunakan".
4. IF Pengelola mengirimkan formulir tambah Produk dengan field wajib yang kosong, THEN THE Sistem SHALL menolak penyimpanan dan menampilkan pesan kesalahan yang menyebutkan field mana yang belum diisi.
5. WHEN Pengelola memperbarui data Produk yang ada dengan data valid, THE Sistem SHALL menyimpan perubahan dan menampilkan data terbaru pada daftar produk.
6. WHEN Pengelola menghapus Produk yang tidak memiliki riwayat transaksi, THE Sistem SHALL menghapus Produk dari Sistem.
7. IF Pengelola mencoba menghapus Produk yang memiliki riwayat transaksi, THEN THE Sistem SHALL menolak penghapusan dan menampilkan pesan "Produk tidak dapat dihapus karena memiliki riwayat transaksi".
8. THE Sistem SHALL menampilkan daftar Produk dengan kemampuan pencarian berdasarkan nama produk atau Kode_SKU.
9. THE Sistem SHALL menampilkan daftar Produk dengan kemampuan filter berdasarkan Kategori.
10. IF Pengelola memasukkan nilai Stok_Minimum kurang dari nol, THEN THE Sistem SHALL menolak penyimpanan dan menampilkan pesan "Stok minimum tidak boleh kurang dari 0".

---

### Requirement 2: Manajemen Kategori

**User Story:** Sebagai Pengelola, saya ingin mengelola kategori produk, agar produk dapat dikelompokkan dengan rapi dan mudah ditemukan.

#### Acceptance Criteria

1. THE Sistem SHALL menyimpan data Kategori yang mencakup nama kategori dan deskripsi opsional.
2. WHEN Pengelola mengirimkan formulir tambah Kategori dengan nama yang unik dan valid, THE Sistem SHALL menyimpan Kategori baru dan menampilkannya dalam daftar kategori.
3. IF Pengelola mengirimkan formulir tambah Kategori dengan nama yang sudah terdaftar, THEN THE Sistem SHALL menolak penyimpanan dan menampilkan pesan "Nama kategori sudah digunakan".
4. WHEN Pengelola memperbarui nama Kategori, THE Sistem SHALL menyimpan perubahan dan memperbarui nama Kategori pada seluruh Produk yang terkait.
5. IF Pengelola mencoba menghapus Kategori yang masih memiliki Produk terdaftar, THEN THE Sistem SHALL menolak penghapusan dan menampilkan pesan "Kategori tidak dapat dihapus karena masih memiliki produk".
6. WHEN Pengelola menghapus Kategori yang tidak memiliki Produk terdaftar, THE Sistem SHALL menghapus Kategori dari Sistem.

---

### Requirement 3: Manajemen Stok — Transaksi Masuk

**User Story:** Sebagai Pengelola, saya ingin mencatat penerimaan barang dari supplier, agar Stok di Sistem selalu mencerminkan kondisi nyata di gudang.

#### Acceptance Criteria

1. WHEN Pengelola mencatat Transaksi_Masuk dengan Produk, jumlah bilangan bulat positif, tanggal, dan nama Supplier yang valid, THE Sistem SHALL menambahkan jumlah tersebut ke Stok Produk yang bersangkutan.
2. THE Sistem SHALL menyimpan riwayat setiap Transaksi_Masuk beserta tanggal, nama Supplier, jumlah, harga beli per unit, dan nama Pengelola yang mencatat.
3. IF Pengelola memasukkan jumlah Transaksi_Masuk kurang dari atau sama dengan nol, THEN THE Sistem SHALL menolak transaksi dan menampilkan pesan "Jumlah harus lebih dari 0".
4. IF Pengelola memasukkan jumlah Transaksi_Masuk yang bukan bilangan bulat, THEN THE Sistem SHALL menolak transaksi dan menampilkan pesan "Jumlah harus berupa bilangan bulat".
5. WHEN Transaksi_Masuk berhasil disimpan, THE Sistem SHALL menampilkan nilai Stok terbaru Produk tanpa perlu memuat ulang halaman.
6. WHEN Transaksi_Masuk berhasil disimpan dan Stok Produk sebelumnya berada di bawah atau sama dengan Stok_Minimum, THE Sistem SHALL memperbarui indikator status stok Produk secara otomatis.

---

### Requirement 4: Manajemen Stok — Transaksi Keluar

**User Story:** Sebagai Pengelola atau Kasir, saya ingin mencatat penjualan atau pengeluaran barang, agar Stok berkurang sesuai dengan barang yang telah keluar.

#### Acceptance Criteria

1. WHEN Pengelola_Aktif atau Kasir mencatat Transaksi_Keluar dengan Produk, jumlah bilangan bulat positif, dan tanggal yang valid, THE Sistem SHALL mengurangi jumlah tersebut dari Stok Produk yang bersangkutan.
2. THE Sistem SHALL menyimpan riwayat setiap Transaksi_Keluar beserta tanggal, jumlah, harga jual per unit, dan nama Pengguna_Aktif yang mencatat.
3. IF Pengguna_Aktif memasukkan jumlah Transaksi_Keluar melebihi Stok yang tersedia, THEN THE Sistem SHALL menolak transaksi dan menampilkan pesan "Jumlah melebihi stok yang tersedia".
4. IF Pengguna_Aktif memasukkan jumlah Transaksi_Keluar kurang dari atau sama dengan nol, THEN THE Sistem SHALL menolak transaksi dan menampilkan pesan "Jumlah harus lebih dari 0".
5. IF Pengguna_Aktif memasukkan jumlah Transaksi_Keluar yang bukan bilangan bulat, THEN THE Sistem SHALL menolak transaksi dan menampilkan pesan "Jumlah harus berupa bilangan bulat".
6. WHEN Transaksi_Keluar berhasil disimpan, THE Sistem SHALL menampilkan nilai Stok terbaru Produk tanpa perlu memuat ulang halaman.

---

### Requirement 5: Monitoring Stok dan Notifikasi

**User Story:** Sebagai Pengelola, saya ingin mendapatkan peringatan ketika stok produk mendekati atau mencapai batas minimum, agar saya dapat segera melakukan pemesanan ulang.

#### Acceptance Criteria

1. THE Sistem SHALL menampilkan indikator visual pada daftar Produk untuk membedakan tiga status: stok normal (Stok > Stok_Minimum), stok rendah (Stok sama dengan atau kurang dari Stok_Minimum dan lebih dari nol), dan stok habis (Stok sama dengan nol).
2. WHEN Stok Produk turun ke nilai sama dengan atau kurang dari Stok_Minimum setelah Transaksi_Keluar dicatat, THE Sistem SHALL menampilkan notifikasi peringatan kepada Pengelola yang sedang aktif dalam sesi yang sama.
3. WHEN Stok Produk mencapai nol, THE Sistem SHALL menandai Produk tersebut sebagai "Stok Habis" pada daftar Produk.
4. THE Sistem SHALL menyediakan halaman ringkasan yang menampilkan seluruh Produk dengan Stok di bawah atau sama dengan Stok_Minimum, diurutkan berdasarkan selisih antara Stok dan Stok_Minimum dari yang terkecil.
5. WHEN Stok Produk kembali di atas Stok_Minimum setelah Transaksi_Masuk dicatat, THE Sistem SHALL memperbarui indikator visual Produk tersebut menjadi status stok normal.

---

### Requirement 6: Laporan Inventaris

**User Story:** Sebagai Pengelola, saya ingin melihat laporan stok dan riwayat transaksi, agar saya dapat menganalisis pergerakan barang dan membuat keputusan pembelian.

#### Acceptance Criteria

1. THE Sistem SHALL menampilkan laporan ringkasan Stok saat ini untuk seluruh Produk, mencakup nama produk, Kode_SKU, Stok saat ini, Stok_Minimum, dan status stok.
2. WHEN Pengelola memilih rentang tanggal yang valid dan menekan tombol filter, THE Sistem SHALL menampilkan riwayat transaksi (masuk dan keluar) dalam rentang tanggal tersebut.
3. IF Pengelola memasukkan tanggal awal yang lebih besar dari tanggal akhir pada filter rentang tanggal, THEN THE Sistem SHALL menolak filter dan menampilkan pesan "Tanggal awal tidak boleh lebih besar dari tanggal akhir".
4. THE Sistem SHALL menampilkan riwayat transaksi dengan kemampuan filter berdasarkan jenis transaksi (masuk atau keluar) dan berdasarkan Produk.
5. WHEN Pengelola menekan tombol ekspor laporan, THE Sistem SHALL mengunduh file CSV yang berisi data Stok atau riwayat transaksi sesuai filter yang aktif, dengan nama file yang mencantumkan tanggal ekspor.
6. THE Sistem SHALL menampilkan ringkasan nilai total stok (jumlah unit dikali harga beli) untuk seluruh Produk pada halaman laporan.

---

### Requirement 7: Manajemen Pengguna dan Autentikasi

**User Story:** Sebagai Pengelola, saya ingin mengatur akun pengguna dengan peran yang berbeda, agar hanya pengguna yang berwenang yang dapat mengakses atau mengubah data inventaris.

#### Acceptance Criteria

1. THE Sistem SHALL mendukung dua peran pengguna: Pengelola dan Kasir, dengan hak akses yang berbeda.
2. WHEN pengguna memasukkan email dan kata sandi yang valid, THE Sistem SHALL mengautentikasi pengguna dan mengarahkan ke halaman utama sesuai perannya.
3. IF pengguna memasukkan email atau kata sandi yang tidak valid, THEN THE Sistem SHALL menolak akses dan menampilkan pesan "Email atau kata sandi salah".
4. WHILE pengguna tidak terautentikasi, THE Sistem SHALL mengarahkan setiap permintaan halaman ke halaman login.
5. WHEN pengguna menekan tombol keluar, THE Sistem SHALL mengakhiri sesi pengguna dan mengarahkan ke halaman login.
6. THE Sistem SHALL membatasi akses Kasir hanya pada fitur pencatatan Transaksi_Keluar dan melihat daftar Produk.
7. IF Kasir mencoba mengakses halaman yang hanya diizinkan untuk Pengelola, THEN THE Sistem SHALL menolak akses dan menampilkan pesan "Anda tidak memiliki izin untuk mengakses halaman ini".
8. WHEN Pengelola membuat akun pengguna baru dengan email unik dan peran yang valid, THE Sistem SHALL menyimpan akun tersebut dan mengirimkan kata sandi sementara ke alamat email pengguna baru.
9. IF Pengelola mencoba membuat akun dengan email yang sudah terdaftar, THEN THE Sistem SHALL menolak pembuatan akun dan menampilkan pesan "Email sudah terdaftar".
10. WHEN Pengguna_Aktif menggunakan kata sandi sementara untuk login pertama kali, THE Sistem SHALL mewajibkan pengguna mengganti kata sandi sebelum dapat mengakses halaman utama.
11. WHEN Pengelola menonaktifkan akun pengguna, THE Sistem SHALL mencabut sesi aktif pengguna tersebut dan mencegah login berikutnya.

---

### Requirement 8: Audit Trail

**User Story:** Sebagai Pengelola, saya ingin melihat riwayat perubahan data penting, agar saya dapat melacak siapa yang melakukan perubahan dan kapan perubahan tersebut terjadi.

#### Acceptance Criteria

1. THE Sistem SHALL mencatat setiap perubahan data Produk (tambah, ubah, hapus) beserta nama Pengelola yang melakukan perubahan dan waktu perubahan.
2. THE Sistem SHALL mencatat setiap Transaksi_Masuk dan Transaksi_Keluar beserta nama Pengguna_Aktif yang mencatat dan waktu pencatatan.
3. WHEN Pengelola mengakses halaman audit trail, THE Sistem SHALL menampilkan riwayat perubahan yang dapat difilter berdasarkan jenis aksi, nama pengguna, dan rentang tanggal.
4. THE Sistem SHALL menyimpan data audit trail selama minimal 1 tahun sejak tanggal pencatatan.
