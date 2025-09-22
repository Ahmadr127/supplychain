<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalStep;
use App\Models\ApprovalRequestAttachment;
use App\Models\MasterItem;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ApprovalRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ApprovalRequest::with(['workflow', 'requester', 'currentStep', 'steps.approver', 'steps.approverRole', 'steps.approverDepartment']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
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
        $workflows = ApprovalWorkflow::where('is_active', true)->get();
        $masterItems = MasterItem::active()->with(['itemType', 'itemCategory', 'commodity', 'unit'])->get();
        
        // Add dropdown data for the modal
        $itemTypes = \App\Models\ItemType::where('is_active', true)->get();
        $itemCategories = \App\Models\ItemCategory::where('is_active', true)->get();
        $commodities = \App\Models\Commodity::where('is_active', true)->get();
        $units = \App\Models\Unit::where('is_active', true)->get();
        
        return view('approval-requests.create', compact('workflows', 'masterItems', 'itemTypes', 'itemCategories', 'commodities', 'units'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'workflow_id' => 'required|exists:approval_workflows,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'request_number' => 'nullable|string|max:255|unique:approval_requests,request_number',
            'request_type' => 'required|in:normal,cto',
            'items' => 'nullable|array',
            'items.*.master_item_id' => 'required_with:items|exists:master_items,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf|max:10240' // Max 10MB per file
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $workflow = ApprovalWorkflow::findOrFail($request->workflow_id);
        
        // Generate request number if not provided
        $requestNumber = $request->request_number;
        if (empty($requestNumber)) {
            // Get user's primary department
            $user = auth()->user();
            $primaryDepartment = $user->departments()->wherePivot('is_primary', true)->first();
            $departmentCode = $primaryDepartment ? $primaryDepartment->code : 'UNKNOWN';
            
            // Format: AZ/FARMASI/190925/PPBJ-0004
            $dateCode = date('dmy'); // Format: 190925 (tanggal-bulan-tahun)
            $sequenceNumber = ApprovalRequest::whereDate('created_at', today())->count() + 1;
            $requestNumber = "AZ/{$departmentCode}/{$dateCode}/PPBJ-" . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
        }
        
        $approvalRequest = $workflow->createRequest(
            requesterId: auth()->id(),
            title: $request->title,
            description: $request->description,
            requestNumber: $requestNumber,
            priority: $request->request_type === 'cto' ? 'high' : 'normal',
            isCtoRequest: $request->request_type === 'cto'
        );

        // Handle items
        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $itemData) {
                if (!empty($itemData['master_item_id'])) {
                    $masterItem = MasterItem::findOrFail($itemData['master_item_id']);
                    $unitPrice = $itemData['unit_price'] ?? $masterItem->total_price;
                    $totalPrice = $itemData['quantity'] * $unitPrice;

                    $approvalRequest->masterItems()->attach($itemData['master_item_id'], [
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'notes' => $itemData['notes'] ?? null
                    ]);
                }
            }
        }

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $originalName = $file->getClientOriginalName();
                    $fileName = time() . '_' . $originalName;
                    $filePath = $file->storeAs('approval-attachments', $fileName, 'public');

                    ApprovalRequestAttachment::create([
                        'approval_request_id' => $approvalRequest->id,
                        'original_name' => $originalName,
                        'file_name' => $fileName,
                        'file_path' => $filePath,
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'description' => null
                    ]);
                }
            }
        }

        return redirect()->route('approval-requests.show', $approvalRequest)
                        ->with('success', 'Approval request berhasil dibuat!');
    }

    public function show(ApprovalRequest $approvalRequest)
    {
        // Check if user has permission to view this approval request
        $user = auth()->user();
        
        // Allow if user has view_all_approvals permission
        if ($user->hasPermission('view_all_approvals')) {
            // User can view all approval requests
        }
        // Allow if user is the requester and has view_my_approvals permission
        elseif ($user->hasPermission('view_my_approvals') && $approvalRequest->requester_id === $user->id) {
            // User can view their own requests
        }
        // Allow if user can approve this request and has view_pending_approvals permission
        elseif ($user->hasPermission('view_pending_approvals') && $approvalRequest->canApprove($user->id)) {
            // User can view requests they can approve
        }
        else {
            abort(403, 'Anda tidak memiliki akses untuk melihat approval request ini.');
        }

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
            'attachments'
        ]);
        
        return view('approval-requests.show', compact('approvalRequest'));
    }

    public function edit(ApprovalRequest $approvalRequest)
    {
        // Only allow edit if status is pending and user is the requester
        if ($approvalRequest->status !== 'pending' || $approvalRequest->requester_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit request ini.');
        }

        $workflows = ApprovalWorkflow::where('is_active', true)->get();
        $masterItems = MasterItem::active()->with(['itemType', 'itemCategory', 'commodity', 'unit'])->get();
        
        // Add dropdown data for the modal
        $itemTypes = \App\Models\ItemType::where('is_active', true)->get();
        $itemCategories = \App\Models\ItemCategory::where('is_active', true)->get();
        $commodities = \App\Models\Commodity::where('is_active', true)->get();
        $units = \App\Models\Unit::where('is_active', true)->get();
        
        $approvalRequest->load(['masterItems', 'attachments']);
        
        return view('approval-requests.edit', compact('approvalRequest', 'workflows', 'masterItems', 'itemTypes', 'itemCategories', 'commodities', 'units'));
    }

    public function update(Request $request, ApprovalRequest $approvalRequest)
    {
        // Only allow update if status is pending and user is the requester
        if ($approvalRequest->status !== 'pending' || $approvalRequest->requester_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit request ini.');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'request_number' => 'nullable|string|max:255|unique:approval_requests,request_number,' . $approvalRequest->id,
            'request_type' => 'required|in:normal,cto',
            'items' => 'nullable|array',
            'items.*.master_item_id' => 'required_with:items|exists:master_items,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf|max:10240', // Max 10MB per file
            'remove_attachments' => 'nullable|array',
            'remove_attachments.*' => 'exists:approval_request_attachments,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $approvalRequest->update([
            'title' => $request->title,
            'description' => $request->description,
            'request_number' => $request->request_number ?: $approvalRequest->request_number,
            'priority' => $request->request_type === 'cto' ? 'high' : 'normal',
            'is_cto_request' => $request->request_type === 'cto'
        ]);

        // Handle items update
        if ($request->has('items') && is_array($request->items)) {
            // Remove existing items
            $approvalRequest->masterItems()->detach();
            
            // Add new items
            foreach ($request->items as $itemData) {
                if (!empty($itemData['master_item_id'])) {
                    $masterItem = MasterItem::findOrFail($itemData['master_item_id']);
                    $unitPrice = $itemData['unit_price'] ?? $masterItem->total_price;
                    $totalPrice = $itemData['quantity'] * $unitPrice;

                    $approvalRequest->masterItems()->attach($itemData['master_item_id'], [
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'notes' => $itemData['notes'] ?? null
                    ]);
                }
            }
        }

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $originalName = $file->getClientOriginalName();
                    $fileName = time() . '_' . $originalName;
                    $filePath = $file->storeAs('approval-attachments', $fileName, 'public');

                    ApprovalRequestAttachment::create([
                        'approval_request_id' => $approvalRequest->id,
                        'original_name' => $originalName,
                        'file_name' => $fileName,
                        'file_path' => $filePath,
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'description' => null
                    ]);
                }
            }
        }

        // Handle removal of attachments
        if ($request->has('remove_attachments') && is_array($request->remove_attachments)) {
            foreach ($request->remove_attachments as $attachmentId) {
                $attachment = ApprovalRequestAttachment::find($attachmentId);
                if ($attachment && $attachment->approval_request_id == $approvalRequest->id) {
                    $attachment->delete(); // This will also delete the file from storage
                }
            }
        }

        return redirect()->route('approval-requests.show', $approvalRequest)
                        ->with('success', 'Approval request berhasil diperbarui!');
    }

    public function destroy(ApprovalRequest $approvalRequest)
    {
        // Only allow delete if status is pending and user is the requester
        if ($approvalRequest->status !== 'pending' || $approvalRequest->requester_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus request ini.');
        }

        $approvalRequest->delete();
        return redirect()->route('approval-requests.index')->with('success', 'Approval request berhasil dihapus!');
    }

    public function approve(Request $request, ApprovalRequest $approvalRequest)
    {
        $validator = Validator::make($request->all(), [
            'comments' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if request is still pending
        if ($approvalRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Request ini sudah tidak dalam status pending.');
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

        // Check if request is still pending
        if ($approvalRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Request ini sudah tidak dalam status pending.');
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
        // Only allow cancel if status is pending and user is the requester
        if ($approvalRequest->status !== 'pending' || $approvalRequest->requester_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses untuk membatalkan request ini.');
        }

        $success = $approvalRequest->cancel(auth()->id());

        if ($success) {
            return redirect()->route('approval-requests.index')->with('success', 'Request berhasil dibatalkan!');
        } else {
            return redirect()->back()->with('error', 'Gagal membatalkan request.');
        }
    }

    public function myRequests(Request $request)
    {
        $query = auth()->user()->approvalRequests()->with(['workflow', 'currentStep', 'steps.approver', 'steps.approverRole', 'steps.approverDepartment']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
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
        
        // Find approval steps that user can approve (both pending and completed)
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
                                  });
                            })
                            ->with(['request.workflow', 'request.requester', 'request.steps.approver', 'request.steps.approverRole', 'request.steps.approverDepartment', 'approver', 'approverRole', 'approverDepartment']);

        // Status filter - show all by default, but allow filtering
        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->where('status', 'pending')
                      ->whereHas('request', function($q) {
                          $q->where('status', 'pending');
                      });
            } elseif ($request->status === 'completed') {
                $query->whereIn('status', ['approved', 'rejected'])
                      ->whereHas('request', function($q) {
                          $q->whereIn('status', ['approved', 'rejected']);
                      });
            }
        } else {
            // Show both pending and completed by default
            $query->whereHas('request', function($q) {
                $q->whereIn('status', ['pending', 'approved', 'rejected']);
            });
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('request', function($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
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

        // Type filter
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

    public function downloadAttachment(ApprovalRequestAttachment $attachment)
    {
        $user = auth()->user();
        $approvalRequest = $attachment->approvalRequest;
        
        // Check if user has access to this attachment
        $hasAccess = false;
        
        // Allow if user has view_all_approvals permission
        if ($user->hasPermission('view_all_approvals')) {
            $hasAccess = true;
        }
        // Allow if user is the requester and has view_my_approvals permission
        elseif ($user->hasPermission('view_my_approvals') && $approvalRequest->requester_id === $user->id) {
            $hasAccess = true;
        }
        // Allow if user can approve this request and has view_pending_approvals permission
        elseif ($user->hasPermission('view_pending_approvals') && $approvalRequest->canApprove($user->id)) {
            $hasAccess = true;
        }
        
        if (!$hasAccess) {
            abort(403, 'Anda tidak memiliki akses untuk mengunduh file ini.');
        }

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->original_name);
    }

    public function viewAttachment(ApprovalRequestAttachment $attachment)
    {
        $user = auth()->user();
        $approvalRequest = $attachment->approvalRequest;
        
        // Check if user has access to this attachment
        $hasAccess = false;
        
        // Allow if user has view_all_approvals permission
        if ($user->hasPermission('view_all_approvals')) {
            $hasAccess = true;
        }
        // Allow if user is the requester and has view_my_approvals permission
        elseif ($user->hasPermission('view_my_approvals') && $approvalRequest->requester_id === $user->id) {
            $hasAccess = true;
        }
        // Allow if user can approve this request and has view_pending_approvals permission
        elseif ($user->hasPermission('view_pending_approvals') && $approvalRequest->canApprove($user->id)) {
            $hasAccess = true;
        }
        
        if (!$hasAccess) {
            abort(403, 'Anda tidak memiliki akses untuk melihat file ini.');
        }

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }

        // Check if file is PDF
        if ($attachment->mime_type !== 'application/pdf') {
            abort(400, 'File ini bukan PDF dan tidak dapat ditampilkan.');
        }

        $filePath = Storage::disk('public')->path($attachment->file_path);
        
        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $attachment->original_name . '"'
        ]);
    }
}
