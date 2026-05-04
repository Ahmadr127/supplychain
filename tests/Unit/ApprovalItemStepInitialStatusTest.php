<?php

namespace Tests\Unit;

use App\Models\ApprovalItemStep;
use Tests\TestCase;

class ApprovalItemStepInitialStatusTest extends TestCase
{
    public function test_approval_step_after_release_in_workflow_is_pending_not_pending_purchase(): void
    {
        $workflowSteps = collect([
            (object) ['step_number' => 1, 'step_phase' => 'approval', 'step_type' => 'approver'],
            (object) ['step_number' => 2, 'step_phase' => 'release', 'step_type' => 'releaser'],
            (object) ['step_number' => 3, 'step_phase' => 'approval', 'step_type' => 'approver'],
        ]);

        $direktur = $workflowSteps[2];
        $this->assertSame(
            'pending',
            ApprovalItemStep::initialStatusForWorkflowStep($direktur, $workflowSteps)
        );
    }

    public function test_release_step_is_pending_not_pending_purchase(): void
    {
        $workflowSteps = collect([
            (object) ['step_number' => 2, 'step_phase' => 'release', 'step_type' => 'releaser'],
        ]);

        $this->assertSame(
            'pending',
            ApprovalItemStep::initialStatusForWorkflowStep($workflowSteps[0], $workflowSteps)
        );
    }

    public function test_purchasing_after_release_is_pending_purchase(): void
    {
        $workflowSteps = collect([
            (object) ['step_number' => 4, 'step_phase' => 'release', 'step_type' => 'releaser'],
            (object) ['step_number' => 5, 'step_phase' => 'purchasing', 'step_type' => 'purchasing'],
        ]);

        $this->assertSame(
            'pending_purchase',
            ApprovalItemStep::initialStatusForWorkflowStep($workflowSteps[1], $workflowSteps)
        );
    }

    public function test_purchasing_before_any_release_is_pending(): void
    {
        $workflowSteps = collect([
            (object) ['step_number' => 3, 'step_phase' => 'purchasing', 'step_type' => 'purchasing'],
        ]);

        $this->assertSame(
            'pending',
            ApprovalItemStep::initialStatusForWorkflowStep($workflowSteps[0], $workflowSteps)
        );
    }
}
