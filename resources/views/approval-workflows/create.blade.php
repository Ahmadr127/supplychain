@extends('layouts.app')

@section('title', 'Tambah Approval Workflow')

@section('content')
    <div class="w-full mx-auto max-w-6xl">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Tambah Approval Workflow</h2>
                    <a href="{{ route('approval-workflows.index') }}"
                        class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Kembali
                    </a>
                </div>
            </div>

            <div class="p-6">
                <form action="{{ route('approval-workflows.store') }}" method="POST" id="workflowForm">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <!-- Nama Workflow -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Workflow <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required
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
                            <input type="text" id="type" name="type" value="{{ old('type') }}" required
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
                                placeholder="Masukkan deskripsi workflow">{{ old('description') }}</textarea>
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
                                @foreach (\App\Models\ItemType::where('is_active', true)->get() as $itemType)
                                    <option value="{{ $itemType->id }}"
                                        {{ old('item_type_id') == $itemType->id ? 'selected' : '' }}>
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
                                <input type="checkbox" name="is_active" value="1"
                                    {{ old('is_active', true) ? 'checked' : '' }}
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
                            <button type="button" onclick="addStep()"
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded flex items-center">
                                <i class="fas fa-plus mr-2"></i>
                                Tambah Step
                            </button>
                        </div>

                        <div id="stepsContainer">
                            <!-- Steps will be added here dynamically -->
                        </div>

                        <div id="noStepsMessage" class="text-center py-8 text-gray-500 hidden">
                            <i class="fas fa-list-ul text-4xl mb-4"></i>
                            <p>Belum ada step dalam workflow ini</p>
                            <p class="text-sm">Klik "Tambah Step" untuk memulai</p>
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
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                            Simpan Workflow
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let stepCounter = 0;
        let steps = [];

        // Initialize with one step
        document.addEventListener('DOMContentLoaded', function() {
            addStep();
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
            const noStepsMessage = document.getElementById('noStepsMessage');

            // Hide no steps message
            if (noStepsMessage) {
                noStepsMessage.classList.add('hidden');
            }

            const stepDiv = document.createElement('div');
            stepDiv.className = 'border border-gray-200 rounded-lg p-6 mb-4 step-item bg-white shadow-sm';
            stepDiv.setAttribute('data-step-id', stepId);
            stepDiv.innerHTML = createStepHTML(stepId, stepNumber);

            container.appendChild(stepDiv);
            updateStepNumbers();

            // Scroll to new step
            stepDiv.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
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

                // Show no steps message if no steps left
                const stepItems = document.querySelectorAll('.step-item');
                const noStepsMessage = document.getElementById('noStepsMessage');
                if (stepItems.length === 0 && noStepsMessage) {
                    noStepsMessage.classList.remove('hidden');
                }
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
                        const newName = name.replace(/workflow_steps\[\d+\]/,
                            `workflow_steps[${stepNumber}]`);
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipe approver</label>
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
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role->display_name ?? 'No Role' }})</option>
                    @endforeach
                </select>
            </div>
            <div id="approver_role_${stepNumber}" class="approver-field" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                <select name="workflow_steps[${stepNumber}][approver_role_id]"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Pilih Role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div id="approver_department_${stepNumber}" class="approver-field" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                <select name="workflow_steps[${stepNumber}][approver_department_id]"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Pilih Department</option>
                    @foreach ($departments as $dept)
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
                // No additional fields needed for requester_department_manager
            } else if (approverType === 'any_department_manager') {
                // No additional fields needed for any_department_manager
            }
        }

        // Form validation before submit
        document.getElementById('workflowForm').addEventListener('submit', function(e) {
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
    </script>
@endsection
