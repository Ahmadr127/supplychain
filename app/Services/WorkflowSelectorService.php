<?php

namespace App\Services;

use App\Models\ApprovalWorkflow;
use App\Models\ProcurementType;
use Illuminate\Support\Facades\Log;

class WorkflowSelectorService
{
    /**
     * Select the appropriate workflow based on:
     * 1. Procurement Type (BARANG_BARU or PEREMAJAAN)
     * 2. Total Amount (nominal)
     *
     * @param string $procurementTypeCode 'BARANG_BARU' or 'PEREMAJAAN'
     * @param float $totalAmount Total nominal amount
     * @return ApprovalWorkflow|null
     */
    public function select(string $procurementTypeCode, float $totalAmount): ?ApprovalWorkflow
    {
        // Get procurement type
        $procurementType = ProcurementType::where('code', $procurementTypeCode)->first();
        
        if (!$procurementType) {
            Log::warning('WorkflowSelector: Procurement type not found', [
                'code' => $procurementTypeCode
            ]);
            return $this->getFallbackWorkflow();
        }

        // Determine nominal range
        $nominalRange = $this->determineNominalRange($totalAmount);

        // Find matching workflow
        $workflow = ApprovalWorkflow::where('is_active', true)
            ->where('procurement_type_id', $procurementType->id)
            ->where('nominal_range', $nominalRange)
            ->orderByDesc('priority')
            ->first();

        if (!$workflow) {
            // Try to find workflow with just procurement type (any nominal)
            $workflow = ApprovalWorkflow::where('is_active', true)
                ->where('procurement_type_id', $procurementType->id)
                ->whereNull('nominal_range')
                ->orderByDesc('priority')
                ->first();
        }

        if (!$workflow) {
            Log::warning('WorkflowSelector: No matching workflow found', [
                'procurement_type' => $procurementTypeCode,
                'total_amount' => $totalAmount,
                'nominal_range' => $nominalRange
            ]);
            return $this->getFallbackWorkflow();
        }

        Log::info('WorkflowSelector: Workflow selected', [
            'workflow_id' => $workflow->id,
            'workflow_name' => $workflow->name,
            'procurement_type' => $procurementTypeCode,
            'total_amount' => $totalAmount,
            'nominal_range' => $nominalRange
        ]);

        return $workflow;
    }

    /**
     * Select workflow by procurement type ID and amount
     */
    public function selectByTypeId(int $procurementTypeId, float $totalAmount): ?ApprovalWorkflow
    {
        $procurementType = ProcurementType::find($procurementTypeId);
        
        if (!$procurementType) {
            return $this->getFallbackWorkflow();
        }

        return $this->select($procurementType->code, $totalAmount);
    }

    /**
     * Determine nominal range from total amount
     */
    public function determineNominalRange(float $totalAmount): string
    {
        if ($totalAmount <= 10000000) {
            return 'low';      // ≤ 10 Juta
        } elseif ($totalAmount <= 50000000) {
            return 'medium';   // 10 - 50 Juta
        } else {
            return 'high';     // > 50 Juta
        }
    }

    /**
     * Get human-readable nominal range description
     */
    public function getNominalRangeDescription(string $range): string
    {
        return match ($range) {
            'low' => '≤ 10 Juta',
            'medium' => '10 - 50 Juta',
            'high' => '> 50 Juta',
            default => 'Unknown'
        };
    }

    /**
     * Check if FS (Feasibility Study) is required for the given amount
     * FS is required for amounts > 50 Juta
     */
    public function requiresFS(float $totalAmount): bool
    {
        return $totalAmount > 50000000;
    }

    /**
     * Check if Direktur PT release is required
     * Required for amounts > 50 Juta
     */
    public function requiresDirekturPT(float $totalAmount): bool
    {
        return $totalAmount > 50000000;
    }

    /**
     * Get fallback workflow if no specific workflow matches
     */
    protected function getFallbackWorkflow(): ?ApprovalWorkflow
    {
        return ApprovalWorkflow::where('is_active', true)
            ->where('type', 'standard')
            ->whereNull('procurement_type_id')
            ->first()
            ?? ApprovalWorkflow::where('is_active', true)->first();
    }

    /**
     * Re-evaluate and update workflow if amount changes significantly
     * Returns true if workflow was changed
     */
    public function reevaluateWorkflow(
        int $currentWorkflowId, 
        string $procurementTypeCode, 
        float $newTotalAmount
    ): ?ApprovalWorkflow {
        $currentWorkflow = ApprovalWorkflow::find($currentWorkflowId);
        $newWorkflow = $this->select($procurementTypeCode, $newTotalAmount);

        if (!$currentWorkflow || !$newWorkflow) {
            return $newWorkflow;
        }

        // Check if workflow needs to change
        if ($currentWorkflow->id !== $newWorkflow->id) {
            Log::info('WorkflowSelector: Workflow change required', [
                'old_workflow_id' => $currentWorkflow->id,
                'old_workflow_name' => $currentWorkflow->name,
                'new_workflow_id' => $newWorkflow->id,
                'new_workflow_name' => $newWorkflow->name,
                'new_total_amount' => $newTotalAmount
            ]);
            return $newWorkflow;
        }

        return null; // No change needed
    }

    /**
     * Get all available workflows for a procurement type
     */
    public function getWorkflowsForType(string $procurementTypeCode): \Illuminate\Database\Eloquent\Collection
    {
        $procurementType = ProcurementType::where('code', $procurementTypeCode)->first();
        
        if (!$procurementType) {
            return collect();
        }

        return ApprovalWorkflow::where('is_active', true)
            ->where('procurement_type_id', $procurementType->id)
            ->orderBy('nominal_min')
            ->get();
    }

    /**
     * Preview which workflow would be selected without actually selecting
     */
    public function preview(string $procurementTypeCode, float $totalAmount): array
    {
        $workflow = $this->select($procurementTypeCode, $totalAmount);
        $nominalRange = $this->determineNominalRange($totalAmount);

        return [
            'procurement_type' => $procurementTypeCode,
            'total_amount' => $totalAmount,
            'total_amount_formatted' => 'Rp ' . number_format($totalAmount, 0, ',', '.'),
            'nominal_range' => $nominalRange,
            'nominal_range_description' => $this->getNominalRangeDescription($nominalRange),
            'requires_fs' => $this->requiresFS($totalAmount),
            'requires_direktur_pt' => $this->requiresDirekturPT($totalAmount),
            'workflow' => $workflow ? [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'description' => $workflow->description,
                'step_count' => count($workflow->steps ?? []),
            ] : null,
        ];
    }
}
