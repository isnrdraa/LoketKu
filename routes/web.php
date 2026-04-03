<?php

use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// Halaman utama redirect ke login
Route::redirect('/', '/login');

// Semua user yang sudah login (admin & cashier)
Route::middleware(['auth', 'role'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Transaksi
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');

    // API untuk form POS
    Route::get('api/services', [ServiceController::class, 'forPos'])->name('api.services');
});

// Hanya admin
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Manajemen User
    Route::resource('users', UserController::class)->except(['show']);

    // Kategori Layanan
    Route::resource('service-categories', ServiceCategoryController::class)->except(['show']);

    // Layanan
    Route::resource('services', AdminServiceController::class)->except(['show']);

    // Pengeluaran
    Route::resource('expenses', ExpenseController::class)->except(['show']);

    // Laporan
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    // Pengaturan Toko
    Route::get('store-settings', [SettingController::class, 'edit'])->name('store-settings.edit');
    Route::put('store-settings', [SettingController::class, 'update'])->name('store-settings.update');
});

require __DIR__.'/settings.php';
