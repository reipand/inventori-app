<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * GET /api/audit-logs
     * Riwayat perubahan data dengan filter jenis aksi, pengguna, dan rentang tanggal.
     * Hanya Pengelola yang dapat mengakses.
     *
     * Query params:
     *   - action       : create|update|delete
     *   - user_id      : UUID pengguna
     *   - start_date   : YYYY-MM-DD
     *   - end_date     : YYYY-MM-DD
     *   - per_page     : integer (default 15)
     *
     * Requirements: 8.3, 8.4
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('changedBy:id,name,email');

        // Filter berdasarkan jenis aksi (create/update/delete)
        if ($action = $request->query('action')) {
            if (in_array($action, ['create', 'update', 'delete'])) {
                $query->where('action', $action);
            }
        }

        // Filter berdasarkan nama pengguna
        if ($userName = $request->query('user_name')) {
            $query->whereHas('changedBy', function ($q) use ($userName) {
                $q->where('name', 'like', '%' . $userName . '%');
            });
        }

        // Filter berdasarkan rentang tanggal
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min(100, $perPage)); // clamp antara 1–100

        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Label ramah untuk jenis entitas
        $entityLabels = [
            'product'     => 'Produk',
            'category'    => 'Kategori',
            'transaction' => 'Transaksi',
            'user'        => 'Pengguna',
        ];

        // Label ramah untuk field data
        $fieldLabels = [
            'name'          => 'Nama',
            'sku'           => 'SKU',
            'sell_price'    => 'Harga Jual',
            'buy_price'     => 'Harga Beli',
            'current_stock' => 'Stok Saat Ini',
            'min_stock'     => 'Stok Minimum',
            'unit'          => 'Satuan',
            'category_id'   => 'Kategori',
            'description'   => 'Deskripsi',
            'quantity'      => 'Jumlah',
            'type'          => 'Jenis',
            'note'          => 'Catatan',
            'email'         => 'Email',
            'role'          => 'Peran',
            'is_active'     => 'Status Aktif',
        ];

        $humanizeData = function (?array $data) use ($fieldLabels): ?array {
            if (!$data) return null;
            $result = [];
            foreach ($data as $key => $value) {
                $label = $fieldLabels[$key] ?? $key;
                if (is_bool($value)) $value = $value ? 'Ya' : 'Tidak';
                $result[$label] = $value;
            }
            return $result;
        };

        return response()->json([
            'success' => true,
            'data'    => $logs->through(function (AuditLog $log) use ($entityLabels, $humanizeData) {
                return [
                    'id'            => $log->id,
                    'entity_type'   => $log->entity_type,
                    'entity_label'  => $entityLabels[$log->entity_type] ?? ucfirst($log->entity_type),
                    'entity_id'     => $log->entity_id,
                    'action'        => $log->action,
                    'user'          => $log->changedBy ? [
                        'id'    => $log->changedBy->id,
                        'name'  => $log->changedBy->name,
                        'email' => $log->changedBy->email,
                    ] : null,
                    'old_data'      => $humanizeData($log->old_data),
                    'new_data'      => $humanizeData($log->new_data),
                    'created_at'    => $log->created_at?->toIso8601String(),
                ];
            }),
        ]);
    }
}
