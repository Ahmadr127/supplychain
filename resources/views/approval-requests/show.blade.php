@extends('layouts.app')

@section('title', 'Detail Approval Request')

@section('content')
<div class="w-full px-0">
    <div class="bg-white overflow-visible shadow-none rounded-none">
        <!-- Header -->
        <div class="p-2 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $approvalRequest->submissionType->name ?? 'Request' }}</h2>
                    <p class="text-sm text-gray-600">{{ $approvalRequest->request_number }}</p>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('approval-requests.my-requests') }}"
                       class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-3 rounded text-sm">
                        My Requests
                    </a>
                    <a href="{{ route('approval-requests.pending-approvals') }}"
                       class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-3 rounded text-sm">
                        Approval
                    </a>
                    @if(($approvalRequest->status == 'pending' || $approvalRequest->status == 'on progress') && $approvalRequest->requester_id == auth()->id())
                        <a href="{{ route('approval-requests.edit', $approvalRequest) }}" 
                           class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-3 rounded text-sm">
                            Edit
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-2">
            @php
                // Ensure departments map is available even if controller didn't pass it
                if (!isset($departmentsMap)) {
                    $departmentsMap = \App\Models\Department::pluck('name', 'id');
                }
            @endphp
            <div class="grid grid-cols-1 xl:grid-cols-5 gap-3">
                <!-- Main Content -->
                <div class="xl:col-span-4 space-y-4">
                    <!-- Request Info -->
                    <div class="bg-gray-50 rounded-lg p-2">
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Informasi Request</h3>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Status</label>
                                <p class="mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $approvalRequest->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                           ($approvalRequest->status == 'approved' ? 'bg-green-100 text-green-800' : 
                                           ($approvalRequest->status == 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                                        {{ ucfirst($approvalRequest->status) }}
                                    </span>
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Progress</label>
                                <p class="mt-1 text-xs text-gray-900">
                                    Step {{ $approvalRequest->current_step }}/{{ $approvalRequest->total_steps }}
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Workflow</label>
                                <p class="mt-1 text-xs text-gray-900 truncate">{{ $approvalRequest->workflow->name }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Dibuat</label>
                                <p class="mt-1 text-xs text-gray-900">{{ $approvalRequest->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        
                        <!-- Requester and Department removed to avoid duplication with sidebar -->
                        
                        @if($approvalRequest->description)
                        <div class="mt-2">
                            <label class="block text-xs font-medium text-gray-700">Deskripsi</label>
                            <p class="mt-1 text-xs text-gray-900">{{ $approvalRequest->description }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- Items Section -->
                    @if($approvalRequest->masterItems->count() > 0)
                    <div class="bg-gray-50 rounded-lg p-2">
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Item yang Diminta</h3>
                        
                        <div class="space-y-2">
                            @foreach($approvalRequest->masterItems as $item)
                            @php
                                $qty = (int) ($item->pivot->quantity ?? 0);
                                $unitPrice = $item->pivot->unit_price; // nullable, no fallback to master item
                                $totalPrice = $item->pivot->total_price; // nullable, no fallback calculation
                            @endphp
                            <div class="bg-white border border-gray-200 rounded-xl p-2 shadow-sm">
                                <!-- Row 1: header/meta on left, KPIs + brand/vendor on right -->
                                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-2 leading-snug">
                                    <div>
                                        <div class="text-[16px] font-semibold text-gray-900">{{ $item->name }}</div>
                                        <div class="text-xs text-gray-500">Kode: {{ $item->code }}</div>
                                        <div class="text-xs text-gray-400">{{ $item->itemType->name ?? '-' }} @if($item->itemCategory) • {{ $item->itemCategory->name }} @endif</div>
                                    </div>
                                    <div class="grid grid-cols-3 md:grid-cols-7 gap-2 text-right leading-snug">
                                        <div>
                                            <div class="text-xs text-gray-500">Jumlah</div>
                                            <div class="text-base font-medium text-gray-900">{{ $qty }} {{ $item->unit->name ?? '' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500">Harga Satuan</div>
                                            <div class="text-base font-medium text-gray-900">
                                                {{ $unitPrice !== null ? 'Rp '.number_format((float)$unitPrice, 0, ',', '.') : '-' }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500">Total</div>
                                            <div class="text-base font-bold text-gray-900">
                                                {{ $totalPrice !== null ? 'Rp '.number_format((float)$totalPrice, 0, ',', '.') : '-' }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500">Merk</div>
                                            <div class="text-sm text-gray-900 leading-snug">{{ $item->pivot->brand ?? '-' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500">Vendor Alternatif</div>
                                            <div class="text-sm text-gray-900 truncate max-w-[12rem] md:max-w-[10rem] leading-snug">{{ $item->pivot->alternative_vendor ?? '-' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500">No Surat</div>
                                            <div class="text-sm text-gray-900 leading-snug">{{ $item->pivot->letter_number ?? '-' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500">Unit Peruntukan</div>
                                            @php $allocDeptName = $departmentsMap[$item->pivot->allocation_department_id ?? null] ?? '-'; @endphp
                                            <div class="text-sm text-gray-900 leading-snug">{{ $allocDeptName }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="my-2 border-t border-gray-200"></div>

                                <!-- Row 2: Spesifikasi, Catatan, Dokumen Pendukung -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-xs leading-snug">
                                    <div>
                                        <div class="text-xs text-gray-500">Spesifikasi</div>
                                        <div class="text-sm text-gray-900 leading-snug">{{ $item->pivot->specification ?? '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Catatan</div>
                                        <div class="text-sm text-gray-900 leading-snug">{{ $item->pivot->notes ?? '-' }}</div>
                                    </div>
                                    <div>
                                        @php
                                            $filesForItem = isset($itemFiles) ? ($itemFiles->get($item->id) ?? collect()) : collect();
                                        @endphp
                                        <div class="text-xs text-gray-500">Dokumen Pendukung</div>
                                        @if($filesForItem->count())
                                            <ul class="divide-y divide-gray-200 rounded-md border border-gray-200 overflow-hidden">
                                                @foreach($filesForItem as $f)
                                                    <li class="flex items-center justify-between px-2 py-1.5 text-xs bg-gray-50 gap-2">
                                                        <a href="{{ route('approval-requests.view-attachment', $f->id) }}" target="_blank" class="text-blue-700 hover:underline truncate mr-2 max-w-[12rem] md:max-w-[10rem]" title="{{ $f->original_name }}">
                                                            {{ $f->original_name }}
                                                        </a>
                                                        <a href="{{ route('approval-requests.download-attachment', $f->id) }}" class="text-gray-600 hover:text-gray-900 whitespace-nowrap px-2 py-1 rounded border border-gray-300 hover:bg-gray-100">Download</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <div class="text-gray-400">-</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            
                            <!-- Total -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-900">Total Keseluruhan:</span>
                                    <span class="text-sm font-bold text-blue-900">
                                        Rp {{ number_format($approvalRequest->getTotalItemsPrice(), 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Attachments Section removed -->

                </div>

                <!-- Sidebar -->
                <div class="space-y-3">
                    <!-- Requester Info -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Requester</h3>
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-6 w-6 rounded-full bg-blue-500 flex items-center justify-center">
                                    <span class="text-white font-medium text-xs">
                                        {{ substr($approvalRequest->requester->name, 0, 2) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-2">
                                <p class="text-xs font-medium text-gray-900">{{ $approvalRequest->requester->name }}</p>
                                @php
                                    $primaryDepartment = $approvalRequest->requester->departments()->wherePivot('is_primary', true)->first();
                                    $role = $approvalRequest->requester->role;
                                @endphp
                                <p class="text-xs text-gray-500">
                                    {{ $primaryDepartment ? $primaryDepartment->name : 'No Department' }}
                                    @if($role)
                                        • {{ $role->display_name }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Request Status Info -->
                    <div class="bg-white border border-gray-200 rounded-lg p-3">
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Status Request</h3>
                        
                        @if($approvalRequest->status == 'on progress' || $approvalRequest->status == 'pending')
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <div class="flex items-center">
                                <div class="h-3 w-3 rounded-full bg-blue-500 mr-2"></div>
                                <span class="text-sm font-medium text-blue-900">On Progress</span>
                            </div>
                            <p class="text-xs text-blue-700 mt-1">Request sedang dalam proses approval</p>
                        </div>
                        @elseif($approvalRequest->status == 'approved')
                        <div class="bg-green-50 border border-green-200 rounded-lg p-2">
                            <div class="flex items-center">
                                <div class="h-3 w-3 rounded-full bg-green-500 mr-2"></div>
                                <span class="text-sm font-medium text-green-900">Approved</span>
                            </div>
                            <p class="text-xs text-green-700 mt-1">
                                Request telah disetujui oleh {{ $approvalRequest->approver->name ?? 'System' }} 
                                pada {{ $approvalRequest->approved_at->format('d/m/Y H:i') }}
                            </p>
                            @if($approvalRequest->approver)
                            <div class="mt-2">
                                <p class="text-xs text-green-600">
                                    @php
                                        $approverDepartment = $approvalRequest->approver->departments()->wherePivot('is_primary', true)->first();
                                        $approverRole = $approvalRequest->approver->role;
                                    @endphp
                                    {{ $approverDepartment ? $approverDepartment->name : 'No Department' }}
                                    @if($approverRole)
                                        • {{ $approverRole->display_name }}
                                    @endif
                                </p>
                            </div>
                            @endif
                        </div>
                        @elseif($approvalRequest->status == 'rejected')
                        <div class="bg-red-50 border border-red-200 rounded-lg p-2">
                            <div class="flex items-center">
                                <div class="h-3 w-3 rounded-full bg-red-500 mr-2"></div>
                                <span class="text-sm font-medium text-red-900">Rejected</span>
                            </div>
                            <p class="text-xs text-red-700 mt-1">
                                Request ditolak oleh {{ $approvalRequest->approver->name ?? 'System' }} 
                                pada {{ $approvalRequest->approved_at->format('d/m/Y H:i') }}
                            </p>
                            @if($approvalRequest->approver)
                            <div class="mt-2">
                                <p class="text-xs text-red-600">
                                    @php
                                        $approverDepartment = $approvalRequest->approver->departments()->wherePivot('is_primary', true)->first();
                                        $approverRole = $approvalRequest->approver->role;
                                    @endphp
                                    {{ $approverDepartment ? $approverDepartment->name : 'No Department' }}
                                    @if($approverRole)
                                        • {{ $approverRole->display_name }}
                                    @endif
                                </p>
                            </div>
                            @endif
                            @if($approvalRequest->rejection_reason)
                            <div class="mt-2">
                                <label class="block text-xs font-medium text-gray-700">Alasan Penolakan</label>
                                <p class="text-xs text-gray-900 mt-1">{{ $approvalRequest->rejection_reason }}</p>
                            </div>
                            @endif
                        </div>
                        @else
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-2">
                            <div class="flex items-center">
                                <div class="h-3 w-3 rounded-full bg-gray-500 mr-2"></div>
                                <span class="text-sm font-medium text-gray-900">{{ ucfirst($approvalRequest->status) }}</span>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Approval Actions -->
                    @if($approvalRequest->status == 'on progress' || $approvalRequest->status == 'pending')
                        @php
                            $currentStep = $approvalRequest->currentStep;
                            $canApprove = false;
                            
                            if ($currentStep) {
                                $canApprove = $currentStep->canApprove(auth()->id());
                            }
                        @endphp
                        
                        @if($canApprove)
                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                            <h3 class="text-base font-semibold text-gray-900 mb-2">Approval Actions</h3>

                            <!-- Simplified Approval Form -->
                            <form id="approvalForm" class="space-y-4">
                                @csrf
                                
                                <!-- Action Selection -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
                                    <div class="space-y-1.5">
                                        <label class="flex items-center p-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors cursor-pointer">
                                            <input type="radio" name="action" value="approve" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300" required>
                                            <span class="ml-2 text-sm text-gray-900">Approve</span>
                                        </label>
                                        <label class="flex items-center p-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors cursor-pointer">
                                            <input type="radio" name="action" value="reject" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300" required>
                                            <span class="ml-2 text-sm text-gray-900">Reject</span>
                                        </label>
                                        <label class="flex items-center p-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors cursor-pointer">
                                            <input type="radio" name="action" value="pending" class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300" required>
                                            <span class="ml-2 text-sm text-gray-900">Set Pending</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Comments Section (Hidden when reject is selected) -->
                                <div id="comments-section">
                                    <label for="comments" class="block text-sm font-medium text-gray-700 mb-1">Comments</label>
                                    <textarea id="comments" name="comments" rows="3"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                              placeholder="Comments..."></textarea>
                                </div>

                                <!-- Rejection Reason (Hidden by default, shown only when reject is selected) -->
                                <div id="rejection-reason" class="hidden">
                                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-1">
                                        Rejection Reason <span class="text-red-500">*</span>
                                    </label>
                                    <textarea id="rejection_reason" name="rejection_reason" rows="2"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm"
                                              placeholder="Reason for rejection..."></textarea>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex gap-2 pt-1">
                                    <button type="submit" 
                                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded text-sm">
                                        Submit
                                    </button>
                                    <button type="button" 
                                            onclick="resetForm()"
                                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded text-sm">
                                        Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                        @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                            <h3 class="text-base font-semibold text-gray-900 mb-2">Waiting for Approval</h3>
                            <p class="text-sm text-gray-600">Request sedang menunggu approval dari approver yang ditentukan untuk step ini.</p>
                        </div>
                        @endif
                    @endif

                    <!-- Request Actions -->
                    @if(($approvalRequest->status == 'pending' || $approvalRequest->status == 'on progress') && $approvalRequest->requester_id == auth()->id())
                    <div class="bg-white border border-gray-200 rounded-lg p-3">
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Request Actions</h3>
                        <form action="{{ route('approval-requests.cancel', $approvalRequest) }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    class="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-3 rounded text-sm"
                                    onclick="return confirm('Yakin ingin membatalkan request ini?')">
                                <i class="fas fa-ban mr-1"></i>Cancel Request
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Progress Overview - Only for Request Creator -->
            @if($approvalRequest->requester_id == auth()->id())
            <div class="mt-3">
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-3 py-2 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900">Progress Overview</h3>
                    </div>
                    <div class="p-3">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600">
                                Step {{ $approvalRequest->current_step }} dari {{ $approvalRequest->total_steps }}
                            </div>
                            <div class="text-sm font-medium text-gray-900">
                                {{ round(($approvalRequest->current_step / $approvalRequest->total_steps) * 100) }}% Complete
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-3">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                 style="width: {{ ($approvalRequest->current_step / $approvalRequest->total_steps) * 100 }}%"></div>
                        </div>
                        
                        <!-- Workflow Steps -->
                        <div class="space-y-2">
                            @foreach($approvalRequest->workflow->steps as $step)
                                @php
                                    $stepStatus = 'pending';
                                    $stepColor = 'bg-gray-100 text-gray-600';
                                    $stepStatusText = 'Pending';
                                    
                                    if ($approvalRequest->status == 'approved') {
                                        $stepStatus = 'completed';
                                        $stepColor = 'bg-green-100 text-green-800';
                                        $stepStatusText = 'Approved';
                                    } elseif ($approvalRequest->status == 'rejected') {
                                        if ($step->step_number >= $approvalRequest->current_step) {
                                            $stepColor = 'bg-red-100 text-red-800';
                                            $stepStatusText = 'Rejected';
                                        } else {
                                            $stepColor = 'bg-green-100 text-green-800';
                                            $stepStatusText = 'Approved';
                                        }
                                    } else {
                                        if ($step->step_number < $approvalRequest->current_step) {
                                            $stepStatus = 'completed';
                                            $stepColor = 'bg-green-100 text-green-800';
                                            $stepStatusText = 'Approved';
                                        } elseif ($step->step_number == $approvalRequest->current_step) {
                                            $stepStatus = 'current';
                                            $stepColor = 'bg-blue-100 text-blue-800';
                                            $stepStatusText = 'On Progress';
                                        }
                                    }
                                @endphp
                                <div class="flex items-center justify-between p-2 border border-gray-200 rounded-lg {{ $stepColor }}">
                                    <div class="flex items-center">
                                        <div class="h-2 w-2 rounded-full mr-2 {{ $stepStatus == 'completed' ? 'bg-green-500' : ($stepStatus == 'current' ? 'bg-blue-500' : 'bg-gray-300') }}"></div>
                                        <span class="text-sm font-medium">{{ $step->step_name }}</span>
                                    </div>
                                    <span class="text-xs font-medium">{{ $stepStatusText }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const approvalForm = document.getElementById('approvalForm');
    const commentsSection = document.getElementById('comments-section');
    const rejectionReasonDiv = document.getElementById('rejection-reason');
    const rejectionReasonTextarea = document.getElementById('rejection_reason');
    const actionRadios = document.querySelectorAll('input[name="action"]');
    
    if (approvalForm) {
        // Show/hide fields based on action selection and handle styling
        actionRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                // Remove active styling from all labels
                actionRadios.forEach(r => {
                    const label = r.closest('label');
                    label.classList.remove('bg-green-50', 'border-green-300', 'bg-red-50', 'border-red-300', 'bg-yellow-50', 'border-yellow-300');
                    label.classList.add('border-gray-200');
                });
                
                // Add active styling to selected label
                const selectedLabel = this.closest('label');
                selectedLabel.classList.remove('border-gray-200');
                
                if (this.value === 'approve') {
                    selectedLabel.classList.add('bg-green-50', 'border-green-300');
                } else if (this.value === 'reject') {
                    selectedLabel.classList.add('bg-red-50', 'border-red-300');
                } else if (this.value === 'pending') {
                    selectedLabel.classList.add('bg-yellow-50', 'border-yellow-300');
                }
                
                // Handle field visibility based on action
                if (this.value === 'reject') {
                    // Show only rejection reason, hide comments
                    commentsSection.classList.add('hidden');
                    rejectionReasonDiv.classList.remove('hidden');
                    rejectionReasonTextarea.required = true;
                } else {
                    // Show comments, hide rejection reason
                    commentsSection.classList.remove('hidden');
                    rejectionReasonDiv.classList.add('hidden');
                    rejectionReasonTextarea.required = false;
                    rejectionReasonTextarea.value = '';
                }
            });
        });

        // Handle form submission
        approvalForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = formData.get('action');
            const requestId = {{ $approvalRequest->id }};
            const stepNumber = {{ $approvalRequest->current_step }};
            
            // Validate rejection reason if reject is selected
            if (action === 'reject' && !formData.get('rejection_reason').trim()) {
                alert('Please provide a rejection reason.');
                rejectionReasonTextarea.focus();
                return;
            }
            
            // Show confirmation dialog
            let confirmMessage = '';
            switch(action) {
                case 'approve':
                    confirmMessage = 'Are you sure you want to approve this request?';
                    break;
                case 'reject':
                    confirmMessage = 'Are you sure you want to reject this request?';
                    break;
                case 'pending':
                    confirmMessage = 'Are you sure you want to set this request as pending?';
                    break;
            }
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Processing...';
            submitBtn.disabled = true;
            
            // Prepare data for API
            const apiData = new FormData();
            
            // Map action values to controller expected values
            const statusMap = {
                'approve': 'approved',
                'reject': 'rejected',
                'pending': 'pending'
            };
            
            apiData.append('status', statusMap[action]);
            apiData.append('comments', formData.get('comments') || '');
            if (action === 'reject') {
                apiData.append('rejection_reason', formData.get('rejection_reason'));
            }
            
            // Get CSRF token from the form or meta tag
            let csrfToken = formData.get('_token');
            if (!csrfToken) {
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    csrfToken = metaTag.getAttribute('content');
                }
            }
            if (csrfToken) {
                apiData.append('_token', csrfToken);
            }
            
            // Debug logging
            console.log('Sending data:', {
                action: action,
                status: statusMap[action],
                comments: formData.get('comments'),
                rejection_reason: formData.get('rejection_reason'),
                csrfToken: csrfToken
            });
            
            fetch(`/api/approval-steps/${requestId}/${stepNumber}/update-status`, {
                method: 'POST',
                body: apiData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json().then(data => {
                    console.log('Response data:', data);
                    
                    if (!response.ok) {
                        // Handle validation errors
                        if (response.status === 422 && data.errors) {
                            let errorMessage = 'Validation errors:\n';
                            for (const field in data.errors) {
                                errorMessage += `${field}: ${data.errors[field].join(', ')}\n`;
                            }
                            alert(errorMessage);
                        } else {
                            alert(`HTTP error! status: ${response.status}\nError: ${data.error || 'Unknown error'}`);
                        }
                        // Restore button state
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        return;
                    }
                    
                    if (data.success) {
                        // Show success message
                        const successMessages = {
                            'approve': 'Request has been approved successfully!',
                            'reject': 'Request has been rejected successfully!',
                            'pending': 'Request status has been updated to pending!'
                        };
                        alert(successMessages[action] || 'Action completed successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Failed to update status'));
                        // Restore button state
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                });
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your request');
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
});

// Reset form function
function resetForm() {
    const form = document.getElementById('approvalForm');
    if (form) {
        form.reset();
        
        // Reset field visibility
        document.getElementById('comments-section').classList.remove('hidden');
        document.getElementById('rejection-reason').classList.add('hidden');
        document.getElementById('rejection_reason').required = false;
        document.getElementById('rejection_reason').value = '';
        
        // Reset radio button styling
        const actionRadios = document.querySelectorAll('input[name="action"]');
        actionRadios.forEach(radio => {
            const label = radio.closest('label');
            label.classList.remove('bg-green-50', 'border-green-300', 'bg-red-50', 'border-red-300', 'bg-yellow-50', 'border-yellow-300');
            label.classList.add('border-gray-200');
        });
    }
}
</script>
@endsection
