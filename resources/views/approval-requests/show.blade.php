@extends('layouts.app')

@section('title', 'Detail Approval Request')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <!-- Header -->
        <div class="p-4 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $approvalRequest->submissionType->name ?? 'Request' }}</h2>
                    <p class="text-sm text-gray-600">{{ $approvalRequest->request_number }}</p>
                </div>
                <div class="flex space-x-2">
                    @php
                        $referer = request()->header('referer');
                        $backUrl = route('dashboard');
                        $backText = 'Kembali ke Dashboard';
                        
                        if ($referer) {
                            if (str_contains($referer, '/pending-approvals')) {
                                $backUrl = route('approval-requests.pending-approvals');
                                $backText = 'Kembali ke Approval';
                            } elseif (str_contains($referer, '/my-requests')) {
                                $backUrl = route('approval-requests.my-requests');
                                $backText = 'Kembali ke My Requests';
                            }
                        } else {
                            // Fallback based on permissions if no referer
                            if (auth()->user()->hasPermission('view_my_approvals')) {
                                $backUrl = route('approval-requests.my-requests');
                                $backText = 'Kembali ke My Requests';
                            } elseif (auth()->user()->hasPermission('approval')) {
                                $backUrl = route('approval-requests.pending-approvals');
                                $backText = 'Kembali ke Approval';
                            }
                        }
                    @endphp
                    
                    <a href="{{ $backUrl }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-3 rounded text-sm">
                        {{ $backText }}
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

        <div class="p-4">
            <div class="grid grid-cols-1 xl:grid-cols-4 gap-4">
                <!-- Main Content -->
                <div class="xl:col-span-3 space-y-4">
                    <!-- Request Info -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3">Informasi Request</h3>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
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
                        
                        <!-- Requester Details -->
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Requester</label>
                                <p class="mt-1 text-xs text-gray-900">{{ $approvalRequest->requester->name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Department & Position</label>
                                <p class="mt-1 text-xs text-gray-900">
                                    @php
                                        $primaryDepartment = $approvalRequest->requester->departments()->wherePivot('is_primary', true)->first();
                                        $role = $approvalRequest->requester->role;
                                    @endphp
                                    {{ $primaryDepartment ? $primaryDepartment->name : 'No Department' }}
                                    @if($role)
                                        • {{ $role->display_name }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        
                        @if($approvalRequest->description)
                        <div class="mt-3">
                            <label class="block text-xs font-medium text-gray-700">Deskripsi</label>
                            <p class="mt-1 text-xs text-gray-900">{{ $approvalRequest->description }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- Items Section -->
                    @if($approvalRequest->masterItems->count() > 0)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3">Item yang Diminta</h3>
                        
                        <div class="space-y-2">
                            @foreach($approvalRequest->masterItems as $item)
                            <div class="bg-white border border-gray-200 rounded-lg p-3">
                                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                                    <div class="md:col-span-2">
                                        <div class="text-xs font-medium text-gray-900">{{ $item->name }}</div>
                                        <div class="text-xs text-gray-500">Kode: {{ $item->code }}</div>
                                        <div class="text-xs text-gray-400">{{ $item->itemType->name }} - {{ $item->itemCategory->name }}</div>
                                    </div>
                                    <div class="text-xs">
                                        <div class="text-gray-500">Jumlah</div>
                                        <div class="font-medium">{{ $item->pivot->quantity }} {{ $item->unit->name }}</div>
                                    </div>
                                    <div class="text-xs">
                                        <div class="text-gray-500">Harga Satuan</div>
                                        <div class="font-medium">Rp {{ number_format($item->pivot->unit_price, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="text-xs">
                                        <div class="text-gray-500">Total</div>
                                        <div class="font-bold">Rp {{ number_format($item->pivot->total_price, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                @if($item->pivot->notes)
                                <div class="mt-2 text-xs text-gray-500">
                                    <span class="font-medium">Catatan:</span> {{ $item->pivot->notes }}
                                </div>
                                @endif
                            </div>
                            @endforeach
                            
                            <!-- Total -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
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

                    <!-- Attachments Section -->
                    @if($approvalRequest->attachments->count() > 0)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3">Lampiran File</h3>
                        
                        <div class="space-y-2">
                            @foreach($approvalRequest->attachments as $attachment)
                            <div class="flex items-center justify-between bg-white border border-gray-200 rounded-lg p-2">
                                <div class="flex items-center space-x-2 flex-1 min-w-0">
                                    <div class="flex-shrink-0">
                                        <svg class="h-4 w-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-gray-900 truncate">{{ $attachment->original_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $attachment->human_file_size }}</p>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 ml-2 flex space-x-1">
                                    @if($attachment->mime_type === 'application/pdf')
                                    <a href="{{ route('approval-requests.view-attachment', $attachment) }}" 
                                       target="_blank"
                                       class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-green-700 bg-green-100 hover:bg-green-200"
                                       title="Lihat PDF">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        View
                                    </a>
                                    @endif
                                    <a href="{{ route('approval-requests.download-attachment', $attachment) }}" 
                                       class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200"
                                       title="Download File">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                        Download
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                </div>

                <!-- Sidebar -->
                <div class="space-y-4">
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
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3">Status Request</h3>
                        
                        @if($approvalRequest->status == 'on progress' || $approvalRequest->status == 'pending')
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <div class="flex items-center">
                                <div class="h-3 w-3 rounded-full bg-blue-500 mr-2"></div>
                                <span class="text-sm font-medium text-blue-900">On Progress</span>
                            </div>
                            <p class="text-xs text-blue-700 mt-1">Request sedang dalam proses approval</p>
                        </div>
                        @elseif($approvalRequest->status == 'approved')
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3">
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
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3">
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
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
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
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h3 class="text-base font-semibold text-gray-900 mb-4">Approval Actions</h3>

                            <!-- Simplified Approval Form -->
                            <form id="approvalForm" class="space-y-4">
                                @csrf
                                
                                <!-- Action Selection -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
                                    <div class="space-y-2">
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
                                <div class="flex gap-2 pt-2">
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
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <h3 class="text-base font-semibold text-gray-900 mb-2">Waiting for Approval</h3>
                            <p class="text-sm text-gray-600">Request sedang menunggu approval dari approver yang ditentukan untuk step ini.</p>
                        </div>
                        @endif
                    @endif

                    <!-- Request Actions -->
                    @if(($approvalRequest->status == 'pending' || $approvalRequest->status == 'on progress') && $approvalRequest->requester_id == auth()->id())
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3">Request Actions</h3>
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
            <div class="mt-4">
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 py-2 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900">Progress Overview</h3>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-4">
                            <div class="text-sm text-gray-600">
                                Step {{ $approvalRequest->current_step }} dari {{ $approvalRequest->total_steps }}
                            </div>
                            <div class="text-sm font-medium text-gray-900">
                                {{ round(($approvalRequest->current_step / $approvalRequest->total_steps) * 100) }}% Complete
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
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
