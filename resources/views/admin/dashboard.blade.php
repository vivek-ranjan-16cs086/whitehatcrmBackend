@extends('layouts.admin')
@section('body-class', 'dashboard-page')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Your CRM at a glance')
@section('content')
<div class="fade-in dashboard-overview">
    <div class="dashboard-top-grid mb-4">
        <section class="dashboard-intro card">
            <div class="card-body">
                <span class="dashboard-eyebrow">WORKSPACE OVERVIEW</span>
                <h2>Welcome back,<br>{{ auth()->user()->name }}.</h2>
                <p class="text-muted">A clear view of your leads, your team, and what needs your attention today.</p>
                <span class="dashboard-today"><i data-lucide="calendar-days"></i>{{ now()->format('l, F j, Y') }}</span>
                <div class="dashboard-team-summary">
                    <a href="{{ route('admin.users.index') }}"><strong>{{ number_format($totalUsers) }}</strong><span>Total Users</span></a>
                    <a href="{{ route('admin.roles.index') }}"><strong>{{ number_format($totalRoles) }}</strong><span>Total Roles</span></a>
                    <a href="{{ route('admin.permissions.index') }}"><strong>{{ number_format($totalPermissions) }}</strong><span>Permissions</span></a>
                </div>
            </div>
            <svg class="dashboard-building" viewBox="0 0 240 280" fill="none" aria-hidden="true"><path d="M30 260V100L120 35L210 100V260" fill="#f1f5f9" stroke="#cbd5e1"/><path d="M120 35V260M30 100L120 145L210 100M30 145L120 190L210 145M30 190L120 235L210 190M65 75V260M165 68V260" stroke="#cbd5e1"/><path d="M8 260H232" stroke="#94a3b8"/></svg>
        </section>
        @if($canViewLeads)
        <div class="dashboard-lead-metrics">
            @foreach([
                ['total', 'Total Leads', $dashboardTotalLeads, 'users', 'All leads in your pipeline'],
                ['active', 'Active Leads', $activeLeads, 'activity', 'New, active and follow-up leads'],
                ['follow-ups', 'Follow-ups', $followUpLeads, 'calendar-clock', 'Leads awaiting follow-up'],
                ['booked', 'Bookings', $bookedLeads, 'badge-check', 'Successfully booked leads'],
            ] as [$view, $label, $count, $icon, $caption])
            <a href="{{ route('lead-management.index') }}" data-dashboard-lead-view="{{ $view }}" class="dashboard-metric card">
                <div class="dashboard-metric-heading"><span class="dashboard-metric-icon"><i data-lucide="{{ $icon }}"></i></span><span>{{ $label }}</span><i class="dashboard-metric-arrow" data-lucide="arrow-up-right"></i></div>
                <strong>{{ number_format($count) }}</strong>
                <span class="dashboard-metric-caption">{{ $caption }}</span>
            </a>
            @endforeach
        </div>
        @else
        <section class="card dashboard-access-note"><i data-lucide="shield-check"></i><h5>Your workspace is ready</h5><p class="text-muted mb-0">Manage your team, roles and permissions from this dashboard.</p></section>
        @endif
    </div>

    @if($canViewLeads)
    <div class="dashboard-analytics-grid mb-4">
        <section class="card dashboard-trend-panel">
            <div class="card-header"><div><span class="dashboard-eyebrow">LEAD ANALYTICS</span><h5>Lead activity</h5></div><span class="dashboard-chart-caption">{{ $chartStart->format('d M') }} – {{ $chartEnd->format('d M Y') }}</span></div>
            <div class="card-body">@include('admin.partials.pipeline-chart')</div>
        </section>
        <section class="card dashboard-distribution">
            <div class="card-header"><div><span class="dashboard-eyebrow">PIPELINE OVERVIEW</span><h5>Lead distribution</h5></div></div>
            <div class="card-body">
                @php
                    $segments = $leadCounts->sortDesc();
                    $segmentTotal = max(1, $segments->sum());
                    $offset = 0;
                    $shades = ['#2563eb', '#64748b', '#94a3b8', '#cbd5e1', '#e2e8f0', '#475569'];
                @endphp
                <div class="dashboard-donut">
                    <svg viewBox="0 0 200 200" role="img" aria-label="Current lead status distribution">
                        <circle cx="100" cy="100" r="74" fill="none" stroke="#f1f5f9" stroke-width="24"/>
                        @foreach($segments as $status => $count)
                            @php $portion = $count / $segmentTotal * 100; @endphp
                            <circle cx="100" cy="100" r="74" pathLength="100" fill="none" stroke="{{ $shades[$loop->index % count($shades)] }}" stroke-width="24" stroke-dasharray="{{ $portion }} {{ 100 - $portion }}" stroke-dashoffset="{{ -$offset }}" transform="rotate(-90 100 100)"><title>{{ ucwords(str_replace('_', ' ', $status)) }}: {{ number_format($count) }}</title></circle>
                            @php $offset += $portion; @endphp
                        @endforeach
                        <text x="100" y="98" text-anchor="middle" class="donut-total">{{ number_format($dashboardTotalLeads) }}</text>
                        <text x="100" y="118" text-anchor="middle" class="donut-caption">Total leads</text>
                    </svg>
                </div>
                <div class="dashboard-legend">
                    @forelse($segments as $status => $count)
                    <div><span class="dashboard-legend-dot" style="background: {{ $shades[$loop->index % count($shades)] }}"></span><span>{{ ucwords(str_replace('_', ' ', $status)) }}</span><strong>{{ number_format($count) }}</strong><small>{{ round($count / $segmentTotal * 100) }}%</small></div>
                    @empty
                    <p class="text-muted">No lead data to display yet.</p>
                    @endforelse
                </div>
                <p class="chart-note mt-3 mb-0">Current totals across all dates. The date filter applies to lead activity.</p>
            </div>
        </section>
    </div>
    <section class="action-required-section mb-4" aria-labelledby="actionRequiredHeading">
        <div class="action-required-heading"><div><span class="action-required-eyebrow">Keep your pipeline moving</span><h5 id="actionRequiredHeading">Action required</h5></div><span class="action-required-live"><span></span> Live overview</span></div>
        <div class="row g-3">
            @foreach([
                ['unassigned', 'Unassigned leads', $unassignedLeads, 'user-round-x'],
                ['overdue', 'Overdue follow-ups', $overdueFollowups, 'calendar-clock'],
                ['no-follow-up', 'No follow-up set', $noFollowup, 'calendar-plus'],
                ['stale', 'Stale leads · 7+ days', $staleLeads, 'hourglass'],
            ] as [$attention, $label, $count, $icon])
            <div class="col-xl-3 col-sm-6"><a class="action-required-card" href="{{ route('lead-management.index', ['attention' => $attention]) }}"><span class="action-required-icon"><i data-lucide="{{ $icon }}"></i></span><span class="action-required-label">{{ $label }}</span><strong>{{ number_format($count) }}</strong><span class="action-required-link">Review leads <i data-lucide="arrow-up-right"></i></span></a></div>
            @endforeach
        </div>
        <div class="dashboard-health-summary"><span>Pipeline Health <strong>{{ $pipelineHealth }}%</strong></span><div class="progress"><div class="progress-bar" role="progressbar" style="width: {{ $pipelineHealth }}%" aria-valuenow="{{ $pipelineHealth }}" aria-valuemin="0" aria-valuemax="100"></div></div><span>At Risk <strong>{{ number_format($atRiskLeads) }}</strong></span></div>
    </section>
    @endif

    <section class="card">
        <div class="card-header d-flex justify-content-between align-items-center"><div><span class="dashboard-eyebrow">YOUR TEAM</span><h5>Recent Users</h5></div><div class="d-flex gap-2"><a href="{{ route('admin.users.create') }}" class="btn btn-outline-primary btn-sm">Add New User</a><a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary btn-sm">View All</a></div></div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead><tbody>
        @forelse($recentUsers as $user)
        <tr><td><div class="d-flex align-items-center gap-3"><div class="avatar-circle">{{ strtoupper(substr($user->name, 0, 1)) }}</div><span class="fw-semibold">{{ $user->name }}</span></div></td><td><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></td><td><span class="role-badge">{{ $user->roles->first()?->name ?? 'none' }}</span></td><td><span class="badge bg-success">Active</span></td><td class="text-muted small">{{ $user->created_at->diffForHumans() }}</td><td><div class="btn-group"><a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit {{ $user->name }}"><i data-lucide="edit-2"></i></a><a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-primary" aria-label="View {{ $user->name }}"><i data-lucide="eye"></i></a></div></td></tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted py-4">No users to display.</td></tr>
        @endforelse
        </tbody></table></div>
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.lucide) lucide.createIcons();
    document.querySelectorAll('[data-dashboard-lead-view]').forEach(link => link.addEventListener('click', function () { sessionStorage.setItem('selectedLeadView', this.dataset.dashboardLeadView); }));
});
</script>
@endsection
