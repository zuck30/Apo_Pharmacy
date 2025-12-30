// resources/js/modules/products.js
class ProductsManager {
    constructor() {
        this.initBarcodeScanner();
        this.initQuickActions();
        this.initStockCalculations();
    }
    
    initBarcodeScanner() {
        const barcodeInput = document.getElementById('barcode-input');
        if (barcodeInput) {
            let lastScan = '';
            let scanTimer;
            
            barcodeInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    clearTimeout(scanTimer);
                    
                    if (barcodeInput.value !== lastScan) {
                        this.searchByBarcode(barcodeInput.value);
                        lastScan = barcodeInput.value;
                    }
                    
                    barcodeInput.value = '';
                    e.preventDefault();
                } else {
                    clearTimeout(scanTimer);
                    scanTimer = setTimeout(() => {
                        barcodeInput.value = '';
                    }, 1000);
                }
            });
        }
    }
    
    async searchByBarcode(barcode) {
        try {
            const response = await fetch(`/api/products/search?barcode=${encodeURIComponent(barcode)}`);
            const data = await response.json();
            
            if (data.success && data.data) {
                this.showProductQuickView(data.data);
            } else {
                this.showToast('Product not found', 'warning');
            }
        } catch (error) {
            console.error('Barcode search error:', error);
            this.showToast('Search failed', 'error');
        }
    }
    
    showProductQuickView(product) {
        Swal.fire({
            title: product.product_name,
            html: `
                <div class="text-start">
                    <p><strong>Code:</strong> ${product.product_code}</p>
                    <p><strong>Generic:</strong> ${product.generic_name || 'N/A'}</p>
                    <p><strong>Stock:</strong> ${product.current_stock} ${product.unit_symbol || ''}</p>
                    <p><strong>Price:</strong> TZS ${product.selling_price?.toFixed(2) || 'N/A'}</p>
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'View Details',
            cancelButtonText: 'Close'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `/products/${product.product_id}`;
            }
        });
    }
    
    initQuickActions() {
        // Quick edit
        document.querySelectorAll('.quick-edit').forEach(button => {
            button.addEventListener('click', async function() {
                const productId = this.dataset.id;
                await this.loadProductData(productId);
            });
        });
        
        // Quick stock check
        document.querySelectorAll('.check-stock').forEach(button => {
            button.addEventListener('click', async function() {
                const productId = this.dataset.id;
                const stock = await this.getProductStock(productId);
                
                Swal.fire({
                    title: 'Current Stock',
                    text: `Available: ${stock.quantity} units`,
                    icon: 'info'
                });
            });
        });
    }
    
    initStockCalculations() {
        // Auto-calculate line totals
        document.querySelectorAll('.stock-input').forEach(input => {
            input.addEventListener('input', this.calculateStockValue);
        });
    }
    
    calculateStockValue() {
        const row = this.closest('tr');
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const unitCost = parseFloat(row.querySelector('.cost-input').value) || 0;
        const sellingPrice = parseFloat(row.querySelector('.price-input').value) || 0;
        
        const totalCost = quantity * unitCost;
        const totalValue = quantity * sellingPrice;
        const profit = totalValue - totalCost;
        
        row.querySelector('.total-cost').textContent = totalCost.toFixed(2);
        row.querySelector('.total-value').textContent = totalValue.toFixed(2);
        row.querySelector('.profit').textContent = profit.toFixed(2);
    }
    
    async loadProductData(productId) {
        try {
            const response = await fetch(`/api/products/${productId}/quick-data`);
            const data = await response.json();
            
            if (data.success) {
                this.populateEditForm(data.data);
            }
        } catch (error) {
            console.error('Load product error:', error);
        }
    }
    
    populateEditForm(product) {
        // Populate form fields
        document.getElementById('product_name').value = product.product_name;
        document.getElementById('generic_name').value = product.generic_name || '';
        document.getElementById('barcode').value = product.barcode || '';
        // ... populate other fields
    }
    
    async getProductStock(productId) {
        try {
            const response = await fetch(`/api/products/${productId}/stock`);
            const data = await response.json();
            return data.data;
        } catch (error) {
            console.error('Get stock error:', error);
            return { quantity: 0 };
        }
    }
    
    showToast(message, type = 'success') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        
        Toast.fire({
            icon: type,
            title: message
        });
    }
}

// Initialize when on products pages
if (document.querySelector('.products-page')) {
    window.productsManager = new ProductsManager();
}