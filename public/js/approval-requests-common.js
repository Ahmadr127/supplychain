// Common JavaScript functions for approval requests

// Step Status Modal Functions
function showStepStatus(stepName, status, stepNumber, requestId) {
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
                        <h3 class="text-lg font-medium text-gray-900">Detail Status Step</h3>
                        <button onclick="closeStepStatusModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="stepStatusContent">
                        <!-- Content will be populated here -->
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Fetch step details via AJAX
    fetch(`/api/approval-steps/${requestId}/${stepNumber}`)
        .then(response => response.json())
        .then(data => {
            const content = document.getElementById('stepStatusContent');
            content.innerHTML = `
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Step</label>
                        <p class="text-sm text-gray-900">${stepName}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(status)}">
                            ${status}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Step Number</label>
                        <p class="text-sm text-gray-900">${stepNumber}</p>
                    </div>
                    ${data.approved_at ? `
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Waktu Aksi</label>
                        <p class="text-sm text-gray-900">${new Date(data.approved_at).toLocaleString('id-ID')}</p>
                    </div>
                    ` : ''}
                    ${data.approved_by_name ? `
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Diapprove Oleh</label>
                        <p class="text-sm text-gray-900">${data.approved_by_name}</p>
                    </div>
                    ` : ''}
                    ${data.comments ? `
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Komentar</label>
                        <p class="text-sm text-gray-900">${data.comments}</p>
                    </div>
                    ` : ''}
                    <div class="pt-3">
                        <button onclick="closeStepStatusModal()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded text-sm">
                            Tutup
                        </button>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error fetching step details:', error);
            const content = document.getElementById('stepStatusContent');
            content.innerHTML = `
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Step</label>
                        <p class="text-sm text-gray-900">${stepName}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(status)}">
                            ${status}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Step Number</label>
                        <p class="text-sm text-gray-900">${stepNumber}</p>
                    </div>
                    <div class="text-xs text-red-600">${String(error)}</div>
                    <div class="pt-3">
                        <button onclick="closeStepStatusModal()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded text-sm">
                            Tutup
                        </button>
                    </div>
                </div>
            `;
        });
    
    // Show modal
    modal.classList.remove('hidden');
}

function closeStepStatusModal() {
    const modal = document.getElementById('stepStatusModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function getStatusColor(status) {
    switch(status.toLowerCase()) {
        case 'approved':
            return 'bg-green-100 text-green-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        case 'on progress':
            return 'bg-blue-100 text-blue-800';
        case 'pending':
        default:
            return 'bg-yellow-100 text-yellow-800';
    }
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

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Add click event listeners to step badges
    const stepBadges = document.querySelectorAll('.step-badge');
    stepBadges.forEach(badge => {
        badge.addEventListener('click', function() {
            const stepName = this.getAttribute('data-step-name');
            const stepStatus = this.getAttribute('data-step-status');
            const stepNumber = this.getAttribute('data-step-number');
            const requestId = this.getAttribute('data-request-id');
            showStepStatus(stepName, stepStatus, stepNumber, requestId);
        });
    });

    // Populate metadata for step badges (only if step-meta elements exist)
    const metas = document.querySelectorAll('.step-meta');
    if (metas.length > 0) {
        metas.forEach(async el => {
            const requestId = el.getAttribute('data-request-id');
            const stepNumber = el.getAttribute('data-step-number');
            try {
                const res = await fetch(`/api/approval-steps/${requestId}/${stepNumber}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('Failed');
                const data = await res.json();
                const approvedBy = data.approved_by_name || null;
                let approvedAt = data.approved_at ? new Date(data.approved_at) : null;
                // Format: d/m/Y (date only)
                const pad = n => String(n).padStart(2, '0');
                let approvedAtStr = null;
                if (approvedAt) {
                    const d = pad(approvedAt.getDate());
                    const m = pad(approvedAt.getMonth() + 1);
                    const y = approvedAt.getFullYear();
                    approvedAtStr = `${d}/${m}/${y}`;
                }
                if (approvedBy && approvedAtStr) {
                    el.innerHTML = `<span>${approvedBy} • ${approvedAtStr}</span>`;
                } else if (approvedBy) {
                    el.innerHTML = `<span>${approvedBy}</span>`;
                } else if (approvedAtStr) {
                    el.innerHTML = `<span>${approvedAtStr}</span>`;
                } else {
                    el.innerHTML = '';
                }
            } catch (e) {
                el.innerHTML = '';
            }
        });
    }
});

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    const stepModal = document.getElementById('stepStatusModal');
    const purchasingModal = document.getElementById('purchasingStatusModal');
    
    if (stepModal && !stepModal.classList.contains('hidden')) {
        if (e.target === stepModal) {
            closeStepStatusModal();
        }
    }
    
    if (purchasingModal && !purchasingModal.classList.contains('hidden')) {
        if (e.target === purchasingModal) {
            closePurchasingStatusModal();
        }
    }
});
