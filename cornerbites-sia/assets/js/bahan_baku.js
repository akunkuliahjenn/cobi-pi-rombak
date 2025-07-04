// bahan_baku.js
// JavaScript functions for bahan baku management with AJAX search

const unitOptions = ['kg', 'gram', 'liter', 'ml', 'pcs', 'buah', 'roll', 'meter', 'box', 'botol', 'lembar'];
const typeOptions = ['bahan', 'kemasan'];

// Currency formatting for price input
document.addEventListener('DOMContentLoaded', function() {
    const priceInput = document.getElementById('purchase_price_per_unit');

    if (priceInput) {
        // Format input saat user mengetik
        priceInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d]/g, '');
            if (value) {
                e.target.value = formatNumber(value);
            }
        });

        // Convert ke number saat submit
        const form = priceInput.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Convert formatted price back to number
                const currentValue = priceInput.value.replace(/[^\d]/g, '');
                priceInput.value = currentValue;
                
                // Let the form submit normally
                return true;
            });
        }
    }

    // Dynamic label and button update based on type selection
    const typeSelect = document.getElementById('type');
    const purchaseSizeLabel = document.getElementById('purchase_size_label');
    const currentStockLabel = document.getElementById('current_stock_label');
    const purchasePriceLabel = document.getElementById('purchase_price_label');
    const purchaseSizeHelp = document.getElementById('purchase_size_help');
    const purchasePriceHelp = document.getElementById('purchase_price_help');
    const currentStockHelp = document.getElementById('current_stock_help');
    const submitButton = document.getElementById('submit_button');

    function updateLabelsBasedOnType(type) {
        if (type === 'bahan') {
            purchaseSizeLabel.textContent = 'Ukuran Beli Kemasan Bahan';
            currentStockLabel.textContent = 'Stok Bahan Tersedia';
            purchasePriceLabel.textContent = 'Harga Beli Per Kemasan Bahan';
            purchaseSizeHelp.textContent = 'Isi per kemasan bahan yang Anda beli (sesuai satuan yang tertera di plastik kemasan yang anda beli)';
            purchasePriceHelp.textContent = 'Harga per kemasan bahan saat pembelian';
            currentStockHelp.textContent = 'Berapa berat bahan yang saat ini tersedia di stok';
            submitButton.innerHTML = `
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Tambah Bahan
            `;
        } else {
            purchaseSizeLabel.textContent = 'Ukuran Beli Kemasan';
            currentStockLabel.textContent = 'Stok Kemasan Tersedia';
            purchasePriceLabel.textContent = 'Harga Beli Per Kemasan';
            purchaseSizeHelp.textContent = 'Isi per kemasan yang Anda beli (sesuai satuan yang tertera di kemasan yang anda beli)';
            purchasePriceHelp.textContent = 'Harga per kemasan saat pembelian';
            currentStockHelp.textContent = 'Berapa stok kemasan yang saat ini tersedia';
            submitButton.innerHTML = `
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Tambah Kemasan
            `;
        }
    }

    if (typeSelect && purchaseSizeLabel && currentStockLabel && submitButton) {
        typeSelect.addEventListener('change', function() {
            updateLabelsBasedOnType(this.value);
        });

        // Set initial labels
        updateLabelsBasedOnType(typeSelect.value);
    }

    // Cancel edit button event
    const cancelButton = document.getElementById('cancel_edit_button');
    if (cancelButton) {
        cancelButton.addEventListener('click', resetForm);
    }

    // AJAX search implementation
    setupAjaxSearch();

    // Tambahkan event listener untuk menyimpan posisi scroll saat user berinteraksi
    const searchInputs = document.querySelectorAll('#search_raw, #search_kemasan');
    const limitSelects = document.querySelectorAll('#bahan_limit, #kemasan_limit');

    searchInputs.forEach(input => {
        input.addEventListener('focus', saveScrollPosition);
        input.addEventListener('input', saveScrollPosition);
    });

    limitSelects.forEach(select => {
        select.addEventListener('change', saveScrollPosition);
    });
});

function formatNumber(num) {
    return parseInt(num).toLocaleString('id-ID');
}

function editBahanBaku(material) {
    // Scroll to form first
    const formTitle = document.getElementById('form-title');
    if (formTitle) {
        formTitle.scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
    }

    // Fill form data
    document.getElementById('bahan_baku_id').value = material.id;
    document.getElementById('name').value = material.name;
    document.getElementById('brand').value = material.brand || '';
    document.getElementById('type').value = material.type;
    document.getElementById('unit').value = material.unit;

    // Format numbers without .00 for display
    const purchaseSize = parseFloat(material.default_package_quantity);
    document.getElementById('purchase_size').value = purchaseSize % 1 === 0 ? purchaseSize.toFixed(0) : purchaseSize.toString();

    // Calculate stok terakhir (current_stock - total_used)
    const currentStock = parseFloat(material.current_stock);
    const totalUsed = parseFloat(material.total_used) || 0;
    const stokTerakhir = currentStock - totalUsed;
    document.getElementById('current_stock').value = stokTerakhir % 1 === 0 ? stokTerakhir.toFixed(0) : stokTerakhir.toString();

    document.getElementById('purchase_price_per_unit').value = formatNumber(material.purchase_price_per_unit);

    // Update labels and button based on type
    const purchaseSizeLabel = document.getElementById('purchase_size_label');
    const currentStockLabel = document.getElementById('current_stock_label');
    const purchasePriceLabel = document.getElementById('purchase_price_label');
    const purchaseSizeHelp = document.getElementById('purchase_size_help');
    const purchasePriceHelp = document.getElementById('purchase_price_help');
    const currentStockHelp = document.getElementById('current_stock_help');
    const submitButton = document.getElementById('submit_button');
    const cancelButton = document.getElementById('cancel_edit_button');

    if (material.type === 'bahan') {
        purchaseSizeLabel.textContent = 'Ukuran Beli Kemasan Bahan';
        currentStockLabel.textContent = 'Stok Terakhir';
        purchasePriceLabel.textContent = 'Harga Beli Per Kemasan Bahan';
        purchaseSizeHelp.textContent = 'Isi per kemasan bahan yang Anda beli (sesuai satuan yang tertera di plastik kemasan yang anda beli)';
        purchasePriceHelp.textContent = 'Harga per kemasan bahan saat pembelian';
        currentStockHelp.textContent = 'Update stok terakhir yang tersedia (setelah dikurangi penggunaan dalam resep)';
        document.getElementById('form-title').textContent = 'Edit Bahan';
        submitButton.innerHTML = `
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Update Bahan
        `;
    } else {
        purchaseSizeLabel.textContent = 'Ukuran Beli Kemasan';
        currentStockLabel.textContent = 'Stok Terakhir';
        purchasePriceLabel.textContent = 'Harga Beli Per Kemasan';
        purchaseSizeHelp.textContent = 'Isi per kemasan yang Anda beli (sesuai satuan yang tertera di kemasan yang anda beli)';
        purchasePriceHelp.textContent = 'Harga per kemasan saat pembelian';
        currentStockHelp.textContent = 'Update stok terakhir yang tersedia (setelah dikurangi penggunaan dalam resep)';
        document.getElementById('form-title').textContent = 'Edit Kemasan';
        submitButton.innerHTML = `
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Update Kemasan
        `;
    }

    submitButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
    submitButton.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
    cancelButton.classList.remove('hidden');

    // Focus pada nama field dengan delay minimal
    setTimeout(() => {
        const nameField = document.getElementById('name');
        if (nameField) {
            nameField.focus();
            nameField.select(); // Select text untuk editing yang lebih mudah
        }
    }, 300);
}

function resetForm() {
    document.getElementById('bahan_baku_id').value = '';
    document.getElementById('name').value = '';
    document.getElementById('brand').value = '';
    document.getElementById('type').value = typeOptions[0];
    document.getElementById('unit').value = unitOptions[0];
    document.getElementById('purchase_size').value = '';
    document.getElementById('purchase_price_per_unit').value = '';
    document.getElementById('current_stock').value = '';

    // Reset labels to default (bahan)
    const purchaseSizeLabel = document.getElementById('purchase_size_label');
    const currentStockLabel = document.getElementById('current_stock_label');
    const purchasePriceLabel = document.getElementById('purchase_price_label');
    const purchaseSizeHelp = document.getElementById('purchase_size_help');
    const purchasePriceHelp = document.getElementById('purchase_price_help');
    const currentStockHelp = document.getElementById('current_stock_help');

    purchaseSizeLabel.textContent = 'Ukuran Beli Kemasan Bahan';
    currentStockLabel.textContent = 'Stok Bahan Tersedia';
    purchasePriceLabel.textContent = 'Harga Beli Per Kemasan Bahan';
    purchaseSizeHelp.textContent = 'Isi per kemasan bahan yang Anda beli (sesuai satuan yang tertera di plastik kemasan yang anda beli)';
    purchasePriceHelp.textContent = 'Harga per kemasan bahan saat pembelian';
    currentStockHelp.textContent = 'Berapa berat bahan yang saat ini tersedia di stok';

    document.getElementById('form-title').textContent = 'Tambah Bahan Baku/Kemasan Baru';

    const submitButton = document.getElementById('submit_button');
    const cancelButton = document.getElementById('cancel_edit_button');

    submitButton.innerHTML = `
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Tambah Bahan
    `;
    submitButton.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
    submitButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
    cancelButton.classList.add('hidden');
}

// AJAX search setup
function setupAjaxSearch() {
    let searchTimeoutRaw;
    let searchTimeoutKemasan;

    // Search for raw materials
    const searchRaw = document.getElementById('search_raw');
    const bahanLimit = document.getElementById('bahan_limit');

    if (searchRaw) {
        searchRaw.addEventListener('input', function() {
            const searchTerm = this.value;
            clearTimeout(searchTimeoutRaw);
            searchTimeoutRaw = setTimeout(() => {
                performAjaxSearch('raw', searchTerm, bahanLimit ? bahanLimit.value : 6);
            }, 500);
        });
    }

    if (bahanLimit) {
        bahanLimit.addEventListener('change', function() {
            const searchTerm = searchRaw ? searchRaw.value : '';
            performAjaxSearch('raw', searchTerm, this.value);
        });
    }

    // Search for packaging materials
    const searchKemasan = document.getElementById('search_kemasan');
    const kemasanLimit = document.getElementById('kemasan_limit');

    if (searchKemasan) {
        searchKemasan.addEventListener('input', function() {
            const searchTerm = this.value;
            clearTimeout(searchTimeoutKemasan);
            searchTimeoutKemasan = setTimeout(() => {
                performAjaxSearch('kemasan', searchTerm, kemasanLimit ? kemasanLimit.value : 6);
            }, 500);
        });
    }

    if (kemasanLimit) {
        kemasanLimit.addEventListener('change', function() {
            const searchTerm = searchKemasan ? searchKemasan.value : '';
            performAjaxSearch('kemasan', searchTerm, this.value);
        });
    }
}

// Variables untuk menyimpan posisi scroll
let currentScrollPosition = 0;

function saveScrollPosition() {
    currentScrollPosition = window.pageYOffset;
}

function restoreScrollPosition() {
    window.scrollTo(0, currentScrollPosition);
}

function performAjaxSearch(type, searchTerm, limit) {
    const containerId = type === 'raw' ? 'raw-materials-container' : 'packaging-materials-container';
    const container = document.getElementById(containerId);

    if (!container) return;

    // Simpan posisi scroll sebelum AJAX
    saveScrollPosition();

    // Show loading
    container.innerHTML = `
        <div class="col-span-full flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Mencari...</span>
        </div>
    `;

    // Build URL parameters
    const params = new URLSearchParams();
    if (type === 'raw') {
        params.set('search_raw', searchTerm);
        params.set('bahan_limit', limit);
        params.set('ajax_type', 'raw');
    } else {
        params.set('search_kemasan', searchTerm);
        params.set('kemasan_limit', limit);
        params.set('ajax_type', 'kemasan');
    }
    params.set('ajax', '1');

    // Perform AJAX request
    fetch(`/cornerbites-sia/pages/bahan_baku.php?${params.toString()}`)
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
            // Restore posisi scroll setelah konten dimuat
            setTimeout(() => {
                restoreScrollPosition();
            }, 10);
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = `
                <div class="col-span-full text-center py-12 text-red-600">
                    Terjadi kesalahan saat mencari data.
                </div>
            `;
            // Restore posisi scroll meskipun ada error
            setTimeout(() => {
                restoreScrollPosition();
            }, 10);
        });
}

// Make functions global
window.editBahanBaku = editBahanBaku;
window.resetForm = resetForm;