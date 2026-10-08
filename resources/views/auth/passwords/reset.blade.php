@extends('layouts.app')

@section('title', 'Set New Password')
@section('auth-subtitle', 'Choose a new password for your account')

@section('content')
<form method="POST" action="{{ route('password.update') }}" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    {{-- Email (readonly) --}}
    <div class="mb-3">
        <label for="email" class="form-label fw-semibold small">Email Address</label>
        <div class="input-icon-wrap">
            <i class="bi bi-envelope"></i>
            <input id="email" type="email" name="email"
                   value="{{ $email ?? old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   required autofocus autocomplete="email">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- New Password --}}
    <div class="mb-3">
        <label for="password" class="form-label fw-semibold small">New Password <span class="text-muted fw-normal">(min. 8 characters)</span></label>
        <div class="input-icon-wrap">
            <i class="bi bi-lock"></i>
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="••••••••" required autocomplete="new-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Confirm Password --}}
    <div class="mb-4">
        <label for="password_confirmation" class="form-label fw-semibold small">Confirm New Password</label>
        <div class="input-icon-wrap">
            <i class="bi bi-lock-fill"></i>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   class="form-control"
                   placeholder="••••••••" required autocomplete="new-password">
        </div>
    </div>

    <div class="d-grid">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i> Reset Password
        </button>
    </div>
</form>
@endsection

@section('auth-footer')
<p class="auth-footer mt-3">
    <a href="{{ route('login') }}"><i class="bi bi-arrow-left me-1"></i>Back to Sign In</a>
</p>
@endsection
