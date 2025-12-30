<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SaleTransaction;
use App\Models\StockBatch;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Today's date
        $today = Carbon::today();
        
        // Initialize stats with default values
        $stats = [
            'total_products' => 0,
            'low_stock' => 0,
            'expiring_soon' => 0,
            'today_sales' => 0,
            'today_transactions' => 0,
            'total_customers' => 0,
        ];
        
        try {
            // Check if tables exist
            if (\Schema::hasTable('products')) {
                $stats['total_products'] = Product::count();
                
                // Low stock calculation (simplified for now)
                $stats['low_stock'] = Product::where('min_stock', '>', 0)
                    ->where(function($query) {
                        $query->whereHas('batches', function($q) {
                            $q->where('status', 'ACTIVE')
                              ->select('product_id')
                              ->groupBy('product_id')
                              ->havingRaw('SUM(current_quantity) <= 10'); // Simplified
                        })
                        ->orWhereDoesntHave('batches');
                    })
                    ->count();
            }
            
            if (\Schema::hasTable('stock_batches')) {
                $stats['expiring_soon'] = StockBatch::where('expiry_date', '<=', $today->copy()->addDays(30))
                    ->where('expiry_date', '>', $today)
                    ->where('status', 'ACTIVE')
                    ->count();
            }
            
            if (\Schema::hasTable('sale_transactions')) {
                $stats['today_sales'] = SaleTransaction::whereDate('transaction_date', $today)
                    ->where('transaction_type', 'SALE')
                    ->sum('total_amount') ?? 0;
                    
                $stats['today_transactions'] = SaleTransaction::whereDate('transaction_date', $today)->count();
            }
            
            if (\Schema::hasTable('persons')) {
                $stats['total_customers'] = DB::table('persons')
                    ->where('person_type', 'CUSTOMER')
                    ->where('status', 'ACTIVE')
                    ->count();
            }
            
        } catch (\Exception $e) {
            // Tables might not exist yet, that's okay
        }
        
        // Get recent sales if table exists
        $recent_sales = collect();
        $low_stock_items = collect();
        $expiring_soon = collect();
        
        if (\Schema::hasTable('sale_transactions')) {
            $recent_sales = SaleTransaction::with(['person', 'store'])
                ->where('transaction_type', 'SALE')
                ->orderBy('transaction_date', 'desc')
                ->limit(10)
                ->get();
        }
        
        if (\Schema::hasTable('products')) {
            $low_stock_items = Product::with(['batches' => function($query) {
                    $query->where('status', 'ACTIVE');
                }])
                ->where('min_stock', '>', 0)
                ->limit(5)
                ->get();
        }
        
        if (\Schema::hasTable('stock_batches')) {
            $expiring_soon = StockBatch::with(['product', 'store'])
                ->where('expiry_date', '<=', $today->copy()->addDays(30))
                ->where('expiry_date', '>', $today)
                ->where('status', 'ACTIVE')
                ->orderBy('expiry_date')
                ->limit(5)
                ->get();
        }
        
        return view('dashboard.index', compact('stats', 'recent_sales', 'low_stock_items', 'expiring_soon'));
    }
}