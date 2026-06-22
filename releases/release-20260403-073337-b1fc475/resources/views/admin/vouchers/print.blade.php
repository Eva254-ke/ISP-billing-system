@extends('admin.layouts.app')

@section('page-title', 'Print Vouchers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Print Vouchers</h2>
    <a href="{{ route('admin.vouchers.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Package</th>
                    <th>Status</th>
                    <th>Generated</th>
                </tr>
            </thead>
            <tbody>
                @forelse($latestVouchers as $voucher)
                    <tr>
                        <td><code>{{ $voucher->code }}</code></td>
                        <td>{{ $voucher->package?->name ?? '-' }}</td>
                        <td>{{ ucfirst($voucher->status) }}</td>
                        <td>{{ $voucher->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No vouchers available to print</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
