# Rencana Implementasi: POS, Purchase & Profit Tracking

## Ikhtisar

Implementasi tiga modul baru: Purchase Module (invoice pembelian + kalkulasi COGS otomatis), POS/Kasir (keranjang belanja, checkout, struk), dan Profit Tracking (laporan profit berbasis snapshot COGS). Menggunakan stack yang sudah ada: Laravel + Vue 3 + TypeScript + Pinia + TailwindCSS.

## Tasks

- [x] 1. Database migrations dan model backend
  - [x] 1.1 Buat migration untuk menambahkan kolom `cogs` ke tabel `products`
    - Buat file migration baru di `database/migrations/` dengan `$table->decimal('cogs', 15, 2)->default(0)->after('sell_price')`
    - Tambahkan `cogs` ke `$fillable` dan `$casts` di `app/Models/Product.php`
    - _Requirements: 3.1, 3.3, 12.1_

  - [x] 1.2 Buat migration untuk tabel `invoices` dan `invoice_items`
    - Buat file migration `create_invoices_table` dengan kolom sesuai desain (UUID primary key, invoice_number unique, supplier_name, invoice_date, discount_global_type enum, discount_global_value, total_before_discount, total_discount, total_final, recorded_by FK ke users)
    - Buat file migration `create_invoice_items_table` dengan kolom sesuai desain (UUID primary key, invoice_id FK cascade, product_id FK, qty, price_input, price_mode enum, discount_item_type enum nullable, discount_item_value, price_per_unit_final, global_discount_portion, cogs_per_unit, subtotal_final)
    - _Requirements: 1.1, 1.2, 2.4_

  - [x] 1.3 Buat migration untuk tabel `sales` dan `sale_items`
    - Buat file migration `create_sales_table` dengan kolom sesuai desain (UUID primary key, transaction_date, subtotal, total_discount, total, payment_method enum, amount_paid, change_amount, recorded_by FK ke users)
    - Buat file migration `create_sale_items_table` dengan kolom sesuai desain (UUID primary key, sale_id FK cascade, product_id FK, qty, sell_price, cogs, discount_per_item, subtotal)
    - _Requirements: 5.2, 5.3, 7.4_

  - [x] 1.4 Buat Eloquent model `Invoice`, `InvoiceItem`, `Sale`, `SaleItem`
    - `Invoice`: HasUuids, fillable sesuai desain, relasi `hasMany InvoiceItem`, `belongsTo User (recorded_by)`, cast untuk decimal fields
    - `InvoiceItem`: HasUuids, fillable sesuai desain, relasi `belongsTo Invoice`, `belongsTo Product`, cast untuk decimal fields
    - `Sale`: HasUuids, fillable sesuai desain, relasi `hasMany SaleItem`, `belongsTo User (recorded_by)`, cast untuk decimal fields
    - `SaleItem`: HasUuids, fillable sesuai desain, relasi `belongsTo Sale`, `belongsTo Product`, cast untuk decimal fields
    - _Requirements: 1.1, 1.2, 5.2, 5.3_

- [x] 2. Backend: InvoiceController — index dan show
  - [x] 2.1 Implementasi `InvoiceController@index`
    - Buat `app/Http/Controllers/InvoiceController.php`
    - Implementasi `index(Request)`: query Invoice dengan filter `start_date`, `end_date`, `supplier_name` (LIKE), `search` (nomor invoice LIKE); eager load `invoiceItems` count dan `recordedBy`; pagination 15/halaman; return JSON `{ success, data }`
    - Daftarkan route `GET /api/invoices` di `routes/api.php` dengan middleware `auth:api, check_active, role:pengelola`
    - _Requirements: 9.1, 9.3, 9.4_

  - [x] 2.2 Implementasi `InvoiceController@show`
    - Implementasi `show(string $id)`: load Invoice dengan semua `invoiceItems` beserta relasi `product`; return 404 jika tidak ditemukan
    - Daftarkan route `GET /api/invoices/{id}` di `routes/api.php`
    - _Requirements: 9.2_

- [x] 3. Backend: InvoiceController — store (kalkulasi COGS + update stok)
  - [x] 3.1 Implementasi `InvoiceController@store` — validasi dan kalkulasi
    - Validasi field wajib: invoice_number, supplier_name, invoice_date, items (array min 1)
    - Validasi setiap item: product_id ada, qty > 0, price_input > 0, price_mode valid, diskon persen ≤ 100%
    - Cek duplikat invoice_number (return 409 jika sudah ada)
    - Hitung `price_per_unit_final` per item sesuai price_mode dan tipe diskon
    - Hitung `total_before_discount` = SUM(price_per_unit_final × qty)
    - Hitung `total_global_discount` dari discount_global_type dan discount_global_value
    - Distribusikan diskon global secara proporsional ke setiap item → `global_discount_portion`
    - Hitung `cogs_per_unit` = (price_per_unit_final × qty − global_discount_portion) / qty
    - _Requirements: 1.1–1.6, 1.9, 1.10_

  - [x] 3.2 Implementasi `InvoiceController@store` — DB transaction, update stok, audit log
    - Bungkus dalam `DB::transaction`: simpan Invoice, simpan semua InvoiceItem, `lockForUpdate` + `increment('current_stock', qty)` per produk, update `products.cogs` dengan nilai `cogs_per_unit` terbaru, catat AuditLog `entity_type='invoice'`
    - Daftarkan route `POST /api/invoices` di `routes/api.php` dengan middleware `role:pengelola`
    - _Requirements: 1.7, 1.8, 12.1, 12.2_

  - [ ]* 3.3 Tulis property test untuk kalkulasi COGS (Property 1, 2, 3, 4)
    - Buat file `resources/js/stores/__tests__/cogsCalculation.test.ts`
    - **Property 1: COGS per unit tidak negatif** — `cogs_per_unit >= 0` untuk semua kombinasi input valid
    - **Validates: Requirements 1.6**
    - **Property 2: Distribusi diskon global konservatif** — `SUM(global_discount_portions) == total_global_discount` dalam toleransi ±0.01
    - **Validates: Requirements 1.5, 1.6**
    - **Property 3: Price_Mode "final" mengabaikan diskon per item** — `price_per_unit_final == price_input` saat price_mode='final'
    - **Validates: Requirements 1.3**
    - **Property 4: Price_Mode "before_discount" menerapkan diskon per item** — `price_per_unit_final == price_input × (1 - D/100)` untuk diskon persen
    - **Validates: Requirements 1.4**

  - [ ]* 3.4 Tulis unit test untuk skenario spesifik kalkulasi COGS
    - Invoice dengan diskon global 0 → COGS per unit = price_per_unit_final
    - Invoice dengan satu item → seluruh diskon global jatuh ke item tersebut
    - Nomor invoice duplikat → return 409
    - Qty invoice_item = 0 → validasi menolak
    - _Requirements: 1.9, 1.10_

- [x] 4. Checkpoint — Pastikan semua test kalkulasi COGS lulus
  - Pastikan semua test lulus, tanyakan ke user jika ada pertanyaan.

- [x] 5. Backend: InvoiceController — destroy
  - [x] 5.1 Implementasi `InvoiceController@destroy`
    - Load Invoice beserta InvoiceItems; return 404 jika tidak ditemukan
    - Cek apakah ada produk dari invoice ini yang sudah terjual (ada di `sale_items`); jika ya, sertakan flag `has_sold_products: true` di response konfirmasi (frontend yang menampilkan peringatan)
    - Bungkus dalam `DB::transaction`: `lockForUpdate` + `decrement('current_stock', qty)` per produk, hapus Invoice (cascade hapus InvoiceItems), catat AuditLog `entity_type='invoice'` action='delete'
    - Daftarkan route `DELETE /api/invoices/{id}` di `routes/api.php` dengan middleware `role:pengelola`
    - _Requirements: 9.5, 9.6_

  - [ ]* 5.2 Tulis unit test untuk penghapusan invoice
    - Hapus invoice → stok dikembalikan ke nilai sebelum invoice
    - Hapus invoice dengan produk yang sudah terjual → response menyertakan peringatan
    - _Requirements: 9.5, 9.6_

- [x] 6. Backend: SaleController — store dan show
  - [x] 6.1 Implementasi `SaleController@store`
    - Buat `app/Http/Controllers/SaleController.php`
    - Validasi: items tidak kosong, setiap item punya product_id valid dan qty > 0, payment_method valid, amount_paid ≥ total (untuk metode cash)
    - Bungkus dalam `DB::transaction`: `lockForUpdate` per produk, cek stok cukup (throw DomainException jika tidak), simpan Sale, simpan SaleItem dengan snapshot `sell_price` dan `cogs` dari `products` saat transaksi, `decrement('current_stock', qty)` per produk, catat AuditLog `entity_type='sale'`
    - Hitung `change_amount = amount_paid - total` (0 untuk metode QR)
    - Daftarkan route `POST /api/sales` di `routes/api.php` dengan middleware `auth:api, check_active` (pengelola + kasir)
    - _Requirements: 5.1–5.5, 7.1–7.5, 12.3_

  - [x] 6.2 Implementasi `SaleController@show`
    - Implementasi `show(string $id)`: load Sale dengan semua SaleItems beserta relasi `product`; return 404 jika tidak ditemukan
    - Daftarkan route `GET /api/sales/{id}` di `routes/api.php` dengan middleware `auth:api, check_active`
    - _Requirements: 5.6, 6.1_

  - [ ]* 6.3 Tulis property test untuk kalkulasi kembalian dan snapshot COGS (Property 7, 9)
    - Buat atau tambahkan ke file test yang sesuai
    - **Property 7: Kembalian = nominal_bayar − total** — `change == amount_paid - total` untuk semua amount_paid ≥ total
    - **Validates: Requirements 4.9, 5.2**
    - **Property 9: Snapshot COGS di sale_items tidak berubah setelah invoice baru** — nilai `cogs` di sale_item tidak berubah meskipun invoice baru disimpan
    - **Validates: Requirements 5.3, 8.6**

  - [ ]* 6.4 Tulis unit test untuk skenario checkout
    - Checkout dengan stok pas → stok menjadi 0
    - Checkout dengan stok kurang → return 422, stok tidak berubah
    - Metode QR → checkout berhasil tanpa nominal bayar, kembalian = 0
    - Cart kosong → return 400
    - _Requirements: 5.4, 5.5, 7.3, 7.5_

- [x] 7. Backend: ReportController — profit endpoint
  - [x] 7.1 Implementasi `ReportController@profit`
    - Tambahkan method `profit(Request)` ke `app/Http/Controllers/ReportController.php`
    - Query `sale_items` join `sales` dengan filter `start_date` dan `end_date` pada `sales.transaction_date`
    - Hitung per sale_item: `profit = (sell_price - cogs) × qty`
    - Agregasi: `total_revenue = SUM(sell_price × qty)`, `total_cogs = SUM(cogs × qty)`, `total_profit = total_revenue - total_cogs`, `margin = total_profit / total_revenue × 100`
    - Sertakan tabel detail per produk: nama produk, total qty terjual, total revenue, total COGS, total profit
    - Daftarkan route `GET /api/reports/profit` di `routes/api.php` dengan middleware `role:pengelola`
    - _Requirements: 8.1–8.6_

  - [ ]* 7.2 Tulis property test untuk kalkulasi profit (Property 10, 11)
    - **Property 10: Kalkulasi profit menggunakan snapshot COGS** — `profit == (sell_price - cogs) × qty` dan `total_profit == total_revenue - total_cogs`
    - **Validates: Requirements 8.1, 8.2, 8.6**
    - **Property 11: Filter tanggal hanya mengembalikan data dalam rentang** — semua record yang dikembalikan memiliki tanggal dalam `[start_date, end_date]`
    - **Validates: Requirements 8.4, 9.3**

  - [ ]* 7.3 Tulis unit test untuk laporan profit
    - Profit dengan COGS = 0 → profit = revenue
    - Profit dengan sell_price < cogs → profit negatif (rugi)
    - Filter tanggal → hanya data dalam rentang yang dikembalikan
    - _Requirements: 8.1, 8.4_

- [x] 8. Checkpoint — Pastikan semua test backend lulus
  - Pastikan semua test lulus, tanyakan ke user jika ada pertanyaan.

- [x] 9. Frontend: Service dan Store layer
  - [x] 9.1 Buat `resources/js/services/invoiceService.ts`
    - Definisikan interface TypeScript: `Invoice`, `InvoiceItem`, `InvoicePayload`, `InvoiceItemPayload`, `InvoiceListParams`, `PaginatedInvoices`
    - Implementasi fungsi: `getInvoices(params)`, `createInvoice(payload)`, `getInvoice(id)`, `deleteInvoice(id)`
    - Ikuti pola yang sama dengan `productService.ts` (axios, ApiResponse wrapper)
    - _Requirements: 9.1, 9.2, 9.4_

  - [x] 9.2 Buat `resources/js/services/posService.ts`
    - Definisikan interface TypeScript: `SalePayload`, `SaleItem`, `Sale`, `CartItem`
    - Implementasi fungsi: `createSale(payload)`, `getSale(id)`
    - _Requirements: 5.1, 5.6_

  - [x] 9.3 Buat `resources/js/stores/cartStore.ts`
    - Definisikan state: `items: CartItem[]`, `paymentMethod: 'cash' | 'qr'`, `amountPaid: number`
    - Implementasi actions: `addItem(product)` (tambah qty jika sudah ada, buat baru jika belum), `removeItem(productId)`, `updateQty(productId, qty)` (auto-remove jika qty ≤ 0), `updateDiscount(productId, discount)`, `clearCart()`
    - Implementasi getters: `subtotal`, `totalDiscount`, `total`, `change` (amountPaid - total, min 0), `isValid` (items tidak kosong dan pembayaran cukup)
    - _Requirements: 4.3, 4.6, 4.7, 4.8, 4.9, 4.10_

  - [ ]* 9.4 Tulis property test untuk cartStore (Property 8)
    - Buat file `resources/js/stores/__tests__/cartStore.test.ts`
    - **Property 8: Penghapusan dan penambahan item di Cart** — (a) addItem produk yang sudah ada menambah qty; (b) addItem produk baru membuat item baru dengan qty=1; (c) updateQty ke 0 menghapus item; (d) item lain tidak terpengaruh
    - **Validates: Requirements 4.3, 4.6, 4.7**

  - [ ]* 9.5 Tulis unit test untuk cartStore
    - `clearCart` mengosongkan semua item
    - `isValid` false saat cart kosong
    - `isValid` false saat amountPaid < total (metode cash)
    - `change` dihitung dengan benar
    - _Requirements: 4.8, 4.9, 4.10_

  - [x] 9.6 Buat `resources/js/stores/purchaseStore.ts`
    - Definisikan state: `invoices`, `currentInvoice`, `loading`, `error`
    - Implementasi actions: `fetchInvoices(params)`, `createInvoice(payload)`, `fetchInvoice(id)`, `deleteInvoice(id)`
    - _Requirements: 9.1, 9.2, 9.5_

- [x] 10. Checkpoint — Pastikan semua test store dan service lulus
  - Pastikan semua test lulus, tanyakan ke user jika ada pertanyaan.

- [x] 11. Frontend: Fungsi kalkulasi COGS (shared utility)
  - [x] 11.1 Buat fungsi kalkulasi di `resources/js/lib/cogsCalculation.ts`
    - `calculatePricePerUnitFinal(priceInput, priceMode, discountType, discountValue)` — mengembalikan harga satuan final setelah diskon per item
    - `distributeGlobalDiscount(items, globalDiscount)` — mendistribusikan diskon global secara proporsional, mengembalikan array `global_discount_portion` per item
    - `calculateCogsPerUnit(pricePerUnitFinal, qty, globalDiscountPortion)` — mengembalikan COGS per unit
    - `calculateInvoiceSummary(items, globalDiscountType, globalDiscountValue)` — mengembalikan `{ total_before_discount, total_discount, total_final, items_with_cogs }`
    - Semua fungsi harus pure (tidak ada side effect) untuk memudahkan testing
    - _Requirements: 1.3–1.6, 2.1, 2.2, 2.3_

  - [ ]* 11.2 Tulis property test untuk fungsi kalkulasi (Property 1, 2, 3, 4 — frontend)
    - Gunakan fast-check untuk memverifikasi semua 4 property kalkulasi COGS
    - Tambahkan ke file `resources/js/stores/__tests__/cogsCalculation.test.ts`
    - _Requirements: 1.3–1.6_

- [x] 12. Frontend: PurchasePage dan InvoiceFormModal
  - [x] 12.1 Buat `resources/js/pages/PurchasePage.vue` (`/purchase`)
    - Tampilkan daftar invoice dengan kolom: nomor invoice, nama supplier, tanggal, jumlah item, total nilai
    - Filter: rentang tanggal (start_date, end_date) dan nama supplier
    - Search bar untuk nomor invoice
    - Tombol "Buat Invoice Baru" membuka `InvoiceFormModal`
    - Klik baris invoice membuka `InvoiceDetailModal`
    - Tombol "Hapus" per baris dengan konfirmasi dialog (tampilkan peringatan jika ada produk yang sudah terjual)
    - Loading skeleton, empty state, pagination
    - _Requirements: 9.1, 9.3, 9.4, 9.5, 9.6_

  - [x] 12.2 Buat `resources/js/components/InvoiceFormModal.vue`
    - Form field header: nomor invoice, nama supplier, tanggal invoice, diskon global (type + value, opsional)
    - Tabel item dinamis: tambah/hapus baris, setiap baris punya: pilih produk, qty, harga input, price_mode (dropdown), diskon per item (type + value, opsional)
    - Preview kalkulasi real-time (computed): subtotal per item, COGS per unit per item, total sebelum diskon global, total diskon global, total akhir — diperbarui < 100ms tanpa request server
    - Validasi client-side: qty > 0, harga > 0, diskon persen ≤ 100%, minimal 1 item
    - Tombol "Simpan" dengan loading state, error toast jika gagal
    - _Requirements: 1.1–1.6, 1.9, 1.10, 2.1–2.3, 2.5_

  - [x] 12.3 Buat `resources/js/components/InvoiceDetailModal.vue`
    - Tampilkan semua field header invoice
    - Tabel invoice items dengan kolom: nama produk, qty, harga input, price_mode, diskon per item, harga satuan final, COGS per unit, subtotal
    - Tampilkan ringkasan: total sebelum diskon, total diskon, total akhir
    - _Requirements: 9.2_

- [x] 13. Frontend: POSPage dan ReceiptModal
  - [x] 13.1 Buat `resources/js/pages/POSPage.vue` (`/pos`)
    - Layout dua kolom pada desktop/tablet (Cart kiri 2/3, Summary kanan 1/3)
    - Layout satu kolom pada mobile (search → cart → summary → checkout)
    - Search bar dengan debounce 300ms untuk cari produk berdasarkan nama/SKU
    - Daftar hasil pencarian; klik produk → `cartStore.addItem(product)`
    - Daftar Cart_Item: nama produk, qty (input number), harga satuan, diskon per item (opsional), subtotal, tombol hapus
    - Ubah qty ke 0 → auto-remove item dari cart
    - Panel Summary: subtotal, total diskon, total, pilihan metode pembayaran (Tunai/QR), input nominal bayar (hanya untuk Tunai), kembalian real-time
    - Tombol Checkout: disabled jika cart kosong atau pembayaran kurang; loading state saat proses
    - Setelah checkout sukses: clearCart, tampilkan ReceiptModal, toast "Transaksi berhasil"
    - _Requirements: 4.1–4.11, 5.1, 5.6, 5.7, 7.1–7.5, 10.1, 10.2, 10.4, 10.5, 10.6_

  - [x] 13.2 Buat `resources/js/components/ReceiptModal.vue`
    - Tampilkan: nama toko, tanggal dan waktu transaksi, nomor transaksi, daftar item (nama, qty, harga satuan, subtotal), subtotal, total diskon, total, metode pembayaran, nominal bayar, kembalian
    - Layout dioptimalkan untuk thermal printer 58mm/80mm dengan CSS print (`@media print`)
    - Font monospace untuk alignment kolom
    - Tombol "Cetak Struk" → `window.print()`
    - Tombol "Tutup / Transaksi Baru" → tutup modal, kembali ke POS dengan cart kosong
    - _Requirements: 6.1–6.6_

- [x] 14. Frontend: ProfitReportPage
  - [x] 14.1 Buat `resources/js/pages/ProfitReportPage.vue` (`/reports/profit`)
    - Filter rentang tanggal (start_date, end_date)
    - KPI cards: total revenue, total COGS, total profit kotor, margin profit (%)
    - Tabel detail per produk: nama produk, total qty terjual, total revenue, total COGS, total profit
    - Loading skeleton, empty state, error state
    - _Requirements: 8.1–8.5_

- [x] 15. Frontend: Navigasi dan route guard
  - [x] 15.1 Tambahkan route baru ke router Vue
    - Tambahkan route `/purchase` → `PurchasePage.vue` (hanya pengelola)
    - Tambahkan route `/pos` → `POSPage.vue` (pengelola + kasir)
    - Tambahkan route `/reports/profit` → `ProfitReportPage.vue` (hanya pengelola)
    - Tambahkan route guard: jika kasir mencoba akses `/purchase` atau `/reports/profit`, redirect ke `/pos` dengan toast "Akses tidak diizinkan"
    - _Requirements: 11.1–11.4_

  - [x] 15.2 Tambahkan menu navigasi ke `Layout.vue`
    - Tambahkan item "Pembelian" (icon: ShoppingCart atau FileText) ke `NAV_ITEMS` dengan `roles: ['pengelola']`, path `/purchase`
    - Tambahkan item "Kasir" (icon: ShoppingBag atau CreditCard) ke `NAV_ITEMS` dengan `roles: ['pengelola', 'kasir']`, path `/pos`
    - Tambahkan item "Laporan Profit" ke `NAV_ITEMS` dengan `roles: ['pengelola']`, path `/reports/profit`
    - _Requirements: 11.1, 11.2, 11.3_

  - [ ]* 15.3 Tulis unit test untuk visibilitas menu navigasi (Property 12)
    - **Property 12: Visibilitas menu navigasi sesuai peran** — menu "Pembelian" dan "Laporan Profit" hanya muncul untuk pengelola; menu "Kasir" muncul untuk pengelola dan kasir
    - **Validates: Requirements 11.1, 11.2, 11.3**

- [x] 16. Checkpoint — Pastikan semua test frontend lulus
  - Pastikan semua test lulus, tanyakan ke user jika ada pertanyaan.

- [x] 17. Integrasi dan wiring akhir
  - [x] 17.1 Hubungkan `purchaseStore` dengan `PurchasePage` dan modal-modal invoice
    - Pastikan `PurchasePage` menggunakan `purchaseStore.fetchInvoices()` saat mount dan saat filter berubah
    - Pastikan `InvoiceFormModal` memanggil `purchaseStore.createInvoice()` dan merefresh daftar setelah sukses
    - Pastikan konfirmasi hapus invoice memanggil `purchaseStore.deleteInvoice()` dan merefresh daftar
    - _Requirements: 9.1–9.6_

  - [x] 17.2 Hubungkan `cartStore` dan `posService` dengan `POSPage` dan `ReceiptModal`
    - Pastikan `POSPage` menggunakan `cartStore` untuk semua operasi cart
    - Pastikan checkout memanggil `posService.createSale()` dengan data dari `cartStore`
    - Pastikan `ReceiptModal` menerima data sale dari response `createSale` dan menampilkannya
    - _Requirements: 5.1–5.7, 6.1–6.6_

  - [x] 17.3 Pastikan `products.cogs` ditampilkan di halaman produk
    - Update interface `Product` di `productService.ts` untuk menyertakan field `cogs`
    - Tambahkan kolom "COGS" ke tabel di `ProductListPage.vue` atau `ProductFormPage.vue`
    - _Requirements: 3.2_

  - [ ]* 17.4 Tulis integration test untuk alur lengkap (Property 5, 6, 13, 14, 15)
    - **Property 5: Stok produk konsisten setelah operasi invoice dan sale** — `current_stock = stock_awal + SUM(qty invoice) - SUM(qty sale)`
    - **Validates: Requirements 1.8, 5.4, 9.5**
    - **Property 6: Checkout ditolak jika stok tidak mencukupi** — return 422, stok tidak berubah
    - **Validates: Requirements 5.5**
    - **Property 13: Backend mengembalikan 403 untuk akses tanpa peran yang sesuai** — kasir tidak bisa akses `/api/invoices` dan `/api/reports/profit`
    - **Validates: Requirements 11.5**
    - **Property 14: Setiap invoice dan sale dicatat di audit_logs** — ada entri audit_log dengan entity_type yang sesuai
    - **Validates: Requirements 12.2, 12.3**
    - **Property 15: Snapshot COGS tersimpan di invoice_items (round-trip)** — nilai cogs_per_unit di invoice_item tidak berubah setelah invoice baru disimpan
    - **Validates: Requirements 2.4**

- [x] 18. Final checkpoint — Pastikan semua test lulus
  - Pastikan semua test lulus, tanyakan ke user jika ada pertanyaan.

## Catatan

- Task yang ditandai `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Setiap task mereferensikan persyaratan spesifik untuk keterlacakan
- Checkpoint memastikan validasi inkremental di setiap fase
- Property test memvalidasi properti kebenaran universal menggunakan fast-check
- Unit test memvalidasi skenario spesifik dan edge case
- Semua operasi yang mengubah stok harus menggunakan `DB::transaction` dengan `lockForUpdate` untuk mencegah race condition
- Snapshot `cogs` dan `sell_price` di `sale_items` tidak boleh pernah diubah setelah checkout
