@extends('layouts.app')

@section('title', 'Detail Approval Request')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <!-- Header -->
        <div class="p-4 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $approvalRequest->title }}</h2>
                    <p class="text-sm text-gray-600">{{ $approvalRequest->request_number }}</p>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('approval-requests.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-3 rounded text-sm">
                        Kembali
                    </a>
                    @if($approvalRequest->status == 'pending' && $approvalRequest->requester_id == auth()->id())
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
                                <p class="text-xs text-gray-500">{{ $approvalRequest->requester->departments()->wherePivot('is_primary', true)->first()->name ?? 'No Department' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Approval Actions -->
                    @if($approvalRequest->status == 'pending')
                        @php
                            $currentStep = $approvalRequest->currentStep;
                            $canApprove = false;
                            
                            if ($currentStep) {
                                $canApprove = $currentStep->canApprove(auth()->id());
                            }
                        @endphp
                        
                        @if($canApprove)
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h3 class="text-base font-semibold text-gray-900 mb-3">Approval Actions</h3>
                            
                            <!-- Approve Form -->
                            <form action="{{ route('approval-requests.approve', $approvalRequest) }}" method="POST" class="mb-3">
                                @csrf
                                <div class="mb-3">
                                    <label for="approve_comments" class="block text-xs font-medium text-gray-700 mb-1">
                                        Comments (Optional)
                                    </label>
                                    <textarea id="approve_comments" name="comments" rows="2"
                                              class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500"
                                              placeholder="Komentar approval..."></textarea>
                                </div>
                                <button type="submit" 
                                        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-3 rounded text-sm"
                                        onclick="return confirm('Yakin ingin approve request ini?')">
                                    <i class="fas fa-check mr-1"></i>Approve
                                </button>
                            </form>
                            
                            <!-- Reject Form -->
                            <form action="{{ route('approval-requests.reject', $approvalRequest) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="reject_reason" class="block text-xs font-medium text-gray-700 mb-1">
                                        Rejection Reason <span class="text-red-500">*</span>
                                    </label>
                                    <textarea id="reject_reason" name="reason" rows="2" required
                                              class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-red-500"
                                              placeholder="Alasan penolakan..."></textarea>
                                </div>
                                <button type="submit" 
                                        class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-3 rounded text-sm"
                                        onclick="return confirm('Yakin ingin reject request ini?')">
                                    <i class="fas fa-times mr-1"></i>Reject
                                </button>
                            </form>
                        </div>
                        @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <h3 class="text-base font-semibold text-gray-900 mb-2">Waiting for Approval</h3>
                            <p class="text-xs text-gray-600">
                                Request ini sedang menunggu approval dari approver yang ditentukan untuk step ini.
                            </p>
                            @if($currentStep)
                                <div class="mt-2 text-xs text-gray-500">
                                    <strong>Current Step:</strong> {{ $currentStep->step_name }}<br>
                                    <strong>Approver Type:</strong> {{ ucfirst(str_replace('_', ' ', $currentStep->approver_type)) }}
                                </div>
                            @endif
                        </div>
                        @endif
                    @elseif($approvalRequest->status == 'approved')
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Request Approved</h3>
                        <p class="text-xs text-gray-600">
                            Request ini telah disetujui oleh {{ $approvalRequest->approver->name ?? 'System' }} 
                            pada {{ $approvalRequest->approved_at->format('d/m/Y H:i') }}.
                        </p>
                    </div>
                    @elseif($approvalRequest->status == 'rejected')
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Request Rejected</h3>
                        <p class="text-xs text-gray-600 mb-2">
                            Request ini ditolak oleh {{ $approvalRequest->approver->name ?? 'System' }} 
                            pada {{ $approvalRequest->approved_at->format('d/m/Y H:i') }}.
                        </p>
                        @if($approvalRequest->rejection_reason)
                        <div class="mt-2">
                            <label class="block text-xs font-medium text-gray-700">Alasan Penolakan</label>
                            <p class="text-xs text-gray-900 mt-1">{{ $approvalRequest->rejection_reason }}</p>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- Request Actions -->
                    @if($approvalRequest->status == 'pending' && $approvalRequest->requester_id == auth()->id())
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

            <!-- Approval Steps -->
            <div class="mt-4">
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 py-2 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900">Approval Steps</h3>
                    </div>
                    <div class="p-3">
                        <div class="space-y-2">
                            @foreach($approvalRequest->steps as $step)
                            <div class="flex items-center p-2 border border-gray-200 rounded-lg
                                {{ $step->status == 'approved' ? 'bg-green-50 border-green-200' : 
                                   ($step->status == 'rejected' ? 'bg-red-50 border-red-200' : 
                                   ($step->step_number == $approvalRequest->current_step ? 'bg-yellow-50 border-yellow-200' : 'bg-gray-50')) }}">
                                
                                <div class="flex-shrink-0">
                                    @if($step->status == 'approved')
                                        <div class="h-5 w-5 rounded-full bg-green-500 flex items-center justify-center">
                                            <i class="fas fa-check text-white text-xs"></i>
                                        </div>
                                    @elseif($step->status == 'rejected')
                                        <div class="h-5 w-5 rounded-full bg-red-500 flex items-center justify-center">
                                            <i class="fas fa-times text-white text-xs"></i>
                                        </div>
                                    @elseif($step->step_number == $approvalRequest->current_step)
                                        <div class="h-5 w-5 rounded-full bg-yellow-500 flex items-center justify-center">
                                            <i class="fas fa-clock text-white text-xs"></i>
                                        </div>
                                    @else
                                        <div class="h-5 w-5 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-gray-600 text-xs font-medium">{{ $step->step_number }}</span>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="ml-2 flex-1">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-xs font-medium text-gray-900">{{ $step->step_name }}</p>
                                            <p class="text-xs text-gray-500">
                                                @if($step->approver_type == 'user' && $step->approver)
                                                    {{ $step->approver->name }}
                                                @elseif($step->approver_type == 'role' && $step->approverRole)
                                                    {{ $step->approverRole->display_name }}
                                                @elseif($step->approver_type == 'department_manager' && $step->approverDepartment)
                                                    {{ $step->approverDepartment->name }} Manager
                                                @elseif($step->approver_type == 'department_level')
                                                    Level {{ $step->approver_level }}
                                                @endif
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $step->status == 'approved' ? 'bg-green-100 text-green-800' : 
                                                   ($step->status == 'rejected' ? 'bg-red-100 text-red-800' : 
                                                   ($step->step_number == $approvalRequest->current_step ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) }}">
                                                {{ ucfirst($step->status) }}
                                            </span>
                                            @if($step->approved_at)
                                                <p class="text-xs text-gray-500 mt-1">{{ $step->approved_at->format('d/m H:i') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    @if($step->comments)
                                    <div class="mt-1 p-1 bg-white border border-gray-200 rounded">
                                        <p class="text-xs text-gray-600">{{ $step->comments }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
