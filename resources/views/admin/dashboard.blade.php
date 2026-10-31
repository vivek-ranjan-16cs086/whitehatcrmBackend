@extends('layouts.admin')

@section('body-class', 'dashboard-page')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Welcome back, ' . auth()->user()->name)

@section('content')
    <div class="fade-in">
        {{-- ── Welcome Section ── --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card dashboard-welcome-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="mb-1">Good {{ now()->format('l') }}, {{ auth()->user()->name }}!</h4>
                                <p class="text-muted mb-0">Here's what's happening with your CRM today.</p>
                            </div>
                            <div class="text-end">
                                <div class="text-muted small">{{ now()->format('F j, Y') }}</div>
                                <div class="text-muted small">{{ now()->format('l, g:i A') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

 {{-- ── added── --}}
 
@if($canViewLeads)
    <section class="action-required-section mb-4" aria-labelledby="actionRequiredHeading">
        <div class="action-required-heading">
            <div>
                <span class="action-required-eyebrow">Stay ahead of lead leakage</span>
                <h5 id="actionRequiredHeading" class="mb-0">Action required</h5>
            </div>
            <span class="action-required-live"><span></span> Live overview</span>
        </div>

        <div class="row g-3">
            <div class="col-xl-3 col-sm-6">
                <a class="action-required-card action-required-purple"
                   href="{{ route('lead-management.index', ['attention' => 'unassigned']) }}">
                    <span class="action-required-icon"><i data-lucide="user-round-x"></i></span>
                    <span class="action-required-label">Unassigned leads</span>
                    <strong>{{ number_format($unassignedLeads) }}</strong>
                    <span class="action-required-link">Review leads <i data-lucide="arrow-up-right"></i></span>
                </a>
            </div>
            <div class="col-xl-3 col-sm-6">
                <a class="action-required-card action-required-red"
                   href="{{ route('lead-management.index', ['attention' => 'overdue']) }}">
                    <span class="action-required-icon"><i data-lucide="calendar-clock"></i></span>
                    <span class="action-required-label">Overdue follow-ups</span>
                    <strong>{{ number_format($overdueFollowups) }}</strong>
                    <span class="action-required-link">Review leads <i data-lucide="arrow-up-right"></i></span>
                </a>
            </div>
            <div class="col-xl-3 col-sm-6">
                <a class="action-required-card action-required-amber"
                   href="{{ route('lead-management.index', ['attention' => 'no-follow-up']) }}">
                    <span class="action-required-icon"><i data-lucide="calendar-plus"></i></span>
                    <span class="action-required-label">No follow-up set</span>
                    <strong>{{ number_format($noFollowup) }}</strong>
                    <span class="action-required-link">Review leads <i data-lucide="arrow-up-right"></i></span>
                </a>
            </div>
            <div class="col-xl-3 col-sm-6">
                <a class="action-required-card action-required-blue"
                   href="{{ route('lead-management.index', ['attention' => 'stale']) }}">
                    <span class="action-required-icon"><i data-lucide="hourglass"></i></span>
                    <span class="action-required-label">Stale leads · 7+ days</span>
                    <strong>{{ number_format($staleLeads) }}</strong>
                    <span class="action-required-link">Review leads <i data-lucide="arrow-up-right"></i></span>
                </a>
            </div>
        </div>
    </section>
@endif

 
@if($canViewLeads)

<div class="dashboard-count-grid mb-4">

    <!-- Total Leads -->
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('lead-management.index') }}"
           data-dashboard-lead-view="total"
           class="text-decoration-none d-block">

            <div class="stat-card stat-blue h-100">
                <div class="stat-number">
                    {{ $dashboardTotalLeads }}
                </div>
                <div class="stat-label">
                    <i data-lucide="users" class="me-1"></i>
                    Total Leads
                </div>
                <div class="mt-3">
                    <small class="text-white-50">
                        View all leads
                    </small>
                </div>
            </div>
        </a>
    </div>

    <!-- Active Leads -->
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('lead-management.index') }}"
           data-dashboard-lead-view="active"
           class="text-decoration-none d-block">

            <div class="stat-card stat-green h-100">
                <div class="stat-number">
                    {{ $activeLeads }}
                </div>
                <div class="stat-label">
                    <i data-lucide="activity" class="me-1"></i>
                    Active Leads
                </div>
                <div class="mt-3">
                    <small class="text-white-50">
                        View active leads
                    </small>
                </div>
            </div>
        </a>
    </div>

    <!-- Follow Ups -->
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('lead-management.index') }}"
           data-dashboard-lead-view="follow-ups"
           class="text-decoration-none d-block">

            <div class="stat-card stat-orange h-100">
                <div class="stat-number">
                    {{ $followUpLeads }}
                </div>
                <div class="stat-label">
                    <i data-lucide="calendar-clock" class="me-1"></i>
                    Follow-ups
                </div>
                <div class="mt-3">
                    <small class="text-white-50">
                        View follow-up leads
                    </small>
                </div>
            </div>
        </a>
    </div>

    <!-- Booked Leads -->
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('lead-management.index') }}"
           data-dashboard-lead-view="booked"
           class="text-decoration-none d-block">

            <div class="stat-card stat-purple h-100">
                <div class="stat-number">
                    {{ $bookedLeads }}
                </div>
                <div class="stat-label">
                    <i data-lucide="badge-check" class="me-1"></i>
                    Bookings
                </div>
                <div class="mt-3">
                    <small class="text-white-50">
                        View booked leads
                    </small>
                </div>
            </div>
        </a>
    </div>

@endif

        {{-- ── Stat Cards ── --}}
        @if(!$canViewLeads)<div class="dashboard-count-grid mb-4">@endif
            <div class="col-xl-4 col-md-6">
			<a href="{{ route('admin.users.index') }}"
   class="text-decoration-none d-block h-100">
                <div class="stat-card stat-blue">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-number">{{ $totalUsers }}</div>
                            <div class="stat-label">
                                <i data-lucide="users" class="me-1"></i>
                                Total Users
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i data-lucide="users"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-white-50">Active user accounts</small>
                    </div>
                </div>
				</a>
            </div>
            <div class="col-xl-4 col-md-6">
			<a href="{{ route('admin.roles.index') }}"
   class="text-decoration-none d-block h-100">
                <div class="stat-card stat-green">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-number">{{ $totalRoles }}</div>
                            <div class="stat-label">
                                <i data-lucide="shield" class="me-1"></i>
                                Total Roles
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i data-lucide="shield"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-white-50">User role definitions</small>
                    </div>
                </div>
				</a>
            </div>
            <div class="col-xl-4 col-md-6">
			<a href="{{ route('admin.permissions.index') }}"
   class="text-decoration-none d-block h-100">
                <div class="stat-card stat-orange">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-number">{{ $totalPermissions }}</div>
                            <div class="stat-label">
                                <i data-lucide="key" class="me-1"></i>
                                Permissions
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i data-lucide="key"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-white-50">Access permissions</small>
                    </div>
                </div>
				</a>
            </div>
        </div>

{{-- Pipeline Health and At Risk --}}
@if($canViewLeads)

<div class="row g-4 mb-4">

    {{-- Pipeline Health --}}
    <div class="col-xl-8">
        <div class="card h-100">

            <div class="card-header">
                <h6 class="mb-0">
                    <i data-lucide="activity" class="me-2"></i>
                    Pipeline Health
                </h6>
            </div>

            <div class="card-body">

                <div class="d-flex align-items-center gap-3 mb-3">
                    <h2 class="fw-bold mb-0">
                        {{ $pipelineHealth }}%
                    </h2>

                    <span class="text-muted">
                        Composite score
                    </span>
                </div>

                <div class="progress" style="height: 14px;">
                    <div class="progress-bar bg-success"
                         role="progressbar"
                         style="width: {{ $pipelineHealth }}%;"
                         aria-valuenow="{{ $pipelineHealth }}"
                         aria-valuemin="0"
                         aria-valuemax="100">
                    </div>
                </div>

            </div>

        </div>
    </div>

    {{-- At Risk --}}
    <div class="col-xl-4">
        <div class="card h-100">

            <div class="card-header">
                <h6 class="mb-0">
                    <i data-lucide="alert-triangle" class="me-2"></i>
                    At Risk
                </h6>
            </div>

            <div class="card-body">

                <h2 class="fw-bold mb-2">
                    {{ number_format($atRiskLeads) }}
                </h2>

                <p class="text-muted mb-0">
                    At-risk Leads
                </p>

            </div>

        </div>
    </div>

</div>

{{-- Risk Breakdown --}}
<div class="row g-3 mb-4">

    <div class="col-md-4">
    <div class="risk-breakdown-item">
        <span class="text-muted">Overdue</span>
        <strong>{{ number_format($overdueFollowups) }}</strong>
    </div>
    </div>

    <div class="col-md-4">
    <div class="risk-breakdown-item">
        <span class="text-muted">No follow-up</span>
        <strong>{{ number_format($noFollowup) }}</strong>
    </div>
    </div>

    <div class="col-md-4">
    <div class="risk-breakdown-item">
        <span class="text-muted">Stale</span>
        <strong>{{ number_format($staleLeads) }}</strong>
    </div>
    </div>

</div>

@endif


{{-- Performance Ranking --}}


        {{-- ── Charts and Analytics Row ── --}}
        <div class="row g-4 mb-4">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i data-lucide="bar-chart-3" class="me-2"></i>
                            Lead pipeline
                        </h6>
                        @if($canViewLeads)
                            <span class="dashboard-chart-caption">Leads by status</span>
                        @endif
                    </div>
                    <div class="card-body dashboard-chart-body">
                        @if($canViewLeads)
                            @include('admin.partials.pipeline-chart')
                        @else
                            <p class="text-muted">Your account does not have permission to view lead data.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i data-lucide="activity" class="me-2"></i>
                            Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                                <i data-lucide="user-plus" class="me-2"></i>
                                Add New User
                            </a>
                            <a href="{{ route('admin.roles.create') }}" class="btn btn-outline-primary">
                                <i data-lucide="user-check" class="me-2"></i>
                                Create Role
                            </a>
                            <a href="" class="btn btn-outline-success">
                                <i data-lucide="plus" class="me-2"></i>
                                New Lead
                            </a>
                            <button class="btn btn-outline-secondary">
                                <i data-lucide="download" class="me-2"></i>
                                Export Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Recent Users ── --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i data-lucide="clock" class="me-2"></i>
                    Recent Users
                </h6>
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-primary">
                    <i data-lucide="eye" class="me-1"></i>
                    View All
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentUsers as $user)
                                @php $roleName = $user->roles->first()?->name ?? 'none'; @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar-circle">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                            <div>
                                                <div class="fw-semibold">{{ $user->name }}</div>
                                                <small
                                                    class="text-muted">{{ '@' . strtolower(str_replace(' ', '', $user->name)) }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="mailto:{{ $user->email }}" class="text-decoration-none">{{ $user->email }}</a>
                                    </td>
                                    <td>
                                        <span
                                            class="role-badge role-{{ str_replace([' ', '_'], '-', $roleName) }} role-default">
                                            {{ $roleName }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                    </td>
                                    <td class="text-muted small">{{ $user->created_at->diffForHumans() }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                                class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i data-lucide="edit-2"></i>
                                            </a>
                                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-info"
                                                title="View">
                                                <i data-lucide="eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        //added
document.addEventListener('DOMContentLoaded', function () {

    lucide.createIcons();

    document.querySelectorAll('[data-dashboard-lead-view]')
        .forEach(function(link) {

            link.addEventListener('click', function() {
                sessionStorage.setItem(
                    'selectedLeadView',
                    this.dataset.dashboardLeadView
                );
            });

        });

});
    </script>
	
@endsection
