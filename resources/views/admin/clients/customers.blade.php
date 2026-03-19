@extends('admin.layouts.app')

@section('page-title', 'Customers')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Customer Directory</h2>
        <p class="text-muted mb-0">Manage active subscribers and support requests.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
        <i class="fas fa-user-plus me-2"></i>Add Customer
    </button>
</div>

<!-- Stats Row -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>312</h3>
                <p>Total Customers</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>218</h3>
                <p>Active</p>
            </div>
            <div class="icon"><i class="fas fa-signal"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>24</h3>
                <p>Suspended</p>
            </div>
            <div class="icon"><i class="fas fa-user-slash"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>34</h3>
                <p>New This Month</p>
            </div>
            <div class="icon"><i class="fas fa-user-check"></i></div>
        </div>
    </div>
</div>

<!-- Filters & Actions -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-3 mb-3 mb-md-0">
                <label class="form-label">Status</label>
                <select class="form-select" id="customerStatus">
                    <option value="all">All</option>
                    <option value="Active">Active</option>
                    <option value="Suspended">Suspended</option>
                    <option value="Expired">Expired</option>
                </select>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <label class="form-label">Access Type</label>
                <select class="form-select" id="customerType">
                    <option value="all">All</option>
                    <option value="Hotspot">Hotspot</option>
                    <option value="PPPoE">PPPoE</option>
                </select>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <label class="form-label">Package</label>
                <select class="form-select" id="customerPackage">
                    <option value="all">All Packages</option>
                    <option value="1 Hour">1 Hour Pass</option>
                    <option value="Weekly">Weekly Hotspot</option>
                    <option value="Monthly">Monthly 10Mbps</option>
                    <option value="Business">Business 20Mbps</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" id="customerSearch" placeholder="Name, phone, username">
            </div>
        </div>
    </div>
</div>

<!-- Customer Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Customer List</h3>
        <div class="card-tools d-flex gap-2">
            <button class="btn btn-sm btn-outline-warning" id="bulkSuspend">
                <i class="fas fa-user-slash me-1"></i>Suspend Selected
            </button>
            <button class="btn btn-sm btn-outline-success" id="bulkActivate">
                <i class="fas fa-user-check me-1"></i>Activate Selected
            </button>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped data-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAllCustomers"></th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Package</th>
                    <th>Status</th>
                    <th>Last Online</th>
                    <th>Expiry</th>
                    <th>Last Payment</th>
                    <th>Connection</th>
                    <th class="action-col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="checkbox" class="customer-checkbox" value="1"></td>
                    <td class="action-col">
                        <strong>Jane W.</strong>
                        <div class="text-muted small">0712***678 - janew</div>
                    </td>
                    <td><span class="badge bg-secondary">Hotspot</span></td>
                    <td>Weekly Hotspot</td>
                    <td><span class="badge bg-success">Active</span></td>
                    <td class="action-col">
                        <div class="fw-semibold text-success">Online</div>
                        <div class="text-muted small">Just now</div>
                    </td>
                    <td class="action-col">
                        <div class="fw-semibold">2026-03-25</div>
                        <span class="badge bg-success">7 days left</span>
                    </td>
                    <td>2026-03-18 10:22</td>
                    <td class="action-col">
                        <span class="status-dot online"></span>
                        <span class="text-success">Online</span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary"
                                    onclick="viewCustomerDetails(this)"
                                    data-name="Jane W."
                                    data-phone="0712***678"
                                    data-username="janew"
                                    data-type="Hotspot"
                                    data-package="Weekly Hotspot"
                                    data-status="Active"
                                    data-last-online="Just now"
                                    data-expiry="2026-03-25"
                                    data-last-payment="2026-03-18 10:22"
                                    data-router="Main Hotspot (192.168.88.1)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning" onclick="toggleCustomerStatus('Jane W.', 'suspend')">
                                <i class="fas fa-user-slash"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="resetCustomerPassword('Jane W.')">
                                <i class="fas fa-key"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><input type="checkbox" class="customer-checkbox" value="2"></td>
                    <td>
                        <strong>Michael K.</strong>
                        <div class="text-muted small">0745***901 - mikek</div>
                    </td>
                    <td><span class="badge bg-info">PPPoE</span></td>
                    <td>Monthly 10Mbps</td>
                    <td><span class="badge bg-success">Active</span></td>
                    <td>
                        <div class="fw-semibold text-success">Online</div>
                        <div class="text-muted small">2 min ago</div>
                    </td>
                    <td>
                        <div class="fw-semibold">2026-04-17</div>
                        <span class="badge bg-info">29 days left</span>
                    </td>
                    <td>2026-03-17 09:05</td>
                    <td>
                        <span class="status-dot online"></span>
                        <span class="text-success">Online</span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary"
                                    onclick="viewCustomerDetails(this)"
                                    data-name="Michael K."
                                    data-phone="0745***901"
                                    data-username="mikek"
                                    data-type="PPPoE"
                                    data-package="Monthly 10Mbps"
                                    data-status="Active"
                                    data-last-online="2 min ago"
                                    data-expiry="2026-04-17"
                                    data-last-payment="2026-03-17 09:05"
                                    data-router="PPPoE Server (192.168.88.2)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning" onclick="toggleCustomerStatus('Michael K.', 'suspend')">
                                <i class="fas fa-user-slash"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="resetCustomerPassword('Michael K.')">
                                <i class="fas fa-key"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr class="text-muted">
                    <td><input type="checkbox" class="customer-checkbox" value="3"></td>
                    <td>
                        <strong>Agnes M.</strong>
                        <div class="text-muted small">0723***789 - agnesm</div>
                    </td>
                    <td><span class="badge bg-secondary">Hotspot</span></td>
                    <td>1 Hour Pass</td>
                    <td><span class="badge bg-warning">Suspended</span></td>
                    <td>
                        <div class="fw-semibold text-muted">Offline</div>
                        <div class="text-muted small">3 days ago</div>
                    </td>
                    <td>
                        <div class="fw-semibold">2026-03-10</div>
                        <span class="badge bg-danger">Expired</span>
                    </td>
                    <td>2026-03-10 16:40</td>
                    <td>
                        <span class="status-dot offline"></span>
                        <span class="text-muted">Offline</span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary"
                                    onclick="viewCustomerDetails(this)"
                                    data-name="Agnes M."
                                    data-phone="0723***789"
                                    data-username="agnesm"
                                    data-type="Hotspot"
                                    data-package="1 Hour Pass"
                                    data-status="Suspended"
                                    data-last-online="3 days ago"
                                    data-expiry="2026-03-10"
                                    data-last-payment="2026-03-10 16:40"
                                    data-router="Main Hotspot (192.168.88.1)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="toggleCustomerStatus('Agnes M.', 'activate')">
                                <i class="fas fa-user-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="resetCustomerPassword('Agnes M.')">
                                <i class="fas fa-key"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr class="text-muted">
                    <td><input type="checkbox" class="customer-checkbox" value="4"></td>
                    <td>
                        <strong>Rashid A.</strong>
                        <div class="text-muted small">0701***224 - rashida</div>
                    </td>
                    <td><span class="badge bg-info">PPPoE</span></td>
                    <td>Business 20Mbps</td>
                    <td><span class="badge bg-danger">Expired</span></td>
                    <td>
                        <div class="fw-semibold text-muted">Offline</div>
                        <div class="text-muted small">12 days ago</div>
                    </td>
                    <td>
                        <div class="fw-semibold">2026-02-28</div>
                        <span class="badge bg-danger">Expired</span>
                    </td>
                    <td>2026-02-28 08:15</td>
                    <td>
                        <span class="status-dot offline"></span>
                        <span class="text-muted">Offline</span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary"
                                    onclick="viewCustomerDetails(this)"
                                    data-name="Rashid A."
                                    data-phone="0701***224"
                                    data-username="rashida"
                                    data-type="PPPoE"
                                    data-package="Business 20Mbps"
                                    data-status="Expired"
                                    data-last-online="12 days ago"
                                    data-expiry="2026-02-28"
                                    data-last-payment="2026-02-28 08:15"
                                    data-router="PPPoE Server (192.168.88.2)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="toggleCustomerStatus('Rashid A.', 'activate')">
                                <i class="fas fa-user-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="resetCustomerPassword('Rashid A.')">
                                <i class="fas fa-key"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" placeholder="e.g., Jane W." required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number *</label>
                        <input type="tel" class="form-control" placeholder="07XX XXX XXX" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Access Type *</label>
                        <select class="form-select" required>
                            <option value="">Select</option>
                            <option>Hotspot</option>
                            <option>PPPoE</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Package *</label>
                        <select class="form-select" required>
                            <option value="">Select</option>
                            <option>1 Hour Pass</option>
                            <option>Weekly Hotspot</option>
                            <option>Monthly 10Mbps</option>
                            <option>Business 20Mbps</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" placeholder="Optional">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Initial Status</label>
                        <select class="form-select">
                            <option>Active</option>
                            <option>Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Password reset link will be sent after saving.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveCustomer()">
                    <i class="fas fa-save me-1"></i>Save Customer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Customer Details Modal -->
<div class="modal fade" id="customerDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Customer Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2"><strong id="custName">Customer Name</strong></div>
                <div class="text-muted mb-3" id="custContact">Phone - Username</div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted">Access Type</label>
                        <p class="mb-0" id="custType">Hotspot</p>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">Package</label>
                        <p class="mb-0" id="custPackage">Weekly Hotspot</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted">Status</label>
                        <p class="mb-0" id="custStatus">Active</p>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">Last Online</label>
                        <p class="mb-0" id="custLastOnline">Just now</p>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label text-muted">Router</label>
                    <p class="mb-0" id="custRouter">Main Hotspot</p>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted">Expiry</label>
                        <p class="mb-0" id="custExpiry">2026-03-25</p>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">Last Payment</label>
                        <p class="mb-0" id="custPayment">2026-03-18 10:22</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-primary" onclick="sendCustomerMessage()">
                    <i class="fas fa-paper-plane me-1"></i>Message
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function viewCustomerDetails(button) {
    const data = button.dataset;
    document.getElementById('custName').textContent = data.name;
    document.getElementById('custContact').textContent = `${data.phone} - ${data.username}`;
    document.getElementById('custType').textContent = data.type;
    document.getElementById('custPackage').textContent = data.package;
    document.getElementById('custStatus').textContent = data.status;
    document.getElementById('custLastOnline').textContent = data.lastOnline || '—';
    document.getElementById('custExpiry').textContent = data.expiry || '—';
    document.getElementById('custPayment').textContent = data.lastPayment;
    document.getElementById('custRouter').textContent = data.router;

    new bootstrap.Modal(document.getElementById('customerDetailsModal')).show();
}

function toggleCustomerStatus(name, action) {
    const isSuspend = action === 'suspend';
    Swal.fire({
        title: `${isSuspend ? 'Suspend' : 'Activate'} Customer?`,
        text: `${name} will be ${isSuspend ? 'temporarily blocked' : 'restored'} immediately.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: isSuspend ? 'Yes, Suspend' : 'Yes, Activate',
        confirmButtonColor: isSuspend ? '#F59E0B' : '#10B981'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Done!', `${name} has been ${isSuspend ? 'suspended' : 'activated'}.`, 'success');
        }
    });
}

function resetCustomerPassword(name) {
    Swal.fire({
        title: 'Send Password Reset?',
        text: `A reset link will be sent to ${name}.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Send Link'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Sent!', 'Reset link sent successfully.', 'success');
        }
    });
}

function saveCustomer() {
    Swal.fire({
        title: 'Saving Customer...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    setTimeout(() => {
        Swal.fire('Saved!', 'Customer created successfully.', 'success')
            .then(() => location.reload());
    }, 1000);
}

function sendCustomerMessage() {
    Swal.fire({
        title: 'Send Message',
        input: 'textarea',
        inputPlaceholder: 'Type message to customer...',
        showCancelButton: true,
        confirmButtonText: 'Send'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Sent!', 'Message delivered.', 'success');
        }
    });
}

// Select all checkboxes
document.getElementById('selectAllCustomers').addEventListener('change', function() {
    document.querySelectorAll('.customer-checkbox').forEach(cb => {
        cb.checked = this.checked;
    });
});

// Bulk actions
document.getElementById('bulkSuspend').addEventListener('click', function() {
    const selected = document.querySelectorAll('.customer-checkbox:checked');
    if (selected.length === 0) {
        Swal.fire('Info', 'No customers selected', 'info');
        return;
    }
    Swal.fire({
        title: 'Suspend Selected Customers?',
        text: `You are about to suspend ${selected.length} customer(s).`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Suspend',
        confirmButtonColor: '#F59E0B'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Done!', 'Customers suspended.', 'success');
        }
    });
});

document.getElementById('bulkActivate').addEventListener('click', function() {
    const selected = document.querySelectorAll('.customer-checkbox:checked');
    if (selected.length === 0) {
        Swal.fire('Info', 'No customers selected', 'info');
        return;
    }
    Swal.fire({
        title: 'Activate Selected Customers?',
        text: `You are about to activate ${selected.length} customer(s).`,
        icon: 'success',
        showCancelButton: true,
        confirmButtonText: 'Activate'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Done!', 'Customers activated.', 'success');
        }
    });
});

// DataTable and filters
$(document).ready(function() {
    const tableEl = $('.data-table');
    if ($.fn.DataTable.isDataTable(tableEl)) {
        tableEl.DataTable().destroy();
    }
    const table = tableEl.DataTable({
        responsive: false,
        scrollX: true,
        scrollCollapse: true,
        autoWidth: false,
        paging: true,
        searching: true,
        order: [[7, 'desc']],
        columnDefs: [
            { targets: [0, -1], orderable: false, searchable: false }
        ]
    });

    $('#customerSearch').on('keyup', function() {
        table.search(this.value).draw();
    });

    function applyCustomerFilters() {
        const status = $('#customerStatus').val();
        const type = $('#customerType').val();
        const pkg = $('#customerPackage').val();

        table.column(4).search(status === 'all' ? '' : status);
        table.column(2).search(type === 'all' ? '' : type);
        table.column(3).search(pkg === 'all' ? '' : pkg);
        table.draw();
    }

    $('#customerStatus, #customerType, #customerPackage').on('change', applyCustomerFilters);
});
</script>
@endpush
