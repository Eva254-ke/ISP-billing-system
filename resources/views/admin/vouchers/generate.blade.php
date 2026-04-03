@extends('admin.layouts.app')

@section('page-title', 'Generate Vouchers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Generate Vouchers</h2>
    <a href="{{ route('admin.vouchers.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card">
    <div class="card-body">
        <p class="text-muted">Generate new voucher batch for {{ $tenant?->name ?? 'the selected scope' }}.</p>
        <form class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Package</label>
                <select class="form-select">
                    @forelse($packages as $package)
                        <option value="{{ $package->id }}">{{ $package->name }} ({{ $package->currency ?? 'KES' }} {{ number_format((float) $package->price, 2) }})</option>
                    @empty
                        <option>No active packages</option>
                    @endforelse
                </select>
            </div>
            <div class="col-md-3"><label class="form-label">Quantity</label><input class="form-control" value="100"></div>
            <div class="col-md-3"><label class="form-label">Prefix</label><input class="form-control" value="CB"></div>
        </form>
    </div>
</div>
@endsection
