@extends('admin.layouts.app')

@section('page-title', 'Edit Package')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Package</h2>
    <a href="{{ route('admin.packages.show', $package) }}" class="btn btn-outline-secondary">View</a>
</div>

<div class="card">
    <div class="card-body">
        <form class="row g-3">
            <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" value="{{ $package->name }}"></div>
            <div class="col-md-3"><label class="form-label">Price</label><input class="form-control" value="{{ $package->price }}"></div>
            <div class="col-md-3"><label class="form-label">Status</label><input class="form-control" value="{{ $package->is_active ? 'active' : 'inactive' }}"></div>
            <div class="col-md-6"><label class="form-label">Profile</label><input class="form-control" value="{{ $package->mikrotik_profile_name }}"></div>
            <div class="col-md-6"><label class="form-label">Description</label><input class="form-control" value="{{ $package->description }}"></div>
        </form>
    </div>
</div>
@endsection
