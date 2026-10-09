@extends('layouts.admin')

@section('title', 'Roles')
@section('page-title', 'Roles')
@section('page-subtitle', 'Manage access roles and their permissions')

@section('content')
<div class="roles-page">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0 fw-bold">All Roles</h5>
        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> New Role
        </a>
    </div>

    <div class="row g-4">
        @foreach($roles as $role)
            @php
                $grouped = [];
                foreach ($role->permissions as $perm) {
                    $parts = explode('.', $perm->name);
                    $model = $parts[1] ?? 'general';
                    $grouped[$model][] = $parts[0];
                }

                $isSuperAdmin = $role->name === 'super-admin';
                $shieldColor = $isSuperAdmin ? 'text-danger' : 'text-primary';
            @endphp

            <div class="col-md-6 col-xl-6">
                <div class="card role-card h-100">

                    <div class="card-header d-flex justify-content-between align-items-start">
                        <div class="d-flex gap-2">
                            <i class="bi bi-shield-fill fs-5 {{ $shieldColor }}"></i>
                            <div>
                                <div class="fw-bold text-capitalize">{{ $role->name }}</div>
                                <div class="text-muted small">
                                    {{ $isSuperAdmin ? 'Full access role' : $role->permissions->count().' permissions' }}
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-1">
                            @if(!$isSuperAdmin)
                                <a href="{{ route('admin.roles.edit', $role) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <button type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="myConfirm('admin/roles/delete/{{ $role->id }}')">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="card-body">
                        @if($isSuperAdmin)
                            <span class="badge bg-danger">
                                <i class="bi bi-infinity me-1"></i> Full System Access
                            </span>
                        @elseif($grouped)
                            @foreach($grouped as $model => $actions)
                                <div class="perm-group">
                                    <div class="perm-title">{{ $model }}</div>

                                    <div class="perm-actions">
                                        @foreach($actions as $action)
                                            <span class="action-badge action-{{ $action }}">
                                                {{ $action }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <span class="text-muted small">No permissions assigned</span>
                        @endif
                    </div>

                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
function loadAjaxList() {
    location.reload(); 
}
</script>
@endpush
