<?php

namespace App\Http\Controllers\Api;

use App\Models\Lead;
use App\Models\Notification;
use App\Models\Target;
use App\Models\User;
use App\Services\PipelineHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class HomeController extends BaseController
{
    public function __invoke(Request $request, PipelineHealthService $pipeline): JsonResponse
    {
        $user = $request->user('api');
        $team = $user->canViewAllLeads();
        $now = now()->toImmutable();

        $leadCounts = Lead::query()
            ->when(!$team, fn ($query) => $query->where('assigned_to', $user->id))
            ->selectRaw('status, COUNT(*) as total, COUNT(assigned_to) as assigned')
            ->groupBy('status')
            ->get();
        $statusCounts = $leadCounts->pluck('total', 'status');
        $total = (int) $leadCounts->sum('total');
        $assigned = (int) $leadCounts->sum('assigned');
        $booked = (int) $statusCounts->get('booked', 0);
        $assignedBooked = (int) ($leadCounts->firstWhere('status', 'booked')?->assigned ?? 0);
        $active = (int) $statusCounts->only(['new', 'active', 'follow_up'])->sum();
        $conversion = $assigned > 0
            ? (int) round($assignedBooked / $assigned * 100)
            : 0;
        [$visits, $bookings] = $this->weeklyAchievements($user, $team, $now->toDateString());

        $cards = [
            ['key' => 'total_leads', 'label' => 'Total Leads', 'value' => $total],
            ...($team ? [['key' => 'unassigned_leads', 'label' => 'Unassigned Leads', 'value' => $total - $assigned]] : []),
            ...(!$team ? [['key' => 'booked_leads', 'label' => 'Booked Leads', 'value' => $booked]] : []),
            ['key' => 'active_leads', 'label' => 'Active Leads', 'value' => $active],
            ['key' => 'followups', 'label' => 'Follow-ups', 'value' => (int) $statusCounts->get('follow_up', 0)],
            ...($team ? [
                ['key' => 'expired_leads', 'label' => 'Expired Follow-ups', 'value' => (int) $statusCounts->get('follow_up_expired', 0)],
                ['key' => 'booked_leads', 'label' => 'Booked Leads', 'value' => $booked],
            ] : []),
            ['key' => 'performance_percentage', 'label' => 'Performance & Ranking', 'value' => $conversion],
            ['key' => 'weekly_visit_target', 'label' => 'Weekly Target (Visit)', 'value' => $visits],
            ['key' => 'weekly_booking_target', 'label' => 'Weekly Target (Booking)', 'value' => $bookings],
        ];

        return $this->sendResponse('Home data retrieved successfully', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'roles' => $user->getRoleNames()->values(),
                'profile_picture_url' => $user->profile_pic ? Storage::disk('public')->url($user->profile_pic) : null,
            ],
            'scope' => $team ? 'team' : 'self',
            'date' => $now->toDateString(),
            'unread_notifications' => Notification::where('user_id', $user->id)->where('is_read', false)->count(),
            'cards' => $cards,
            'decision_focus' => $team ? $pipeline->getMetrics($user) : null,
            'next_best_actions' => [
                'due_followups' => [
                    'count' => Lead::dueForFollowUp($now, $now->addHours(12))->where('assigned_to', $user->id)->count(),
                    'scope' => 'self',
                    'window_hours' => 12,
                ],
            ],
        ]);
    }

    private function weeklyAchievements(User $user, bool $team, string $today): array
    {
        $targets = Target::query()
            ->where('status', 'active')
            ->whereDate('start_day', '<=', $today)
            ->whereDate('end_day', '>=', $today)
            ->when(!$team, fn ($query) => $query->where('user_id', $user->id))
            ->get(['user_id', 'start_day', 'end_day']);

        $visits = 0;
        $bookings = 0;
        foreach ($targets->unique(fn (Target $target) => [$target->user_id, $target->start_day, $target->end_day]) as $target) {
            $start = Carbon::parse($target->start_day)->startOfDay();
            $end = Carbon::parse($target->end_day)->endOfDay();
            $leads = Lead::where('assigned_to', $target->user_id);

            $visits += (clone $leads)->where('visit_status', 'visited')
                ->whereBetween('visit_date', [$start->toDateString(), $end->toDateString()])->count();
            $bookings += $leads->where('status', 'booked')->whereBetween('created_at', [$start, $end])->count();
        }

        return [$visits, $bookings];
    }
}
