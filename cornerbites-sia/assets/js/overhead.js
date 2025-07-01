
// Reset form overhead
function resetOverheadForm() {
    document.getElementById('overhead_id_to_edit').value = '';
    document.getElementById('overhead_name').value = '';
    document.getElementById('overhead_amount').value = '';
    document.getElementById('overhead_description').value = '';
    document.getElementById('overhead_form_title').textContent = 'Tambah Biaya Overhead Baru';
    document.getElementById('overhead_submit_button').innerHTML = `
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Tambah Overhead
    `;
    document.getElementById('overhead_cancel_edit_button').classList.add('hidden');
}

// Reset form labor
function resetLaborForm() {
    document.getElementById('labor_id_to_edit').value = '';
    document.getElementById('labor_position_name').value = '';
    document.getElementById('labor_hourly_rate').value = '';
    document.getElementById('labor_form_title').textContent = 'Tambah Posisi Tenaga Kerja Baru';
    document.getElementById('labor_submit_button').innerHTML = `
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Tambah Posisi
    `;
    document.getElementById('labor_cancel_edit_button').classList.add('hidden');
}

// Edit overhead
function editOverhead(overhead) {
    document.getElementById('overhead_id_to_edit').value = overhead.id;
    document.getElementById('overhead_name').value = overhead.name;
    document.getElementById('overhead_amount').value = overhead.amount;
    document.getElementById('overhead_description').value = overhead.description || '';
    document.getElementById('overhead_form_title').textContent = 'Edit Biaya Overhead';
    document.getElementById('overhead_submit_button').innerHTML = `
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Update Overhead
    `;
    document.getElementById('overhead_cancel_edit_button').classList.remove('hidden');
}

// Edit labor
function editLabor(labor) {
    document.getElementById('labor_id_to_edit').value = labor.id;
    document.getElementById('labor_position_name').value = labor.position_name;
    document.getElementById('labor_hourly_rate').value = labor.hourly_rate;
    document.getElementById('labor_form_title').textContent = 'Edit Posisi Tenaga Kerja';
    document.getElementById('labor_submit_button').innerHTML = `
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Update Posisi
    `;
    document.getElementById('labor_cancel_edit_button').classList.remove('hidden');
}

// Delete overhead
function deleteOverhead(id, name) {
    if (confirm(`Apakah Anda yakin ingin menghapus biaya overhead "${name}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/cornerbites-sia/process/hapus_overhead.php';
        
        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'type';
        typeInput.value = 'overhead';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'overhead_id';
        idInput.value = id;
        
        form.appendChild(typeInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Delete labor
function deleteLabor(id, name) {
    if (confirm(`Apakah Anda yakin ingin menghapus posisi "${name}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/cornerbites-sia/process/hapus_overhead.php';
        
        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'type';
        typeInput.value = 'labor';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'labor_id';
        idInput.value = id;
        
        form.appendChild(typeInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Format currency input
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('overhead_amount');
    const hourlyRateInput = document.getElementById('labor_hourly_rate');
    
    if (amountInput) {
        amountInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d]/g, '');
            if (value) {
                e.target.value = value;
            }
        });
    }
    
    if (hourlyRateInput) {
        hourlyRateInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d]/g, '');
            if (value) {
                e.target.value = value;
            }
        });
    }
});
