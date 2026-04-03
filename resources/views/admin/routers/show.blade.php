@extends('admin.layouts.app')

@section('page-title', 'Router Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>{{ $router->name }}</h2>
    <a href="{{ route('admin.routers.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h5>Router Info</h5>
            <p class="mb-1"><strong>IP:</strong> {{ $router->ip_address }}</p>
            <p class="mb-1"><strong>Model:</strong> {{ $router->model }}</p>
            <p class="mb-1"><strong>Status:</strong> {{ ucfirst($router->status) }}</p>
            <p class="mb-0"><strong>Active Sessions:</strong> {{ (int) ($router->active_sessions ?? 0) }}</p>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h5>Recent Sessions</h5>
            <ul class="list-group list-group-flush">
                @forelse($activeSessions as $session)
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>{{ $session->username }}</span>
                        <small class="text-muted">{{ $session->expires_at?->format('H:i') }}</small>
                    </li>
                @empty
                    <li class="list-group-item px-0 text-muted">No active sessions</li>
                @endforelse
            </ul>
        </div></div>
    </div>
</div>
@endsection
