@extends('admin.layouts.app')

@section('page-title', 'Vouchers')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Voucher Codes</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateVouchersModal">
        <i class="fas fa-ticket-alt me-2"></i>Generate Codes
    </button>
</div>

<!-- Stats Row -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>500</h3>
                <p>Total Vouchers</p>
            </div>
            <div class="icon"><i class="fas fa-ticket-alt"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>312</h3>
                <p>Unused</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>188</h3>
                <p>Used</p>
            </div>
            <div class="icon"><i class="fas fa-usage"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>24</h3>
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
                    <option value="1hour">1 Hour Pass</option>
                    <option value="3hours">3 Hours Pass</option>
                    <option value="24hours">24 Hours Pass</option>
                    <option value="weekly">Weekly Pass</option>
                    <option value="monthly">Monthly Pass</option>
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
                <!-- Voucher 1: Unused -->
                <tr>
                    <td><input type="checkbox" class="voucher-checkbox" value="1"></td>
                    <td>
                        <code class="bg-light px-2 py-1 rounded">CB-WIFI-A1B2C3</code>
                        <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copyCode('CB-WIFI-A1B2C3')" title="Copy">
                            <i class="fas fa-copy"></i>
                        </button>
                    </td>
                    <td>
                        <span class="badge bg-secondary">1 Hour Pass</span>
                    </td>
                    <td>2026-03-19 10:30</td>
                    <td class="text-muted">—</td>
                    <td class="text-muted">—</td>
                    <td>2026-03-20 10:30</td>
                    <td><span class="badge bg-success">Unused</span></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete('CB-WIFI-A1B2C3')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Voucher 2: Used -->
                <tr>
                    <td><input type="checkbox" class="voucher-checkbox" value="2"></td>
                    <td>
                        <code class="bg-light px-2 py-1 rounded">CB-WIFI-D4E5F6</code>
                        <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copyCode('CB-WIFI-D4E5F6')" title="Copy">
                            <i class="fas fa-copy"></i>
                        </button>
                    </td>
                    <td>
                        <span class="badge bg-primary">24 Hours Pass</span>
                    </td>
                    <td>2026-03-18 14:20</td>
                    <td>0712***678</td>
                    <td>2026-03-18 15:45</td>
                    <td>2026-03-19 14:20</td>
                    <td><span class="badge bg-info">Used</span></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewVoucherDetails('CB-WIFI-D4E5F6')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete('CB-WIFI-D4E5F6')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Voucher 3: Expired -->
                <tr class="text-muted">
                    <td><input type="checkbox" class="voucher-checkbox" value="3"></td>
                    <td>
                        <code class="bg-light px-2 py-1 rounded">CB-WIFI-G7H8I9</code>
                        <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copyCode('CB-WIFI-G7H8I9')" title="Copy">
                            <i class="fas fa-copy"></i>
                        </button>
                    </td>
                    <td>
                        <span class="badge bg-secondary">3 Hours Pass</span>
                    </td>
                    <td>2026-03-15 09:00</td>
                    <td class="text-muted">—</td>
                    <td class="text-muted">—</td>
                    <td class="text-danger">2026-03-16 09:00</td>
                    <td><span class="badge bg-danger">Expired</span></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete('CB-WIFI-G7H8I9')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Voucher 4: Unused -->
                <tr>
                    <td><input type="checkbox" class="voucher-checkbox" value="4"></td>
                    <td>
                        <code class="bg-light px-2 py-1 rounded">CB-WIFI-J0K1L2</code>
                        <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copyCode('CB-WIFI-J0K1L2')" title="Copy">
                            <i class="fas fa-copy"></i>
                        </button>
                    </td>
                    <td>
                        <span class="badge bg-success">Weekly Pass</span>
                    </td>
                    <td>2026-03-19 11:15</td>
                    <td class="text-muted">—</td>
                    <td class="text-muted">—</td>
                    <td>2026-03-26 11:15</td>
                    <td><span class="badge bg-success">Unused</span></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete('CB-WIFI-J0K1L2')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Voucher 5: Used -->
                <tr>
                    <td><input type="checkbox" class="voucher-checkbox" value="5"></td>
                    <td>
                        <code class="bg-light px-2 py-1 rounded">CB-WIFI-M3N4O5</code>
                        <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copyCode('CB-WIFI-M3N4O5')" title="Copy">
                            <i class="fas fa-copy"></i>
                        </button>
                    </td>
                    <td>
                        <span class="badge bg-secondary">1 Hour Pass</span>
                    </td>
                    <td>2026-03-19 08:45</td>
                    <td>0723***789</td>
                    <td>2026-03-19 09:10</td>
                    <td>2026-03-19 10:45</td>
                    <td><span class="badge bg-info">Used</span></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewVoucherDetails('CB-WIFI-M3N4O5')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete('CB-WIFI-M3N4O5')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
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
            Showing 1-5 of 500 vouchers
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
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="generateVouchersForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Select Package *</label>
                            <select class="form-select" name="package_id" required>
                                <option value="">-- Choose Package --</option>
                                <option value="1">1 Hour Pass - KES 50</option>
                                <option value="2">3 Hours Pass - KES 100</option>
                                <option value="3">24 Hours Pass - KES 400</option>
                                <option value="4">Weekly Pass - KES 2,000</option>
                                <option value="5">Monthly Pass - KES 5,000</option>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                        <p class="mb-0"><strong>24 Hours Pass</strong></p>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">Status</label>
                        <p class="mb-0"><span class="badge bg-info">Used</span></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted">Generated</label>
                        <p class="mb-0">2026-03-18 14:20</p>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">Expires</label>
                        <p class="mb-0">2026-03-19 14:20</p>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Used By</label>
                    <p class="mb-0"><strong>0712***678</strong></p>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Used At</label>
                    <p class="mb-0">2026-03-18 15:45 (25 minutes after generation)</p>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Router</label>
                    <p class="mb-0">Main Hotspot (192.168.88.1)</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete(document.getElementById('detailCode').textContent)">
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

// Generate vouchers (Mock)
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
    
    setTimeout(() => {
        const outputValue = outputOption ? outputOption.value : 'none';
        const outputLabel = outputOption ? outputOption.nextElementSibling.textContent.trim() : 'No Print';
        const batchLabel = form.querySelector('input[name="batch_label"]').value.trim();
        const voucherLabel = qty === 1 ? 'Voucher' : 'Vouchers';
        const confirmText = outputValue === 'none' ? 'Done' : 'Print Now';
        const showCancel = outputValue !== 'none';

        Swal.fire({
            icon: 'success',
            title: `${qty} ${voucherLabel} Generated!`,
            html: `
                <div class="text-start">
                    <p><strong>Package:</strong> ${packageSelect.options[packageSelect.selectedIndex].text}</p>
                    <p><strong>Validity:</strong> ${form.querySelector('input[name="validity_hours"]').value} hours</p>
                    <p><strong>Output:</strong> ${outputLabel}</p>
                    ${batchLabel ? `<p><strong>Batch:</strong> ${batchLabel}</p>` : ''}
                </div>
            `,
            showCancelButton: showCancel,
            confirmButtonText: confirmText,
            cancelButtonText: 'Close',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed && outputValue !== 'none') {
                printVouchers(outputValue);
            }
            location.reload();
        });
    }, 2000);
}

// View voucher details
function viewVoucherDetails(code) {
    document.getElementById('detailCode').textContent = code;
    new bootstrap.Modal(document.getElementById('voucherDetailsModal')).show();
}

// Confirm delete
function confirmDelete(code) {
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
            setTimeout(() => {
                Swal.fire('Deleted!', `Voucher ${code} has been deleted.`, 'success')
                    .then(() => location.reload());
            }, 1000);
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
            Swal.fire('Deleted!', 'Selected vouchers have been deleted.', 'success')
                .then(() => location.reload());
        }
    });
});

// Select all checkboxes
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.voucher-checkbox').forEach(cb => {
        cb.checked = this.checked;
    });
});

// Filter vouchers (Mock)
document.getElementById('statusFilter').addEventListener('change', filterVouchers);
document.getElementById('packageFilter').addEventListener('change', filterVouchers);

function filterVouchers() {
    const status = document.getElementById('statusFilter').value;
    const pkg = document.getElementById('packageFilter').value;
    
    Swal.fire({
        title: 'Filtering...',
        text: `Showing ${status} vouchers for ${pkg === 'all' ? 'all packages' : pkg}`,
        timer: 800,
        showConfirmButton: false
    });
}

// Search vouchers
function searchVouchers() {
    const query = document.getElementById('voucherSearch').value;
    if (!query) return;
    
    Swal.fire({
        title: 'Searching...',
        text: `Looking for "${query}"`,
        timer: 800,
        showConfirmButton: false
    });
}

// Print vouchers - List or Card format
function printVouchers(format = 'list') {
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
            <div class="voucher-card">
                <div class="package">1 Hour Pass</div>
                <div class="code">CB-WIFI-A1B2C3</div>
                <div class="validity">Valid: 24 hours from first use</div>
                <div class="footer">CloudBridge Networks • www.cloudbridge.network</div>
            </div>
            <div class="voucher-card">
                <div class="package">1 Hour Pass</div>
                <div class="code">CB-WIFI-D4E5F6</div>
                <div class="validity">Valid: 24 hours from first use</div>
                <div class="footer">CloudBridge Networks • www.cloudbridge.network</div>
            </div>
            <div class="voucher-card">
                <div class="package">24 Hours Pass</div>
                <div class="code">CB-WIFI-G7H8I9</div>
                <div class="validity">Valid: 24 hours from first use</div>
                <div class="footer">CloudBridge Networks • www.cloudbridge.network</div>
            </div>
            <div class="voucher-card">
                <div class="package">Weekly Pass</div>
                <div class="code">CB-WIFI-J0K1L2</div>
                <div class="validity">Valid: 7 days from first use</div>
                <div class="footer">CloudBridge Networks • www.cloudbridge.network</div>
            </div>
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
                    <tr>
                        <td>1</td>
                        <td><code>CB-WIFI-A1B2C3</code></td>
                        <td>1 Hour Pass</td>
                        <td>2026-03-19 10:30</td>
                        <td>Unused</td>
                        <td>2026-03-20 10:30</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td><code>CB-WIFI-D4E5F6</code></td>
                        <td>24 Hours Pass</td>
                        <td>2026-03-18 14:20</td>
                        <td>Used</td>
                        <td>2026-03-19 14:20</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td><code>CB-WIFI-G7H8I9</code></td>
                        <td>3 Hours Pass</td>
                        <td>2026-03-15 09:00</td>
                        <td>Expired</td>
                        <td>2026-03-16 09:00</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td><code>CB-WIFI-J0K1L2</code></td>
                        <td>Weekly Pass</td>
                        <td>2026-03-19 11:15</td>
                        <td>Unused</td>
                        <td>2026-03-26 11:15</td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td><code>CB-WIFI-M3N4O5</code></td>
                        <td>1 Hour Pass</td>
                        <td>2026-03-19 08:45</td>
                        <td>Used</td>
                        <td>2026-03-19 10:45</td>
                    </tr>
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

// Initialize DataTable
$(document).ready(function() {
    $('.data-table').DataTable({
        responsive: true,
        autoWidth: false,
        paging: true,
        searching: true,
        order: [[3, 'desc']]
    });
});

// Handle browser print completion
window.onafterprint = function() {
    closePrint();
};
</script>
@endpush
