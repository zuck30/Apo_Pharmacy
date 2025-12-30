{{-- resources/views/products/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Products')
@section('icon', 'bi-box-seam')

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">Products</li>
@endsection

@section('actions')
<a href="{{ route('products.create') }}" class="btn btn-primary">
    <i class="bi bi-plus-circle me-1"></i> Add Product
</a>
@endsection

@section('content')
<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary">Product List</h6>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search by code, name, barcode..." 
                       value="{{ $search }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="ACTIVE" {{ $status == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                    <option value="INACTIVE" {{ $status == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                    <option value="DISCONTINUED" {{ $status == 'DISCONTINUED' ? 'selected' : '' }}>Discontinued</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="group_id" class="form-select">
                    <option value="">All Categories</option>
                    @foreach($materialGroups as $group)
                        <option value="{{ $group->material_group_id }}" 
                                {{ $group_id == $group->material_group_id ? 'selected' : '' }}>
                            {{ $group->group_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
            </div>
        </form>

        <!-- Products Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Product Name</th>
                        <th>Generic Name</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td>
                            <strong>{{ $product->product_code }}</strong>
                            @if($product->barcode)
                            <br><small class="text-muted">{{ $product->barcode }}</small>
                            @endif
                        </td>
                        <td>{{ $product->product_name }}</td>
                        <td>{{ $product->generic_name ?? '-' }}</td>
                        <td>{{ $product->materialGroup->group_name ?? '-' }}</td>
                        <td>
                            @php
                                $stock = $product->getCurrentStock();
                                $minStock = $product->min_stock;
                                $stockClass = $stock <= $minStock ? 'text-danger fw-bold' : 'text-success';
                            @endphp
                            <span class="{{ $stockClass }}">
                                {{ number_format($stock) }}
                            </span>
                            @if($product->unit)
                                {{ $product->unit->unit_symbol ?? $product->unit->unit_name }}
                            @endif
                        </td>
                        <td>
                            @if($product->status == 'ACTIVE')
                                <span class="badge bg-success">Active</span>
                            @elseif($product->status == 'INACTIVE')
                                <span class="badge bg-secondary">Inactive</span>
                            @else
                                <span class="badge bg-danger">Discontinued</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('products.show', $product->product_id) }}" 
                                   class="btn btn-outline-primary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('products.edit', $product->product_id) }}" 
                                   class="btn btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger delete-product" 
                                        data-id="{{ $product->product_id }}" 
                                        data-name="{{ $product->product_name }}" 
                                        title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-box display-6 d-block mb-2"></i>
                                No products found
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted">
                Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} products
            </div>
            {{ $products->appends(request()->query())->links() }}
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="productName"></strong>?</p>
                <p class="text-danger"><small>This action cannot be undone!</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Delete product confirmation
        document.querySelectorAll('.delete-product').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.id;
                const productName = this.dataset.name;
                
                document.getElementById('productName').textContent = productName;
                document.getElementById('deleteForm').action = `/products/${productId}`;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            });
        });

        // Auto-focus search input
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.focus();
        }
    });
</script>
@endpush