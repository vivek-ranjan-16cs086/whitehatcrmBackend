@extends('layouts.admin')

@section('title', 'Team Management')
@section('page-title', 'Team Management')
@section('page-subtitle', 'Manage project teams and team leads')

@section('content')
@if($errors->any())
    <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
@endif
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <h6 class="mb-0 fw-bold">Team Management</h6>
            <form method="GET" action="{{ route('admin.teams.index') }}" class="d-flex gap-2">
                <input name="search" type="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search teams" aria-label="Search teams">
                <button class="btn btn-outline-secondary btn-sm" type="submit" aria-label="Search"><i class="bi bi-search"></i></button>
            </form>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm" id="addTeamButton" data-bs-toggle="modal" data-bs-target="#teamModal"><i class="bi bi-plus-lg me-1"></i>Add Team</button>
            <a href="{{ route('admin.teams.index') }}" class="btn btn-outline-secondary btn-sm" aria-label="Refresh teams" title="Refresh teams"><i class="bi bi-arrow-clockwise"></i></a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>Name</th><th>Project</th><th>Team Lead</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($teams as $team)
                    <tr>
                        <td>{{ $team->name }}</td><td>{{ $team->project?->name ?? 'No project' }}</td><td>{{ $team->teamLead?->name ?? 'No team lead' }}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-secondary js-edit-team" aria-label="Edit {{ $team->name }}" data-team="{{ json_encode($team->only('id', 'name', 'project_id', 'team_lead_id')) }}" data-bs-toggle="modal" data-bs-target="#teamModal"><i class="bi bi-pencil-square"></i></button>
                            <form method="POST" action="{{ route('admin.teams.destroy', $team) }}" class="d-inline" onsubmit="return confirm('Delete this team?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $team->name }}"><i class="bi bi-trash3"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No teams found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center"><span class="text-muted">{{ $teams->total() }} total</span>{{ $teams->links('pagination.admin-compact') }}</div>
    </div>
</div>
<div class="modal fade" id="teamModal" tabindex="-1" aria-labelledby="teamModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.teams.store') }}" class="modal-content" id="teamForm">
            @csrf
            <input type="hidden" name="_method" id="teamMethod" value="POST">
            <div class="modal-header"><h5 class="modal-title" id="teamModalTitle">Add Team</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label for="teamName" class="form-label">Name</label><input id="teamName" name="name" class="form-control" maxlength="255" required></div>
                <div class="mb-3"><label for="teamProject" class="form-label">Project</label><select id="teamProject" name="project_id" class="form-select" required><option value="">Select project</option>@foreach($projects as $project)<option value="{{ $project->id }}">{{ $project->name }}</option>@endforeach</select></div>
                <div class="mb-3"><label for="teamLead" class="form-label">Team Lead</label><select id="teamLead" name="team_lead_id" class="form-select" required><option value="">Select team lead</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save Team</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const teamStoreUrl = @json(route('admin.teams.store'));
    document.getElementById('addTeamButton').addEventListener('click', function () {
        document.getElementById('teamForm').reset();
        document.getElementById('teamForm').action = teamStoreUrl;
        document.getElementById('teamMethod').value = 'POST';
        document.getElementById('teamModalTitle').textContent = 'Add Team';
    });
    document.querySelectorAll('.js-edit-team').forEach(function (button) {
        button.addEventListener('click', function () {
            const team = JSON.parse(this.dataset.team);
            document.getElementById('teamForm').action = teamStoreUrl + '/' + team.id;
            document.getElementById('teamMethod').value = 'PUT';
            document.getElementById('teamModalTitle').textContent = 'Edit Team';
            document.getElementById('teamName').value = team.name;
            document.getElementById('teamProject').value = team.project_id || '';
            document.getElementById('teamLead').value = team.team_lead_id || '';
        });
    });
</script>
@endpush
