// Common JavaScript functions for approval requests

// Request-level approval step UI removed

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

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Request-level step badge interactions removed
});

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    const purchasingModal = document.getElementById('purchasingStatusModal');
    if (purchasingModal && !purchasingModal.classList.contains('hidden')) {
        if (e.target === purchasingModal) {
            closePurchasingStatusModal();
        }
    }
});
