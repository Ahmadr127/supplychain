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
            'workflow_steps' => 'required|array|min:1',
            'workflow_steps.*.name' => 'required|string|max:255',
            'workflow_steps.*.approver_type' => 'required|in:user,role,department_manager,department_level',
            'workflow_steps.*.approver_id' => 'nullable|exists:users,id',
            'workflow_steps.*.approver_role_id' => 'nullable|exists:roles,id',
            'workflow_steps.*.approver_department_id' => 'nullable|exists:departments,id',
            'workflow_steps.*.approver_level' => 'nullable|integer|min:1|max:5',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Process and clean workflow steps
        $workflowSteps = $this->processWorkflowSteps($request->workflow_steps);

        $workflow = ApprovalWorkflow::create([
            'name' => $request->name,
            'type' => $request->type,
            'description' => $request->description,
            'workflow_steps' => $workflowSteps,
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
            'workflow_steps' => 'required|array|min:1',
            'workflow_steps.*.name' => 'required|string|max:255',
            'workflow_steps.*.approver_type' => 'required|in:user,role,department_manager,department_level',
            'workflow_steps.*.approver_id' => 'nullable|exists:users,id',
            'workflow_steps.*.approver_role_id' => 'nullable|exists:roles,id',
            'workflow_steps.*.approver_department_id' => 'nullable|exists:departments,id',
            'workflow_steps.*.approver_level' => 'nullable|integer|min:1|max:5',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Process and clean workflow steps
        $workflowSteps = $this->processWorkflowSteps($request->workflow_steps);

        $approvalWorkflow->update([
            'name' => $request->name,
            'type' => $request->type,
            'description' => $request->description,
            'workflow_steps' => $workflowSteps,
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
                
                case 'department_level':
                    if (!empty($step['approver_level'])) {
                        $processedStep['approver_level'] = (int) $step['approver_level'];
                    }
                    break;
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
