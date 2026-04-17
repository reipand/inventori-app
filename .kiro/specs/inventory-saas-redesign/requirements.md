# Dokumen Persyaratan

## Pendahuluan

Fitur ini merupakan overhaul besar (redesign) pada aplikasi inventori **Cahaya Prima** yang sudah berjalan. Tujuannya adalah meningkatkan kualitas UX, keandalan sistem notifikasi, keamanan sesi, dan konsistensi antarmuka agar setara dengan standar SaaS modern (Notion / Slack / Stripe Dashboard). Perubahan mencakup tujuh area utama: sistem notifikasi, keamanan sesi otomatis, alur transaksi, sidebar, halaman login, desain responsif, dan micro-interactions.

Stack yang digunakan: Vue 3 + TypeScript + Pinia + Tailwind CSS + shadcn-vue (frontend), Laravel + tymon/jwt-auth (backend), Firebase Cloud Messaging (FCM), MySQL.

---

## Glosarium

- **Aplikasi**: Sistem inventori Cahaya Prima secara keseluruhan (frontend Vue 3 + backend Laravel).
- **NotificationStore**: Pinia store (`stores/notification.ts`) yang mengelola state notifikasi.
- **NotificationBell**: Komponen Vue (`NotificationBell.vue`) berupa ikon lonceng di topbar.
- **NotificationDropdown**: Komponen Vue (`NotificationDropdown.vue`) berupa panel daftar notifikasi.
- **FCM_Service**: Layanan Firebase Cloud Messaging yang mengirim push notification ke browser.
- **FCM_Composable**: Composable `useFCM.ts` yang menginisialisasi Firebase, mendaftarkan token, dan menangani pesan foreground.
- **ServiceWorker**: File `firebase-messaging-sw.js` yang menangani pesan FCM saat tab tidak aktif (background).
- **IdleDetector**: Mekanisme frontend yang mendeteksi ketidakaktifan pengguna berdasarkan event `mousemove`, `click`, `scroll`, dan `keydown`.
- **AuthStore**: Pinia store (`stores/auth.ts`) yang mengelola state autentikasi.
- **AuthService**: Service `authService.ts` yang mengelola token JWT di localStorage dan interceptor Axios.
- **JWT_Access_Token**: Token JWT berumur pendek (15 menit) untuk mengautentikasi setiap request API.
- **JWT_Refresh_Token**: Token JWT berumur panjang (7 hari) untuk memperbarui JWT_Access_Token secara diam-diam.
- **TransactionModal**: Modal Vue yang menampilkan form transaksi masuk atau keluar, dipanggil dari halaman produk.
- **ProductListPage**: Halaman daftar produk (`ProductListPage.vue`).
- **Sidebar**: Komponen navigasi vertikal di sisi kiri layar, bagian dari `Layout.vue`.
- **Topbar**: Komponen header horizontal di bagian atas konten utama, bagian dari `Layout.vue`.
- **LoginPage**: Halaman login (`LoginPage.vue`).
- **Pengelola**: Peran pengguna dengan akses penuh ke semua fitur.
- **Kasir**: Peran pengguna dengan akses terbatas (produk, transaksi keluar, notifikasi).
- **Toast**: Notifikasi sementara yang muncul di sudut layar.
- **Breakpoint_Mobile**: Lebar layar ≤ 767px.
- **Breakpoint_Tablet**: Lebar layar 768px – 1365px.
- **Breakpoint_Desktop**: Lebar layar ≥ 1366px.

---

## Persyaratan

---

### Persyaratan 1: Perbaikan Bug Sistem Notifikasi

**User Story:** Sebagai pengguna, saya ingin dropdown notifikasi menampilkan data yang benar dan badge yang akurat, sehingga saya tidak melewatkan notifikasi penting.

#### Kriteria Penerimaan

1. WHEN pengguna membuka NotificationDropdown, THE NotificationDropdown SHALL menampilkan daftar notifikasi yang diambil dari endpoint `GET /api/notifications` milik NotificationStore.
2. WHEN NotificationStore berhasil mengambil data dari API, THE NotificationBell SHALL menampilkan badge dengan angka yang sama dengan jumlah notifikasi yang belum dibaca (`is_read = false`).
3. WHEN NotificationStore selesai memuat data dan daftar notifikasi kosong, THE NotificationDropdown SHALL menampilkan pesan "Tidak ada notifikasi" beserta ikon lonceng.
4. WHEN FCM_Composable menerima pesan foreground melalui `onMessage`, THE Toast SHALL muncul di layar dengan judul dan isi pesan FCM tersebut.
5. WHEN FCM_Composable menerima pesan foreground, THE NotificationStore SHALL menambahkan notifikasi baru ke posisi paling atas daftar tanpa perlu reload halaman.
6. IF NotificationStore gagal mengambil data dari API karena error jaringan, THEN THE NotificationDropdown SHALL menampilkan pesan error "Gagal memuat notifikasi" dan menyediakan tombol coba ulang.

---

### Persyaratan 2: FCM Foreground dan Background

**User Story:** Sebagai pengguna, saya ingin menerima notifikasi push baik saat tab aktif maupun tidak aktif, sehingga saya selalu mendapat informasi terkini.

#### Kriteria Penerimaan

1. WHEN pengguna pertama kali membuka Aplikasi setelah login, THE FCM_Composable SHALL meminta izin notifikasi browser kepada pengguna.
2. WHEN pengguna memberikan izin notifikasi, THE FCM_Composable SHALL mengambil FCM token dari Firebase dan mengirimkannya ke endpoint `POST /api/devices/register` di backend.
3. THE ServiceWorker SHALL terdaftar di browser pada path `/firebase-messaging-sw.js` saat Aplikasi diinisialisasi.
4. WHEN FCM_Service mengirim pesan ke browser saat tab tidak aktif (`document.visibilityState === 'hidden'`), THE ServiceWorker SHALL menampilkan notifikasi sistem operasi dengan judul, isi, dan ikon dari payload FCM.
5. WHEN FCM_Service mengirim pesan ke browser saat tab aktif (`document.visibilityState === 'visible'`), THE FCM_Composable SHALL menangani pesan melalui handler `onMessage` tanpa menampilkan notifikasi sistem operasi duplikat.
6. IF pengguna menolak izin notifikasi browser, THEN THE FCM_Composable SHALL mencatat peringatan di konsol dan melanjutkan inisialisasi Aplikasi tanpa error.
7. IF konfigurasi Firebase tidak lengkap (variabel VITE_FIREBASE_* kosong), THEN THE FCM_Composable SHALL melewati seluruh proses inisialisasi FCM tanpa melempar error ke pengguna.

---

### Persyaratan 3: Penyimpanan FCM Token ke Database

**User Story:** Sebagai sistem, saya ingin menyimpan FCM token setiap perangkat pengguna ke database, sehingga backend dapat mengirim notifikasi push yang tepat sasaran.

#### Kriteria Penerimaan

1. WHEN FCM_Composable berhasil mendapatkan FCM token baru, THE AuthService SHALL mengirim token tersebut ke endpoint `POST /api/devices/register` beserta JWT_Access_Token pengguna yang sedang login.
2. THE Backend SHALL menyimpan FCM token ke tabel `user_devices` dengan kolom `user_id`, `fcm_token`, `device_name`, dan `last_active_at`.
3. WHEN pengguna yang sama mendaftarkan FCM token yang sudah ada di database, THE Backend SHALL memperbarui kolom `last_active_at` tanpa membuat entri duplikat.
4. WHEN pengguna logout, THE AuthService SHALL mengirim request ke endpoint `DELETE /api/devices/token` untuk menghapus FCM token perangkat tersebut dari database.
5. IF request pendaftaran FCM token gagal karena error server, THEN THE FCM_Composable SHALL mencatat error di konsol tanpa mengganggu alur kerja pengguna.

---

### Persyaratan 4: Tampilan NotificationBell Modern

**User Story:** Sebagai pengguna, saya ingin melihat ikon lonceng yang modern dengan badge animasi, sehingga saya langsung tahu ada notifikasi baru.

#### Kriteria Penerimaan

1. THE NotificationBell SHALL menampilkan ikon lonceng dari library Lucide (`Bell`) berukuran 24×24px.
2. WHEN `unreadCount` di NotificationStore lebih dari 0, THE NotificationBell SHALL menampilkan badge merah berbentuk lingkaran di pojok kanan atas ikon.
3. WHEN `unreadCount` bernilai 1 hingga 9, THE NotificationBell SHALL menampilkan angka tersebut di dalam badge.
4. WHEN `unreadCount` lebih dari 9, THE NotificationBell SHALL menampilkan teks "9+" di dalam badge.
5. WHEN nilai `unreadCount` bertambah, THE NotificationBell SHALL memainkan animasi `bounce` pada badge selama 400ms.
6. WHEN `unreadCount` bernilai 0, THE NotificationBell SHALL tidak menampilkan badge sama sekali.

---

### Persyaratan 5: Tampilan NotificationDropdown Modern

**User Story:** Sebagai pengguna, saya ingin dropdown notifikasi yang rapi dan mudah digunakan, sehingga saya dapat membaca dan mengelola notifikasi dengan nyaman.

#### Kriteria Penerimaan

1. THE NotificationDropdown SHALL memiliki lebar tetap 360px, sudut membulat `rounded-xl`, dan bayangan `shadow-lg`.
2. WHEN NotificationDropdown terbuka, THE NotificationDropdown SHALL menampilkan animasi `fade` dan `slide-down` dengan durasi 150ms.
3. WHEN pengguna mengklik area di luar NotificationDropdown, THE NotificationDropdown SHALL menutup secara otomatis.
4. WHEN sebuah notifikasi belum dibaca (`is_read = false`), THE NotificationDropdown SHALL menampilkan baris notifikasi tersebut dengan latar belakang `bg-blue-50`.
5. WHEN pengguna mengarahkan kursor ke baris notifikasi, THE NotificationDropdown SHALL menampilkan efek highlight dengan transisi warna yang halus (durasi 150ms).
6. WHEN pengguna mengklik sebuah notifikasi, THE NotificationStore SHALL menandai notifikasi tersebut sebagai sudah dibaca melalui endpoint `PATCH /api/notifications/{id}/read`.
7. WHEN pengguna mengklik tombol "Tandai semua dibaca", THE NotificationStore SHALL menandai semua notifikasi sebagai sudah dibaca melalui endpoint `PATCH /api/notifications/read-all`.
8. THE NotificationDropdown SHALL mengelompokkan notifikasi berdasarkan tanggal: "Hari ini", "Kemarin", dan "Lebih lama".

---

### Persyaratan 6: Idle Timeout dan Auto Logout

**User Story:** Sebagai pengelola sistem, saya ingin pengguna yang tidak aktif selama 15 menit otomatis dikeluarkan, sehingga keamanan data terjaga jika pengguna meninggalkan perangkat.

#### Kriteria Penerimaan

1. THE IdleDetector SHALL memantau event `mousemove`, `click`, `scroll`, dan `keydown` pada `document` untuk mendeteksi aktivitas pengguna.
2. WHEN pengguna tidak melakukan aktivitas apapun selama 15 menit berturut-turut, THE Aplikasi SHALL melakukan logout otomatis dan mengarahkan pengguna ke halaman `/login`.
3. WHEN logout otomatis terjadi karena idle, THE Toast SHALL menampilkan pesan "Sesi berakhir karena tidak aktif" dengan tipe `warning`.
4. WHEN pengguna melakukan aktivitas apapun yang terdeteksi IdleDetector, THE IdleDetector SHALL mereset timer idle ke 0.
5. WHILE pengguna berada di halaman `/login` atau halaman publik, THE IdleDetector SHALL tidak aktif.
6. WHEN tab browser menjadi tidak aktif (`document.visibilityState === 'hidden'`), THE IdleDetector SHALL tetap menghitung waktu idle berdasarkan waktu terakhir aktivitas yang tercatat.
7. WHEN tab browser kembali aktif (`document.visibilityState === 'visible'`) dan waktu idle sudah melebihi 15 menit, THE Aplikasi SHALL segera melakukan logout otomatis.

---

### Persyaratan 7: JWT Access Token dan Refresh Token

**User Story:** Sebagai pengguna, saya ingin sesi login saya diperbarui secara otomatis tanpa harus login ulang setiap 15 menit, sehingga pengalaman kerja tidak terganggu.

#### Kriteria Penerimaan

1. THE Backend SHALL menerbitkan JWT_Access_Token dengan masa berlaku 15 menit saat pengguna berhasil login.
2. THE Backend SHALL menerbitkan JWT_Refresh_Token dengan masa berlaku 7 hari saat pengguna berhasil login.
3. THE AuthService SHALL menyimpan JWT_Refresh_Token di `httpOnly cookie` dan JWT_Access_Token di `localStorage`.
4. WHEN JWT_Access_Token akan kedaluwarsa dalam waktu kurang dari 2 menit, THE AuthService SHALL secara otomatis mengirim request ke endpoint `POST /api/auth/refresh` menggunakan JWT_Refresh_Token untuk mendapatkan JWT_Access_Token baru (silent refresh).
5. WHEN silent refresh berhasil, THE AuthService SHALL memperbarui JWT_Access_Token di `localStorage` tanpa interaksi pengguna.
6. IF JWT_Refresh_Token sudah kedaluwarsa atau tidak valid saat silent refresh dilakukan, THEN THE AuthService SHALL melakukan logout dan mengarahkan pengguna ke `/login` dengan Toast "Sesi berakhir, silakan login kembali".
7. IF request API mengembalikan HTTP 401 dan bukan dari endpoint `/auth/logout`, THEN THE AuthService SHALL mencoba silent refresh sekali sebelum melakukan logout.

---

### Persyaratan 8: Multi-Device Session Management

**User Story:** Sebagai pengguna, saya ingin sesi lama saya dicabut secara otomatis ketika login dari perangkat baru, sehingga akun saya tidak digunakan di banyak perangkat secara bersamaan tanpa sepengetahuan saya.

#### Kriteria Penerimaan

1. WHEN pengguna berhasil login dari perangkat baru, THE Backend SHALL mencabut (revoke/blacklist) semua JWT_Refresh_Token aktif milik pengguna tersebut dari perangkat lain.
2. WHEN sesi dari perangkat lain dicabut, THE Backend SHALL mengirim notifikasi FCM ke perangkat lama dengan pesan "Akun Anda masuk dari perangkat lain. Sesi ini telah diakhiri."
3. WHEN perangkat lama menerima notifikasi pencabutan sesi, THE Aplikasi SHALL melakukan logout otomatis dan mengarahkan pengguna ke `/login`.
4. THE Backend SHALL menyimpan riwayat sesi aktif per pengguna di tabel `user_devices` dengan kolom `user_id`, `fcm_token`, `refresh_token_id`, dan `last_active_at`.

---

### Persyaratan 9: Refaktor Alur Transaksi

**User Story:** Sebagai pengguna, saya ingin melakukan transaksi langsung dari halaman produk tanpa berpindah halaman, sehingga alur kerja lebih cepat dan efisien.

#### Kriteria Penerimaan

1. THE Sidebar SHALL tidak menampilkan menu "Transaksi" untuk semua peran pengguna.
2. THE ProductListPage SHALL menampilkan dua tombol aksi di bagian header: "[+ Barang Masuk]" dan "[- Barang Keluar]".
3. WHEN pengguna mengklik tombol "[+ Barang Masuk]" pada baris produk tertentu, THE TransactionModal SHALL terbuka dalam mode "masuk" dengan nama produk yang dipilih sudah terisi.
4. WHEN pengguna mengklik tombol "[- Barang Keluar]" pada baris produk tertentu, THE TransactionModal SHALL terbuka dalam mode "keluar" dengan nama produk yang dipilih sudah terisi.
5. THE TransactionModal SHALL memiliki sudut membulat `rounded-xl`, bayangan `shadow-xl`, dan animasi `fade` + `scale` saat membuka dan menutup.
6. THE TransactionModal SHALL berisi field: nama produk (read-only), input jumlah (angka positif), tanggal transaksi (default hari ini), supplier (opsional, hanya untuk transaksi masuk), dan dua tombol: "Batal" dan "Simpan".
7. WHEN pengguna mengklik "Simpan" di TransactionModal, THE TransactionModal SHALL mengirim data ke endpoint API transaksi yang sesuai dan menampilkan Toast sukses.
8. WHEN pengguna mengklik "Batal" atau area di luar TransactionModal, THE TransactionModal SHALL menutup tanpa menyimpan data.
9. IF input jumlah di TransactionModal bernilai 0 atau negatif, THEN THE TransactionModal SHALL menampilkan pesan validasi "Jumlah harus lebih dari 0" dan tidak mengirim request.
10. IF transaksi keluar melebihi stok yang tersedia, THEN THE TransactionModal SHALL menampilkan pesan error dari server dan tidak menutup modal.
11. WHEN TransactionModal berhasil menyimpan transaksi, THE ProductListPage SHALL memperbarui nilai stok produk yang bersangkutan tanpa reload halaman penuh.

---

### Persyaratan 10: Peningkatan UI/UX Sidebar

**User Story:** Sebagai pengguna, saya ingin sidebar yang bersih dan modern, sehingga navigasi terasa nyaman dan profesional.

#### Kriteria Penerimaan

1. THE Sidebar SHALL memiliki lebar 260px saat dalam kondisi diperluas (expanded) di Breakpoint_Desktop.
2. THE Sidebar SHALL menggunakan latar belakang putih (`bg-white`) dengan border kanan tipis (`border-r border-gray-200`).
3. WHEN menu navigasi aktif (route saat ini cocok), THE Sidebar SHALL menampilkan item tersebut dengan latar belakang `bg-primary/10` dan teks berwarna `text-primary`.
4. WHEN pengguna mengarahkan kursor ke item menu, THE Sidebar SHALL menampilkan efek highlight dengan transisi warna yang halus (durasi 150ms).
5. THE Sidebar SHALL menggunakan ikon dari library Lucide untuk setiap item menu.
6. THE Sidebar SHALL memiliki struktur tiga bagian: logo dan nama aplikasi di atas, daftar menu di tengah, dan profil pengguna di bawah.
7. THE Sidebar SHALL menampilkan nama email pengguna dan badge peran (Pengelola/Kasir) di bagian bawah.
8. WHILE Aplikasi berjalan di Breakpoint_Desktop, THE Sidebar SHALL selalu terlihat (fixed/sticky) di sisi kiri layar.
9. WHILE Aplikasi berjalan di Breakpoint_Mobile, THE Sidebar SHALL tersembunyi secara default dan dapat dibuka sebagai drawer melalui tombol hamburger di Topbar.
10. WHEN pengguna mengklik tombol hamburger di Topbar pada Breakpoint_Mobile, THE Sidebar SHALL muncul sebagai drawer dari sisi kiri dengan overlay gelap di belakangnya.
11. WHEN pengguna mengklik overlay di belakang Sidebar drawer, THE Sidebar SHALL menutup secara otomatis.

---

### Persyaratan 11: Peningkatan Halaman Login

**User Story:** Sebagai pengguna, saya ingin halaman login yang menarik dan profesional, sehingga kesan pertama terhadap aplikasi positif.

#### Kriteria Penerimaan

1. WHILE Aplikasi diakses dari Breakpoint_Desktop, THE LoginPage SHALL menampilkan layout dua kolom: panel kiri berisi ilustrasi/branding, panel kanan berisi form login.
2. THE LoginPage SHALL menampilkan panel kiri dengan latar belakang berwarna navy (`#1e3a5f`), logo Cahaya Prima, tagline, dan statistik fitur aplikasi.
3. WHILE Aplikasi diakses dari Breakpoint_Mobile, THE LoginPage SHALL menampilkan form login terpusat secara penuh tanpa panel kiri.
4. THE LoginPage SHALL menampilkan card form login dengan sudut membulat `rounded-2xl` dan bayangan lembut.
5. THE LoginPage SHALL menampilkan input email dan password dengan efek focus ring berwarna biru saat aktif.
6. WHEN pengguna mengklik tombol "Masuk" dengan field kosong, THE LoginPage SHALL menampilkan pesan validasi per field tanpa mengirim request ke server.
7. WHEN login berhasil dan pengguna adalah Pengelola, THE LoginPage SHALL mengarahkan pengguna ke `/dashboard`.
8. WHEN login berhasil dan pengguna adalah Kasir, THE LoginPage SHALL mengarahkan pengguna ke `/products`.
9. IF login gagal karena kredensial salah, THEN THE LoginPage SHALL menampilkan banner error dengan pesan dari server.
10. THE LoginPage SHALL menyediakan tombol toggle untuk menampilkan atau menyembunyikan kata sandi.

---

### Persyaratan 12: Desain Responsif Mobile-First

**User Story:** Sebagai pengguna yang mengakses dari perangkat mobile, saya ingin tampilan yang nyaman dan mudah digunakan di layar kecil, sehingga saya dapat bekerja dari mana saja.

#### Kriteria Penerimaan

1. THE Aplikasi SHALL menggunakan pendekatan mobile-first dalam penulisan CSS, dimulai dari Breakpoint_Mobile kemudian diperluas ke breakpoint yang lebih besar.
2. THE Aplikasi SHALL mendukung breakpoint: 360px (mobile kecil), 768px (tablet), 1366px (laptop), 1440px (desktop), dan 1920px (layar lebar).
3. THE Aplikasi SHALL memastikan semua elemen interaktif (tombol, link, input) memiliki ukuran touch target minimal 44×44px.
4. THE Aplikasi SHALL menggunakan ukuran font minimal 14px untuk semua teks konten.
5. WHILE Aplikasi berjalan di Breakpoint_Mobile, THE ProductListPage SHALL menampilkan tabel produk dalam format yang dapat di-scroll secara horizontal.
6. WHILE Aplikasi berjalan di Breakpoint_Mobile, THE TransactionModal SHALL menampilkan modal yang memenuhi hampir seluruh tinggi layar dengan scroll internal jika konten melebihi viewport.
7. WHILE Aplikasi berjalan di Breakpoint_Desktop, THE Sidebar SHALL ditampilkan secara permanen di sisi kiri tanpa tombol hamburger.
8. WHILE Aplikasi berjalan di Breakpoint_Mobile, THE Topbar SHALL menampilkan tombol hamburger untuk membuka Sidebar drawer.

---

### Persyaratan 13: Micro-Interactions dan Animasi

**User Story:** Sebagai pengguna, saya ingin antarmuka yang terasa hidup dan responsif terhadap interaksi saya, sehingga pengalaman menggunakan aplikasi terasa menyenangkan.

#### Kriteria Penerimaan

1. WHEN nilai `unreadCount` di NotificationBell bertambah, THE NotificationBell SHALL memainkan animasi `bounce` pada badge selama 400ms.
2. WHEN NotificationDropdown dibuka, THE NotificationDropdown SHALL menampilkan animasi `fade` + `slide-down` dengan durasi 150ms.
3. WHEN TransactionModal dibuka, THE TransactionModal SHALL menampilkan animasi `scale-in` dari skala 0.95 ke 1.0 dengan durasi 150ms.
4. WHEN pengguna mengarahkan kursor ke tombol atau item menu, THE Aplikasi SHALL menampilkan transisi warna latar belakang yang halus dengan durasi 150ms.
5. WHEN Toast muncul, THE Toast SHALL masuk dari sisi kanan layar dengan animasi `slide-in` dan keluar dengan animasi `slide-out` dengan durasi 200ms.
6. WHEN halaman berpindah melalui Vue Router, THE Aplikasi SHALL menampilkan transisi `fade` antar halaman dengan durasi 150ms.
7. THE Aplikasi SHALL menggunakan CSS `transition` dengan `ease` atau `ease-in-out` untuk semua animasi interaktif, bukan `linear`.

---

### Persyaratan 14: Aksesibilitas dan Standar Kode

**User Story:** Sebagai pengembang, saya ingin kode yang bersih dan komponen yang aksesibel, sehingga aplikasi mudah dipelihara dan dapat digunakan oleh semua pengguna.

#### Kriteria Penerimaan

1. THE NotificationBell SHALL memiliki atribut `aria-label="Notifikasi"` dan `aria-haspopup="true"` pada elemen tombol.
2. THE TransactionModal SHALL memiliki atribut `role="dialog"`, `aria-modal="true"`, dan `aria-labelledby` yang merujuk ke judul modal.
3. WHEN TransactionModal terbuka, THE Aplikasi SHALL memindahkan fokus keyboard ke elemen pertama yang dapat difokus di dalam modal.
4. WHEN TransactionModal terbuka, THE Aplikasi SHALL menjebak fokus keyboard di dalam modal (focus trap) hingga modal ditutup.
5. WHEN pengguna menekan tombol `Escape`, THE TransactionModal SHALL menutup.
6. THE LoginPage SHALL memiliki atribut `autocomplete` yang sesuai pada field email (`autocomplete="email"`) dan password (`autocomplete="current-password"`).
7. THE Aplikasi SHALL memastikan semua gambar dekoratif memiliki atribut `aria-hidden="true"`.
