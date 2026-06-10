<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalWorkflow extends Model
{
    use HasFactory;

    /** Standard purchasing step keys available in this system */
    public const PURCHASING_STEP_KEYS = [
        'benchmarking',
        'trial',
        'preferred_vendor',
        'po',
        'invoice_grn_done',
    ];

    /** Default label map for purchasing step keys */
    public const PURCHASING_STEP_LABELS = [
        'benchmarking'     => 'Benchmarking Vendor',
        'trial'            => 'Trial Vendor',
        'preferred_vendor' => 'Pilih Preferred Vendor',
        'po'               => 'Input PO',
        'invoice_grn_done' => 'Invoice & GRN (Selesai)',
    ];

    protected $fillable = [
        'name',
        'type',
        'description',
        'workflow_steps',  // JSON: array of step definitions
        'steps',           // Alias for workflow_steps (from seeder)
        'is_active',
        'item_type_id',
        'is_specific_type',
        // Procurement type and nominal-based workflow selection
        'procurement_type_id',
        'nominal_min',
        'nominal_max',
        'nominal_range',   // low, medium, high
        'priority',        // For workflow selection ordering
        // Purchasing step configuration (which steps are enabled for this workflow)
        'purchasing_step_config',
        // Technical Support Config
        'ts_approver_type',
        'ts_approver_id',
        'ts_approver_role_id',
    ];

    protected $casts = [
        'workflow_steps'         => 'array',
        'steps'                  => 'array',
        'is_active'              => 'boolean',
        'is_specific_type'       => 'boolean',
        'nominal_min'            => 'decimal:2',
        'nominal_max'            => 'decimal:2',
        'priority'               => 'integer',
        'purchasing_step_config' => 'array',
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Get the ts categories that trigger this workflow.
     */
    public function tsCategories()
    {
        return $this->belongsToMany(TsCategory::class, 'approval_workflow_ts_categories', 'approval_workflow_id', 'ts_category_id')->withTimestamps();
    }

    // Relasi dengan approval requests
    public function requests()
    {
        return $this->hasMany(ApprovalRequest::class, 'workflow_id');
    }

    // Relasi dengan item type
    public function itemType()
    {
        return $this->belongsTo(\App\Models\ItemType::class);
    }

    // Relasi dengan procurement type
    public function procurementType()
    {
        return $this->belongsTo(ProcurementType::class);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Method untuk mendapatkan workflow steps sebagai collection
     * Supports both old format (workflow_steps) and new format (steps)
     */
    public function getStepsAttribute($value)
    {
        // If already accessed as attribute and cached
        if ($value && is_array($value)) {
            return $this->parseSteps($value);
        }

        // Try new 'steps' column first (from seeder), then fall back to 'workflow_steps'
        $stepsData = $this->attributes['steps'] ?? $this->attributes['workflow_steps'] ?? null;
        
        if (!$stepsData) {
            return collect();
        }

        // Decode if JSON string
        if (is_string($stepsData)) {
            $stepsData = json_decode($stepsData, true);
        }

        return $this->parseSteps($stepsData);
    }

    /**
     * Parse steps array into collection of objects
     */
    protected function parseSteps(array $stepsData): \Illuminate\Support\Collection
    {
        return collect($stepsData)->map(function ($step, $index) {
            // Handle both object and array formats
            $step = (array) $step;
            
            return (object) [
                'step_number' => $step['step_number'] ?? ($index + 1),
                'step_name' => $step['step_name'] ?? $step['name'] ?? "Step " . ($index + 1),
                'approver_type' => $step['approver_type'] ?? 'role',
                'approver_id' => $step['approver_id'] ?? null,
                'approver_role_id' => $step['approver_role_id'] ?? null,
                'approver_department_id' => $step['approver_department_id'] ?? null,
                'can_insert_step' => $step['can_insert_step'] ?? false,
                'insert_step_template' => $step['insert_step_template'] ?? null,
                'required_action' => $step['required_action'] ?? null,
                'required_actions' => $step['required_actions'] ?? null, // NEW: plural actions
                'is_conditional' => $step['is_conditional'] ?? false,
                'condition_type' => $step['condition_type'] ?? null,
                'condition_value' => $step['condition_value'] ?? null,
                'description' => $step['description'] ?? null,
                // NEW: Step type and phase for 3-phase workflow
                'step_type' => $step['step_type'] ?? 'approver',
                'step_phase' => $step['step_phase'] ?? 'approval',
                'scope_process' => $step['scope_process'] ?? null,
            ];
        });
    }

    /**
     * Get only approval phase steps
     */
    public function getApprovalPhaseSteps(): \Illuminate\Support\Collection
    {
        return $this->steps->filter(fn($step) => ($step->step_phase ?? 'approval') === 'approval');
    }

    /**
     * Get only release phase steps
     */
    public function getReleasePhaseSteps(): \Illuminate\Support\Collection
    {
        return $this->steps->filter(fn($step) => ($step->step_phase ?? 'approval') === 'release');
    }

    /**
     * Count approval steps
     */
    public function countApprovalSteps(): int
    {
        return $this->getApprovalPhaseSteps()->count();
    }

    /**
     * Count release steps
     */
    public function countReleaseSteps(): int
    {
        return $this->getReleasePhaseSteps()->count();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PURCHASING STEP CONFIG
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Get the normalized purchasing step config.
     * If purchasing_step_config is null, returns the default (all enabled, standard order).
     * Always returns a Collection of objects with: step_key, label, enabled, order, allow_skip.
     */
    public function getEnabledPurchasingSteps(): \Illuminate\Support\Collection
    {
        $config = $this->purchasing_step_config;

        if (empty($config)) {
            // Default: all steps enabled
            return $this->buildDefaultPurchasingStepConfig();
        }

        return collect($config)
            ->map(fn($step) => (object) [
                'step_key'   => $step['step_key']   ?? '',
                'label'      => $step['label']      ?? (self::PURCHASING_STEP_LABELS[$step['step_key'] ?? ''] ?? $step['step_key']),
                'enabled'    => (bool) ($step['enabled']    ?? true),
                'order'      => (int)  ($step['order']      ?? 99),
                'allow_skip' => (bool) ($step['allow_skip'] ?? false),
            ])
            ->filter(fn($s) => $s->enabled && in_array($s->step_key, self::PURCHASING_STEP_KEYS))
            ->sortBy('order')
            ->values();
    }

    /**
     * Check whether a specific purchasing step key is enabled in this workflow.
     */
    public function isPurchasingStepEnabled(string $stepKey): bool
    {
        $config = $this->purchasing_step_config;

        if (empty($config)) {
            // Default: all steps enabled
            return in_array($stepKey, self::PURCHASING_STEP_KEYS);
        }

        $found = collect($config)->firstWhere('step_key', $stepKey);
        return $found && (bool) ($found['enabled'] ?? true);
    }

    /**
     * Check whether a purchasing step allows being skipped.
     */
    public function isPurchasingStepSkippable(string $stepKey): bool
    {
        $config = $this->purchasing_step_config;

        if (empty($config)) {
            // Default: trial is skippable, others are not
            return $stepKey === 'trial';
        }

        $found = collect($config)->firstWhere('step_key', $stepKey);
        return $found ? (bool) ($found['allow_skip'] ?? false) : false;
    }

    /**
     * Build the default purchasing step config (all enabled, standard order).
     */
    public function buildDefaultPurchasingStepConfig(): \Illuminate\Support\Collection
    {
        return collect([
            (object) ['step_key' => 'benchmarking',     'label' => 'Benchmarking Vendor',   'enabled' => true, 'order' => 1, 'allow_skip' => false],
            (object) ['step_key' => 'trial',            'label' => 'Trial Vendor',           'enabled' => true, 'order' => 2, 'allow_skip' => true],
            (object) ['step_key' => 'preferred_vendor', 'label' => 'Pilih Preferred Vendor', 'enabled' => true, 'order' => 3, 'allow_skip' => false],
            (object) ['step_key' => 'po',               'label' => 'Input PO',               'enabled' => true, 'order' => 4, 'allow_skip' => false],
            (object) ['step_key' => 'invoice_grn_done', 'label' => 'Invoice & GRN (Selesai)','enabled' => true, 'order' => 5, 'allow_skip' => false],
        ]);
    }

    // Scope untuk workflow aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope untuk workflow berdasarkan item type
    public function scopeForItemType($query, $itemTypeId)
    {
        return $query->where(function($q) use ($itemTypeId) {
            $q->where('item_type_id', $itemTypeId)
              ->orWhere('is_specific_type', false);
        });
    }

    // Scope untuk workflow umum (tidak specific ke item type)
    public function scopeGeneral($query)
    {
        return $query->where('is_specific_type', false);
    }

    // Scope untuk workflow specific
    public function scopeSpecific($query)
    {
        return $query->where('is_specific_type', true);
    }

    // Method untuk membuat approval request
    public function createRequest($requesterId, $submissionTypeId, $description = null, $requestNumber = null, $priority = 'normal', $isCtoRequest = false)
    {
        $requestNumber = $requestNumber ?: $this->generateRequestNumber();
        
        $request = $this->requests()->create([
            'request_number' => $requestNumber,
            'requester_id' => $requesterId,
            'submission_type_id' => $submissionTypeId,
            'priority' => $priority,
            'is_cto_request' => $isCtoRequest,
            // 'total_steps' and 'current_step' removed - per-item approval system
            'status' => 'on progress'
        ]);

        // DEPRECATED: createApprovalSteps() removed
        // In per-item approval system, steps are created per-item in the controller
        // See ApprovalRequestController::initializeItemSteps()
        
        return $request;
    }

    // DEPRECATED: Method untuk membuat approval steps (request-level)
    // Replaced by per-item approval steps (ApprovalItemStep)
    // Steps are now created per-item in ApprovalRequestController::initializeItemSteps()
    /*
    private function createApprovalSteps($request)
    {
        foreach ($this->workflow_steps as $index => $step) {
            $request->steps()->create([
                'step_number' => $index + 1,
                'step_name' => $step['name'],
                'approver_type' => $step['approver_type'],
                'approver_id' => $step['approver_id'] ?? null,
                'approver_role_id' => $step['approver_role_id'] ?? null,
                'approver_department_id' => $step['approver_department_id'] ?? null,
                'status' => 'pending'
            ]);
        }
    }
    */

    // Method untuk generate nomor request
    private function generateRequestNumber()
    {
        $prefix = strtoupper(substr($this->type, 0, 3));
        $date = now()->format('Ymd');
        $count = $this->requests()->whereDate('created_at', now()->toDateString())->count() + 1;
        
        return $prefix . '-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
