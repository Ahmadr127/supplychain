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

    // Data for searchable selects
    const usersOptions = @json($users->map(fn($u) => ['id' => $u->id, 'label' => $u->name . ' (' . ($u->role->display_name ?? 'No Role') . ')']));
    const rolesOptions = @json($roles->map(fn($r) => ['id' => $r->id, 'label' => $r->display_name]));
    const departmentsOptions = @json($departments->map(fn($d) => ['id' => $d->id, 'label' => $d->name . ' (' . $d->code . ')']));

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

    // Helper to render searchable select in JS
    function renderSearchableSelect(name, options, selectedId, placeholder = 'Pilih...') {
        const found = options.find(o => o.id == selectedId);
        const selectedLabel = found ? found.label : placeholder;
        const optionsJson = JSON.stringify(options).replace(/"/g, '&quot;');
        
        return `
            <div x-data="{
                open: false,
                search: '',
                selectedId: '${selectedId}',
                selectedLabel: '${selectedLabel}',
                options: ${optionsJson},
                get filtered() {
                    if (!this.search) return this.options;
                    const q = this.search.toLowerCase();
                    return this.options.filter(o => o.label.toLowerCase().includes(q));
                },
                select(id, label) {
                    this.selectedId = id;
                    this.selectedLabel = label;
                    this.open = false;
                    this.search = '';
                }
            }" @click.outside="open = false" class="relative">
                <input type="hidden" name="${name}" :value="selectedId">
                <button type="button" @click="open = !open" 
                    class="w-full flex items-center justify-between px-3 py-2 text-sm border border-gray-300 rounded-md bg-white hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                    <span class="truncate text-gray-700" x-text="selectedLabel"></span>
                    <i class="fas fa-chevron-down text-gray-400 text-xs ml-2 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="open" style="display:none;" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg">
                    <div class="p-2 border-b border-gray-100">
                        <div class="relative">
                            <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text" x-model="search" placeholder="Cari..." x-ref="searchInput"
                                @keydown.escape="open = false" x-effect="if(open) $nextTick(() => $refs.searchInput.focus())"
                                class="w-full pl-7 pr-3 py-1.5 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>
                    <ul class="max-h-56 overflow-y-auto py-1">
                        <li @click="select('', '${placeholder}')" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 flex items-center gap-2" :class="selectedId === '' ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'">
                            <i class="fas fa-check text-blue-600 text-xs w-3" x-show="selectedId === ''"></i>
                            <span :class="selectedId === '' ? '' : 'ml-5'">${placeholder}</span>
                        </li>
                        <template x-for="opt in filtered" :key="opt.id">
                            <li @click="select(opt.id, opt.label)" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 flex items-center gap-2" :class="selectedId == opt.id ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'">
                                <i class="fas fa-check text-blue-600 text-xs w-3" x-show="selectedId == opt.id"></i>
                                <span :class="selectedId == opt.id ? '' : 'ml-5'" x-text="opt.label"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        `;
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
        const canInsertStep = data.can_insert_step || false;

        const userSelect = renderSearchableSelect(`workflow_steps[${stepNumber}][approver_id]`, usersOptions, data.approver_id || '', '-- Pilih User --');
        const roleSelect = renderSearchableSelect(`workflow_steps[${stepNumber}][approver_role_id]`, rolesOptions, data.approver_role_id || '', '-- Pilih Role --');
        const deptSelect = renderSearchableSelect(`workflow_steps[${stepNumber}][approver_department_id]`, departmentsOptions, data.approver_department_id || '', '-- Pilih Department --');

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
                    ${userSelect}
                </div>

                <div id="approver_role_${stepNumber}" class="approver-field" style="display: ${approverType === 'role' ? 'block' : 'none'};">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    ${roleSelect}
                </div>

                <div id="approver_department_${stepNumber}" class="approver-field" style="display: ${approverType === 'department_manager' ? 'block' : 'none'};">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                    ${deptSelect}
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
        // Hide only detail fields, keep "Tipe Approver" dropdown visible.
        document.querySelectorAll(`.approver-field[id$="_${stepNumber}"]`).forEach(field => {
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
            if (!isPurchasingStep && typeSelect) {
                if (!typeSelect.value) {
                    isValid = false;
                    typeSelect.classList.add('border-red-500');
                } else {
                    typeSelect.classList.remove('border-red-500');
                    
                    // Validate the specific ID input
                    const selectedType = typeSelect.value;
                    let idInput = null;
                    if (selectedType === 'user') {
                        idInput = item.querySelector('input[name*="[approver_id]"]');
                    } else if (selectedType === 'role') {
                        idInput = item.querySelector('input[name*="[approver_role_id]"]');
                    } else if (selectedType === 'department_manager') {
                        idInput = item.querySelector('input[name*="[approver_department_id]"]');
                    }
                    
                    if (idInput) {
                        const button = idInput.nextElementSibling;
                        if (!idInput.value) {
                            isValid = false;
                            if (button && button.tagName === 'BUTTON') {
                                button.classList.add('border-red-500');
                            }
                        } else {
                            if (button && button.tagName === 'BUTTON') {
                                button.classList.remove('border-red-500');
                            }
                        }
                    }
                }
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Mohon lengkapi semua field yang wajib diisi!');
            return false;
        }
    });
</script>

{{-- Purchasing Step Config Section --}}
@php
    $availableSteps = \App\Models\ApprovalWorkflow::PURCHASING_STEP_KEYS;
    $stepLabels     = \App\Models\ApprovalWorkflow::PURCHASING_STEP_LABELS;
    $existingConfig = $approvalWorkflow->purchasing_step_config ?? null;
    $configByKey = collect($existingConfig)->keyBy('step_key');
@endphp

<div class="bg-white rounded-none shadow-none p-6 border-t border-gray-200" id="purchasing-config-section">
    <h3 class="text-lg font-semibold text-gray-900 mb-1 flex items-center gap-2">
        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        Konfigurasi Langkah Purchasing
    </h3>
    <p class="text-sm text-gray-500 mb-4">
        Pilih langkah purchasing yang aktif untuk workflow ini. Jika tidak ada yang dipilih (semua dinonaktifkan), sistem akan menggunakan semua 5 langkah default.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($availableSteps as $idx => $stepKey)
            @php
                $cfg       = $configByKey->get($stepKey);
                $enabled   = $cfg ? (bool) ($cfg['enabled'] ?? true) : true;
                $allowSkip = $cfg ? (bool) ($cfg['allow_skip'] ?? ($stepKey === 'trial')) : ($stepKey === 'trial');
                $label     = $stepLabels[$stepKey] ?? $stepKey;
            @endphp
            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50 hover:bg-blue-50 hover:border-blue-200 transition-colors">
                <div class="flex items-start justify-between mb-2">
                    <span class="text-sm font-semibold text-gray-700">{{ ($idx + 1) }}. {{ $label }}</span>
                    <span class="text-xs px-1.5 py-0.5 bg-gray-200 text-gray-600 rounded font-mono">{{ $stepKey }}</span>
                </div>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               name="purchasing_step_config[{{ $stepKey }}][enabled]"
                               value="1"
                               class="w-4 h-4 rounded border-gray-300 text-blue-600"
                               {{ $enabled ? 'checked' : '' }}
                               id="psc_enabled_{{ $stepKey }}" />
                        <span class="text-sm text-gray-700">Aktifkan step ini</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               name="purchasing_step_config[{{ $stepKey }}][allow_skip]"
                               value="1"
                               class="w-4 h-4 rounded border-gray-300 text-amber-500"
                               {{ $allowSkip ? 'checked' : '' }}
                               id="psc_skip_{{ $stepKey }}" />
                        <span class="text-sm text-gray-600">Boleh dilewati</span>
                    </label>
                </div>
            </div>
        @endforeach
    </div>
    <p class="text-xs text-gray-400 mt-3">
        ℹ️ Urutan langkah mengikuti urutan standar di atas. Step yang dinonaktifkan akan dianggap selesai secara otomatis (kondisi skip).
    </p>
</div>
