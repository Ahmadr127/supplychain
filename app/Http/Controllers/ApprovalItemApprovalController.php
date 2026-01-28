<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestItem;
use App\Models\ApprovalItemStep;
use App\Models\PurchasingItem;
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
                    $capexItem = \App\Models\CapexItem::findOrFail($capexItemId);
                    
                    // If unit_price is provided (manual override or confirmation), use it. 
                    // Otherwise could default to something else, but here we expect user to input price 
                    // even if capex is selected (to confirm how much of the budget is used).
                    // However, the requirement says "bisa memilih id item capex... atau menggunakan inputan harga manual".
                    // Usually, selecting Capex means we are allocating THIS item to THAT budget. 
                    // The price itself is still the price of the item.
                    
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
                
                Log::info('🟦 Updating item with price and capex...');
                $item->update([
                    'unit_price' => $unitPrice,
                    'total_price' => $item->quantity * $unitPrice,
                    'capex_item_id' => $capexItemId,
                    'approved_price_by' => auth()->id(),
                    'approved_price_at' => now(),
                ]);
                
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
            if ($currentStep->required_action == 'input_price') {
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
                                ->where('master_item_id', $item->master_item_id)
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
                }
            }
            
            // Handle quick insert step (if checkbox checked)
            if ($request->has('quick_insert_step') && $currentStep->insert_step_template) {
                Log::info('🟨 Quick insert step requested');
                $this->handleQuickInsertStep($item, $currentStep);
            }

            // Determine if current step is in release phase
            $isReleasePhase = ($currentStep->step_phase ?? 'approval') === 'release';
            
            if ($isReleasePhase) {
                // RELEASE PHASE: Check for next release step
                Log::info('🟦 Current step is RELEASE PHASE - checking for next release steps...');
                
                $nextReleaseStep = ApprovalItemStep::where('approval_request_id', $approvalRequest->id)
                    ->where('master_item_id', $item->master_item_id)
                    ->where('step_number', '>', $currentStep->step_number)
                    ->where('step_phase', 'release')
                    ->whereIn('status', ['pending', 'pending_purchase'])
                    ->orderBy('step_number')
                    ->first();

                if (!$nextReleaseStep) {
                    // All RELEASE steps complete - Item is FULLY APPROVED!
                    Log::info('🟩 All release steps complete - item FULLY APPROVED!');
                    Log::info('🟦 Item status BEFORE: ' . $item->status);
                    
                    $item->update([
                        'status' => 'approved',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);
                    
                    $item->refresh();
                    Log::info('🟩 Item status AFTER: ' . $item->status);
                    Log::info('🟩 Item FULLY APPROVED (3-phase workflow complete)', ['item_id' => $item->id]);
                    
                } else {
                    // Activate next release step
                    Log::info('🟨 Next release step found: Step ' . $nextReleaseStep->step_number . ' - ' . $nextReleaseStep->step_name);
                    
                    // If next step is pending_purchase, activate it
                    if ($nextReleaseStep->status === 'pending_purchase') {
                        $nextReleaseStep->update(['status' => 'pending']);
                        Log::info('🟩 Next release step activated');
                    }
                    
                    $item->update(['status' => 'in_release']);
                    $item->refresh();
                    Log::info('🟩 Item status: ' . $item->status);
                }
                
            } else {
                // APPROVAL PHASE: Check for next approval step
                Log::info('🟦 Current step is APPROVAL PHASE - checking for next approval steps...');
                
                // Get next pending step in approval phase
                $nextApprovalStep = ApprovalItemStep::where('approval_request_id', $approvalRequest->id)
                    ->where('master_item_id', $item->master_item_id)
                    ->where('step_number', '>', $currentStep->step_number)
                    ->where('status', 'pending')
                    ->where(function($q) {
                        $q->where('step_phase', 'approval')
                          ->orWhereNull('step_phase');
                    })
                    ->orderBy('step_number')
                    ->first();

                if (!$nextApprovalStep) {
                    // All APPROVAL PHASE steps are complete
                    // Check if there are RELEASE PHASE steps waiting
                    $hasReleaseSteps = ApprovalItemStep::where('approval_request_id', $approvalRequest->id)
                        ->where('master_item_id', $item->master_item_id)
                        ->where('step_phase', 'release')
                        ->exists();

                    if ($hasReleaseSteps) {
                        // 3-Phase workflow: All approval done → Go to Purchasing
                        Log::info('🟨 All approval steps complete - item entering PURCHASING phase');
                        Log::info('🟦 Item status BEFORE: ' . $item->status);
                        
                        $item->update([
                            'status' => 'in_purchasing', // New status: waiting for purchasing
                        ]);
                        
                        $item->refresh();
                        Log::info('🟩 Item status AFTER: ' . $item->status);

                        // Create purchasing item immediately
                        $this->createPurchasingItem($item);

                        Log::info('🟩 Item approved for purchasing, awaiting vendor selection', ['item_id' => $item->id]);
                        
                        // Note: Release steps will be activated when purchasing is complete (vendor selected)
                        // This is handled in PurchasingItemService::activateReleaseSteps()
                        
                    } else {
                        // Old workflow (no release steps) - mark as fully approved immediately
                        Log::info('🟨 No release steps - marking item as fully approved');
                        Log::info('🟦 Item status BEFORE: ' . $item->status);
                        
                        $item->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                        
                        $item->refresh();
                        Log::info('🟩 Item status AFTER: ' . $item->status);

                        // Create purchasing item immediately
                        $this->createPurchasingItem($item);

                        Log::info('🟩 Item fully approved', ['item_id' => $item->id]);
                    }
                } else {
                    // Move to next approval step
                    Log::info('🟨 Next approval step found: Step ' . $nextApprovalStep->step_number . ' - ' . $nextApprovalStep->step_name);
                    Log::info('🟦 Item status BEFORE: ' . $item->status);
                    
                    $item->update(['status' => 'on progress']);
                    
                    $item->refresh();
                    Log::info('🟩 Item status AFTER: ' . $item->status);
                }
            }

            // Aggregate request status
            Log::info('🟦 Aggregating request status...');
            $this->aggregateRequestStatus($approvalRequest);
            
            $approvalRequest->refresh();
            Log::info('🟩 Request status: ' . $approvalRequest->status);

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
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejected_reason' => $request->rejected_reason,
            ]);

            Log::info('Item rejected', [
                'item_id' => $item->id,
                'step_number' => $currentStep->step_number,
                'approver_id' => auth()->id(),
                'reason' => $request->rejected_reason,
            ]);

            // Aggregate request status (will mark request as rejected if any item rejected)
            $this->aggregateRequestStatus($approvalRequest);

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
            $this->aggregateRequestStatus($approvalRequest);

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
     * Aggregate item statuses to update request status
     */
    private function aggregateRequestStatus(ApprovalRequest $approvalRequest): void
    {
        $items = $approvalRequest->items;

        // If ANY item is rejected, mark request as rejected
        if ($items->contains('status', 'rejected')) {
            $approvalRequest->update(['status' => 'rejected']);
            Log::info('Request marked as rejected', ['request_id' => $approvalRequest->id]);
            return;
        }

        // If ALL items are approved, mark request as approved
        if ($items->every(fn($i) => $i->status === 'approved')) {
            $approvalRequest->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            Log::info('Request fully approved', ['request_id' => $approvalRequest->id]);
            return;
        }

        // If ANY item is pending or on progress, keep request as on progress
        if ($items->some(fn($i) => in_array($i->status, ['pending', 'on progress']))) {
            $approvalRequest->update(['status' => 'on progress']);
            return;
        }

        // Default: pending
        $approvalRequest->update(['status' => 'pending']);
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
                ->where('master_item_id', $item->master_item_id)
                ->where('step_number', '>', $currentStep->step_number)
                ->increment('step_number');
            
            // Create new step from template
            ApprovalItemStep::create([
                'approval_request_id' => $item->approval_request_id,
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
