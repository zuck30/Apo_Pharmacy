<?php
// app/Http/Controllers/Api/ProductApiController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductApiController extends Controller
{
    public function search(Request $request)
    {
        $search = $request->get('search', '');
        $barcode = $request->get('barcode', '');
        
        $query = Product::with(['unit', 'batches' => function($q) {
            $q->where('status', 'ACTIVE')
              ->where('current_quantity', '>', 0)
              ->orderBy('expiry_date')
              ->limit(5);
        }]);
        
        if ($barcode) {
            $query->where('barcode', $barcode);
        } elseif ($search) {
            $query->where(function($q) use ($search) {
                $q->where('product_code', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%")
                  ->orWhere('generic_name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No search criteria provided'
            ], 400);
        }
        
        $product = $query->first();
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
        
        // Calculate current stock
        $currentStock = $product->batches->sum('current_quantity');
        
        // Get lowest selling price from active batches
        $lowestPrice = $product->batches->min('selling_price');
        
        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->product_id,
                'product_code' => $product->product_code,
                'product_name' => $product->product_name,
                'generic_name' => $product->generic_name,
                'current_stock' => $currentStock,
                'selling_price' => $lowestPrice,
                'unit_symbol' => $product->unit->unit_symbol ?? '',
                'batches' => $product->batches->map(function($batch) {
                    return [
                        'batch_number' => $batch->batch_number,
                        'expiry_date' => $batch->expiry_date->format('Y-m-d'),
                        'current_quantity' => $batch->current_quantity,
                        'selling_price' => $batch->selling_price,
                    ];
                }),
            ]
        ]);
    }
    
    public function stock($id)
    {
        $product = Product::findOrFail($id);
        
        $totalStock = $product->batches()
            ->where('status', 'ACTIVE')
            ->sum('current_quantity');
            
        $totalValue = $product->batches()
            ->where('status', 'ACTIVE')
            ->sum(DB::raw('current_quantity * unit_cost'));
            
        return response()->json([
            'success' => true,
            'data' => [
                'quantity' => $totalStock,
                'value' => $totalValue,
            ]
        ]);
    }
    
    public function lowStockAlerts()
    {
        $lowStockProducts = Product::where('min_stock', '>', 0)
            ->whereHas('batches', function($query) {
                $query->where('status', 'ACTIVE')
                      ->select('product_id')
                      ->groupBy('product_id')
                      ->havingRaw('SUM(current_quantity) <= (SELECT min_stock FROM products WHERE products.product_id = stock_batches.product_id)');
            })
            ->with(['unit'])
            ->limit(10)
            ->get()
            ->map(function($product) {
                $currentStock = $product->batches->where('status', 'ACTIVE')->sum('current_quantity');
                return [
                    'product_id' => $product->product_id,
                    'product_name' => $product->product_name,
                    'current_quantity' => $currentStock,
                    'min_stock' => $product->min_stock,
                    'unit' => $product->unit->unit_name ?? '',
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $lowStockProducts
        ]);
    }
    
    public function expiryAlerts()
    {
        $thirtyDaysFromNow = Carbon::now()->addDays(30);
        
        $expiringBatches = StockBatch::with(['product', 'store'])
            ->where('status', 'ACTIVE')
            ->where('expiry_date', '<=', $thirtyDaysFromNow)
            ->where('expiry_date', '>', Carbon::now())
            ->orderBy('expiry_date')
            ->limit(10)
            ->get()
            ->map(function($batch) {
                $daysLeft = Carbon::now()->diffInDays($batch->expiry_date, false);
                return [
                    'batch_id' => $batch->batch_id,
                    'product_name' => $batch->product->product_name,
                    'batch_number' => $batch->batch_number,
                    'expiry_date' => $batch->expiry_date->format('Y-m-d'),
                    'days_left' => $daysLeft,
                    'quantity' => $batch->current_quantity,
                    'store_name' => $batch->store->store_name,
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $expiringBatches
        ]);
    }
}