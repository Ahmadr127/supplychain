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
    
    // Check if current step is Manager (step 1) and needs price input
    $isManagerStep = $currentPendingStep && $currentPendingStep->step_number == 1;
    $needsPriceInput = $isManagerStep && ($item->unit_price === null || $item->unit_price <= 0);
    
    // Check if current step is Keuangan (step 2) and needs FS upload
    $isKeuanganStep = $currentPendingStep && $currentPendingStep->step_number == 2;
    $totalPrice = $item->quantity * ($item->unit_price ?? 0);
    $needsFsUpload = $isKeuanganStep && $totalPrice >= 100000000;
@endphp

@if($itemSteps->count() > 0 && $currentPendingStep && $currentPendingStep->canApprove($userId))
<!-- Approval Action Card -->
<div class="bg-white border border-gray-200 rounded-lg p-2 shadow-sm">
    <!-- Workflow Progress -->
    <div class="mb-4">
        <div class="flex items-center gap-1 flex-wrap">
            @foreach($itemSteps as $step)
                <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-medium
                    {{ $step->status == 'approved' ? 'bg-green-100 text-green-800' : 
                       ($step->status == 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700') }}">
                    @if($step->status == 'approved')
                        <i class="fas fa-check mr-0.5"></i>
                    @elseif($step->status == 'rejected')
                        <i class="fas fa-times mr-0.5"></i>
                    @else
                        <i class="fas fa-circle mr-0.5 text-[6px]"></i>
                    @endif
                    {{ $step->step_name }}
                </span>
                @if(!$loop->last)
                    <i class="fas fa-chevron-right text-gray-400 text-[8px]"></i>
                @endif
            @endforeach
        </div>
    </div>
    
    <!-- Hybrid Action Form -->
    <div x-data="{ 
        action: 'approve',
        approveRoute: '{{ route('approval.items.approve', [$approvalRequest, $item]) }}',
        rejectRoute: '{{ route('approval.items.reject', [$approvalRequest, $item]) }}',
        pendingRoute: '{{ route('approval.items.setPending', [$approvalRequest, $item]) }}'
    }">
        <form :action="action === 'approve' ? approveRoute : (action === 'reject' ? rejectRoute : pendingRoute)" method="POST" enctype="multipart/form-data" class="space-y-2.5">
            @csrf
            
            <!-- Action Selector -->
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Pilih Aksi</label>
                <select x-model="action" 
                        class="w-full text-sm border-2 border-gray-300 rounded-md px-2.5 py-1.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-colors">
                    <option value="approve">✓ Approve</option>
                    <option value="reject">✗ Reject</option>
                    <option value="pending">⟲ Set Pending</option>
                </select>
            </div>
            
            <!-- Conditional Fields -->
            
            <!-- Manager Step: Price Input (Required) -->
            @if($needsPriceInput)
            <div x-show="action === 'approve'" x-transition>
                <div class="bg-blue-50 border border-blue-200 rounded-md p-2 mb-2">
                    <div class="flex items-start gap-1">
                        <i class="fas fa-info-circle text-blue-600 text-xs mt-0.5"></i>
                        <p class="text-[10px] text-blue-800">
                            <strong>Manager:</strong> Anda perlu menginput harga satuan untuk item ini sebelum approve.
                        </p>
                    </div>
                </div>
                
                <div class="grid grid-cols-3 gap-2 mb-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Harga Satuan <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="unit_price" 
                               inputmode="numeric"
                               placeholder="0"
                               :required="action === 'approve'"
                               class="w-full text-xs border-2 border-gray-300 rounded-md px-2.5 py-1.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-colors"
                               oninput="this.value = this.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Jumlah</label>
                        <input type="text" 
                               value="{{ number_format($item->quantity, 0, ',', '.') }}"
                               readonly
                               class="w-full text-xs border-2 border-gray-200 rounded-md px-2.5 py-1.5 bg-gray-50 text-gray-600">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Total</label>
                        <input type="text" 
                               id="total-price-{{ $item->id }}"
                               readonly
                               class="w-full text-xs border-2 border-gray-200 rounded-md px-2.5 py-1.5 bg-gray-50 text-gray-600 font-semibold">
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
            @if($needsFsUpload)
            <div x-show="action === 'approve'" x-transition>
                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-2 mb-2">
                    <div class="flex items-start gap-1">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-xs mt-0.5"></i>
                        <p class="text-[10px] text-yellow-800">
                            <strong>Keuangan:</strong> Total harga ≥ Rp 100.000.000. Wajib upload dokumen FS.
                        </p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Upload Dokumen FS <span class="text-red-500">*</span>
                    </label>
                    <input type="file" 
                           name="fs_document" 
                           accept=".pdf,.doc,.docx"
                           :required="action === 'approve'"
                           class="w-full text-xs border-2 border-gray-300 rounded-md px-2.5 py-1.5 focus:border-yellow-500 focus:ring-1 focus:ring-yellow-200 transition-colors file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-yellow-600 file:text-white hover:file:bg-yellow-700">
                    <p class="text-[10px] text-gray-500 mt-1">Format: PDF, DOC, DOCX (Max: 5MB)</p>
                </div>
            </div>
            @endif
            
            <div x-show="action === 'reject'" x-transition>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    Alasan Reject <span class="text-red-500">*</span>
                </label>
                <textarea name="rejected_reason" 
                          rows="2" 
                          placeholder="Jelaskan alasan penolakan..."
                          :required="action === 'reject'"
                          class="w-full text-xs border-2 border-gray-300 rounded-md px-2.5 py-1.5 focus:border-red-500 focus:ring-1 focus:ring-red-200 transition-colors resize-none"></textarea>
            </div>
            
            <div x-show="action === 'approve'" x-transition>
                <label class="block text-xs font-medium text-gray-700 mb-1">Komentar (opsional)</label>
                <textarea name="comments" 
                          rows="2" 
                          placeholder="Tambahkan komentar jika diperlukan..."
                          class="w-full text-xs border-2 border-gray-300 rounded-md px-2.5 py-1.5 focus:border-green-500 focus:ring-1 focus:ring-green-200 transition-colors resize-none"></textarea>
            </div>
            
            <div x-show="action === 'reject'" x-transition>
                <label class="block text-xs font-medium text-gray-700 mb-1">Komentar Tambahan (opsional)</label>
                <textarea name="comments" 
                          rows="2" 
                          placeholder="Komentar tambahan..."
                          class="w-full text-xs border-2 border-gray-300 rounded-md px-2.5 py-1.5 focus:border-red-500 focus:ring-1 focus:ring-red-200 transition-colors resize-none"></textarea>
            </div>
            
            <div x-show="action === 'pending'" x-transition>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    Alasan Reset <span class="text-red-500">*</span>
                </label>
                <textarea name="reason" 
                          rows="2" 
                          placeholder="Jelaskan alasan reset ke pending..."
                          :required="action === 'pending'"
                          class="w-full text-xs border-2 border-gray-300 rounded-md px-2.5 py-1.5 focus:border-yellow-500 focus:ring-1 focus:ring-yellow-200 transition-colors resize-none"></textarea>
            </div>
            
            <!-- Submit Button -->
            <button type="submit" 
                    :onclick="action === 'approve' ? 'return confirm(\'Yakin approve item ini?\')' : (action === 'reject' ? 'return confirm(\'Yakin reject item ini?\')' : 'return confirm(\'Yakin reset item ke pending?\')')"
                    :class="action === 'approve' ? 
                        'bg-green-600 hover:bg-green-700 focus:ring-green-500' : 
                        (action === 'reject' ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500' : 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500')"
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
@endif
