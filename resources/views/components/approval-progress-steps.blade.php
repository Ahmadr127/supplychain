@props(['request', 'stepData' => null, 'showMetadata' => false])

@php
    // Use stepData if provided (for per-item display), otherwise use request directly
    $workflowSteps = $request->workflow->steps ?? collect();
    $requestStatus = $request->status;
    $requestId = $request->id;
    
    // Get actual step statuses from database for this specific item
    $actualStepStatuses = [];
    if ($stepData) {
        // stepData is ApprovalRequestItem
        $itemSteps = \App\Models\ApprovalItemStep::where('approval_request_id', $stepData->approval_request_id)
            ->where('master_item_id', $stepData->master_item_id)
            ->get()
            ->keyBy('step_number');
        foreach ($itemSteps as $itemStep) {
            $actualStepStatuses[$itemStep->step_number] = $itemStep->status;
        }
        
        // Get current pending step number
        $currentPendingStep = $itemSteps->firstWhere('status', 'pending');
        $currentStep = $currentPendingStep ? $currentPendingStep->step_number : ($itemSteps->count() + 1);
    } else {
        $currentStep = 1;
    }
@endphp

<div class="min-w-0">
    <div class="flex flex-nowrap gap-1 overflow-x-auto">
        @foreach($workflowSteps as $step)
            @php
                $stepStatus = 'pending';
                $stepColor = 'bg-gray-100 text-gray-600';
                $stepStatusText = 'Pending';
                
                // Use actual step status if available (for pending-approvals)
                if (isset($actualStepStatuses[$step->step_number])) {
                    $actualStatus = $actualStepStatuses[$step->step_number];
                    if ($actualStatus === 'approved') {
                        $stepColor = 'bg-green-600 text-white';
                        $stepStatusText = 'Approved';
                    } elseif ($actualStatus === 'rejected') {
                        $stepColor = 'bg-red-600 text-white';
                        $stepStatusText = 'Rejected';
                    } elseif ($actualStatus === 'pending') {
                        // Check if this is the current step
                        if ($step->step_number == $currentStep) {
                            $stepColor = 'bg-blue-500 text-white';
                            $stepStatusText = 'On Progress';
                        } else {
                            $stepColor = 'bg-yellow-500 text-white';
                            $stepStatusText = 'Pending';
                        }
                    }
                } else {
                    // Fallback to old logic if no actual status
                    if ($requestStatus == 'approved') {
                        $stepStatus = 'completed';
                        $stepColor = 'bg-green-600 text-white';
                        $stepStatusText = 'Approved';
                    } elseif ($requestStatus == 'rejected') {
                        if ($step->step_number >= $currentStep) {
                            $stepColor = 'bg-red-600 text-white';
                            $stepStatusText = 'Rejected';
                        } else {
                            $stepColor = 'bg-green-600 text-white';
                            $stepStatusText = 'Approved';
                        }
                    } else {
                        if ($step->step_number < $currentStep) {
                            $stepStatus = 'completed';
                            $stepColor = 'bg-green-600 text-white';
                            $stepStatusText = 'Approved';
                        } elseif ($step->step_number == $currentStep) {
                            $stepStatus = 'current';
                            $stepColor = 'bg-blue-500 text-white';
                            $stepStatusText = 'On Progress';
                        } else {
                            $stepColor = 'bg-yellow-500 text-white';
                            $stepStatusText = 'Pending';
                        }
                    }
                }
            @endphp
            <div class="flex flex-col flex-shrink-0">
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium whitespace-nowrap {{ $stepColor }} cursor-pointer step-badge hover:opacity-80 transition-opacity" 
                      data-step-name="{{ $step->step_name }}" 
                      data-step-status="{{ $stepStatusText }}" 
                      data-step-number="{{ $step->step_number }}" 
                      data-request-id="{{ $requestId }}"
                      onclick="showStepStatus('{{ $step->step_name }}', '{{ $stepStatusText }}', '{{ $step->step_number }}', '{{ $requestId }}')"
                      title="Klik untuk melihat detail status">
                    {{ $step->step_name }}
                </span>
                @if($stepData || $showMetadata)
                    <div class="mt-0.5 text-[11px] text-gray-600 step-meta" 
                         data-request-id="{{ $requestId }}" 
                         data-step-number="{{ $step->step_number }}"
                         data-master-item-id="{{ $stepData ? $stepData->master_item_id : '' }}">
                        <!-- info disisipkan via JS: status, oleh, pada -->
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
