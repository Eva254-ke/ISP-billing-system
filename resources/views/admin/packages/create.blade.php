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
        <form id="createPackageForm" class="row g-3">
            @if(!empty($isSuperAdmin))
            <div class="col-md-6">
                <label class="form-label">Tenant</label>
                <select class="form-select" name="tenant_id" required>
                    <option value="">Select tenant</option>
                    @foreach($tenants as $tenantOption)
                        <option value="{{ $tenantOption->id }}">{{ $tenantOption->name }} (ID {{ $tenantOption->id }})</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input class="form-control" name="name" placeholder="1 Hour Pass" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Description</label>
                <input class="form-control" name="description" placeholder="Optional short description">
            </div>
            <div class="col-md-3">
                <label class="form-label">Price (KES)</label>
                <input type="number" min="0" step="0.01" class="form-control" name="price" placeholder="50" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Duration Value</label>
                <input type="number" min="1" class="form-control" name="duration_value" placeholder="1" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Duration Unit</label>
                <select class="form-select" name="duration_unit" required>
                    <option value="minutes">minutes</option>
                    <option value="hours" selected>hours</option>
                    <option value="days">days</option>
                    <option value="weeks">weeks</option>
                    <option value="months">months</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Active</label>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                    <label class="form-check-label" for="isActive">Enable package</label>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Download Mbps</label>
                <input type="number" min="1" class="form-control" name="download_limit_mbps" placeholder="Optional">
            </div>
            <div class="col-md-3">
                <label class="form-label">Upload Mbps</label>
                <input type="number" min="1" class="form-control" name="upload_limit_mbps" placeholder="Optional">
            </div>
            <div class="col-md-6">
                <label class="form-label">MikroTik Profile (optional)</label>
                <input class="form-control" name="mikrotik_profile_name" placeholder="profile-1hour">
            </div>
            <div class="col-12 d-flex gap-2 justify-content-end">
                <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Package</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('createPackageForm')?.addEventListener('submit', async function (event) {
    event.preventDefault();

    const form = event.currentTarget;
    const payload = {
        name: form.querySelector('[name="name"]').value.trim(),
        description: form.querySelector('[name="description"]').value.trim(),
        price: Number(form.querySelector('[name="price"]').value || 0),
        duration_value: Number(form.querySelector('[name="duration_value"]').value || 0),
        duration_unit: form.querySelector('[name="duration_unit"]').value,
        download_limit_mbps: form.querySelector('[name="download_limit_mbps"]').value ? Number(form.querySelector('[name="download_limit_mbps"]').value) : null,
        upload_limit_mbps: form.querySelector('[name="upload_limit_mbps"]').value ? Number(form.querySelector('[name="upload_limit_mbps"]').value) : null,
        mikrotik_profile_name: (form.querySelector('[name="mikrotik_profile_name"]').value || '').trim() || null,
        is_active: !!form.querySelector('[name="is_active"]').checked,
    };

    const tenantField = form.querySelector('[name="tenant_id"]');
    if (tenantField) {
        payload.tenant_id = Number(tenantField.value || 0);
    }

    try {
        const response = await fetch('/admin/api/packages', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(payload),
        });

        const json = await response.json().catch(() => ({}));
        if (!response.ok || !json?.success) {
            throw new Error(json?.message || 'Failed to create package');
        }

        if (window.Swal) {
            await Swal.fire({
                icon: 'success',
                title: 'Package Created',
                text: json.message || 'Package created successfully',
                timer: 1400,
                showConfirmButton: false
            });
        }

        window.location.href = '{{ route('admin.packages.index') }}';
    } catch (error) {
        if (window.Swal) {
            Swal.fire('Error', error.message || 'Failed to create package', 'error');
            return;
        }
        alert(error.message || 'Failed to create package');
    }
});
</script>
@endpush
@endsection
