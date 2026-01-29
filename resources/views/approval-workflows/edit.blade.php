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

                    <!-- Sifat Pengadaan (Procurement Type) -->
                    <div>
                        <label for="procurement_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Sifat Pengadaan <span class="text-red-500">*</span>
                        </label>
                        <select id="procurement_type_id" name="procurement_type_id" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('procurement_type_id') border-red-500 @enderror">
                            <option value="">Pilih Sifat Pengadaan</option>
                            @foreach(\App\Models\ProcurementType::where('is_active', true)->get() as $procType)
                                <option value="{{ $procType->id }}" {{ old('procurement_type_id', $approvalWorkflow->procurement_type_id) == $procType->id ? 'selected' : '' }}>
                                    {{ $procType->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('procurement_type_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Placeholder for grid alignment -->
                    <div></div>

                    <!-- Nominal Min -->
                    <div>
                        <label for="nominal_min" class="block text-sm font-medium text-gray-700 mb-2">
                            Nominal Minimum (Rp) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="nominal_min" name="nominal_min" required
                               value="{{ old('nominal_min', $approvalWorkflow->nominal_min ? number_format($approvalWorkflow->nominal_min, 0, ',', '.') : '0') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nominal_min') border-red-500 @enderror"
                               placeholder="0"
                               oninput="this.value = this.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')">
                        @error('nominal_min')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nominal Max -->
                    <div>
                        <label for="nominal_max" class="block text-sm font-medium text-gray-700 mb-2">
                            Nominal Maksimum (Rp)
                        </label>
                        <input type="text" id="nominal_max" name="nominal_max" 
                               value="{{ old('nominal_max', $approvalWorkflow->nominal_max ? number_format($approvalWorkflow->nominal_max, 0, ',', '.') : '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nominal_max') border-red-500 @enderror"
                               placeholder="Kosongkan untuk tidak ada batas"
                               oninput="this.value = this.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')">
                        @error('nominal_max')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Kosongkan jika tidak ada batas maksimum</p>
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
                        @php
                            $editSteps = $approvalWorkflow->steps ?? collect($approvalWorkflow->workflow_steps ?? []);
                        @endphp
                        @foreach($editSteps as $index => $step)
                        @php
                            $stepData = is_object($step) ? $step : (object) $step;
                            $stepName = $stepData->step_name ?? $stepData->name ?? 'Step ' . ($index + 1);
                            $approverType = $stepData->approver_type ?? 'role';
                            $description = $stepData->description ?? '';
                            $requiredAction = $stepData->required_action ?? '';
                            $isConditional = $stepData->is_conditional ?? false;
                            $conditionType = $stepData->condition_type ?? '';
                            $conditionValue = $stepData->condition_value ?? '';
                            $canInsertStep = $stepData->can_insert_step ?? false;
                            $approverId = $stepData->approver_id ?? null;
                            $approverRoleId = $stepData->approver_role_id ?? null;
                            $approverDeptId = $stepData->approver_department_id ?? null;
                            $template = (array) ($stepData->insert_step_template ?? []);
                            $stepType = $stepData->step_type ?? 'approver';
                        @endphp
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
                                    <input type="text" name="workflow_steps[{{ $index + 1 }}][name]" value="{{ $stepName }}" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="Unit Manager Approval">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fase Step</label>
                                    <select name="workflow_steps[{{ $index + 1 }}][step_type]" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="approver" {{ $stepType == 'approver' ? 'selected' : '' }}>Approver (Sebelum Purchasing)</option>
                                        <option value="releaser" {{ $stepType == 'releaser' ? 'selected' : '' }}>Releaser (Setelah Purchasing)</option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Approver: Menyetujui pengadaan.<br>
                                        Releaser: final release.
                                    </p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Approver</label>
                                    <select name="workflow_steps[{{ $index + 1 }}][approver_type]" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            onchange="toggleApproverFields(this, {{ $index + 1 }})">
                                        <option value="">Pilih Tipe Approver</option>
                                        <option value="user" {{ $approverType == 'user' ? 'selected' : '' }}>User Spesifik</option>
                                        <option value="role" {{ $approverType == 'role' ? 'selected' : '' }}>Role</option>
                                        <option value="department_manager" {{ $approverType == 'department_manager' ? 'selected' : '' }}>Manager Department</option>
                                        <option value="requester_department_manager" {{ $approverType == 'requester_department_manager' ? 'selected' : '' }}>Manager Departemen Requester</option>
                                        <option value="any_department_manager" {{ $approverType == 'any_department_manager' ? 'selected' : '' }}>Semua Manager</option>
                                    </select>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Step (opsional)</label>
                                    <textarea name="workflow_steps[{{ $index + 1 }}][description]" rows="2"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="Contoh: Manager unit input harga dan approve">{{ $description }}</textarea>
                                </div>
                                
                                <!-- Required Action (NEW) -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-cog text-blue-600 mr-1"></i>
                                        Required Action (Aksi Khusus)
                                    </label>
                                    <select name="workflow_steps[{{ $index + 1 }}][required_action]"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Tidak ada aksi khusus</option>
                                        <option value="input_price" {{ $requiredAction == 'input_price' ? 'selected' : '' }}>
                                            Input Harga (Manager)
                                        </option>
                                        <option value="verify_budget" {{ $requiredAction == 'verify_budget' ? 'selected' : '' }}>
                                            Verifikasi Budget + Upload FS (Keuangan)
                                        </option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <strong>input_price:</strong> Step ini akan menampilkan form input harga satuan<br>
                                        <strong>verify_budget:</strong> Step ini akan menampilkan upload dokumen FS jika total ≥ threshold
                                    </p>
                                </div>
                                
                                <!-- Conditional Step Settings -->
                                <div class="md:col-span-2 border-t pt-3 mt-2">
                                    <label class="flex items-center mb-3">
                                        <input type="checkbox" name="workflow_steps[{{ $index + 1 }}][is_conditional]" value="1"
                                               {{ $isConditional ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                               onchange="toggleConditionalFields(this, {{ $index + 1 }})">
                                        <span class="ml-2 text-sm font-medium text-gray-700">Step Conditional (skip jika kondisi tidak terpenuhi)</span>
                                    </label>
                                    
                                    <div id="conditional_fields_{{ $index + 1 }}" class="grid grid-cols-2 gap-4 {{ $isConditional ? '' : 'hidden' }}">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Kondisi</label>
                                            <select name="workflow_steps[{{ $index + 1 }}][condition_type]"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option value="">Pilih Kondisi</option>
                                                <option value="total_price" {{ $conditionType == 'total_price' ? 'selected' : '' }}>Total Harga</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Nilai Threshold (Rp)</label>
                                            <input type="text" name="workflow_steps[{{ $index + 1 }}][condition_value]"
                                                   value="{{ $conditionValue ? number_format($conditionValue, 0, ',', '.') : '' }}"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   placeholder="100000000"
                                                   oninput="this.value = this.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')">
                                            <p class="text-xs text-gray-500 mt-1">Step ini akan dijalankan jika total harga >= nilai ini</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Dynamic Step Insertion Permission (NEW) -->
                                <div class="md:col-span-2 border-t pt-3 mt-2">
                                    <label class="flex items-center mb-3">
                                        <input type="checkbox" name="workflow_steps[{{ $index + 1 }}][can_insert_step]" value="1"
                                               {{ $canInsertStep ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-yellow-600 shadow-sm focus:border-yellow-300 focus:ring focus:ring-yellow-200 focus:ring-opacity-50"
                                               onchange="toggleInsertStepTemplate(this, {{ $index + 1 }})">
                                        <span class="ml-2 text-sm font-medium text-gray-700">
                                            <i class="fas fa-plus-circle text-yellow-600 mr-1"></i>
                                            Approver di step ini bisa menambah step baru
                                        </span>
                                    </label>
                                    <p class="text-xs text-gray-500 mb-3 ml-6">
                                        Jika dicentang, approver dapat menambahkan step approval tambahan secara dinamis
                                    </p>
                                    
                                    <!-- Insert Step Template Configuration -->
                                    <div id="insert_step_template_{{ $index + 1 }}" class="{{ $canInsertStep ? '' : 'hidden' }} bg-yellow-50 border border-yellow-200 rounded-lg p-4 space-y-3">
                                        <h4 class="text-sm font-semibold text-gray-900 mb-2">
                                            <i class="fas fa-cog text-yellow-600 mr-1"></i>
                                            Konfigurasi Quick Insert Template
                                        </h4>
                                        <p class="text-xs text-gray-600 mb-3">Template step yang akan ditambahkan (user hanya perlu centang checkbox)</p>
                                        

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div class="md:col-span-2">
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Nama Step Template</label>
                                                <input type="text" name="workflow_steps[{{ $index + 1 }}][insert_step_template][name]"
                                                       value="{{ $template['name'] ?? '' }}"
                                                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                                       placeholder="Contoh: Manager Keuangan - Verifikasi Budget">
                                            </div>
                                            
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Tipe Approver</label>
                                                <select name="workflow_steps[{{ $index + 1 }}][insert_step_template][approver_type]"
                                                        id="template_approver_type_{{ $index + 1 }}"
                                                        onchange="toggleTemplateApproverFields(this, {{ $index + 1 }})"
                                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                                    <option value="">Pilih Tipe...</option>
                                                    <option value="user" {{ isset($template['approver_type']) && $template['approver_type'] == 'user' ? 'selected' : '' }}>User Spesifik</option>
                                                    <option value="role" {{ isset($template['approver_type']) && $template['approver_type'] == 'role' ? 'selected' : '' }}>Role</option>
                                                    <option value="department_manager" {{ isset($template['approver_type']) && $template['approver_type'] == 'department_manager' ? 'selected' : '' }}>Manager Department</option>
                                                    <option value="requester_department_manager" {{ isset($template['approver_type']) && $template['approver_type'] == 'requester_department_manager' ? 'selected' : '' }}>Manager Dept Requester</option>
                                                    <option value="any_department_manager" {{ isset($template['approver_type']) && $template['approver_type'] == 'any_department_manager' ? 'selected' : '' }}>Semua Manager</option>
                                                </select>
                                            </div>
                                            
                                            <div id="template_approver_user_{{ $index + 1 }}" class="{{ isset($template['approver_type']) && $template['approver_type'] == 'user' ? '' : 'hidden' }}">
                                                <label class="block text-xs font-medium text-gray-700 mb-1">User</label>
                                                <select name="workflow_steps[{{ $index + 1 }}][insert_step_template][approver_id]"
                                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                                    <option value="">Pilih User...</option>
                                                    @foreach ($users as $user)
                                                        <option value="{{ $user->id }}" {{ isset($template['approver_id']) && $template['approver_id'] == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            
                                            <div id="template_approver_role_{{ $index + 1 }}" class="{{ isset($template['approver_type']) && $template['approver_type'] == 'role' ? '' : 'hidden' }}">
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Role</label>
                                                <select name="workflow_steps[{{ $index + 1 }}][insert_step_template][approver_role_id]"
                                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                                    <option value="">Pilih Role...</option>
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role->id }}" {{ isset($template['approver_role_id']) && $template['approver_role_id'] == $role->id ? 'selected' : '' }}>{{ $role->display_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            
                                            <div id="template_approver_department_{{ $index + 1 }}" class="{{ isset($template['approver_type']) && $template['approver_type'] == 'department_manager' ? '' : 'hidden' }}">
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Department</label>
                                                <select name="workflow_steps[{{ $index + 1 }}][insert_step_template][approver_department_id]"
                                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                                    <option value="">Pilih Department...</option>
                                                    @foreach ($departments as $dept)
                                                        <option value="{{ $dept->id }}" {{ isset($template['approver_department_id']) && $template['approver_department_id'] == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                                    <i class="fas fa-cog text-blue-600 mr-1"></i>
                                                    Required Action
                                                </label>
                                                <select name="workflow_steps[{{ $index + 1 }}][insert_step_template][required_action]"
                                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                                    <option value="">Tidak ada aksi khusus</option>
                                                    <option value="input_price" {{ isset($template['required_action']) && $template['required_action'] == 'input_price' ? 'selected' : '' }}>
                                                        Input Harga (Manager)
                                                    </option>
                                                    <option value="verify_budget" {{ isset($template['required_action']) && $template['required_action'] == 'verify_budget' ? 'selected' : '' }}>
                                                        Verifikasi Budget + Upload FS
                                                    </option>
                                                </select>
                                                <p class="text-xs text-gray-500 mt-1">Aksi khusus yang diperlukan di step ini</p>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                                    <i class="fas fa-dollar-sign text-green-600 mr-1"></i>
                                                    Threshold FS (Rp)
                                                </label>
                                                <input type="text" name="workflow_steps[{{ $index + 1 }}][insert_step_template][condition_value]"
                                                       value="{{ isset($template['condition_value']) ? number_format($template['condition_value'], 0, ',', '.') : '' }}"
                                                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                                       placeholder="90.000"
                                                       oninput="this.value = this.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')">
                                                <p class="text-xs text-gray-500 mt-1">Upload FS jika total ≥ nilai ini (kosongkan = 100jt default)</p>
                                            </div>
                                            
                                            <div class="md:col-span-2">
                                                <label class="flex items-center">
                                                    <input type="checkbox" name="workflow_steps[{{ $index + 1 }}][insert_step_template][can_insert_step]" value="1"
                                                           {{ isset($template['can_insert_step']) && $template['can_insert_step'] ? 'checked' : '' }}
                                                           class="rounded border-gray-300 text-yellow-600">
                                                    <span class="ml-2 text-xs text-gray-700">Step yang ditambahkan juga bisa insert step lagi (nested)</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="approver_user_{{ $index + 1 }}" class="approver-field" style="display: {{ $approverType == 'user' ? 'block' : 'none' }};">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                                    <select name="workflow_steps[{{ $index + 1 }}][approver_id]"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Pilih User</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ $approverId == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->role->display_name ?? 'No Role' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div id="approver_role_{{ $index + 1 }}" class="approver-field" style="display: {{ $approverType == 'role' ? 'block' : 'none' }};">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                                    <select name="workflow_steps[{{ $index + 1 }}][approver_role_id]"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Pilih Role</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}" {{ $approverRoleId == $role->id ? 'selected' : '' }}>
                                                {{ $role->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div id="approver_department_{{ $index + 1 }}" class="approver-field" style="display: {{ $approverType == 'department_manager' ? 'block' : 'none' }};">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                                    <select name="workflow_steps[{{ $index + 1 }}][approver_department_id]"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Pilih Department</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}" {{ $approverDeptId == $dept->id ? 'selected' : '' }}>
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
let stepCounter = {{ count($approvalWorkflow->steps ?? $approvalWorkflow->workflow_steps ?? []) }};
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Fase Step</label>
                <select name="workflow_steps[${stepNumber}][step_type]" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="approver">Approver (Sebelum Purchasing)</option>
                    <option value="releaser">Releaser (Setelah Purchasing)</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    Approver: Menyetujui pengadaan.<br>
                    Releaser: Final release.
                </p>
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

// Toggle insert step template configuration
function toggleInsertStepTemplate(checkbox, stepNumber) {
    const templateDiv = document.getElementById(`insert_step_template_${stepNumber}`);
    if (checkbox.checked) {
        templateDiv.classList.remove('hidden');
    } else {
        templateDiv.classList.add('hidden');
    }
}

// Toggle template approver fields based on type
function toggleTemplateApproverFields(select, stepNumber) {
    const approverType = select.value;
    const userField = document.getElementById(`template_approver_user_${stepNumber}`);
    const roleField = document.getElementById(`template_approver_role_${stepNumber}`);
    const deptField = document.getElementById(`template_approver_department_${stepNumber}`);
    
    // Hide all
    userField.classList.add('hidden');
    roleField.classList.add('hidden');
    deptField.classList.add('hidden');
    
    // Show relevant field
    if (approverType === 'user') {
        userField.classList.remove('hidden');
    } else if (approverType === 'role') {
        roleField.classList.remove('hidden');
    } else if (approverType === 'department_manager') {
        deptField.classList.remove('hidden');
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
