@extends('layouts.app')

@section('title', 'Reset Password')
@section('auth-subtitle', 'We\'ll send a reset link to your email')

@section('content')

@if (session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('password.email') }}" novalidate>
    @csrf

    <div class="mb-4">
        <label for="email" class="form-label fw-semibold small">Email Address</label>
        <div class="input-icon-wrap">
            <i class="bi bi-envelope"></i>
            <input id="email" type="email" name="email"
                   value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="you@example.com" required autofocus autocomplete="email">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="d-grid">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-send me-1"></i> Send Reset Link
        </button>
    </div>
</form>
@endsection

@section('auth-footer')
<p class="auth-footer mt-3">
    <a href="{{ route('login') }}"><i class="bi bi-arrow-left me-1"></i>Back to Sign In</a>
</p>
@endsection
