<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * POST /api/sales
     * Validasi, simpan sale + sale_items + kurangi stok dalam satu DB transaction.
     * Snapshot sell_price dan cogs dari produk saat transaksi.
     * Accessible by pengelola + kasir.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        // ── 1. Validasi items tidak kosong ──────────────────────────────────────
        if (empty($data['items']) || ! is_array($data['items']) || count($data['items']) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cart tidak boleh kosong',
                'errors'  => ['items' => ['Cart tidak boleh kosong']],
            ], 400);
        }

        // ── 2. Validasi payment_method ──────────────────────────────────────────
        $validPaymentMethods = ['cash', 'qr'];
        $paymentMethod = $data['payment_method'] ?? null;

        if (! in_array($paymentMethod, $validPaymentMethods)) {
            return response()->json([
                'success' => false,
                'message' => 'Metode pembayaran tidak valid.',
                'errors'  => ['payment_method' => ['Metode pembayaran harus "cash" atau "qr".']],
            ], 422);
        }

        // ── 3. Validasi setiap item ─────────────────────────────────────────────
        $itemErrors = [];

        foreach ($data['items'] as $index => $item) {
            $errors = [];

            if (empty($item['product_id'])) {
                $errors[] = 'product_id wajib diisi.';
            } else {
                if (! Product::find($item['product_id'])) {
                    $errors[] = 'Produk tidak ditemukan.';
                }
            }

            if (! isset($item['qty']) || (int) $item['qty'] <= 0) {
                $errors[] = 'Qty harus lebih dari 0.';
            }

            if (! empty($errors)) {
                $itemErrors["items.{$index}"] = $errors;
            }
        }

        if (! empty($itemErrors)) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi item gagal.',
                'errors'  => $itemErrors,
            ], 422);
        }

        // ── 4. Hitung total dari items ──────────────────────────────────────────
        // We'll recalculate inside the transaction using locked product prices,
        // but we also accept a pre-calculated total from the frontend for
        // amount_paid validation. Use the provided total if given, otherwise
        // we'll compute it inside the transaction.
        $providedTotal    = isset($data['total']) ? (float) $data['total'] : null;
        $totalDiscount    = isset($data['total_discount']) ? (float) $data['total_discount'] : 0;
        $subtotalProvided = isset($data['subtotal']) ? (float) $data['subtotal'] : null;

        // ── 5. Validasi amount_paid untuk metode cash ───────────────────────────
        $amountPaid = isset($data['amount_paid']) ? (float) $data['amount_paid'] : 0;

        if ($paymentMethod === 'cash') {
            if ($providedTotal !== null && $amountPaid < $providedTotal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nominal pembayaran kurang dari total',
                    'errors'  => ['amount_paid' => ['Nominal pembayaran kurang dari total']],
                ], 400);
            }
        }

        // ── 6. DB Transaction ───────────────────────────────────────────────────
        $userId = auth('api')->id();

        try {
            $sale = DB::transaction(function () use (
                $data,
                $paymentMethod,
                $amountPaid,
                $totalDiscount,
                $userId
            ) {
                $items = $data['items'];

                // 6a. Lock semua produk dan validasi stok
                $productMap = [];
                foreach ($items as $item) {
                    $product = Product::lockForUpdate()->find($item['product_id']);

                    if (! $product) {
                        throw new \DomainException("Produk tidak ditemukan.");
                    }

                    $qty = (int) $item['qty'];

                    if ($product->current_stock < $qty) {
                        throw new \DomainException(
                            "Stok {$product->name} tidak mencukupi (tersedia: {$product->current_stock})"
                        );
                    }

                    $productMap[$item['product_id']] = $product;
                }

                // 6b. Hitung subtotal dari snapshot harga produk
                $subtotal = 0;
                $calculatedItems = [];

                foreach ($items as $item) {
                    $product          = $productMap[$item['product_id']];
                    $qty              = (int) $item['qty'];
                    $sellPrice        = (float) $product->sell_price;
                    $cogs             = (float) $product->cogs;
                    $discountPerItem  = isset($item['discount_per_item']) ? (float) $item['discount_per_item'] : 0;
                    $itemSubtotal     = ($sellPrice - $discountPerItem) * $qty;

                    $subtotal += $itemSubtotal;

                    $calculatedItems[] = [
                        'product_id'       => $item['product_id'],
                        'qty'              => $qty,
                        'sell_price'       => $sellPrice,
                        'cogs'             => $cogs,
                        'discount_per_item' => $discountPerItem,
                        'subtotal'         => $itemSubtotal,
                    ];
                }

                // total_discount dari request (diskon keseluruhan, jika ada)
                $totalDiscountVal = isset($data['total_discount']) ? (float) $data['total_discount'] : 0;
                $total            = $subtotal - $totalDiscountVal;

                // 6c. Hitung change_amount
                $amountPaidFinal = (float) ($data['amount_paid'] ?? 0);

                if ($data['payment_method'] === 'qr') {
                    // QR: anggap lunas, kembalian = 0, amount_paid = total
                    $amountPaidFinal = $total;
                    $changeAmount    = 0;
                } else {
                    // cash: validasi ulang amount_paid >= total
                    if ($amountPaidFinal < $total) {
                        throw new \DomainException("Nominal pembayaran kurang dari total");
                    }
                    $changeAmount = $amountPaidFinal - $total;
                }

                // 6d. Simpan Sale
                $sale = Sale::create([
                    'transaction_date' => now()->toDateString(),
                    'subtotal'         => $subtotal,
                    'total_discount'   => $totalDiscountVal,
                    'total'            => $total,
                    'payment_method'   => $data['payment_method'],
                    'amount_paid'      => $amountPaidFinal,
                    'change_amount'    => $changeAmount,
                    'recorded_by'      => $userId,
                ]);

                // 6e. Simpan SaleItems + kurangi stok
                foreach ($calculatedItems as $itemData) {
                    SaleItem::create([
                        'sale_id'          => $sale->id,
                        'product_id'       => $itemData['product_id'],
                        'qty'              => $itemData['qty'],
                        'sell_price'       => $itemData['sell_price'],
                        'cogs'             => $itemData['cogs'],
                        'discount_per_item' => $itemData['discount_per_item'],
                        'subtotal'         => $itemData['subtotal'],
                    ]);

                    // Kurangi stok (produk sudah di-lock di atas)
                    $productMap[$itemData['product_id']]->decrement('current_stock', $itemData['qty']);
                }

                // 6f. Catat AuditLog
                AuditLog::create([
                    'entity_type' => 'sale',
                    'entity_id'   => $sale->id,
                    'action'      => 'create',
                    'changed_by'  => $userId,
                    'old_data'    => null,
                    'new_data'    => $sale->toArray(),
                ]);

                return $sale;
            });
        } catch (\DomainException $e) {
            $message = $e->getMessage();

            // Stok tidak mencukupi → 422
            if (str_contains($message, 'tidak mencukupi')) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            // Pembayaran kurang → 400
            if (str_contains($message, 'kurang dari total')) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors'  => ['amount_paid' => [$message]],
                ], 400);
            }

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        // Reload dengan relasi
        $sale->load(['items.product', 'recordedBy:id,email']);

        return response()->json([
            'success' => true,
            'data'    => $sale,
            'message' => 'Transaksi berhasil',
        ], 201);
    }

    /**
     * GET /api/sales/{id}
     * Detail sale beserta semua SaleItems dan relasi product; return 404 jika tidak ditemukan.
     * Accessible by pengelola + kasir.
     */
    public function show(string $id): JsonResponse
    {
        $sale = Sale::with([
            'items.product',
            'recordedBy:id,email',
        ])->find($id);

        if (! $sale) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $sale,
        ]);
    }
}
