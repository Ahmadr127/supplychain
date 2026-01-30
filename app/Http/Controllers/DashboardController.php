<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Dashboard\MyRequestsStatsService;
use App\Services\Dashboard\PendingApprovalsStatsService;
use App\Services\Dashboard\ProcessPurchasingStatsService;
use App\Services\Dashboard\PendingReleasesStatsService;
use App\Services\Dashboard\RecentUpdatesService;

class DashboardController extends Controller
{
    protected $myRequestsStatsService;
    protected $pendingApprovalsStatsService;
    protected $processPurchasingStatsService;
    protected $pendingReleasesStatsService;
    protected $recentUpdatesService;
    
    public function __construct(
        MyRequestsStatsService $myRequestsStatsService,
        PendingApprovalsStatsService $pendingApprovalsStatsService,
        ProcessPurchasingStatsService $processPurchasingStatsService,
        PendingReleasesStatsService $pendingReleasesStatsService,
        RecentUpdatesService $recentUpdatesService
    ) {
        $this->myRequestsStatsService = $myRequestsStatsService;
        $this->pendingApprovalsStatsService = $pendingApprovalsStatsService;
        $this->processPurchasingStatsService = $processPurchasingStatsService;
        $this->pendingReleasesStatsService = $pendingReleasesStatsService;
        $this->recentUpdatesService = $recentUpdatesService;
    }
    
    public function index()
    {
        $user = Auth::user();
        
        // Initialize stats arrays
        $myRequestsStats = null;
        $pendingApprovalsStats = null;
        $processPurchasingStats = null;
        $pendingReleasesStats = null;
        $recentUpdates = collect();
        
        // Get stats based on user permissions
        try {
            if ($user->hasPermission('view_my_approvals')) {
                $myRequestsStats = [
                    'stats' => $this->myRequestsStatsService->getStats(),
                    'breakdown' => $this->myRequestsStatsService->getBreakdown(),
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Dashboard: Failed to load My Requests stats', ['error' => $e->getMessage()]);
        }
        
        try {
            if ($user->hasPermission('approval')) {
                $pendingApprovalsStats = [
                    'stats' => $this->pendingApprovalsStatsService->getStats(),
                    'breakdown' => $this->pendingApprovalsStatsService->getBreakdown(),
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Dashboard: Failed to load Pending Approvals stats', ['error' => $e->getMessage()]);
        }
        
        try {
            if ($user->hasPermission('view_process_purchasing')) {
                $processPurchasingStats = [
                    'stats' => $this->processPurchasingStatsService->getStats(),
                    'breakdown' => $this->processPurchasingStatsService->getBreakdown(),
                    'need_attention' => $this->processPurchasingStatsService->getNeedAttentionCount(),
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Dashboard: Failed to load Process Purchasing stats', ['error' => $e->getMessage()]);
        }
        
        try {
            if ($user->hasPermission('view_pending_release')) {
                $pendingReleasesStats = [
                    'stats' => $this->pendingReleasesStatsService->getStats(),
                    'breakdown' => $this->pendingReleasesStatsService->getBreakdown(),
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Dashboard: Failed to load Pending Releases stats', ['error' => $e->getMessage()]);
        }
        
        // Get recent updates
        try {
            $recentUpdates = $this->recentUpdatesService->getRecentUpdates(10);
        } catch (\Exception $e) {
            \Log::error('Dashboard: Failed to load Recent Updates', ['error' => $e->getMessage()]);
        }
        
        return view('dashboard', compact(
            'user',
            'myRequestsStats',
            'pendingApprovalsStats',
            'processPurchasingStats',
            'pendingReleasesStats',
            'recentUpdates'
        ));
    }
}
