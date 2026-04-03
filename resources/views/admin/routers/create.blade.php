@extends('admin.layouts.app')

@section('page-title', 'Add Router')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Add Router</h2>
    <a href="{{ route('admin.routers.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card">
    <div class="card-body">
        <p class="text-muted mb-3">Create a new router for {{ $tenant?->name ?? 'the selected scope' }}.</p>
        <form class="row g-3">
            <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" placeholder="Main Hotspot"></div>
            <div class="col-md-6"><label class="form-label">Model</label><input class="form-control" placeholder="RB750Gr3"></div>
            <div class="col-md-6"><label class="form-label">IP Address</label><input class="form-control" placeholder="192.168.88.1"></div>
            <div class="col-md-6"><label class="form-label">API Port</label><input class="form-control" value="8728"></div>
        </form>
    </div>
</div>
@endsection
