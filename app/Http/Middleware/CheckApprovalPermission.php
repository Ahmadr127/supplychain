<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApprovalPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $approvalRequest = $request->route('approvalRequest');
        
        if (!$approvalRequest) {
            abort(404, 'Approval request not found.');
        }

        // Check if user can approve this request
        $currentApprover = $approvalRequest->getCurrentApprover();
        
        if (!$currentApprover || $currentApprover->id !== auth()->id()) {
            abort(403, 'You are not authorized to approve this request.');
        }
        
        return $next($request);
    }
}
