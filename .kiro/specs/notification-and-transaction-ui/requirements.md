# Dokumen Requirements: Notification and Transaction UI

## Pendahuluan

Fitur ini mencakup dua peningkatan UI yang saling berkaitan pada sistem inventori berbasis Laravel + Vue.js 3:

1. **Sistem Notifikasi Lengkap** — Memperluas notifikasi yang sudah ada menjadi sistem notifikasi yang lebih kaya dengan icon bell di topbar, halaman notifikasi tersendiri (`/notifications`), dan dukungan tipe notifikasi tambahan (transaksi masuk/keluar).

2. **Halaman Transaksi Terpadu** — Menggabungkan `TransactionInPage.vue` dan `TransactionOutPage.vue` menjadi satu halaman `/transactions` dengan tab switcher, serta menambahkan tombol akses cepat ke transaksi di halaman produk.

Seluruh perubahan ada di sisi frontend Vue.js 3 + TypeScript — tidak ada perubahan backend.

---

## Glosarium

- **NotificationStore**: Pinia store yang mengelola state notifikasi di seluruh aplikasi
- **NotificationBell**: Komponen Vue yang menampilkan icon bell dengan badge jumlah notifikasi belum dibaca di topbar
- **NotificationPage**: Halaman Vue di route `/notifications` yang menampilkan daftar semua notifikasi
- **TransactionPage**: Halaman Vue di route `/transactions` yang menggabungkan form transaksi masuk dan keluar dalam satu tampilan dengan tab switcher
- **TransactionInForm**: Komponen form untuk mencatat transaksi masuk (penerimaan barang dari supplier)
- **TransactionOutForm**: Komponen form untuk mencatat transaksi keluar (penjualan atau pengeluaran barang)
- **ProductListPage**: Halaman Vue di route `/products` yang menampilkan daftar inventaris barang
- **Layout**: Komponen Vue yang membungkus semua halaman terproteksi, berisi sidebar navigasi dan topbar
- **unreadCount**: Jumlah notifikasi yang belum dibaca (read === false) dalam NotificationStore
- **Pengelola**: Peran pengguna dengan akses penuh ke semua fitur termasuk transaksi masuk
- **Kasir**: Peran pengguna dengan akses terbatas, hanya dapat melakukan transaksi keluar

---

## Requirements

### Requirement 1: NotificationStore — Struktur Data dan Penyimpanan

**User Story:** Sebagai pengembang, saya ingin NotificationStore menyimpan semua jenis notifikasi dalam satu array terpadu, sehingga state notifikasi dapat dikelola secara konsisten di seluruh aplikasi.

#### Acceptance Criteria

1. THE NotificationStore SHALL menyimpan notifikasi dalam array `notifications` dengan tipe `Notification` yang memiliki field: `id`, `type`, `title`, `message`, `read`, dan `createdAt`
2. THE NotificationStore SHALL mendukung tiga tipe notifikasi: `'low_stock'`, `'transaction_in'`, dan `'transaction_out'`
3. WHEN `addLowStockAlert` dipanggil dengan `productId` yang sudah ada dalam array `notifications`, THEN THE NotificationStore SHALL tidak menambahkan entri duplikat
4. WHEN `addTransactionAlert` dipanggil, THEN THE NotificationStore SHALL selalu menambahkan notifikasi baru tanpa deduplikasi
5. WHEN jumlah notifikasi dalam array melebihi 50, THEN THE NotificationStore SHALL menghapus notifikasi terlama (index terkecil) sehingga array tidak pernah melebihi 50 item

---

### Requirement 2: NotificationStore — Computed dan Actions

**User Story:** Sebagai pengguna, saya ingin badge notifikasi selalu menampilkan jumlah yang akurat, sehingga saya tahu berapa banyak notifikasi yang belum saya baca.

#### Acceptance Criteria

1. THE NotificationStore SHALL menyediakan computed `unreadCount` yang nilainya selalu sama dengan jumlah item dalam `notifications` yang memiliki `read === false`
2. WHEN `markAllRead()` dipanggil, THEN THE NotificationStore SHALL mengubah semua notifikasi menjadi `read === true` sehingga `unreadCount` menjadi 0
3. WHEN `markRead(id)` dipanggil dengan id notifikasi yang valid, THEN THE NotificationStore SHALL mengubah notifikasi dengan id tersebut menjadi `read === true`
4. WHEN `dismissNotification(id)` dipanggil dengan id notifikasi yang valid, THEN THE NotificationStore SHALL menghapus tepat satu item dari array `notifications` sehingga panjang array berkurang 1 dan item dengan id tersebut tidak ada lagi
5. WHEN `clearAll()` dipanggil, THEN THE NotificationStore SHALL mengosongkan array `notifications` sehingga `unreadCount` menjadi 0

---

### Requirement 3: NotificationStore — Backward Compatibility

**User Story:** Sebagai pengembang, saya ingin kode yang sudah menggunakan `lowStockAlerts` tetap berfungsi tanpa perubahan, sehingga tidak ada regresi pada fitur yang sudah ada.

#### Acceptance Criteria

1. THE NotificationStore SHALL menyediakan computed `lowStockAlerts` yang mengembalikan array berisi semua notifikasi bertipe `'low_stock'` yang dipetakan ke format `{ productId, productName, currentStock, minStock }`
2. WHEN `addLowStockAlert(alert)` dipanggil, THEN THE NotificationStore SHALL membuat notifikasi bertipe `'low_stock'` dengan data `productId`, `productName`, `currentStock`, dan `minStock` yang identik dengan parameter `alert`
3. THE NotificationStore SHALL menyediakan method `dismissAlert(productId)` yang menghapus notifikasi `'low_stock'` dengan `productId` yang sesuai untuk backward compatibility

---

### Requirement 4: NotificationBell — Komponen Topbar

**User Story:** Sebagai pengguna, saya ingin melihat icon bell di topbar yang menampilkan jumlah notifikasi belum dibaca, sehingga saya dapat mengetahui adanya notifikasi baru tanpa harus membuka halaman notifikasi.

#### Acceptance Criteria

1. THE NotificationBell SHALL selalu menampilkan icon bell SVG di topbar, terlepas dari jumlah notifikasi
2. WHEN `unreadCount` bernilai 0, THEN THE NotificationBell SHALL tidak menampilkan badge
3. WHEN `unreadCount` bernilai antara 1 dan 9, THEN THE NotificationBell SHALL menampilkan badge merah dengan angka yang sesuai
4. WHEN `unreadCount` bernilai lebih dari 9, THEN THE NotificationBell SHALL menampilkan badge merah dengan teks `"9+"`
5. WHEN pengguna mengklik NotificationBell, THEN THE NotificationBell SHALL menavigasi pengguna ke halaman `/notifications`

---

### Requirement 5: Layout — Integrasi NotificationBell

**User Story:** Sebagai pengguna, saya ingin topbar menampilkan NotificationBell sebagai pengganti tombol bell lama, sehingga pengalaman notifikasi lebih konsisten dan lengkap.

#### Acceptance Criteria

1. THE Layout SHALL menampilkan komponen `NotificationBell` di area topbar sebelah kanan
2. THE Layout SHALL menghapus blok tombol bell lama beserta panel dropdown notifikasi yang sudah ada
3. THE Layout SHALL menghapus ref `showNotifPanel` dan logika panel dropdown dari script

---

### Requirement 6: NotificationPage — Halaman Notifikasi

**User Story:** Sebagai pengguna, saya ingin melihat semua notifikasi dalam satu halaman, sehingga saya dapat meninjau dan mengelola riwayat notifikasi dengan mudah.

#### Acceptance Criteria

1. THE NotificationPage SHALL dapat diakses di route `/notifications` oleh pengguna dengan peran `pengelola` maupun `kasir`
2. WHEN NotificationPage dimuat, THEN THE NotificationPage SHALL memanggil `markAllRead()` secara otomatis sehingga badge di topbar menjadi 0
3. THE NotificationPage SHALL menampilkan daftar notifikasi diurutkan dari yang terbaru di atas
4. THE NotificationPage SHALL menampilkan icon dan warna latar berbeda untuk setiap tipe notifikasi: kuning untuk `'low_stock'`, hijau untuk `'transaction_in'`, biru untuk `'transaction_out'`
5. THE NotificationPage SHALL menampilkan waktu relatif untuk setiap notifikasi (contoh: "2 menit lalu")
6. WHEN tidak ada notifikasi dalam array, THEN THE NotificationPage SHALL menampilkan empty state
7. THE NotificationPage SHALL menyediakan tombol dismiss (×) per item yang memanggil `dismissNotification(id)`
8. THE NotificationPage SHALL menyediakan tombol "Tandai semua dibaca" dan "Hapus semua"
9. THE NotificationPage SHALL menyediakan filter tab: Semua, Stok Rendah, Transaksi Masuk, Transaksi Keluar

---

### Requirement 7: TransactionPage — Halaman Transaksi Terpadu

**User Story:** Sebagai pengguna, saya ingin mengakses transaksi masuk dan keluar dari satu halaman, sehingga navigasi lebih efisien dan pengalaman pengguna lebih baik.

#### Acceptance Criteria

1. THE TransactionPage SHALL dapat diakses di route `/transactions` oleh pengguna dengan peran `pengelola` maupun `kasir`
2. WHEN pengguna dengan peran `pengelola` mengakses TransactionPage, THEN THE TransactionPage SHALL menampilkan kedua tab: "Transaksi Masuk" dan "Transaksi Keluar"
3. WHEN pengguna dengan peran `kasir` mengakses TransactionPage, THEN THE TransactionPage SHALL hanya menampilkan konten "Transaksi Keluar" tanpa tab switcher
4. WHEN query param `?tab=in` ada di URL, THEN THE TransactionPage SHALL mengaktifkan tab "Transaksi Masuk"
5. WHEN query param `?tab=out` ada di URL, THEN THE TransactionPage SHALL mengaktifkan tab "Transaksi Keluar"
6. IF query param `tab` tidak ada atau tidak valid, THEN THE TransactionPage SHALL menggunakan tab default: `'in'` untuk pengelola dan `'out'` untuk kasir
7. WHEN TransactionInForm memancarkan event `success`, THEN THE TransactionPage SHALL memanggil `notifStore.addTransactionAlert('transaction_in', ...)` dengan data yang sesuai
8. WHEN TransactionOutForm memancarkan event `success`, THEN THE TransactionPage SHALL memanggil `notifStore.addTransactionAlert('transaction_out', ...)` dengan data yang sesuai

---

### Requirement 8: TransactionInForm dan TransactionOutForm — Komponen Form

**User Story:** Sebagai pengembang, saya ingin logika form transaksi diekstrak ke komponen tersendiri, sehingga dapat digunakan di dalam TransactionPage tanpa duplikasi kode.

#### Acceptance Criteria

1. THE TransactionInForm SHALL mengimplementasikan logika yang identik dengan `TransactionInPage.vue` yang sudah ada, termasuk validasi, pemanggilan API, dan penanganan error
2. THE TransactionOutForm SHALL mengimplementasikan logika yang identik dengan `TransactionOutPage.vue` yang sudah ada, termasuk validasi, pemanggilan API, dan penanganan error
3. WHEN transaksi masuk berhasil disimpan, THEN THE TransactionInForm SHALL memancarkan event `success` dengan payload `{ productName, quantity, currentStock }`
4. WHEN transaksi keluar berhasil disimpan, THEN THE TransactionOutForm SHALL memancarkan event `success` dengan payload `{ productName, quantity, currentStock }`
5. WHEN stok produk berada di bawah atau sama dengan stok minimum setelah transaksi masuk, THEN THE TransactionInForm SHALL memanggil `notifStore.addLowStockAlert(...)` dengan data produk yang sesuai

---

### Requirement 9: ProductListPage — Tombol Akses Cepat

**User Story:** Sebagai pengguna, saya ingin ada tombol akses cepat ke halaman transaksi di halaman produk, sehingga saya dapat berpindah ke halaman transaksi dengan cepat tanpa melalui sidebar.

#### Acceptance Criteria

1. THE ProductListPage SHALL menampilkan tombol "Transaksi" di header, di sebelah kiri tombol "Tambah Barang"
2. WHEN pengguna mengklik tombol "Transaksi", THEN THE ProductListPage SHALL menavigasi ke `/transactions?tab=in`

---

### Requirement 10: Routing — Perubahan Konfigurasi Router

**User Story:** Sebagai pengembang, saya ingin konfigurasi router diperbarui untuk mendukung route baru dan redirect dari route lama, sehingga tidak ada broken link.

#### Acceptance Criteria

1. THE Router SHALL mendaftarkan route `/transactions` dengan komponen `TransactionPage` dan meta `roles: ['pengelola', 'kasir']`
2. THE Router SHALL mendaftarkan route `/notifications` dengan komponen `NotificationPage` dan meta `roles: ['pengelola', 'kasir']`
3. THE Router SHALL mendaftarkan redirect dari `/transactions/in` ke `/transactions?tab=in`
4. THE Router SHALL mendaftarkan redirect dari `/transactions/out` ke `/transactions?tab=out`
5. THE Layout SHALL mengganti dua item navigasi "Transaksi Masuk" dan "Transaksi Keluar" dengan satu item "Transaksi" yang mengarah ke `/transactions`
