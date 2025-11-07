// Common JavaScript functions for approval requests

// Step Status Modal Functions
async function showStepStatus(stepName, stepStatus, stepNumber, requestId) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('stepStatusModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'stepStatusModal';
        modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden';
        modal.innerHTML = `
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-medium text-gray-900">Detail Step Approval</h3>
                        <button onclick="closeStepStatusModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="stepStatusContent"></div>
                    <div class="pt-3">
                        <button onclick="closeStepStatusModal()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded text-sm">Tutup</button>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(modal);
    }

    const content = document.getElementById('stepStatusContent');
    content.innerHTML = '<div class="p-3 text-sm text-gray-600">Memuat...</div>';
    modal.classList.remove('hidden');

    try {
        const baseUrl = window.location.origin;
        const url = `${baseUrl}/api/approval-requests/${requestId}/step-status/${stepNumber}`;
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        
        const actionTime = data.action_time ? new Date(data.action_time).toLocaleString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }) : '-';
        const actionBy = data.action_by || '-';
        const notes = data.notes || '-';

        content.innerHTML = `
            <div class="space-y-3 text-sm">
                <div><span class="font-medium">Step:</span> ${stepName}</div>
                <div><span class="font-medium">Nomor Step:</span> ${stepNumber}</div>
                <div><span class="font-medium">Status:</span> <span class="px-2 py-1 rounded text-xs ${getStepStatusClass(stepStatus)}">${stepStatus}</span></div>
                <div><span class="font-medium">Waktu Aksi:</span> ${actionTime}</div>
                <div><span class="font-medium">Oleh:</span> ${actionBy}</div>
                ${notes !== '-' ? `<div><span class="font-medium">Catatan:</span><div class="mt-1 p-2 bg-gray-50 rounded text-gray-700">${notes}</div></div>` : ''}
            </div>`;
    } catch (e) {
        console.error('Error fetching step status:', e);
        content.innerHTML = '<div class="p-3 text-sm text-red-600">Gagal memuat detail step.</div>';
    }
}

function closeStepStatusModal() {
    const modal = document.getElementById('stepStatusModal');
    if (modal) modal.classList.add('hidden');
}

function getStepStatusClass(status) {
    const statusMap = {
        'Approved': 'bg-green-100 text-green-800',
        'Rejected': 'bg-red-100 text-red-800',
        'On Progress': 'bg-blue-100 text-blue-800',
        'Pending': 'bg-yellow-100 text-yellow-800'
    };
    return statusMap[status] || 'bg-gray-100 text-gray-800';
}

// Purchasing Status Modal Functions
async function openPurchasingStatusModal(code, label, requestId, el = null) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('purchasingStatusModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'purchasingStatusModal';
        modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden';
        modal.innerHTML = `
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-medium text-gray-900">Detail Status Purchasing</h3>
                        <button onclick="closePurchasingStatusModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="purchasingStatusContent"></div>
                    <div class="pt-3">
                        <button onclick="closePurchasingStatusModal()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded text-sm">Tutup</button>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(modal);
    }

    const content = document.getElementById('purchasingStatusContent');
    content.innerHTML = '<div class="p-3 text-sm text-gray-600">Memuat...</div>';
    modal.classList.remove('hidden');

    try {
        const baseUrl = window.location.origin;
        const url = `${baseUrl}/api/purchasing/status/${requestId}`;
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        const changedAt = data.changed_at ? new Date(data.changed_at).toLocaleString('id-ID') : '-';
        const changedBy = data.changed_by_name || 'Tidak tersedia';
        const notes = (data.status_code === 'done' && data.done_notes) ? data.done_notes : null;
        const bmNotes = (data.status_code === 'benchmarking') ? (el?.getAttribute('data-bmnotes') || '') : '';

        content.innerHTML = `
            <div class="space-y-3 text-sm">
                <div><span class="font-medium">Request ID:</span> ${requestId}</div>
                <div><span class="font-medium">Status Purchasing:</span> ${data.status_label || label}</div>
                <div><span class="font-medium">Waktu aksi:</span> ${changedAt}</div>
                <div><span class="font-medium">Diubah oleh:</span> ${changedBy}</div>
                ${bmNotes ? `<div class="text-gray-800">${bmNotes}</div>` : ''}
                ${notes ? `<div><span class="font-medium">Catatan DONE:</span> ${notes}</div>` : ''}
                <div class="text-gray-600">Status ini adalah status gabungan dari item-item pembelian pada request ini.</div>
            </div>`;
    } catch (e) {
        content.innerHTML = '<div class="p-3 text-sm text-red-600">Gagal memuat detail status.</div>';
    }
}

function closePurchasingStatusModal(){
    const modal = document.getElementById('purchasingStatusModal');
    if (modal) modal.classList.add('hidden');
}

// Load step metadata (time and user info)
async function loadStepMetadata() {
    const stepMetas = document.querySelectorAll('.step-meta');
    
    for (const meta of stepMetas) {
        const requestId = meta.getAttribute('data-request-id');
        const stepNumber = meta.getAttribute('data-step-number');
        const masterItemId = meta.getAttribute('data-master-item-id');
        
        if (!requestId || !stepNumber) continue;
        
        try {
            const baseUrl = window.location.origin;
            let url = `${baseUrl}/api/approval-requests/${requestId}/step-status/${stepNumber}`;
            
            // Add master_item_id as query parameter if available
            if (masterItemId) {
                url += `?master_item_id=${masterItemId}`;
            }
            
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            
            if (data.action_time && data.action_by) {
                const actionDate = new Date(data.action_time);
                const dateStr = actionDate.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit' });
                const timeStr = actionDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                const userName = data.action_by; // Full name
                
                // Don't show required_action - it's internal metadata
                meta.innerHTML = `<div class="text-gray-600 whitespace-nowrap">${dateStr} ${timeStr} • ${userName}</div>`;
            } else {
                meta.innerHTML = '<div class="text-gray-400">-</div>';
            }
        } catch (e) {
            console.error('Error loading step metadata:', e);
            meta.innerHTML = '<div class="text-gray-400">-</div>';
        }
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Load step metadata on page load
    loadStepMetadata();
});

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    const stepModal = document.getElementById('stepStatusModal');
    if (stepModal && !stepModal.classList.contains('hidden')) {
        if (e.target === stepModal) {
            closeStepStatusModal();
        }
    }
    
    const purchasingModal = document.getElementById('purchasingStatusModal');
    if (purchasingModal && !purchasingModal.classList.contains('hidden')) {
        if (e.target === purchasingModal) {
            closePurchasingStatusModal();
        }
    }
});
