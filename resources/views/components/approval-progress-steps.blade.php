@props(['request', 'stepData' => null, 'showMetadata' => false])

@php
    $requestStatus = $request->status;
    $requestId = $request->id;
    
    // IMPORTANT: Use ACTUAL steps from database, not workflow template
    // This allows dynamic inserted steps to be displayed
    if ($stepData) {
        // stepData is ApprovalRequestItem - get actual steps from database
        $itemSteps = $stepData->steps;
        
        // Use actual steps (includes dynamic inserted steps)
        $workflowSteps = $itemSteps;
        
        // Get current pending step number
        $currentPendingStep = $itemSteps->firstWhere('status', 'pending');
        $currentStep = $currentPendingStep ? $currentPendingStep->step_number : ($itemSteps->count() + 1);
    } else {
        // Fallback: use workflow template if no stepData
        $workflowSteps = $request->workflow->steps ?? collect();
        $currentStep = 1;
    }
@endphp

<div class="min-w-0">
    <div class="flex flex-nowrap gap-1 overflow-x-auto">
        @foreach($workflowSteps as $step)
            @php
                // Check if this is an actual step (from database) or template step
                $isActualStep = isset($step->status);
                
                if ($isActualStep) {
                    // Actual step from database - use its status directly
                    $actualStatus = $step->status;
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
                    
                    // Add dynamic indicator if step was inserted
                    $stepName = $step->step_name;
                    if ($step->is_dynamic) {
                        $stepName ; // Lightning bolt for dynamic step
                    }
                } else {
                    // Template step - use fallback logic
                    $stepName = $step->step_name;
                    if ($requestStatus == 'approved') {
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
                            $stepColor = 'bg-green-600 text-white';
                            $stepStatusText = 'Approved';
                        } elseif ($step->step_number == $currentStep) {
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
                      data-step-name="{{ $stepName }}" 
                      data-step-status="{{ $stepStatusText }}" 
                      data-step-number="{{ $step->step_number }}" 
                      data-request-id="{{ $requestId }}"
                      onclick="showStepStatus('{{ $stepName }}', '{{ $stepStatusText }}', '{{ $step->step_number }}', '{{ $requestId }}')"
                      title="Klik untuk melihat detail status{{ $isActualStep && $step->is_dynamic ? ' (Step dinamis)' : '' }}">
                    {{ $stepName }}
                </span>
                @if($stepData || $showMetadata)
                    <div class="mt-0.5 text-[11px] text-gray-600 step-meta" 
                         data-request-id="{{ $requestId }}" 
                         data-step-number="{{ $step->step_number }}"
                         data-master-item-id="{{ $stepData ? $stepData->master_item_id : '' }}"
                         data-item-id="{{ $stepData ? $stepData->id : '' }}">
                        <!-- info disisipkan via JS: status, oleh, pada -->
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
