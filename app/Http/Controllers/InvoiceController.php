<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * POST /api/invoices
     * Validasi, kalkulasi COGS, simpan invoice + invoice_items + update stok + update cogs produk
     * dalam satu DB transaction. Pengelola only.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        // ── 1. Validasi field wajib header ──────────────────────────────────────
        $requiredHeader = ['invoice_number', 'supplier_name', 'invoice_date', 'items'];
        $missingFields  = [];

        foreach ($requiredHeader as $field) {
            if (! isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $missingFields[] = $field;
            }
        }

        if (! empty($missingFields)) {
            return response()->json([
                'success' => false,
                'message' => 'Field wajib tidak boleh kosong.',
                'errors'  => array_fill_keys($missingFields, ['Field ini wajib diisi.']),
            ], 422);
        }

        // items harus array dengan minimal 1 elemen
        if (! is_array($data['items']) || count($data['items']) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice harus memiliki minimal 1 item.',
                'errors'  => ['items' => ['Invoice harus memiliki minimal 1 item.']],
            ], 422);
        }

        // ── 2. Cek duplikat invoice_number ──────────────────────────────────────
        if (Invoice::where('invoice_number', $data['invoice_number'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor invoice sudah digunakan',
            ], 409);
        }

        // ── 3. Validasi setiap item ─────────────────────────────────────────────
        $validPriceModes = ['final', 'before_discount'];
        $validDiscountTypes = ['percent', 'nominal'];
        $itemErrors = [];

        foreach ($data['items'] as $index => $item) {
            $errors = [];

            // product_id wajib dan harus ada di DB
            if (empty($item['product_id'])) {
                $errors[] = 'product_id wajib diisi.';
            } else {
                if (! Product::find($item['product_id'])) {
                    $errors[] = 'Produk tidak ditemukan.';
                }
            }

            // qty > 0
            if (! isset($item['qty']) || (int) $item['qty'] <= 0) {
                $errors[] = 'Qty harus lebih dari 0';
            }

            // price_input > 0
            if (! isset($item['price_input']) || (float) $item['price_input'] <= 0) {
                $errors[] = 'Harga input harus lebih dari 0.';
            }

            // price_mode valid
            if (empty($item['price_mode']) || ! in_array($item['price_mode'], $validPriceModes)) {
                $errors[] = 'price_mode harus "final" atau "before_discount".';
            }

            // diskon persen ≤ 100%
            if (
                ! empty($item['discount_item_type']) &&
                $item['discount_item_type'] === 'percent' &&
                isset($item['discount_item_value']) &&
                (float) $item['discount_item_value'] > 100
            ) {
                $errors[] = 'Diskon persen tidak boleh melebihi 100%.';
            }

            if (! empty($errors)) {
                $itemErrors["items.{$index}"] = $errors;
            }
        }

        if (! empty($itemErrors)) {
            // Return the first qty error with the specific message required
            foreach ($itemErrors as $key => $errors) {
                foreach ($errors as $error) {
                    if (str_contains($error, 'Qty harus lebih dari 0')) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Qty harus lebih dari 0',
                            'errors'  => $itemErrors,
                        ], 422);
                    }
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Validasi item gagal.',
                'errors'  => $itemErrors,
            ], 422);
        }

        // ── 4. Kalkulasi per item ───────────────────────────────────────────────
        $calculatedItems = [];

        foreach ($data['items'] as $item) {
            $priceInput        = (float) $item['price_input'];
            $qty               = (int) $item['qty'];
            $priceMode         = $item['price_mode'];
            $discountItemType  = $item['discount_item_type'] ?? null;
            $discountItemValue = (float) ($item['discount_item_value'] ?? 0);

            // Hitung price_per_unit_final
            if ($priceMode === 'final') {
                $pricePerUnitFinal = $priceInput;
            } else {
                // before_discount
                if ($discountItemType === 'percent') {
                    $pricePerUnitFinal = $priceInput * (1 - $discountItemValue / 100);
                } elseif ($discountItemType === 'nominal') {
                    $pricePerUnitFinal = $priceInput - $discountItemValue;
                } else {
                    // Tidak ada diskon per item
                    $pricePerUnitFinal = $priceInput;
                }
            }

            // Pastikan tidak negatif
            $pricePerUnitFinal = max(0, $pricePerUnitFinal);

            $subtotalItem = $pricePerUnitFinal * $qty;

            $calculatedItems[] = [
                'product_id'          => $item['product_id'],
                'qty'                 => $qty,
                'price_input'         => $priceInput,
                'price_mode'          => $priceMode,
                'discount_item_type'  => $discountItemType,
                'discount_item_value' => $discountItemValue,
                'price_per_unit_final' => $pricePerUnitFinal,
                'subtotal_before_global' => $subtotalItem,
            ];
        }

        // ── 5. Hitung total_before_discount ────────────────────────────────────
        $totalBeforeDiscount = array_sum(array_column($calculatedItems, 'subtotal_before_global'));

        // ── 6. Hitung total_global_discount ────────────────────────────────────
        $discountGlobalType  = $data['discount_global_type'] ?? null;
        $discountGlobalValue = (float) ($data['discount_global_value'] ?? 0);

        if ($discountGlobalType === 'percent') {
            $totalGlobalDiscount = $totalBeforeDiscount * ($discountGlobalValue / 100);
        } elseif ($discountGlobalType === 'nominal') {
            $totalGlobalDiscount = $discountGlobalValue;
        } else {
            $totalGlobalDiscount = 0;
        }

        // ── 7. Distribusikan diskon global secara proporsional ─────────────────
        foreach ($calculatedItems as &$item) {
            if ($totalBeforeDiscount > 0) {
                $item['global_discount_portion'] = ($item['subtotal_before_global'] / $totalBeforeDiscount) * $totalGlobalDiscount;
            } else {
                $item['global_discount_portion'] = 0;
            }

            // Hitung cogs_per_unit
            $item['cogs_per_unit'] = $item['qty'] > 0
                ? max(0, ($item['price_per_unit_final'] * $item['qty'] - $item['global_discount_portion']) / $item['qty'])
                : 0;

            // Hitung subtotal_final per item
            $item['subtotal_final'] = $item['price_per_unit_final'] * $item['qty'] - $item['global_discount_portion'];
        }
        unset($item);

        $totalFinal = $totalBeforeDiscount - $totalGlobalDiscount;

        // ── 8. DB Transaction: simpan semua data ───────────────────────────────
        $userId = auth('api')->id();

        $invoice = DB::transaction(function () use (
            $data,
            $calculatedItems,
            $totalBeforeDiscount,
            $totalGlobalDiscount,
            $totalFinal,
            $discountGlobalType,
            $discountGlobalValue,
            $userId
        ) {
            // 8a. Simpan Invoice
            $invoice = Invoice::create([
                'invoice_number'       => $data['invoice_number'],
                'supplier_name'        => $data['supplier_name'],
                'invoice_date'         => $data['invoice_date'],
                'discount_global_type' => $discountGlobalType,
                'discount_global_value' => $discountGlobalValue,
                'total_before_discount' => $totalBeforeDiscount,
                'total_discount'       => $totalGlobalDiscount,
                'total_final'          => $totalFinal,
                'recorded_by'          => $userId,
            ]);

            // 8b. Simpan semua InvoiceItem + update stok + update cogs produk
            foreach ($calculatedItems as $itemData) {
                InvoiceItem::create([
                    'invoice_id'             => $invoice->id,
                    'product_id'             => $itemData['product_id'],
                    'qty'                    => $itemData['qty'],
                    'price_input'            => $itemData['price_input'],
                    'price_mode'             => $itemData['price_mode'],
                    'discount_item_type'     => $itemData['discount_item_type'],
                    'discount_item_value'    => $itemData['discount_item_value'],
                    'price_per_unit_final'   => $itemData['price_per_unit_final'],
                    'global_discount_portion' => $itemData['global_discount_portion'],
                    'cogs_per_unit'          => $itemData['cogs_per_unit'],
                    'subtotal_final'         => $itemData['subtotal_final'],
                ]);

                // 8c. lockForUpdate + increment stok
                $product = Product::lockForUpdate()->find($itemData['product_id']);
                $product->increment('current_stock', $itemData['qty']);

                // 8d. Update cogs produk dengan last purchase price
                $product->update(['cogs' => $itemData['cogs_per_unit']]);
            }

            // 8e. Catat AuditLog
            AuditLog::create([
                'entity_type' => 'invoice',
                'entity_id'   => $invoice->id,
                'action'      => 'create',
                'changed_by'  => $userId,
                'old_data'    => null,
                'new_data'    => $invoice->toArray(),
            ]);

            return $invoice;
        });

        // Reload dengan relasi
        $invoice->load(['items.product', 'recordedBy:id,email']);

        return response()->json([
            'success' => true,
            'data'    => $invoice,
            'message' => 'Invoice berhasil disimpan',
        ], 201);
    }

    /**
     * GET /api/invoices/{id}
     * Detail invoice beserta semua items dan relasi product; return 404 jika tidak ditemukan.
     * Pengelola only.
     */
    public function show(string $id): JsonResponse
    {
        $invoice = Invoice::with([
            'items.product',
            'recordedBy:id,email',
        ])->find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $invoice,
        ]);
    }

    /**
     * DELETE /api/invoices/{id}
     * Hapus invoice, kembalikan stok produk, catat audit log.
     * Jika ada produk dari invoice ini yang sudah terjual via POS, sertakan flag has_sold_products: true.
     * Pengelola only.
     */
    public function destroy(string $id): JsonResponse
    {
        $invoice = Invoice::with('items')->find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice tidak ditemukan',
            ], 404);
        }

        // Kumpulkan semua product_id dari invoice items
        $productIds = $invoice->items->pluck('product_id')->unique()->values();

        // Cek apakah ada produk dari invoice ini yang sudah terjual (ada di sale_items)
        $hasSoldProducts = SaleItem::whereIn('product_id', $productIds)->exists();

        $userId = auth('api')->id();

        // Simpan data invoice sebelum dihapus untuk audit log
        $invoiceData = $invoice->toArray();

        DB::transaction(function () use ($invoice, $userId, $invoiceData) {
            // Kembalikan stok per produk secara atomik
            foreach ($invoice->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                if ($product) {
                    $product->decrement('current_stock', $item->qty);
                }
            }

            // Hapus invoice (cascade akan menghapus invoice_items)
            $invoice->delete();

            // Catat AuditLog
            AuditLog::create([
                'entity_type' => 'invoice',
                'entity_id'   => $invoiceData['id'],
                'action'      => 'delete',
                'changed_by'  => $userId,
                'old_data'    => $invoiceData,
                'new_data'    => null,
            ]);
        });

        $response = [
            'success' => true,
            'message' => 'Invoice berhasil dihapus',
        ];

        if ($hasSoldProducts) {
            $response['has_sold_products'] = true;
        }

        return response()->json($response);
    }

    /**
     * GET /api/invoices
     * Daftar invoice dengan filter tanggal, supplier, pencarian nomor invoice; pagination 15/halaman.
     * Pengelola only.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::withCount('items')
            ->with('recordedBy:id,email');

        // Filter start_date (berdasarkan invoice_date)
        if ($startDate = $request->query('start_date')) {
            $query->whereDate('invoice_date', '>=', $startDate);
        }

        // Filter end_date (berdasarkan invoice_date)
        if ($endDate = $request->query('end_date')) {
            $query->whereDate('invoice_date', '<=', $endDate);
        }

        // Filter supplier_name (LIKE %value%)
        if ($supplierName = $request->query('supplier_name')) {
            $query->where('supplier_name', 'LIKE', "%{$supplierName}%");
        }

        // Search nomor invoice (LIKE %value%)
        if ($search = $request->query('search')) {
            $query->where('invoice_number', 'LIKE', "%{$search}%");
        }

        $invoices = $query->orderBy('invoice_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $invoices,
        ]);
    }
}
