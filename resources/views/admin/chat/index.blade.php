@extends('layouts.admin')
@section('body-class', 'chat-page')
@section('title', 'Team Chat')
@section('page-title', 'Team Chat')
@section('page-subtitle', 'Stay connected with your team')
@section('content')
<link rel="stylesheet" href="{{ url('admin/css/chat.css') }}?v={{ filemtime(public_path('admin/css/chat.css')) }}">
<div id="teamChat" class="card team-chat" data-base="{{ url('admin/chat') }}">
    <aside class="chat-sidebar">
        <div class="chat-sidebar-heading"><h6 class="mb-0">Conversations</h6><button type="button" id="chatNew" class="btn btn-primary btn-sm">New chat</button></div>
        <input id="chatSearch" type="search" class="form-control" placeholder="Search conversations..." aria-label="Search conversations">
        <div id="chatConversations" class="chat-conversations" aria-label="Conversations"></div>
        <button type="button" id="chatMoreConversations" class="btn btn-outline-secondary btn-sm" hidden>More conversations</button>
        <span id="chatConnection" class="chat-connection" role="status">Connecting...</span>
    </aside>
    <section class="chat-thread">
        <div class="chat-thread-heading"><div><h6 id="chatTitle" class="mb-1">Your team, one conversation away</h6><span id="chatMembers" class="text-muted small">Choose a conversation or start a new chat.</span></div></div>
        <div id="chatError" class="alert alert-danger m-3" role="alert" hidden></div>
        <div id="chatMessages" class="chat-messages" role="log" aria-label="Messages"></div>
        <div id="chatTyping" class="chat-typing" aria-live="polite"></div>
        <form id="chatComposer" class="chat-composer">
            <textarea id="chatBody" class="form-control" rows="2" maxlength="5000" placeholder="Write a message..." aria-label="Message" disabled></textarea>
            <button id="chatSend" class="btn btn-primary" type="submit" disabled>Send</button>
        </form>
        <p class="chat-hint">Enter to send. Shift + Enter for a new line.</p>
    </section>
</div>
<div class="modal fade" id="chatNewModal" tabindex="-1" aria-labelledby="chatNewTitle" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="chatNewTitle">Start a conversation</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <form id="chatCreateForm"><div class="modal-body">
            <div id="chatCreateError" class="alert alert-danger" role="alert" hidden></div>
            <label class="form-label" for="chatKind">Conversation type</label>
            <select id="chatKind" class="form-select mb-3"><option value="direct">One-to-one</option><option value="group" hidden>Group</option></select>
            <div id="chatGroupNameWrap" hidden><label class="form-label" for="chatGroupName">Group name</label><input id="chatGroupName" class="form-control mb-3" maxlength="100"></div>
            <label class="form-label" for="chatPeopleSearch">Find team members</label><input id="chatPeopleSearch" type="search" class="form-control mb-2" placeholder="Search by name">
            <div id="chatPeople" class="chat-people"></div>
            <button type="button" id="chatMorePeople" class="btn btn-outline-secondary btn-sm mt-2" hidden>Load more people</button>
        </div><div class="modal-footer"><button class="btn btn-primary" id="chatCreate" type="submit">Start chat</button></div></form>
    </div></div>
</div>
@endsection
@push('scripts')
<script src="{{ url('admin/libraries/js/pusher.min.js') }}"></script>
<script src="{{ url('admin/libraries/js/echo.iife.js') }}"></script>
<script src="{{ url('admin/js/chat.js') }}?v={{ filemtime(public_path('admin/js/chat.js')) }}"></script>
@endpush
