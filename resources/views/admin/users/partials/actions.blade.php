<button type="button"
    class="btn btn-sm btn-outline-secondary me-1 js-edit-user"
    title="Edit"
    data-user="{{ json_encode([
        'id' => $user->id,
        'name' => $user->name,
        'username' => $user->username,
        'email' => $user->email,
        'role' => $user->roles->first()?->name,
    ]) }}">
    <i class="bi bi-pencil-square"></i>
</button>

@if(!$user->hasRole('super-admin'))
    <button type="button"
        class="btn btn-sm btn-outline-danger"
        title="Delete"
        onclick="myConfirm('admin/users/delete/{{ $user->id }}')">
        <i class="bi bi-trash3"></i>
    </button>
@endif
