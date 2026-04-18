@extends('admin.layouts.app')

@section('page-title', 'Packages')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>WiFi Packages</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPackageModal" data-toggle="modal" data-target="#addPackageModal">
        <i class="fas fa-plus me-2"></i>Create Package
    </button>
</div>

<!-- Stats Row -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ number_format((int) ($stats['total'] ?? 0)) }}</h3>
                <p>Total Packages</p>
            </div>
            <div class="icon"><i class="fas fa-box"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format((int) ($stats['active'] ?? 0)) }}</h3>
                <p>Active</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format((int) ($stats['inactive'] ?? 0)) }}</h3>
                <p>Inactive</p>
            </div>
            <div class="icon"><i class="fas fa-pause-circle"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>KES {{ number_format((float) ($stats['revenue_week'] ?? 0), 0) }}</h3>
                <p>This Week Revenue</p>
            </div>
            <div class="icon"><i class="fas fa-chart-line"></i></div>
        </div>
    </div>
</div>

<!-- Packages Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Available Packages</h3>
        <div class="card-tools">
            <div class="input-group input-group-sm" style="width: 250px;">
                <input type="text" class="form-control" placeholder="Search packages...">
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped data-table">
            <thead>
                <tr>
                    <th>Package Name</th>
                    <th>Duration</th>
                    <th>Price (KES)</th>
                    <th>Bandwidth</th>
                    <th>MikroTik Profile</th>
                    <th>Status</th>
                    <th>Sales</th>
                    <th class="action-col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($packages ?? collect()) as $package)
                    @php
                        $duration = trim(($package->duration_value ?? '-') . ' ' . ($package->duration_unit ?? ''));
                        $bandwidth = ($package->download_limit_mbps ?? '∞') . ' Mbps ↓ / ' . ($package->upload_limit_mbps ?? '∞') . ' Mbps ↑';
                    @endphp
                    <tr class="{{ $package->is_active ? '' : 'text-muted' }}">
                        <td>
                            <strong>{{ $package->name }}</strong>
                            <div class="text-muted small">{{ $package->description ?? 'No description' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $duration }}</span>
                        </td>
                        <td><strong>KES {{ number_format((float) ($package->price ?? 0), 0) }}</strong></td>
                        <td>
                            <span class="text-muted">{{ $bandwidth }}</span>
                        </td>
                        <td><code>{{ $package->mikrotik_profile_name ?? '-' }}</code></td>
                        <td>
                            <label class="switch">
                                <input type="checkbox" data-id="{{ $package->id }}" {{ $package->is_active ? 'checked' : '' }}>
                                <span class="slider round"></span>
                            </label>
                        </td>
                        <td>{{ number_format((int) ($package->total_sales ?? 0)) }}</td>
                        <td class="action-col">
                            <div class="btn-group">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary js-package-edit"
                                    title="Edit"
                                    aria-label="Edit package"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editPackageModal"
                                    data-toggle="modal"
                                    data-target="#editPackageModal"
                                    onclick="window.openEditPackageFromButton(this)"
                                    data-id="{{ $package->id }}"
                                    data-name="{{ $package->name }}"
                                    data-description="{{ $package->description ?? '' }}"
                                    data-duration-value="{{ $package->duration_value }}"
                                    data-duration-unit="{{ $package->duration_unit }}"
                                    data-price="{{ $package->price }}"
                                    data-download="{{ $package->download_limit_mbps ?? '' }}"
                                    data-upload="{{ $package->upload_limit_mbps ?? '' }}"
                                    data-profile="{{ $package->mikrotik_profile_name ?? '' }}"
                                    data-active="{{ $package->is_active ? 1 : 0 }}"
                                >
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete({{ (int) $package->id }}, '{{ addslashes($package->name) }}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center text-muted py-4">No packages available.</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer clearfix">
        <div class="float-end">
            Showing {{ number_format((int) (($packages ?? collect())->count())) }} packages
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Add Package Modal -->
<div class="modal fade" id="addPackageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if(!empty($isSuperAdmin))
                <div class="mb-3">
                    <label class="form-label">Tenant</label>
                    <select class="form-select" id="packageTenantId">
                        <option value="">Select tenant</option>
                        @foreach(($tenants ?? collect()) as $tenantOption)
                            <option value="{{ $tenantOption->id }}" {{ ((int) ($selectedTenantId ?? 0) === (int) $tenantOption->id) ? 'selected' : '' }}>
                                {{ $tenantOption->name }} (ID {{ $tenantOption->id }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Required when using super admin account.</small>
                </div>
                @endif

                <form id="addPackageForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Package Name *</label>
                            <input type="text" class="form-control" name="name" placeholder="e.g., 1 Hour Pass" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" name="description" placeholder="e.g., Quick browsing session">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Duration *</label>
                            <input type="number" class="form-control" name="duration" placeholder="60" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Duration Unit *</label>
                            <select class="form-select" name="duration_unit" required>
                                <option value="minutes">Minutes</option>
                                <option value="hours">Hours</option>
                                <option value="days">Days</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price (KES) *</label>
                            <input type="number" class="form-control" name="price" placeholder="50" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Download Speed (Mbps)</label>
                            <input type="number" class="form-control" name="download_speed" placeholder="5" min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Upload Speed (Mbps)</label>
                            <input type="number" class="form-control" name="upload_speed" placeholder="2" min="1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">MikroTik User Profile</label>
                        <select class="form-select" name="mikrotik_profile">
                            <option value="">-- Auto-generate --</option>
                            <option value="" disabled>Loading MikroTik profiles...</option>
                            <option value="__custom__">-- Custom --</option>
                        </select>
                        <small class="text-muted">Leave empty to auto-generate from package name</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Custom Profile Name</label>
                        <input type="text" class="form-control" name="custom_profile" placeholder="e.g., custom-profile-name" disabled>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                        <label class="form-check-label" for="isActive">
                            Active (visible to customers)
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="savePackage()">
                    <i class="fas fa-save me-1"></i>Create Package
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Package Modal -->
<div class="modal fade" id="editPackageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editPackageForm">
                    <input type="hidden" name="id" value="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Package Name *</label>
                            <input type="text" class="form-control" name="name" value="" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" name="description" value="">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Duration *</label>
                            <input type="number" class="form-control" name="duration" value="" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Duration Unit *</label>
                            <select class="form-select" name="duration_unit" required>
                                <option value="minutes">Minutes</option>
                                <option value="hours">Hours</option>
                                <option value="days">Days</option>
                                <option value="weeks">Weeks</option>
                                <option value="months">Months</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price (KES) *</label>
                            <input type="number" class="form-control" name="price" value="" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Download Speed (Mbps)</label>
                            <input type="number" class="form-control" name="download_speed" value="" min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Upload Speed (Mbps)</label>
                            <input type="number" class="form-control" name="upload_speed" value="" min="1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">MikroTik User Profile</label>
                        <select class="form-select" name="mikrotik_profile">
                            <option value="">-- Auto-generate --</option>
                            <option value="" disabled>Loading MikroTik profiles...</option>
                            <option value="__custom__">-- Custom --</option>
                        </select>
                        <small class="text-muted">Use a router profile when available, or choose custom.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Custom Profile Name</label>
                        <input type="text" class="form-control" name="custom_profile" placeholder="e.g., custom-profile-name" disabled>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive">
                        <label class="form-check-label" for="editIsActive">
                            Active (visible to customers)
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updatePackage()">
                    <i class="fas fa-save me-1"></i>Update Package
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Toggle Switch Styling */
.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}
.switch input { opacity: 0; width: 0; height: 0; }
.slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}
.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}
input:checked + .slider { background-color: #2563EB; }
input:checked + .slider:before { transform: translateX(26px); }
</style>

<script>
function getScopedTenantId() {
    const tenantSelect = document.getElementById('packageTenantId');
    const fromSelect = tenantSelect ? Number(tenantSelect.value || 0) : 0;
    if (fromSelect > 0) {
        return fromSelect;
    }

    const params = new URLSearchParams(window.location.search);
    const fromQuery = Number(params.get('tenant_id') || 0);
    return fromQuery > 0 ? fromQuery : 0;
}

function withTenantScope(url) {
    const tenantId = getScopedTenantId();
    if (tenantId <= 0) {
        return url;
    }

    const glue = url.includes('?') ? '&' : '?';
    return `${url}${glue}tenant_id=${tenantId}`;
}

const PROFILE_CUSTOM_VALUE = '__custom__';

function getProfileSelects() {
    return Array.from(document.querySelectorAll('select[name="mikrotik_profile"]'));
}

function getCurrentProfileValue(form) {
    const profileSelect = form.querySelector('select[name="mikrotik_profile"]');
    const customProfile = form.querySelector('input[name="custom_profile"]')?.value?.trim() || '';
    const rawProfile = profileSelect ? profileSelect.value : '';

    return rawProfile === PROFILE_CUSTOM_VALUE ? customProfile : rawProfile;
}

function toggleCustomProfileInput(select, focusWhenCustom = false) {
    if (!select) return;

    const form = select.closest('form');
    const customInput = form ? form.querySelector('input[name="custom_profile"]') : null;
    if (!customInput) return;

    const isCustom = select.value === PROFILE_CUSTOM_VALUE;
    customInput.disabled = !isCustom;
    customInput.required = isCustom;

    if (!isCustom) {
        customInput.value = '';
    } else if (focusWhenCustom) {
        customInput.focus();
    }
}

function attachProfileSelectHandlers() {
    getProfileSelects().forEach((select) => {
        if (select.dataset.profileHandlerBound === '1') {
            return;
        }

        select.addEventListener('change', function() {
            toggleCustomProfileInput(this, true);
        });

        select.dataset.profileHandlerBound = '1';
        toggleCustomProfileInput(select, false);
    });
}

function setProfileValue(form, profileName) {
    const select = form.querySelector('select[name="mikrotik_profile"]');
    const customInput = form.querySelector('input[name="custom_profile"]');
    const normalized = String(profileName || '').trim();

    if (!select) {
        return;
    }

    if (normalized === '') {
        select.value = '';
        if (customInput) {
            customInput.value = '';
        }
        toggleCustomProfileInput(select, false);
        return;
    }

    const hasOption = Array.from(select.options).some((option) => option.value === normalized);
    if (hasOption) {
        select.value = normalized;
        if (customInput) {
            customInput.value = '';
        }
        toggleCustomProfileInput(select, false);
        return;
    }

    if (customInput) {
        select.value = PROFILE_CUSTOM_VALUE;
        customInput.value = normalized;
    } else {
        select.value = '';
    }

    toggleCustomProfileInput(select, false);
}

function renderProfileSelectOptions(profileNames = []) {
    const options = ['<option value="">-- Auto-generate --</option>'];

    for (const name of profileNames) {
        const safeName = String(name)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
        options.push(`<option value="${safeName}">${safeName}</option>`);
    }

    options.push('<option value="__custom__">-- Custom --</option>');

    const forms = [document.getElementById('addPackageForm'), document.getElementById('editPackageForm')].filter(Boolean);
    forms.forEach((form) => {
        const selectedValue = getCurrentProfileValue(form);
        const select = form.querySelector('select[name="mikrotik_profile"]');
        if (!select) return;

        select.innerHTML = options.join('');
        setProfileValue(form, selectedValue);
    });

    attachProfileSelectHandlers();
}

function renderProfileLoadingState() {
    const options = [
        '<option value="">-- Auto-generate --</option>',
        '<option value="" disabled>Loading MikroTik profiles...</option>',
        '<option value="__custom__">-- Custom --</option>',
    ].join('');

    getProfileSelects().forEach((select) => {
        const selected = select.value;
        select.innerHTML = options;
        if (selected === PROFILE_CUSTOM_VALUE) {
            select.value = PROFILE_CUSTOM_VALUE;
        }
    });
}

async function loadMikroTikProfiles() {
    const tenantSelect = document.getElementById('packageTenantId');
    if (tenantSelect && getScopedTenantId() <= 0) {
        renderProfileSelectOptions([]);
        return;
    }

    renderProfileLoadingState();

    try {
        const res = await fetch(withTenantScope('/admin/api/mikrotik/profiles'), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });

        const payload = await res.json().catch(() => ({}));
        if (!res.ok || !payload?.success) {
            throw new Error(payload?.message || 'Failed to load MikroTik profiles');
        }

        const profiles = Array.isArray(payload?.data?.profiles) ? payload.data.profiles : [];
        renderProfileSelectOptions(profiles);
    } catch (error) {
        console.warn('Unable to load MikroTik profiles:', error);
        renderProfileSelectOptions([]);
    }
}

function buildPackagePayload(form) {
    const customProfile = form.querySelector('input[name="custom_profile"]')?.value?.trim() || '';
    const rawProfile = form.querySelector('select[name="mikrotik_profile"]')?.value || '';
    const selectedProfile = rawProfile === PROFILE_CUSTOM_VALUE ? customProfile : rawProfile;
    const tenantId = getScopedTenantId();

    const payload = {
        name: form.querySelector('input[name="name"]').value.trim(),
        description: form.querySelector('input[name="description"]').value.trim(),
        duration_value: Number(form.querySelector('input[name="duration"]').value || 0),
        duration_unit: form.querySelector('select[name="duration_unit"]').value,
        price: Number(form.querySelector('input[name="price"]').value || 0),
        download_limit_mbps: form.querySelector('input[name="download_speed"]').value ? Number(form.querySelector('input[name="download_speed"]').value) : null,
        upload_limit_mbps: form.querySelector('input[name="upload_speed"]').value ? Number(form.querySelector('input[name="upload_speed"]').value) : null,
        mikrotik_profile_name: selectedProfile || null,
        is_active: !!form.querySelector('input[name="is_active"]')?.checked,
    };

    if (tenantId > 0) {
        payload.tenant_id = tenantId;
    }

    return payload;
}

function packageRequest(url, method, payload) {
    return fetch(withTenantScope(url), {
        method,
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify(payload),
    }).then(async (res) => {
        const json = await res.json().catch(() => ({}));
        if (!res.ok || !json?.success) {
            throw new Error(json?.message || 'Request failed');
        }
        return json;
    });
}

window.savePackage = function savePackage() {
    const form = document.getElementById('addPackageForm');
    const tenantSelect = document.getElementById('packageTenantId');
    if (tenantSelect && getScopedTenantId() <= 0) {
        Swal.fire('Tenant Required', 'Select a tenant before creating a package.', 'warning');
        return;
    }

    const payload = buildPackagePayload(form);

    Swal.fire({
        title: 'Creating Package...',
        text: 'Saving package and syncing with MikroTik',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    packageRequest('/admin/api/packages', 'POST', payload)
        .then((json) => {
            Swal.fire({
                icon: 'success',
                title: 'Package Created!',
                text: json.message || 'Package created successfully',
                timer: 1600,
                showConfirmButton: false
            }).then(() => location.reload());
        })
        .catch((error) => {
            Swal.fire('Error', error.message || 'Failed to create package', 'error');
        });
};

window.updatePackage = function updatePackage() {
    const form = document.getElementById('editPackageForm');
    const packageId = Number(form.querySelector('input[name="id"]').value || 0);
    if (!packageId) {
        Swal.fire('Error', 'Select a package to edit first', 'error');
        return;
    }

    const payload = buildPackagePayload(form);

    Swal.fire({
        title: 'Updating Package...',
        text: 'Saving changes and syncing with MikroTik',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    packageRequest(`/admin/api/packages/${packageId}`, 'PUT', payload)
        .then((json) => {
            Swal.fire({
                icon: 'success',
                title: 'Package Updated!',
                text: json.message || 'Changes have been saved successfully.',
                timer: 1600,
                showConfirmButton: false
            }).then(() => location.reload());
        })
        .catch((error) => {
            Swal.fire('Error', error.message || 'Failed to update package', 'error');
        });
};

// Confirm delete
window.confirmDelete = function confirmDelete(packageId, packageName) {
    Swal.fire({
        title: 'Delete Package?',
        text: `Are you sure you want to delete "${packageName}"? This cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete!',
        confirmButtonColor: '#EF4444'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleting...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch(withTenantScope(`/admin/api/packages/${packageId}`), {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            })
            .then(async (res) => {
                const json = await res.json().catch(() => ({}));
                if (!res.ok || !json?.success) {
                    throw new Error(json?.message || 'Failed to delete package');
                }
            })
            .then(() => {
                Swal.fire('Deleted!', `"${packageName}" has been deleted.`, 'success')
                    .then(() => location.reload());
            })
            .catch((error) => {
                Swal.fire('Error', error.message || 'Failed to delete package', 'error');
            });
        }
    });
};

window.openEditPackageFromButton = function openEditPackageFromButton(button) {
    const form = document.getElementById('editPackageForm');
    if (!form) return;

    form.querySelector('input[name="id"]').value = button.dataset.id || '';
    form.querySelector('input[name="name"]').value = button.dataset.name || '';
    form.querySelector('input[name="description"]').value = button.dataset.description || '';
    form.querySelector('input[name="duration"]').value = button.dataset.durationValue || '';
    form.querySelector('select[name="duration_unit"]').value = button.dataset.durationUnit || 'minutes';
    form.querySelector('input[name="price"]').value = button.dataset.price || '';
    form.querySelector('input[name="download_speed"]').value = button.dataset.download || '';
    form.querySelector('input[name="upload_speed"]').value = button.dataset.upload || '';
    setProfileValue(form, button.dataset.profile || '');
    form.querySelector('input[name="is_active"]').checked = String(button.dataset.active || '0') === '1';
};

function bindStatusToggles() {
    document.querySelectorAll('.switch input[data-id]').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const packageId = Number(this.dataset.id || 0);
            const isActive = !!this.checked;

            packageRequest(`/admin/api/packages/${packageId}/status`, 'PATCH', { is_active: isActive })
                .then(() => {
                    const status = isActive ? 'Active' : 'Inactive';
                    Swal.fire({
                        icon: 'success',
                        title: 'Status Updated',
                        text: `Package is now ${status.toLowerCase()}`,
                        timer: 1200,
                        showConfirmButton: false
                    });
                })
                .catch((error) => {
                    this.checked = !isActive;
                    Swal.fire('Error', error.message || 'Failed to update status', 'error');
                });
        });
    });
}

attachProfileSelectHandlers();
loadMikroTikProfiles();

const packageTenantSelect = document.getElementById('packageTenantId');
if (packageTenantSelect) {
    packageTenantSelect.addEventListener('change', function() {
        loadMikroTikProfiles();
    });
}

bindStatusToggles();

</script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableEl = window.jQuery ? window.jQuery('.data-table') : null;
    const tbody = document.querySelector('.data-table tbody');
    const statsBoxes = document.querySelectorAll('.row.mb-4 .small-box .inner h3');

    async function getJson(url) {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) {
            throw new Error(`Request failed: ${res.status}`);
        }
        return res.json();
    }

    function formatDuration(row) {
        if (row.duration_value && row.duration_unit) {
            return `${row.duration_value} ${row.duration_unit}`;
        }
        return '-';
    }

    function escapeAttr(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function renderRows(rows) {
        if (!tbody) return;
        if (!rows.length) {
            tbody.innerHTML = `
                <tr>
                    <td class="text-center text-muted py-4">No packages found</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = rows.map((row, index) => `
            <tr class="${row.is_active ? '' : 'text-muted'}">
                <td>
                    <strong>${escapeAttr(row.name || '-')}</strong>
                    <div class="text-muted small">${escapeAttr(row.description || 'No description')}</div>
                </td>
                <td>${formatDuration(row)}</td>
                <td><strong>KES ${Number(row.price || 0).toLocaleString()}</strong></td>
                <td><span class="text-muted">${Number(row.download_limit_mbps || 0) || '∞'} Mbps ↓ / ${Number(row.upload_limit_mbps || 0) || '∞'} Mbps ↑</span></td>
                <td><code>${escapeAttr(row.mikrotik_profile_name || '-')}</code></td>
                <td>
                    <label class="switch">
                        <input type="checkbox" data-id="${row.id || 0}" ${row.is_active ? 'checked' : ''}>
                        <span class="slider round"></span>
                    </label>
                </td>
                <td>${Number(row.total_sales || 0).toLocaleString()}</td>
                <td class="action-col">
                    <div class="btn-group">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary js-package-edit"
                            title="Edit"
                            aria-label="Edit package"
                            data-bs-toggle="modal"
                            data-bs-target="#editPackageModal"
                            data-toggle="modal"
                            data-target="#editPackageModal"
                            onclick="window.openEditPackageFromButton(this)"
                            data-id="${row.id || 0}"
                            data-name="${escapeAttr(row.name)}"
                            data-description="${escapeAttr(row.description)}"
                            data-duration-value="${row.duration_value || ''}"
                            data-duration-unit="${row.duration_unit || ''}"
                            data-price="${row.price || 0}"
                            data-download="${row.download_limit_mbps || ''}"
                            data-upload="${row.upload_limit_mbps || ''}"
                            data-profile="${escapeAttr(row.mikrotik_profile_name)}"
                            data-active="${row.is_active ? 1 : 0}"
                        ><i class="fas fa-edit"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete(${row.id || 0}, '${(row.name || '').replace(/'/g, "\\'")}')"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');

        bindStatusToggles();
    }

    function renderStats(stats) {
        if (statsBoxes.length >= 4) {
            statsBoxes[0].textContent = Number(stats.total || 0).toLocaleString();
            statsBoxes[1].textContent = Number(stats.active || 0).toLocaleString();
            statsBoxes[2].textContent = Number((stats.total || 0) - (stats.active || 0)).toLocaleString();
            statsBoxes[3].textContent = `KES ${Number(stats.revenue_week || 0).toLocaleString()}`;
        }
    }

    async function loadPackages() {
        try {
            const [rowsPayload, statsPayload] = await Promise.all([
                getJson(withTenantScope('/admin/api/packages')),
                getJson(withTenantScope('/admin/api/packages/stats'))
            ]);

            renderRows(Array.isArray(rowsPayload?.data) ? rowsPayload.data : []);
            renderStats(statsPayload || {});

            if (tableEl && $.fn.DataTable.isDataTable(tableEl)) {
                tableEl.DataTable().destroy();
            }
            if (tableEl) {
                tableEl.DataTable({ responsive: true, autoWidth: false, paging: true, searching: true, order: [[2, 'asc']] });
            }

            const footerCount = document.querySelector('.card-footer .float-end');
            if (footerCount) {
                const count = Array.isArray(rowsPayload?.data) ? rowsPayload.data.length : 0;
                footerCount.textContent = `Showing ${count.toLocaleString()} packages`;
            }
        } catch (error) {
            console.error('Failed to load packages:', error);
        }
    }

    loadPackages();
});
</script>
@endpush
