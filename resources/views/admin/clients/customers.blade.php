@extends('admin.layouts.app')

@section('page-title', 'Customers')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Customer Directory</h2>
        <p class="text-muted mb-0">Manage active subscribers and support requests.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal" data-toggle="modal" data-target="#addCustomerModal">
        <i class="fas fa-user-plus me-2"></i>Add Customer
    </button>
</div>

<!-- Stats Row -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ number_format((int) ($stats['total'] ?? 0)) }}</h3>
                <p>Total Customers</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format((int) ($stats['active'] ?? 0)) }}</h3>
                <p>Active</p>
            </div>
            <div class="icon"><i class="fas fa-signal"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format((int) ($stats['suspended'] ?? 0)) }}</h3>
                <p>Suspended</p>
            </div>
            <div class="icon"><i class="fas fa-user-slash"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format((int) ($stats['new_month'] ?? 0)) }}</h3>
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
                @forelse(($sessions ?? []) as $session)
                    @php
                        $username = $session->username ?: ($session->phone ?: 'guest');
                        $type = str_starts_with(strtolower((string) $username), 'pppoe') ? 'PPPoE' : 'Hotspot';
                        $status = strtolower((string) ($session->status ?? 'unknown'));
                        $statusBadge = match ($status) {
                            'active' => 'bg-success',
                            'suspended' => 'bg-warning',
                            'expired', 'terminated' => 'bg-danger',
                            default => 'bg-secondary',
                        };
                        $isOnline = $status === 'active';
                        $awaitingFirstLogin = $session->awaitsRadiusReauthentication();
                        $authorizationExpiresAt = $awaitingFirstLogin ? $session->pendingRadiusAuthorizationExpiresAt() : null;
                        $durationLabel = optional($session->package)->duration_formatted;
                        $expiryLabel = $awaitingFirstLogin
                            ? 'Starts on first login' . ($durationLabel ? ' (' . $durationLabel . ')' : '')
                            : (optional($session->expires_at)->format('Y-m-d H:i') ?? '-');
                    @endphp
                    <tr class="{{ $isOnline ? '' : 'text-muted' }}">
                        <td><input type="checkbox" class="customer-checkbox" value="{{ $session->id }}"></td>
                        <td>
                            <strong>{{ $username }}</strong>
                            <div class="text-muted small">{{ $session->phone ?? '-' }} - {{ $username }}</div>
                        </td>
                        <td><span class="badge {{ $type === 'PPPoE' ? 'bg-info' : 'bg-secondary' }}">{{ $type }}</span></td>
                        <td>{{ optional($session->package)->name ?? '-' }}</td>
                        <td><span class="badge {{ $statusBadge }}">{{ ucfirst($status) }}</span></td>
                        <td>
                            <div class="fw-semibold {{ $isOnline ? 'text-success' : 'text-muted' }}">{{ $isOnline ? 'Online' : 'Offline' }}</div>
                            <div class="text-muted small">{{ optional($session->last_activity_at ?? $session->started_at)->diffForHumans() ?? '-' }}</div>
                        </td>
                        <td>
                            @if($awaitingFirstLogin)
                                <div class="fw-semibold">Starts on first login</div>
                                <div class="text-muted small">{{ $durationLabel ?? '-' }}</div>
                                <div class="text-muted small">Login window: {{ $authorizationExpiresAt?->format('Y-m-d H:i') ?? '-' }}</div>
                            @else
                                <div class="fw-semibold">{{ optional($session->expires_at)->format('Y-m-d H:i') ?? '-' }}</div>
                            @endif
                        </td>
                        <td>{{ optional($session->created_at)->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>
                            <span class="status-dot {{ $isOnline ? 'online' : 'offline' }}"></span>
                            <span class="{{ $isOnline ? 'text-success' : 'text-muted' }}">{{ $isOnline ? 'Online' : 'Offline' }}</span>
                        </td>
                        <td class="action-col">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick="viewCustomerDetails(this)"
                                        data-name="{{ $username }}"
                                        data-phone="{{ $session->phone ?? '-' }}"
                                        data-username="{{ $username }}"
                                        data-type="{{ $type }}"
                                        data-package="{{ optional($session->package)->name ?? '-' }}"
                                        data-status="{{ ucfirst($status) }}"
                                        data-last-online="{{ optional($session->last_activity_at ?? $session->started_at)->diffForHumans() ?? '-' }}"
                                        data-expiry="{{ $expiryLabel }}"
                                        data-last-payment="{{ optional($session->created_at)->format('Y-m-d H:i') ?? '-' }}"
                                        data-router="{{ optional($session->router)->name ?? '-' }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="toggleCustomerStatus('{{ $username }}', 'suspend')">
                                    <i class="fas fa-user-slash"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetCustomerPassword('{{ $username }}')">
                                    <i class="fas fa-key"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center text-muted py-4">No customers available.</td>
                        <td></td>
                        <td></td>
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
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal"></button>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Cancel</button>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal"></button>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Close</button>
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

    if (window.CBModal && window.CBModal.showById) {
        window.CBModal.showById('customerDetailsModal');
    } else if (window.bootstrap && window.bootstrap.Modal) {
        new bootstrap.Modal(document.getElementById('customerDetailsModal')).show();
    } else if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
        window.jQuery('#customerDetailsModal').modal('show');
    }
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.querySelector('.data-table tbody');
    const tableEl = $('.data-table');

    async function getJson(url) {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) {
            throw new Error(`Request failed: ${res.status}`);
        }
        return res.json();
    }

    function badgeForStatus(status) {
        const normalized = String(status || '').toLowerCase();
        if (normalized === 'active') return '<span class="badge bg-success">Active</span>';
        if (normalized === 'suspended') return '<span class="badge bg-warning">Suspended</span>';
        if (normalized === 'terminated') return '<span class="badge bg-danger">Terminated</span>';
        return `<span class="badge bg-secondary">${normalized || 'unknown'}</span>`;
    }

    function renderRows(rows) {
        if (!tbody) return;

        if (!rows.length) {
            tbody.innerHTML = `
                <tr>
                    <td class="text-center text-muted py-4">No customers found</td>
                    <td></td>
                    <td></td>
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

        tbody.innerHTML = rows.map((row, index) => {
            const username = row.username || row.phone || `customer-${row.id || index + 1}`;
            const type = username.toLowerCase().startsWith('pppoe') ? 'PPPoE' : 'Hotspot';
            const packageName = row.package || '-';
            const status = row.status || '-';
            const online = String(status).toLowerCase() === 'active';
            const lastOnline = row.started_at ? new Date(row.started_at).toLocaleString('en-KE') : '-';
            const expiry = row.display_expires_at ? new Date(row.display_expires_at).toLocaleString('en-KE') : '-';
            const authorizationExpires = row.authorization_expires_at ? new Date(row.authorization_expires_at).toLocaleString('en-KE') : '-';
            const expiryDisplay = row.awaiting_first_login
                ? `Starts on first login (${row.duration_label || '-'})`
                : expiry;
            const expiryCell = row.awaiting_first_login
                ? `<div class="fw-semibold">Starts on first login</div><div class="text-muted small">${row.duration_label || '-'}</div><div class="text-muted small">Login window: ${authorizationExpires}</div>`
                : `<div class="fw-semibold">${expiry}</div>`;

            return `
                <tr>
                    <td><input type="checkbox" class="customer-checkbox" value="${row.id || index + 1}"></td>
                    <td><strong>${username}</strong><div class="text-muted small">${row.phone || '-'} - ${username}</div></td>
                    <td><span class="badge ${type === 'PPPoE' ? 'bg-info' : 'bg-secondary'}">${type}</span></td>
                    <td>${packageName}</td>
                    <td>${badgeForStatus(status)}</td>
                    <td><div class="fw-semibold ${online ? 'text-success' : 'text-muted'}">${online ? 'Online' : 'Offline'}</div><div class="text-muted small">${lastOnline}</div></td>
                    <td>${expiryCell}</td>
                    <td>${lastOnline}</td>
                    <td><span class="status-dot ${online ? 'online' : 'offline'}"></span><span class="${online ? 'text-success' : 'text-muted'}">${online ? 'Online' : 'Offline'}</span></td>
                    <td class="action-col">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewCustomerDetails(this)" data-name="${username}" data-phone="${row.phone || '-'}" data-username="${username}" data-type="${type}" data-package="${packageName}" data-status="${status}" data-last-online="${lastOnline}" data-expiry="${expiryDisplay}" data-last-payment="${lastOnline}" data-router="${row.router || '-'}"><i class="fas fa-eye"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="toggleCustomerStatus('${username}', 'suspend')"><i class="fas fa-user-slash"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetCustomerPassword('${username}')"><i class="fas fa-key"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function loadCustomers() {
        try {
            const search = document.getElementById('customerSearch')?.value?.trim() || '';
            const status = document.getElementById('customerStatus')?.value || '';

            const url = `/admin/api/clients/sessions?limit=300${status && status !== 'all' ? `&status=${encodeURIComponent(status)}` : ''}${search ? `&search=${encodeURIComponent(search)}` : ''}`;
            const payload = await getJson(url);
            renderRows(Array.isArray(payload?.data) ? payload.data : []);

            if ($.fn.DataTable.isDataTable(tableEl)) {
                tableEl.DataTable().destroy();
            }
            tableEl.DataTable({ responsive: false, autoWidth: false, paging: true, searching: false, order: [[7, 'desc']], columnDefs: [{ targets: [0, -1], orderable: false, searchable: false }] });
        } catch (error) {
            console.error('Failed to load customers:', error);
        }
    }

    document.getElementById('customerSearch')?.addEventListener('input', loadCustomers);
    document.getElementById('customerStatus')?.addEventListener('change', loadCustomers);
    document.getElementById('customerType')?.addEventListener('change', loadCustomers);
    document.getElementById('customerPackage')?.addEventListener('change', loadCustomers);

    loadCustomers();
});
</script>
@endpush
