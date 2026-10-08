@extends('layouts.admin')

@section('title', 'Edit Role')
@section('page-title', 'Edit Role')
@section('page-subtitle', 'Update role name and permissions')

@section('content')
<div class="roles-form-page">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">

            <div class="card role-form-card">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-pencil-square me-2 text-primary"></i>
                        Edit — {{ $role->name }}
                    </h6>
                </div>

                <div class="card-body">
                    <form action="{{ route('admin.roles.update', $role) }}" method="POST" novalidate>
                        @csrf
                        @method('PUT')

                        {{-- Role Name --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Role Name</label>
                            <input type="text" name="name"
                                   value="{{ old('name', $role->name) }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="e.g. editor" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Permissions --}}
                        @if($permissions)
                            <h6 class="fw-bold mb-3">Assign Permissions</h6>

                            <div class="perm-grid">
                                @foreach($permissions as $model => $perms)
                                    <div class="perm-card">

                                        <div class="perm-card-header">
                                            <span>{{ ucfirst($model) }}</span>
                                            <button type="button"
                                                class="btn btn-sm btn-light select-all-btn"
                                                data-group="{{ $model }}">
                                                Select All
                                            </button>
                                        </div>

                                        <div class="perm-card-body">
                                            @foreach($perms as $perm)
                                                @php $action = explode('.', $perm->name)[0]; @endphp

                                                <label class="perm-item">
                                                    <input type="checkbox"
                                                        name="permissions[]"
                                                        value="{{ $perm->name }}"
                                                        class="perm-check-{{ $model }}"
                                                        {{ in_array($perm->name, old('permissions', $currentPermissions)) ? 'checked' : '' }}>

                                                    <span class="action-badge action-{{ $action }}">
                                                        {{ $action }}
                                                    </span>

                                                    <span class="perm-text">
                                                        {{ $perm->name }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>

                                    </div>
                                @endforeach
                            </div>

                        @else
                            <div class="alert alert-info">
                                Run <code>php artisan permission:sync</code>
                            </div>
                        @endif

                        {{-- Buttons --}}
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-light">
                                Cancel
                            </a>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Update Role
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.select-all-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var group = this.dataset.group;
        var checkboxes = document.querySelectorAll('.perm-check-' + group);
        var allChecked = Array.from(checkboxes).every(function(cb) { return cb.checked; });
        checkboxes.forEach(function(cb) { cb.checked = !allChecked; });
        btn.textContent = allChecked ? 'Select All' : 'Deselect All';
    });
});
</script>
@endpush
