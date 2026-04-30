<?php

namespace App\Http\Controllers;

use App\Models\ApprovalWorkflow;
use App\Models\Role;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApprovalWorkflowController extends Controller
{
    public function index(Request $request)
    {
        $query = ApprovalWorkflow::withCount('requests');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $workflows = $query->latest()->paginate(10)->withQueryString();
        
        return view('approval-workflows.index', compact('workflows'));
    }

    public function create()
    {
        $roles = Role::all();
        $departments = Department::where('is_active', true)->get();
        $users = User::with('role')->get();
        return view('approval-workflows.create', compact('roles', 'departments', 'users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'nominal_min' => 'required|string',
            'nominal_max' => 'nullable|string',
            'workflow_steps' => 'required|array|min:1',
            'workflow_steps.*.name' => 'required|string|max:255',
            'workflow_steps.*.step_type' => 'nullable|in:approver,releaser,purchasing,maker',
            'workflow_steps.*.approver_type' => 'required|in:user,role,department_manager,requester_department_manager,any_department_manager',
            'workflow_steps.*.approver_id' => 'required_if:workflow_steps.*.approver_type,user|nullable|exists:users,id',
            'workflow_steps.*.approver_role_id' => 'required_if:workflow_steps.*.approver_type,role|nullable|exists:roles,id',
            'workflow_steps.*.approver_department_id' => 'required_if:workflow_steps.*.approver_type,department_manager|nullable|exists:departments,id',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Process and clean workflow steps
        $workflowSteps = $this->processWorkflowSteps($request->workflow_steps);
        
        // Parse nominal values (remove dots from formatted numbers)
        $nominalMin = (float) str_replace('.', '', $request->nominal_min);
        $nominalMax = $request->nominal_max ? (float) str_replace('.', '', $request->nominal_max) : null;
        
        // Auto-calculate nominal_range based on nominal_max value
        $nominalRange = $this->calculateNominalRange($nominalMax);

        $workflow = ApprovalWorkflow::create([
            'name' => $request->name,
            'type' => $request->type,
            'description' => $request->description,
            'nominal_range' => $nominalRange,
            'nominal_min' => $nominalMin,
            'nominal_max' => $nominalMax,
            'priority' => $this->calculatePriority($nominalMax),
            'workflow_steps' => $workflowSteps,
            'steps' => $workflowSteps,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('approval-workflows.index')->with('success', 'Approval workflow berhasil dibuat!');
    }

    public function show(ApprovalWorkflow $approvalWorkflow)
    {
        $approvalWorkflow->load('requests.requester');
        
        return view('approval-workflows.show', compact('approvalWorkflow'));
    }

    public function edit(ApprovalWorkflow $approvalWorkflow)
    {
        $roles = Role::all();
        $departments = Department::where('is_active', true)->get();
        $users = User::with('role')->get();
        return view('approval-workflows.edit', compact('approvalWorkflow', 'roles', 'departments', 'users'));
    }

    public function update(Request $request, ApprovalWorkflow $approvalWorkflow)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'nominal_min' => 'required|string',
            'nominal_max' => 'nullable|string',
            'workflow_steps' => 'required|array|min:1',
            'workflow_steps.*.name' => 'required|string|max:255',
            'workflow_steps.*.step_type' => 'nullable|in:approver,releaser,purchasing,maker',
            'workflow_steps.*.approver_type' => 'required|in:user,role,department_manager,requester_department_manager,any_department_manager',
            'workflow_steps.*.approver_id' => 'required_if:workflow_steps.*.approver_type,user|nullable|exists:users,id',
            'workflow_steps.*.approver_role_id' => 'required_if:workflow_steps.*.approver_type,role|nullable|exists:roles,id',
            'workflow_steps.*.approver_department_id' => 'required_if:workflow_steps.*.approver_type,department_manager|nullable|exists:departments,id',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Process and clean workflow steps
        $workflowSteps = $this->processWorkflowSteps($request->workflow_steps);
        
        // Parse nominal values (remove dots from formatted numbers)
        $nominalMin = (float) str_replace('.', '', $request->nominal_min);
        $nominalMax = $request->nominal_max ? (float) str_replace('.', '', $request->nominal_max) : null;
        
        // Auto-calculate nominal_range based on nominal_max value
        $nominalRange = $this->calculateNominalRange($nominalMax);

        $approvalWorkflow->update([
            'name' => $request->name,
            'type' => $request->type,
            'description' => $request->description,
            'nominal_range' => $nominalRange,
            'nominal_min' => $nominalMin,
            'nominal_max' => $nominalMax,
            'priority' => $this->calculatePriority($nominalMax),
            'workflow_steps' => $workflowSteps,
            'steps' => $workflowSteps,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('approval-workflows.index')->with('success', 'Approval workflow berhasil diperbarui!');
    }

    public function destroy(ApprovalWorkflow $approvalWorkflow)
    {
        // Check if workflow has requests
        if ($approvalWorkflow->requests()->count() > 0) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus workflow yang memiliki request!');
        }

        $approvalWorkflow->delete();
        return redirect()->route('approval-workflows.index')->with('success', 'Approval workflow berhasil dihapus!');
    }

    public function toggleStatus(ApprovalWorkflow $approvalWorkflow)
    {
        $approvalWorkflow->update(['is_active' => !$approvalWorkflow->is_active]);
        
        $status = $approvalWorkflow->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Workflow berhasil {$status}!");
    }

    /**
     * Calculate nominal range based on nominal_max value
     */
    private function calculateNominalRange(?float $nominalMax): string
    {
        if ($nominalMax === null) {
            return 'high';  // No upper limit = high
        }
        
        if ($nominalMax <= 10000000) {
            return 'low';   // <= 10 juta
        }
        
        if ($nominalMax <= 50000000) {
            return 'medium'; // 10-50 juta
        }
        
        return 'high';  // > 50 juta
    }

    /**
     * Calculate priority based on nominal_max value
     * Higher nominal = higher priority
     */
    private function calculatePriority(?float $nominalMax): int
    {
        if ($nominalMax === null) {
            return 30;  // No upper limit = highest priority
        }
        
        if ($nominalMax <= 10000000) {
            return 10;  // <= 10 juta
        }
        
        if ($nominalMax <= 50000000) {
            return 20;  // 10-50 juta
        }
        
        return 30;  // > 50 juta
    }

    /**
     * Process and clean workflow steps data
     * Ensures proper indexing and removes empty values
     */
    private function processWorkflowSteps($workflowSteps)
    {
        if (empty($workflowSteps)) {
            return [];
        }

        $processedSteps = [];
        $stepIndex = 0;

        foreach ($workflowSteps as $step) {
            // Skip if step is empty or missing required fields
            if (empty($step['name']) || empty($step['approver_type'])) {
                continue;
            }

            $processedStep = [
                'name' => trim($step['name']),
                'approver_type' => $step['approver_type']
            ];
            
            // Add optional fields
            if (!empty($step['description'])) {
                $processedStep['description'] = trim($step['description']);
            }
            if (!empty($step['required_action'])) {
                $processedStep['required_action'] = trim($step['required_action']);
            }

            // Add approver-specific data based on type
            switch ($step['approver_type']) {
                case 'user':
                    if (!empty($step['approver_id'])) {
                        $processedStep['approver_id'] = (int) $step['approver_id'];
                    }
                    break;
                
                case 'role':
                    if (!empty($step['approver_role_id'])) {
                        $processedStep['approver_role_id'] = (int) $step['approver_role_id'];
                    }
                    break;
                
                case 'department_manager':
                    if (!empty($step['approver_department_id'])) {
                        $processedStep['approver_department_id'] = (int) $step['approver_department_id'];
                    }
                    break;
                
                case 'requester_department_manager':
                    // No extra fields needed; approver ditentukan dari departemen primary requester
                    break;

                case 'any_department_manager':
                    // No extra fields required; semua manager lintas departemen
                    break;
            }
            
            // Conditional threshold FS feature is deprecated.
            $processedStep['is_conditional'] = false;
            
            $processedStep['can_insert_step'] = false;

            // Add step type and phase (NEW)
            if (!empty($step['step_type'])) {
                $processedStep['step_type'] = $step['step_type'];
                // Auto-set phase based on type
                if ($step['step_type'] === 'releaser') {
                    $processedStep['step_phase'] = 'release';
                    // Releasers usually have 'release' action, but let's keep what user selected if any
                    if (empty($processedStep['required_action'])) {
                        $processedStep['required_action'] = 'release';
                    }
                } elseif ($step['step_type'] === 'purchasing') {
                    $processedStep['step_phase'] = 'purchasing';
                } else {
                    $processedStep['step_phase'] = 'approval';
                }
            } else {
                // Default to approver
                $processedStep['step_type'] = 'approver';
                $processedStep['step_phase'] = 'approval';
            }

            $processedSteps[] = $processedStep;
            $stepIndex++;
        }

        return $processedSteps;
    }

    /**
     * Get workflow steps for API
     */
    public function getSteps(ApprovalWorkflow $workflow)
    {
        return response()->json([
            'success' => true,
            'steps' => $workflow->workflow_steps
        ]);
    }
}
