# Dokumen Desain: POS, Purchase & Profit Tracking

## Ikhtisar

Fitur ini menambahkan tiga modul baru ke sistem inventori Cahaya Prima:

1. **Purchase Module** — pencatatan invoice pembelian dari supplier, dengan kalkulasi COGS otomatis per item setelah distribusi diskon.
2. **POS (Point of Sale)** — antarmuka kasir berbasis keranjang belanja untuk memproses transaksi penjualan, mendukung pembayaran tunai dan QR, serta mencetak struk.
3. **Profit Tracking** — laporan profit akurat berbasis COGS snapshot dari invoice pembelian, bukan input manual.

**Masalah yang diselesaikan:** Sistem lama meminta kasir menginput harga beli secara manual saat transaksi keluar, sehingga profit tidak akurat. Solusinya: COGS diambil otomatis dari invoice pembelian dan di-snapshot ke setiap `sale_item` saat checkout.

**Integrasi dengan sistem yang ada:**
- Menggunakan tabel `products`, `audit_logs`, `notifications`, dan sistem autentikasi JWT yang sudah ada.
- Menambahkan kolom `cogs` ke tabel `products` via migration baru.
- Menambahkan tabel baru: `invoices`, `invoice_items`, `sales`, `sale_items`.
- Mengikuti pola arsitektur yang sudah ada: Laravel API + Vue 3 + Pinia + TailwindCSS.

---

## Arsitektur

### Gambaran Umum

```
┌─────────────────────────────────────────────────────────────────┐
│                        Frontend (Vue 3 + Pinia)                  │
│                                                                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐   │
│  │ PurchasePage │  │   POSPage    │  │   ProfitReportPage   │   │
│  │ (Pengelola)  │  │ (Kasir+Peng) │  │    (Pengelola)       │   │
│  └──────┬───────┘  └──────┬───────┘  └──────────┬───────────┘   │
│         │                 │                       │               │
│  ┌──────▼───────┐  ┌──────▼───────┐  ┌──────────▼───────────┐   │
│  │purchaseStore │  │  cartStore   │  │  reportStore (ext.)  │   │
│  └──────┬───────┘  └──────┬───────┘  └──────────┬───────────┘   │
│         │                 │                       │               │
│  ┌──────▼───────┐  ┌──────▼───────┐  ┌──────────▼───────────┐   │
│  │purchaseService│ │  posService  │  │   reportService(ext.)│   │
│  └──────┬───────┘  └──────┬───────┘  └──────────┬───────────┘   │
└─────────┼─────────────────┼───────────────────────┼─────────────┘
          │  HTTP/JSON API  │                        │
┌─────────▼─────────────────▼────────────────────────▼─────────────┐
│                     Laravel API (JWT Auth)                         │
│                                                                    │
│  POST /api/invoices          InvoiceController@store               │
│  GET  /api/invoices          InvoiceController@index               │
│  GET  /api/invoices/{id}     InvoiceController@show                │
│  DELETE /api/invoices/{id}   InvoiceController@destroy             │
│  POST /api/sales             SaleController@store                  │
│  GET  /api/sales/{id}        SaleController@show                   │
│  GET  /api/reports/profit    ReportController@profit               │
└────────────────────────────────────────────────────────────────────┘
          │
┌─────────▼──────────────────────────────────────────────────────────┐
│                         Database (MySQL/SQLite)                      │
│                                                                      │
│  products (+ kolom cogs)   invoices    invoice_items                 │
│  sales                     sale_items  audit_logs (existing)         │
└──────────────────────────────────────────────────────────────────────┘
```

### Prinsip Desain

1. **COGS sebagai snapshot** — Nilai `cogs` di `sale_items` tidak pernah berubah setelah checkout, menjamin akurasi laporan profit historis.
2. **Atomisitas database** — Semua operasi yang mengubah stok (simpan invoice, checkout POS, hapus invoice) dibungkus dalam satu `DB::transaction`.
3. **Kalkulasi di frontend** — Preview kalkulasi invoice (subtotal, diskon, COGS) dihitung di frontend secara real-time tanpa request ke server.
4. **Validasi di backend** — Backend selalu memvalidasi ulang kalkulasi dan stok sebelum menyimpan, tidak mempercayai nilai dari frontend.
5. **Role-based access** — Middleware `role:pengelola` melindungi endpoint Purchase dan Profit Report; POS dapat diakses oleh `pengelola` dan `kasir`.

---

## Komponen dan Antarmuka

### Backend: Controller Baru

#### `InvoiceController`
- `index(Request)` — Daftar invoice dengan filter tanggal, supplier, pencarian nomor invoice; pagination 15/halaman.
- `store(Request)` — Validasi, kalkulasi COGS, simpan invoice + invoice_items + update stok + update cogs produk dalam satu DB transaction.
- `show(string $id)` — Detail invoice beserta semua invoice_items.
- `destroy(string $id)` — Hapus invoice, kembalikan stok, catat audit log. Jika ada produk yang sudah terjual via POS, tampilkan peringatan tapi tetap izinkan penghapusan.

#### `SaleController`
- `store(Request)` — Validasi stok, simpan sale + sale_items + kurangi stok dalam satu DB transaction. Snapshot `sell_price` dan `cogs` dari produk saat transaksi.
- `show(string $id)` — Detail sale beserta sale_items (untuk receipt).

#### `ReportController` (ekstensi)
- `profit(Request)` — Laporan profit dengan filter tanggal; menghitung revenue, COGS, profit dari `sale_items`.

### Backend: Model Baru

#### `Invoice`
```php
// Relasi: hasMany InvoiceItem, belongsTo User (recorded_by)
// Fillable: invoice_number, supplier_name, invoice_date, discount_global_type, discount_global_value, total_before_discount, total_discount, total_final, recorded_by
```

#### `InvoiceItem`
```php
// Relasi: belongsTo Invoice, belongsTo Product
// Fillable: invoice_id, product_id, qty, price_input, price_mode, discount_item_type, discount_item_value, price_per_unit_final, cogs_per_unit, subtotal_final
```

#### `Sale`
```php
// Relasi: hasMany SaleItem, belongsTo User (recorded_by)
// Fillable: transaction_date, subtotal, total_discount, total, payment_method, amount_paid, change_amount, recorded_by
```

#### `SaleItem`
```php
// Relasi: belongsTo Sale, belongsTo Product
// Fillable: sale_id, product_id, qty, sell_price, cogs, discount_per_item, subtotal
```

### Frontend: Halaman Baru

#### `PurchasePage.vue` (`/purchase`)
- Daftar invoice dengan filter dan pencarian.
- Tombol "Buat Invoice Baru" membuka `InvoiceFormModal.vue`.
- Klik baris invoice membuka `InvoiceDetailModal.vue`.

#### `InvoiceFormModal.vue`
- Form multi-item dengan preview kalkulasi real-time.
- Computed properties untuk subtotal, diskon, COGS per item.
- Validasi client-side sebelum submit.

#### `POSPage.vue` (`/pos`)
- Layout dua kolom (desktop/tablet): search + cart kiri, summary kanan.
- Layout satu kolom (mobile): search → cart → summary → checkout.
- Setelah checkout sukses, tampilkan `ReceiptModal.vue`.

#### `ReceiptModal.vue`
- Tampilan struk dengan CSS print-optimized.
- Tombol "Cetak" dan "Transaksi Baru".

#### `ProfitReportPage.vue` (`/reports/profit`)
- Tab baru di halaman laporan yang sudah ada, atau halaman terpisah.
- Filter rentang tanggal, tabel ringkasan dan detail per produk.

### Frontend: Store Baru

#### `cartStore.ts`
```typescript
// State: items: CartItem[], paymentMethod, amountPaid
// Actions: addItem, removeItem, updateQty, updateDiscount, clearCart
// Getters: subtotal, totalDiscount, total, change, isValid
```

#### `purchaseStore.ts`
```typescript
// State: invoices, currentInvoice, loading, error
// Actions: fetchInvoices, createInvoice, fetchInvoice, deleteInvoice
```

### Frontend: Service Baru

#### `invoiceService.ts`
```typescript
// getInvoices(params), createInvoice(payload), getInvoice(id), deleteInvoice(id)
```

#### `posService.ts`
```typescript
// createSale(payload), getSale(id)
```

---

## Model Data

### Tabel Baru: `invoices`

```sql
CREATE TABLE invoices (
    id                    UUID PRIMARY KEY,
    invoice_number        VARCHAR(100) UNIQUE NOT NULL,
    supplier_name         VARCHAR(255) NOT NULL,
    invoice_date          DATE NOT NULL,
    discount_global_type  ENUM('percent', 'nominal') NULL,
    discount_global_value DECIMAL(15,2) DEFAULT 0,
    total_before_discount DECIMAL(15,2) NOT NULL,
    total_discount        DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_final           DECIMAL(15,2) NOT NULL,
    recorded_by           UUID NOT NULL,
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);
```

### Tabel Baru: `invoice_items`

```sql
CREATE TABLE invoice_items (
    id                   UUID PRIMARY KEY,
    invoice_id           UUID NOT NULL,
    product_id           UUID NOT NULL,
    qty                  INTEGER NOT NULL,
    price_input          DECIMAL(15,2) NOT NULL,
    price_mode           ENUM('final', 'before_discount') NOT NULL DEFAULT 'final',
    discount_item_type   ENUM('percent', 'nominal') NULL,
    discount_item_value  DECIMAL(15,2) DEFAULT 0,
    price_per_unit_final DECIMAL(15,2) NOT NULL,  -- setelah diskon per item
    global_discount_portion DECIMAL(15,2) NOT NULL DEFAULT 0,  -- porsi diskon global
    cogs_per_unit        DECIMAL(15,2) NOT NULL,  -- snapshot COGS yang disimpan
    subtotal_final       DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

### Tabel Baru: `sales`

```sql
CREATE TABLE sales (
    id               UUID PRIMARY KEY,
    transaction_date DATE NOT NULL,
    subtotal         DECIMAL(15,2) NOT NULL,
    total_discount   DECIMAL(15,2) NOT NULL DEFAULT 0,
    total            DECIMAL(15,2) NOT NULL,
    payment_method   ENUM('cash', 'qr') NOT NULL DEFAULT 'cash',
    amount_paid      DECIMAL(15,2) NOT NULL,
    change_amount    DECIMAL(15,2) NOT NULL DEFAULT 0,
    recorded_by      UUID NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);
```

### Tabel Baru: `sale_items`

```sql
CREATE TABLE sale_items (
    id               UUID PRIMARY KEY,
    sale_id          UUID NOT NULL,
    product_id       UUID NOT NULL,
    qty              INTEGER NOT NULL,
    sell_price       DECIMAL(15,2) NOT NULL,  -- snapshot harga jual saat transaksi
    cogs             DECIMAL(15,2) NOT NULL,  -- snapshot COGS saat transaksi
    discount_per_item DECIMAL(15,2) NOT NULL DEFAULT 0,
    subtotal         DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

### Perubahan Tabel yang Ada: `products`

Migration baru menambahkan kolom:
```sql
ALTER TABLE products ADD COLUMN cogs DECIMAL(15,2) NOT NULL DEFAULT 0;
```

### Diagram Relasi

```
products ──────────────────────────────────────────────────────────┐
    │                                                               │
    │ 1:N                                                           │ 1:N
    ▼                                                               ▼
invoice_items ──── N:1 ──── invoices ──── N:1 ──── users    sale_items ──── N:1 ──── sales ──── N:1 ──── users
```

### Logika Kalkulasi COGS

```
Untuk setiap invoice_item:
  1. Hitung price_per_unit_final:
     - Jika price_mode = 'final':
         price_per_unit_final = price_input
     - Jika price_mode = 'before_discount':
         - Jika discount_item_type = 'percent':
             price_per_unit_final = price_input × (1 - discount_item_value / 100)
         - Jika discount_item_type = 'nominal':
             price_per_unit_final = price_input - discount_item_value

  2. Hitung subtotal_item_before_global = price_per_unit_final × qty

  3. Hitung porsi diskon global per item:
     global_discount_portion = (subtotal_item_before_global / total_before_global_discount) × total_global_discount

  4. Hitung COGS per unit:
     cogs_per_unit = (price_per_unit_final × qty - global_discount_portion) / qty
                   = price_per_unit_final - (global_discount_portion / qty)
```

### Logika Kalkulasi Profit

```
Untuk setiap sale_item:
  profit_per_item = (sell_price - cogs) × qty

Total profit = SUM(profit_per_item) untuk semua sale_items dalam periode
Margin (%) = (total_profit / total_revenue) × 100
```

---

## Properti Kebenaran

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: COGS per unit tidak negatif

*Untuk setiap* kombinasi `price_input` ≥ 0, `qty` > 0, diskon per item (persen 0–100% atau nominal ≥ 0), dan `global_discount_portion` ≥ 0 yang valid, nilai `cogs_per_unit` yang dihitung SHALL selalu ≥ 0.

**Validates: Requirements 1.6**

### Property 2: Distribusi diskon global bersifat proporsional dan konservatif

*Untuk setiap* invoice dengan satu atau lebih item dan diskon global D ≥ 0, jumlah semua `global_discount_portion` di semua invoice_items SHALL sama dengan D dalam toleransi pembulatan ±0.01.

**Validates: Requirements 1.5, 1.6**

### Property 3: Price_Mode "final" mengabaikan diskon per item

*Untuk setiap* invoice_item dengan `price_mode = 'final'` dan nilai `price_input` apapun, nilai `price_per_unit_final` SHALL sama dengan `price_input`, terlepas dari nilai `discount_item_value` yang dimasukkan.

**Validates: Requirements 1.3**

### Property 4: Price_Mode "before_discount" menerapkan diskon per item dengan benar

*Untuk setiap* invoice_item dengan `price_mode = 'before_discount'`, nilai `price_per_unit_final` SHALL sama dengan `price_input × (1 - D/100)` untuk diskon persen D, atau `price_input - N` untuk diskon nominal N.

**Validates: Requirements 1.4**

### Property 5: Stok produk konsisten setelah operasi invoice dan sale

*Untuk setiap* produk, nilai `current_stock` setelah serangkaian operasi SHALL sama dengan `stock_awal + SUM(qty invoice_items) - SUM(qty sale_items)`. Secara spesifik: (a) menyimpan invoice menambah stok sebesar qty di invoice_item; (b) checkout POS mengurangi stok sebesar qty di sale_item; (c) menghapus invoice mengembalikan stok ke nilai sebelum invoice.

**Validates: Requirements 1.8, 5.4, 9.5**

### Property 6: Checkout ditolak jika stok tidak mencukupi

*Untuk setiap* permintaan checkout di mana qty yang diminta untuk produk manapun melebihi `current_stock` produk tersebut, backend SHALL mengembalikan error 422 dan `current_stock` SHALL tidak berubah.

**Validates: Requirements 5.5**

### Property 7: Kembalian = nominal_bayar − total (metode tunai)

*Untuk setiap* transaksi POS dengan metode pembayaran tunai dan `amount_paid ≥ total`, nilai `change_amount` yang dihitung dan disimpan SHALL sama dengan `amount_paid - total`.

**Validates: Requirements 4.9, 5.2**

### Property 8: Penghapusan dan penambahan item di Cart

*Untuk setiap* Cart dengan satu atau lebih Cart_Item: (a) menambahkan produk yang sudah ada di Cart SHALL menambah qty-nya sebesar 1; (b) menambahkan produk baru SHALL membuat Cart_Item baru dengan qty = 1; (c) mengubah qty Cart_Item menjadi 0 atau mengklik hapus SHALL menghilangkan item tersebut dari Cart; (d) Cart_Item lain SHALL tidak terpengaruh.

**Validates: Requirements 4.3, 4.6, 4.7**

### Property 9: Snapshot COGS di sale_items tidak berubah setelah invoice baru

*Untuk setiap* sale_item yang sudah tersimpan, nilai `cogs` di sale_item tersebut SHALL tidak berubah meskipun invoice pembelian baru untuk produk yang sama disimpan setelahnya dan mengubah `cogs` di tabel `products`.

**Validates: Requirements 5.3, 8.6**

### Property 10: Kalkulasi profit menggunakan snapshot COGS

*Untuk setiap* sale_item dengan `sell_price`, `cogs` (snapshot), dan `qty`, profit yang dilaporkan SHALL sama dengan `(sell_price - cogs) × qty`. Untuk setiap kumpulan sale_items dalam satu periode, `total_revenue = SUM(sell_price × qty)`, `total_cogs = SUM(cogs × qty)`, `total_profit = total_revenue - total_cogs`, dan `margin = total_profit / total_revenue × 100`.

**Validates: Requirements 8.1, 8.2, 8.6**

### Property 11: Filter tanggal hanya mengembalikan data dalam rentang

*Untuk setiap* query dengan filter `start_date` dan `end_date`, semua record yang dikembalikan (invoice maupun sale) SHALL memiliki tanggal yang berada dalam rentang `[start_date, end_date]` inklusif.

**Validates: Requirements 8.4, 9.3**

### Property 12: Visibilitas menu navigasi sesuai peran

*Untuk setiap* pengguna dengan peran tertentu, menu yang ditampilkan di sidebar SHALL sesuai dengan peran: menu "Pembelian" dan "Laporan Profit" hanya muncul untuk `pengelola`; menu "Kasir" muncul untuk `pengelola` dan `kasir`.

**Validates: Requirements 11.1, 11.2, 11.3**

### Property 13: Backend mengembalikan 403 untuk akses tanpa peran yang sesuai

*Untuk setiap* request ke endpoint Purchase (`/api/invoices`) atau Profit Report (`/api/reports/profit`) yang dilakukan oleh pengguna dengan peran `kasir`, backend SHALL mengembalikan HTTP 403 dan tidak memproses request.

**Validates: Requirements 11.5**

### Property 14: Setiap invoice dan sale dicatat di audit_logs

*Untuk setiap* invoice yang disimpan atau dihapus, dan setiap sale yang disimpan, SHALL ada minimal satu entri di tabel `audit_logs` dengan `entity_type` yang sesuai (`'invoice'` atau `'sale'`) dan `entity_id` yang cocok.

**Validates: Requirements 12.2, 12.3**

### Property 15: Snapshot COGS tersimpan di invoice_items (round-trip)

*Untuk setiap* invoice yang disimpan, nilai `cogs_per_unit` yang tersimpan di setiap `invoice_item` SHALL sama dengan nilai yang dihitung dari formula COGS pada saat penyimpanan, dan SHALL tidak berubah meskipun invoice baru untuk produk yang sama disimpan di kemudian hari.

**Validates: Requirements 2.4**

---

## Penanganan Error

### Backend

| Kondisi | HTTP Status | Error Code | Pesan |
|---|---|---|---|
| Nomor invoice duplikat | 409 | `CONFLICT` | "Nomor invoice sudah digunakan" |
| Qty invoice_item ≤ 0 | 400 | `VALIDATION_ERROR` | "Qty harus lebih dari 0" |
| Produk tidak ditemukan | 404 | `NOT_FOUND` | "Produk tidak ditemukan" |
| Stok tidak cukup saat checkout | 422 | `BUSINESS_RULE_VIOLATION` | "Stok [nama produk] tidak mencukupi (tersedia: X)" |
| Akses tanpa role yang sesuai | 403 | `FORBIDDEN` | "Anda tidak memiliki izin untuk mengakses halaman ini" |
| Cart kosong saat checkout | 400 | `VALIDATION_ERROR` | "Cart tidak boleh kosong" |
| Nominal bayar kurang (tunai) | 400 | `VALIDATION_ERROR` | "Nominal pembayaran kurang dari total" |

### Frontend

- **Validasi real-time** di form invoice: qty > 0, harga > 0, diskon persen ≤ 100%.
- **Debounce 300ms** pada search bar POS untuk menghindari request berlebihan.
- **Optimistic UI** tidak digunakan — semua perubahan stok menunggu konfirmasi backend.
- **Toast error** ditampilkan untuk semua kegagalan API.
- **Loading state** pada tombol Checkout dan Simpan Invoice untuk mencegah double-submit.
- **Konfirmasi dialog** sebelum menghapus invoice, dengan peringatan tambahan jika produk sudah terjual.

### Konsistensi Data

- Semua operasi yang mengubah stok menggunakan `DB::transaction` dengan `lockForUpdate()` pada baris produk untuk mencegah race condition.
- Jika salah satu item dalam invoice gagal diproses, seluruh transaksi di-rollback.
- Penghapusan invoice mengembalikan stok secara atomik dalam satu transaksi.

---

## Strategi Pengujian

### Pendekatan Dual Testing

Fitur ini menggunakan dua lapisan pengujian yang saling melengkapi:

1. **Property-Based Tests** — Memverifikasi logika kalkulasi COGS, distribusi diskon, kalkulasi profit, dan invariant stok menggunakan property-based testing dengan **fast-check** (sudah tersedia di `node_modules/fast-check`).
2. **Unit Tests** — Memverifikasi skenario spesifik, edge case, dan perilaku UI.
3. **Integration Tests** — Memverifikasi alur end-to-end dan interaksi antar komponen.

### Property-Based Testing

Library: **fast-check** untuk frontend TypeScript; **PHPUnit** dengan data provider untuk backend PHP.

Setiap property test dikonfigurasi untuk minimum **100 iterasi**.

Format tag: `Feature: pos-purchase-inventory, Property {N}: {deskripsi singkat}`

**Property 1: COGS per unit tidak negatif**
```typescript
// Feature: pos-purchase-inventory, Property 1: cogs_per_unit >= 0
fc.assert(fc.property(
  fc.record({
    priceInput: fc.float({ min: 0.01, max: 1_000_000 }),
    qty: fc.integer({ min: 1, max: 10_000 }),
    discountItemPercent: fc.float({ min: 0, max: 100 }),
    globalDiscountPortion: fc.float({ min: 0 }),
  }),
  ({ priceInput, qty, discountItemPercent, globalDiscountPortion }) => {
    const priceFinal = priceInput * (1 - discountItemPercent / 100);
    // global_discount_portion cannot exceed subtotal
    const cappedGlobalPortion = Math.min(globalDiscountPortion, priceFinal * qty);
    const cogsPerUnit = priceFinal - (cappedGlobalPortion / qty);
    return cogsPerUnit >= 0;
  }
), { numRuns: 100 });
```

**Property 2: Distribusi diskon global konservatif**
```typescript
// Feature: pos-purchase-inventory, Property 2: sum(global_discount_portions) == total_global_discount
fc.assert(fc.property(
  fc.array(fc.float({ min: 0.01, max: 1_000_000 }), { minLength: 1, maxLength: 20 }),
  fc.float({ min: 0, max: 1_000_000 }),
  (subtotals, globalDiscount) => {
    const totalSubtotal = subtotals.reduce((a, b) => a + b, 0);
    if (totalSubtotal === 0) return true;
    const portions = subtotals.map(s => (s / totalSubtotal) * globalDiscount);
    const sumPortions = portions.reduce((a, b) => a + b, 0);
    return Math.abs(sumPortions - globalDiscount) < 0.01;
  }
), { numRuns: 100 });
```

**Property 3: Price_Mode "final" mengabaikan diskon per item**
```typescript
// Feature: pos-purchase-inventory, Property 3: price_mode=final ignores discount
fc.assert(fc.property(
  fc.float({ min: 0.01, max: 1_000_000 }),
  fc.float({ min: 0, max: 1_000_000 }),
  (priceInput, discountValue) => {
    const result = calculatePricePerUnitFinal(priceInput, 'final', 'percent', discountValue);
    return result === priceInput;
  }
), { numRuns: 100 });
```

**Property 4: Price_Mode "before_discount" menerapkan diskon per item**
```typescript
// Feature: pos-purchase-inventory, Property 4: price_mode=before_discount applies discount
fc.assert(fc.property(
  fc.float({ min: 0.01, max: 1_000_000 }),
  fc.float({ min: 0, max: 100 }),
  (priceInput, discountPercent) => {
    const result = calculatePricePerUnitFinal(priceInput, 'before_discount', 'percent', discountPercent);
    const expected = priceInput * (1 - discountPercent / 100);
    return Math.abs(result - expected) < 0.001;
  }
), { numRuns: 100 });
```

**Property 7: Kembalian = nominal_bayar − total**
```typescript
// Feature: pos-purchase-inventory, Property 7: change == amount_paid - total
fc.assert(fc.property(
  fc.float({ min: 0.01, max: 10_000_000 }),
  fc.float({ min: 0, max: 10_000_000 }),
  (total, extraPayment) => {
    const amountPaid = total + extraPayment;
    const change = calculateChange(amountPaid, total);
    return Math.abs(change - extraPayment) < 0.01;
  }
), { numRuns: 100 });
```

**Property 10: Kalkulasi profit menggunakan snapshot COGS**
```typescript
// Feature: pos-purchase-inventory, Property 10: profit == (sell_price - cogs) * qty
fc.assert(fc.property(
  fc.array(
    fc.record({
      sellPrice: fc.float({ min: 0, max: 10_000_000 }),
      cogs: fc.float({ min: 0, max: 10_000_000 }),
      qty: fc.integer({ min: 1, max: 10_000 }),
    }),
    { minLength: 1, maxLength: 50 }
  ),
  (saleItems) => {
    const totalRevenue = saleItems.reduce((s, i) => s + i.sellPrice * i.qty, 0);
    const totalCogs = saleItems.reduce((s, i) => s + i.cogs * i.qty, 0);
    const totalProfit = totalRevenue - totalCogs;
    const calculatedProfit = saleItems.reduce((s, i) => s + (i.sellPrice - i.cogs) * i.qty, 0);
    return Math.abs(totalProfit - calculatedProfit) < 0.01;
  }
), { numRuns: 100 });
```

### Unit Tests (Contoh Spesifik)

- **Invoice dengan diskon global 0** — COGS per unit = price_per_unit_final.
- **Invoice dengan satu item** — Seluruh diskon global jatuh ke item tersebut.
- **POS checkout dengan stok pas** — Stok menjadi 0 setelah checkout.
- **POS checkout dengan stok kurang** — Backend mengembalikan 422 dan stok tidak berubah.
- **Hapus invoice** — Stok dikembalikan ke nilai sebelum invoice.
- **Metode QR** — Checkout berhasil tanpa input nominal bayar, kembalian = 0.
- **Cart kosong** — Tombol Checkout dinonaktifkan.
- **Nomor invoice duplikat** — Backend mengembalikan 409.
- **Qty invoice_item = 0** — Validasi menolak dengan pesan error.

### Integration Tests

- **Alur lengkap**: Buat invoice → cek stok bertambah → checkout POS → cek stok berkurang → cek laporan profit menggunakan snapshot COGS.
- **Atomisitas**: Simulasi kegagalan di tengah transaksi → verifikasi rollback dan stok tidak berubah.
- **Role access**: Kasir tidak bisa akses endpoint Purchase dan Profit Report (HTTP 403).
- **Audit log**: Setiap invoice dan sale tercatat di `audit_logs` dengan `entity_type` yang benar.
- **Snapshot immutability**: Simpan sale, buat invoice baru untuk produk yang sama, verifikasi `sale_items.cogs` tidak berubah.

### Frontend Tests

- **cartStore**: Unit test untuk `addItem` (produk baru dan yang sudah ada), `removeItem`, `updateQty` ke 0 (auto-remove), `clearCart`, dan computed `total`, `change`, `isValid`.
- **Kalkulasi COGS**: Unit test untuk fungsi `calculateCogs` dan `calculatePricePerUnitFinal` dengan berbagai kombinasi Price_Mode dan tipe diskon.
- **Debounce search**: Verifikasi bahwa request tidak dikirim lebih dari sekali per 300ms saat mengetik di search bar POS.
