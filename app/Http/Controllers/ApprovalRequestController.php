<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalStep;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApprovalRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ApprovalRequest::with(['workflow', 'requester', 'currentStep']);

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
        
        return view('approval-requests.create', compact('workflows'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'workflow_id' => 'required|exists:approval_workflows,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $workflow = ApprovalWorkflow::findOrFail($request->workflow_id);
        
        $approvalRequest = $workflow->createRequest(
            requesterId: auth()->id(),
            title: $request->title,
            description: $request->description,
            data: $request->data
        );

        return redirect()->route('approval-requests.show', $approvalRequest)
                        ->with('success', 'Approval request berhasil dibuat!');
    }

    public function show(ApprovalRequest $approvalRequest)
    {
        $approvalRequest->load([
            'workflow', 
            'requester', 
            'steps.approver', 
            'steps.approverRole', 
            'steps.approverDepartment',
            'steps.approvedBy'
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
        
        return view('approval-requests.edit', compact('approvalRequest', 'workflows'));
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
            'data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $approvalRequest->update([
            'title' => $request->title,
            'description' => $request->description,
            'data' => $request->data
        ]);

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
        $query = auth()->user()->approvalRequests()->with(['workflow', 'currentStep']);

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
        
        // Find approval steps that are pending and user can approve
        $query = ApprovalStep::where('status', 'pending')
                            ->whereHas('request', function($q) {
                                $q->where('status', 'pending'); // Only pending requests
                            })
                            ->where(function($q) use ($userDepartments, $userRoles, $user) {
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
                            ->with(['request.workflow', 'request.requester', 'approver', 'approverRole', 'approverDepartment']);

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
}
