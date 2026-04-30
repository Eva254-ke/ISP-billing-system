@extends('admin.layouts.app')

@section('page-title', 'Create Package')

@section('content')
<div class="cb-page-header">
    <div class="cb-page-heading">
        <h2>Create Package</h2>
        <p>Set the price, duration, and connection profile for a package on {{ $tenant?->name ?? 'the selected scope' }}.</p>
    </div>
    <div class="cb-page-actions">
        <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary">Back to Packages</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="cb-section-card">
            <form id="createPackageForm">
                <div>
                    <h3 class="cb-section-title">Package details</h3>
                    <p class="cb-section-copy">Customers will see this name, price, and duration in the captive portal.</p>
                </div>

                <div class="row g-3 mt-1">
                    @if(!empty($isSuperAdmin))
                    <div class="col-md-6">
                        <label class="form-label">Tenant</label>
                        <select class="form-select" name="tenant_id" required>
                            <option value="">Select tenant</option>
                            @foreach($tenants as $tenantOption)
                                <option value="{{ $tenantOption->id }}">{{ $tenantOption->name }} (ID {{ $tenantOption->id }})</option>
                            @endforeach
                        </select>
                        <small class="cb-field-note">Choose which tenant owns this package before loading routers and profiles.</small>
                    </div>
                    @endif
                    <div class="col-md-{{ !empty($isSuperAdmin) ? '6' : '12' }}">
                        <label class="form-label">Name</label>
                        <input class="form-control" name="name" placeholder="1 Hour Pass" required>
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
                        <label class="form-label">Status</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                            <label class="form-check-label" for="isActive">Enable package</label>
                        </div>
                    </div>
                </div>

                <div class="cb-divider"></div>

                <div>
                    <h3 class="cb-section-title">Speed and router profile</h3>
                    <p class="cb-section-copy">Use direct speed limits, a router profile, or both if the deployment needs it.</p>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-3">
                        <label class="form-label">Download Mbps</label>
                        <input type="number" min="1" class="form-control" name="download_limit_mbps" placeholder="Optional">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Upload Mbps</label>
                        <input type="number" min="1" class="form-control" name="upload_limit_mbps" placeholder="Optional">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Router for Profile Lookup</label>
                        <select class="form-select" name="router_id">
                            <option value="">No router profile</option>
                            <option value="" disabled>Loading routers...</option>
                        </select>
                        <small class="cb-field-note">Leave this blank if the package should work using direct per-user limits only.</small>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">MikroTik Profile</label>
                        <select class="form-select" name="mikrotik_profile_name">
                            <option value="">No router profile</option>
                            <option value="" disabled>Loading MikroTik profiles...</option>
                        </select>
                        <small class="cb-field-note">Only use this if the same profile exists on every router that may activate the package.</small>
                    </div>
                </div>

                <div class="cb-stacked-actions">
                    <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Package</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="cb-section-card cb-section-card--compact">
            <h3 class="cb-section-title">What to check</h3>
            <p class="cb-section-copy">Use the same duration the admin expects to sell. The expiry time is calculated from this value after activation.</p>
            <p class="cb-section-copy">If you pick a router profile, keep the profile names aligned across routers so paid users connect the same way everywhere.</p>
            <p class="cb-section-copy">If you only need rate limits, you can leave the router profile fields empty and save the package directly.</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
const createPackageForm = document.getElementById('createPackageForm');
const routerField = createPackageForm?.querySelector('[name="router_id"]');
const profileField = createPackageForm?.querySelector('[name="mikrotik_profile_name"]');
const tenantField = createPackageForm?.querySelector('[name="tenant_id"]');

function withTenantScope(url) {
    const tenantId = tenantField ? Number(tenantField.value || 0) : 0;
    if (tenantId <= 0) {
        return url;
    }

    const glue = url.includes('?') ? '&' : '?';
    return `${url}${glue}tenant_id=${tenantId}`;
}

function setProfileMessage(message) {
    if (!profileField) {
        return;
    }

    profileField.innerHTML = `
        <option value="">No router profile</option>
        <option value="" disabled>${String(message || 'No profiles found')}</option>
    `;
    profileField.value = '';
}

async function loadProfilesForRouter(routerId) {
    if (!profileField) {
        return;
    }

    const normalizedRouterId = Number(routerId || 0);
    if (normalizedRouterId <= 0) {
        setProfileMessage('Choose a router only if you want to use one of its profiles');
        return;
    }

    profileField.innerHTML = `
        <option value="">No router profile</option>
        <option value="" disabled>Loading MikroTik profiles...</option>
    `;

    try {
        const response = await fetch(withTenantScope(`/admin/api/mikrotik/profiles?router_id=${normalizedRouterId}`), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });

        const payload = await response.json().catch(() => ({}));
        if (!response.ok || !payload?.success) {
            throw new Error(payload?.message || 'Unable to load router profiles');
        }

        const profiles = Array.isArray(payload?.data?.profiles) ? payload.data.profiles : [];
        if (!profiles.length) {
            setProfileMessage(payload?.message || 'No profiles found on selected router');
            return;
        }

        const options = ['<option value="">No router profile</option>'];
        for (const profileName of profiles) {
            const safe = String(profileName || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
            options.push(`<option value="${safe}">${safe}</option>`);
        }

        profileField.innerHTML = options.join('');
    } catch (error) {
        setProfileMessage(error?.message || 'Unable to load router profiles');
    }
}

async function loadRouters() {
    if (!routerField) {
        return;
    }

    if (tenantField && Number(tenantField.value || 0) <= 0) {
        routerField.innerHTML = `
            <option value="">No router profile</option>
            <option value="" disabled>Select tenant first</option>
        `;
        setProfileMessage('Select tenant first');
        return;
    }

    routerField.innerHTML = `
        <option value="">No router profile</option>
        <option value="" disabled>Loading routers...</option>
    `;

    try {
        const response = await fetch(withTenantScope('/admin/api/mikrotik/profiles'), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });

        const payload = await response.json().catch(() => ({}));
        if (!response.ok || !payload?.success) {
            throw new Error(payload?.message || 'Unable to load routers');
        }

        const routers = Array.isArray(payload?.data?.routers) ? payload.data.routers : [];
        const options = ['<option value="">No router profile</option>'];

        for (const router of routers) {
            const id = Number(router?.id || 0);
            if (id <= 0) {
                continue;
            }

            const name = String(router?.name || `Router ${id}`)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
            const ip = String(router?.ip_address || 'unknown')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
            options.push(`<option value="${id}">${name} (${ip})</option>`);
        }

        routerField.innerHTML = options.join('');

        if (routers.length > 0) {
            setProfileMessage('Choose a router only if you want to use one of its profiles');
        } else {
            setProfileMessage('No routers found. Add a router first.');
        }
    } catch (error) {
        setProfileMessage(error?.message || 'Unable to load routers');
    }
}

routerField?.addEventListener('change', function () {
    loadProfilesForRouter(this.value);
});

tenantField?.addEventListener('change', function () {
    loadRouters();
});

loadRouters();

createPackageForm?.addEventListener('submit', async function (event) {
    event.preventDefault();

    const form = event.currentTarget;
    const selectedRouterId = Number(form.querySelector('[name="router_id"]').value || 0);
    const selectedProfile = (form.querySelector('[name="mikrotik_profile_name"]').value || '').trim();

    if (selectedProfile !== '' && selectedRouterId <= 0) {
        if (window.Swal) {
            Swal.fire('Router Needed', 'Select a router only if you want to use one of its MikroTik profiles.', 'warning');
        } else {
            alert('Select a router only if you want to use one of its MikroTik profiles.');
        }
        return;
    }

    const payload = {
        name: form.querySelector('[name="name"]').value.trim(),
        price: Number(form.querySelector('[name="price"]').value || 0),
        duration_value: Number(form.querySelector('[name="duration_value"]').value || 0),
        duration_unit: form.querySelector('[name="duration_unit"]').value,
        download_limit_mbps: form.querySelector('[name="download_limit_mbps"]').value ? Number(form.querySelector('[name="download_limit_mbps"]').value) : null,
        upload_limit_mbps: form.querySelector('[name="upload_limit_mbps"]').value ? Number(form.querySelector('[name="upload_limit_mbps"]').value) : null,
        router_id: selectedRouterId > 0 ? selectedRouterId : null,
        mikrotik_profile_name: selectedProfile || null,
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
