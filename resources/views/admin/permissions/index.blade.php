@extends('layouts.admin')

@section('title', 'Permissions')
@section('page-title', 'Permissions')
@section('page-subtitle', 'Permission matrix across all roles')

@section('content')
{{-- Sync button --}}
<div class="permissions-page">

    {{-- Top Action --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0 fw-bold">Permission Matrix</h5>

        <form action="{{ route('admin.permissions.sync') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-success btn-sm"
                    onclick="return confirm('Sync permissions from all models?')">
                <i class="bi bi-arrow-repeat me-1"></i> Sync
            </button>
        </form>
    </div>

    @if(empty($grouped))
        <div class="alert alert-info">
            No permissions found. Click <strong>Sync</strong>.
        </div>
    @endif

    @foreach($grouped as $model => $perms)
        <div class="card permission-card mb-4">

            <div class="card-header d-flex align-items-center">
                <i class="bi bi-box text-primary me-2"></i>
                <span class="fw-bold text-uppercase">{{ $model }}</span>

                <span class="badge bg-light text-dark ms-auto">
                    {{ count($perms) }}
                </span>
            </div>

            <div class="table-responsive">
                <table class="table permission-table mb-0">
                    <thead>
                        <tr>
                            <th>Permission</th>

                            @foreach($roles as $role)
                                @php
                                    $roleColors = [
                                        'admin'       => 'bg-primary',
                                        'manager'     => 'bg-success',
                                        'salesperson' => 'bg-warning text-dark',
                                    ];
                                    $badgeClass = $roleColors[$role->name] ?? 'bg-secondary';
                                @endphp

                                <th class="text-center">
                                    <span class="badge {{ $badgeClass }}">
                                        {{ $role->name }}
                                    </span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($perms as $perm)
                            @php $action = explode('.', $perm->name)[0]; @endphp
                            <tr>
                                <td>
                                    <span class="action-badge action-{{ $action }}">
                                        {{ $action }}
                                    </span>
                                    <code class="perm-code">{{ $perm->name }}</code>
                                </td>

                                @foreach($roles as $role)
                                    <td class="text-center">
                                        @if(($matrix[$model][$role->name][$perm->name] ?? false))
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                        @else
                                            <i class="bi bi-x-circle text-muted"></i>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>

        </div>
    @endforeach

</div>

@endsection
