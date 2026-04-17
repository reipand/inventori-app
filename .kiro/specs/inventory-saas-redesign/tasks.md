# Rencana Implementasi: Inventory SaaS Redesign

## Ikhtisar

Implementasi overhaul besar aplikasi inventori Cahaya Prima mencakup tujuh area utama: perbaikan sistem notifikasi, keamanan sesi (idle logout + JWT silent refresh), alur transaksi berbasis modal, sidebar modern, halaman login, desain responsif, dan micro-interactions.

Stack: Vue 3 + TypeScript + Pinia + Tailwind CSS + shadcn-vue (frontend), Laravel + tymon/jwt-auth (backend), Firebase Cloud Messaging (FCM).

---

## Tasks

- [x] 1. Perbaikan NotificationStore dan NotificationDropdown
  - [x] 1.1 Tambahkan state `error` dan `initialized` ke `stores/notification.ts`
    - Tambahkan `const error = ref<string | null>(null)` dan `const initialized = ref(false)` ke state
    - Perbarui `fetchNotifications()` agar set `error.value` saat gagal dan `initialized.value = true` saat berhasil
    - Tambahkan action `clearError()` untuk mereset error state
    - Pastikan `error` dan `initialized` di-expose dari store
    - _Persyaratan: 1.6_

  - [ ]* 1.2 Tulis property test untuk badge count (Properti 1)
    - **Properti 1: Badge menampilkan angka yang benar untuk sembarang N antara 1–9, "9+" untuk N > 9, dan tidak tampil untuk N = 0**
    - **Validates: Persyaratan 1.2, 4.2, 4.3, 4.4, 4.6**
    - Buat file `stores/__tests__/notificationBadge.property.test.ts`
    - Gunakan `fc.integer({ min: 0, max: 100 })` sebagai arbitrary untuk `unreadCount`
    - Tag: `// Feature: inventory-saas-redesign, Property 1: badge count`

  - [ ]* 1.3 Tulis property test untuk addRealtime prepend (Properti 2)
    - **Properti 2: `addRealtime(N)` selalu menempatkan N di `notifications[0]` dan menambah panjang array sebesar 1**
    - **Validates: Persyaratan 1.5**
    - Tambahkan ke file test yang sama atau buat `stores/__tests__/notificationRealtime.property.test.ts`
    - Tag: `// Feature: inventory-saas-redesign, Property 2: addRealtime prepend`

  - [x] 1.4 Perbarui `NotificationDropdown.vue` untuk menampilkan error state dan tombol retry
    - Tampilkan pesan "Gagal memuat notifikasi" dan tombol "Coba Ulang" saat `store.error` tidak null
    - Tampilkan pesan "Tidak ada notifikasi" dengan ikon lonceng saat daftar kosong
    - Tambahkan pengelompokan notifikasi berdasarkan tanggal: "Hari ini", "Kemarin", "Lebih lama"
    - Terapkan lebar 360px, `rounded-xl`, `shadow-lg`, dan animasi `fade` + `slide-down` 150ms
    - Baris notifikasi belum dibaca: latar `bg-blue-50`; hover: transisi warna 150ms
    - _Persyaratan: 1.1, 1.3, 1.6, 5.1, 5.2, 5.3, 5.4, 5.5, 5.8_

  - [ ]* 1.5 Tulis unit test untuk NotificationStore
    - Test `fetchNotifications()` mengisi state dan set `initialized = true`
    - Test `fetchNotifications()` gagal → `error.value` terisi, `initialized` tetap false
    - Test `markAsRead()` mengubah `is_read = true` pada notifikasi yang tepat
    - Test `markAllAsRead()` mengubah semua `is_read = true`
    - _Persyaratan: 1.1, 1.2, 5.6, 5.7_

- [x] 2. Perbaikan `useFCM.ts`
  - [x] 2.1 Tambahkan guard `handlerRegistered` untuk mencegah duplikasi listener `onMessage`
    - Deklarasikan `let handlerRegistered = false` di module scope (di luar fungsi)
    - Wrap pemanggilan `onMessage(messaging, handler)` dengan pengecekan `if (!handlerRegistered)`
    - Set `handlerRegistered = true` setelah listener terdaftar
    - _Persyaratan: 2.5_

  - [x] 2.2 Tambahkan pengecekan `visibilityState` di foreground handler
    - Di dalam handler `onMessage`, tambahkan `if (document.visibilityState !== 'visible') return`
    - Pastikan Toast dan `addRealtime` hanya dipanggil saat tab aktif
    - _Persyaratan: 2.4, 2.5_

  - [x] 2.3 Tambahkan fungsi `unregisterDevice()` untuk dipanggil saat logout
    - Implementasikan `unregisterDevice()` yang memanggil `DELETE /api/devices/token`
    - Panggil `unregisterDevice()` dari `authStore.logout()` atau interceptor logout
    - _Persyaratan: 3.4_

  - [ ]* 2.4 Tulis property test untuk FCM token registration (Properti 3)
    - **Properti 3: Untuk sembarang FCM token string T yang valid, `registerDevice(T)` dipanggil tepat satu kali setelah izin diberikan**
    - **Validates: Persyaratan 2.2, 3.1**
    - Buat file `composables/__tests__/useFCM.property.test.ts`
    - Mock Firebase dan gunakan `fc.string({ minLength: 10 })` sebagai arbitrary token
    - Tag: `// Feature: inventory-saas-redesign, Property 3: FCM token registration`

  - [ ]* 2.5 Tulis unit test untuk useFCM
    - Test `init()` tidak melempar error jika Firebase tidak dikonfigurasi (VITE_FIREBASE_* kosong)
    - Test permission ditolak → `console.warn` dipanggil, tidak ada error
    - Test `handlerRegistered` mencegah duplikasi listener saat `init()` dipanggil dua kali
    - _Persyaratan: 2.6, 2.7_

- [x] 3. Buat composable `useIdleLogout.ts`
  - [x] 3.1 Buat file `composables/useIdleLogout.ts` dengan logika deteksi idle
    - Implementasikan interface `UseIdleLogoutOptions` dengan `timeoutMs` (default 15 menit) dan `onIdle` callback
    - Daftarkan event listener untuk `mousemove`, `click`, `scroll`, `keydown` pada `document`
    - Gunakan `setInterval` setiap 10 detik untuk memeriksa `Date.now() - lastActivity > timeoutMs`
    - Pantau `visibilitychange`: saat tab kembali aktif, periksa apakah sudah idle
    - Return `{ lastActivity, isIdle, reset, stop }`
    - _Persyaratan: 6.1, 6.4, 6.5, 6.6, 6.7_

  - [x] 3.2 Integrasikan `useIdleLogout` ke `Layout.vue`
    - Panggil `useIdleLogout({ timeoutMs: 15 * 60 * 1000 })` di `onMounted` Layout
    - Saat `onIdle` terpicu: panggil `authStore.logout()`, tampilkan Toast "Sesi berakhir karena tidak aktif" (tipe `warning`), redirect ke `/login`
    - Panggil `stop()` di `onUnmounted`
    - Pastikan hanya aktif saat `authStore.isAuthenticated === true`
    - _Persyaratan: 6.2, 6.3, 6.5_

  - [ ]* 3.3 Tulis property test untuk idle logout (Properti 4)
    - **Properti 4: Untuk sembarang durasi T >= 900.000ms, logout dipanggil; untuk T < 900.000ms, logout tidak dipanggil**
    - **Validates: Persyaratan 6.2, 6.7**
    - Buat file `composables/__tests__/useIdleLogout.property.test.ts`
    - Gunakan `fc.integer({ min: 900000, max: 3600000 })` dan `fc.integer({ min: 0, max: 899999 })`
    - Gunakan fake timers (`vi.useFakeTimers()`)
    - Tag: `// Feature: inventory-saas-redesign, Property 4: idle logout trigger`

  - [ ]* 3.4 Tulis property test untuk reset timer idle (Properti 5)
    - **Properti 5: Untuk sembarang event aktivitas, `lastActivity` diperbarui ke timestamp saat event terjadi**
    - **Validates: Persyaratan 6.1, 6.4**
    - Tambahkan ke file test yang sama
    - Gunakan `fc.constantFrom('mousemove', 'click', 'scroll', 'keydown')` sebagai arbitrary event
    - Tag: `// Feature: inventory-saas-redesign, Property 5: idle timer reset`

  - [ ]* 3.5 Tulis unit test untuk useIdleLogout
    - Test event listeners terdaftar saat composable diinisialisasi
    - Test event listeners dihapus setelah `stop()` dipanggil
    - Test composable tidak aktif saat pengguna belum login
    - _Persyaratan: 6.1, 6.5_

- [x] 4. Checkpoint — Pastikan semua test lulus
  - Pastikan semua test lulus, tanyakan ke pengguna jika ada pertanyaan.

- [x] 5. Implementasi JWT Silent Refresh Interceptor di `authService.ts`
  - [x] 5.1 Tambahkan interceptor request Axios untuk silent refresh proaktif
    - Tambahkan `axios.interceptors.request.use` yang memeriksa sisa waktu token sebelum request dikirim
    - Jika sisa waktu < 2 menit, panggil `POST /api/auth/refresh` terlebih dahulu
    - Simpan token baru ke `localStorage` setelah refresh berhasil
    - _Persyaratan: 7.4, 7.5_

  - [x] 5.2 Tambahkan interceptor response Axios untuk retry setelah 401
    - Implementasikan pattern `isRefreshing` + `failedQueue` sesuai desain
    - Jika response 401 dan bukan dari `/auth/logout` atau `/auth/refresh`, coba refresh sekali
    - Jika refresh berhasil: proses antrian dan ulangi request asli
    - Jika refresh gagal: panggil `removeToken()`, `removeUser()`, redirect ke `/login`
    - _Persyaratan: 7.6, 7.7_

  - [ ]* 5.3 Tulis property test untuk silent refresh proaktif (Properti 6)
    - **Properti 6: Untuk sembarang access token dengan sisa waktu T < 2 menit, interceptor memanggil `POST /api/auth/refresh` sebelum request asli**
    - **Validates: Persyaratan 7.4**
    - Buat file `services/__tests__/authService.property.test.ts`
    - Gunakan `fc.integer({ min: 0, max: 119 })` (detik) sebagai arbitrary sisa waktu token
    - Tag: `// Feature: inventory-saas-redesign, Property 6: silent refresh proactive`

  - [ ]* 5.4 Tulis property test untuk penyimpanan token baru (Properti 7)
    - **Properti 7: Untuk sembarang token baru T dari endpoint refresh, `localStorage.getItem('auth_token')` mengembalikan T setelah refresh berhasil**
    - **Validates: Persyaratan 7.5**
    - Gunakan `fc.string({ minLength: 20 })` sebagai arbitrary token
    - Tag: `// Feature: inventory-saas-redesign, Property 7: token storage after refresh`

  - [ ]* 5.5 Tulis property test untuk retry 401 tepat satu kali (Properti 8)
    - **Properti 8: Untuk sembarang request yang mengembalikan 401 (bukan dari `/auth/logout`), interceptor mencoba refresh tepat satu kali**
    - **Validates: Persyaratan 7.7**
    - Gunakan `fc.webUrl()` sebagai arbitrary URL endpoint (exclude `/auth/logout`)
    - Tag: `// Feature: inventory-saas-redesign, Property 8: 401 retry once`

  - [ ]* 5.6 Tulis unit test untuk authService interceptor
    - Test token tersimpan di localStorage setelah login
    - Test token dihapus setelah logout
    - Test 401 dari `/auth/logout` tidak memicu refresh
    - Test refresh token expired → logout + redirect
    - _Persyaratan: 7.3, 7.6, 7.7_

- [x] 6. Buat komponen `TransactionModal.vue`
  - [x] 6.1 Buat file `components/TransactionModal.vue` dengan struktur dasar
    - Implementasikan props: `modelValue: boolean`, `mode: 'masuk' | 'keluar'`, `product: Product | null`
    - Implementasikan emits: `update:modelValue` dan `saved`
    - Gunakan `<Teleport to="body">` untuk overlay dan dialog
    - Tambahkan atribut aksesibilitas: `role="dialog"`, `aria-modal="true"`, `aria-labelledby`
    - _Persyaratan: 9.5, 14.2_

  - [x] 6.2 Implementasikan form fields dan validasi di TransactionModal
    - Field nama produk (read-only, pre-fill dari prop `product`)
    - Input jumlah (number, min=1, validasi inline "Jumlah harus lebih dari 0")
    - Input tanggal (date, default hari ini)
    - Field supplier (text, hanya tampil saat `mode === 'masuk'`)
    - Field harga beli (number, hanya tampil saat `mode === 'masuk'`)
    - Tombol "Batal" dan "Simpan"
    - _Persyaratan: 9.6, 9.9_

  - [x] 6.3 Implementasikan logika submit dan error handling di TransactionModal
    - Saat "Simpan" diklik: validasi form, kirim ke endpoint API yang sesuai
    - Saat berhasil: emit `saved` dengan data produk terbaru, tutup modal, tampilkan Toast sukses
    - Saat gagal (stok tidak cukup): tampilkan pesan error dari server, modal tetap terbuka
    - _Persyaratan: 9.7, 9.8, 9.10_

  - [x] 6.4 Implementasikan focus trap dan keyboard navigation di TransactionModal
    - Pindahkan fokus ke elemen pertama saat modal terbuka
    - Implementasikan focus trap: `Tab` dan `Shift+Tab` tidak keluar dari modal
    - Tutup modal saat tombol `Escape` ditekan
    - _Persyaratan: 14.3, 14.4, 14.5_

  - [x] 6.5 Tambahkan animasi dan responsive behavior ke TransactionModal
    - Animasi `scale-in` (0.95 → 1.0) + `fade` saat buka, durasi 150ms
    - Mobile: `fixed inset-x-0 bottom-0`, `max-h-[90vh]`, scroll internal
    - Desktop: `fixed inset-0 flex items-center justify-center`, `max-w-md`
    - _Persyaratan: 9.5, 12.6, 13.3_

  - [ ]* 6.6 Tulis property test untuk TransactionModal (Properti 9 dan 10)
    - **Properti 9: Untuk sembarang produk P, modal terbuka dengan `product.id === P.id` dan nama produk sudah terisi**
    - **Validates: Persyaratan 9.3, 9.4**
    - **Properti 10: Untuk sembarang integer N <= 0, submit ditolak dan tidak ada request API yang dikirim**
    - **Validates: Persyaratan 9.9**
    - Buat file `components/__tests__/TransactionModal.property.test.ts`
    - Tag: `// Feature: inventory-saas-redesign, Property 9 & 10`

  - [ ]* 6.7 Tulis unit test untuk TransactionModal
    - Test modal terbuka dengan mode yang benar (masuk/keluar)
    - Test klik overlay menutup modal
    - Test Escape key menutup modal
    - Test focus trap berfungsi
    - Test ARIA attributes ada dan benar
    - _Persyaratan: 9.5, 9.8, 14.2, 14.3, 14.4, 14.5_

- [x] 7. Perbarui `ProductListPage.vue` — integrasi TransactionModal
  - [x] 7.1 Tambahkan state dan tombol aksi header di ProductListPage
    - Tambahkan state: `showTransactionModal`, `selectedProduct`, `transactionMode`
    - Tambahkan tombol "[+ Barang Masuk]" dan "[- Barang Keluar]" di header halaman
    - Klik tombol header: set `selectedProduct = null`, set mode, buka modal
    - _Persyaratan: 9.2_

  - [x] 7.2 Tambahkan tombol transaksi per baris di tabel produk
    - Tambahkan kolom aksi dengan tombol kecil "↑ Masuk" dan "↓ Keluar" di samping Edit/Hapus
    - Klik tombol baris: set `selectedProduct` ke produk baris tersebut, set mode, buka modal
    - Pastikan touch target minimal 44×44px
    - _Persyaratan: 9.3, 9.4, 12.3_

  - [x] 7.3 Implementasikan handler `onSaved` untuk update stok tanpa reload
    - Terima `updatedProduct` dari emit `saved` TransactionModal
    - Update `products.value` dengan mengganti item yang sesuai berdasarkan `id`
    - Tidak perlu reload halaman atau re-fetch semua produk
    - _Persyaratan: 9.11_

  - [ ]* 7.4 Tulis property test untuk update stok setelah transaksi (Properti 11)
    - **Properti 11: Untuk sembarang transaksi masuk dengan quantity Q pada produk P dengan stok S, stok P menjadi S + Q setelah modal berhasil menyimpan**
    - **Validates: Persyaratan 9.11**
    - Buat file `pages/__tests__/ProductListPage.property.test.ts`
    - Gunakan `fc.integer({ min: 1, max: 1000 })` sebagai arbitrary quantity dan stok awal
    - Tag: `// Feature: inventory-saas-redesign, Property 11: stock update after transaction`

  - [x] 7.5 Tambahkan tabel horizontal-scroll untuk mobile
    - Bungkus tabel dengan `overflow-x-auto` pada Breakpoint_Mobile
    - Pastikan semua kolom tetap terbaca di layar 360px
    - _Persyaratan: 12.5_

- [x] 8. Checkpoint — Pastikan semua test lulus
  - Pastikan semua test lulus, tanyakan ke pengguna jika ada pertanyaan.

- [x] 9. Perbarui `Layout.vue` — sidebar redesign
  - [x] 9.1 Hapus menu "Transaksi" dari `NAV_ITEMS` di Layout.vue
    - Hapus entri menu Transaksi dari array navigasi
    - Pastikan route `/transactions` masih ada di router (untuk riwayat), hanya tidak di sidebar
    - _Persyaratan: 9.1_

  - [x] 9.2 Perbarui struktur dan visual sidebar
    - Ubah lebar sidebar expanded menjadi `w-[260px]`
    - Latar belakang `bg-white`, border kanan `border-r border-gray-200`
    - Ganti semua inline SVG dengan ikon Lucide
    - Active state: `bg-primary/10 text-primary`; hover: transisi warna 150ms
    - Struktur tiga bagian: logo di atas, menu di tengah, profil pengguna di bawah
    - Tampilkan nama, email, dan badge peran (Pengelola/Kasir) di bagian bawah
    - _Persyaratan: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7_

  - [x] 9.3 Implementasikan sidebar responsive (drawer mobile)
    - Desktop (≥ 1024px): sidebar selalu terlihat, fixed di sisi kiri
    - Mobile (< 1024px): sidebar tersembunyi default, muncul sebagai drawer via hamburger
    - Tambahkan overlay gelap di belakang drawer; klik overlay menutup sidebar
    - Tambahkan tombol hamburger di Topbar untuk mobile
    - _Persyaratan: 10.8, 10.9, 10.10, 10.11, 12.7, 12.8_

  - [x] 9.4 Integrasikan `useIdleLogout` di Layout.vue (jika belum dilakukan di task 3.2)
    - Pastikan `useIdleLogout` sudah terpanggil di `onMounted` Layout
    - _Persyaratan: 6.2, 6.3_

- [x] 10. Perbarui `LoginPage.vue` — perbaikan redirect kasir
  - [x] 10.1 Perbaiki logika redirect setelah login berhasil
    - Ubah redirect kasir dari `/transactions/out` menjadi `/products`
    - Pengelola tetap diarahkan ke `/dashboard`
    - _Persyaratan: 11.7, 11.8_

  - [x] 10.2 Verifikasi atribut aksesibilitas LoginPage
    - Pastikan field email memiliki `autocomplete="email"`
    - Pastikan field password memiliki `autocomplete="current-password"`
    - Pastikan gambar dekoratif memiliki `aria-hidden="true"`
    - _Persyaratan: 14.6, 14.7_

- [x] 11. Tambahkan micro-interactions CSS ke `app.css`
  - [x] 11.1 Tambahkan keyframe `badge-bounce` untuk NotificationBell
    - Implementasikan `@keyframes badge-bounce` (scale 1 → 1.3 → 1, durasi 400ms)
    - Tambahkan class `.badge-bounce` yang menggunakan animasi tersebut
    - _Persyaratan: 4.5, 13.1_

  - [x] 11.2 Tambahkan keyframe `modal-scale-in` untuk TransactionModal
    - Implementasikan `@keyframes modal-scale-in` (opacity 0 + scale 0.95 → opacity 1 + scale 1, durasi 150ms)
    - Tambahkan class `.modal-scale-in` yang menggunakan animasi tersebut
    - _Persyaratan: 9.5, 13.3_

  - [x] 11.3 Verifikasi dan lengkapi animasi yang sudah ada
    - Pastikan animasi dropdown `fade` + `slide-down` 150ms sudah ada atau tambahkan
    - Pastikan Toast `slide-in` / `slide-out` 200ms sudah ada atau tambahkan
    - Pastikan page transition `fade` 150ms sudah ada atau tambahkan
    - Semua transisi menggunakan `ease` atau `ease-in-out`, bukan `linear`
    - _Persyaratan: 5.2, 13.2, 13.4, 13.5, 13.6, 13.7_

- [x] 12. Checkpoint akhir — Pastikan semua test lulus
  - Pastikan semua test lulus, tanyakan ke pengguna jika ada pertanyaan.

---

## Catatan

- Task bertanda `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Setiap task mereferensikan persyaratan spesifik untuk keterlacakan
- Property test menggunakan library **fast-check** yang sudah tersedia di project
- Unit test menggunakan **Vitest** (jalankan dengan `npx vitest --run`)
- Checkpoint memastikan validasi inkremental sebelum melanjutkan ke area berikutnya
- Properti 1–11 dari design.md semuanya tercakup dalam sub-task opsional di atas
