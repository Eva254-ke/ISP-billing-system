@extends('admin.layouts.app')

@section('page-title', 'Packages')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>WiFi Packages</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPackageModal">
        <i class="fas fa-plus me-2"></i>Create Package
    </button>
</div>

<!-- Stats Row -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>12</h3>
                <p>Total Packages</p>
            </div>
            <div class="icon"><i class="fas fa-box"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>8</h3>
                <p>Active</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>4</h3>
                <p>Inactive</p>
            </div>
            <div class="icon"><i class="fas fa-pause-circle"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>KES 45,200</h3>
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Package 1: 1 Hour -->
                <tr>
                    <td>
                        <strong>1 Hour Pass</strong>
                        <div class="text-muted small">Quick browsing</div>
                    </td>
                    <td>
                        <span class="badge bg-secondary">60 minutes</span>
                    </td>
                    <td><strong>KES 50</strong></td>
                    <td>
                        <span class="text-muted">5 Mbps ↓ / 2 Mbps ↑</span>
                    </td>
                    <td><code>profile-1hour</code></td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider round"></span>
                        </label>
                    </td>
                    <td>156</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="Edit" data-bs-toggle="modal" data-bs-target="#editPackageModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete('1 Hour Pass')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Package 2: 3 Hours -->
                <tr>
                    <td>
                        <strong>3 Hours Pass</strong>
                        <div class="text-muted small">Extended session</div>
                    </td>
                    <td>
                        <span class="badge bg-secondary">180 minutes</span>
                    </td>
                    <td><strong>KES 100</strong></td>
                    <td>
                        <span class="text-muted">5 Mbps ↓ / 2 Mbps ↑</span>
                    </td>
                    <td><code>profile-3hours</code></td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider round"></span>
                        </label>
                    </td>
                    <td>89</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="Edit" data-bs-toggle="modal" data-bs-target="#editPackageModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete('3 Hours Pass')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Package 3: 24 Hours -->
                <tr>
                    <td>
                        <strong>24 Hours Pass</strong>
                        <div class="text-muted small">Full day access</div>
                    </td>
                    <td>
                        <span class="badge bg-primary">1440 minutes</span>
                    </td>
                    <td><strong>KES 400</strong></td>
                    <td>
                        <span class="text-muted">10 Mbps ↓ / 5 Mbps ↑</span>
                    </td>
                    <td><code>profile-24hours</code></td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider round"></span>
                        </label>
                    </td>
                    <td>234</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="Edit" data-bs-toggle="modal" data-bs-target="#editPackageModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete('24 Hours Pass')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Package 4: Weekly -->
                <tr>
                    <td>
                        <strong>Weekly Pass</strong>
                        <div class="text-muted small">7 days unlimited</div>
                    </td>
                    <td>
                        <span class="badge bg-success">7 days</span>
                    </td>
                    <td><strong>KES 2,000</strong></td>
                    <td>
                        <span class="text-muted">10 Mbps ↓ / 5 Mbps ↑</span>
                    </td>
                    <td><code>profile-weekly</code></td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider round"></span>
                        </label>
                    </td>
                    <td>45</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="Edit" data-bs-toggle="modal" data-bs-target="#editPackageModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete('Weekly Pass')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Package 5: Monthly (Inactive) -->
                <tr class="text-muted">
                    <td>
                        <strong>Monthly Pass</strong>
                        <div class="text-muted small">30 days premium</div>
                    </td>
                    <td>
                        <span class="badge bg-info">30 days</span>
                    </td>
                    <td><strong>KES 5,000</strong></td>
                    <td>
                        <span class="text-muted">15 Mbps ↓ / 10 Mbps ↑</span>
                    </td>
                    <td><code>profile-monthly</code></td>
                    <td>
                        <label class="switch">
                            <input type="checkbox">
                            <span class="slider round"></span>
                        </label>
                    </td>
                    <td>12</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="Edit" data-bs-toggle="modal" data-bs-target="#editPackageModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete('Monthly Pass')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer clearfix">
        <div class="float-end">
            Showing 1-5 of 12 packages
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
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
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
                            <option value="profile-1hour">profile-1hour</option>
                            <option value="profile-3hours">profile-3hours</option>
                            <option value="profile-24hours">profile-24hours</option>
                            <option value="profile-weekly">profile-weekly</option>
                            <option value="profile-monthly">profile-monthly</option>
                            <option value="custom">-- Custom --</option>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editPackageForm">
                    <input type="hidden" name="id" value="1">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Package Name *</label>
                            <input type="text" class="form-control" name="name" value="1 Hour Pass" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" name="description" value="Quick browsing">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Duration *</label>
                            <input type="number" class="form-control" name="duration" value="60" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Duration Unit *</label>
                            <select class="form-select" name="duration_unit" required>
                                <option value="minutes" selected>Minutes</option>
                                <option value="hours">Hours</option>
                                <option value="days">Days</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price (KES) *</label>
                            <input type="number" class="form-control" name="price" value="50" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Download Speed (Mbps)</label>
                            <input type="number" class="form-control" name="download_speed" value="5" min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Upload Speed (Mbps)</label>
                            <input type="number" class="form-control" name="upload_speed" value="2" min="1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">MikroTik User Profile</label>
                        <input type="text" class="form-control" name="mikrotik_profile" value="profile-1hour">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive" checked>
                        <label class="form-check-label" for="editIsActive">
                            Active (visible to customers)
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
// Custom profile name toggle
document.querySelector('select[name="mikrotik_profile"]').addEventListener('change', function() {
    const customInput = document.querySelector('input[name="custom_profile"]');
    customInput.disabled = this.value !== 'custom';
    if (this.value === 'custom') customInput.focus();
});

// Save new package (Mock)
function savePackage() {
    Swal.fire({
        title: 'Creating Package...',
        text: 'Saving package and syncing with MikroTik',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    setTimeout(() => {
        Swal.fire({
            icon: 'success',
            title: 'Package Created!',
            text: 'Package has been added and is now available to customers.',
            timer: 2000,
            showConfirmButton: false
        }).then(() => location.reload());
    }, 2000);
}

// Update package (Mock)
function updatePackage() {
    Swal.fire({
        title: 'Updating Package...',
        text: 'Saving changes and syncing with MikroTik',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    setTimeout(() => {
        Swal.fire({
            icon: 'success',
            title: 'Package Updated!',
            text: 'Changes have been saved successfully.',
            timer: 2000,
            showConfirmButton: false
        }).then(() => location.reload());
    }, 1500);
}

// Confirm delete
function confirmDelete(packageName) {
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
            setTimeout(() => {
                Swal.fire('Deleted!', `"${packageName}" has been deleted.`, 'success')
                    .then(() => location.reload());
            }, 1000);
        }
    });
}

// Toggle switch functionality
document.querySelectorAll('.switch input').forEach(toggle => {
    toggle.addEventListener('change', function() {
        const status = this.checked ? 'Active' : 'Inactive';
        Swal.fire({
            icon: 'success',
            title: 'Status Updated',
            text: `Package is now ${status.toLowerCase()}`,
            timer: 1500,
            showConfirmButton: false
        });
    });
});

// Initialize DataTable
$(document).ready(function() {
    $('.data-table').DataTable({
        responsive: true,
        autoWidth: false,
        paging: true,
        searching: true,
        order: [[2, 'asc']]
    });
});
</script>
@endpush
