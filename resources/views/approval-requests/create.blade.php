@extends('layouts.app')

@section('title', 'Buat Approval Request')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form action="{{ route('approval-requests.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                    <!-- Left Column - Main Form (60%) -->
                    <div class="lg:col-span-3 space-y-4">
                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                                Judul Request <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="title" name="title" value="{{ old('title') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('title') border-red-500 @enderror"
                                   placeholder="Masukkan judul request">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Request Number -->
                        <div>
                            <label for="request_number" class="block text-sm font-medium text-gray-700 mb-1">
                                Nomor Request
                            </label>
                            <input type="text" id="request_number" name="request_number" value="{{ old('request_number') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('request_number') border-red-500 @enderror"
                                   placeholder="Auto-generated jika kosong">
                            @error('request_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>



                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                Deskripsi
                            </label>
                            <textarea id="description" name="description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                                      placeholder="Masukkan deskripsi detail request">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Master Items Selection -->
                        <div id="itemsSection">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-lg font-semibold text-gray-900">Item yang Diminta</h3>
                                <button type="button" onclick="openAddItemModal()" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-3 rounded-lg text-sm transition-colors duration-200">
                                    <i class="fas fa-plus mr-1"></i> Tambah Barang
                                </button>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-3">Pilih item yang akan diminta dalam approval ini (opsional)</p>
                                
                                <!-- Item Search -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cari Item</label>
                                    <div class="relative">
                                        <input type="text" id="itemSearch" placeholder="Ketik nama atau kode item..."
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               autocomplete="off">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                        </div>
                                        
                                        <!-- Search Results Dropdown -->
                                        <div id="searchResults" class="hidden absolute z-10 left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                            <!-- Search results will be populated here -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Selected Items -->
                                <div id="selectedItems" class="space-y-3">
                                    <div class="text-center text-gray-500 py-6">
                                        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                        <p class="mt-2 text-sm">Belum ada item yang dipilih</p>
                                        <p class="text-xs text-gray-400">Gunakan pencarian atau tombol "Tambah Barang" di atas</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column - Workflow & Info (40%) -->
                    <div class="lg:col-span-2 space-y-4">
                    <!-- Workflow Selection -->
                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                            <h3 class="text-base font-semibold text-gray-900 mb-2">Workflow Approval</h3>
                    <div>
                                <label for="workflow_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Pilih Workflow <span class="text-red-500">*</span>
                        </label>
                        <select id="workflow_id" name="workflow_id" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('workflow_id') border-red-500 @enderror"
                                onchange="loadWorkflowSteps(this.value)">
                            <option value="">Pilih Workflow</option>
                            @foreach($workflows as $workflow)
                                <option value="{{ $workflow->id }}" {{ old('workflow_id') == $workflow->id ? 'selected' : '' }}>
                                    {{ $workflow->name }} ({{ $workflow->type }})
                                </option>
                            @endforeach
                        </select>
                        @error('workflow_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Workflow Steps Preview -->
                            <div id="workflowStepsPreview" class="hidden mt-3">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Tahap Approval</h4>
                                <div id="stepsList" class="space-y-1">
                            <!-- Steps will be loaded here -->
                        </div>
                    </div>
                    </div>

                        <!-- Request Type -->
                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                            <h3 class="text-base font-semibold text-gray-900 mb-2">Tipe Request</h3>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" id="request_type_normal" name="request_type" value="normal" 
                                           {{ old('request_type', 'normal') == 'normal' ? 'checked' : '' }}
                                           class="text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Request Normal</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" id="request_type_cto" name="request_type" value="cto" 
                                           {{ old('request_type') == 'cto' ? 'checked' : '' }}
                                           class="text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Request CTO (Chief Technology Officer)</span>
                        </label>
                        </div>
                    </div>

                    <!-- File Attachments -->
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Lampiran File</h3>
                            <div class="space-y-3">
                                <div>
                                    <input type="file" id="attachments" name="attachments[]" multiple 
                                           accept=".pdf" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('attachments') border-red-500 @enderror">
                                    @error('attachments')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-gray-500">Pilih satu atau lebih file PDF (maksimal 10MB per file)</p>
                                </div>
                                
                                <div id="filePreview" class="space-y-2">
                                    <!-- File previews will be shown here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <a href="{{ route('approval-requests.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                        Batal
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                        <i class="fas fa-save mr-1"></i> Buat Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include Modal Form for Adding Items -->
@include('components.modals.form-master-items')

<script>
// Helper function to escape HTML
function escapeHtml(text) {
    if (text == null) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

let selectedItems = [];
let allMasterItems = {!! json_encode($masterItems, JSON_HEX_APOS | JSON_HEX_QUOT) !!};

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    const workflowId = document.getElementById('workflow_id').value;
    if (workflowId) {
        loadWorkflowSteps(workflowId);
    }
    
    // Initialize item search functionality
    initializeItemSearch();
    
    // Initialize file preview
    initializeFilePreview();
});

function loadWorkflowSteps(workflowId) {
    if (!workflowId) {
        document.getElementById('workflowStepsPreview').classList.add('hidden');
        return;
    }

    // Get workflow data from the select option
    const select = document.getElementById('workflow_id');
    const selectedOption = select.options[select.selectedIndex];
    const workflowName = selectedOption.text.split(' (')[0];
    
    // Show loading state
    const stepsList = document.getElementById('stepsList');
    stepsList.innerHTML = `
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
                <p class="text-sm text-gray-600">Memuat workflow steps...</p>
            </div>
        </div>
    `;
    
    document.getElementById('workflowStepsPreview').classList.remove('hidden');
    
    // Fetch workflow steps via AJAX
    fetch(`/api/workflows/${workflowId}/steps`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.steps) {
                let stepsHtml = '<div class="flex flex-wrap gap-2">';
                data.steps.forEach((step, index) => {
                    stepsHtml += `
                        <div class="flex items-center p-2 border border-gray-200 rounded-lg bg-white min-w-0 flex-1">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium text-gray-900 break-words">${step.name}</p>
                            </div>
                        </div>
                    `;
                });
                stepsHtml += '</div>';
                
                stepsList.innerHTML = stepsHtml;
            } else {
                stepsList.innerHTML = `
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm text-yellow-800">Tidak dapat memuat workflow steps.</p>
                        <p class="text-xs text-yellow-600 mt-1">Request akan melalui tahap approval sesuai dengan workflow yang dipilih.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading workflow steps:', error);
            stepsList.innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <p class="text-sm text-red-800">Error memuat workflow steps.</p>
                    <p class="text-xs text-red-600 mt-1">Request akan melalui tahap approval sesuai dengan workflow yang dipilih.</p>
                </div>
            `;
        });
}


// Item search and selection functionality
function initializeItemSearch() {
    const searchInput = document.getElementById('itemSearch');
    const searchResults = document.getElementById('searchResults');
    
    // Add event listeners
    searchInput.addEventListener('input', handleSearch);
    searchInput.addEventListener('focus', handleSearch);
    searchInput.addEventListener('blur', function() {
        // Delay hiding to allow clicking on results
        setTimeout(() => {
            searchResults.classList.add('hidden');
        }, 200);
    });
    
    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.add('hidden');
        }
    });
}

function handleSearch() {
    const search = document.getElementById('itemSearch').value.toLowerCase().trim();
    const searchResults = document.getElementById('searchResults');
    
    if (search.length < 2) {
        searchResults.classList.add('hidden');
        return;
    }
    
    let filteredItems = allMasterItems.filter(item => {
        return item.name.toLowerCase().includes(search) || 
               item.code.toLowerCase().includes(search);
    }).slice(0, 10); // Limit to 10 results
    
    displaySearchResults(filteredItems);
}

function displaySearchResults(items) {
    const container = document.getElementById('searchResults');
    const searchInput = document.getElementById('itemSearch');
    
    if (items.length === 0) {
        container.innerHTML = `
            <div class="p-3 text-center text-gray-500">
                <p class="text-sm">Tidak ada item yang ditemukan</p>
            </div>
        `;
        container.classList.remove('hidden');
        return;
    }
    
    let html = '<div class="py-1">';
    items.forEach(item => {
        const isSelected = selectedItems.some(selected => selected.id === item.id);
        html += `
            <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer ${isSelected ? 'bg-blue-50' : ''}" 
                 onclick="${isSelected ? '' : 'selectItem(' + item.id + ')'}">
                <div class="flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">${escapeHtml(item.name)}</p>
                                <p class="text-xs font-medium text-green-600">
                                    Rp ${parseFloat(item.total_price).toLocaleString('id-ID')} / ${escapeHtml(item.unit.name)}
                                </p>
                    </div>
                    <div class="flex-shrink-0">
                        ${isSelected ? 
                            '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Dipilih</span>' :
                            '<button type="button" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200">Pilih</button>'
                        }
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
    container.classList.remove('hidden');
}

// Function to open add item modal
function openAddItemModal() {
    openAddModal();
}

function selectItem(itemId) {
    const item = allMasterItems.find(i => i.id === itemId);
    if (item && !selectedItems.some(selected => selected.id === itemId)) {
        selectedItems.push({
            ...item,
            quantity: 1,
            unit_price: parseFloat(item.total_price),
            notes: ''
        });
        updateSelectedItemsDisplay();
        
        // Clear search and hide results
        document.getElementById('itemSearch').value = '';
        document.getElementById('searchResults').classList.add('hidden');
    }
}

function removeItem(itemId) {
    selectedItems = selectedItems.filter(item => item.id !== itemId);
    updateSelectedItemsDisplay();
}

function updateItemQuantity(itemId, quantity) {
    const item = selectedItems.find(i => i.id === itemId);
    if (item) {
        item.quantity = parseInt(quantity) || 1;
        item.total_price = item.quantity * item.unit_price;
        updateSelectedItemsDisplay();
    }
}

function updateItemUnitPrice(itemId, unitPrice) {
    const item = selectedItems.find(i => i.id === itemId);
    if (item) {
        item.unit_price = parseFloat(unitPrice) || 0;
        item.total_price = item.quantity * item.unit_price;
        updateSelectedItemsDisplay();
    }
}

function updateItemNotes(itemId, notes) {
    const item = selectedItems.find(i => i.id === itemId);
    if (item) {
        item.notes = notes;
    }
}

function updateSelectedItemsDisplay() {
    const container = document.getElementById('selectedItems');
    
    if (selectedItems.length === 0) {
        container.innerHTML = `
            <div class="text-center text-gray-500 py-6">
                <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <p class="mt-2 text-sm">Belum ada item yang dipilih</p>
                <p class="text-xs text-gray-400">Gunakan pencarian atau tombol "Tambah Barang" di atas</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    selectedItems.forEach((item, index) => {
        html += `
            <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm">
                <!-- Row 1: Item Name and Remove Button -->
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-900">${escapeHtml(item.name)}</h4>
                    <button type="button" onclick="removeItem(${item.id})" 
                            class="p-1 text-gray-400 hover:text-red-600 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Row 2: Quantity, Unit Price, Total Price, and Notes -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Jumlah</label>
                        <div class="relative">
                            <input type="number" min="1" value="${item.quantity}" 
                                   onchange="updateItemQuantity(${item.id}, this.value)"
                                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2">
                                <span class="text-xs text-gray-500">${escapeHtml(item.unit.name)}</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Harga Satuan</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-2">
                                <span class="text-xs text-gray-500">Rp</span>
                            </div>
                            <input type="number" min="0" step="0.01" value="${item.unit_price}" 
                                   onchange="updateItemUnitPrice(${item.id}, this.value)"
                                   class="w-full pl-8 pr-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Total Harga</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-2">
                                <span class="text-xs text-gray-500">Rp</span>
                            </div>
                            <input type="text" value="${item.total_price.toLocaleString('id-ID')}" readonly
                                   class="w-full pl-8 pr-2 py-1 text-sm border border-gray-300 rounded bg-gray-50 text-gray-900 font-medium">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Catatan</label>
                        <input type="text" placeholder="Catatan khusus..."
                               onchange="updateItemNotes(${item.id}, this.value)"
                               value="${escapeHtml(item.notes)}"
                               class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                </div>
                </div>
                
                <!-- Hidden inputs for form submission -->
                <input type="hidden" name="items[${index}][master_item_id]" value="${item.id}">
                <input type="hidden" name="items[${index}][quantity]" value="${item.quantity}">
                <input type="hidden" name="items[${index}][unit_price]" value="${item.unit_price}">
                <input type="hidden" name="items[${index}][notes]" value="${escapeHtml(item.notes)}">
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// File preview functionality
function initializeFilePreview() {
    const fileInput = document.getElementById('attachments');
    fileInput.addEventListener('change', handleFilePreview);
}

function handleFilePreview(event) {
    const files = event.target.files;
    const previewContainer = document.getElementById('filePreview');
    
    previewContainer.innerHTML = '';
    
    if (files.length === 0) {
        return;
    }
    
    Array.from(files).forEach((file, index) => {
        if (file.type === 'application/pdf') {
            const fileDiv = document.createElement('div');
            fileDiv.className = 'flex items-center justify-between p-2 bg-white border border-gray-200 rounded-lg';
            fileDiv.innerHTML = `
                <div class="flex items-center space-x-2 flex-1 min-w-0">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-gray-900 truncate">${file.name}</p>
                        <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                    </div>
                </div>
                <div class="flex-shrink-0 ml-2">
                    <button type="button" onclick="removeFilePreview(${index})" 
                            class="p-1 text-gray-400 hover:text-red-600 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            previewContainer.appendChild(fileDiv);
        }
    });
}

function removeFilePreview(index) {
    const fileInput = document.getElementById('attachments');
    const dt = new DataTransfer();
    
    // Add all files except the one to be removed
    Array.from(fileInput.files).forEach((file, i) => {
        if (i !== index) {
            dt.items.add(file);
        }
    });
    
    // Update the file input
    fileInput.files = dt.files;
    
    // Refresh the preview
    handleFilePreview({ target: fileInput });
}
</script>
@endsection
