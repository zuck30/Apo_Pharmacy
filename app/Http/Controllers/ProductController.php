<?php
// app/Http/Controllers/ProductController.php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\MaterialGroup;
use App\Models\MaterialSubgroup;
use App\Models\MaterialForm;
use App\Models\Unit;
use App\Models\Manufacturer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $group_id = $request->get('group_id', '');
        
        $products = Product::with(['materialGroup', 'materialForm', 'unit', 'manufacturer'])
            ->when($search, function($query) use ($search) {
                return $query->where('product_code', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            })
            ->when($status, function($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when($group_id, function($query) use ($group_id) {
                return $query->where('material_group_id', $group_id);
            })
            ->orderBy('product_name')
            ->paginate(25);
            
        $materialGroups = MaterialGroup::where('status', 'ACTIVE')->get();
        
        return view('products.index', compact('products', 'materialGroups', 'search', 'status', 'group_id'));
    }
    
    public function create()
    {
        $materialGroups = MaterialGroup::where('status', 'ACTIVE')->get();
        $materialForms = MaterialForm::where('status', 'ACTIVE')->get();
        $units = Unit::where('status', 'ACTIVE')->get();
        $manufacturers = Manufacturer::where('status', 'ACTIVE')->get();
        
        return view('products.create', compact('materialGroups', 'materialForms', 'units', 'manufacturers'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_code' => 'required|unique:products,product_code|max:50',
            'product_name' => 'required|max:200',
            'generic_name' => 'nullable|max:200',
            'material_group_id' => 'nullable|exists:material_groups,material_group_id',
            'material_subgroup_id' => 'nullable|exists:material_subgroups,material_subgroup_id',
            'material_form_id' => 'nullable|exists:material_forms,material_form_id',
            'unit_id' => 'nullable|exists:units,unit_id',
            'quantity_per_unit' => 'nullable|numeric|min:0',
            'strength' => 'nullable|max:100',
            'dosage' => 'nullable|max:100',
            'indication' => 'nullable',
            'manufacturer_id' => 'nullable|exists:manufacturers,manufacturer_id',
            'barcode' => 'nullable|unique:products,barcode|max:100',
            'min_stock' => 'required|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'required|numeric|min:0',
            'storage_conditions' => 'nullable|max:255',
            'status' => 'required|in:ACTIVE,INACTIVE,DISCONTINUED',
        ]);
        
        try {
            DB::beginTransaction();
            
            $product = Product::create($validated);
            
            // Generate barcode if not provided
            if (!$product->barcode) {
                $product->barcode = 'P' . str_pad($product->product_id, 9, '0', STR_PAD_LEFT);
                $product->save();
            }
            
            DB::commit();
            
            return redirect()->route('products.index')
                ->with('success', 'Product created successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create product: ' . $e->getMessage());
        }
    }
    
    public function show(Product $product)
    {
        $product->load(['materialGroup', 'materialSubgroup', 'materialForm', 'unit', 'manufacturer', 'batches.store']);
        
        // Calculate total stock
        $total_stock = $product->batches->where('status', 'ACTIVE')->sum('current_quantity');
        $total_value = $product->batches->where('status', 'ACTIVE')
            ->sum(function($batch) {
                return $batch->current_quantity * $batch->unit_cost;
            });
        
        return view('products.show', compact('product', 'total_stock', 'total_value'));
    }
    
    public function edit(Product $product)
    {
        $materialGroups = MaterialGroup::where('status', 'ACTIVE')->get();
        $materialForms = MaterialForm::where('status', 'ACTIVE')->get();
        $units = Unit::where('status', 'ACTIVE')->get();
        $manufacturers = Manufacturer::where('status', 'ACTIVE')->get();
        
        // Get subgroups for selected group
        $subgroups = [];
        if ($product->material_group_id) {
            $subgroups = MaterialSubgroup::where('material_group_id', $product->material_group_id)
                ->where('status', 'ACTIVE')
                ->get();
        }
        
        return view('products.edit', compact('product', 'materialGroups', 'materialForms', 'units', 'manufacturers', 'subgroups'));
    }
    
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'product_name' => 'required|max:200',
            'generic_name' => 'nullable|max:200',
            'material_group_id' => 'nullable|exists:material_groups,material_group_id',
            'material_subgroup_id' => 'nullable|exists:material_subgroups,material_subgroup_id',
            'material_form_id' => 'nullable|exists:material_forms,material_form_id',
            'unit_id' => 'nullable|exists:units,unit_id',
            'quantity_per_unit' => 'nullable|numeric|min:0',
            'strength' => 'nullable|max:100',
            'dosage' => 'nullable|max:100',
            'indication' => 'nullable',
            'manufacturer_id' => 'nullable|exists:manufacturers,manufacturer_id',
            'barcode' => 'nullable|max:100|unique:products,barcode,' . $product->product_id . ',product_id',
            'min_stock' => 'required|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'required|numeric|min:0',
            'storage_conditions' => 'nullable|max:255',
            'status' => 'required|in:ACTIVE,INACTIVE,DISCONTINUED',
        ]);
        
        try {
            DB::beginTransaction();
            
            $product->update($validated);
            
            DB::commit();
            
            return redirect()->route('products.index')
                ->with('success', 'Product updated successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update product: ' . $e->getMessage());
        }
    }
    
    public function destroy(Product $product)
    {
        try {
            // Check if product has stock
            $hasStock = $product->batches()->where('current_quantity', '>', 0)->exists();
            
            if ($hasStock) {
                return redirect()->back()
                    ->with('error', 'Cannot delete product with existing stock. Please adjust stock first.');
            }
            
            $product->delete();
            
            return redirect()->route('products.index')
                ->with('success', 'Product deleted successfully!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete product: ' . $e->getMessage());
        }
    }
    
    public function getSubgroups($groupId)
    {
        $subgroups = MaterialSubgroup::where('material_group_id', $groupId)
            ->where('status', 'ACTIVE')
            ->get();
            
        return response()->json($subgroups);
    }
}