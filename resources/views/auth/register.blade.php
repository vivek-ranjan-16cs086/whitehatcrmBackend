@extends('layouts.app')

@section('title', 'Create Account')
@section('auth-subtitle', 'Create a new account')

@section('content')
<form method="POST" action="{{ route('register') }}" novalidate>
    @csrf

    {{-- Full Name --}}
    <div class="mb-3">
        <label for="name" class="form-label fw-semibold small">Full Name</label>
        <div class="input-icon-wrap">
            <i class="bi bi-person"></i>
            <input id="name" type="text" name="name"
                   value="{{ old('name') }}"
                   class="form-control @error('name') is-invalid @enderror"
                   placeholder="John Doe" required autofocus autocomplete="name">
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Email --}}
    <div class="mb-3">
        <label for="email" class="form-label fw-semibold small">Email Address</label>
        <div class="input-icon-wrap">
            <i class="bi bi-envelope"></i>
            <input id="email" type="email" name="email"
                   value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="you@example.com" required autocomplete="email">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Password --}}
    <div class="mb-3">
        <label for="password" class="form-label fw-semibold small">Password <span class="text-muted fw-normal">(min. 8 characters)</span></label>
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
        <label for="password_confirmation" class="form-label fw-semibold small">Confirm Password</label>
        <div class="input-icon-wrap">
            <i class="bi bi-lock-fill"></i>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   class="form-control"
                   placeholder="••••••••" required autocomplete="new-password">
        </div>
    </div>

    <div class="d-grid">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Create Account
        </button>
    </div>
</form>
@endsection

@section('auth-footer')
<p class="auth-footer mt-3">
    Already have an account? <a href="{{ route('login') }}">Sign in</a>
</p>
@endsection
