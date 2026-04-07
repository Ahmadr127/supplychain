<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestItem;
use App\Models\ApprovalItemStep;
use App\Models\PurchasingItem;
use App\Models\CapexItem;
use App\Services\CapexAllocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApprovalItemApprovalController extends Controller
{
    /**
     * Approve a specific item at current pending step
     */
    public function approve(Request $request, ApprovalRequest $approvalRequest, ApprovalRequestItem $item)
    {
        Log::info('🟦 ========== APPROVAL PROCESS STARTED ==========');
        Log::info('🟦 Request ID: ' . $approvalRequest->id);
        Log::info('🟦 Item ID: ' . $item->id);
        Log::info('🟦 User ID: ' . auth()->id());
        Log::info('🟦 User Name: ' . auth()->user()->name);
        
        // Get current pending step for validation
        $currentStep = $item->getCurrentPendingStep();
        
        if ($currentStep) {
            Log::info('🟨 Current Pending Step Found:');
            Log::info('🟨   - Step Number: ' . $currentStep->step_number);
            Log::info('🟨   - Step Name: ' . $currentStep->step_name);
            Log::info('🟨   - Step Status BEFORE: ' . $currentStep->status);
            Log::info('🟨   - Approver Type: ' . $currentStep->approver_type);
        } else {
            Log::warning('🟥 No pending step found for this item!');
        }
        // Dynamic validation based on step
        $rules = [
            'comments' => 'nullable|string|max:1000',
        ];
        
        // Step with required_action 'input_price': require price input if not set
        if ($currentStep && $currentStep->required_action == 'input_price' && ($item->unit_price === null || $item->unit_price <= 0)) {
            $rules['unit_price'] = 'required|string|min:1'; // Accept string with dots
            Log::info('🟨 Step requires price input (required_action: input_price)');
        }
        
        // Step with required_action 'verify_budget': require FS upload if total >= threshold
        if ($currentStep && $currentStep->required_action == 'verify_budget') {
            $totalPrice = $item->quantity * ($item->unit_price ?? 0);
            
            // Use step's condition_value as threshold if available, otherwise use global setting
            $fsThreshold = $currentStep->condition_value 
                ? $currentStep->condition_value 
                : \App\Models\Setting::get('fs_threshold_per_item', 100000000);
            
            Log::info('🟨 Budget Verification Step: Total Price = Rp ' . number_format($totalPrice, 0, ',', '.') . ' | Threshold = Rp ' . number_format($fsThreshold, 0, ',', '.') . ' | Step condition_value = ' . ($currentStep->condition_value ?? 'NULL'));
            
            if ($totalPrice >= $fsThreshold) {
                $rules['fs_document'] = 'required|file|mimes:pdf,doc,docx|max:5120';
                Log::info('🟨 FS Document upload required (total >= threshold)');
            }
        }
        
        $validated = $request->validate($rules);
        Log::info('🟩 Validation passed');
        
        // Additional validation for price input
        if (isset($validated['unit_price'])) {
            $cleanPrice = (float) str_replace('.', '', $validated['unit_price']);
            if ($cleanPrice <= 0) {
                Log::error('🟥 Price validation failed: price <= 0');
                return back()->withErrors(['unit_price' => 'Harga harus lebih dari 0'])->withInput();
            }
            Log::info('🟩 Price validated: Rp ' . number_format($cleanPrice, 0, ',', '.'));
        }

        try {
            DB::beginTransaction();
            Log::info('🟦 Database transaction started');

            // Get current pending step for this item
            $currentStep = $item->getCurrentPendingStep();

            if (!$currentStep) {
                return back()->withErrors(['error' => 'Tidak ada step yang perlu di-approve untuk item ini.']);
            }

            // Check authorization
            if (!$currentStep->canApprove(auth()->id())) {
                Log::error('🟥 Authorization failed: User cannot approve this step');
                return back()->withErrors(['error' => 'Anda tidak memiliki akses untuk approve item ini.']);
            }
            Log::info('🟩 Authorization passed');
            
            // Manager step: Save price input (based on required_action)
            if ($currentStep->required_action == 'input_price') {


                $unitPrice = 0;
                $capexItemId = null;

                // Check price source: Capex or Manual
                if ($request->has('capex_item_id') && $request->capex_item_id) {
                    // Option A: From Capex
                    $capexItemId = $request->capex_item_id;
                    $capexItem = CapexItem::findOrFail($capexItemId);

                    if ($request->has('unit_price')) {
                        $unitPrice = (float) str_replace('.', '', $request->unit_price);
                    }

                    Log::info('🟦 Selected Capex Item: ' . $capexItemId);
                } else {
                    // Option B: Manual Input (Non-Capex)
                    if ($request->has('unit_price')) {
                        $unitPrice = (float) str_replace('.', '', $request->unit_price);
                    }
                }

                if ($unitPrice <= 0) {
                    DB::rollBack();
                    Log::error('🟥 Price <= 0, rolling back transaction');
                    return back()->withErrors(['unit_price' => 'Harga harus lebih dari 0'])->withInput();
                }

                // Validasi budget CapEx sebelum update
                if ($capexItemId) {
                    $capexItem = $capexItem ?? CapexItem::findOrFail($capexItemId);
                    $totalForCapex = (int) ($itemData['quantity'] ?? $item->quantity) * $unitPrice;
                    $allocationService = app(CapexAllocationService::class);
                    if (!$allocationService->hasSufficientBudget($capexItem, $totalForCapex)) {
                        DB::rollBack();
                        $available = $allocationService->getAvailableBudget($capexItem);
                        Log::error('🟥 Budget CapEx tidak mencukupi', [
                            'capex_item_id' => $capexItemId,
                            'requested'     => $totalForCapex,
                            'available'     => $available,
                        ]);
                        return back()->withErrors([
                            'capex_item_id' => 'Budget CapEx tidak mencukupi. Tersedia: Rp ' . number_format($available, 0, ',', '.'),
                        ])->withInput();
                    }
                }

                Log::info('🟦 Updating item with price and capex...');
                $item->update([
                    'unit_price'         => $unitPrice,
                    'total_price'        => $item->quantity * $unitPrice,
                    'capex_item_id'      => $capexItemId,
                    'approved_price_by'  => auth()->id(),
                    'approved_price_at'  => now(),
                ]);

                // Reserve budget CapEx
                if ($capexItemId) {
                    $allocationService = app(CapexAllocationService::class);
                    $item->refresh();
                    $reserved = $allocationService->reserve(
                        CapexItem::find($capexItemId),
                        $item,
                        (float) $item->total_price,
                        auth()->id()
                    );
                    if (!$reserved) {
                        Log::warning('⚠️ Gagal reserve budget CapEx (mungkin race condition)', [
                            'capex_item_id' => $capexItemId,
                        ]);
                    }
                }
                
                Log::info('🟩 Manager approved with price:', [
                    'item_id' => $item->id,
                    'unit_price' => $unitPrice,
                    'capex_item_id' => $capexItemId,
                    'total_price' => $item->quantity * $unitPrice,
                ]);


            }
            
            // Save FS document if step requires budget verification
            if ($currentStep->required_action == 'verify_budget' && $request->hasFile('fs_document')) {
                Log::info('🟦 Uploading FS document for budget verification...');
                $fsPath = $request->file('fs_document')->store('fs_documents', 'public');
                $item->update(['fs_document' => $fsPath]);
                
                Log::info('🟩 FS document uploaded:', [
                    'item_id' => $item->id,
                    'fs_document' => $fsPath,
                    'step_name' => $currentStep->step_name,
                ]);
            }

            // Mark current step as approved
            Log::info('🟦 Updating step status to approved...');
            Log::info('🟦 Step ID: ' . $currentStep->id);
            Log::info('🟦 Step Status BEFORE update: ' . $currentStep->status);
            Log::info('🟦 Comments from request: ' . ($request->comments ?? 'NULL'));
            Log::info('🟦 All request data: ', $request->all());
            
            $currentStep->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'comments' => $request->comments,
            ]);
            
            // Refresh model to get updated data
            $currentStep->refresh();
            
            Log::info('🟩 Step status AFTER update: ' . $currentStep->status);
            Log::info('🟩 Step approved_by: ' . $currentStep->approved_by);
            Log::info('🟩 Step approved_at: ' . $currentStep->approved_at);

            Log::info('🟩 Item step approved successfully', [
                'item_id' => $item->id,
                'step_number' => $currentStep->step_number,
                'step_name' => $currentStep->step_name,
                'approver_id' => auth()->id(),
            ]);

            // Re-evaluate workflow based on new price (if applicable)
            // Must be done AFTER approval to ensure current step is marked approved and not deleted
            
            Log::info('🔍 Checking if workflow re-evaluation is needed', [
                'step_id' => $currentStep->id,
                'required_action' => $currentStep->required_action,
                'item_total_price' => $item->total_price
            ]);

            if ($currentStep->required_action == 'input_price') {
                Log::info('🚀 Triggering workflow re-evaluation...');
                try {
                    $workflowService = app(\App\Services\WorkflowService::class);
                    $workflowService->reevaluateWorkflow($item);
                    // IMPORTANT:
                    // Re-evaluation may regenerate steps (and can change step_number),
                    // so don't blindly refresh (it can throw "No query results..." if row moved/deleted).
                    // Re-fetch safely instead.
                    try {
                        $refetched = ApprovalItemStep::find($currentStep->id);
                        if ($refetched) {
                            $currentStep = $refetched;
                        } else {
                            $currentStep = ApprovalItemStep::where('approval_request_id', $approvalRequest->id)
                                ->where('approval_request_item_id', $item->id)
                                ->where('status', 'approved')
                                ->orderBy('step_number', 'desc')
                                ->first() ?? $currentStep;
                        }
                    } catch (ModelNotFoundException $e) {
                        // Keep using the in-memory $currentStep; flow below uses step_number primarily.
                        Log::warning('⚠️ Step refresh skipped after workflow re-evaluation', [
                            'step_id' => $currentStep->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Workflow re-evaluation failed', ['error' => $e->getMessage()]);
                    throw $e; // Re-throw to rollback transaction
                }
            } else {
                Log::info('⏭️ Skipping workflow re-evaluation (action not input_price)');
            }
            
            // Handle quick insert step (if checkbox checked)
            if ($request->has('quick_insert_step') && $currentStep->insert_step_template) {
                Log::info('🟨 Quick insert step requested');
                $this->handleQuickInsertStep($item, $currentStep);
            }

            // ─── SEQUENTIAL STEP ACTIVATION: Find next step by step_number ───
            // step_phase is now metadata only — routing follows step_number order.
            Log::info('🟦 Finding next step after step: ' . $currentStep->step_number);

            $nextStep = ApprovalItemStep::where('approval_request_id', $approvalRequest->id)
                ->where('approval_request_item_id', $item->id)
                ->where('step_number', '>', $currentStep->step_number)
                ->orderBy('step_number')
                ->first();

            if (!$nextStep) {
                // No more steps → item is fully approved
                Log::info('🟩 No more steps → item FULLY APPROVED!');
                $item->update([
                    'status'      => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
                $item->refresh();

                if ($item->capex_item_id) {
                    app(CapexAllocationService::class)->confirmAllocation($item);
                    Log::info('🟩 CapEx allocation confirmed');
                }

            } elseif ($nextStep->step_type === 'purchasing') {
                // Next step is purchasing → trigger purchasing
                Log::info('🟨 Next step is PURCHASING → activating purchasing step & creating item');
                $nextStep->update(['status' => 'pending']);
                $item->update(['status' => 'in_purchasing']);
                $item->refresh();
                $this->createPurchasingItem($item);

            } else {
                // Next step is any other type (approver, releaser, post-purchasing approver, etc.)
                Log::info('🟨 Activating step ' . $nextStep->step_number . ' - ' . $nextStep->step_name . ' (' . ($nextStep->step_type ?? 'approver') . ')');
                $nextStep->update(['status' => 'pending']);

                $itemStatus = ($nextStep->step_type === 'releaser') ? 'in_release' : 'on progress';
                $item->update(['status' => $itemStatus]);
                $item->refresh();
                Log::info('🟩 Item status set to: ' . $itemStatus);

                app(\App\Services\NotificationService::class)->notifyApprovers($approvalRequest);
            }

            // Aggregate request status
            Log::info('🟦 Aggregating request status...');
            $approvalRequest->refreshStatus();
            
            $approvalRequest->refresh();
            Log::info('🟩 Request status: ' . $approvalRequest->status);

            if ($approvalRequest->status === 'approved') {
                app(\App\Services\NotificationService::class)->notifyRequesterApproved($approvalRequest);
            }

            DB::commit();
            Log::info('🟩 Database transaction committed');
            Log::info('🟩 ========== APPROVAL PROCESS COMPLETED ==========');

            // Redirect to same page with item_id to keep viewing single item
            return redirect()->route('approval-requests.show', ['approvalRequest' => $approvalRequest->id, 'item_id' => $item->id])
                ->with('success', 'Item berhasil di-approve!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('🟥 ========== APPROVAL PROCESS FAILED ==========');
            Log::error('🟥 Item approval failed', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Gagal approve item: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject a specific item at current pending step
     */
    public function reject(Request $request, ApprovalRequest $approvalRequest, ApprovalRequestItem $item)
    {
        $request->validate([
            'comments' => 'required|string|max:1000',
            'rejected_reason' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Get current pending step
            $currentStep = $item->getCurrentPendingStep();

            if (!$currentStep) {
                return back()->withErrors(['error' => 'Tidak ada step yang perlu di-reject untuk item ini.']);
            }

            // Check authorization
            if (!$currentStep->canApprove(auth()->id())) {
                return back()->withErrors(['error' => 'Anda tidak memiliki akses untuk reject item ini.']);
            }

            // Mark current step as rejected
            $currentStep->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejected_reason' => $request->rejected_reason,
                'comments' => $request->comments,
            ]);

            // Mark item as rejected
            $item->update([
                'status'          => 'rejected',
                'approved_by'     => auth()->id(),
                'approved_at'     => now(),
                'rejected_reason' => $request->rejected_reason,
            ]);

            // Release CapEx reservation jika ada
            if ($item->capex_item_id) {
                app(CapexAllocationService::class)->releaseReservation(
                    $item,
                    'Item ditolak: ' . $request->rejected_reason
                );
                Log::info('🟩 CapEx reservation released (item rejected)', [
                    'capex_item_id' => $item->capex_item_id,
                    'item_id'       => $item->id,
                ]);
            }

            Log::info('Item rejected', [
                'item_id' => $item->id,
                'step_number' => $currentStep->step_number,
                'approver_id' => auth()->id(),
                'reason' => $request->rejected_reason,
            ]);

            // Aggregate request status (will mark request as rejected if any item rejected)
            $approvalRequest->refreshStatus();
            $approvalRequest->refresh();
            
            if ($approvalRequest->status === 'rejected') {
                app(\App\Services\NotificationService::class)->notifyRequesterRejected($approvalRequest, $request->rejected_reason);
            }

            DB::commit();

            // Redirect to same page with item_id to keep viewing single item
            return redirect()->route('approval-requests.show', ['approvalRequest' => $approvalRequest->id, 'item_id' => $item->id])
                ->with('success', 'Item berhasil di-reject.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Item rejection failed', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Gagal reject item: ' . $e->getMessage()]);
        }
    }

    /**
     * Create purchasing item when item is approved
     */
    private function createPurchasingItem(ApprovalRequestItem $item): void
    {
        // Check if purchasing item already exists
        $exists = PurchasingItem::where('approval_request_id', $item->approval_request_id)
            ->where('master_item_id', $item->master_item_id)
            ->exists();

        if (!$exists) {
            PurchasingItem::create([
                'approval_request_id' => $item->approval_request_id,
                'master_item_id' => $item->master_item_id,
                'quantity' => $item->quantity,
                'status' => 'unprocessed',
            ]);

            Log::info('Purchasing item created', [
                'item_id' => $item->id,
                'master_item_id' => $item->master_item_id,
            ]);
        }
    }

    /**
     * Set item step back to pending (reset approval)
     */
    public function setPending(Request $request, ApprovalRequest $approvalRequest, ApprovalRequestItem $item)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Get current pending step
            $currentStep = $item->getCurrentPendingStep();

            if (!$currentStep) {
                return back()->withErrors(['error' => 'Tidak ada step yang dapat di-reset untuk item ini.']);
            }

            // Check authorization - only approver can reset
            if (!$currentStep->canApprove(auth()->id())) {
                return back()->withErrors(['error' => 'Anda tidak memiliki akses untuk reset item ini.']);
            }

            // Reset current step to pending
            $currentStep->update([
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'comments' => 'Reset to pending: ' . $request->reason,
            ]);

            // Update item status to pending
            $item->update([
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'rejected_reason' => null,
            ]);

            Log::info('Item reset to pending', [
                'item_id' => $item->id,
                'step_number' => $currentStep->step_number,
                'reset_by' => auth()->id(),
                'reason' => $request->reason,
            ]);

            // Aggregate request status
            $approvalRequest->refreshStatus();

            DB::commit();

            // Redirect to same page with item_id to keep viewing single item
            return redirect()->route('approval-requests.show', ['approvalRequest' => $approvalRequest->id, 'item_id' => $item->id])
                ->with('success', 'Item berhasil di-reset ke status pending.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Item reset to pending failed', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Gagal reset item: ' . $e->getMessage()]);
        }
    }


    
    /**
     * Handle quick insert step using template
     */
    private function handleQuickInsertStep(ApprovalRequestItem $item, ApprovalItemStep $currentStep): void
    {
        try {
            $template = $currentStep->insert_step_template;
            
            // Renumber existing steps after current step
            ApprovalItemStep::where('approval_request_id', $item->approval_request_id)
                ->where('approval_request_item_id', $item->id)
                ->where('step_number', '>', $currentStep->step_number)
                ->increment('step_number');
            
            // Create new step from template
            ApprovalItemStep::create([
                'approval_request_id' => $item->approval_request_id,
                'approval_request_item_id' => $item->id,
                'master_item_id' => $item->master_item_id,
                'step_number' => $currentStep->step_number + 1,
                'step_name' => $template['name'],
                'approver_type' => $template['approver_type'],
                'approver_id' => $template['approver_id'] ?? null,
                'approver_role_id' => $template['approver_role_id'] ?? null,
                'approver_department_id' => $template['approver_department_id'] ?? null,
                'status' => 'pending',
                'can_insert_step' => $template['can_insert_step'] ?? false,
                'insert_step_template' => $template['insert_step_template'] ?? null,
                'is_dynamic' => true,
                'inserted_by' => auth()->id(),
                'inserted_at' => now(),
                'insertion_reason' => 'Ditambahkan via quick insert oleh ' . auth()->user()->name,
                'required_action' => $template['required_action'] ?? null,
                'condition_value' => $template['condition_value'] ?? null,
            ]);
            
            Log::info('✅ Quick insert step created', [
                'item_id' => $item->id,
                'template_name' => $template['name'],
                'inserted_by' => auth()->id(),
                'required_action' => $template['required_action'] ?? null,
                'condition_value' => $template['condition_value'] ?? null,
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Quick insert step failed', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - continue with normal approval flow
        }
    }
}
