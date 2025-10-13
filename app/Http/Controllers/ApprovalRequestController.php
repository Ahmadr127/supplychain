<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalStep;
use App\Models\MasterItem;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\ItemResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ApprovalRequest::with(['workflow', 'requester', 'currentStep', 'steps.approver', 'steps.approverRole', 'steps.approverDepartment', 'submissionType']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhereHas('submissionType', function($st) use ($search) {
                      $st->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('requester', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }
        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Workflow filter
        if ($request->filled('workflow_id')) {
            $query->where('workflow_id', $request->workflow_id);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $requests = $query->latest()->paginate(10)->withQueryString();
        $workflows = ApprovalWorkflow::where('is_active', true)->get();
        
        return view('approval-requests.index', compact('requests', 'workflows'));
    }

    public function create()
    {
        // Get all active item types for radio button selection
        $itemTypes = \App\Models\ItemType::where('is_active', true)->get();
        // Get submission types
        $submissionTypes = \App\Models\SubmissionType::where('is_active', true)->orderBy('name')->get();
        
        // Get default general workflow (not specific to any item type)
        $defaultWorkflow = ApprovalWorkflow::where('is_active', true)
            ->where('is_specific_type', false)
            ->where('type', 'standard')
            ->first();
            
        if (!$defaultWorkflow) {
            // If no general workflow exists, get the first active workflow
            $defaultWorkflow = ApprovalWorkflow::where('is_active', true)->first();
        }
        
        if (!$defaultWorkflow) {
            abort(500, 'Tidak ada workflow yang tersedia. Silakan hubungi administrator.');
        }
        
        // Do not prefill old-format preview number; use JS preview based on item type code
        $previewRequestNumber = null;

        // Get all master items for search
        $masterItems = MasterItem::active()->with(['itemType', 'itemCategory', 'commodity', 'unit'])->get();
        
        // Add dropdown data for the modal
        $itemCategories = \App\Models\ItemCategory::where('is_active', true)->get();
        $commodities = \App\Models\Commodity::where('is_active', true)->get();
        $units = \App\Models\Unit::where('is_active', true)->get();
        // Load submission types for the edit view (used in the form)
        $submissionTypes = \App\Models\SubmissionType::where('is_active', true)->orderBy('name')->get();
        
        return view('approval-requests.create', compact('defaultWorkflow', 'masterItems', 'itemTypes', 'itemCategories', 'commodities', 'units', 'submissionTypes', 'previewRequestNumber'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'workflow_id' => 'required|exists:approval_workflows,id',
            'item_type_id' => 'required|exists:item_types,id',
            'submission_type_id' => 'required|exists:submission_types,id',
            'request_number' => 'nullable|string|max:255|unique:approval_requests,request_number',
            'items' => 'required|array',
            'items.*.master_item_id' => 'nullable|exists:master_items,id',
            'items.*.name' => 'required_without:items.*.master_item_id|string|max:255',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            // Make unit_price optional; fallback to master item price when missing or <= 0
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.item_category_id' => 'nullable|integer|exists:item_categories,id',
            'items.*.item_category_name' => 'nullable|string|max:255',
            'items.*.specification' => 'nullable|string',
            'items.*.brand' => 'nullable|string|max:100',
            'items.*.supplier_id' => 'nullable|exists:suppliers,id',
            'items.*.supplier_name' => 'nullable|string|max:255',
            'items.*.alternative_vendor' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string|max:500',
            'items.*.files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:20480'
        ]);

        // Additional conditional validation: new items must include a category
        $validator->after(function($v) use ($request) {
            $items = $request->input('items', []);
            foreach ($items as $idx => $row) {
                $hasId = !empty($row['master_item_id']);
                $hasName = !empty($row['name']);
                if (!$hasId && $hasName) {
                    $hasCat = !empty($row['item_category_id']) || !empty($row['item_category_name']);
                    if (!$hasCat) {
                        $v->errors()->add("items.$idx.item_category", 'Kategori wajib diisi untuk item baru.');
                    }
                }
            }
        });

        if ($validator->fails()) {
            Log::warning('ApprovalRequest.update.validation_failed', [
                'request_id' => $approvalRequest->id,
                'auth_id' => auth()->id(),
                'errors' => $validator->errors()->toArray(),
            ]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Ensure at least one valid item row is provided (has id or name)
        $itemsInput = $request->input('items', []);
        $validItems = array_filter($itemsInput, function ($row) {
            $hasId = !empty($row['master_item_id']);
            $hasName = !empty($row['name']);
            return $hasId || $hasName;
        });
        if (count($validItems) === 0) {
            return redirect()->back()
                ->withErrors(['items' => 'Minimal 1 item harus diisi.'])
                ->withInput();
        }

        $workflow = ApprovalWorkflow::findOrFail($request->workflow_id);

        // Determine specific type from selected workflow
        $isSpecificType = (bool) $workflow->is_specific_type;

        // Generate simple request number using ItemType code (e.g., PM-1, PNM-1) per type
        $requestNumber = $request->request_number;
        if (empty($requestNumber)) {
            $itemType = \App\Models\ItemType::findOrFail((int) $request->item_type_id);
            $code = $itemType->code ?: 'REQ';

            // Calculate next sequence per code in a safe manner
            $lastNumber = ApprovalRequest::where('request_number', 'like', $code . '-%')
                ->latest('id')
                ->value('request_number');
            $next = 1;
            if ($lastNumber && preg_match('/^' . preg_quote($code, '/') . '-(\d+)$/', $lastNumber, $m)) {
                $next = (int) $m[1] + 1;
            }
            $requestNumber = $code . '-' . $next;
        }
        
        $approvalRequest = $workflow->createRequest(
            requesterId: auth()->id(),
            submissionTypeId: $request->submission_type_id,
            description: $request->description,
            requestNumber: $requestNumber,
            priority: 'normal',
            isCtoRequest: false
        );

        // Update approval request with item type information
        $approvalRequest->update([
            'item_type_id' => $request->item_type_id,
            'submission_type_id' => $request->submission_type_id,
            'is_specific_type' => $isSpecificType
        ]);

        // Handle items (by ID or by name)
        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $itemData) {
                // Skip empty rows
                $hasId = !empty($itemData['master_item_id']);
                $hasName = !empty($itemData['name']);
                if (!$hasId && !$hasName) continue;

                $masterItemId = $hasId
                    ? (int) $itemData['master_item_id']
                    : $this->resolveOrCreateMasterItem(
                        $itemData['name'],
                        (int) $request->item_type_id,
                        [
                            'item_category_id' => $itemData['item_category_id'] ?? null,
                            'item_category_name' => $itemData['item_category_name'] ?? null,
                        ]
                    );

                $masterItem = MasterItem::findOrFail($masterItemId);
                // Do not fallback to master item price; keep unit_price nullable if not provided
                $unitPrice = isset($itemData['unit_price']) && $itemData['unit_price'] !== ''
                    ? (float) $itemData['unit_price']
                    : null;
                $quantity = (int) ($itemData['quantity'] ?? 1);
                $totalPrice = $unitPrice !== null ? ($quantity * $unitPrice) : null;

                // Resolve supplier if only name provided
                $supplierId = isset($itemData['supplier_id']) ? (int) $itemData['supplier_id'] : null;
                if (!$supplierId && !empty($itemData['supplier_name'])) {
                    $supplierId = $this->resolveOrCreateSupplierByName($itemData['supplier_name']);
                }

                $approvalRequest->masterItems()->attach($masterItemId, [
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'notes' => $itemData['notes'] ?? null,
                    'specification' => $itemData['specification'] ?? null,
                    'brand' => $itemData['brand'] ?? null,
                    'supplier_id' => $supplierId,
                    'alternative_vendor' => $itemData['alternative_vendor'] ?? null,
                ]);

                // Store per-item files if any
                if (isset($itemData['files']) && is_array($itemData['files'])) {
                    foreach ($itemData['files'] as $uploaded) {
                        if (!$uploaded) continue;
                        $path = $uploaded->store('approval_items');
                        \DB::table('approval_request_item_files')->insert([
                            'approval_request_id' => $approvalRequest->id,
                            'master_item_id' => $masterItemId,
                            'original_name' => $uploaded->getClientOriginalName(),
                            'path' => $path,
                            'mime' => $uploaded->getClientMimeType(),
                            'size' => $uploaded->getSize(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // Upload lampiran dinonaktifkan

        return redirect()->route('approval-requests.my-requests')
                        ->with('success', 'Approval request berhasil dibuat!');
    }

    public function show(ApprovalRequest $approvalRequest)
    {
        // Allow all authenticated users to view approval requests

        $approvalRequest->load([
            'workflow', 
            'requester', 
            'steps.approver', 
            'steps.approverRole', 
            'steps.approverDepartment',
            'steps.approvedBy',
            'masterItems.itemType',
            'masterItems.itemCategory',
            'masterItems.commodity',
            'masterItems.unit',
            'submissionType'
        ]);

        // Load item files grouped by master_item_id to show per item
        $files = \DB::table('approval_request_item_files')
            ->where('approval_request_id', $approvalRequest->id)
            ->get()
            ->groupBy('master_item_id');
        
        return view('approval-requests.show', [
            'approvalRequest' => $approvalRequest,
            'itemFiles' => $files,
        ]);
    }

    public function edit(ApprovalRequest $approvalRequest)
    {
        // Log attempt
        Log::info('ApprovalRequest.edit.attempt', [
            'request_id' => $approvalRequest->id,
            'status' => $approvalRequest->status,
            'requester_id' => $approvalRequest->requester_id,
            'auth_id' => auth()->id(),
        ]);

        // Only allow edit if status is pending or on progress and user is the requester
        if (($approvalRequest->status !== 'pending' && $approvalRequest->status !== 'on progress') || (int)$approvalRequest->requester_id !== (int)auth()->id()) {
            Log::warning('ApprovalRequest.edit.denied', [
                'request_id' => $approvalRequest->id,
                'status' => $approvalRequest->status,
                'requester_id' => $approvalRequest->requester_id,
                'auth_id' => auth()->id(),
                'reason' => 'status_or_owner_mismatch',
            ]);
            abort(403, 'Anda tidak memiliki akses untuk mengedit request ini.');
        }

        Log::info('ApprovalRequest.edit.allowed', [
            'request_id' => $approvalRequest->id,
            'auth_id' => auth()->id(),
        ]);

        // Get all active item types for radio button selection
        $itemTypes = \App\Models\ItemType::where('is_active', true)->get();
        
        // Get workflow based on item type or default general workflow
        $defaultWorkflow = null;
        if ($approvalRequest->item_type_id) {
            // Try to get specific workflow for this item type
            $defaultWorkflow = ApprovalWorkflow::where('is_active', true)
                ->where('item_type_id', $approvalRequest->item_type_id)
                ->where('is_specific_type', true)
                ->first();
        }
        
        // If no specific workflow found, get general workflow
        if (!$defaultWorkflow) {
            $defaultWorkflow = ApprovalWorkflow::where('is_active', true)
                ->where('is_specific_type', false)
                ->where('type', 'standard')
                ->first();
                
            if (!$defaultWorkflow) {
                // If no general workflow exists, get the first active workflow
                $defaultWorkflow = ApprovalWorkflow::where('is_active', true)->first();
            }
        }
        
        if (!$defaultWorkflow) {
            abort(500, 'Tidak ada workflow yang tersedia. Silakan hubungi administrator.');
        }
        
        $masterItems = MasterItem::active()->with(['itemType', 'itemCategory', 'commodity', 'unit'])->get();
        
        // Add dropdown data for the modal
        $itemCategories = \App\Models\ItemCategory::where('is_active', true)->get();
        $commodities = \App\Models\Commodity::where('is_active', true)->get();
        $units = \App\Models\Unit::where('is_active', true)->get();
        // Submission types needed by the edit form
        $submissionTypes = \App\Models\SubmissionType::where('is_active', true)->orderBy('name')->get();
        
        $approvalRequest->load(['masterItems', 'itemType', 'submissionType']);
        
        return view('approval-requests.edit', compact('approvalRequest', 'defaultWorkflow', 'masterItems', 'itemTypes', 'itemCategories', 'commodities', 'units', 'submissionTypes'));
    }

    public function update(Request $request, ApprovalRequest $approvalRequest)
    {
        // Log attempt
        Log::info('ApprovalRequest.update.attempt', [
            'request_id' => $approvalRequest->id,
            'status' => $approvalRequest->status,
            'requester_id' => $approvalRequest->requester_id,
            'auth_id' => auth()->id(),
            'items_count' => is_array($request->items) ? count($request->items) : 0,
        ]);

        // Only allow update if status is pending or on progress and user is the requester
        if (($approvalRequest->status !== 'pending' && $approvalRequest->status !== 'on progress') || (int)$approvalRequest->requester_id !== (int)auth()->id()) {
            Log::warning('ApprovalRequest.update.denied', [
                'request_id' => $approvalRequest->id,
                'status' => $approvalRequest->status,
                'requester_id' => $approvalRequest->requester_id,
                'auth_id' => auth()->id(),
                'reason' => 'status_or_owner_mismatch',
            ]);
            abort(403, 'Anda tidak memiliki akses untuk mengedit request ini.');
        }

        $validator = Validator::make($request->all(), [
            'item_type_id' => 'required|exists:item_types,id',
            'submission_type_id' => 'required|exists:submission_types,id',
            'request_number' => 'nullable|string|max:255|unique:approval_requests,request_number,' . $approvalRequest->id,
            'items' => 'nullable|array',
            'items.*.master_item_id' => 'nullable|exists:master_items,id',
            'items.*.name' => 'required_without:items.*.master_item_id|string|max:255',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.item_category_id' => 'nullable|integer|exists:item_categories,id',
            'items.*.item_category_name' => 'nullable|string|max:255',
            'items.*.specification' => 'nullable|string',
            'items.*.brand' => 'nullable|string|max:100',
            'items.*.supplier_id' => 'nullable|exists:suppliers,id',
            'items.*.supplier_name' => 'nullable|string|max:255',
            'items.*.alternative_vendor' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string|max:500',
            'items.*.files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:20480'
        ]);

        // Additional conditional validation: new items must include a category
        $validator->after(function($v) use ($request) {
            $items = $request->input('items', []);
            foreach ($items as $idx => $row) {
                $hasId = !empty($row['master_item_id']);
                $hasName = !empty($row['name']);
                if (!$hasId && $hasName) {
                    $hasCat = !empty($row['item_category_id']) || !empty($row['item_category_name']);
                    if (!$hasCat) {
                        $v->errors()->add("items.$idx.item_category", 'Kategori wajib diisi untuk item baru.');
                    }
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // All requests are now specific to item type
        $isSpecificType = true;

        $approvalRequest->update([
            'request_number' => $request->request_number ?: $approvalRequest->request_number,
            'priority' => 'normal',
            'is_cto_request' => false,
            'item_type_id' => $request->item_type_id,
            'submission_type_id' => $request->submission_type_id,
            'is_specific_type' => $isSpecificType
        ]);

        // Handle items update
        if ($request->has('items') && is_array($request->items)) {
            // Remove existing items
            $approvalRequest->masterItems()->detach();
            
            // Add new items
            foreach ($request->items as $itemData) {
                $hasId = !empty($itemData['master_item_id']);
                $hasName = !empty($itemData['name']);
                if (!$hasId && !$hasName) continue;

                $masterItemId = $hasId
                    ? (int) $itemData['master_item_id']
                    : $this->resolveOrCreateMasterItem($itemData['name'], (int) $request->item_type_id);

                $masterItem = MasterItem::findOrFail($masterItemId);
                // Do not fallback to master item price; keep unit_price nullable if not provided
                $unitPrice = isset($itemData['unit_price']) && $itemData['unit_price'] !== ''
                    ? (float) $itemData['unit_price']
                    : null;
                $quantity = (int) ($itemData['quantity'] ?? 1);
                $totalPrice = $unitPrice !== null ? ($quantity * $unitPrice) : null;

                // Resolve supplier if only name provided
                $supplierId = isset($itemData['supplier_id']) ? (int) $itemData['supplier_id'] : null;
                if (!$supplierId && !empty($itemData['supplier_name'])) {
                    $supplierId = $this->resolveOrCreateSupplierByName($itemData['supplier_name']);
                }

                $approvalRequest->masterItems()->attach($masterItemId, [
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'notes' => $itemData['notes'] ?? null,
                    'specification' => $itemData['specification'] ?? null,
                    'brand' => $itemData['brand'] ?? null,
                    'supplier_id' => $supplierId,
                    'alternative_vendor' => $itemData['alternative_vendor'] ?? null,
                ]);
            }
        }

        // Upload dan penghapusan lampiran dinonaktifkan

        Log::info('ApprovalRequest.update.success', [
            'request_id' => $approvalRequest->id,
            'auth_id' => auth()->id(),
        ]);

        return redirect()->route('approval-requests.show', $approvalRequest)
                        ->with('success', 'Approval request berhasil diperbarui!');
    }

    public function destroy(ApprovalRequest $approvalRequest)
    {
        // Only allow delete if status is pending or on progress and user is the requester
        if (($approvalRequest->status !== 'pending' && $approvalRequest->status !== 'on progress') || (int)$approvalRequest->requester_id !== (int)auth()->id()) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus request ini.');
        }

        $approvalRequest->delete();
        
        // Redirect based on user permissions
        if (auth()->user()->hasPermission('view_all_approvals')) {
            return redirect()->route('approval-requests.index')->with('success', 'Approval request berhasil dihapus!');
        } elseif (auth()->user()->hasPermission('view_my_approvals')) {
            return redirect()->route('approval-requests.my-requests')->with('success', 'Approval request berhasil dihapus!');
        } else {
            return redirect()->route('dashboard')->with('success', 'Approval request berhasil dihapus!');
        }
    }

    public function approve(Request $request, ApprovalRequest $approvalRequest)
    {
        $validator = Validator::make($request->all(), [
            'comments' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if request is still pending or on progress
        if (!in_array($approvalRequest->status, ['pending', 'on progress'])) {
            return redirect()->back()->with('error', 'Request ini sudah tidak dalam status yang dapat di-approve.');
        }

        // Check if user can approve
        $currentStep = $approvalRequest->currentStep;
        if (!$currentStep || !$currentStep->canApprove(auth()->id())) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk approve request ini.');
        }

        $success = $approvalRequest->approve(auth()->id(), $request->comments);

        if ($success) {
            $message = $approvalRequest->status === 'approved' 
                ? 'Request berhasil di-approve sepenuhnya!' 
                : 'Request berhasil di-approve untuk step ini!';
            return redirect()->back()->with('success', $message);
        } else {
            return redirect()->back()->with('error', 'Gagal approve request. Silakan coba lagi.');
        }
    }

    public function reject(Request $request, ApprovalRequest $approvalRequest)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
            'comments' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if request is still pending or on progress
        if (!in_array($approvalRequest->status, ['pending', 'on progress'])) {
            return redirect()->back()->with('error', 'Request ini sudah tidak dalam status yang dapat di-reject.');
        }

        // Check if user can approve
        $currentStep = $approvalRequest->currentStep;
        if (!$currentStep || !$currentStep->canApprove(auth()->id())) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk reject request ini.');
        }

        $success = $approvalRequest->reject(auth()->id(), $request->reason, $request->comments);

        if ($success) {
            return redirect()->back()->with('success', 'Request berhasil di-reject!');
        } else {
            return redirect()->back()->with('error', 'Gagal reject request. Silakan coba lagi.');
        }
    }

    public function cancel(ApprovalRequest $approvalRequest)
    {
        // Only allow cancel if status is pending or on progress and user is the requester
        if (($approvalRequest->status !== 'pending' && $approvalRequest->status !== 'on progress') || (int)$approvalRequest->requester_id !== (int)auth()->id()) {
            abort(403, 'Anda tidak memiliki akses untuk membatalkan request ini.');
        }

        $success = $approvalRequest->cancel(auth()->id());

        if ($success) {
            // Redirect based on user permissions
            if (auth()->user()->hasPermission('view_all_approvals')) {
                return redirect()->route('approval-requests.index')->with('success', 'Request berhasil dibatalkan!');
            } elseif (auth()->user()->hasPermission('view_my_approvals')) {
                return redirect()->route('approval-requests.my-requests')->with('success', 'Request berhasil dibatalkan!');
            } else {
                return redirect()->route('dashboard')->with('success', 'Request berhasil dibatalkan!');
            }
        } else {
            return redirect()->back()->with('error', 'Gagal membatalkan request.');
        }
    }

    public function myRequests(Request $request)
    {
        $query = auth()->user()->approvalRequests()->with(['workflow', 'currentStep', 'steps.approver', 'steps.approverRole', 'steps.approverDepartment', 'submissionType']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhereHas('submissionType', function($st) use ($search) {
                      $st->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->paginate(10)->withQueryString();
        
        return view('approval-requests.my-requests', compact('requests'));
    }

    public function pendingApprovals(Request $request)
    {
        $user = auth()->user();
        $userDepartments = $user->departments()->pluck('departments.id');
        $userRoles = $user->role ? [$user->role->id] : [];
        
        // Find approval steps that user can approve - show ALL steps regardless of status
        $query = ApprovalStep::where(function($q) use ($userDepartments, $userRoles, $user) {
                                // User is directly assigned as approver
                                $q->where('approver_id', $user->id)
                                  // User has the required role
                                  ->orWhereIn('approver_role_id', $userRoles)
                                  // User is in the required department
                                  ->orWhereIn('approver_department_id', $userDepartments)
                                  // User is manager of required department level
                                  ->orWhereHas('approverDepartment', function($deptQuery) use ($user) {
                                      $deptQuery->where('manager_id', $user->id);
                                  })
                                  // For department_level approver type - check if user can approve based on department level
                                  ->orWhere(function($levelQuery) use ($user, $userDepartments) {
                                      $levelQuery->where('approver_type', 'department_level')
                                                ->whereExists(function($existsQuery) use ($user, $userDepartments) {
                                                    $existsQuery->select(\DB::raw(1))
                                                              ->from('departments')
                                                              ->whereIn('id', $userDepartments)
                                                              ->where('level', '>=', \DB::raw('approval_steps.approver_level'));
                                                });
                                  })
                                  // For requester_department_manager approver type - user must be manager of requester's primary department
                                  ->orWhere(function($reqMgrQuery) use ($user) {
                                      $reqMgrQuery->where('approver_type', 'requester_department_manager')
                                                  ->whereExists(function($exists) use ($user) {
                                                      $exists->select(\DB::raw(1))
                                                            ->from('approval_requests')
                                                            ->join('user_departments', function($join) {
                                                                $join->on('user_departments.user_id', '=', 'approval_requests.requester_id')
                                                                     ->where('user_departments.is_primary', true);
                                                            })
                                                            ->join('departments', 'departments.id', '=', 'user_departments.department_id')
                                                            ->whereColumn('approval_requests.id', 'approval_steps.request_id')
                                                            ->where('departments.manager_id', $user->id);
                                                  });
                                  });
                            })
                            ->whereHas('request', function($q) {
                                // Show requests with any status by default
                                $q->whereIn('status', ['pending', 'on progress', 'approved', 'rejected', 'cancelled']);
                            })
                            ->with(['request.workflow', 'request.requester', 'request.steps.approver', 'request.steps.approverRole', 'request.steps.approverDepartment', 'request.submissionType', 'approver', 'approverRole', 'approverDepartment']);

        // Status filter
        if ($request->filled('status')) {
            $query->whereHas('request', function($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('request', function($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhereHas('submissionType', function($st) use ($search) {
                      $st->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('requester', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $pendingApprovals = $query->latest()->paginate(10)->withQueryString();
        
        return view('approval-requests.pending-approvals', compact('pendingApprovals'));
    }

    // API methods for AJAX requests
    public function getMasterItems(Request $request)
    {
        $query = MasterItem::active()->with(['itemType', 'itemCategory', 'commodity', 'unit']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Item type filter (for specific type requests)
        if ($request->filled('item_type_id')) {
            $query->where('item_type_id', $request->item_type_id);
        }

        // Category filter
        if ($request->filled('item_category_id')) {
            $query->where('item_category_id', $request->item_category_id);
        }

        // Commodity filter
        if ($request->filled('commodity_id')) {
            $query->where('commodity_id', $request->commodity_id);
        }

        $items = $query->limit(20)->get();

        return response()->json([
            'success' => true,
            'items' => $items
        ]);
    }

    public function downloadAttachment($attachmentId)
    {
        $file = \DB::table('approval_request_item_files')->where('id', $attachmentId)->first();
        if (!$file) {
            abort(404, 'File tidak ditemukan.');
        }
        if (!\Storage::exists($file->path)) {
            abort(404, 'Path file tidak ditemukan.');
        }
        return \Storage::download($file->path, $file->original_name);
    }

    public function viewAttachment($attachmentId)
    {
        $file = \DB::table('approval_request_item_files')->where('id', $attachmentId)->first();
        if (!$file) {
            abort(404, 'File tidak ditemukan.');
        }
        if (!\Storage::exists($file->path)) {
            abort(404, 'Path file tidak ditemukan.');
        }
        // Only allow inline view for PDFs; others fallback to download
        $mime = $file->mime ?: \Storage::mimeType($file->path);
        if ($mime !== 'application/pdf') {
            return \Storage::download($file->path, $file->original_name);
        }
        $stream = \Storage::readStream($file->path);
        return response()->stream(function() use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.addslashes($file->original_name).'"'
        ]);
    }

    public function getStepDetails($requestId, $stepNumber)
    {
        $step = ApprovalStep::where('request_id', $requestId)
            ->where('step_number', $stepNumber)
            ->with('approvedBy')
            ->first();

        if (!$step) {
            return response()->json(['error' => 'Step not found'], 404);
        }

        return response()->json([
            'step_name' => $step->step_name,
            'status' => $step->status,
            'approved_at' => $step->approved_at,
            'approved_by_name' => $step->approvedBy ? $step->approvedBy->name : null,
            'comments' => $step->comments
        ]);
    }

    /**
     * Get workflow for specific item type
     */
    public function getWorkflowForItemType($itemTypeId)
    {
        // Try to get specific workflow for this item type
        $workflow = ApprovalWorkflow::where('is_active', true)
            ->where('item_type_id', $itemTypeId)
            ->where('is_specific_type', true)
            ->first();
            
        // If no specific workflow found, get general workflow
        if (!$workflow) {
            $workflow = ApprovalWorkflow::where('is_active', true)
                ->where('is_specific_type', false)
                ->where('type', 'standard')
                ->first();
        }
        
        if (!$workflow) {
            return response()->json(['error' => 'No workflow found'], 404);
        }
        
        return response()->json([
            'success' => true,
            'workflow' => [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'type' => $workflow->type,
                'is_specific_type' => $workflow->is_specific_type,
                'item_type_id' => $workflow->item_type_id
            ]
        ]);
    }

    public function updateStepStatus(Request $request, $requestId, $stepNumber)
    {
        // Debug logging
        \Log::info('UpdateStepStatus called', [
            'requestId' => $requestId,
            'stepNumber' => $stepNumber,
            'requestData' => $request->all(),
            'user_id' => auth()->id()
        ]);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,approved,rejected',
            'comments' => 'nullable|string|max:1000',
            'rejection_reason' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            \Log::info('Validation failed', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $step = ApprovalStep::where('request_id', $requestId)
            ->where('step_number', $stepNumber)
            ->first();

        \Log::info('Step lookup result', [
            'step' => $step ? $step->toArray() : null,
            'requestId' => $requestId,
            'stepNumber' => $stepNumber
        ]);

        if (!$step) {
            \Log::error('Step not found', ['requestId' => $requestId, 'stepNumber' => $stepNumber]);
            return response()->json(['error' => 'Step not found'], 404);
        }

        // Check if user can edit this step
        $currentStep = $step->request->currentStep;
        if (!$currentStep || !$currentStep->canApprove(auth()->id())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $approvalRequest = $step->request;

        // Update the step
        $step->update([
            'status' => $request->status,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'comments' => $request->comments
        ]);

        // Update approval request based on action
        if ($request->status === 'approved') {
            // Check if this is the last step
            if ($stepNumber >= $approvalRequest->total_steps) {
                // This is the final approval
                $approvalRequest->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now()
                ]);
            } else {
                // Move to next step
                $approvalRequest->update([
                    'current_step' => $stepNumber + 1,
                    'status' => 'on progress'
                ]);
            }
        } elseif ($request->status === 'rejected') {
            // Reject the entire request
            $approvalRequest->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejection_reason' => $request->rejection_reason
            ]);
        } elseif ($request->status === 'pending') {
            // Keep as pending - no changes to approval request
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully'
        ]);
    }

    /**
     * Resolve or create a MasterItem by name and return its ID.
     * This uses the centralized ItemResolver service to avoid duplication.
     */
    private function resolveOrCreateMasterItem(string $name, int $itemTypeId, array $extras = []): int
    {
        /** @var ItemResolver $resolver */
        $resolver = app(ItemResolver::class);
        $payload = [
            'name' => $name,
            'item_type_id' => $itemTypeId,
        ];
        if (!empty($extras['item_category_id'])) $payload['item_category_id'] = $extras['item_category_id'];
        if (!empty($extras['item_category_name'])) $payload['item_category_name'] = $extras['item_category_name'];
        $item = $resolver->resolveOrCreate($payload);
        return (int) $item->id;
    }

    private function resolveOrCreateSupplierByName(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') return null;
        $existing = \App\Models\Supplier::where('name', $name)->first();
        if ($existing) return (int) $existing->id;
        $base = strtoupper(\Str::slug($name, '_')) ?: 'SUP';
        $code = substr($base, 0, 20);
        $suffix = 1;
        while (\App\Models\Supplier::where('code', $code)->exists()) {
            $suffix++;
            $code = substr($base, 0, 20 - strlen((string)$suffix)) . $suffix;
        }
        $supplier = \App\Models\Supplier::create([
            'name' => $name,
            'code' => $code,
            'is_active' => true,
        ]);
        return (int) $supplier->id;
    }
}
