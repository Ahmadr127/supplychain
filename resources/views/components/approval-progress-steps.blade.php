@props(['request', 'stepData' => null])

@php
    // Use stepData if provided (for pending-approvals), otherwise use request directly
    $workflowSteps = $stepData ? $stepData->request->workflow->steps : $request->workflow->steps;
    $requestStatus = $stepData ? $stepData->request->status : $request->status;
    // DEPRECATED: current_step removed in per-item approval system
    // Use step_number from stepData if available, otherwise default to 1
    $currentStep = $stepData ? ($stepData->step_number ?? 1) : 1;
    $requestId = $stepData ? $stepData->request->id : $request->id;
@endphp

<div class="min-w-0">
    <div class="flex flex-nowrap gap-1 overflow-x-auto">
        @foreach($workflowSteps as $step)
            @php
                $stepStatus = 'pending';
                $stepColor = 'bg-gray-100 text-gray-600';
                $stepStatusText = 'Pending';
                
                if ($requestStatus == 'approved') {
                    // If request is fully approved, all steps should be green
                    $stepStatus = 'completed';
                    $stepColor = 'bg-green-600 text-white';
                    $stepStatusText = 'Approved';
                } elseif ($requestStatus == 'rejected') {
                    // If request is rejected, steps at or after current step should be red
                    if ($step->step_number >= $currentStep) {
                        $stepColor = 'bg-red-600 text-white';
                        $stepStatusText = 'Rejected';
                    } else {
                        $stepColor = 'bg-green-600 text-white';
                        $stepStatusText = 'Approved';
                    }
                } else {
                    // For on progress and pending requests
                    if ($step->step_number < $currentStep) {
                        $stepStatus = 'completed';
                        $stepColor = 'bg-green-600 text-white';
                        $stepStatusText = 'Approved';
                    } elseif ($step->step_number == $currentStep) {
                        $stepStatus = 'current';
                        $stepColor = 'bg-blue-500 text-white';
                        $stepStatusText = 'On Progress';
                    } else {
                        // Future steps are pending/waiting
                        $stepColor = 'bg-yellow-500 text-white';
                        $stepStatusText = 'Pending';
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
                @if($stepData)
                    <div class="mt-0.5 text-[11px] text-gray-600 step-meta" data-request-id="{{ $requestId }}" data-step-number="{{ $step->step_number }}">
                        <!-- info disisipkan via JS: status, oleh, pada -->
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
