<?php
namespace App\Services;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PipelineHealthService
{
    public function getMetrics(User $user): array
    {
        $canViewTeam = $user->hasRole('super-admin', 'web')
            || $user->canViewAllLeads();

        $query = Lead::query();

        if (!$canViewTeam) {
            $query->where('assigned_to', $user->id);
        }

        $now = Carbon::now();

        $totalLeads = (clone $query)->count();

        $bookedLeads = (clone $query)
            ->where('status', 'booked')
            ->count();

        $activeQuery = (clone $query)
            ->whereIn('status', [
                'new', 'active', 'follow_up'
            ]);

        $activeLeads = (clone $activeQuery)->count();

        $unassigned = (clone $activeQuery)
            ->whereNull('assigned_to')
            ->count();

        $withFollowUp = (clone $activeQuery)
            ->whereNotNull('follow_up_date')
            ->count();

        $freshLeads = (clone $activeQuery)
            ->where('created_at', '>=', $now->copy()->subDays(3))
            ->count();

        $overdue = (clone $activeQuery)
            ->whereNotNull('follow_up_date')
            ->whereDate('follow_up_date', '<', $now->toDateString())
            ->count();

        $noFollowUp = (clone $activeQuery)
            ->whereNull('follow_up_date')
            ->count();

        $stale = (clone $activeQuery)
            ->where('created_at', '<', $now->copy()->subDays(7))
            ->count();

        $conversion = $totalLeads > 0
            ? ($bookedLeads / $totalLeads) * 100
            : 0;

        $followUpHealth = $activeLeads > 0
            ? ($withFollowUp / $activeLeads) * 100
            : 0;

        $agingScore = $activeLeads > 0
            ? ($freshLeads / $activeLeads) * 100
            : 0;

        // Preserve existing Android API formula
        $activityScore = 70;
        $velocityScore = 50;

        $pipelineHealth = $totalLeads > 0
            ? round(
                ($conversion * 0.25) +
                ($followUpHealth * 0.25) +
                ($activityScore * 0.20) +
                ($agingScore * 0.15) +
                ($velocityScore * 0.15)
            )
            : 0;

        return [
            'pipeline_health' => $pipelineHealth,
            'at_risk_leads' => $noFollowUp,
            'breakdown' => [
                'unassigned_leads' => $unassigned,
                'overdue_followups' => $overdue,
                'no_followup' => $noFollowUp,
                'stale_leads' => $stale,
            ],
        ];
    }
}
