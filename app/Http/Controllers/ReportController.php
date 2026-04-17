<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * GET /api/reports/stock-summary
     * Laporan ringkasan stok: nama produk, SKU, stok saat ini, stok minimum,
     * status stok, dan nilai total stok (current_stock × buy_price).
     * Hanya Pengelola yang dapat mengakses.
     */
    public function stockSummary(): JsonResponse
    {
        $products = Product::with('category:id,name')->get();

        $items = $products->map(function (Product $product) {
            return [
                'id'            => $product->id,
                'name'          => $product->name,
                'sku'           => $product->sku,
                'category'      => $product->category?->name,
                'unit'          => $product->unit,
                'current_stock' => $product->current_stock,
                'min_stock'     => $product->min_stock,
                'buy_price'     => $product->buy_price,
                'stock_status'  => $product->stock_status,
                'total_value'   => (float) $product->buy_price * $product->current_stock,
            ];
        });

        $totalStockValue = $items->sum('total_value');

        return response()->json([
            'success' => true,
            'data'    => [
                'items'             => $items->values(),
                'total_stock_value' => $totalStockValue,
            ],
        ]);
    }

    /**
     * GET /api/reports/export?type=stock|transactions
     * Ekspor CSV stok atau riwayat transaksi.
     * Query params: type, start_date, end_date, transaction_type (masuk/keluar), product_id
     * Hanya Pengelola yang dapat mengakses.
     */
    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $type      = $request->query('type', 'stock');
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');

        // Validasi rentang tanggal jika keduanya diberikan
        if ($startDate && $endDate && $startDate > $endDate) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => 'Tanggal awal tidak boleh lebih besar dari tanggal akhir',
                    'fields'  => ['start_date', 'end_date'],
                ],
            ], 422);
        }

        $exportDate = now()->format('Y-m-d');

        if ($type === 'stock') {
            return $this->exportStock($exportDate);
        }

        if ($type === 'transactions') {
            return $this->exportTransactions($request, $exportDate, $startDate, $endDate);
        }

        return response()->json([
            'success' => false,
            'error'   => [
                'code'    => 'VALIDATION_ERROR',
                'message' => 'Tipe ekspor tidak valid. Gunakan "stock" atau "transactions".',
                'fields'  => ['type'],
            ],
        ], 400);
    }

    /**
     * Ekspor CSV ringkasan stok semua produk.
     */
    private function exportStock(string $exportDate): StreamedResponse
    {
        $filename = "laporan-stok-{$exportDate}.csv";
        $products = Product::with('category:id,name')->get();

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');

            // BOM untuk Excel agar UTF-8 terbaca dengan benar
            fwrite($handle, "\xEF\xBB\xBF");

            // Header CSV
            fputcsv($handle, [
                'Nama Produk',
                'Kode SKU',
                'Kategori',
                'Satuan',
                'Stok Saat Ini',
                'Stok Minimum',
                'Status Stok',
                'Harga Beli',
                'Nilai Total Stok',
            ]);

            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->name,
                    $product->sku,
                    $product->category?->name ?? '-',
                    $product->unit,
                    $product->current_stock,
                    $product->min_stock,
                    $product->stock_status,
                    $product->buy_price,
                    (float) $product->buy_price * $product->current_stock,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Ekspor CSV riwayat transaksi dengan filter opsional.
     */
    private function exportTransactions(
        Request $request,
        string $exportDate,
        ?string $startDate,
        ?string $endDate
    ): StreamedResponse {
        $filename = "laporan-transaksi-{$exportDate}.csv";

        $query = Transaction::with([
            'product:id,name,sku',
            'recordedBy:id,email',
        ]);

        if ($startDate) {
            $query->whereDate('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('transaction_date', '<=', $endDate);
        }

        // Filter jenis transaksi (masuk/keluar)
        if ($transactionType = $request->query('transaction_type')) {
            if (in_array($transactionType, ['masuk', 'keluar'])) {
                $query->where('type', $transactionType);
            }
        }

        // Filter produk
        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');

            // BOM untuk Excel agar UTF-8 terbaca dengan benar
            fwrite($handle, "\xEF\xBB\xBF");

            // Header CSV
            fputcsv($handle, [
                'Tanggal',
                'Jenis',
                'Nama Produk',
                'Kode SKU',
                'Jumlah',
                'Harga Per Unit',
                'Nama Supplier',
                'Dicatat Oleh',
            ]);

            foreach ($transactions as $transaction) {
                fputcsv($handle, [
                    $transaction->transaction_date->format('Y-m-d'),
                    $transaction->type,
                    $transaction->product?->name ?? '-',
                    $transaction->product?->sku ?? '-',
                    $transaction->quantity,
                    $transaction->price_per_unit,
                    $transaction->supplier_name ?? '-',
                    $transaction->recordedBy?->name ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
