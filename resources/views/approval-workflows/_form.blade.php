{{-- Reusable Form Component for Approval Workflows --}}
<form action="{{ $action }}" method="POST" id="workflowForm">
    @csrf
    @if(isset($approvalWorkflow))
        @method('PUT')
    @endif

    {{-- Basic Information Section --}}
    <div class="bg-white rounded-none shadow-none p-6 mb-0 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Dasar</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Nama Workflow --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Workflow <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" 
                       value="{{ old('name', $approvalWorkflow->name ?? '') }}" 
                       required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                       placeholder="Contoh: Purchase Request, Leave Request">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tipe Workflow --}}
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                    Tipe Workflow <span class="text-red-500">*</span>
                </label>
                <input type="text" id="type" name="type" 
                       value="{{ old('type', $approvalWorkflow->type ?? '') }}" 
                       required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('type') border-red-500 @enderror"
                       placeholder="Contoh: purchase, leave, expense">
                @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Deskripsi --}}
            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Deskripsi
                </label>
                <textarea id="description" name="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                          placeholder="Masukkan deskripsi workflow">{{ old('description', $approvalWorkflow->description ?? '') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Procurement & Nominal Section --}}
    <div class="bg-white rounded-none shadow-none p-6 mb-0 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Konfigurasi Pengadaan</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Sifat Pengadaan --}}
            <div>
                <label for="procurement_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Sifat Pengadaan <span class="text-red-500">*</span>
                </label>
                <select id="procurement_type_id" name="procurement_type_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('procurement_type_id') border-red-500 @enderror">
                    <option value="">-- Pilih Sifat Pengadaan --</option>
                    @foreach($procurementTypes as $procType)
                        <option value="{{ $procType->id }}" 
                                {{ old('procurement_type_id', $approvalWorkflow->procurement_type_id ?? '') == $procType->id ? 'selected' : '' }}>
                            {{ $procType->name }}
                        </option>
                    @endforeach
                </select>
                @error('procurement_type_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nominal Min --}}
            <div>
                <label for="nominal_min" class="block text-sm font-medium text-gray-700 mb-2">
                    Nominal Minimum (Rp) <span class="text-red-500">*</span>
                </label>
                <input type="text" id="nominal_min" name="nominal_min" required
                       value="{{ old('nominal_min', isset($approvalWorkflow) && $approvalWorkflow->nominal_min ? number_format($approvalWorkflow->nominal_min, 0, ',', '.') : '0') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nominal_min') border-red-500 @enderror"
                       placeholder="0"
                       oninput="formatCurrency(this)">
                @error('nominal_min')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nominal Max --}}
            <div>
                <label for="nominal_max" class="block text-sm font-medium text-gray-700 mb-2">
                    Nominal Maksimum (Rp)
                </label>
                <input type="text" id="nominal_max" name="nominal_max"
                       value="{{ old('nominal_max', isset($approvalWorkflow) && $approvalWorkflow->nominal_max ? number_format($approvalWorkflow->nominal_max, 0, ',', '.') : '') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nominal_max') border-red-500 @enderror"
                       placeholder="Kosongkan untuk tidak ada batas"
                       oninput="formatCurrency(this)">
                @error('nominal_max')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Kosongkan jika tidak ada batas maksimum</p>
            </div>

            {{-- Status --}}
            <div class="flex items-center">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $approvalWorkflow->is_active ?? true) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-700">Workflow Aktif</span>
                </label>
            </div>
        </div>
    </div>

    {{-- Workflow Steps Section --}}
    <div class="bg-white rounded-none shadow-none p-6 mb-0 border-b border-gray-200">
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

        <div id="stepsContainer" class="space-y-4">
            {{-- Steps will be added here --}}
        </div>

        <div id="noStepsMessage" class="text-center py-8 text-gray-500">
            <i class="fas fa-list-ul text-4xl mb-4"></i>
            <p>Belum ada step dalam workflow ini</p>
            <p class="text-sm">Klik "Tambah Step" untuk memulai</p>
        </div>

        @error('workflow_steps')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Form Actions --}}
    <div class="bg-white rounded-none shadow-none p-6 flex justify-end space-x-4 border-t border-gray-200">
        <a href="{{ route('approval-workflows.index') }}"
           class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded">
            Batal
        </a>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
            {{ isset($approvalWorkflow) ? 'Update Workflow' : 'Simpan Workflow' }}
        </button>
    </div>
</form>

<script>
    let stepCounter = 0;
    let steps = [];

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        @if(isset($approvalWorkflow) && $approvalWorkflow->steps)
            loadExistingSteps();
        @else
            addStep();
        @endif
    });

    // Format currency input
    function formatCurrency(input) {
        let value = input.value.replace(/\D/g, '');
        if (value) {
            input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
    }

    // Load existing steps for edit mode
    function loadExistingSteps() {
        const stepsData = @json(isset($approvalWorkflow) ? ($approvalWorkflow->workflow_steps ?? $approvalWorkflow->steps ?? []) : []);
        if (stepsData && stepsData.length > 0) {
            stepsData.forEach((step, index) => {
                stepCounter++;
                steps.push({ id: `step_${stepCounter}`, number: index + 1 });
                addStepToDOM(step, index + 1);
            });
            updateStepNumbers();
        } else {
            addStep();
        }
    }

    // Add new step
    function addStep() {
        stepCounter++;
        const stepNumber = steps.length + 1;
        steps.push({ id: `step_${stepCounter}`, number: stepNumber });
        addStepToDOM(null, stepNumber);
        updateStepNumbers();
    }

    // Add step to DOM
    function addStepToDOM(stepData, stepNumber) {
        const container = document.getElementById('stepsContainer');
        const noStepsMessage = document.getElementById('noStepsMessage');
        
        if (noStepsMessage) noStepsMessage.classList.add('hidden');

        const stepDiv = document.createElement('div');
        stepDiv.className = 'border-b border-gray-200 p-6 step-item bg-white';
        stepDiv.setAttribute('data-step-number', stepNumber);
        stepDiv.innerHTML = createStepHTML(stepData, stepNumber);

        container.appendChild(stepDiv);
    }

    // Create step HTML
    function createStepHTML(stepData, stepNumber) {
        const data = stepData || {};
        const approverType = data.approver_type || '';
        const isConditional = data.is_conditional || false;
        const canInsertStep = data.can_insert_step || false;

        let html = `
            <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-200">
                <h4 class="text-md font-medium text-gray-900 step-number">Step ${stepNumber}</h4>
                <button type="button" onclick="removeStep(this)" class="text-red-600 hover:text-red-800 flex items-center">
                    <i class="fas fa-trash mr-1"></i> Hapus
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Step</label>
                    <input type="text" name="workflow_steps[${stepNumber}][name]" 
                           value="${data.name || data.step_name || ''}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Contoh: Manager Unit Approval">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fase Step</label>
                    <select name="workflow_steps[${stepNumber}][step_type]" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onchange="handleStepTypeChange(this, ${stepNumber})">
                        <option value="approver" ${data.step_type === 'approver' ? 'selected' : ''}>Approver (Sebelum Purchasing)</option>
                        <option value="purchasing" ${data.step_type === 'purchasing' ? 'selected' : ''} style="color:#2563eb;font-weight:600;">🛒 Purchasing (Proses Pembelian)</option>
                        <option value="releaser" ${data.step_type === 'releaser' ? 'selected' : ''}>Releaser (Setelah Purchasing)</option>
                    </select>
                    <p id="purchasing_note_${stepNumber}" class="mt-1 text-xs text-blue-600 ${data.step_type === 'purchasing' ? '' : 'hidden'}">
                        ℹ️ Step ini diproses oleh tim Purchasing (6 langkah: Terima Dok → Benchmarking → Vendor → PO → Invoice → Done)
                    </p>
                </div>

                <div id="approver_type_section_${stepNumber}">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Approver</label>
                    <select name="workflow_steps[${stepNumber}][approver_type]"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            id="approver_type_select_${stepNumber}"
                            onchange="toggleApproverFields(this, ${stepNumber})">
                        <option value="">-- Pilih Tipe Approver --</option>
                        <option value="user" ${approverType === 'user' ? 'selected' : ''}>User Spesifik</option>
                        <option value="role" ${approverType === 'role' ? 'selected' : ''}>Role</option>
                        <option value="department_manager" ${approverType === 'department_manager' ? 'selected' : ''}>Manager Department</option>
                        <option value="requester_department_manager" ${approverType === 'requester_department_manager' ? 'selected' : ''}>Manager Departemen Requester</option>
                        <option value="any_department_manager" ${approverType === 'any_department_manager' ? 'selected' : ''}>Semua Manager</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Step (opsional)</label>
                    <textarea name="workflow_steps[${stepNumber}][description]" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Contoh: Manager unit input harga dan approve">${data.description || ''}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-cog text-blue-600 mr-1"></i>
                        Required Action
                    </label>
                    <select name="workflow_steps[${stepNumber}][required_action]"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Tidak ada aksi khusus</option>
                        <option value="input_price" ${data.required_action === 'input_price' ? 'selected' : ''}>Input Harga (Manager)</option>
                        <option value="verify_budget" ${data.required_action === 'verify_budget' ? 'selected' : ''}>Verifikasi Budget + Upload FS</option>
                    </select>
                </div>

                <div id="approver_user_${stepNumber}" class="approver-field" style="display: ${approverType === 'user' ? 'block' : 'none'};">
                    <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                    <select name="workflow_steps[${stepNumber}][approver_id]"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih User --</option>`;
        
        // Add users dynamically
        const usersData = @json($users);
        usersData.forEach(user => {
            const selected = data.approver_id == user.id ? 'selected' : '';
            const roleName = user.role?.display_name || 'No Role';
            html += `<option value="${user.id}" ${selected}>${user.name} (${roleName})</option>`;
        });
        
        html += `</select>
                </div>

                <div id="approver_role_${stepNumber}" class="approver-field" style="display: ${approverType === 'role' ? 'block' : 'none'};">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <select name="workflow_steps[${stepNumber}][approver_role_id]"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih Role --</option>`;
        
        // Add roles dynamically
        const rolesData = @json($roles);
        rolesData.forEach(role => {
            const selected = data.approver_role_id == role.id ? 'selected' : '';
            html += `<option value="${role.id}" ${selected}>${role.display_name}</option>`;
        });
        
        html += `</select>
                </div>

                <div id="approver_department_${stepNumber}" class="approver-field" style="display: ${approverType === 'department_manager' ? 'block' : 'none'};">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                    <select name="workflow_steps[${stepNumber}][approver_department_id]"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih Department --</option>`;
        
        // Add departments dynamically
        const departmentsData = @json($departments);
        departmentsData.forEach(dept => {
            const selected = data.approver_department_id == dept.id ? 'selected' : '';
            html += `<option value="${dept.id}" ${selected}>${dept.name} (${dept.code})</option>`;
        });
        
        html += `</select>
                </div>

                <div class="md:col-span-2 border-t pt-4 mt-4">
                    <label class="flex items-center mb-3">
                        <input type="checkbox" name="workflow_steps[${stepNumber}][is_conditional]" value="1"
                               ${isConditional ? 'checked' : ''}
                               class="rounded border-gray-300 text-blue-600"
                               onchange="toggleConditionalFields(this, ${stepNumber})">
                        <span class="ml-2 text-sm font-medium text-gray-700">Step Conditional (skip jika kondisi tidak terpenuhi)</span>
                    </label>

                    <div id="conditional_fields_${stepNumber}" class="grid grid-cols-2 gap-4 ${isConditional ? '' : 'hidden'}">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Kondisi</label>
                            <select name="workflow_steps[${stepNumber}][condition_type]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Pilih Kondisi</option>
                                <option value="total_price" ${data.condition_type === 'total_price' ? 'selected' : ''}>Total Harga</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nilai Threshold (Rp)</label>
                            <input type="text" name="workflow_steps[${stepNumber}][condition_value]"
                                   value="${data.condition_value ? data.condition_value.toLocaleString('id-ID') : ''}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="100000000"
                                   oninput="formatCurrency(this)">
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2 border-t pt-4 mt-4">
                    <label class="flex items-center mb-3">
                        <input type="checkbox" name="workflow_steps[${stepNumber}][can_insert_step]" value="1"
                               ${canInsertStep ? 'checked' : ''}
                               class="rounded border-gray-300 text-yellow-600"
                               onchange="toggleInsertStepTemplate(this, ${stepNumber})">
                        <span class="ml-2 text-sm font-medium text-gray-700">
                            <i class="fas fa-plus-circle text-yellow-600 mr-1"></i>
                            Approver bisa menambah step baru
                        </span>
                    </label>

                    <div id="insert_step_template_${stepNumber}" class="${canInsertStep ? '' : 'hidden'} bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-3">
                        <p class="text-xs text-gray-600 mb-3">Konfigurasi template step yang akan ditambahkan</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Nama Step Template</label>
                                <input type="text" name="workflow_steps[${stepNumber}][insert_step_template][name]"
                                       value="${data.insert_step_template?.name || ''}"
                                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                       placeholder="Contoh: Manager Keuangan - Verifikasi Budget">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        return html;
    }

    // Remove step
    function removeStep(button) {
        const stepDiv = button.closest('.step-item');
        if (!confirm('Yakin ingin menghapus step ini?')) return;

        stepDiv.style.transition = 'all 0.3s ease';
        stepDiv.style.opacity = '0';
        stepDiv.style.transform = 'translateX(-100%)';

        setTimeout(() => {
            stepDiv.remove();
            updateStepNumbers();
            checkNoStepsMessage();
        }, 300);
    }

    // Update step numbers
    function updateStepNumbers() {
        const stepItems = document.querySelectorAll('.step-item');
        stepItems.forEach((item, index) => {
            const stepNumber = index + 1;
            const stepNumberElement = item.querySelector('.step-number');
            if (stepNumberElement) {
                stepNumberElement.textContent = `Step ${stepNumber}`;
            }

            const inputs = item.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name && name.includes('workflow_steps[')) {
                    const newName = name.replace(/workflow_steps\[\d+\]/, `workflow_steps[${stepNumber}]`);
                    input.setAttribute('name', newName);
                }
            });

            const elementsWithId = item.querySelectorAll('[id*="_"]');
            elementsWithId.forEach(element => {
                const id = element.getAttribute('id');
                if (id && id.match(/_\d+$/)) {
                    const newId = id.replace(/_\d+$/, `_${stepNumber}`);
                    element.setAttribute('id', newId);
                }
            });
        });
    }

    // Handle step type change (show/hide approver section for purchasing)
    function handleStepTypeChange(select, stepNumber) {
        const stepType = select.value;
        const approverSection = document.getElementById(`approver_type_section_${stepNumber}`);
        const purchasingNote = document.getElementById(`purchasing_note_${stepNumber}`);
        const approverTypeSelect = document.getElementById(`approver_type_select_${stepNumber}`);

        if (stepType === 'purchasing') {
            // Hide approver section — purchasing is handled by purchasing system
            if (approverSection) approverSection.style.display = 'none';
            if (purchasingNote) purchasingNote.classList.remove('hidden');
            // Clear approver_type value so it doesn't conflict
            if (approverTypeSelect) approverTypeSelect.value = '';
            // Hide all approver-specific fields
            document.querySelectorAll(`[id^="approver_"][id$="_${stepNumber}"]`).forEach(f => f.style.display = 'none');
        } else {
            if (approverSection) approverSection.style.display = '';
            if (purchasingNote) purchasingNote.classList.add('hidden');
        }
    }

    // Toggle approver fields
    function toggleApproverFields(select, stepNumber) {
        const approverType = select.value;
        document.querySelectorAll(`[id^="approver_"][id$="_${stepNumber}"]`).forEach(field => {
            field.style.display = 'none';
        });

        if (approverType === 'user') {
            document.getElementById(`approver_user_${stepNumber}`).style.display = 'block';
        } else if (approverType === 'role') {
            document.getElementById(`approver_role_${stepNumber}`).style.display = 'block';
        } else if (approverType === 'department_manager') {
            document.getElementById(`approver_department_${stepNumber}`).style.display = 'block';
        }
    }

    // Toggle conditional fields
    function toggleConditionalFields(checkbox, stepNumber) {
        const conditionalFields = document.getElementById(`conditional_fields_${stepNumber}`);
        if (checkbox.checked) {
            conditionalFields.classList.remove('hidden');
        } else {
            conditionalFields.classList.add('hidden');
        }
    }

    // Toggle insert step template
    function toggleInsertStepTemplate(checkbox, stepNumber) {
        const templateDiv = document.getElementById(`insert_step_template_${stepNumber}`);
        if (checkbox.checked) {
            templateDiv.classList.remove('hidden');
        } else {
            templateDiv.classList.add('hidden');
        }
    }

    // Check if no steps message should be shown
    function checkNoStepsMessage() {
        const stepItems = document.querySelectorAll('.step-item');
        const noStepsMessage = document.getElementById('noStepsMessage');
        if (stepItems.length === 0 && noStepsMessage) {
            noStepsMessage.classList.remove('hidden');
        }
    }

    // Form validation
    document.getElementById('workflowForm').addEventListener('submit', function(e) {
        const stepItems = document.querySelectorAll('.step-item');
        if (stepItems.length === 0) {
            e.preventDefault();
            alert('Minimal harus ada 1 step dalam workflow!');
            return false;
        }

        let isValid = true;
        stepItems.forEach((item) => {
            const nameInput = item.querySelector('input[name*="[name]"]');
            const typeSelect = item.querySelector('select[name*="[approver_type]"]');
            const stepTypeSelect = item.querySelector('select[name*="[step_type]"]');

            if (!nameInput.value.trim()) {
                isValid = false;
                nameInput.classList.add('border-red-500');
            } else {
                nameInput.classList.remove('border-red-500');
            }

            // approver_type is not required for purchasing steps
            const isPurchasingStep = stepTypeSelect && stepTypeSelect.value === 'purchasing';
            if (!isPurchasingStep && typeSelect && !typeSelect.value) {
                isValid = false;
                typeSelect.classList.add('border-red-500');
            } else if (typeSelect) {
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
