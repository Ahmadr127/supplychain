<?php

namespace Database\Factories;

use App\Models\ApprovalRequest;
use App\Models\User;
use App\Models\ApprovalWorkflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApprovalRequestFactory extends Factory
{
    protected $model = ApprovalRequest::class;

    public function definition(): array
    {
        return [
            'request_number' => 'REQ-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'letter_number' => fake()->optional()->numerify('LTR-####'),
            'workflow_id' => null,
            'requester_id' => User::factory(),
            'submission_type_id' => null,
            'description' => fake()->optional()->sentence(),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'is_cto_request' => false,
            'status' => 'on progress',
            'purchasing_status' => 'unprocessed',
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
            'item_type_id' => null,
            'is_specific_type' => false,
            'received_at' => now(),
            'fs_document' => null,
            'procurement_type_id' => null,
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
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
