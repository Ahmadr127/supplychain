<?php

namespace Database\Factories;

use App\Models\ApprovalItemStep;
use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApprovalItemStepFactory extends Factory
{
    protected $model = ApprovalItemStep::class;

    public function definition(): array
    {
        return [
            'approval_request_id' => ApprovalRequest::factory(),
            'approval_request_item_id' => null,
            'master_item_id' => null,
            'step_number' => 1,
            'step_name' => fake()->words(3, true),
            'approver_type' => 'user',
            'approver_id' => User::factory(),
            'approver_role_id' => null,
            'approver_department_id' => null,
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'comments' => null,
            'rejected_reason' => null,
            'can_insert_step' => false,
            'insert_step_template' => null,
            'is_dynamic' => false,
            'inserted_by' => null,
            'inserted_at' => null,
            'insertion_reason' => null,
            'required_action' => null,
            'is_conditional' => false,
            'condition_type' => null,
            'condition_value' => null,
            'step_type' => 'approver',
            'step_phase' => 'approval',
            'scope_process' => null,
            'selected_capex_id' => null,
            'skip_reason' => null,
            'skipped_at' => null,
            'skipped_by' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejected_reason' => fake()->sentence(),
        ]);
    }

    public function releasePhase(): static
    {
        return $this->state(fn (array $attributes) => [
            'step_phase' => 'release',
            'step_type' => 'releaser',
        ]);
    }

    public function roleApprover(): static
    {
        return $this->state(fn (array $attributes) => [
            'approver_type' => 'role',
            'approver_id' => null,
        ]);
    }
}
