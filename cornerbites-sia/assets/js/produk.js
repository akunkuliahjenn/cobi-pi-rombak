// Produk JavaScript Functions

// Format Rupiah function
function formatRupiah(element, hiddenInputId) {
    let value = element.value.replace(/[^0-9]/g, '');
    
    if (value === '') {
        element.value = '';
        document.getElementById(hiddenInputId).value = '';
        return;
    }
    
    let formatted = new Intl.NumberFormat('id-ID').format(value);
    element.value = formatted;
    document.getElementById(hiddenInputId).value = value;
}

// JavaScript untuk mengisi form saat tombol edit diklik
function editProduct(product) {
    document.getElementById('product_id_to_edit').value = product.id;
    document.getElementById('product_name').value = product.name;
    
    // Handle unit dropdown
    const unitSelect = document.getElementById('unit');
    const customInput = document.getElementById('unit_custom');
    const unitOptions = ['pcs', 'porsi', 'bungkus', 'cup', 'botol', 'gelas', 'slice', 'pack', 'box', 'kg', 'gram', 'liter', 'ml'];
    
    if (unitOptions.includes(product.unit)) {
        unitSelect.value = product.unit;
        customInput.classList.add('hidden');
        customInput.required = false;
    } else {
        unitSelect.value = 'custom';
        customInput.value = product.unit;
        customInput.classList.remove('hidden');
        customInput.required = true;
    }
    
    document.getElementById('stock').value = product.stock;
    
    // Format dan set harga jual - mempertahankan nilai lama dan menampilkan info harga terakhir
    const formattedPrice = new Intl.NumberFormat('id-ID').format(product.sale_price);
    document.getElementById('sale_price_display').value = formattedPrice;
    document.getElementById('sale_price').value = product.sale_price;

    // Tampilkan info harga terakhir
    const lastPriceInfo = document.getElementById('last_price_info');
    const lastPriceValue = document.getElementById('last_price_value');
    lastPriceValue.textContent = 'Rp ' + formattedPrice;
    lastPriceInfo.classList.remove('hidden');

    // Update judul form
    const formTitle = document.getElementById('form_title');
    const formDescription = document.getElementById('form_description');
    
    if (formTitle) {
        formTitle.textContent = 'Edit Produk';
    }
    if (formDescription) {
        formDescription.textContent = 'Ubah detail produk yang sudah ada sesuai kebutuhan Anda.';
    }

    // Update tombol dan tampilan
    const submitButton = document.getElementById('submit_button');
    const cancelButton = document.getElementById('cancel_edit_button');
    
    submitButton.innerHTML = `
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
        </svg>
        Edit Produk
    `;
    submitButton.classList.remove('bg-green-600', 'hover:bg-green-700');
    submitButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
    
    // Tampilkan tombol batal edit
    if (cancelButton) {
        cancelButton.classList.remove('hidden');
    }
    
    // Scroll to form to make it visible
    document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
}

// JavaScript untuk mereset form
function resetForm() {
    // Reset semua field
    document.getElementById('product_id_to_edit').value = '';
    document.getElementById('product_name').value = '';
    
    // Reset unit dropdown
    const unitSelect = document.getElementById('unit');
    const customInput = document.getElementById('unit_custom');
    unitSelect.value = '';
    customInput.value = '';
    customInput.classList.add('hidden');
    customInput.required = false;
    
    document.getElementById('stock').value = '';
    
    // Reset display dan hidden inputs untuk harga
    document.getElementById('sale_price_display').value = '';
    document.getElementById('sale_price').value = '';

    // Sembunyikan info harga terakhir
    const lastPriceInfo = document.getElementById('last_price_info');
    lastPriceInfo.classList.add('hidden');

    // Reset judul form
    const formTitle = document.getElementById('form_title');
    const formDescription = document.getElementById('form_description');
    
    if (formTitle) {
        formTitle.textContent = 'Tambah Produk Baru';
    }
    if (formDescription) {
        formDescription.textContent = 'Isi detail produk baru Anda atau gunakan form ini untuk mengedit produk yang sudah ada.';
    }

    // Reset tombol ke mode tambah
    const submitButton = document.getElementById('submit_button');
    const cancelButton = document.getElementById('cancel_edit_button');
    
    submitButton.innerHTML = `
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Tambah Produk
    `;
    submitButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
    submitButton.classList.add('bg-green-600', 'hover:bg-green-700');
    cancelButton.classList.add('hidden');
    
    // Scroll ke form untuk menampilkan form
    document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
}

// Toggle custom unit input
function toggleCustomUnit() {
    const unitSelect = document.getElementById('unit');
    const customInput = document.getElementById('unit_custom');
    
    if (unitSelect.value === 'custom') {
        customInput.classList.remove('hidden');
        customInput.required = true;
        customInput.focus();
    } else {
        customInput.classList.add('hidden');
        customInput.required = false;
        customInput.value = '';
    }
}

// Validate form before submit
function validateForm() {
    const unitSelect = document.getElementById('unit');
    const customInput = document.getElementById('unit_custom');
    
    if (unitSelect.value === 'custom' && customInput.value.trim() === '') {
        alert('Silakan masukkan satuan custom');
        customInput.focus();
        return false;
    }
    
    return true;
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add form validation
    const form = document.querySelector('form[action="/cornerbites-sia/process/simpan_produk.php"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });
    }
    
    console.log('Produk page loaded');
});