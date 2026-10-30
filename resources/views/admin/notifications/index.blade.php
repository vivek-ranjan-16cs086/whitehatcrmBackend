@extends('layouts.admin')

@section('title', 'Notifications')
@section('page-title', 'Notifications')
@section('page-subtitle', 'Manage and view all your notifications')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 fw-bold">All Notifications</h6>
                    @if($notifications->count() > 0)
                        <button onclick="clearAllNotifications()" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash me-1"></i> Clear All
                        </button>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="all-notifications-list">
                        @forelse($notifications as $notification)
                            @php
                                $leadId = data_get($notification->data, 'leadId')
                                    ?? data_get($notification->data, 'lead_id');
                            @endphp
                            <div
                                class="list-group-item list-group-item-action border-0 px-4 py-3 {{ $notification->is_read ? 'opacity-75' : 'bg-light-subtle' }}"
                                id="notification-{{ $notification->id }}"
                                @if($leadId)
                                    role="link"
                                    tabindex="0"
                                    data-lead-url="{{ route('lead-management.index', ['open_lead' => $leadId]) }}"
                                    data-notification-id="{{ $notification->id }}"
                                    style="cursor: pointer;"
                                @endif
                            >
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar-circle {{ $notification->is_read ? 'bg-secondary' : 'bg-primary' }}">
                                            <i class="bi {{ $notification->is_read ? 'bi-bell' : 'bi-bell-fill' }}"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="mb-0 fw-bold {{ $notification->is_read ? 'text-muted' : 'text-dark' }}">{{ $notification->title }}</h6>
                                            <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                        </div>
                                        <p class="mb-0 text-secondary small">{{ $notification->message }}</p>
                                    </div>
                                    @if(!$notification->is_read)
                                        <div class="ms-3">
                                            <button onclick="markAsRead({{ $notification->id }})" class="btn btn-sm btn-link text-primary p-0" title="Mark as read">
                                                <i class="bi bi-check2-all fs-5"></i>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <i class="bi bi-bell-slash display-4 text-muted mb-3 d-block"></i>
                                <p class="text-muted">No notifications found.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                @if($notifications->hasPages())
                    <div class="card-footer bg-white py-3">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function markAsRead(id) {
        let url = "{{ route('admin.notifications.read', ':id') }}";
        url = url.replace(':id', id);

        $.ajax({
            url: url,
            type: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if(response.status) {
                    $(`#notification-${id}`).addClass('opacity-75').removeClass('bg-light-subtle');
                    $(`#notification-${id} .avatar-circle`).removeClass('bg-primary').addClass('bg-secondary');
                    $(`#notification-${id} h6`).removeClass('text-dark').addClass('text-muted');
                    $(`#notification-${id} button`).fadeOut();
                    // Update unread count locally without fetching the API
                    if(typeof decrementUnreadCount === 'function') decrementUnreadCount();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Could not mark as read',
                        text: response.message || 'The notification could not be updated.'
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Could not mark as read',
                    text: xhr.responseJSON?.message || 'The notification could not be updated.'
                });
            }
        });
    }

    function openLeadFromNotification(element) {
        const url = element.dataset.leadUrl;
        const notificationId = element.dataset.notificationId;

        $.ajax({
            url: "{{ route('admin.notifications.read', ':id') }}".replace(':id', notificationId),
            type: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.status) {
                    window.location.assign(url);
                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Could not open lead',
                    text: response.message || 'The notification could not be updated.'
                });
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Could not open lead',
                    text: xhr.responseJSON?.message || 'The notification could not be updated.'
                });
            }
        });
    }

    $(document).on('click', '[data-lead-url]', function(event) {
        if (event.target.closest('button')) {
            return;
        }

        openLeadFromNotification(this);
    });

    $(document).on('keydown', '[data-lead-url]', function(event) {
        if (event.target.closest('button')) {
            return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openLeadFromNotification(this);
        }
    });

    function clearAllNotifications() {
        confirmAdminAction({
            url: `{{ route('admin.notifications.clearAll') }}`,
            method: 'DELETE',
            title: 'Clear all notifications?',
            text: 'This will permanently remove all notifications.',
            confirmButtonText: 'Yes, clear all',
            successTitle: 'Notifications cleared',
            destructive: true
        });
    }
</script>
@endpush
@endsection
