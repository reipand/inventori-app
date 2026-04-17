# Tasks: Notification and Transaction UI

## Task List

---

### BAGIAN A: Backend Laravel

- [x] A1. Buat migration database
  - [x] A1.1 Buat migration `create_notifications_table` dengan kolom: id, user_id (FK), title, message, type (enum: success/warning/danger/info), link (nullable), is_read (boolean default false), timestamps, index (user_id, is_read), index (user_id, created_at)
  - [x] A1.2 Buat migration `create_user_devices_table` dengan kolom: id, user_id (FK), fcm_token (unique), device_info (json nullable), timestamps, index user_id

- [x] A2. Buat Model
  - [x] A2.1 Buat `app/Models/Notification.php` dengan fillable, cast is_read ke boolean, relasi belongsTo User
  - [x] A2.2 Buat `app/Models/UserDevice.php` dengan fillable, cast device_info ke array, relasi belongsTo User

- [x] A3. Buat NotificationService
  - [x] A3.1 Buat `app/Services/NotificationService.php`
  - [x] A3.2 Implementasikan method `sendToUser(int $userId, array $payload): Notification` yang menyimpan ke DB lalu mengirim FCM ke semua device user
  - [x] A3.3 Implementasikan method private `sendFcm(string $token, array $payload, int $notifId)` menggunakan `kreait/laravel-firebase`

- [x] A4. Buat NotificationController
  - [x] A4.1 Buat `app/Http/Controllers/NotificationController.php`
  - [x] A4.2 Implementasikan `index()` — GET /api/notifications, ambil notif user dari JWT, paginate 20, order by created_at desc
  - [x] A4.3 Implementasikan `send()` — POST /api/notifications/send, validasi input, panggil NotificationService::sendToUser
  - [x] A4.4 Implementasikan `markRead(int $id)` — PATCH /api/notifications/{id}/read, validasi user_id dari JWT
  - [x] A4.5 Implementasikan `markAllRead()` — PATCH /api/notifications/read-all, update semua notif user yang is_read=false

- [x] A5. Buat DeviceController
  - [x] A5.1 Buat `app/Http/Controllers/DeviceController.php`
  - [x] A5.2 Implementasikan `register()` — POST /api/devices/register, upsert berdasarkan fcm_token, set user_id dari JWT
  - [x] A5.3 Implementasikan `unregister()` — DELETE /api/devices/unregister, hapus token milik user yang login

- [x] A6. Daftarkan route API
  - [x] A6.1 Tambahkan route group dengan middleware `auth:api` di `routes/api.php`
  - [x] A6.2 Tambahkan `GET /api/notifications`, `POST /api/notifications/send`, `PATCH /api/notifications/{id}/read`, `PATCH /api/notifications/read-all`
  - [x] A6.3 Tambahkan `POST /api/devices/register`, `DELETE /api/devices/unregister`
  - [x] A6.4 Tambahkan rate limiting 60/menit pada `POST /api/notifications/send`

- [x] A7. Install dan konfigurasi kreait/laravel-firebase
  - [x] A7.1 Jalankan `composer require kreait/laravel-firebase`
  - [x] A7.2 Publish config: `php artisan vendor:publish --provider="Kreait\Laravel\Firebase\ServiceProvider"`
  - [x] A7.3 Tambahkan `FIREBASE_CREDENTIALS` dan `FIREBASE_PROJECT_ID` ke `.env` dan `.env.example`
  - [x] A7.4 Simpan file credentials JSON Firebase di `storage/app/firebase-credentials.json`

---

### BAGIAN B: Frontend — NotificationStore

- [x] B1. Perbarui NotificationStore (`resources/js/stores/notification.ts`)
  - [x] B1.1 Tambahkan tipe `NotificationType = 'success' | 'warning' | 'danger' | 'info'` dan interface `Notification` dengan field: id, user_id, title, message, type, link, is_read, created_at, updated_at
  - [x] B1.2 Tambahkan state: `notifications: Notification[]`, `unreadCount: number`, `loading: boolean`, `hasMore: boolean`, `page: number`
  - [x] B1.3 Implementasikan `fetchNotifications(page?)` — GET /api/notifications, isi array, update hasMore dan page
  - [x] B1.4 Implementasikan `markAsRead(id)` — PATCH /api/notifications/{id}/read, update item di array lokal
  - [x] B1.5 Implementasikan `markAllAsRead()` — PATCH /api/notifications/read-all, set semua is_read=true di array lokal
  - [x] B1.6 Implementasikan `addRealtime(notification)` — tambah notif baru ke index 0 array (dari FCM foreground)
  - [x] B1.7 Implementasikan `registerDevice(token)` — POST /api/devices/register
  - [x] B1.8 Pertahankan backward compatibility: computed `lowStockAlerts`, method `addLowStockAlert`, `dismissAlert`, `clearAll`

---

### BAGIAN C: Frontend — Komponen Notifikasi

- [x] C1. Buat NotificationBell.vue
  - [x] C1.1 Buat file `resources/js/components/NotificationBell.vue`
  - [x] C1.2 Tampilkan icon `Bell` dari lucide-vue-next selalu
  - [x] C1.3 Tampilkan badge merah dengan angka jika unreadCount 1–9
  - [x] C1.4 Tampilkan badge merah dengan teks "9+" jika unreadCount > 9
  - [x] C1.5 Sembunyikan badge jika unreadCount === 0
  - [x] C1.6 Klik → emit 'toggle' untuk membuka/menutup NotificationDropdown
  - [x] C1.7 Tambahkan CSS keyframe `badge-bounce` yang aktif saat unreadCount bertambah

- [x] C2. Buat NotificationDropdown.vue
  - [x] C2.1 Buat file `resources/js/components/NotificationDropdown.vue`
  - [x] C2.2 Implementasikan v-model (modelValue: boolean) untuk open/close
  - [x] C2.3 Tampilkan skeleton loading (3 placeholder) saat `loading === true`
  - [x] C2.4 Tampilkan empty state "Tidak ada notifikasi" dengan icon Bell jika array kosong
  - [x] C2.5 Tampilkan daftar notifikasi dengan grouping: Hari ini / Kemarin / Lebih lama
  - [x] C2.6 Per item: icon sesuai type (CheckCircle/AlertTriangle/XCircle/Info), title, message, timestamp relatif
  - [x] C2.7 Unread item: `bg-blue-50`, hover: `bg-gray-50`
  - [x] C2.8 Klik item → navigate ke `notification.link` (jika ada) + panggil `markAsRead(id)`
  - [x] C2.9 Tombol "Tandai semua dibaca" → `markAllAsRead()`
  - [x] C2.10 Tambahkan transisi fade + slide-down saat buka/tutup

- [x] C3. Buat composable useFCM.ts
  - [x] C3.1 Buat file `resources/js/composables/useFCM.ts`
  - [x] C3.2 Inisialisasi Firebase app dengan config dari `import.meta.env.VITE_FIREBASE_*`
  - [x] C3.3 Implementasikan `requestPermissionAndRegister()` — minta izin, dapatkan token, panggil `notifStore.registerDevice(token)`
  - [x] C3.4 Implementasikan `setupForegroundHandler()` — `onMessage()` handler yang memanggil `toast.show()` + `notifStore.addRealtime()`
  - [x] C3.5 Implementasikan `init()` — panggil requestPermissionAndRegister + setupForegroundHandler
  - [x] C3.6 Register service worker `firebase-messaging-sw.js`

- [x] C4. Buat firebase-messaging-sw.js
  - [x] C4.1 Buat file `public/firebase-messaging-sw.js`
  - [x] C4.2 Import Firebase compat scripts via importScripts
  - [x] C4.3 Inisialisasi Firebase app dengan config yang sama
  - [x] C4.4 Implementasikan `messaging.onBackgroundMessage()` yang memanggil `self.registration.showNotification()`

- [x] C5. Tambahkan variabel environment Firebase
  - [x] C5.1 Tambahkan `VITE_FIREBASE_API_KEY`, `VITE_FIREBASE_AUTH_DOMAIN`, `VITE_FIREBASE_PROJECT_ID`, `VITE_FIREBASE_MESSAGING_SENDER_ID`, `VITE_FIREBASE_APP_ID`, `VITE_FIREBASE_VAPID_KEY` ke `.env` dan `.env.example`

- [x] C6. Install firebase npm package
  - [x] C6.1 Jalankan `npm install firebase`

---

### BAGIAN D: Frontend — Layout & Routing

- [x] D1. Perbarui Layout.vue
  - [x] D1.1 Import dan gunakan komponen `NotificationBell` dan `NotificationDropdown` di topbar
  - [x] D1.2 Hapus blok `<button v-if="notifStore.lowStockAlerts.length">` beserta panel dropdown lama
  - [x] D1.3 Hapus ref `showNotifPanel`, ganti dengan `showDropdown` untuk NotificationDropdown
  - [x] D1.4 Panggil `useFCM().init()` di `onMounted` Layout
  - [x] D1.5 Ganti dua nav item "Transaksi Masuk" dan "Transaksi Keluar" dengan satu item "Transaksi" ke `/transactions`
  - [x] D1.6 Tambahkan nav item "Notifikasi" ke `/notifications` untuk semua role

- [x] D2. Perbarui konfigurasi Router di app.ts
  - [x] D2.1 Hapus route `/transactions/in` dan `/transactions/out`
  - [x] D2.2 Tambahkan route `/transactions` dengan komponen `TransactionPage` dan meta `roles: ['pengelola', 'kasir']`
  - [x] D2.3 Tambahkan route `/notifications` dengan komponen `NotificationPage` dan meta `roles: ['pengelola', 'kasir']`
  - [x] D2.4 Tambahkan redirect dari `/transactions/in` ke `/transactions?tab=in`
  - [x] D2.5 Tambahkan redirect dari `/transactions/out` ke `/transactions?tab=out`

---

### BAGIAN E: Frontend — Komponen Transaksi

- [x] E1. Buat komponen TransactionInForm
  - [x] E1.1 Buat file `resources/js/components/TransactionInForm.vue`
  - [x] E1.2 Pindahkan seluruh template dan logika dari `TransactionInPage.vue` ke komponen ini
  - [x] E1.3 Tambahkan emit `success` dengan payload `{ productName, quantity, currentStock }` setelah transaksi berhasil
  - [x] E1.4 Pastikan pemanggilan `notifStore.addLowStockAlert(...)` tetap ada

- [x] E2. Buat komponen TransactionOutForm
  - [x] E2.1 Buat file `resources/js/components/TransactionOutForm.vue`
  - [x] E2.2 Pindahkan seluruh template dan logika dari `TransactionOutPage.vue` ke komponen ini
  - [x] E2.3 Tambahkan emit `success` dengan payload `{ productName, quantity, currentStock }` setelah transaksi berhasil

- [x] E3. Buat halaman TransactionPage
  - [x] E3.1 Buat file `resources/js/pages/TransactionPage.vue`
  - [x] E3.2 Implementasikan tab switcher yang menampilkan `TransactionInForm` atau `TransactionOutForm`
  - [x] E3.3 Baca query param `?tab=` dari route untuk menentukan tab aktif awal
  - [x] E3.4 Sembunyikan tab switcher dan paksa tab `'out'` untuk pengguna dengan peran `kasir`
  - [x] E3.5 Handle emit `success` dari TransactionInForm → `notifStore.addTransactionAlert('transaction_in', ...)`
  - [x] E3.6 Handle emit `success` dari TransactionOutForm → `notifStore.addTransactionAlert('transaction_out', ...)`

---

### BAGIAN F: Frontend — Halaman Notifikasi & Produk

- [x] F1. Buat halaman NotificationPage
  - [x] F1.1 Buat file `resources/js/pages/NotificationPage.vue`
  - [x] F1.2 Panggil `fetchNotifications()` dan `markAllAsRead()` di `onMounted`
  - [x] F1.3 Tampilkan daftar notifikasi diurutkan dari terbaru di atas
  - [x] F1.4 Tampilkan icon dan warna per tipe: success (hijau/CheckCircle), warning (kuning/AlertTriangle), danger (merah/XCircle), info (biru/Info)
  - [x] F1.5 Tampilkan waktu relatif untuk setiap notifikasi
  - [x] F1.6 Tombol dismiss (×) per item → `notifStore.dismissNotification(id)` (hapus dari array lokal)
  - [x] F1.7 Tombol "Tandai semua dibaca" → `markAllAsRead()` dan "Hapus semua" → `clearAll()`
  - [x] F1.8 Filter tab: Semua | Success | Warning | Danger | Info
  - [x] F1.9 Empty state jika tidak ada notifikasi
  - [x] F1.10 Implementasikan load more / infinite scroll menggunakan `fetchNotifications(page + 1)`

- [x] F2. Perbarui ProductListPage
  - [x] F2.1 Tambahkan tombol "Transaksi" di header sebelah kiri tombol "Tambah Barang"
  - [x] F2.2 Arahkan tombol "Transaksi" ke `/transactions?tab=in`

---

### BAGIAN G: Testing

- [x] G1. Property-based test NotificationStore (fast-check)
  - [x] G1.1 Test: unreadCount selalu === notifications.filter(n => !n.is_read).length untuk semua state
  - [x] G1.2 Test: markAllAsRead() selalu menghasilkan unreadCount === 0
  - [x] G1.3 Test: addRealtime() selalu menambah item ke index 0

- [x] G2. Unit test Backend (PHPUnit)
  - [x] G2.1 Test NotificationService::sendToUser menyimpan ke DB
  - [x] G2.2 Test markRead hanya mengubah notifikasi milik user yang benar (user lain tidak bisa)
  - [x] G2.3 Test DeviceController::register melakukan upsert (tidak duplikat token)
