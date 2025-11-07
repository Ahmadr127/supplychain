<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestItem;
use App\Models\ApprovalItemStep;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalItemStepController extends Controller
{
    /**
     * Show form to insert a new step (modal data)
     */
    public function showInsertForm(ApprovalRequestItem $item)
    {
        $currentStep = $item->getCurrentPendingStep();
        
        // Validasi: step harus bisa insert step
        if (!$currentStep || !$currentStep->can_insert_step) {
            return back()->withErrors(['error' => 'Step ini tidak memiliki izin untuk menambah step baru.']);
        }
        
        // Validasi: user harus bisa approve step ini
        if (!$currentStep->canApprove(auth()->id())) {
            return back()->withErrors(['error' => 'Anda tidak memiliki akses untuk menambah step.']);
        }
        
        // Return data untuk modal (atau bisa juga return view)
        return response()->json([
            'current_step' => $currentStep,
            'roles' => Role::orderBy('display_name')->get(),
            'users' => User::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }
    
    /**
     * Quick insert step using pre-configured template (checkbox only)
     */
    public function quickInsertStep(Request $request, ApprovalRequestItem $item)
    {
        $currentStep = $item->getCurrentPendingStep();
        
        // Validation
        if (!$currentStep || !$currentStep->can_insert_step) {
            return back()->withErrors(['error' => 'Step ini tidak memiliki izin untuk menambah step baru.']);
        }
        
        if (!$currentStep->canApprove(auth()->id())) {
            return back()->withErrors(['error' => 'Anda tidak memiliki akses untuk menambah step.']);
        }
        
        if (!$currentStep->insert_step_template) {
            return back()->withErrors(['error' => 'Template insert step belum dikonfigurasi untuk step ini.']);
        }
        
        if (in_array($item->status, ['approved', 'rejected'])) {
            return back()->withErrors(['error' => 'Item sudah diproses, tidak bisa menambah step baru.']);
        }
        
        try {
            DB::beginTransaction();
            
            $template = $currentStep->insert_step_template;
            
            // Use template data
            $stepData = [
                'step_name' => $template['name'],
                'approver_type' => $template['approver_type'],
                'approver_id' => $template['approver_id'] ?? null,
                'approver_role_id' => $template['approver_role_id'] ?? null,
                'approver_department_id' => $template['approver_department_id'] ?? null,
                'insertion_reason' => $request->input('reason', 'Diperlukan untuk proses approval'),
                'required_action' => $template['required_action'] ?? null,
                'can_insert_step' => $template['can_insert_step'] ?? false,
            ];
            
            $newStep = $this->insertStepAfter($item, $currentStep->step_number, $stepData);
            
            Log::info('✅ Quick insert step successful', [
                'item_id' => $item->id,
                'template_used' => $template['name'],
                'inserted_by' => auth()->id(),
            ]);
            
            DB::commit();
            
            return redirect()
                ->route('approval-requests.show', [
                    'approvalRequest' => $item->approval_request_id,
                    'item_id' => $item->id
                ])
                ->with('success', 'Step "' . $newStep->step_name . '" berhasil ditambahkan!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Quick insert step failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Gagal menambah step: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Insert a new approval step dynamically (manual form)
     */
    public function insertStep(Request $request, ApprovalRequestItem $item)
    {
        // Get current pending step
        $currentStep = $item->getCurrentPendingStep();
        
        // Validasi 1: Step harus ada dan pending
        if (!$currentStep) {
            return back()->withErrors(['error' => 'Tidak ada step yang sedang aktif untuk item ini.']);
        }
        
        // Validasi 2: Step harus memiliki permission untuk insert step
        if (!$currentStep->can_insert_step) {
            Log::warning('Attempt to insert step without permission', [
                'user_id' => auth()->id(),
                'step_id' => $currentStep->id,
                'step_name' => $currentStep->step_name,
            ]);
            
            return back()->withErrors([
                'error' => 'Step "' . $currentStep->step_name . '" tidak memiliki izin untuk menambah step baru.'
            ]);
        }
        
        // Validasi 3: User harus bisa approve current step
        if (!$currentStep->canApprove(auth()->id())) {
            return back()->withErrors(['error' => 'Anda tidak memiliki akses untuk menambah step pada item ini.']);
        }
        
        // Validasi 4: Item tidak boleh sudah approved/rejected
        if (in_array($item->status, ['approved', 'rejected'])) {
            return back()->withErrors(['error' => 'Item sudah diproses, tidak bisa menambah step baru.']);
        }
        
        // Validate input
        $validated = $request->validate([
            'step_name' => 'required|string|max:255',
            'approver_type' => 'required|in:user,role,department_manager,requester_department_manager,any_department_manager',
            'approver_id' => 'required_if:approver_type,user|nullable|exists:users,id',
            'approver_role_id' => 'required_if:approver_type,role|nullable|exists:roles,id',
            'approver_department_id' => 'required_if:approver_type,department_manager|nullable|exists:departments,id',
            'insertion_reason' => 'required|string|min:10|max:500',
            'required_action' => 'nullable|string|max:100',
            'can_insert_step' => 'nullable|boolean', // Inserted step bisa juga punya permission insert
        ]);
        
        try {
            DB::beginTransaction();
            
            // Insert step setelah current step
            $newStep = $this->insertStepAfter(
                $item, 
                $currentStep->step_number,
                $validated
            );
            
            // Log activity
            Log::info('✅ Dynamic step inserted successfully', [
                'approval_request_id' => $item->approval_request_id,
                'item_id' => $item->id,
                'master_item_id' => $item->master_item_id,
                'current_step_number' => $currentStep->step_number,
                'current_step_name' => $currentStep->step_name,
                'new_step_number' => $newStep->step_number,
                'new_step_name' => $newStep->step_name,
                'inserted_by' => auth()->id(),
                'inserted_by_name' => auth()->user()->name,
                'reason' => $validated['insertion_reason'],
            ]);
            
            DB::commit();
            
            return redirect()
                ->route('approval-requests.show', [
                    'approvalRequest' => $item->approval_request_id,
                    'item_id' => $item->id
                ])
                ->with('success', 'Step baru "' . $newStep->step_name . '" berhasil ditambahkan!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('❌ Failed to insert dynamic step', [
                'item_id' => $item->id,
                'current_step' => $currentStep->step_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return back()->withErrors(['error' => 'Gagal menambah step: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Insert step after specified step number with automatic renumbering
     */
    private function insertStepAfter(ApprovalRequestItem $item, int $afterStepNumber, array $data): ApprovalItemStep
    {
        Log::info('🔄 Starting step insertion and renumbering', [
            'item_id' => $item->id,
            'after_step' => $afterStepNumber,
        ]);
        
        // 1. Renumber all steps after insertion point
        $affectedRows = ApprovalItemStep::where('approval_request_id', $item->approval_request_id)
            ->where('master_item_id', $item->master_item_id)
            ->where('step_number', '>', $afterStepNumber)
            ->increment('step_number');
        
        Log::info('📊 Renumbered existing steps', [
            'affected_rows' => $affectedRows,
        ]);
        
        // 2. Create new step at position afterStepNumber + 1
        $newStep = ApprovalItemStep::create([
            'approval_request_id' => $item->approval_request_id,
            'master_item_id' => $item->master_item_id,
            'step_number' => $afterStepNumber + 1,
            'step_name' => $data['step_name'],
            'approver_type' => $data['approver_type'],
            'approver_id' => $data['approver_id'] ?? null,
            'approver_role_id' => $data['approver_role_id'] ?? null,
            'approver_department_id' => $data['approver_department_id'] ?? null,
            'status' => 'pending',
            'can_insert_step' => $data['can_insert_step'] ?? false, // Inherit permission if specified
            'is_dynamic' => true,
            'inserted_by' => auth()->id(),
            'inserted_at' => now(),
            'insertion_reason' => $data['insertion_reason'],
            'required_action' => $data['required_action'] ?? null,
        ]);
        
        Log::info('✨ New step created', [
            'new_step_id' => $newStep->id,
            'step_number' => $newStep->step_number,
            'step_name' => $newStep->step_name,
        ]);
        
        return $newStep;
    }
    
    /**
     * Delete a dynamic step (only if not yet processed)
     */
    public function deleteStep(ApprovalItemStep $step)
    {
        // Validasi: hanya dynamic step yang bisa dihapus
        if (!$step->is_dynamic) {
            return back()->withErrors(['error' => 'Hanya step yang ditambahkan secara dinamis yang bisa dihapus.']);
        }
        
        // Validasi: step belum diproses
        if ($step->status !== 'pending') {
            return back()->withErrors(['error' => 'Step yang sudah diproses tidak bisa dihapus.']);
        }
        
        // Validasi: hanya yang menambahkan atau admin yang bisa hapus
        if ($step->inserted_by !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return back()->withErrors(['error' => 'Anda tidak memiliki akses untuk menghapus step ini.']);
        }
        
        try {
            DB::beginTransaction();
            
            $stepNumber = $step->step_number;
            $itemId = $step->master_item_id;
            $requestId = $step->approval_request_id;
            
            // Delete step
            $step->delete();
            
            // Renumber steps after deleted step
            ApprovalItemStep::where('approval_request_id', $requestId)
                ->where('master_item_id', $itemId)
                ->where('step_number', '>', $stepNumber)
                ->decrement('step_number');
            
            Log::info('🗑️ Dynamic step deleted', [
                'step_id' => $step->id,
                'step_number' => $stepNumber,
                'deleted_by' => auth()->id(),
            ]);
            
            DB::commit();
            
            return back()->with('success', 'Step berhasil dihapus.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete step', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Gagal menghapus step.']);
        }
    }
}
