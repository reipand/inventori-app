<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * GET /api/categories
     * Return all categories (accessible by all authenticated users).
     */
    public function index(): JsonResponse
    {
        $categories = Category::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * POST /api/categories
     * Create a new category (Pengelola only).
     */
    public function store(Request $request): JsonResponse
    {
        $name        = $request->input('name');
        $description = $request->input('description');

        // Validate required fields
        if (empty($name)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => 'Nama kategori wajib diisi.',
                    'fields'  => ['name'],
                ],
            ], 400);
        }

        // Check for duplicate name
        if (Category::where('name', $name)->exists()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code'    => 'CONFLICT',
                    'message' => 'Nama kategori sudah digunakan',
                ],
            ], 409);
        }

        $category = Category::create([
            'name'        => $name,
            'description' => $description,
        ]);

        // Audit log
        AuditLog::create([
            'entity_type' => 'category',
            'entity_id'   => $category->id,
            'action'      => 'create',
            'changed_by'  => auth('api')->id(),
            'old_data'    => null,
            'new_data'    => $category->toArray(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $category,
        ], 201);
    }

    /**
     * PUT /api/categories/:id
     * Update an existing category (Pengelola only).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $category = Category::find($id);

        if (! $category) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code'    => 'NOT_FOUND',
                    'message' => 'Kategori tidak ditemukan.',
                ],
            ], 404);
        }

        $name        = $request->input('name');
        $description = $request->input('description', $category->description);

        // Validate required fields
        if (isset($request->all()['name']) && empty($name)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => 'Nama kategori tidak boleh kosong.',
                    'fields'  => ['name'],
                ],
            ], 400);
        }

        // Check for duplicate name (exclude current category)
        if ($name && Category::where('name', $name)->where('id', '!=', $id)->exists()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code'    => 'CONFLICT',
                    'message' => 'Nama kategori sudah digunakan',
                ],
            ], 409);
        }

        $oldData = $category->toArray();

        $category->update([
            'name'        => $name ?? $category->name,
            'description' => $description,
        ]);

        // Audit log
        AuditLog::create([
            'entity_type' => 'category',
            'entity_id'   => $category->id,
            'action'      => 'update',
            'changed_by'  => auth('api')->id(),
            'old_data'    => $oldData,
            'new_data'    => $category->fresh()->toArray(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $category->fresh(),
        ]);
    }

    /**
     * DELETE /api/categories/:id
     * Delete a category (Pengelola only).
     * Rejected if the category still has associated products.
     */
    public function destroy(string $id): JsonResponse
    {
        $category = Category::find($id);

        if (! $category) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code'    => 'NOT_FOUND',
                    'message' => 'Kategori tidak ditemukan.',
                ],
            ], 404);
        }

        // Business rule: cannot delete category that still has products
        if ($category->products()->exists()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code'    => 'BUSINESS_RULE_VIOLATION',
                    'message' => 'Kategori tidak dapat dihapus karena masih memiliki produk',
                ],
            ], 422);
        }

        $oldData = $category->toArray();

        $category->delete();

        // Audit log
        AuditLog::create([
            'entity_type' => 'category',
            'entity_id'   => $id,
            'action'      => 'delete',
            'changed_by'  => auth('api')->id(),
            'old_data'    => $oldData,
            'new_data'    => null,
        ]);

        return response()->json([
            'success' => true,
            'data'    => ['message' => 'Kategori berhasil dihapus.'],
        ]);
    }
}
