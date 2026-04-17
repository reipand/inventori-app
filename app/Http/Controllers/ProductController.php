<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * GET /api/products
     * Daftar produk dengan search, filter kategori, dan pagination.
     * Accessible by all authenticated users.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category');

        // Filter by search (nama atau SKU)
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('sku', 'LIKE', "%{$search}%");
            });
        }

        // Filter by category_id
        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->orderBy('name')->paginate(15);

        // Tambahkan stock_status ke setiap produk
        $products->getCollection()->transform(function ($product) {
            $arr = $product->toArray();
            $arr['stock_status'] = $product->stock_status;
            return $arr;
        });

        return response()->json([
            'success' => true,
            'data'    => $products,
        ]);
    }

    /**
     * POST /api/products
     * Tambah produk baru (Pengelola only).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->only(['sku', 'name', 'category_id', 'unit', 'buy_price', 'sell_price', 'min_stock']);

        // Validasi field wajib
        $requiredFields = ['sku', 'name', 'category_id', 'unit', 'buy_price', 'sell_price'];
        $missingFields  = [];

        foreach ($requiredFields as $field) {
            if (empty($data[$field]) && $data[$field] !== '0' && $data[$field] !== 0) {
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

        // Validasi min_stock >= 0
        $minStock = $data['min_stock'] ?? 0;
        if ((int) $minStock < 0) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => 'Stok minimum tidak boleh kurang dari 0',
                    'fields'  => ['min_stock'],
                ],
            ], 400);
        }

        // Cek duplikat SKU
        if (Product::where('sku', $data['sku'])->exists()) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'CONFLICT',
                    'message' => 'Kode SKU sudah digunakan',
                ],
            ], 409);
        }

        $product = Product::create([
            'sku'       => $data['sku'],
            'name'      => $data['name'],
            'category_id' => $data['category_id'],
            'unit'      => $data['unit'],
            'buy_price' => $data['buy_price'],
            'sell_price' => $data['sell_price'],
            'min_stock' => (int) $minStock,
        ]);

        // Audit log
        AuditLog::create([
            'entity_type' => 'product',
            'entity_id'   => $product->id,
            'action'      => 'create',
            'changed_by'  => auth('api')->id(),
            'old_data'    => null,
            'new_data'    => $product->toArray(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $product->load('category'),
        ], 201);
    }

    /**
     * GET /api/products/{id}
     * Detail produk (semua authenticated).
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::with('category')->find($id);

        if (! $product) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'NOT_FOUND',
                    'message' => 'Produk tidak ditemukan.',
                ],
            ], 404);
        }

        $data = $product->toArray();
        $data['stock_status'] = $product->stock_status;

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * PUT /api/products/{id}
     * Update produk (Pengelola only).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'NOT_FOUND',
                    'message' => 'Produk tidak ditemukan.',
                ],
            ], 404);
        }

        $input = $request->all();

        // Validasi field wajib jika dikirim tidak boleh kosong
        $requiredFields = ['sku', 'name', 'category_id', 'unit', 'buy_price', 'sell_price'];
        $missingFields  = [];

        foreach ($requiredFields as $field) {
            if (array_key_exists($field, $input) && empty($input[$field]) && $input[$field] !== '0' && $input[$field] !== 0) {
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

        // Validasi min_stock >= 0
        if (array_key_exists('min_stock', $input) && (int) $input['min_stock'] < 0) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => 'Stok minimum tidak boleh kurang dari 0',
                    'fields'  => ['min_stock'],
                ],
            ], 400);
        }

        // Cek duplikat SKU (kecuali produk ini sendiri)
        if (isset($input['sku']) && Product::where('sku', $input['sku'])->where('id', '!=', $id)->exists()) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'CONFLICT',
                    'message' => 'Kode SKU sudah digunakan',
                ],
            ], 409);
        }

        $oldData = $product->toArray();

        $product->update(array_filter($input, fn($v) => $v !== null, ARRAY_FILTER_USE_BOTH));

        // Audit log
        AuditLog::create([
            'entity_type' => 'product',
            'entity_id'   => $product->id,
            'action'      => 'update',
            'changed_by'  => auth('api')->id(),
            'old_data'    => $oldData,
            'new_data'    => $product->fresh()->toArray(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $product->fresh()->load('category'),
        ]);
    }

    /**
     * DELETE /api/products/{id}
     * Hapus produk (Pengelola only).
     * Ditolak jika produk memiliki riwayat transaksi.
     */
    public function destroy(string $id): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'NOT_FOUND',
                    'message' => 'Produk tidak ditemukan.',
                ],
            ], 404);
        }

        // Business rule: tolak jika ada riwayat transaksi
        if ($product->transactions()->exists()) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'BUSINESS_RULE_VIOLATION',
                    'message' => 'Produk tidak dapat dihapus karena memiliki riwayat transaksi',
                ],
            ], 422);
        }

        $oldData = $product->toArray();

        $product->delete();

        // Audit log
        AuditLog::create([
            'entity_type' => 'product',
            'entity_id'   => $id,
            'action'      => 'delete',
            'changed_by'  => auth('api')->id(),
            'old_data'    => $oldData,
            'new_data'    => null,
        ]);

        return response()->json([
            'success' => true,
            'data'    => ['message' => 'Produk berhasil dihapus.'],
        ]);
    }

    /**
     * GET /api/products/low-stock
     * Produk dengan current_stock <= min_stock, diurutkan dari paling kritis.
     * Pengelola only.
     */
    public function lowStock(): JsonResponse
    {
        $products = Product::with('category')
            ->whereRaw('current_stock <= min_stock')
            ->orderByRaw('(current_stock - min_stock) ASC')
            ->get();

        $data = $products->map(function ($product) {
            $arr = $product->toArray();
            $arr['stock_status'] = $product->stock_status;
            return $arr;
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
