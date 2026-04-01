@extends('admin.layouts.app')

@section('page-title', 'Payments')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Payment Reports</h2>
    <div>
        <button class="btn btn-outline-secondary me-2" onclick="exportPayments('csv')">
            <i class="fas fa-file-csv me-1"></i>Export CSV
        </button>
        <button class="btn btn-outline-primary" onclick="exportPayments('pdf')">
            <i class="fas fa-file-pdf me-1"></i>Export PDF
        </button>
    </div>
</div>

<!-- Stats Row -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>KES 45,200</h3>
                <p>Total Revenue</p>
            </div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
            <a href="#" class="small-box-footer">This Week <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>KES 12,500</h3>
                <p>Today</p>
            </div>
            <div class="icon"><i class="fas fa-chart-line"></i></div>
            <a href="#" class="small-box-footer">View Details <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>23</h3>
                <p>Pending</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
            <a href="#" class="small-box-footer">Review <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>8</h3>
                <p>Failed</p>
            </div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
            <a href="#" class="small-box-footer">Investigate <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter me-2"></i>
            Filter Payments
        </h3>
    </div>
    <div class="card-body">
        <form id="filterForm" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Date Range</label>
                <select class="form-select" id="dateRange">
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="week" selected>This Week</option>
                    <option value="month">This Month</option>
                    <option value="custom">Custom Range...</option>
                </select>
            </div>
            <div class="col-md-3 custom-date" style="display: none;">
                <label class="form-label">From</label>
                <input type="date" class="form-control" id="dateFrom">
            </div>
            <div class="col-md-3 custom-date" style="display: none;">
                <label class="form-label">To</label>
                <input type="date" class="form-control" id="dateTo">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" id="statusFilter">
                    <option value="all">All</option>
                    <option value="success">Success</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Package</label>
                <select class="form-select" id="packageFilter">
                    <option value="all">All Packages</option>
                    <option value="1hour">1 Hour</option>
                    <option value="3hours">3 Hours</option>
                    <option value="24hours">24 Hours</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-primary w-100" onclick="applyFilters()">
                    <i class="fas fa-search me-1"></i>Apply
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Payments Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Transaction History</h3>
        <div class="card-tools">
            <div class="input-group input-group-sm" style="width: 250px;">
                <input type="text" class="form-control" id="paymentSearch" placeholder="Search by phone, ref...">
                <button type="button" class="btn btn-outline-secondary" onclick="searchPayments()">
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
                    <th>Date & Time</th>
                    <th>Phone Number</th>
                    <th>Customer</th>
                    <th>Package</th>
                    <th>Amount (KES)</th>
                    <th>M-Pesa Ref</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Payment 1: Success -->
                <tr>
                    <td><input type="checkbox" class="payment-checkbox" value="1"></td>
                    <td>
                        <div><strong>2026-03-19</strong></div>
                        <small class="text-muted">10:45:23</small>
                    </td>
                    <td><code>0712***678</code></td>
                    <td>John M.</td>
                    <td><span class="badge bg-secondary">1 Hour Pass</span></td>
                    <td><strong>KES 50</strong></td>
                    <td><code class="text-primary">QKH123ABC</code></td>
                    <td><span class="badge bg-success">Success</span></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewPaymentDetails('QKH123ABC')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" title="Resend SMS" onclick="resendReceipt('0712345678', 'QKH123ABC')">
                                <i class="fas fa-sms"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Payment 2: Success -->
                <tr>
                    <td><input type="checkbox" class="payment-checkbox" value="2"></td>
                    <td>
                        <div><strong>2026-03-19</strong></div>
                        <small class="text-muted">10:32:15</small>
                    </td>
                    <td><code>0723***789</code></td>
                    <td>Jane K.</td>
                    <td><span class="badge bg-primary">24 Hours Pass</span></td>
                    <td><strong>KES 400</strong></td>
                    <td><code class="text-primary">QKH456DEF</code></td>
                    <td><span class="badge bg-success">Success</span></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewPaymentDetails('QKH456DEF')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" title="Resend SMS" onclick="resendReceipt('0723456789', 'QKH456DEF')">
                                <i class="fas fa-sms"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Payment 3: Pending -->
                <tr class="table-warning">
                    <td><input type="checkbox" class="payment-checkbox" value="3"></td>
                    <td>
                        <div><strong>2026-03-19</strong></div>
                        <small class="text-muted">10:15:42</small>
                    </td>
                    <td><code>0734***890</code></td>
                    <td>Peter O.</td>
                    <td><span class="badge bg-secondary">3 Hours Pass</span></td>
                    <td><strong>KES 100</strong></td>
                    <td><code class="text-warning">PENDING</code></td>
                    <td><span class="badge bg-warning text-dark">Pending</span></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewPaymentDetails('PENDING-789')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-info" title="Check Status" onclick="checkPaymentStatus('PENDING-789')">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Payment 4: Failed -->
                <tr class="table-danger">
                    <td><input type="checkbox" class="payment-checkbox" value="4"></td>
                    <td>
                        <div><strong>2026-03-19</strong></div>
                        <small class="text-muted">09:58:11</small>
                    </td>
                    <td><code>0745***901</code></td>
                    <td>Mary W.</td>
                    <td><span class="badge bg-secondary">1 Hour Pass</span></td>
                    <td><strong>KES 50</strong></td>
                    <td><code class="text-danger">FAILED</code></td>
                    <td><span class="badge bg-danger">Failed</span></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewPaymentDetails('FAILED-901')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Retry Payment" onclick="retryPayment('0745901234', 'FAILED-901')">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Payment 5: Success -->
                <tr>
                    <td><input type="checkbox" class="payment-checkbox" value="5"></td>
                    <td>
                        <div><strong>2026-03-19</strong></div>
                        <small class="text-muted">09:45:33</small>
                    </td>
                    <td><code>0756***012</code></td>
                    <td>David L.</td>
                    <td><span class="badge bg-success">Weekly Pass</span></td>
                    <td><strong>KES 2,000</strong></td>
                    <td><code class="text-primary">QKH789GHI</code></td>
                    <td><span class="badge bg-success">Success</span></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewPaymentDetails('QKH789GHI')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" title="Resend SMS" onclick="resendReceipt('0756012345', 'QKH789GHI')">
                                <i class="fas fa-sms"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Payment 6: Success -->
                <tr>
                    <td><input type="checkbox" class="payment-checkbox" value="6"></td>
                    <td>
                        <div><strong>2026-03-18</strong></div>
                        <small class="text-muted">18:22:45</small>
                    </td>
                    <td><code>0767***123</code></td>
                    <td>Sarah N.</td>
                    <td><span class="badge bg-info">Monthly Pass</span></td>
                    <td><strong>KES 5,000</strong></td>
                    <td><code class="text-primary">QKH012JKL</code></td>
                    <td><span class="badge bg-success">Success</span></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewPaymentDetails('QKH012JKL')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" title="Resend SMS" onclick="resendReceipt('0767123456', 'QKH012JKL')">
                                <i class="fas fa-sms"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer clearfix">
        <div class="float-start">
            <button class="btn btn-sm btn-danger" id="bulkAction">
                <i class="fas fa-trash me-1"></i>Delete Selected
            </button>
            <button class="btn btn-sm btn-outline-primary ms-2" id="bulkExport">
                <i class="fas fa-download me-1"></i>Export Selected
            </button>
        </div>
        <div class="float-end">
            Showing 1-6 of 412 payments
        </div>
    </div>
</div>

<!-- Revenue Chart Card -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-chart-bar me-2"></i>
            Revenue Overview
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div id="revenueChart" style="min-height: 300px;"></div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Payment Details Modal -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted">M-Pesa Reference</label>
                        <p class="mb-0"><strong class="text-primary" id="detailRef">QKH123ABC</strong></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="badge bg-success fs-6" id="detailStatus">Success</span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted">Date & Time</label>
                        <p class="mb-0" id="detailDateTime">2026-03-19 10:45:23</p>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">Amount</label>
                        <p class="mb-0"><strong id="detailAmount">KES 50</strong></p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted">Phone Number</label>
                        <p class="mb-0" id="detailPhone">0712***678</p>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">Customer Name</label>
                        <p class="mb-0" id="detailCustomer">John M.</p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted">Package Purchased</label>
                    <p class="mb-0"><span class="badge bg-secondary" id="detailPackage">1 Hour Pass</span></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted">M-Pesa Response</label>
                    <pre class="bg-light p-2 rounded small" id="detailResponse" style="max-height: 100px; overflow-y: auto;">
{
  "MerchantRequestID": "12345-67890",
  "CheckoutRequestID": "ws_CO_190320261045231234",
  "ResponseCode": "0",
  "ResponseDescription": "Success",
  "CustomerMessage": "Success"
}</pre>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted">Router Assigned</label>
                    <p class="mb-0">Main Hotspot (192.168.88.1)</p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted">Session Duration</label>
                    <p class="mb-0">60 minutes (expires: 2026-03-19 11:45:23)</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-secondary" onclick="copyRef()">
                    <i class="fas fa-copy me-1"></i>Copy Ref
                </button>
                <button type="button" class="btn btn-primary" onclick="resendReceiptFromModal()">
                    <i class="fas fa-sms me-1"></i>Resend Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style media="print">
    @page { size: A4; margin: 15mm; }
    body * { visibility: hidden; }
    #printArea, #printArea * { visibility: visible; }
    #printArea {
        position: absolute;
        left: 0; top: 0;
        width: 100%; padding: 20px;
    }
    .no-print { display: none !important; }
    .print-table {
        width: 100%; border-collapse: collapse;
        font-size: 9pt; font-family: Arial, sans-serif;
    }
    .print-table th, .print-table td {
        border: 1px solid #ddd; padding: 6px; text-align: left;
    }
    .print-table th { background: #f8f9fa; font-weight: 600; }
    .print-header {
        text-align: center; margin-bottom: 20px;
        border-bottom: 2px solid #1E40AF; padding-bottom: 10px;
    }
    .print-header h2 { margin: 0; color: #1E40AF; }
    .print-header p { margin: 5px 0 0; color: #666; font-size: 0.9rem; }
    .print-footer {
        text-align: center; margin-top: 30px;
        font-size: 0.8rem; color: #999;
    }
</style>

<!-- Hidden Print Area -->
<div id="printArea" style="display: none;"></div>

<script>
// Date range toggle
document.getElementById('dateRange').addEventListener('change', function() {
    const customDates = document.querySelectorAll('.custom-date');
    customDates.forEach(el => {
        el.style.display = this.value === 'custom' ? 'block' : 'none';
    });
});

// Apply filters (Mock)
function applyFilters() {
    const dateRange = document.getElementById('dateRange').value;
    const status = document.getElementById('statusFilter').value;
    const pkg = document.getElementById('packageFilter').value;
    
    Swal.fire({
        title: 'Filtering...',
        text: `Applying filters: ${dateRange}, ${status}, ${pkg}`,
        timer: 800,
        showConfirmButton: false
    });
    // In production: AJAX call to filter data
}

// Search payments
function searchPayments() {
    const query = document.getElementById('paymentSearch').value;
    if (!query) return;
    
    Swal.fire({
        title: 'Searching...',
        text: `Looking for "${query}"`,
        timer: 800,
        showConfirmButton: false
    });
}

// View payment details
function viewPaymentDetails(ref) {
    document.getElementById('detailRef').textContent = ref;
    
    // Mock data based on ref
    if (ref.includes('QKH123')) {
        document.getElementById('detailStatus').className = 'badge bg-success fs-6';
        document.getElementById('detailStatus').textContent = 'Success';
        document.getElementById('detailDateTime').textContent = '2026-03-19 10:45:23';
        document.getElementById('detailAmount').textContent = 'KES 50';
        document.getElementById('detailPhone').textContent = '0712***678';
        document.getElementById('detailCustomer').textContent = 'John M.';
        document.getElementById('detailPackage').className = 'badge bg-secondary';
        document.getElementById('detailPackage').textContent = '1 Hour Pass';
    }
    
    if (window.CBModal && window.CBModal.showById) {
        window.CBModal.showById('paymentDetailsModal');
    } else if (window.bootstrap && window.bootstrap.Modal) {
        new bootstrap.Modal(document.getElementById('paymentDetailsModal')).show();
    } else if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
        window.jQuery('#paymentDetailsModal').modal('show');
    }
}

// Copy reference
function copyRef() {
    const ref = document.getElementById('detailRef').textContent;
    navigator.clipboard.writeText(ref).then(() => {
        Swal.fire({ icon: 'success', title: 'Copied!', text: 'Reference copied', timer: 1500, showConfirmButton: false });
    });
}

// Resend receipt
function resendReceipt(phone, ref) {
    Swal.fire({
        title: 'Resend Receipt?',
        text: `Send payment receipt to ${phone} for ${ref}?`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Yes, Send!'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Sending...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            setTimeout(() => {
                Swal.fire('Sent!', 'Receipt SMS has been sent.', 'success');
            }, 1500);
        }
    });
}

function resendReceiptFromModal() {
    const phone = document.getElementById('detailPhone').textContent;
    const ref = document.getElementById('detailRef').textContent;
    resendReceipt(phone, ref);
}

// Check pending payment status
function checkPaymentStatus(ref) {
    Swal.fire({
        title: 'Checking Status...',
        text: `Querying M-Pesa for ${ref}`,
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    setTimeout(() => {
        // Mock: randomly resolve as success or still pending
        const resolved = Math.random() > 0.3;
        if (resolved) {
            Swal.fire({
                icon: 'success',
                title: 'Payment Confirmed!',
                text: 'M-Pesa callback received. Payment successful.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire('Still Pending', 'Payment is still being processed by M-Pesa.', 'info');
        }
    }, 2000);
}

// Retry failed payment
function retryPayment(phone, ref) {
    Swal.fire({
        title: 'Retry Payment?',
        text: `Send new STK Push to ${phone}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Retry!'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Sending STK Push...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            setTimeout(() => {
                Swal.fire('STK Push Sent!', 'Customer will receive prompt on their phone.', 'success');
            }, 2000);
        }
    });
}

// Export payments
function exportPayments(format) {
    Swal.fire({
        title: `Exporting ${format.toUpperCase()}...`,
        text: 'Preparing file download',
        timer: 1200,
        showConfirmButton: false
    }).then(() => {
        if (format === 'csv') {
            // Mock CSV
            const csv = 'Date,Time,Phone,Package,Amount,Status,MpesaRef\n' +
                '2026-03-19,10:45:23,0712***678,1 Hour Pass,50,Success,QKH123ABC\n' +
                '2026-03-19,10:32:15,0723***789,24 Hours Pass,400,Success,QKH456DEF\n' +
                '2026-03-19,10:15:42,0734***890,3 Hours Pass,100,Pending,PENDING-789';
            downloadFile(csv, 'payments.csv', 'text/csv');
        } else {
            // Mock PDF trigger
            window.open('/admin/payments/export/pdf', '_blank');
        }
        
        Swal.fire({
            icon: 'success',
            title: 'Export Complete!',
            text: `payments.${format} downloaded`,
            timer: 2000,
            showConfirmButton: false
        });
    });
}

// Helper: Download file
function downloadFile(content, filename, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Bulk actions
document.getElementById('bulkAction').addEventListener('click', function() {
    const selected = document.querySelectorAll('.payment-checkbox:checked');
    if (selected.length === 0) {
        Swal.fire('Info', 'No payments selected', 'info');
        return;
    }
    Swal.fire({
        title: 'Delete Selected?',
        text: `Delete ${selected.length} payment record(s)? This cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete!',
        confirmButtonColor: '#EF4444'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Deleted!', 'Selected records have been deleted.', 'success')
                .then(() => location.reload());
        }
    });
});

document.getElementById('bulkExport').addEventListener('click', function() {
    const selected = document.querySelectorAll('.payment-checkbox:checked');
    if (selected.length === 0) {
        Swal.fire('Info', 'No payments selected', 'info');
        return;
    }
    exportPayments('csv');
});

// Select all
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.payment-checkbox').forEach(cb => {
        cb.checked = this.checked;
    });
});

// Revenue Chart
document.addEventListener('DOMContentLoaded', function() {
    var options = {
        chart: {
            type: 'bar',
            height: 300,
            toolbar: { show: false },
            stacked: false
        },
        series: [{
            name: 'Revenue (KES)',
            data: [2500, 4200, 3100, 5800, 4500, 6200, 12500]
        }],
        colors: ['#2563EB'],
        xaxis: {
            categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            labels: { style: { colors: '#64748b', fontSize: '11px' } }
        },
        yaxis: {
            labels: {
                formatter: function(val) { return 'KES ' + val.toLocaleString(); },
                style: { colors: '#64748b', fontSize: '11px' }
            }
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                columnWidth: '60%'
            }
        },
        grid: { borderColor: '#e2e8f0', strokeDashArray: 3 },
        tooltip: {
            theme: 'light',
            y: { formatter: val => 'KES ' + val.toLocaleString() }
        }
    };
    new ApexCharts(document.querySelector("#revenueChart"), options).render();
});

// Initialize DataTable
$(document).ready(function() {
    $('.data-table').DataTable({
        responsive: true,
        autoWidth: false,
        paging: true,
        searching: true,
        order: [[0, 'desc']]
    });
});
</script>
@endpush
