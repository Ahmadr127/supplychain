<!-- Modal Form for Master Items -->
<div id="masterItemModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <!-- Modal Header -->
        <div class="flex justify-between items-center pb-4 border-b border-gray-200">
            <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Tambah Master Barang</h3>
            <button onclick="closeMasterItemModal()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="masterItemForm" action="{{ route('master-items.store') }}" method="POST" class="mt-6">
            @csrf
            <input type="hidden" id="formMethod" name="_method" value="POST">
            <input type="hidden" id="redirectAfterSave" name="redirect_after_save" value="false">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Nama Barang -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nama Barang *</label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Masukkan nama barang">
                </div>

                <!-- Kode Barang -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Kode Barang *</label>
                    <input type="text" id="code" name="code" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Masukkan kode barang">
                </div>

                <!-- Tipe Barang -->
                <div>
                    <label for="item_type_id" class="block text-sm font-medium text-gray-700 mb-2">Tipe Barang *</label>
                    <select id="item_type_id" name="item_type_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Pilih Tipe Barang</option>
                        @if(isset($itemTypes))
                            @foreach($itemTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Kategori Barang -->
                <div>
                    <label for="item_category_id" class="block text-sm font-medium text-gray-700 mb-2">Kategori Barang *</label>
                    <select id="item_category_id" name="item_category_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Pilih Kategori</option>
                        @if(isset($itemCategories))
                            @foreach($itemCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Komoditas -->
                <div>
                    <label for="commodity_id" class="block text-sm font-medium text-gray-700 mb-2">Komoditas *</label>
                    <select id="commodity_id" name="commodity_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Pilih Komoditas</option>
                        @if(isset($commodities))
                            @foreach($commodities as $commodity)
                                <option value="{{ $commodity->id }}">{{ $commodity->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Satuan -->
                <div>
                    <label for="unit_id" class="block text-sm font-medium text-gray-700 mb-2">Satuan *</label>
                    <select id="unit_id" name="unit_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Pilih Satuan</option>
                        @if(isset($units))
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->code }})</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Stok -->
                <div>
                    <label for="stock" class="block text-sm font-medium text-gray-700 mb-2">Stok Awal</label>
                    <input type="number" id="stock" name="stock" min="0" step="0.01"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="0" value="0">
                </div>

                <!-- HNA -->
                <div>
                    <label for="hna" class="block text-sm font-medium text-gray-700 mb-2">HNA (Harga Netto) *</label>
                    <input type="number" id="hna" name="hna" required min="0" step="0.01"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="0">
                </div>

                <!-- PPN Percentage -->
                <div>
                    <label for="ppn_percentage" class="block text-sm font-medium text-gray-700 mb-2">PPN (%)</label>
                    <input type="number" id="ppn_percentage" name="ppn_percentage" min="0" max="100" step="0.01"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="0" value="0">
                </div>

                <!-- Status -->
                <div>
                    <label for="is_active" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="is_active" name="is_active"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="1" selected>Aktif</option>
                        <option value="0">Tidak Aktif</option>
                    </select>
                </div>

                <!-- Deskripsi -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Masukkan deskripsi barang (opsional)"></textarea>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeMasterItemModal()"
                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium rounded-md transition-colors duration-200">
                    Batal
                </button>
                <button type="submit" id="submitButton"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors duration-200">
                    <i class="fas fa-save mr-2"></i>
                    <span id="submitButtonText">Simpan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript for Modal -->
<script>
// Global variable to store current mode
let currentMode = 'create';
let currentItemId = null;

function openAddModal() {
    currentMode = 'create';
    currentItemId = null;
    
    // Reset form
    document.getElementById('masterItemForm').reset();
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('masterItemForm').action = '{{ route("master-items.store") }}';
    
    // Update modal title and button
    document.getElementById('modalTitle').textContent = 'Tambah Master Barang';
    document.getElementById('submitButtonText').textContent = 'Simpan';
    
    // Show modal
    document.getElementById('masterItemModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function openEditModal(itemId, itemData) {
    currentMode = 'edit';
    currentItemId = itemId;
    
    // Set form action and method
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('masterItemForm').action = `/master-items/${itemId}`;
    
    // Populate form fields
    document.getElementById('name').value = itemData.name || '';
    document.getElementById('code').value = itemData.code || '';
    document.getElementById('item_type_id').value = itemData.item_type_id || '';
    document.getElementById('item_category_id').value = itemData.item_category_id || '';
    document.getElementById('commodity_id').value = itemData.commodity_id || '';
    document.getElementById('unit_id').value = itemData.unit_id || '';
    document.getElementById('stock').value = itemData.stock || 0;
    document.getElementById('hna').value = itemData.hna || '';
    document.getElementById('ppn_percentage').value = itemData.ppn_percentage || 0;
    document.getElementById('is_active').value = itemData.is_active ? '1' : '0';
    document.getElementById('description').value = itemData.description || '';
    
    // Update modal title and button
    document.getElementById('modalTitle').textContent = 'Edit Master Barang';
    document.getElementById('submitButtonText').textContent = 'Update';
    
    // Show modal
    document.getElementById('masterItemModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeMasterItemModal() {
    document.getElementById('masterItemModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    document.getElementById('masterItemForm').reset();
    currentMode = 'create';
    currentItemId = null;
}

// Close modal when clicking outside
document.getElementById('masterItemModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeMasterItemModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMasterItemModal();
    }
});

// Auto-calculate PPN amount when HNA or PPN percentage changes
document.getElementById('hna').addEventListener('input', calculatePPNAmount);
document.getElementById('ppn_percentage').addEventListener('input', calculatePPNAmount);

function calculatePPNAmount() {
    const hna = parseFloat(document.getElementById('hna').value) || 0;
    const ppnPercentage = parseFloat(document.getElementById('ppn_percentage').value) || 0;
    const ppnAmount = (hna * ppnPercentage) / 100;
    
    // You can display the calculated PPN amount if needed
    // document.getElementById('ppn_amount_display').textContent = 'Rp ' + ppnAmount.toLocaleString('id-ID');
}

// Function to open edit modal from table row
function openEditModalFromRow(button) {
    const row = button.closest('tr');
    const itemId = row.getAttribute('data-item-id');
    
    // Extract all data from data attributes
    const itemData = {
        name: row.getAttribute('data-item-name') || '',
        code: row.getAttribute('data-item-code') || '',
        item_type_id: row.getAttribute('data-item-type-id') || '',
        item_category_id: row.getAttribute('data-item-category-id') || '',
        commodity_id: row.getAttribute('data-commodity-id') || '',
        unit_id: row.getAttribute('data-unit-id') || '',
        stock: row.getAttribute('data-stock') || 0,
        hna: row.getAttribute('data-hna') || '',
        ppn_percentage: row.getAttribute('data-ppn-percentage') || 0,
        is_active: row.getAttribute('data-is-active') === '1',
        description: row.getAttribute('data-description') || ''
    };
    
    openEditModal(itemId, itemData);
}

// Function to load dropdown data via AJAX if not available
function loadDropdownData() {
    // This function can be used to load dropdown data via AJAX if needed
    // For now, we'll rely on the data being passed from the controller
    return Promise.resolve();
}

// Handle form submission with AJAX
document.getElementById('masterItemForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    const submitButton = document.getElementById('submitButton');
    const originalButtonText = submitButton.innerHTML;
    
    // Show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
    
    // Submit via AJAX
    fetch(form.action, {
        method: form.method,
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            closeMasterItemModal();
            
            // Show success message
            showNotification('Item berhasil ditambahkan!', 'success');
            
            // Refresh the master items list if we're on approval request page
            if (typeof allMasterItems !== 'undefined') {
                // Add the new item to the list
                allMasterItems.push(data.item);
            }
        } else {
            // Show error message
            showNotification(data.message || 'Terjadi kesalahan saat menyimpan item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan saat menyimpan item', 'error');
    })
    .finally(() => {
        // Reset button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    });
});

// Simple notification function
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    }`;
    notification.textContent = message;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>
