<?php
// routes/web.php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Authentication routes - we'll create these manually first
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Products
    Route::resource('products', ProductController::class);
    Route::get('/products/low-stock', [ProductController::class, 'lowStock'])->name('products.low-stock');
    Route::get('/products/expiring', [ProductController::class, 'expiring'])->name('products.expiring');
    Route::get('/api/subgroups/{groupId}', [ProductController::class, 'getSubgroups']);
    
    // Sales
    Route::prefix('sales')->group(function () {
        Route::get('/', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('/', [SaleController::class, 'store'])->name('sales.store');
        Route::get('/{id}', [SaleController::class, 'show'])->name('sales.show');
        Route::get('/{id}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
        Route::get('/returns', [SaleController::class, 'returns'])->name('sales.returns');
    });
    
    // Customers
    Route::resource('customers', CustomerController::class);
    
    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
        Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
    });
    
    // Logout
    Route::post('/logout', function () {
        auth()->logout();
        return redirect('/login');
    })->name('logout');
});

// For now, let's create a simple login page that just logs in as admin
Route::post('/login', function () {
    // Simple login for development - we'll use the admin user we'll create
    return redirect('/dashboard');
});