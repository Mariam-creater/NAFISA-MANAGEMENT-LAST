<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BackupController;

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index']);

// Tenants Lifecycle & Records
Route::prefix('tenants')->name('tenants.')->group(function () {
    Route::get('/', [TenantController::class, 'index'])->name('index');
    Route::post('/', [TenantController::class, 'store'])->name('store');
    Route::put('/{tenant}', [TenantController::class, 'update'])->name('update');
    Route::delete('/{tenant}', [TenantController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/restore', [TenantController::class, 'restore'])->name('restore');
    Route::delete('/{id}/force', [TenantController::class, 'forceDelete'])->name('forceDelete');
    Route::post('/{tenant}/remind', [TenantController::class, 'sendReminder'])->name('remind');
    Route::get('/{id}/dossier', [TenantController::class, 'dossier'])->name('dossier');
});

// Dynamic Room Inventory
Route::prefix('rooms')->name('rooms.')->group(function () {
    Route::get('/', [RoomController::class, 'index'])->name('index');
    Route::put('/{room}', [RoomController::class, 'update'])->name('update');
});

// Billing & Receipts
Route::prefix('payments')->name('payments.')->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::post('/', [PaymentController::class, 'store'])->name('store');
    Route::get('/{payment}/receipt', [PaymentController::class, 'downloadReceipt'])->name('receipt');
    Route::post('/{payment}/resend', [PaymentController::class, 'resend'])->name('resend');
});

// Financial Reports & One-Click Exports
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/export/excel', [ReportController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
});

// Settings & Local Runtime Backup
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [BackupController::class, 'index'])->name('index');
    Route::post('/backup/trigger', [BackupController::class, 'trigger'])->name('backup.trigger');
});

Route::get('/backups/download/{filename}', [BackupController::class, 'download'])->name('backups.download');
