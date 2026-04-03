@extends('admin.layouts.app')

@section('page-title', 'Package Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>{{ $package->name }}</h2>
    <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h5>Package Info</h5>
            <p><strong>Code:</strong> {{ $package->code }}</p>
            <p><strong>Price:</strong> {{ $package->currency ?? 'KES' }} {{ number_format((float) $package->price, 2) }}</p>
            <p><strong>Duration:</strong> {{ $package->duration_formatted }}</p>
            <p><strong>Status:</strong> {{ $package->is_active ? 'Active' : 'Inactive' }}</p>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h5>Performance</h5>
            <p><strong>Total Sales:</strong> {{ number_format($sales) }}</p>
            <p><strong>Total Revenue:</strong> {{ $package->currency ?? 'KES' }} {{ number_format($revenue, 2) }}</p>
        </div></div>
    </div>
</div>
@endsection
