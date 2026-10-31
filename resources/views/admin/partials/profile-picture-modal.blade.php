<div class="modal fade" id="adminProfilePictureModal" tabindex="-1" aria-labelledby="adminProfilePictureTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 id="adminProfilePictureTitle" class="modal-title">Profile picture</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <form id="adminProfilePictureForm" data-url="{{ url('admin/chat/profile-picture') }}">
            <div class="modal-body">
                <div class="admin-profile-preview" id="adminProfilePreview">@if(auth()->user()->profile_pic)<img class="chat-avatar" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->profile_pic) }}" alt="Current profile picture">@else{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}@endif</div>
                <label for="adminProfilePictureFile" class="form-label">Choose a picture</label>
                <input id="adminProfilePictureFile" class="form-control" type="file" accept="image/jpeg,image/png,image/gif,image/webp" required>
                <p class="text-muted small mt-2">JPG, PNG, GIF or WebP, up to 2 MB.</p>
                <div id="adminProfilePictureError" class="alert alert-danger mb-0" role="alert" hidden></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button id="adminProfilePictureSave" type="submit" class="btn btn-primary">Save picture</button></div>
        </form>
    </div></div>
</div>
