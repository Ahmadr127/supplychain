<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\ApprovalWorkflow;
// use App\Models\ApprovalStep; // DEPRECATED: Replaced by ApprovalItemStep
use App\Models\MasterItem;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\ApprovalRequestItemExtra;
use App\Models\ApprovalRequestItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\ItemResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ApprovalRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ApprovalRequest::query();

        // Search filter (request-level)
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
        // Status filter (request-level)
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

        // Eager load relations needed by the table and item rows
        $requests = $query->with([
                'workflow',
                'requester',
                // 'currentStep', // DEPRECATED: removed with per-item approval migration
                // 'steps.approver', // DEPRECATED
                // 'steps.approverRole', // DEPRECATED
                // 'steps.approverDepartment', // DEPRECATED
                'submissionType',
                'items.masterItem', // New per-item table
                'purchasingItems.masterItem',
            ])
            ->latest()
            ->get();

        // Flatten to per-item rows
        $rows = [];
        foreach ($requests as $req) {
            $piByItem = ($req->purchasingItems ?? collect())->keyBy('master_item_id');
            foreach ($req->items as $item) {
                $row = new \stdClass();
                $row->request = $req;
                $row->item = $item->masterItem; // Access the related MasterItem
                $row->itemData = $item; // ApprovalRequestItem with quantity, price, etc.
                $row->purchasingItem = $piByItem->get($item->master_item_id);
                $row->sort_ts = $req->created_at?->timestamp ?? 0;
                $rows[] = $row;
            }
        }

        // Sort by request created_at desc and paginate per-item
        usort($rows, function($a, $b){ return $b->sort_ts <=> $a->sort_ts; });
        $items = $this->paginateArray($rows, (int)($request->get('per_page', 10)), $request);

        $workflows = ApprovalWorkflow::where('is_active', true)->get();
        $departmentsMap = Department::pluck('name', 'id');
        
        return view('approval-requests.index', compact('items', 'workflows', 'departmentsMap'));
    }

    /**
     * Normalize form_extra payload: convert checkbox strings to integers and numeric strings to ints.
     */
    private function normalizeFormExtra(array $data): array
    {
        // Boolean-like fields from checkboxes (may arrive as 'true'/'false' strings)
        $boolKeys = [
            'c_kriteria_dn',
            'c_kriteria_impor',
            'c_kriteria_kerajinan',
            'c_kriteria_jasa',
        ];

        foreach ($boolKeys as $k) {
            if (array_key_exists($k, $data)) {
                $val = $data[$k];
                // Accept true/false, 'true'/'false', '1'/'0', 1/0
                $data[$k] = filter_var($val, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }
        }

        // Integer-like fields
        $intKeys = [
            'a_jumlah', 'b_jml_pegawai', 'b_jml_dokter', 'c_jumlah'
        ];
        foreach ($intKeys as $k) {
            if (array_key_exists($k, $data) && $data[$k] !== null && $data[$k] !== '') {
                $data[$k] = (int) $data[$k];
            }
        }

        // Trim strings to avoid accidental spaces
        foreach ($data as $k => $v) {
            if (is_string($v)) {
                $data[$k] = trim($v);
            }
        }

        return $data;
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
        $departments = \App\Models\Department::orderBy('name')->get();
        // Load submission types for the edit view (used in the form)
        $submissionTypes = \App\Models\SubmissionType::where('is_active', true)->orderBy('name')->get();
        
        // Get FS document settings
        $fsSettings = Setting::getGroup('approval_request');
        
        return view('approval-requests.create', compact('defaultWorkflow', 'masterItems', 'itemTypes', 'itemCategories', 'commodities', 'units', 'submissionTypes', 'previewRequestNumber', 'departments', 'fsSettings'));
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
            'items.*.allocation_department_id' => 'nullable|exists:departments,id',
            'items.*.letter_number' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string|max:500',
            'items.*.files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:20480',
            'items.*.fs_document' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // Per-item FS document
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

        // Simplified FS validation: require upload when subtotal >= upload threshold
        $validator->after(function($v) use ($request) {
            $fs = Setting::getGroup('approval_request') ?? [];
            $enabled = (bool)($fs['fs_document_enabled'] ?? true);
            if (!$enabled) return;
            
            // Simplified thresholds
            $thresholdUpload = (int)($fs['fs_threshold_total'] ?? 100000000);

            $items = $request->input('items', []);
            foreach ($items as $idx => $row) {
                $qty = (int)($row['quantity'] ?? 0);
                $price = (int)($row['unit_price'] ?? 0);
                $subtotal = max(0, $qty) * max(0, $price);
                
                // Require upload when subtotal >= upload threshold
                if ($subtotal >= $thresholdUpload) {
                    $file = $request->file("items.$idx.fs_document");
                    if (!$file) {
                        $v->errors()->add("items.$idx.fs_document", 'Dokumen FS wajib diunggah untuk item ini (subtotal ≥ ' . number_format($thresholdUpload, 0, ',', '.') . ').');
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

        // DEPRECATED: Diagnostics disabled during per-item approval migration
        // try {
        //     $this->logStepDiagnostics($approvalRequest);
        // } catch (\Throwable $e) {
        //     Log::warning('ApprovalRequest.store.logStepDiagnostics_failed', [
        //         'request_id' => $approvalRequest->id,
        //         'error' => $e->getMessage(),
        //     ]);
        // }

        // Update approval request with item type information
        $approvalRequest->update([
            'item_type_id' => $request->item_type_id,
            'submission_type_id' => $request->submission_type_id,
            'is_specific_type' => $isSpecificType,
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

                // Handle per-item FS document
                $fsDocumentPath = null;
                if (isset($itemData['fs_document']) && $itemData['fs_document'] instanceof \Illuminate\Http\UploadedFile) {
                    $fsDocumentPath = $itemData['fs_document']->store('fs_documents', 'public');
                }

                // Create item in approval_request_items table (NEW)
                $item = $approvalRequest->items()->create([
                    'master_item_id' => $masterItemId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'notes' => $itemData['notes'] ?? null,
                    'specification' => $itemData['specification'] ?? null,
                    'brand' => $itemData['brand'] ?? null,
                    'supplier_id' => $supplierId,
                    'alternative_vendor' => $itemData['alternative_vendor'] ?? null,
                    'allocation_department_id' => $itemData['allocation_department_id'] ?? null,
                    'letter_number' => $itemData['letter_number'] ?? null,
                    'fs_document' => $fsDocumentPath,
                    'status' => 'pending',
                ]);

                // Initialize per-item approval steps from workflow (NEW)
                $this->initializeItemSteps($approvalRequest, $masterItemId);

                // Handle form extra data if provided
                if (isset($itemData['form_extra']) && is_array($itemData['form_extra'])) {
                    $extraData = $this->normalizeFormExtra($itemData['form_extra']);
                    $itemExtra = new ApprovalRequestItemExtra();
                    $itemExtra->approval_request_id = $approvalRequest->id;
                    $itemExtra->master_item_id = $masterItemId;
                    $itemExtra->fill($extraData);
                    
                    // Auto-fill from main form data
                    $itemExtra->autoFillFromMainForm();
                    $itemExtra->save();
                }

                // Store per-item files if any
                if (isset($itemData['files']) && is_array($itemData['files'])) {
                    foreach ($itemData['files'] as $uploaded) {
                        if (!$uploaded || !($uploaded instanceof \Illuminate\Http\UploadedFile)) continue;
                        $path = $uploaded->store('approval_items', 'public');
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
            'items.masterItem.itemType',
            'items.masterItem.itemCategory',
            'items.masterItem.commodity',
            'items.masterItem.unit',
            'items.steps.approver',
            'items.approver',
            'items.allocationDepartment',
            'submissionType',
            'purchasingItems.vendors',
            'purchasingItems.preferredVendor',
            'itemExtras' // Load form extra data
        ]);

        // Load item files grouped by master_item_id to show per item
        $files = \DB::table('approval_request_item_files')
            ->where('approval_request_id', $approvalRequest->id)
            ->get()
            ->groupBy('master_item_id');
        
        // Group item extras by master_item_id for easy access
        $itemExtras = $approvalRequest->itemExtras->keyBy('master_item_id');
        
        return view('approval-requests.show', [
            'approvalRequest' => $approvalRequest,
            'itemFiles' => $files,
            'itemExtras' => $itemExtras,
        ]);
    }

    /**
     * DEPRECATED: Write detailed diagnostics about generated approval steps and potential approvers.
     * This helps analyze why items may not appear in Pending Approvals.
     * Disabled during per-item approval migration.
     */
    private function logStepDiagnostics(ApprovalRequest $approvalRequest): void
    {
        return; // DISABLED during migration
        $approvalRequest->load(['requester', 'steps']);

        $requester = $approvalRequest->requester;
        $primaryDept = $requester
            ? $requester->departments()->wherePivot('is_primary', true)->first()
            : null;

        $diag = [
            'request_id' => $approvalRequest->id,
            'request_number' => $approvalRequest->request_number,
            'request_status' => $approvalRequest->status,
            'requester_id' => $requester?->id,
            'requester_primary_department_id' => $primaryDept?->id,
            'requester_primary_department_manager_id' => $primaryDept?->manager_id,
            'steps' => [],
        ];

        foreach ($approvalRequest->steps as $step) {
            $entry = [
                'step_number' => $step->step_number,
                'step_name' => $step->step_name,
                'approver_type' => $step->approver_type,
                'approver_id' => $step->approver_id,
                'approver_role_id' => $step->approver_role_id,
                'approver_department_id' => $step->approver_department_id,
                'status' => $step->status,
                'resolution' => [],
            ];

            try {
                switch ($step->approver_type) {
                    case 'user':
                        $userExists = \App\Models\User::where('id', $step->approver_id)->exists();
                        $entry['resolution'] = [
                            'user_exists' => $userExists,
                        ];
                        break;
                    case 'role':
                        $roleUsersCount = $step->approver_role_id
                            ? \App\Models\User::where('role_id', $step->approver_role_id)->count()
                            : 0;
                        $entry['resolution'] = [
                            'role_id' => $step->approver_role_id,
                            'users_with_role_count' => $roleUsersCount,
                        ];
                        break;
                    case 'department_manager':
                        $dept = $step->approver_department_id
                            ? \App\Models\Department::find($step->approver_department_id)
                            : null;
                        $entry['resolution'] = [
                            'department_id' => $dept?->id,
                            'department_manager_id' => $dept?->manager_id,
                        ];
                        break;
                    case 'requester_department_manager':
                        $entry['resolution'] = [
                            'requester_primary_department_id' => $primaryDept?->id,
                            'requester_primary_department_manager_id' => $primaryDept?->manager_id,
                        ];
                        break;
                    case 'any_department_manager':
                        $managersCount = \App\Models\User::whereHas('departments', function($q){
                            $q->where('user_departments.is_manager', true);
                        })->count();
                        $entry['resolution'] = [
                            'any_department_managers_count' => $managersCount,
                        ];
                        break;
                    default:
                        $entry['resolution'] = [
                            'note' => 'Unknown approver_type. Supported: user, role, department_manager, requester_department_manager, any_department_manager',
                        ];
                }
            } catch (\Throwable $e) {
                $entry['resolution_error'] = $e->getMessage();
            }

            $diag['steps'][] = $entry;
        }

        Log::info('ApprovalRequest.store.step_diagnostics', $diag);
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
        // Departments needed by the form (allocation dropdown, etc.)
        $departments = \App\Models\Department::orderBy('name')->get();
        
        // Get FS document settings
        $fsSettings = Setting::getGroup('approval_request');
        
        $approvalRequest->load(['items.masterItem', 'itemType', 'submissionType', 'itemExtras']);
        
        // Group item extras by master_item_id for easy access in view
        $itemExtras = $approvalRequest->itemExtras->keyBy('master_item_id');
        
        // Load item files grouped by master_item_id to show existing files
        $itemFiles = \DB::table('approval_request_item_files')
            ->where('approval_request_id', $approvalRequest->id)
            ->get()
            ->groupBy('master_item_id');
        
        return view('approval-requests.edit', compact('approvalRequest', 'defaultWorkflow', 'masterItems', 'itemTypes', 'itemCategories', 'commodities', 'units', 'submissionTypes', 'departments', 'fsSettings', 'itemExtras', 'itemFiles'));
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
            'items.*.allocation_department_id' => 'nullable|exists:departments,id',
            'items.*.letter_number' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string|max:500',
            'items.*.files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:20480',
            'items.*.fs_document' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // Per-item FS document
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
            'is_specific_type' => $isSpecificType,
        ]);

        // Handle items update (NEW: use items table instead of pivot)
        if ($request->has('items') && is_array($request->items)) {
            // Snapshot existing fs_document before delete
            $existingFsByItem = $approvalRequest->items()
                ->pluck('fs_document', 'master_item_id')
                ->toArray();

            // Remove existing items and their steps
            $approvalRequest->items()->delete();
            $approvalRequest->itemSteps()->delete();
            
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

                // Get existing FS document (from snapshot) if any
                $existingFsDocument = $existingFsByItem[$masterItemId] ?? null;

                // Handle per-item FS document
                $fsDocumentPath = null;
                if (isset($itemData['fs_document']) && $itemData['fs_document'] instanceof \Illuminate\Http\UploadedFile) {
                    $fsDocumentPath = $itemData['fs_document']->store('fs_documents', 'public');
                } elseif (isset($itemData['existing_fs_document'])) {
                    // Keep existing FS document if no new file uploaded
                    $fsDocumentPath = $itemData['existing_fs_document'];
                } elseif ($existingFsDocument) {
                    // Keep existing FS document from database if no new file and no hidden field
                    $fsDocumentPath = $existingFsDocument;
                }

                // Create item in approval_request_items table (NEW)
                $item = $approvalRequest->items()->create([
                    'master_item_id' => $masterItemId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'notes' => $itemData['notes'] ?? null,
                    'specification' => $itemData['specification'] ?? null,
                    'brand' => $itemData['brand'] ?? null,
                    'supplier_id' => $supplierId,
                    'alternative_vendor' => $itemData['alternative_vendor'] ?? null,
                    'allocation_department_id' => $itemData['allocation_department_id'] ?? null,
                    'letter_number' => $itemData['letter_number'] ?? null,
                    'fs_document' => $fsDocumentPath,
                    'status' => 'pending',
                ]);

                // Initialize per-item approval steps from workflow (NEW)
                $this->initializeItemSteps($approvalRequest, $masterItemId);

                // Store per-item files if any (dokumen pendukung)
                if (isset($itemData['files']) && is_array($itemData['files'])) {
                    foreach ($itemData['files'] as $uploaded) {
                        if (!$uploaded || !($uploaded instanceof \Illuminate\Http\UploadedFile)) continue;
                        $path = $uploaded->store('approval_items', 'public');
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

                // Handle form extra data if provided (for update)
                if (isset($itemData['form_extra']) && is_array($itemData['form_extra'])) {
                    $extraData = $this->normalizeFormExtra($itemData['form_extra']);
                    
                    // Check if extra data already exists
                    $itemExtra = ApprovalRequestItemExtra::where('approval_request_id', $approvalRequest->id)
                        ->where('master_item_id', $masterItemId)
                        ->first();
                    
                    if (!$itemExtra) {
                        $itemExtra = new ApprovalRequestItemExtra();
                        $itemExtra->approval_request_id = $approvalRequest->id;
                        $itemExtra->master_item_id = $masterItemId;
                    }
                    
                    $itemExtra->fill($extraData);
                    // Auto-fill from main form data
                    $itemExtra->autoFillFromMainForm();
                    $itemExtra->save();
                }
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
        // Per-item approval if master_item_id provided (non-breaking enhancement)
        if ($request->filled('master_item_id')) {
            $data = $request->validate([
                'master_item_id' => 'required|integer|exists:master_items,id',
                'comments' => 'nullable|string|max:1000',
            ]);

            // Authorization remains step-based at request level
            if (!in_array($approvalRequest->status, ['pending', 'on progress'])) {
                return redirect()->back()->with('error', 'Request ini sudah tidak dalam status yang dapat di-approve.');
            }
            $currentStep = $approvalRequest->currentStep;
            if (!$currentStep || !$currentStep->canApprove(auth()->id())) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk approve item pada request ini.');
            }

            $item = ApprovalRequestItem::where('approval_request_id', $approvalRequest->id)
                ->where('master_item_id', (int)$data['master_item_id'])
                ->first();
            if (!$item) {
                return redirect()->back()->with('error', 'Item tidak ditemukan pada request ini.');
            }

            $item->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Item berhasil di-approve.');
        }

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
        // Per-item rejection if master_item_id provided (non-breaking enhancement)
        if ($request->filled('master_item_id')) {
            $data = $request->validate([
                'master_item_id' => 'required|integer|exists:master_items,id',
                'reason' => 'required|string|max:1000',
                'comments' => 'nullable|string|max:1000',
            ]);

            if (!in_array($approvalRequest->status, ['pending', 'on progress'])) {
                return redirect()->back()->with('error', 'Request ini sudah tidak dalam status yang dapat di-reject.');
            }
            $currentStep = $approvalRequest->currentStep;
            if (!$currentStep || !$currentStep->canApprove(auth()->id())) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk reject item pada request ini.');
            }

            $item = ApprovalRequestItem::where('approval_request_id', $approvalRequest->id)
                ->where('master_item_id', (int)$data['master_item_id'])
                ->first();
            if (!$item) {
                return redirect()->back()->with('error', 'Item tidak ditemukan pada request ini.');
            }

            $item->update([
                'status' => 'rejected',
                'rejected_reason' => $data['reason'],
                'approved_by' => null,
                'approved_at' => null,
            ]);

            return redirect()->back()->with('success', 'Item berhasil di-reject.');
        }

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

    // Purchasing: set tanggal diterima (received_at) for an approval request
    public function setReceivedDate(Request $request, ApprovalRequest $approvalRequest)
    {
        $data = $request->validate([
            'received_at' => 'required|date',
        ]);

        $approvalRequest->update([
            'received_at' => $data['received_at'],
        ]);

        return redirect()->back()->with('success', 'Tanggal diterima berhasil disimpan.');
    }

    public function myRequests(Request $request)
    {
        $query = auth()->user()->approvalRequests();

        // Search filter (request-level)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhereHas('submissionType', function($st) use ($search) {
                      $st->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter (request-level)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Eager load
        $requests = $query->with([
                'workflow',
                // 'currentStep', // DEPRECATED: removed with per-item approval migration
                // 'steps.approver', 'steps.approverRole', 'steps.approverDepartment', // DEPRECATED
                'submissionType',
                'requester',
                'items.masterItem', // New per-item table
                'purchasingItems.masterItem',
            ])
            ->latest()
            ->get();

        // Flatten
        $rows = [];
        foreach ($requests as $req) {
            $piByItem = ($req->purchasingItems ?? collect())->keyBy('master_item_id');
            foreach ($req->items as $item) {
                $row = new \stdClass();
                $row->request = $req;
                $row->item = $item->masterItem;
                $row->itemData = $item; // ApprovalRequestItem with quantity, price, etc.
                $row->purchasingItem = $piByItem->get($item->master_item_id);
                $row->sort_ts = $req->created_at?->timestamp ?? 0;
                $rows[] = $row;
            }
        }
        usort($rows, function($a, $b){ return $b->sort_ts <=> $a->sort_ts; });
        $items = $this->paginateArray($rows, (int)($request->get('per_page', 10)), $request);

        $departmentsMap = Department::pluck('name', 'id');
        
        return view('approval-requests.my-requests', compact('items', 'departmentsMap'));
    }

    public function pendingApprovals(Request $request)
    {
        $user = auth()->user();
        $userDepartments = $user->departments()->pluck('departments.id');
        $userRoles = $user->role ? [$user->role->id] : [];
        
        // Find item steps user can approve (using new per-item approval system)
        // Show ALL steps (pending, approved, rejected) that match the user's approval authority
        $query = \App\Models\ApprovalItemStep::where(function($q) use ($userDepartments, $userRoles, $user) {
                                $q->where('approver_id', $user->id)
                                  ->orWhereIn('approver_role_id', $userRoles)
                                  ->orWhereIn('approver_department_id', $userDepartments)
                                  ->orWhere(function($anyMgrQuery) use ($user) {
                                      $anyMgrQuery->where('approver_type', 'any_department_manager')
                                                  ->whereExists(function($exists) use ($user) {
                                                      $exists->select(\DB::raw(1))
                                                            ->from('user_departments')
                                                            ->whereColumn('user_departments.user_id', \DB::raw((int)$user->id))
                                                            ->where('user_departments.is_manager', true);
                                                  });
                                  })
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
                                                            ->whereColumn('approval_requests.id', 'approval_item_steps.approval_request_id')
                                                            ->where('departments.manager_id', $user->id);
                                                  });
                                  })
                                  ->orWhere(function($deptMgrQuery) use ($user) {
                                      $deptMgrQuery->where('approver_type', 'department_manager')
                                                   ->whereExists(function($exists) use ($user) {
                                                       $exists->select(\DB::raw(1))
                                                             ->from('departments')
                                                             ->whereColumn('departments.id', 'approval_item_steps.approver_department_id')
                                                             ->where('departments.manager_id', $user->id);
                                                   });
                                  });
                            })
                            ->whereHas('request', function($q) {
                                // Show all active requests (not cancelled)
                                $q->whereIn('status', ['pending', 'on progress', 'approved', 'rejected']);
                            });

        // Status filter - can filter by step status OR request status
        if ($request->filled('status')) {
            $statusFilter = $request->status;
            // If filtering by step status (pending, approved, rejected)
            if (in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
                $query->where('status', $statusFilter);
            } else {
                // Otherwise filter by request status
                $query->whereHas('request', function($q) use ($statusFilter) {
                    $q->where('status', $statusFilter);
                });
            }
        }

        // Search filter (request fields)
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

        // Eager load for per-item approval
        $itemSteps = $query->with([
                'request.requester',
                'request.submissionType',
                'request.items', // Load all items to find matching itemData
                'item', // The specific master item for this step
                'request.purchasingItems',
            ])
            ->latest()
            ->get();

        // Build rows (already per-item with new system)
        $rows = [];
        foreach ($itemSteps as $itemStep) {
            $req = $itemStep->request;
            if (!$req) continue;
            
            $piByItem = ($req->purchasingItems ?? collect())->keyBy('master_item_id');
            
            // Find the corresponding ApprovalRequestItem for this step
            $itemData = $req->items->firstWhere('master_item_id', $itemStep->master_item_id);
            
            $row = new \stdClass();
            $row->request = $req;
            $row->step = $itemStep; // This is now an ApprovalItemStep
            $row->item = $itemStep->item; // Direct relation to the MasterItem
            $row->itemData = $itemData; // ApprovalRequestItem with quantity, price, allocation_department_id, etc.
            $row->purchasingItem = $piByItem->get($itemStep->master_item_id);
            $row->sort_ts = $req->created_at?->timestamp ?? 0;
            $rows[] = $row;
        }
        usort($rows, function($a, $b){ return $b->sort_ts <=> $a->sort_ts; });
        $pendingItems = $this->paginateArray($rows, (int)($request->get('per_page', 10)), $request);

        $departmentsMap = Department::pluck('name', 'id');
        
        return view('approval-requests.pending-approvals', compact('pendingItems', 'departmentsMap'));
    }

    /**
     * Paginate a simple array of rows into a LengthAwarePaginator while preserving query string.
     */
    private function paginateArray(array $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = (int) max(1, (int) $request->get('page', 1));
        $total = count($items);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($items, $offset, $perPage);
        $paginator = new LengthAwarePaginator($slice, $total, $perPage, $page, [
            'path' => $request->url(),
            'pageName' => 'page',
        ]);
        $paginator->appends($request->query());
        return $paginator;
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
        // Use public disk since files are stored there
        if (!\Storage::disk('public')->exists($file->path)) {
            abort(404, 'Path file tidak ditemukan.');
        }
        return \Storage::disk('public')->download($file->path, $file->original_name);
    }

    public function viewAttachment($attachmentId)
    {
        $file = \DB::table('approval_request_item_files')->where('id', $attachmentId)->first();
        if (!$file) {
            abort(404, 'File tidak ditemukan.');
        }
        // Use public disk since files are stored there
        if (!\Storage::disk('public')->exists($file->path)) {
            abort(404, 'Path file tidak ditemukan.');
        }
        // Only allow inline view for PDFs; others fallback to download
        $mime = $file->mime ?: \Storage::disk('public')->mimeType($file->path);
        if ($mime !== 'application/pdf') {
            return \Storage::disk('public')->download($file->path, $file->original_name);
        }
        $stream = \Storage::disk('public')->readStream($file->path);
        return response()->stream(function() use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.addslashes($file->original_name).'"'
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

    /**
     * Initialize per-item approval steps from workflow
     */
    private function initializeItemSteps(ApprovalRequest $approvalRequest, int $masterItemId): void
    {
        $workflow = $approvalRequest->workflow;
        if (!$workflow) {
            Log::warning('No workflow found for request', ['request_id' => $approvalRequest->id]);
            return;
        }

        // Get workflow steps from JSON attribute (not a relation)
        $workflowSteps = $workflow->steps; // This uses getStepsAttribute() accessor
        
        foreach ($workflowSteps as $step) {
            \App\Models\ApprovalItemStep::create([
                'approval_request_id' => $approvalRequest->id,
                'master_item_id' => $masterItemId,
                'step_number' => $step->step_number,
                'step_name' => $step->step_name,
                'approver_type' => $step->approver_type,
                'approver_id' => $step->approver_id,
                'approver_role_id' => $step->approver_role_id,
                'approver_department_id' => $step->approver_department_id,
                'status' => 'pending',
            ]);
        }

        Log::info('Item steps initialized', [
            'request_id' => $approvalRequest->id,
            'master_item_id' => $masterItemId,
            'step_count' => count($workflowSteps),
        ]);
    }

    /**
     * Approve or reject an item step
     */
    public function approveItem(Request $request)
    {
        $validated = $request->validate([
            'step_id' => 'required|exists:approval_item_steps,id',
            'approval_request_id' => 'required|exists:approval_requests,id',
            'master_item_id' => 'required',
            'action' => 'required|in:approve,reject',
            'comments' => 'nullable|string|max:500',
        ]);

        $step = \App\Models\ApprovalItemStep::findOrFail($validated['step_id']);
        $user = auth()->user();

        // Verify user can approve this step
        if (!$step->canApprove($user->id)) {
            return back()->with('error', 'You are not authorized to approve this step.');
        }

        // Verify step is still pending
        if ($step->status !== 'pending') {
            return back()->with('error', 'This step has already been processed.');
        }

        $action = $validated['action'];
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';

        // Update the step
        $step->update([
            'status' => $newStatus,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'comments' => $validated['comments'],
        ]);

        // Update the item status
        $approvalRequestItem = \App\Models\ApprovalRequestItem::where('approval_request_id', $validated['approval_request_id'])
            ->where('master_item_id', $validated['master_item_id'])
            ->first();

        if ($approvalRequestItem) {
            if ($newStatus === 'rejected') {
                // If rejected, mark item as rejected
                $approvalRequestItem->update(['status' => 'rejected']);
            } else {
                // If approved, check if all steps for this item are approved
                $allSteps = \App\Models\ApprovalItemStep::where('approval_request_id', $validated['approval_request_id'])
                    ->where('master_item_id', $validated['master_item_id'])
                    ->get();

                $allApproved = $allSteps->every(fn($s) => $s->status === 'approved');
                $anyRejected = $allSteps->contains(fn($s) => $s->status === 'rejected');

                if ($anyRejected) {
                    $approvalRequestItem->update(['status' => 'rejected']);
                } elseif ($allApproved) {
                    $approvalRequestItem->update([
                        'status' => 'approved',
                        'approved_by' => $user->id,
                        'approved_at' => now(),
                    ]);
                } else {
                    $approvalRequestItem->update(['status' => 'on progress']);
                }
            }
        }

        // Update request-level status based on all items
        $approvalRequest = \App\Models\ApprovalRequest::findOrFail($validated['approval_request_id']);
        $allItems = $approvalRequest->items;

        $allItemsApproved = $allItems->every(fn($item) => $item->status === 'approved');
        $anyItemRejected = $allItems->contains(fn($item) => $item->status === 'rejected');

        if ($anyItemRejected) {
            $approvalRequest->update(['status' => 'rejected']);
        } elseif ($allItemsApproved) {
            $approvalRequest->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
            
            // Initialize purchasing items for approved request
            $approvalRequest->load('items');
            foreach ($approvalRequest->items as $item) {
                $exists = $approvalRequest->purchasingItems()
                    ->where('master_item_id', $item->master_item_id)
                    ->exists();
                if (!$exists) {
                    $approvalRequest->purchasingItems()->create([
                        'master_item_id' => $item->master_item_id,
                        'quantity' => (int)($item->quantity ?? 1),
                        'status' => 'unprocessed',
                    ]);
                }
            }
            $approvalRequest->refreshPurchasingStatus();
        } else {
            $approvalRequest->update(['status' => 'on progress']);
        }

        $message = $action === 'approve' 
            ? 'Item has been approved successfully!' 
            : 'Item has been rejected.';

        return redirect()
            ->route('approval-requests.show', [
                'approvalRequest' => $validated['approval_request_id'],
                'item_id' => $validated['master_item_id']
            ])
            ->with('success', $message);
    }
}
