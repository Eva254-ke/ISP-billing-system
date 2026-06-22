@extends('admin.layouts.app')

@section('page-title', 'Create Package')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Create Package</h2>
    <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card">
    <div class="card-body">
        <p class="text-muted">Create a new package for {{ $tenant?->name ?? 'the selected scope' }}.</p>
        <form class="row g-3">
            <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" placeholder="1 Hour Pass"></div>
            <div class="col-md-3"><label class="form-label">Price</label><input class="form-control" placeholder="50"></div>
            <div class="col-md-3"><label class="form-label">Currency</label><input class="form-control" value="KES"></div>
            <div class="col-md-6"><label class="form-label">Duration</label><input class="form-control" placeholder="1 hour"></div>
            <div class="col-md-6"><label class="form-label">MikroTik Profile</label><input class="form-control" placeholder="profile-1hour"></div>
        </form>
    </div>
</div>
@endsection
