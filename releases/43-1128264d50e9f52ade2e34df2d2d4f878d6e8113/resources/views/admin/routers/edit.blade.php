@extends('admin.layouts.app')

@section('page-title', 'Edit Router')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Router</h2>
    <a href="{{ route('admin.routers.show', $router) }}" class="btn btn-outline-secondary">View</a>
</div>

<div class="card">
    <div class="card-body">
        <form class="row g-3">
            <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" value="{{ $router->name }}"></div>
            <div class="col-md-6"><label class="form-label">Location</label><input class="form-control" value="{{ $router->location }}"></div>
            <div class="col-md-6"><label class="form-label">IP</label><input class="form-control" value="{{ $router->ip_address }}"></div>
            <div class="col-md-6"><label class="form-label">Status</label><input class="form-control" value="{{ $router->status }}"></div>
        </form>
    </div>
</div>
@endsection
