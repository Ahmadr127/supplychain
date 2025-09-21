@extends('layouts.app')

@section('title', 'Edit Approval Request')

@section('content')
<div class="w-full mx-auto max-w-4xl">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Edit Approval Request</h2>
                <div class="flex space-x-2">
                    <a href="{{ route('approval-requests.show', $approvalRequest) }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Lihat Detail
                    </a>
                    <a href="{{ route('approval-requests.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <!-- Request Info -->
            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Request</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Request Number</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $approvalRequest->request_number }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Workflow</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $approvalRequest->workflow->name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <p class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $approvalRequest->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                   ($approvalRequest->status == 'approved' ? 'bg-green-100 text-green-800' : 
                                   ($approvalRequest->status == 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                                {{ ucfirst($approvalRequest->status) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <form action="{{ route('approval-requests.update', $approvalRequest) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 gap-6">
                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Judul Request <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" value="{{ old('title', $approvalRequest->title) }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('title') border-red-500 @enderror"
                               placeholder="Masukkan judul request">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Deskripsi
                        </label>
                        <textarea id="description" name="description" rows="4"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                                  placeholder="Masukkan deskripsi detail request">{{ old('description', $approvalRequest->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>


                    <!-- Master Items Selection -->
                    <div id="itemsSection">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Item yang Diminta</h3>
                        <div class="bg-gray-50 rounded-lg p-6">
                            <p class="text-sm text-gray-600 mb-4">Pilih item yang akan diminta dalam approval ini (opsional)</p>
                            
                            <!-- Item Search -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cari Item</label>
                                <div class="relative">
                                    <input type="text" id="itemSearch" placeholder="Ketik nama atau kode item..."
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           autocomplete="off">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                
                                <!-- Search Results Dropdown -->
                                <div id="searchResults" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    <!-- Search results will be populated here -->
                                </div>
                            </div>

                            <!-- Selected Items -->
                            <div id="selectedItems" class="space-y-4">
                                <div class="text-center text-gray-500 py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    <p class="mt-2 text-sm">Belum ada item yang dipilih</p>
                                    <p class="text-xs text-gray-400">Gunakan pencarian di atas untuk menambahkan item</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- File Attachments -->
                    <div id="attachmentsSection">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Lampiran File</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-4">Upload file PDF sebagai lampiran (maksimal 10MB per file)</p>
                            
                            <!-- Current Attachments -->
                            @if($approvalRequest->attachments->count() > 0)
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">File yang Sudah Diupload:</h4>
                                <div class="space-y-2">
                                    @foreach($approvalRequest->attachments as $attachment)
                                    <div class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg">
                                        <div class="flex items-center space-x-3">
                                            <svg class="h-6 w-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                            </svg>
                                        <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $attachment->original_name }}</p>
                                                <p class="text-xs text-gray-500">{{ $attachment->human_file_size }}</p>
                                        </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('approval-requests.download-attachment', $attachment) }}" 
                                               class="text-blue-600 hover:text-blue-800 text-sm">Download</a>
                                            <label class="flex items-center">
                                                <input type="checkbox" name="remove_attachments[]" value="{{ $attachment->id }}" 
                                                       class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                                <span class="ml-2 text-sm text-red-600">Hapus</span>
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            
                            <div class="space-y-4">
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

                <div class="flex justify-end space-x-4 mt-8">
                    <a href="{{ route('approval-requests.show', $approvalRequest) }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded">
                        Batal
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                        Update Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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

let selectedItems = {!! json_encode($approvalRequest->masterItems->map(function($item) {
    return [
        'id' => $item->id,
        'name' => $item->name,
        'code' => $item->code,
        'item_type' => $item->itemType,
        'item_category' => $item->itemCategory,
        'commodity' => $item->commodity,
        'unit' => $item->unit,
        'total_price' => $item->total_price,
        'quantity' => $item->pivot->quantity,
        'unit_price' => $item->pivot->unit_price,
        'notes' => $item->pivot->notes
    ];
}), JSON_HEX_APOS | JSON_HEX_QUOT) !!};
let allMasterItems = {!! json_encode($masterItems, JSON_HEX_APOS | JSON_HEX_QUOT) !!};

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    // Initialize item search functionality
    initializeItemSearch();
    
    // Initialize file preview
    initializeFilePreview();
    
    // Load existing selected items
    updateSelectedItemsDisplay();
});


// Item search and selection functionality (same as create view)
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
    
    if (items.length === 0) {
        container.innerHTML = `
            <div class="p-4 text-center text-gray-500">
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
            <div class="px-4 py-3 hover:bg-gray-50 cursor-pointer ${isSelected ? 'bg-blue-50' : ''}" 
                 onclick="${isSelected ? '' : 'selectItem(' + item.id + ')'}">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">${escapeHtml(item.name)}</p>
                                <p class="text-xs text-gray-500">Kode: ${escapeHtml(item.code)}</p>
                                <p class="text-xs text-gray-400">
                                    ${escapeHtml(item.item_type.name)} • ${escapeHtml(item.item_category.name)}
                                </p>
                                <p class="text-xs font-medium text-green-600">
                                    Rp ${parseFloat(item.total_price).toLocaleString('id-ID')} / ${escapeHtml(item.unit.name)}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        ${isSelected ? 
                            '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Dipilih</span>' :
                            '<button type="button" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Pilih</button>'
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
            <div class="text-center text-gray-500 py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <p class="mt-2 text-sm">Belum ada item yang dipilih</p>
                <p class="text-xs text-gray-400">Gunakan pencarian di atas untuk menambahkan item</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    selectedItems.forEach((item, index) => {
        html += `
            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-lg font-medium text-gray-900">${escapeHtml(item.name)}</h4>
                            <p class="text-sm text-gray-500">Kode: ${escapeHtml(item.code)}</p>
                            <p class="text-sm text-gray-400">${escapeHtml(item.item_type.name)} • ${escapeHtml(item.item_category.name)}</p>
                            <p class="text-sm font-medium text-green-600 mt-1">
                                Rp ${parseFloat(item.total_price).toLocaleString('id-ID')} / ${escapeHtml(item.unit.name)}
                            </p>
                        </div>
                    </div>
                    <button type="button" onclick="removeItem(${item.id})" 
                            class="flex-shrink-0 p-2 text-gray-400 hover:text-red-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah</label>
                        <div class="relative">
                            <input type="number" min="1" value="${item.quantity}" 
                                   onchange="updateItemQuantity(${item.id}, this.value)"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="text-sm text-gray-500">${escapeHtml(item.unit.name)}</span>
                            </div>
                        </div>
                    </div>
        <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Harga Satuan</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-sm text-gray-500">Rp</span>
                            </div>
                            <input type="number" min="0" step="0.01" value="${item.unit_price}" 
                                   onchange="updateItemUnitPrice(${item.id}, this.value)"
                                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
        </div>
        <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Total Harga</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-sm text-gray-500">Rp</span>
                            </div>
                            <input type="text" value="${item.total_price.toLocaleString('id-ID')}" readonly
                                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-900 font-medium">
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Catatan (Opsional)</label>
                    <textarea rows="2" placeholder="Tambahkan catatan khusus untuk item ini..."
                              onchange="updateItemNotes(${item.id}, this.value)"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none">${escapeHtml(item.notes)}</textarea>
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
            fileDiv.className = 'flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg';
            fileDiv.innerHTML = `
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">${file.name}</p>
                        <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <span class="text-xs text-green-600 font-medium">Siap diupload</span>
                </div>
            `;
            previewContainer.appendChild(fileDiv);
        }
    });
}
</script>
@endsection
