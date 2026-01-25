@props(['item', 'approvalRequest'])

@php
    // Load steps directly from database to ensure they're available
$itemSteps = \App\Models\ApprovalItemStep::where('approval_request_id', $item->approval_request_id)
    ->where('master_item_id', $item->master_item_id)
    ->orderBy('step_number')
    ->with('approver')
    ->get();

$currentPendingStep = $item->getCurrentPendingStep();
$userId = auth()->id();
$masterItem = $item->masterItem;

// Separate steps by phase
$approvalPhaseSteps = $itemSteps->filter(fn($s) => ($s->step_phase ?? 'approval') === 'approval');
$releasePhaseSteps = $itemSteps->filter(fn($s) => ($s->step_phase ?? 'approval') === 'release');
$hasReleasePhase = $releasePhaseSteps->count() > 0;

// Check current phase of the item
$currentPhase = 'approval'; // default
if ($item->status === 'in_purchasing') {
    $currentPhase = 'purchasing';
} elseif ($item->status === 'in_release') {
    $currentPhase = 'release';
} elseif ($item->status === 'approved') {
    $currentPhase = 'completed';
}

// Is current step a release phase step?
$isReleaseStep = $currentPendingStep && ($currentPendingStep->step_phase ?? 'approval') === 'release';

// Check if any step is rejected - if yes, don't show approval form
    $hasRejectedStep = $itemSteps->contains('status', 'rejected');

    // Check if current step requires price input (based on required_action)
    $needsPriceInput = $currentPendingStep && 
                       $currentPendingStep->required_action == 'input_price' && 
                       ($item->unit_price === null || $item->unit_price <= 0);

    // Check if current step requires CapEx selection
    $needsCapexSelection = $currentPendingStep && $currentPendingStep->required_action == 'select_capex';

    // Check if current step requires FS upload (based on required_action)
    $requiresFsUpload = $currentPendingStep && $currentPendingStep->required_action == 'verify_budget';
    $totalPrice = $item->quantity * ($item->unit_price ?? 0);
    
    // Use step's condition_value as threshold if available, otherwise use global setting
    // Priority: 1. Step's condition_value, 2. Global setting (100jt default)
    $fsThreshold = ($currentPendingStep && $currentPendingStep->condition_value !== null) 
        ? $currentPendingStep->condition_value 
        : \App\Models\Setting::get('fs_threshold_per_item', 100000000);
    
    // Debug logging jangan dihapus
    \Log::info('🔍 Workflow Step Check', [
        'item_id' => $item->id,
        'item_status' => $item->status,
        'current_phase' => $currentPhase,
        'has_release_phase' => $hasReleasePhase,
        'is_release_step' => $isReleaseStep,
        'current_step_number' => $currentPendingStep ? $currentPendingStep->step_number : 'NO_STEP',
        'current_step_name' => $currentPendingStep ? $currentPendingStep->step_name : 'NO_STEP',
        'step_phase' => $currentPendingStep ? ($currentPendingStep->step_phase ?? 'approval') : 'NO_STEP',
        'step_type' => $currentPendingStep ? ($currentPendingStep->step_type ?? 'approver') : 'NO_STEP',
        'scope_process' => $currentPendingStep ? $currentPendingStep->scope_process : 'NO_STEP',
        'required_action' => $currentPendingStep ? $currentPendingStep->required_action : 'NO_STEP',
    ]);
    
    $needsFsUpload = $requiresFsUpload && $totalPrice >= $fsThreshold;

    // Get PurchasingItem for release phase info
    $purchasingItem = null;
    if ($isReleaseStep || $currentPhase === 'purchasing') {
        $purchasingItem = \App\Models\PurchasingItem::where('approval_request_id', $approvalRequest->id)
            ->where('master_item_id', $item->master_item_id)
            ->first();
    }
@endphp

{{-- Phase Indicator for 3-phase workflow --}}
@if($hasReleasePhase)
<div class="mb-3">
    <x-phase-indicator :item="$item" :purchasingItem="$purchasingItem" />
</div>
@endif

@if ($hasRejectedStep)
    <!-- Rejected Notice -->
    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
        <div class="flex items-start gap-2">
            <i class="fas fa-exclamation-circle text-red-600 text-sm mt-0.5"></i>
            <div>
                <p class="text-xs font-semibold text-red-800">Item Ditolak</p>
                <p class="text-[10px] text-red-700 mt-1">
                    Salah satu step approval telah menolak item ini. Approval tidak dapat dilanjutkan.
                </p>
            </div>
        </div>
    </div>
@elseif($itemSteps->count() > 0 && $currentPendingStep && $currentPendingStep->canApprove($userId))
    {{-- Approval Action Card - Simple & Professional --}}
    <div class="bg-white border {{ $isReleaseStep ? 'border-purple-200' : 'border-gray-200' }} rounded-lg p-3 shadow-sm">
        {{-- Header based on phase --}}
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold {{ $isReleaseStep ? 'text-purple-900' : 'text-gray-900' }}">
                @if($isReleaseStep)
                    <i class="fas fa-paper-plane mr-1 text-purple-500"></i>Release Form
                @else
                    <i class="fas fa-check-circle mr-1 text-green-500"></i>Approval Form
                @endif
            </h3>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium
                {{ $isReleaseStep ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                Step {{ $currentPendingStep->step_number }}
            </span>
        </div>

        {{-- Current Step Info --}}
        <div class="bg-gray-50 rounded-md p-2 mb-3 border border-gray-100">
            <div class="flex items-start gap-2">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold
                    {{ $isReleaseStep ? 'bg-purple-500 text-white' : 'bg-blue-500 text-white' }} flex-shrink-0">
                    {{ $currentPendingStep->step_number }}
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-gray-900">{{ $currentPendingStep->step_name }}</p>
                    @if($currentPendingStep->scope_process)
                        <p class="text-[10px] text-gray-600 mt-0.5">
                            <i class="fas fa-tasks mr-1"></i>{{ $currentPendingStep->scope_process }}
                        </p>
                    @endif
                    @if($currentPendingStep->step_type && $currentPendingStep->step_type !== 'approver')
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium mt-1
                            {{ $currentPendingStep->step_type === 'maker' ? 'bg-yellow-100 text-yellow-700' : '' }}
                            {{ $currentPendingStep->step_type === 'releaser' ? 'bg-purple-100 text-purple-700' : '' }}">
                            {{ ucfirst($currentPendingStep->step_type) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Purchasing Info for Release Phase --}}
        @if($isReleaseStep && $purchasingItem)
        <div class="bg-indigo-50 rounded-md p-2 mb-3 border border-indigo-100">
            <h5 class="text-[10px] font-semibold text-indigo-800 uppercase mb-1">
                <i class="fas fa-shopping-cart mr-1"></i>Info Purchasing
            </h5>
            <div class="grid grid-cols-2 gap-2 text-[10px]">
                @if($purchasingItem->preferredVendor)
                <div>
                    <span class="text-indigo-600">Vendor:</span>
                    <span class="font-medium text-indigo-900 ml-1">{{ $purchasingItem->preferredVendor->name }}</span>
                </div>
                @endif
                @if($purchasingItem->preferred_total_price)
                <div>
                    <span class="text-indigo-600">Total:</span>
                    <span class="font-semibold text-green-700 ml-1">Rp {{ number_format($purchasingItem->preferred_total_price, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($purchasingItem->po_number)
                <div class="col-span-2">
                    <span class="text-indigo-600">No. PO:</span>
                    <span class="font-medium text-indigo-900 ml-1">{{ $purchasingItem->po_number }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Hybrid Action Form --}}
        <div x-data="{
            action: 'approve',
            approveRoute: '{{ route('approval.items.approve', [$approvalRequest, $item]) }}',
            rejectRoute: '{{ route('approval.items.reject', [$approvalRequest, $item]) }}',
            pendingRoute: '{{ route('approval.items.setPending', [$approvalRequest, $item]) }}'
        }">
            <form :action="action === 'approve' ? approveRoute : (action === 'reject' ? rejectRoute : pendingRoute)"
                method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf

                {{-- Action Selector --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Pilih Aksi</label>
                    <select x-model="action"
                        class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                        <option value="approve">{{ $isReleaseStep ? '✓ Release' : '✓ Approve' }}</option>
                        <option value="reject">✗ Reject</option>
                        <option value="pending">⟲ Set Pending</option>
                    </select>
                </div>

                <!-- Conditional Fields -->

                <!-- Quick Insert Step Checkbox (if template configured) -->
                @if ($currentPendingStep && $currentPendingStep->insert_step_template)
                    <div x-show="action === 'approve'" x-transition>
                        <label class="flex items-center cursor-pointer my-3">
                            <input type="checkbox" name="quick_insert_step" value="1"
                                class="h-4 w-4 rounded border-gray-300 text-yellow-600 focus:ring-2 focus:ring-yellow-500">
                            <span class="ml-2.5 text-sm text-gray-800 flex items-center">
                                <i class="fas fa-plus-circle text-yellow-600 mr-1.5"></i>
                                {{ $currentPendingStep->insert_step_template['name'] }}
                            </span>
                        </label>
                    </div>
                @endif

                <!-- Manager Step: Price Input (Required) -->
                @if ($needsPriceInput)
                    <div x-show="action === 'approve'" x-transition>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Harga Satuan <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="unit_price" inputmode="numeric" placeholder="0"
                                    :required="action === 'approve'"
                                    class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                                    oninput="this.value = this.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Jumlah</label>
                                <input type="text" value="{{ number_format($item->quantity, 0, ',', '.') }}" readonly
                                    class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 bg-gray-50 text-gray-600">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Total</label>
                                <input type="text" id="total-price-{{ $item->id }}" readonly
                                    class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 bg-gray-50 text-gray-900 font-semibold">
                            </div>
                        </div>

                        <script>
                            // Auto-calculate total price
                            document.querySelector('input[name="unit_price"]').addEventListener('input', function() {
                                const qty = {{ $item->quantity }};
                                const price = parseInt(this.value.replace(/\./g, '')) || 0;
                                const total = qty * price;
                                document.getElementById('total-price-{{ $item->id }}').value = total.toLocaleString('id-ID');
                            });
                        </script>
                    </div>
                @endif

                <!-- Keuangan Step: FS Upload (Conditional) -->
                @if ($needsFsUpload)
                    <div x-show="action === 'approve'" x-transition>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-2 mb-2">
                            <div class="flex items-start gap-1">
                                <i class="fas fa-exclamation-triangle text-yellow-600 text-xs mt-0.5"></i>
                                <p class="text-[10px] text-yellow-800">
                                    <strong>Keuangan:</strong> Total harga Rp {{ number_format($totalPrice, 0, ',', '.') }} ≥ Threshold Rp {{ number_format($fsThreshold, 0, ',', '.') }}. Wajib upload dokumen FS.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Upload Dokumen FS <span class="text-red-500">*</span>
                            </label>
                            <input type="file" name="fs_document" accept=".pdf,.doc,.docx"
                                :required="action === 'approve'"
                                class="w-full text-xs border-2 border-gray-300 rounded-md px-2.5 py-1.5 focus:border-yellow-500 focus:ring-1 focus:ring-yellow-200 transition-colors file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-yellow-600 file:text-white hover:file:bg-yellow-700">
                            <p class="text-[10px] text-gray-500 mt-1">Format: PDF, DOC, DOCX (Max: 5MB)</p>
                        </div>
                    </div>
                @endif



                <!-- Comments -->
                <template x-if="action === 'approve' || action === 'reject'">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Komentar
                        </label>
                        <textarea name="comments" rows="3"
                            :placeholder="action === 'approve' ? 'Tambahkan komentar jika diperlukan...' : 'Komentar tambahan...'"
                            class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors resize-none"></textarea>
                    </div>
                </template>

                <!-- Reject Reason (only for reject) -->
                <template x-if="action === 'reject'">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Alasan Reject <span class="text-red-500">*</span>
                        </label>
                        <textarea name="rejected_reason" rows="3" placeholder="Jelaskan alasan penolakan..." required
                            class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-red-500 focus:ring-2 focus:ring-red-200 transition-colors resize-none"></textarea>
                    </div>
                </template>

                <!-- Reset Reason (only for pending) -->
                <template x-if="action === 'pending'">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Alasan Reset <span class="text-red-500">*</span>
                        </label>
                        <textarea name="reason" rows="3" placeholder="Jelaskan alasan reset ke pending..." required
                            class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition-colors resize-none"></textarea>
                    </div>
                </template>

                <!-- Submit Button -->
                <button type="submit"
                    :onclick="action === 'approve' ? 'return confirm(\'Yakin approve item ini?\')' : (action === 'reject' ?
                        'return confirm(\'Yakin reject item ini?\')' :
                        'return confirm(\'Yakin reset item ke pending?\')')"
                    :class="action === 'approve' ?
                        'bg-green-600 hover:bg-green-700 focus:ring-green-500' :
                        (action === 'reject' ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500' :
                            'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500')"
                    class="w-full text-white font-semibold py-2 px-3 rounded-md text-sm transition-all duration-200 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-1">
                    <span x-show="action === 'approve'">
                        <i class="fas fa-check mr-1.5"></i>Approve
                    </span>
                    <span x-show="action === 'reject'">
                        <i class="fas fa-times mr-1.5"></i>Reject
                    </span>
                    <span x-show="action === 'pending'">
                        <i class="fas fa-redo mr-1.5"></i>Set Pending
                    </span>
                </button>
            </form>
        </div>
    </div>

    <!-- Remove Insert Step Modal - Feature removed for simplicity -->
    @if (false && $currentPendingStep && $currentPendingStep->can_insert_step)
        <div id="insertStepModal{{ $item->id }}"
            class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            onclick="if(event.target === this) closeInsertStepModal{{ $item->id }}()">
            <div class="relative top-10 mx-auto p-4 border w-full max-w-md shadow-lg rounded-lg bg-white"
                onclick="event.stopPropagation()">
                <div class="flex items-center justify-between mb-3 pb-2 border-b">
                    <h3 class="text-base font-bold text-gray-900">Tambah Step Approval</h3>
                    <button type="button" onclick="closeInsertStepModal{{ $item->id }}()"
                        class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                <form action="{{ route('approval-items.insert-step', $item) }}" method="POST" class="space-y-3">
                    @csrf

                    <div class="bg-blue-50 border border-blue-200 rounded-md p-2 text-xs text-blue-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        Step baru akan ditambahkan setelah step saat ini:
                        <strong>{{ $currentPendingStep->step_name }}</strong>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nama Step <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="step_name" required
                            class="w-full text-sm border-2 border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                            placeholder="Contoh: Manager Keuangan - Upload Dokumen">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tipe Approver <span class="text-red-500">*</span>
                        </label>
                        <select name="approver_type" id="approverType{{ $item->id }}" required
                            onchange="toggleApproverFields{{ $item->id }}(this.value)"
                            class="w-full text-sm border-2 border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                            <option value="">Pilih tipe approver...</option>
                            <option value="role">Role</option>
                            <option value="user">User Spesifik</option>
                            <option value="department_manager">Manager Department Tertentu</option>
                            <option value="requester_department_manager">Manager Department Requester</option>
                            <option value="any_department_manager">Semua Manager Department</option>
                        </select>
                    </div>

                    <!-- Dynamic fields based on approver_type -->
                    <div id="roleField{{ $item->id }}" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select name="approver_role_id"
                            class="w-full text-sm border-2 border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                            <option value="">Pilih role...</option>
                            @foreach (\App\Models\Role::orderBy('display_name')->get() as $role)
                                <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="userField{{ $item->id }}" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            User <span class="text-red-500">*</span>
                        </label>
                        <select name="approver_id"
                            class="w-full text-sm border-2 border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                            <option value="">Pilih user...</option>
                            @foreach (\App\Models\User::orderBy('name')->get() as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="deptField{{ $item->id }}" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Department <span class="text-red-500">*</span>
                        </label>
                        <select name="approver_department_id"
                            class="w-full text-sm border-2 border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                            <option value="">Pilih department...</option>
                            @foreach (\App\Models\Department::orderBy('name')->get() as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Alasan Penambahan Step <span class="text-red-500">*</span>
                        </label>
                        <textarea name="insertion_reason" required rows="3"
                            class="w-full text-sm border-2 border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-200 resize-none"
                            placeholder="Jelaskan mengapa step ini diperlukan (minimal 10 karakter)..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Aksi yang Diperlukan <span class="text-gray-500 text-xs">(Opsional)</span>
                        </label>
                        <input type="text" name="required_action"
                            class="w-full text-sm border-2 border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                            placeholder="Contoh: upload_document, price_verification">
                        <p class="text-xs text-gray-500 mt-1">Kode aksi untuk tracking (opsional)</p>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="can_insert_step" value="1"
                            id="canInsertStep{{ $item->id }}"
                            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="canInsertStep{{ $item->id }}" class="ml-2 text-sm text-gray-700">
                            Step ini juga bisa menambah step baru
                        </label>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t">
                        <button type="button" onclick="closeInsertStepModal{{ $item->id }}()"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium px-4 py-2 rounded text-sm transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded text-sm transition-colors">
                            <i class="fas fa-plus-circle mr-1"></i>
                            Tambah Step
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openInsertStepModal{{ $item->id }}() {
                document.getElementById('insertStepModal{{ $item->id }}').classList.remove('hidden');
            }

            function closeInsertStepModal{{ $item->id }}() {
                document.getElementById('insertStepModal{{ $item->id }}').classList.add('hidden');
            }

            function toggleApproverFields{{ $item->id }}(type) {
                const roleField = document.getElementById('roleField{{ $item->id }}');
                const userField = document.getElementById('userField{{ $item->id }}');
                const deptField = document.getElementById('deptField{{ $item->id }}');

                // Hide all
                roleField.classList.add('hidden');
                userField.classList.add('hidden');
                deptField.classList.add('hidden');

                // Show relevant field
                if (type === 'role') roleField.classList.remove('hidden');
                if (type === 'user') userField.classList.remove('hidden');
                if (type === 'department_manager') deptField.classList.remove('hidden');
            }
        </script>
    @endif
@endif
