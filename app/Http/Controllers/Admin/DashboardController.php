<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Lead;
//added
use App\Services\PipelineHealthService;
use Illuminate\Support\Facades\DB;
//...
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $totalUsers       = User::count();
        $totalRoles       = Role::count();
        $totalPermissions = Permission::count();
        $totalLeads       = Lead::count();
        $recentUsers      = User::with('roles')->latest()->take(10)->get();
		//added
		
        // Dashboard lead statistics - permission aware
        $currentUser = auth()->user();

        $canViewLeads = $currentUser->hasCrmPermission('view.lead');

        $canViewTeam = $currentUser->hasRole('super-admin', 'web')
            || $currentUser->canViewAllLeads();

        $leadCounts = collect();

        if ($canViewLeads) {
            $leadQuery = Lead::query();

            if (!$canViewTeam) {
                $leadQuery->where('assigned_to', $currentUser->id);
            }

            // One grouped query instead of loading every lead
            $leadCounts = $leadQuery
                ->select('status')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');
        }

        $dashboardTotalLeads = $leadCounts->sum();

        $activeLeads = collect([
            'new', 'active', 'follow_up'
        ])->sum(fn ($status) => $leadCounts->get($status, 0));

        $followUpLeads = $leadCounts->get('follow_up', 0);

        $bookedLeads = $leadCounts->get('booked', 0);

        // Leads marked as expired follow-ups
      //  $atRiskLeads = $leadCounts->get('follow_up_expired', 0);
	  
        // Pipeline Health and At Risk
        $pipelineMetrics = [
            'pipeline_health' => 0,
            'at_risk_leads' => 0,
            'breakdown' => [
                'overdue_followups' => 0,
                'no_followup' => 0,
                'stale_leads' => 0,
            ],
        ];

        if ($canViewLeads) {
            $pipelineMetrics = app(PipelineHealthService::class)
                ->getMetrics($currentUser);
        }

        $pipelineHealth = $pipelineMetrics['pipeline_health'];

        $atRiskLeads = $pipelineMetrics['at_risk_leads'];

        $overdueFollowups =
            $pipelineMetrics['breakdown']['overdue_followups'];

        $noFollowup =
            $pipelineMetrics['breakdown']['no_followup'];

        $staleLeads =
            $pipelineMetrics['breakdown']['stale_leads'];



       
		//added
		
		return view('admin.dashboard', compact(
					'totalUsers',
					'totalRoles',
					'totalPermissions',
					'totalLeads',
					'recentUsers',
					'canViewLeads',
					'dashboardTotalLeads',
					'activeLeads',
					'followUpLeads',
					'bookedLeads',
					'atRiskLeads',
					'leadCounts',
					'pipelineHealth',
					'overdueFollowups',
					'noFollowup',
					'staleLeads'
				));

    }
}
