<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * GET /api/transactions
     * Riwayat transaksi dengan filter tanggal, jenis, produk, dan pagination.
     * Accessible by all authenticated users.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::with([
            'product:id,name,sku',
            'recordedBy:id,email',
        ]);

        // Filter start_date
        if ($startDate = $request->query('start_date')) {
            $query->whereDate('transaction_date', '>=', $startDate);
        }

        // Filter end_date
        if ($endDate = $request->query('end_date')) {
            $query->whereDate('transaction_date', '<=', $endDate);
        }

        // Filter type (masuk/keluar)
        if ($type = $request->query('type')) {
            if (! in_array($type, ['masuk', 'keluar'])) {
                return response()->json([
                    'success' => false,
                    'error'   => [
                        'code'    => 'VALIDATION_ERROR',
                        'message' => 'Jenis transaksi tidak valid. Gunakan "masuk" atau "keluar".',
                        'fields'  => ['type'],
                    ],
                ], 400);
            }
            $query->where('type', $type);
        }

        // Filter product_id
        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $transactions,
        ]);
    }

    /**
     * POST /api/transactions/in
     * Catat transaksi masuk (Pengelola only).
     * Menambah current_stock secara atomik menggunakan DB transaction.
     */
    public function storeIn(Request $request): JsonResponse
    {
        $input = $request->only([
            'product_id',
            'quantity',
            'transaction_date',
            'supplier_name',
            'price_per_unit',
        ]);

        // Validasi field wajib
        $requiredFields = ['product_id', 'quantity', 'transaction_date', 'supplier_name', 'price_per_unit'];
        $missingFields  = [];

        foreach ($requiredFields as $field) {
            if (! isset($input[$field]) || $input[$field] === '' || $input[$field] === null) {
                $missingFields[] = $field;
            }
        }

        if (! empty($missingFields)) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => 'Field wajib tidak boleh kosong.',
                    'fields'  => $missingFields,
                ],
            ], 400);
        }

        // Validasi quantity
        $quantityValidation = $this->validateQuantity($input['quantity']);
        if ($quantityValidation !== null) {
            return $quantityValidation;
        }

        $quantity = (int) $input['quantity'];

        // Cek produk ada
        $product = Product::find($input['product_id']);
        if (! $product) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'NOT_FOUND',
                    'message' => 'Produk tidak ditemukan.',
                ],
            ], 404);
        }

        $userId = auth('api')->id();

        // Eksekusi dalam DB transaction untuk atomisitas
        $result = DB::transaction(function () use ($product, $quantity, $input, $userId) {
            // Update stok secara atomik
            $product->increment('current_stock', $quantity);
            $product->refresh();

            // Simpan transaksi
            $transaction = Transaction::create([
                'product_id'       => $product->id,
                'type'             => 'masuk',
                'quantity'         => $quantity,
                'price_per_unit'   => $input['price_per_unit'],
                'supplier_name'    => $input['supplier_name'],
                'transaction_date' => $input['transaction_date'],
                'recorded_by'      => $userId,
            ]);

            // Audit log
            AuditLog::create([
                'entity_type' => 'transaction',
                'entity_id'   => $transaction->id,
                'action'      => 'create',
                'changed_by'  => $userId,
                'old_data'    => null,
                'new_data'    => $transaction->toArray(),
            ]);

            return [
                'transaction'   => $transaction->load(['product:id,name,sku', 'recordedBy:id,email']),
                'current_stock' => $product->current_stock,
                'min_stock'     => $product->min_stock,
            ];
        });

        // low_stock_warning: true jika stok baru <= min_stock
        $lowStockWarning = $result['current_stock'] <= $result['min_stock'];

        return response()->json([
            'success' => true,
            'data'    => array_merge($result['transaction']->toArray(), [
                'current_stock'    => $result['current_stock'],
                'low_stock_warning' => $lowStockWarning,
            ]),
        ], 201);
    }

    /**
     * POST /api/transactions/out
     * Catat transaksi keluar (Pengelola & Kasir).
     * Mengurangi current_stock secara atomik menggunakan DB transaction.
     */
    public function storeOut(Request $request): JsonResponse
    {
        $input = $request->only([
            'product_id',
            'quantity',
            'transaction_date',
            'price_per_unit',
        ]);

        // Validasi field wajib
        $requiredFields = ['product_id', 'quantity', 'transaction_date', 'price_per_unit'];
        $missingFields  = [];

        foreach ($requiredFields as $field) {
            if (! isset($input[$field]) || $input[$field] === '' || $input[$field] === null) {
                $missingFields[] = $field;
            }
        }

        if (! empty($missingFields)) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => 'Field wajib tidak boleh kosong.',
                    'fields'  => $missingFields,
                ],
            ], 400);
        }

        // Validasi quantity
        $quantityValidation = $this->validateQuantity($input['quantity']);
        if ($quantityValidation !== null) {
            return $quantityValidation;
        }

        $quantity = (int) $input['quantity'];

        // Cek produk ada
        $product = Product::find($input['product_id']);
        if (! $product) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'NOT_FOUND',
                    'message' => 'Produk tidak ditemukan.',
                ],
            ], 404);
        }

        $userId = auth('api')->id();

        // Eksekusi dalam DB transaction untuk atomisitas
        try {
            $result = DB::transaction(function () use ($product, $quantity, $input, $userId) {
                // Lock row untuk mencegah race condition
                $product = Product::lockForUpdate()->find($product->id);

                // Validasi stok cukup
                if ($quantity > $product->current_stock) {
                    // Lempar exception agar DB transaction di-rollback
                    throw new \DomainException('Jumlah melebihi stok yang tersedia');
                }

                // Update stok secara atomik
                $product->decrement('current_stock', $quantity);
                $product->refresh();

                // Simpan transaksi
                $transaction = Transaction::create([
                    'product_id'       => $product->id,
                    'type'             => 'keluar',
                    'quantity'         => $quantity,
                    'price_per_unit'   => $input['price_per_unit'],
                    'supplier_name'    => null,
                    'transaction_date' => $input['transaction_date'],
                    'recorded_by'      => $userId,
                ]);

                // Audit log
                AuditLog::create([
                    'entity_type' => 'transaction',
                    'entity_id'   => $transaction->id,
                    'action'      => 'create',
                    'changed_by'  => $userId,
                    'old_data'    => null,
                    'new_data'    => $transaction->toArray(),
                ]);

                return [
                    'transaction'   => $transaction->load(['product:id,name,sku', 'recordedBy:id,email']),
                    'current_stock' => $product->current_stock,
                    'min_stock'     => $product->min_stock,
                ];
            });
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'BUSINESS_RULE_VIOLATION',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }

        // low_stock_warning: true jika stok baru <= min_stock
        $lowStockWarning = $result['current_stock'] <= $result['min_stock'];

        return response()->json([
            'success' => true,
            'data'    => array_merge($result['transaction']->toArray(), [
                'current_stock'    => $result['current_stock'],
                'low_stock_warning' => $lowStockWarning,
            ]),
        ], 201);
    }

    /**
     * Validasi nilai quantity: harus bilangan bulat positif.
     * Mengembalikan JsonResponse jika tidak valid, null jika valid.
     */
    private function validateQuantity(mixed $value): ?JsonResponse
    {
        // Cek apakah bukan integer (desimal)
        if (is_string($value) && str_contains($value, '.')) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => 'Jumlah harus berupa bilangan bulat',
                    'fields'  => ['quantity'],
                ],
            ], 400);
        }

        if (is_float($value) && $value != (int) $value) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => 'Jumlah harus berupa bilangan bulat',
                    'fields'  => ['quantity'],
                ],
            ], 400);
        }

        $intValue = (int) $value;

        if ($intValue <= 0) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => 'Jumlah harus lebih dari 0',
                    'fields'  => ['quantity'],
                ],
            ], 400);
        }

        return null;
    }
}
