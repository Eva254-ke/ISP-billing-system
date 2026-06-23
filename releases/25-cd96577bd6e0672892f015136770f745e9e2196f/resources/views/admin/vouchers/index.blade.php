@extends('admin.layouts.app')

@section('page-title', 'Vouchers')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Voucher Codes</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateVouchersModal" data-toggle="modal" data-target="#generateVouchersModal">
        <i class="fas fa-ticket-alt me-2"></i>Generate Codes
    </button>
</div>

<!-- Stats Row -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ number_format((int) ($stats['total'] ?? 0)) }}</h3>
                <p>Total Vouchers</p>
            </div>
            <div class="icon"><i class="fas fa-ticket-alt"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format((int) ($stats['unused'] ?? 0)) }}</h3>
                <p>Unused</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format((int) ($stats['used'] ?? 0)) }}</h3>
                <p>Used</p>
            </div>
            <div class="icon"><i class="fas fa-usage"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ number_format((int) ($stats['expired'] ?? 0)) }}</h3>
                <p>Expired</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
</div>

<!-- Filters & Actions -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6 mb-2 mb-md-0">
                <label class="form-label">Filter by Status</label>
                <select class="form-select" id="statusFilter">
                    <option value="all">All Vouchers</option>
                    <option value="unused">Unused Only</option>
                    <option value="used">Used Only</option>
                    <option value="expired">Expired Only</option>
                </select>
            </div>
            <div class="col-md-6 mb-2 mb-md-0">
                <label class="form-label">Package</label>
                <select class="form-select" id="packageFilter">
                    <option value="all">All Packages</option>
                    @foreach(($packages ?? collect()) as $package)
                        <option value="{{ $package->id }}">{{ $package->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Vouchers Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Voucher List</h3>
        <div class="card-tools">
            <div class="input-group input-group-sm" style="width: 250px;">
                <input type="text" class="form-control" id="voucherSearch" placeholder="Search vouchers...">
                <button type="button" class="btn btn-outline-secondary" onclick="searchVouchers()">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped data-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>Voucher Code</th>
                    <th>Package</th>
                    <th>Generated</th>
                    <th>Used By</th>
                    <th>Used At</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($vouchers ?? collect()) as $voucher)
                    @php
                        $status = strtolower((string) ($voucher->status ?? 'unused'));
                        $statusClass = match ($status) {
                            'unused' => 'bg-success',
                            'used' => 'bg-info',
                            'expired' => 'bg-danger',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <tr class="{{ $status === 'expired' ? 'text-muted' : '' }}">
                        <td><input type="checkbox" class="voucher-checkbox" value="{{ $voucher->id }}"></td>
                        <td>
                            <code class="bg-light px-2 py-1 rounded">{{ $voucher->code_display ?? $voucher->code }}</code>
                            <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copyCode('{{ $voucher->code ?? '' }}')" title="Copy">
                                <i class="fas fa-copy"></i>
                            </button>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ optional($voucher->package)->name ?? '-' }}</span>
                        </td>
                        <td>{{ optional($voucher->created_at)->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ $voucher->used_by_phone ?? '—' }}</td>
                        <td>{{ optional($voucher->used_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="{{ optional($voucher->valid_until)?->isPast() ? 'text-danger' : '' }}">{{ optional($voucher->valid_until)->format('Y-m-d H:i') ?? '-' }}</td>
                        <td><span class="badge {{ $statusClass }}">{{ ucfirst($status) }}</span></td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewVoucherDetails({{ (int) $voucher->id }}, '{{ $voucher->code_display ?? $voucher->code }}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete({{ (int) $voucher->id }}, '{{ $voucher->code_display ?? $voucher->code }}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center text-muted py-4">No vouchers available.</td>
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
    <div class="card-footer clearfix">
        <div class="float-start">
            <button class="btn btn-sm btn-danger" id="bulkDelete">
                <i class="fas fa-trash me-1"></i>Delete Selected
            </button>
        </div>
        <div class="float-end">
            Showing {{ number_format((int) (($vouchers ?? collect())->count())) }} vouchers
        </div>
    </div>
</div>

<!-- Print Styles (Hidden on Screen, Visible on Print) -->
<style media="print">
    @page {
        size: A4;
        margin: 10mm;
    }
    body * {
        visibility: hidden;
    }
    #printArea, #printArea * {
        visibility: visible;
    }
    #printArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 20px;
    }
    .no-print {
        display: none !important;
    }
    /* Card Print Layout */
    .voucher-card {
        border: 2px dashed #333;
        border-radius: 8px;
        padding: 15px;
        margin: 10px;
        width: 45%;
        display: inline-block;
        page-break-inside: avoid;
        box-sizing: border-box;
    }
    .voucher-card .code {
        font-size: 1.5rem;
        font-weight: bold;
        font-family: monospace;
        letter-spacing: 2px;
        text-align: center;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 4px;
        margin: 10px 0;
    }
    .voucher-card .package {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1E40AF;
        text-align: center;
    }
    .voucher-card .validity {
        text-align: center;
        color: #666;
        font-size: 0.9rem;
        margin-top: 5px;
    }
    .voucher-card .footer {
        text-align: center;
        font-size: 0.8rem;
        color: #999;
        margin-top: 10px;
        border-top: 1px solid #eee;
        padding-top: 5px;
    }
    /* List Print Layout */
    .print-list-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10pt;
    }
    .print-list-table th,
    .print-list-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    .print-list-table th {
        background: #f8f9fa;
        font-weight: 600;
    }
    .print-list-table code {
        font-size: 9pt;
        background: #f8f9fa;
        padding: 2px 4px;
        border-radius: 3px;
    }
    .print-header {
        text-align: center;
        margin-bottom: 20px;
        border-bottom: 2px solid #333;
        padding-bottom: 10px;
    }
    .print-header h2 {
        margin: 0;
        color: #1E40AF;
    }
    .print-header p {
        margin: 5px 0 0;
        color: #666;
        font-size: 0.9rem;
    }
</style>

<!-- Hidden Print Area -->
<div id="printArea" style="display: none;"></div>
@endsection

@push('scripts')
<!-- Generate Vouchers Modal -->
<div class="modal fade" id="generateVouchersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate Voucher Codes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="generateVouchersForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Select Package *</label>
                            <select class="form-select" name="package_id" required>
                                <option value="">-- Choose Package --</option>
                                @foreach(($packages ?? collect()) as $package)
                                    <option value="{{ $package->id }}">{{ $package->name }} - KES {{ number_format((float) $package->price, 0) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quantity *</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="quantity" value="1" min="1" max="1000" required>
                                <span class="input-group-text">vouchers</span>
                            </div>
                            <small class="text-muted">Set 1 for a single voucher. Typical range: 1-50 for small ISPs.</small>
                            <div class="btn-group btn-group-sm mt-2" role="group" aria-label="Quick quantity">
                                <button type="button" class="btn btn-outline-secondary" onclick="setVoucherQty(1)">1</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setVoucherQty(5)">5</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setVoucherQty(10)">10</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setVoucherQty(50)">50</button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Validity Period *</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="validity_hours" value="24" min="1" max="720" required>
                                <span class="input-group-text">hours</span>
                            </div>
                            <small class="text-muted">From first use (0 = no expiry)</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Code Prefix (Optional)</label>
                            <input type="text" class="form-control" name="prefix" value="CB-WIFI-" placeholder="e.g., CB-WIFI-">
                            <small class="text-muted">Max 10 characters, alphanumeric + hyphen</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Batch Label (Optional)</label>
                            <input type="text" class="form-control" name="batch_label" placeholder="e.g., Front Desk - March 2026">
                            <small class="text-muted">Helps trace who issued the vouchers and why.</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Output</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="output_format" value="none" checked>
                            <label class="form-check-label">No Print (Default)</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="output_format" value="list">
                            <label class="form-check-label">Print List</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="output_format" value="card">
                            <label class="form-check-label">Print Cards (4 per page)</label>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Preview:</strong> Codes will look like: <code>CB-WIFI-A1B2C3</code>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="generateVouchers()">
                    <i class="fas fa-magic me-1"></i>Generate Codes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Voucher Details Modal -->
<div class="modal fade" id="voucherDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Voucher Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted">Voucher Code</label>
                    <div class="input-group">
                        <code class="form-control bg-light" id="detailCode">CB-WIFI-D4E5F6</code>
                        <button class="btn btn-outline-secondary" onclick="copyCode(document.getElementById('detailCode').textContent)">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted">Package</label>
                        <p class="mb-0"><strong id="detailPackage">-</strong></p>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">Status</label>
                        <p class="mb-0"><span class="badge bg-secondary" id="detailStatus">Unknown</span></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted">Generated</label>
                        <p class="mb-0" id="detailGenerated">-</p>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">Expires</label>
                        <p class="mb-0" id="detailExpires">-</p>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Used By</label>
                    <p class="mb-0"><strong id="detailUsedBy">-</strong></p>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Used At</label>
                    <p class="mb-0" id="detailUsedAt">-</p>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Router</label>
                    <p class="mb-0" id="detailRouter">-</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete(window.__detailVoucherId, document.getElementById('detailCode').textContent)">
                    <i class="fas fa-trash me-1"></i>Delete Voucher
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Copy voucher code to clipboard
function copyCode(code) {
    navigator.clipboard.writeText(code.trim()).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Copied!',
            text: `Code ${code.trim()} copied to clipboard`,
            timer: 1500,
            showConfirmButton: false
        });
    });
}

// Quick quantity presets
function setVoucherQty(value) {
    const qtyInput = document.querySelector('#generateVouchersForm input[name="quantity"]');
    if (qtyInput) {
        qtyInput.value = value;
        qtyInput.dispatchEvent(new Event('input'));
    }
}

// Generate vouchers
function generateVouchers() {
    const form = document.getElementById('generateVouchersForm');
    const packageSelect = form.querySelector('select[name="package_id"]');
    const qtyInput = form.querySelector('input[name="quantity"]');
    const outputOption = form.querySelector('input[name="output_format"]:checked');
    
    if (!packageSelect.value) {
        Swal.fire('Error', 'Please select a package', 'error');
        return;
    }

    const qty = parseInt(qtyInput.value, 10);
    if (Number.isNaN(qty) || qty < 1 || qty > 1000) {
        Swal.fire('Error', 'Quantity must be between 1 and 1000', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Generating Vouchers...',
        text: 'Creating unique codes and saving to database',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const payload = {
        package_id: packageSelect.value,
        quantity: qty,
        validity_hours: Number(form.querySelector('input[name="validity_hours"]').value || 24),
        prefix: form.querySelector('input[name="prefix"]').value.trim(),
        batch_label: form.querySelector('input[name="batch_label"]').value.trim(),
    };

    fetch('/admin/api/vouchers/generate', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify(payload),
    })
    .then(async (response) => {
        const json = await response.json().catch(() => ({}));
        if (!response.ok || !json?.success) {
            const message = json?.message || 'Failed to generate vouchers';
            throw new Error(message);
        }
        return json;
    })
    .then((json) => {
        const outputValue = outputOption ? outputOption.value : 'none';
        const outputLabel = outputOption ? outputOption.nextElementSibling.textContent.trim() : 'No Print';
        const voucherLabel = qty === 1 ? 'Voucher' : 'Vouchers';
        const generated = Array.isArray(json?.data?.vouchers) ? json.data.vouchers : [];
        window.__lastGeneratedVouchers = generated;
        window.__lastGeneratedPackage = json?.data?.package?.name || packageSelect.options[packageSelect.selectedIndex].text;
        window.__lastGeneratedValidityHours = json?.data?.validity_hours || payload.validity_hours;

        const confirmText = outputValue === 'none' ? 'Done' : 'Print Now';
        const showCancel = outputValue !== 'none';

        Swal.fire({
            icon: 'success',
            title: `${generated.length || qty} ${voucherLabel} Generated!`,
            html: `
                <div class="text-start">
                    <p><strong>Package:</strong> ${window.__lastGeneratedPackage}</p>
                    <p><strong>Validity:</strong> ${window.__lastGeneratedValidityHours} hours</p>
                    <p><strong>Output:</strong> ${outputLabel}</p>
                    <p><strong>Batch:</strong> ${json?.data?.batch_name || '-'}</p>
                </div>
            `,
            showCancelButton: showCancel,
            confirmButtonText: confirmText,
            cancelButtonText: 'Close',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed && outputValue !== 'none') {
                printVouchers(outputValue);
                return;
            }
            location.reload();
        });
    })
    .catch((error) => {
        Swal.fire('Error', error.message || 'Failed to generate vouchers', 'error');
    });
}

// View voucher details
async function viewVoucherDetails(id, code) {
    try {
        window.__detailVoucherId = id;

        const response = await fetch(`/admin/api/vouchers/${id}`, {
            headers: { 'Accept': 'application/json' }
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok || !payload?.success) {
            throw new Error(payload?.message || 'Failed to load voucher details');
        }

        const row = payload.data || {};
        const status = String(row.status || 'unknown').toLowerCase();
        const statusClass = status === 'unused' ? 'bg-success' : (status === 'used' ? 'bg-warning text-dark' : (status === 'expired' ? 'bg-danger' : 'bg-secondary'));

        document.getElementById('detailCode').textContent = row.code_display || code || '-';
        document.getElementById('detailPackage').textContent = row.package_name || '-';
        const statusEl = document.getElementById('detailStatus');
        statusEl.className = `badge ${statusClass}`;
        statusEl.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        document.getElementById('detailGenerated').textContent = row.created_at ? new Date(row.created_at).toLocaleString('en-KE') : '-';
        document.getElementById('detailExpires').textContent = row.valid_until ? new Date(row.valid_until).toLocaleString('en-KE') : '-';
        document.getElementById('detailUsedBy').textContent = row.used_by_phone || '-';
        document.getElementById('detailUsedAt').textContent = row.used_at ? new Date(row.used_at).toLocaleString('en-KE') : '-';
        document.getElementById('detailRouter').textContent = row.router_name || '-';

        if (window.CBModal && window.CBModal.showById) {
            window.CBModal.showById('voucherDetailsModal');
        } else if (window.bootstrap && window.bootstrap.Modal) {
            new bootstrap.Modal(document.getElementById('voucherDetailsModal')).show();
        } else if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
            window.jQuery('#voucherDetailsModal').modal('show');
        }
    } catch (error) {
        Swal.fire('Error', error.message || 'Failed to load voucher details', 'error');
    }
}

// Confirm delete
function confirmDelete(id, code) {
    Swal.fire({
        title: 'Delete Voucher?',
        text: `Are you sure you want to delete "${code}"? This cannot be undone.`,
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

            fetch(`/admin/api/vouchers/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            })
            .then(async (response) => {
                const payload = await response.json().catch(() => ({}));
                if (!response.ok || !payload?.success) {
                    throw new Error(payload?.message || 'Failed to delete voucher');
                }
                return payload;
            })
            .then(() => {
                Swal.fire('Deleted!', `Voucher ${code} has been deleted.`, 'success')
                    .then(() => location.reload());
            })
            .catch((error) => {
                Swal.fire('Error', error.message || 'Failed to delete voucher', 'error');
            });
        }
    });
}

// Bulk delete
document.getElementById('bulkDelete').addEventListener('click', function() {
    const selected = document.querySelectorAll('.voucher-checkbox:checked');
    if (selected.length === 0) {
        Swal.fire('Info', 'No vouchers selected', 'info');
        return;
    }
    Swal.fire({
        title: 'Delete Selected Vouchers?',
        text: `You are about to delete ${selected.length} voucher(s). This cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete!',
        confirmButtonColor: '#EF4444'
    }).then((result) => {
        if (result.isConfirmed) {
            const ids = Array.from(selected)
                .map((cb) => Number(cb.value))
                .filter((id) => Number.isInteger(id) && id > 0);

            fetch('/admin/api/vouchers/bulk-delete', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ voucher_ids: ids }),
            })
            .then(async (response) => {
                const payload = await response.json().catch(() => ({}));
                if (!response.ok || !payload?.success) {
                    throw new Error(payload?.message || 'Failed to delete selected vouchers');
                }
                return payload;
            })
            .then((payload) => {
                Swal.fire('Deleted!', payload.message || 'Selected vouchers deleted.', 'success')
                    .then(() => location.reload());
            })
            .catch((error) => {
                Swal.fire('Error', error.message || 'Failed to delete selected vouchers', 'error');
            });
        }
    });
});

// Select all checkboxes
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.voucher-checkbox').forEach(cb => {
        cb.checked = this.checked;
    });
});

// Search vouchers is bound to live loader below.
function searchVouchers() {}

// Print vouchers - List or Card format
function printVouchers(format = 'list') {
    const generatedVouchers = Array.isArray(window.__lastGeneratedVouchers) ? window.__lastGeneratedVouchers : [];
    const generatedPackage = window.__lastGeneratedPackage || 'WiFi Package';
    const generatedValidity = Number(window.__lastGeneratedValidityHours || 24);

    if (!generatedVouchers.length) {
        Swal.fire('Info', 'Generate vouchers first to print them.', 'info');
        return;
    }

    const printArea = document.getElementById('printArea');
    const today = new Date().toLocaleDateString('en-KE', { 
        year: 'numeric', month: 'long', day: 'numeric' 
    });
    
    if (format === 'card') {
        // Card format: 4 vouchers per page
        printArea.innerHTML = `
            <div class="print-header no-print">
                <h2>CloudBridge Networks</h2>
                <p>Voucher Cards - Printed: ${today}</p>
            </div>
            ${generatedVouchers.map((voucher) => `
                <div class="voucher-card">
                    <div class="package">${generatedPackage}</div>
                    <div class="code">${voucher.code_display || voucher.code || '-'}</div>
                    <div class="validity">Valid: ${generatedValidity} hours from first use</div>
                    <div class="footer">CloudBridge Networks • www.cloudbridge.network</div>
                </div>
            `).join('')}
            <div class="no-print" style="text-align: center; margin-top: 20px;">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Print Now
                </button>
                <button class="btn btn-secondary ms-2" onclick="closePrint()">
                    Close Preview
                </button>
            </div>
        `;
    } else {
        // List format: table view
        printArea.innerHTML = `
            <div class="print-header">
                <h2>CloudBridge Networks</h2>
                <p>Voucher List - Printed: ${today}</p>
            </div>
            <table class="print-list-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Voucher Code</th>
                        <th>Package</th>
                        <th>Generated</th>
                        <th>Status</th>
                        <th>Expires</th>
                    </tr>
                </thead>
                <tbody>
                    ${generatedVouchers.map((voucher, index) => `
                        <tr>
                            <td>${index + 1}</td>
                            <td><code>${voucher.code_display || voucher.code || '-'}</code></td>
                            <td>${generatedPackage}</td>
                            <td>${voucher.created_at ? new Date(voucher.created_at).toLocaleString('en-KE') : '-'}</td>
                            <td>Unused</td>
                            <td>${voucher.valid_until ? new Date(voucher.valid_until).toLocaleString('en-KE') : '-'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            <div class="no-print" style="text-align: center; margin-top: 20px;">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Print Now
                </button>
                <button class="btn btn-secondary ms-2" onclick="closePrint()">
                    Close Preview
                </button>
            </div>
        `;
    }
    
    // Show print area and trigger print
    printArea.style.display = 'block';
    
    Swal.fire({
        title: 'Print Preview Ready',
        text: 'Click "Print Now" to send to printer, or "Close" to cancel',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Print Now',
        cancelButtonText: 'Close Preview'
    }).then((result) => {
        if (result.isConfirmed) {
            window.print();
        }
        closePrint();
    });
}

// Close print preview
function closePrint() {
    document.getElementById('printArea').style.display = 'none';
    document.getElementById('printArea').innerHTML = '';
}

// Handle browser print completion
window.onafterprint = function() {
    closePrint();
};
</script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableEl = $('.data-table');
    const tbody = document.querySelector('.data-table tbody');
    const statsBoxes = document.querySelectorAll('.row.mb-4 .small-box .inner h3');

    async function getJson(url) {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) {
            throw new Error(`Request failed: ${res.status}`);
        }
        return res.json();
    }

    function statusBadge(status) {
        const normalized = String(status || '').toLowerCase();
        if (normalized === 'unused') return '<span class="badge bg-success">Unused</span>';
        if (normalized === 'used') return '<span class="badge bg-warning text-dark">Used</span>';
        if (normalized === 'expired') return '<span class="badge bg-danger">Expired</span>';
        return `<span class="badge bg-secondary">${normalized || 'unknown'}</span>`;
    }

    function renderRows(rows) {
        if (!tbody) return;
        if (!rows.length) {
            tbody.innerHTML = `
                <tr>
                    <td class="text-center text-muted py-4">No vouchers found</td>
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
            const created = row.created_at ? new Date(row.created_at).toLocaleString('en-KE') : '-';
            const usedAt = row.used_at ? new Date(row.used_at).toLocaleString('en-KE') : '—';
            const expiry = row.valid_until ? new Date(row.valid_until).toLocaleString('en-KE') : '-';

            return `
                <tr>
                    <td><input type="checkbox" class="voucher-checkbox" value="${row.id || index + 1}"></td>
                    <td><code class="bg-light px-2 py-1 rounded">${row.code_display || row.code || '-'}</code>
                        <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copyCode('${(row.code || '').replace(/'/g, "\\'")}')" title="Copy"><i class="fas fa-copy"></i></button>
                    </td>
                    <td><span class="badge bg-secondary">${row.package_name || '-'}</span></td>
                    <td>${created}</td>
                    <td>${row.used_by_phone || '—'}</td>
                    <td>${usedAt}</td>
                    <td>${expiry}</td>
                    <td>${statusBadge(row.status)}</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewVoucherDetails(${row.id || 0}, '${(row.code_display || row.code || '').replace(/'/g, "\\'")}')"><i class="fas fa-eye"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(${row.id || 0}, '${(row.code_display || row.code || '').replace(/'/g, "\\'")}')"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderStats(rows) {
        const total = rows.length;
        const unused = rows.filter(r => String(r.status).toLowerCase() === 'unused').length;
        const used = rows.filter(r => String(r.status).toLowerCase() === 'used').length;
        const expired = rows.filter(r => String(r.status).toLowerCase() === 'expired').length;

        if (statsBoxes.length >= 4) {
            statsBoxes[0].textContent = total.toLocaleString();
            statsBoxes[1].textContent = unused.toLocaleString();
            statsBoxes[2].textContent = used.toLocaleString();
            statsBoxes[3].textContent = expired.toLocaleString();
        }
    }

    async function loadVouchers() {
        try {
            const status = document.getElementById('statusFilter')?.value || '';
            const search = document.getElementById('voucherSearch')?.value?.trim() || '';
            const packageIdRaw = document.getElementById('packageFilter')?.value || '';
            const packageId = /^\d+$/.test(packageIdRaw) ? packageIdRaw : '';

            const url = `/admin/api/vouchers?limit=300${status && status !== 'all' ? `&status=${encodeURIComponent(status)}` : ''}${packageId ? `&package_id=${encodeURIComponent(packageId)}` : ''}${search ? `&search=${encodeURIComponent(search)}` : ''}`;
            const payload = await getJson(url);
            const rows = Array.isArray(payload?.data) ? payload.data : [];

            renderRows(rows);
            renderStats(rows);

            if ($.fn.DataTable.isDataTable(tableEl)) {
                tableEl.DataTable().destroy();
            }
            tableEl.DataTable({ responsive: true, autoWidth: false, paging: true, searching: false, order: [[3, 'desc']] });

            const footerCount = document.querySelector('.card-footer .float-end');
            if (footerCount) {
                footerCount.textContent = `Showing ${rows.length.toLocaleString()} vouchers`;
            }
        } catch (error) {
            console.error('Failed to load vouchers:', error);
        }
    }

    window.searchVouchers = loadVouchers;
    document.getElementById('statusFilter')?.addEventListener('change', loadVouchers);
    document.getElementById('packageFilter')?.addEventListener('change', loadVouchers);

    loadVouchers();
});
</script>
@endpush
