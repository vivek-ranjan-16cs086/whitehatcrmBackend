@extends('layouts.admin')

@section('title', 'Settings')
@section('page-title', 'Settings Management')
@section('page-subtitle', 'Manage all your Settings')

@section('content')

<form action="{{ route('maintenance.update') }}" method="POST">

    @csrf

    <div class="mb-3">

        <label>Website Status</label>

        <select name="maintenance_mode" class="form-control">

            <option value="0">Live</option>

            <option value="1">Maintenance</option>

        </select>

    </div>

    <div class="mb-3">

        <label>Maintenance Message</label>

        <textarea
            class="form-control"
            name="maintenance_message"
        >Website is under maintenance. Please try again later.</textarea>

    </div>

    <button class="btn btn-primary">
        Save
    </button>

</form>
@endsection