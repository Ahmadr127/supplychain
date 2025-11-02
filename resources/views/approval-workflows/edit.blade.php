@extends('layouts.app')

@section('title', 'Edit Approval Workflow')

@section('content')
<div class="w-full mx-auto max-w-6xl">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Edit Approval Workflow</h2>
                <a href="{{ route('approval-workflows.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Kembali
                </a>
            </div>
        </div>

        <div class="p-6">
            <form action="{{ route('approval-workflows.update', $approvalWorkflow) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Nama Workflow -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Workflow <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name', $approvalWorkflow->name) }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                               placeholder="Purchase Request, Leave Request, dll">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tipe Workflow -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                            Tipe Workflow <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="type" name="type" value="{{ old('type', $approvalWorkflow->type) }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('type') border-red-500 @enderror"
                               placeholder="purchase, leave, expense, dll">
                        @error('type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Deskripsi -->
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Deskripsi
                        </label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                                  placeholder="Masukkan deskripsi workflow">{{ old('description', $approvalWorkflow->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Item Type Selection -->
                    <div>
                        <label for="item_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Tipe Barang
                        </label>
                        <select id="item_type_id" name="item_type_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('item_type_id') border-red-500 @enderror">
                            <option value="">Pilih Tipe Barang (Opsional)</option>
                            @foreach(\App\Models\ItemType::where('is_active', true)->get() as $itemType)
                                <option value="{{ $itemType->id }}" {{ old('item_type_id', $approvalWorkflow->item_type_id) == $itemType->id ? 'selected' : '' }}>
                                    {{ $itemType->name }} - {{ $itemType->description }}
                                </option>
                            @endforeach
                        </select>
                        @error('item_type_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Kosongkan jika workflow untuk semua tipe barang</p>
                    </div>

                    <!-- Status -->
                    <div class="md:col-span-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $approvalWorkflow->is_active) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Workflow Aktif</span>
                        </label>
                    </div>
                </div>

                <!-- Workflow Steps -->
                <div class="border-t pt-8">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Workflow Steps</h3>
                            <p class="text-sm text-gray-600 mt-1">Tentukan urutan persetujuan untuk workflow ini</p>
                        </div>
                        <button type="button" onclick="addStep()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded flex items-center">
                            <i class="fas fa-plus mr-2"></i>
                            Tambah Step
                        </button>
                    </div>

                    <div id="stepsContainer">
                        <!-- Load existing steps -->
                        @foreach($approvalWorkflow->workflow_steps as $index => $step)
                        <div class="border border-gray-200 rounded-lg p-6 mb-4 step-item bg-white shadow-sm" data-step-id="existing_step_{{ $index + 1 }}">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-md font-medium text-gray-900 step-number">Step {{ $index + 1 }}</h4>
                                <button type="button" onclick="removeStep(this)" class="text-red-600 hover:text-red-800 flex items-center">
                                    <i class="fas fa-trash mr-1"></i> Hapus
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Step</label>
                                    <input type="text" name="workflow_steps[{{ $index + 1 }}][name]" value="{{ $step['name'] }}" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="Unit Manager Approval">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Approver</label>
                                    <select name="workflow_steps[{{ $index + 1 }}][approver_type]" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            onchange="toggleApproverFields(this, {{ $index + 1 }})">
                                        <option value="">Pilih Tipe Approver</option>
                                        <option value="user" {{ $step['approver_type'] == 'user' ? 'selected' : '' }}>User Spesifik</option>
                                        <option value="role" {{ $step['approver_type'] == 'role' ? 'selected' : '' }}>Role</option>
                                        <option value="department_manager" {{ $step['approver_type'] == 'department_manager' ? 'selected' : '' }}>Manager Department</option>
                                        <option value="requester_department_manager" {{ $step['approver_type'] == 'requester_department_manager' ? 'selected' : '' }}>Manager Departemen Requester</option>
                                        <option value="any_department_manager" {{ $step['approver_type'] == 'any_department_manager' ? 'selected' : '' }}>Semua Manager</option>
                                    </select>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Step (opsional)</label>
                                    <textarea name="workflow_steps[{{ $index + 1 }}][description]" rows="2"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="Contoh: Manager unit input harga dan approve">{{ $step['description'] ?? '' }}</textarea>
                                </div>
                                
                                <!-- Conditional Step Settings -->
                                <div class="md:col-span-2 border-t pt-3 mt-2">
                                    <label class="flex items-center mb-3">
                                        <input type="checkbox" name="workflow_steps[{{ $index + 1 }}][is_conditional]" value="1"
                                               {{ isset($step['is_conditional']) && $step['is_conditional'] ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                               onchange="toggleConditionalFields(this, {{ $index + 1 }})">
                                        <span class="ml-2 text-sm font-medium text-gray-700">Step Conditional (skip jika kondisi tidak terpenuhi)</span>
                                    </label>
                                    
                                    <div id="conditional_fields_{{ $index + 1 }}" class="grid grid-cols-2 gap-4 {{ isset($step['is_conditional']) && $step['is_conditional'] ? '' : 'hidden' }}">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Kondisi</label>
                                            <select name="workflow_steps[{{ $index + 1 }}][condition_type]"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option value="">Pilih Kondisi</option>
                                                <option value="total_price" {{ isset($step['condition_type']) && $step['condition_type'] == 'total_price' ? 'selected' : '' }}>Total Harga</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Nilai Threshold (Rp)</label>
                                            <input type="text" name="workflow_steps[{{ $index + 1 }}][condition_value]"
                                                   value="{{ isset($step['condition_value']) ? number_format($step['condition_value'], 0, ',', '.') : '' }}"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   placeholder="100000000"
                                                   oninput="this.value = this.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')">
                                            <p class="text-xs text-gray-500 mt-1">Step ini akan dijalankan jika total harga >= nilai ini</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="approver_user_{{ $index + 1 }}" class="approver-field" style="display: {{ $step['approver_type'] == 'user' ? 'block' : 'none' }};">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                                    <select name="workflow_steps[{{ $index + 1 }}][approver_id]"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Pilih User</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ isset($step['approver_id']) && $step['approver_id'] == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->role->display_name ?? 'No Role' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div id="approver_role_{{ $index + 1 }}" class="approver-field" style="display: {{ $step['approver_type'] == 'role' ? 'block' : 'none' }};">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                                    <select name="workflow_steps[{{ $index + 1 }}][approver_role_id]"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Pilih Role</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}" {{ isset($step['approver_role_id']) && $step['approver_role_id'] == $role->id ? 'selected' : '' }}>
                                                {{ $role->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div id="approver_department_{{ $index + 1 }}" class="approver-field" style="display: {{ $step['approver_type'] == 'department_manager' ? 'block' : 'none' }};">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                                    <select name="workflow_steps[{{ $index + 1 }}][approver_department_id]"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Pilih Department</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}" {{ isset($step['approver_department_id']) && $step['approver_department_id'] == $dept->id ? 'selected' : '' }}>
                                                {{ $dept->name }} ({{ $dept->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                
                            </div>
                        </div>
                        @endforeach
                    </div>

                    @error('workflow_steps')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end space-x-4 mt-8">
                    <a href="{{ route('approval-workflows.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded">
                        Batal
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                        Update Workflow
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let stepCounter = {{ count($approvalWorkflow->workflow_steps) }};
let steps = [];

// Initialize existing steps
document.addEventListener('DOMContentLoaded', function() {
    const existingSteps = document.querySelectorAll('.step-item');
    existingSteps.forEach((step, index) => {
        const stepId = step.getAttribute('data-step-id');
        steps.push({
            id: stepId,
            number: index + 1
        });
    });
});

function addStep() {
    stepCounter++;
    const stepId = `step_${stepCounter}`;
    const stepNumber = steps.length + 1;
    
    steps.push({
        id: stepId,
        number: stepNumber
    });
    
    const container = document.getElementById('stepsContainer');
    const stepDiv = document.createElement('div');
    stepDiv.className = 'border border-gray-200 rounded-lg p-6 mb-4 step-item bg-white shadow-sm';
    stepDiv.setAttribute('data-step-id', stepId);
    stepDiv.innerHTML = createStepHTML(stepId, stepNumber);
    
    container.appendChild(stepDiv);
    updateStepNumbers();
    
    // Scroll to new step
    stepDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function removeStep(button) {
    const stepDiv = button.closest('.step-item');
    const stepId = stepDiv.getAttribute('data-step-id');
    
    // Confirm deletion
    if (!confirm('Yakin ingin menghapus step ini?')) {
        return;
    }
    
    // Remove from steps array
    steps = steps.filter(step => step.id !== stepId);
    
    // Remove from DOM with animation
    stepDiv.style.transition = 'all 0.3s ease';
    stepDiv.style.opacity = '0';
    stepDiv.style.transform = 'translateX(-100%)';
    
    setTimeout(() => {
        stepDiv.remove();
        updateStepNumbers();
    }, 300);
}

function updateStepNumbers() {
    const stepItems = document.querySelectorAll('.step-item');
    stepItems.forEach((item, index) => {
        const stepNumber = index + 1;
        const stepId = item.getAttribute('data-step-id');
        
        // Update step number display
        const stepNumberElement = item.querySelector('.step-number');
        if (stepNumberElement) {
            stepNumberElement.textContent = `Step ${stepNumber}`;
        }
        
        // Update all input names to use sequential numbering
        const inputs = item.querySelectorAll('input, select');
        inputs.forEach(input => {
            const name = input.getAttribute('name');
            if (name && name.includes('workflow_steps[')) {
                const newName = name.replace(/workflow_steps\[\d+\]/, `workflow_steps[${stepNumber}]`);
                input.setAttribute('name', newName);
            }
        });
        
        // Update all IDs to use sequential numbering
        const elementsWithId = item.querySelectorAll('[id*="approver_"]');
        elementsWithId.forEach(element => {
            const id = element.getAttribute('id');
            if (id) {
                const newId = id.replace(/_\d+$/, `_${stepNumber}`);
                element.setAttribute('id', newId);
            }
        });
        
        // Update onchange handlers
        const approverTypeSelect = item.querySelector('select[name*="[approver_type]"]');
        if (approverTypeSelect) {
            approverTypeSelect.setAttribute('onchange', `toggleApproverFields(this, ${stepNumber})`);
        }
    });
}

function createStepHTML(stepId, stepNumber) {
    return `
        <div class="flex justify-between items-center mb-4">
            <h4 class="text-md font-medium text-gray-900 step-number">Step ${stepNumber}</h4>
            <button type="button" onclick="removeStep(this)" class="text-red-600 hover:text-red-800">
                <i class="fas fa-trash"></i> Hapus
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Step</label>
                <input type="text" name="workflow_steps[${stepNumber}][name]" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Unit Manager Approval">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Approver</label>
                <select name="workflow_steps[${stepNumber}][approver_type]" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        onchange="toggleApproverFields(this, ${stepNumber})">
                    <option value="">Pilih Tipe Approver</option>
                    <option value="user">User Spesifik</option>
                    <option value="role">Role</option>
                    <option value="department_manager">Manager Department</option>
                    <option value="requester_department_manager">Manager Departemen Requester</option>
                    <option value="any_department_manager">Semua Manager</option>
                </select>
            </div>
            
            <div id="approver_user_${stepNumber}" class="approver-field" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                <select name="workflow_steps[${stepNumber}][approver_id]"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Pilih User</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role->display_name ?? 'No Role' }})</option>
                    @endforeach
                </select>
            </div>
            
            <div id="approver_role_${stepNumber}" class="approver-field" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                <select name="workflow_steps[${stepNumber}][approver_role_id]"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Pilih Role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div id="approver_department_${stepNumber}" class="approver-field" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                <select name="workflow_steps[${stepNumber}][approver_department_id]"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Pilih Department</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->code }})</option>
                    @endforeach
                </select>
            </div>
            
            
        </div>
    `;
}

function toggleApproverFields(select, stepNumber) {
    const approverType = select.value;
    
    // Hide all approver fields for this step
    document.querySelectorAll(`[id^="approver_"][id$="_${stepNumber}"]`).forEach(field => {
        field.style.display = 'none';
    });
    
    // Show relevant field based on approver type
    if (approverType === 'user') {
        document.getElementById(`approver_user_${stepNumber}`).style.display = 'block';
    } else if (approverType === 'role') {
        document.getElementById(`approver_role_${stepNumber}`).style.display = 'block';
    } else if (approverType === 'department_manager') {
        document.getElementById(`approver_department_${stepNumber}`).style.display = 'block';
    } else if (approverType === 'requester_department_manager') {
        // No extra fields required for requester_department_manager
    } else if (approverType === 'any_department_manager') {
        // No extra fields required for any_department_manager
    }
}

function toggleConditionalFields(checkbox, stepNumber) {
    const conditionalFields = document.getElementById(`conditional_fields_${stepNumber}`);
    if (checkbox.checked) {
        conditionalFields.classList.remove('hidden');
    } else {
        conditionalFields.classList.add('hidden');
    }
}

// Form validation before submit
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action*="approval-workflows"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            const stepItems = document.querySelectorAll('.step-item');
            if (stepItems.length === 0) {
                e.preventDefault();
                alert('Minimal harus ada 1 step dalam workflow!');
                return false;
            }
            
            // Validate each step
            let isValid = true;
            stepItems.forEach((item, index) => {
                const nameInput = item.querySelector('input[name*="[name]"]');
                const typeSelect = item.querySelector('select[name*="[approver_type]"]');
                
                if (!nameInput.value.trim()) {
                    isValid = false;
                    nameInput.classList.add('border-red-500');
                } else {
                    nameInput.classList.remove('border-red-500');
                }
                
                if (!typeSelect.value) {
                    isValid = false;
                    typeSelect.classList.add('border-red-500');
                } else {
                    typeSelect.classList.remove('border-red-500');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Mohon lengkapi semua field yang wajib diisi!');
                return false;
            }
        });
    }
});
</script>
@endsection
