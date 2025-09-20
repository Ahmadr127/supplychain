@extends('layouts.app')

@section('title', 'Detail Approval Request')

@section('content')
<div class="w-full mx-auto max-w-6xl">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $approvalRequest->title }}</h2>
                    <p class="text-gray-600">{{ $approvalRequest->request_number }}</p>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('approval-requests.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Kembali
                    </a>
                    @if($approvalRequest->status == 'pending' && $approvalRequest->requester_id == auth()->id())
                        <a href="{{ route('approval-requests.edit', $approvalRequest) }}" 
                           class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Edit
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Request Info -->
                <div class="lg:col-span-2">
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Request</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <p class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $approvalRequest->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                           ($approvalRequest->status == 'approved' ? 'bg-green-100 text-green-800' : 
                                           ($approvalRequest->status == 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                                        {{ ucfirst($approvalRequest->status) }}
                                    </span>
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Progress</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    Step {{ $approvalRequest->current_step }} of {{ $approvalRequest->total_steps }}
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Workflow</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $approvalRequest->workflow->name }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Dibuat</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $approvalRequest->created_at->format('d M Y H:i') }}</p>
                            </div>
                        </div>
                        
                        @if($approvalRequest->description)
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $approvalRequest->description }}</p>
                        </div>
                        @endif

                        @if($approvalRequest->data)
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Data Request</label>
                            <div class="mt-1 bg-white border border-gray-200 rounded-lg p-4">
                                <pre class="text-sm text-gray-900 whitespace-pre-wrap">{{ json_encode($approvalRequest->data, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Requester Info -->
                    <div class="bg-blue-50 rounded-lg p-6 mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Requester</h3>
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                    <span class="text-white font-medium text-sm">
                                        {{ substr($approvalRequest->requester->name, 0, 2) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">{{ $approvalRequest->requester->name }}</p>
                                <p class="text-sm text-gray-500">{{ $approvalRequest->requester->email }}</p>
                                <p class="text-sm text-gray-500">{{ $approvalRequest->requester->role->display_name ?? 'No Role' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Approval Actions -->
                    @if($approvalRequest->status == 'pending')
                        @php
                            $currentStep = $approvalRequest->currentStep;
                            $canApprove = false;
                            
                            if ($currentStep) {
                                $canApprove = $currentStep->canApprove(auth()->id());
                            }
                            
                            // Debug information (remove in production)
                            $debugInfo = [
                                'current_step' => $currentStep,
                                'user_id' => auth()->id(),
                                'approver_type' => $currentStep ? $currentStep->approver_type : null,
                                'approver_id' => $currentStep ? $currentStep->approver_id : null,
                                'approver_role_id' => $currentStep ? $currentStep->approver_role_id : null,
                                'approver_department_id' => $currentStep ? $currentStep->approver_department_id : null,
                                'approver_level' => $currentStep ? $currentStep->approver_level : null,
                                'can_approve' => $canApprove
                            ];
                        @endphp
                        
                        @if($canApprove)
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Approval Actions</h3>
                            
                            <!-- Approve Form -->
                            <form action="{{ route('approval-requests.approve', $approvalRequest) }}" method="POST" class="mb-4">
                                @csrf
                                <div class="mb-4">
                                    <label for="approve_comments" class="block text-sm font-medium text-gray-700 mb-2">
                                        Comments (Optional)
                                    </label>
                                    <textarea id="approve_comments" name="comments" rows="3"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                              placeholder="Tambahkan komentar untuk approval..."></textarea>
                                </div>
                                <button type="submit" 
                                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                                        onclick="return confirm('Yakin ingin approve request ini?')">
                                    <i class="fas fa-check mr-2"></i>Approve
                                </button>
                            </form>
                            
                            <!-- Reject Form -->
                            <form action="{{ route('approval-requests.reject', $approvalRequest) }}" method="POST">
                                @csrf
                                <div class="mb-4">
                                    <label for="reject_reason" class="block text-sm font-medium text-gray-700 mb-2">
                                        Rejection Reason <span class="text-red-500">*</span>
                                    </label>
                                    <textarea id="reject_reason" name="reason" rows="3" required
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                              placeholder="Alasan penolakan..."></textarea>
                                </div>
                                <div class="mb-4">
                                    <label for="reject_comments" class="block text-sm font-medium text-gray-700 mb-2">
                                        Comments (Optional)
                                    </label>
                                    <textarea id="reject_comments" name="comments" rows="2"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                              placeholder="Komentar tambahan..."></textarea>
                                </div>
                                <button type="submit" 
                                        class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                                        onclick="return confirm('Yakin ingin reject request ini?')">
                                    <i class="fas fa-times mr-2"></i>Reject
                                </button>
                            </form>
                        </div>
                        @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Waiting for Approval</h3>
                            <p class="text-sm text-gray-600">
                                Request ini sedang menunggu approval dari approver yang ditentukan untuk step ini.
                            </p>
                            @if($currentStep)
                                <div class="mt-2 text-xs text-gray-500">
                                    <strong>Current Step:</strong> {{ $currentStep->step_name }}<br>
                                    <strong>Approver Type:</strong> {{ ucfirst(str_replace('_', ' ', $currentStep->approver_type)) }}
                                </div>
                            @endif
                            
                            <!-- Debug Information (remove in production) -->
                            @if(config('app.debug'))
                                <div class="mt-4 p-3 bg-gray-100 rounded text-xs">
                                    <strong>Debug Info:</strong><br>
                                    <pre>{{ json_encode($debugInfo, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            @endif
                        </div>
                        @endif
                    @elseif($approvalRequest->status == 'approved')
                    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Request Approved</h3>
                        <p class="text-sm text-gray-600">
                            Request ini telah disetujui oleh {{ $approvalRequest->approver->name ?? 'System' }} 
                            pada {{ $approvalRequest->approved_at->format('d M Y H:i') }}.
                        </p>
                    </div>
                    @elseif($approvalRequest->status == 'rejected')
                    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Request Rejected</h3>
                        <p class="text-sm text-gray-600 mb-2">
                            Request ini ditolak oleh {{ $approvalRequest->approver->name ?? 'System' }} 
                            pada {{ $approvalRequest->approved_at->format('d M Y H:i') }}.
                        </p>
                        @if($approvalRequest->rejection_reason)
                        <div class="mt-2">
                            <label class="block text-sm font-medium text-gray-700">Alasan Penolakan</label>
                            <p class="text-sm text-gray-900 mt-1">{{ $approvalRequest->rejection_reason }}</p>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- Request Actions -->
                    @if($approvalRequest->status == 'pending' && $approvalRequest->requester_id == auth()->id())
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Actions</h3>
                        <form action="{{ route('approval-requests.cancel', $approvalRequest) }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    class="w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                                    onclick="return confirm('Yakin ingin membatalkan request ini?')">
                                <i class="fas fa-ban mr-2"></i>Cancel Request
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Approval Steps -->
            <div class="mt-8">
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Approval Steps</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            @foreach($approvalRequest->steps as $step)
                            <div class="flex items-center p-4 border border-gray-200 rounded-lg
                                {{ $step->status == 'approved' ? 'bg-green-50 border-green-200' : 
                                   ($step->status == 'rejected' ? 'bg-red-50 border-red-200' : 
                                   ($step->step_number == $approvalRequest->current_step ? 'bg-yellow-50 border-yellow-200' : 'bg-gray-50')) }}">
                                
                                <div class="flex-shrink-0">
                                    @if($step->status == 'approved')
                                        <div class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center">
                                            <i class="fas fa-check text-white text-sm"></i>
                                        </div>
                                    @elseif($step->status == 'rejected')
                                        <div class="h-8 w-8 rounded-full bg-red-500 flex items-center justify-center">
                                            <i class="fas fa-times text-white text-sm"></i>
                                        </div>
                                    @elseif($step->step_number == $approvalRequest->current_step)
                                        <div class="h-8 w-8 rounded-full bg-yellow-500 flex items-center justify-center">
                                            <i class="fas fa-clock text-white text-sm"></i>
                                        </div>
                                    @else
                                        <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-gray-600 text-xs font-medium">{{ $step->step_number }}</span>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $step->step_name }}</p>
                                            <p class="text-xs text-gray-500">
                                                @if($step->approver_type == 'user' && $step->approver)
                                                    Approver: {{ $step->approver->name }}
                                                @elseif($step->approver_type == 'role' && $step->approverRole)
                                                    Role: {{ $step->approverRole->display_name }}
                                                @elseif($step->approver_type == 'department_manager' && $step->approverDepartment)
                                                    Department Manager: {{ $step->approverDepartment->name }}
                                                @elseif($step->approver_type == 'department_level')
                                                    Department Level: {{ $step->approver_level }}
                                                @endif
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $step->status == 'approved' ? 'bg-green-100 text-green-800' : 
                                                   ($step->status == 'rejected' ? 'bg-red-100 text-red-800' : 
                                                   ($step->step_number == $approvalRequest->current_step ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) }}">
                                                {{ ucfirst($step->status) }}
                                            </span>
                                            @if($step->approved_at)
                                                <p class="text-xs text-gray-500 mt-1">{{ $step->approved_at->format('d M Y H:i') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    @if($step->comments)
                                    <div class="mt-2 p-2 bg-white border border-gray-200 rounded">
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
