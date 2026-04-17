<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected auth routes (require valid JWT + active account)
Route::prefix('auth')->middleware(['auth:api', 'check_active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/me', [AuthController::class, 'me']);
});

// User management routes — Pengelola only
Route::middleware(['auth:api', 'check_active', 'role:pengelola'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}/deactivate', [UserController::class, 'deactivate']);
});

// Category routes
Route::middleware(['auth:api', 'check_active'])->group(function () {
    // GET accessible by all authenticated users (Pengelola & Kasir)
    Route::get('/categories', [CategoryController::class, 'index']);
});

Route::middleware(['auth:api', 'check_active', 'role:pengelola'])->group(function () {
    // Write operations — Pengelola only
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
});

// Product routes
Route::middleware(['auth:api', 'check_active'])->group(function () {
    // GET accessible by all authenticated users
    Route::get('/products', [ProductController::class, 'index']);
    // IMPORTANT: low-stock MUST be registered BEFORE {id} to avoid routing conflict
    Route::get('/products/low-stock', [ProductController::class, 'lowStock']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
});

Route::middleware(['auth:api', 'check_active', 'role:pengelola'])->group(function () {
    // Write operations — Pengelola only
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
});

// Transaction routes
Route::middleware(['auth:api', 'check_active'])->group(function () {
    // GET accessible by all authenticated users
    Route::get('/transactions', [TransactionController::class, 'index']);
    // Transaksi keluar — Pengelola & Kasir
    Route::post('/transactions/out', [TransactionController::class, 'storeOut']);
});

Route::middleware(['auth:api', 'check_active', 'role:pengelola'])->group(function () {
    // Transaksi masuk — Pengelola only
    Route::post('/transactions/in', [TransactionController::class, 'storeIn']);
});

// Report routes — Pengelola only
Route::middleware(['auth:api', 'check_active', 'role:pengelola'])->prefix('reports')->group(function () {
    Route::get('/stock-summary', [ReportController::class, 'stockSummary']);
    Route::get('/export', [ReportController::class, 'export']);
});

// Audit log routes — Pengelola only
Route::middleware(['auth:api', 'check_active', 'role:pengelola'])->group(function () {
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
});

// Notification routes — all authenticated users
Route::middleware(['auth:api', 'check_active'])->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::patch('/read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('/{id}/read', [NotificationController::class, 'markRead']);
});

Route::middleware(['auth:api', 'check_active', 'role:pengelola'])->prefix('notifications')->group(function () {
    Route::post('/send', [NotificationController::class, 'send'])->middleware('throttle:60,1');
});

// Device routes — all authenticated users
Route::middleware(['auth:api', 'check_active'])->prefix('devices')->group(function () {
    Route::post('/register', [DeviceController::class, 'register']);
    Route::delete('/unregister', [DeviceController::class, 'unregister']);
});
