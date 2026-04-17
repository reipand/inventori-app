# Rencana Implementasi: Inventory Monitoring Toko Agen Minuman

## Ikhtisar

Implementasi dilakukan secara bertahap: setup proyek → backend Laravel (migrasi, model, API) → frontend React (halaman dan komponen) → integrasi dan pengujian. Setiap tahap membangun di atas tahap sebelumnya hingga semua fitur terhubung.

**Stack teknologi (all-in-one di root project):**
- Framework: Laravel 13, PHP ^8.3
- Frontend: Vite + Vue 3 + TypeScript + Tailwind CSS CLI + shadcn/ui di `resources/js/`
- Backend: Laravel REST API
- Database: MySQL
- Autentikasi: Laravel Sanctum
- Export: PDF via `barryvdh/laravel-dompdf`
- Base API URL lokal: `http://127.0.0.1:8000/api`

---

## Tasks

- [x] 1. Setup struktur proyek
  - [x] 1.1 Inisialisasi proyek Laravel sebagai backend REST API
    - Buat proyek Laravel 13 (PHP ^8.3) di root project
    - Konfigurasi `.env` untuk koneksi MySQL
    - Install package: `laravel/sanctum`, `barryvdh/laravel-dompdf`
    - _Requirements: 7.1, 7.2_

  - [x] 1.2 Setup Vite + Vue 3 + TypeScript + shadcn/ui
    - Install npm packages: `vue`, `@vitejs/plugin-vue`, `axios`, `vue-router` (dependencies), `fast-check` (dev dependency)
    - Install shadcn/ui untuk Vue
    - Konfigurasi Tailwind CSS CLI dan path alias `@` di `vite.config.js` dan `tsconfig.json`
    - Buat struktur folder: `resources/js/pages/`, `resources/js/components/`, `resources/js/services/`, `resources/js/types/`
    - Setup `resources/js/app.ts` sebagai entry point Vue SPA
    - _Requirements: 7.1_

- [ ] 2. Migrasi database dan model Laravel
  - [x] 2.1 Buat migrasi dan model untuk semua tabel
    - Buat migration untuk tabel `users`, `categories`, `products`, `transactions`, `audit_logs`
    - Sesuaikan skema dengan definisi di dokumen desain (UUID, constraint, indeks)
    - Buat Eloquent Model untuk setiap tabel dengan `$fillable` dan relasi yang sesuai
    - _Requirements: 1.1, 2.1, 3.2, 4.2, 8.1_

  - [x] 2.2 Buat database seeder untuk data awal
    - Seeder akun Pengelola default
    - Seeder beberapa kategori dan produk contoh
    - _Requirements: 7.1_

- [x] 3. Autentikasi dan manajemen pengguna (Backend)
  - [x] 3.1 Implementasi autentikasi Sanctum
    - Konfigurasi `laravel/sanctum`
    - Buat `AuthController` dengan method: `login`, `logout`, `me`, `changePassword`
    - Implementasi middleware `auth:sanctum` dan middleware `must_change_password`
    - Daftarkan route: `POST /api/auth/login`, `POST /api/auth/logout`, `POST /api/auth/change-password`, `GET /api/auth/me`
    - _Requirements: 7.2, 7.3, 7.4, 7.5, 7.10_

  - [ ]* 3.2 Tulis property test untuk autentikasi
    - **Property 28: Autentikasi dengan kredensial valid**
    - **Property 29: Penolakan kredensial tidak valid**
    - **Property 30: Proteksi endpoint tanpa autentikasi**
    - **Validates: Requirements 7.2, 7.3, 7.4**

  - [x] 3.3 Implementasi manajemen pengguna
    - Buat `UserController` dengan method: `index`, `store`, `deactivate`
    - Implementasi middleware otorisasi peran `pengelola`
    - Logika pembuatan akun: generate password sementara, set `must_change_password = true`
    - Logika deaktivasi: set `is_active = false`, blacklist token aktif
    - Daftarkan route: `GET /api/users`, `POST /api/users`, `PUT /api/users/:id/deactivate`
    - _Requirements: 7.1, 7.6, 7.7, 7.8, 7.9, 7.11_

  - [ ]* 3.4 Tulis property test untuk otorisasi dan manajemen pengguna
    - **Property 31: Otorisasi berbasis peran Kasir**
    - **Property 32: Keunikan email pengguna**
    - **Property 33: Flag ganti password pertama kali**
    - **Property 34: Pencabutan akses akun yang dinonaktifkan**
    - **Validates: Requirements 7.6, 7.7, 7.9, 7.10, 7.11**

- [x] 4. Checkpoint — Pastikan semua test autentikasi lulus
  - Jalankan `php artisan test` untuk memverifikasi semua test autentikasi lulus
  - Tanyakan kepada pengguna jika ada pertanyaan sebelum melanjutkan.

- [x] 5. Manajemen kategori (Backend)
  - [x] 5.1 Implementasi `CategoryController`
    - Method: `index`, `store`, `update`, `destroy`
    - Validasi: nama unik, nama tidak boleh kosong
    - Proteksi hapus: tolak jika masih ada produk terkait (HTTP 422 `BUSINESS_RULE_VIOLATION`)
    - Catat audit log untuk setiap operasi create/update/delete
    - Daftarkan route: `GET /api/categories`, `POST /api/categories`, `PUT /api/categories/:id`, `DELETE /api/categories/:id`
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 8.1_

  - [ ]* 5.2 Tulis property test untuk kategori
    - **Property 10: Round-trip data kategori**
    - **Property 11: Keunikan nama kategori**
    - **Property 12: Cascade update nama kategori ke produk**
    - **Property 13: Proteksi hapus kategori berisi produk**
    - **Property 14: Hapus kategori kosong**
    - **Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5, 2.6**

- [x] 6. Manajemen produk (Backend)
  - [x] 6.1 Implementasi `ProductController`
    - Method: `index` (dengan query param `search`, `category_id`, pagination), `store`, `show`, `update`, `destroy`, `lowStock`
    - Validasi: SKU unik, field wajib tidak kosong, `min_stock >= 0`
    - Proteksi hapus: tolak jika ada riwayat transaksi (HTTP 422 `BUSINESS_RULE_VIOLATION`)
    - Catat audit log untuk setiap operasi create/update/delete
    - Daftarkan route sesuai dokumen desain
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10, 5.4, 8.1_

  - [ ]* 6.2 Tulis property test untuk produk
    - **Property 1: Round-trip data produk**
    - **Property 2: Keunikan SKU**
    - **Property 3: Validasi field wajib produk**
    - **Property 4: Update produk tersimpan**
    - **Property 5: Hapus produk tanpa transaksi**
    - **Property 6: Proteksi hapus produk bertransaksi**
    - **Property 7: Pencarian produk**
    - **Property 8: Filter produk berdasarkan kategori**
    - **Property 9: Validasi stok minimum tidak negatif**
    - **Validates: Requirements 1.1–1.10**

  - [ ]* 6.3 Tulis unit test untuk produk stok rendah
    - **Property 22: Daftar produk stok rendah terurut**
    - Test endpoint `GET /api/products/low-stock` mengembalikan produk dengan `current_stock <= min_stock` diurutkan berdasarkan selisih terkecil
    - **Validates: Requirements 5.4**

- [x] 7. Manajemen transaksi stok (Backend)
  - [x] 7.1 Implementasi `TransactionController`
    - Method: `index` (filter tanggal, jenis, produk, pagination), `storeIn`, `storeOut`
    - Gunakan database transaction untuk update `current_stock` secara atomik
    - Validasi: jumlah harus bilangan bulat positif, stok tidak boleh negatif
    - Response harus mengandung `current_stock` terbaru dan flag notifikasi stok rendah
    - Catat audit log untuk setiap transaksi
    - Daftarkan route: `GET /api/transactions`, `POST /api/transactions/in`, `POST /api/transactions/out`
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 5.2, 5.5, 8.2_

  - [ ]* 7.2 Tulis property test untuk transaksi stok
    - **Property 15: Invariant aritmatika stok**
    - **Property 16: Round-trip data transaksi**
    - **Property 17: Validasi jumlah transaksi**
    - **Property 18: Response API mengandung stok terbaru**
    - **Property 19: Proteksi stok negatif**
    - **Property 20: Klasifikasi status stok**
    - **Property 21: Trigger notifikasi stok rendah**
    - **Validates: Requirements 3.1–3.6, 4.1–4.6, 5.1–5.3, 5.5**

- [x] 8. Checkpoint — Pastikan semua test backend lulus
  - Jalankan `php artisan test` untuk memverifikasi semua test produk, kategori, dan transaksi lulus
  - Tanyakan kepada pengguna jika ada pertanyaan sebelum melanjutkan.

- [x] 9. Laporan dan audit trail (Backend)
  - [x] 9.1 Implementasi `ReportController`
    - Method: `stockSummary` (ringkasan stok + nilai total), `export` (PDF stok atau transaksi via `barryvdh/laravel-dompdf`)
    - Validasi filter tanggal: tolak jika tanggal awal > tanggal akhir
    - Nama file PDF harus mengandung tanggal ekspor
    - Daftarkan route: `GET /api/reports/stock-summary`, `GET /api/reports/export`
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_

  - [ ]* 9.2 Tulis property test untuk laporan
    - **Property 23: Kelengkapan data laporan ringkasan stok**
    - **Property 24: Filter transaksi berdasarkan rentang tanggal**
    - **Property 25: Validasi urutan tanggal filter**
    - **Property 26: Filter transaksi berdasarkan jenis**
    - **Property 27: Ekspor CSV valid**
    - **Validates: Requirements 6.1–6.6**

  - [x] 9.3 Implementasi `AuditLogController`
    - Method: `index` (filter jenis aksi, pengguna, rentang tanggal, pagination)
    - Daftarkan route: `GET /api/audit-logs`
    - _Requirements: 8.3, 8.4_

  - [ ]* 9.4 Tulis property test untuk audit trail
    - **Property 35: Kelengkapan audit log**
    - **Property 36: Filter audit trail**
    - **Validates: Requirements 8.1, 8.2, 8.3**

- [x] 10. Halaman autentikasi (Frontend)
  - [x] 10.1 Implementasi halaman login dan ganti password
    - Buat `resources/js/pages/LoginPage.vue` dengan form email/password menggunakan shadcn/ui, tampilkan pesan error dari API
    - Buat `resources/js/pages/ChangePasswordPage.vue` yang muncul otomatis jika `must_change_password = true`
    - Buat `resources/js/services/authService.ts` untuk memanggil endpoint autentikasi (`http://127.0.0.1:8000/api`)
    - Simpan Sanctum token di `localStorage`
    - Buat `resources/js/components/PrivateRoute.vue` untuk redirect ke login jika belum autentikasi
    - _Requirements: 7.2, 7.3, 7.4, 7.5, 7.10_

  - [x] 10.2 Implementasi routing dan layout utama
    - Konfigurasi `vue-router` di `resources/js/app.ts` dengan semua route halaman
    - Buat `resources/js/components/Layout.vue` dengan sidebar navigasi yang menyesuaikan peran pengguna
    - Implementasi guard route berdasarkan peran (Pengelola vs Kasir)
    - Tampilkan pesan "Anda tidak memiliki izin" jika Kasir mengakses halaman Pengelola
    - _Requirements: 7.1, 7.6, 7.7_

- [x] 11. Halaman manajemen kategori dan produk (Frontend)
  - [x] 11.1 Implementasi halaman manajemen kategori
    - Buat `resources/js/pages/CategoryPage.vue` dengan tabel daftar kategori, form tambah/edit menggunakan shadcn/ui `Dialog`, dan tombol hapus
    - Tampilkan pesan error dari API (nama duplikat, tidak bisa hapus)
    - Buat `resources/js/services/categoryService.ts` untuk memanggil endpoint kategori
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

  - [x] 11.2 Implementasi halaman daftar dan form produk
    - Buat `resources/js/pages/ProductListPage.vue` dengan tabel shadcn/ui, input pencarian (nama/SKU), filter dropdown kategori, dan indikator status stok (normal/rendah/habis) menggunakan shadcn/ui `Badge` dengan warna berbeda
    - Buat `resources/js/pages/ProductFormPage.vue` untuk tambah dan edit produk
    - Buat `resources/js/services/productService.ts` untuk memanggil endpoint produk
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10, 5.1, 5.3_

- [x] 12. Halaman transaksi stok (Frontend)
  - [x] 12.1 Implementasi halaman transaksi masuk
    - Buat `resources/js/pages/TransactionInPage.vue` dengan form: pilih produk, jumlah, tanggal, nama supplier, harga beli menggunakan shadcn/ui `Select` dan `Input`
    - Tampilkan stok terbaru setelah transaksi berhasil tanpa reload halaman
    - Tampilkan notifikasi peringatan jika stok turun ke bawah minimum
    - Buat `resources/js/services/transactionService.ts` untuk memanggil endpoint transaksi
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 5.2_

  - [x] 12.2 Implementasi halaman transaksi keluar
    - Buat `resources/js/pages/TransactionOutPage.vue` dengan form: pilih produk, jumlah, tanggal, harga jual
    - Tampilkan stok terbaru setelah transaksi berhasil tanpa reload halaman
    - Tampilkan error jika jumlah melebihi stok tersedia
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6_

- [x] 13. Halaman monitoring stok dan laporan (Frontend)
  - [x] 13.1 Implementasi halaman stok rendah dan dashboard
    - Buat `resources/js/pages/LowStockPage.vue` yang menampilkan produk dengan stok <= stok_minimum, diurutkan berdasarkan selisih terkecil, menggunakan shadcn/ui `Badge` untuk status
    - Buat `resources/js/pages/DashboardPage.vue` dengan ringkasan menggunakan shadcn/ui `Card`: total produk, produk stok rendah, produk stok habis
    - _Requirements: 5.1, 5.3, 5.4_

  - [x] 13.2 Implementasi halaman laporan
    - Buat `resources/js/pages/ReportPage.vue` dengan tab ringkasan stok dan riwayat transaksi
    - Filter rentang tanggal, filter jenis transaksi, filter produk
    - Tampilkan nilai total stok (unit × harga beli)
    - Tombol ekspor PDF yang memanggil endpoint ekspor dan mengunduh file
    - Validasi tanggal awal tidak boleh lebih besar dari tanggal akhir di sisi frontend
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_

- [x] 14. Halaman audit trail dan manajemen pengguna (Frontend)
  - [x] 14.1 Implementasi halaman audit trail
    - Buat `resources/js/pages/AuditTrailPage.vue` dengan tabel shadcn/ui riwayat perubahan
    - Filter berdasarkan jenis aksi, nama pengguna, dan rentang tanggal
    - _Requirements: 8.3, 8.4_

  - [x] 14.2 Implementasi halaman manajemen pengguna
    - Buat `resources/js/pages/UserManagementPage.vue` dengan daftar pengguna, form buat akun baru menggunakan shadcn/ui `Dialog`, dan tombol nonaktifkan
    - Tampilkan pesan error dari API (email duplikat)
    - _Requirements: 7.1, 7.8, 7.9, 7.11_

- [x] 15. Checkpoint akhir — Pastikan semua test lulus dan fitur terintegrasi
  - Jalankan `php artisan test` untuk semua test backend
  - Jalankan `npm run test -- --run` untuk semua test frontend
  - Verifikasi semua endpoint terdaftar dengan `php artisan route:list`
  - Tanyakan kepada pengguna jika ada pertanyaan sebelum dianggap selesai.

---

## Catatan

- Task bertanda `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Setiap task mereferensikan requirements spesifik untuk keterlacakan
- Property test memvalidasi properti universal yang harus berlaku untuk semua input valid
- Unit test memvalidasi skenario spesifik dan edge case
- Semua operasi update stok harus menggunakan database transaction untuk mencegah race condition
