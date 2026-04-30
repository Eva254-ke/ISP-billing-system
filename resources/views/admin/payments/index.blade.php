@extends('admin.layouts.app')

@section('page-title', 'Payments')

@section('content')
<div
    id="paymentsPage"
    data-payments-url="{{ route('admin.api.payments.index') }}"
    data-payment-show-base-url="{{ url('/admin/api/payments') }}"
    data-export-url="{{ route('admin.payments.export') }}"
    data-daily-revenue='@json(($dailyRevenue ?? collect())->values())'
>
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Payments</h2>
    <div>
        <button type="button" class="btn btn-outline-secondary me-2" onclick="exportPayments('csv')">
            <i class="fas fa-file-csv me-1"></i>Export CSV
        </button>
        <button type="button" class="btn btn-outline-primary" onclick="exportPayments('pdf')">
            <i class="fas fa-file-pdf me-1"></i>Export PDF
        </button>
    </div>
</div>

<div class="alert alert-light border d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <strong>Invoices live here now:</strong> open any successful payment and click <code>Invoice</code>.
        <div class="text-muted small">Tax, invoice prefix, numbering, and footer text come from Settings > Billing & Tax.</div>
    </div>
    <a href="{{ route('admin.settings.index') }}#tab-billing" class="btn btn-outline-dark btn-sm">
        <i class="fas fa-cog me-1"></i>Billing Settings
    </a>
</div>

<!-- Stats Row -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3 id="statsRevenueTotal">KES {{ number_format((float) ($stats['revenue_total'] ?? 0), 0) }}</h3>
                <p>Revenue Total</p>
            </div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
            <div class="small-box-footer">Filtered successful payments</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="statsRevenueToday">KES {{ number_format((float) ($stats['revenue_today'] ?? 0), 0) }}</h3>
                <p>Today</p>
            </div>
            <div class="icon"><i class="fas fa-chart-line"></i></div>
            <div class="small-box-footer">Today's successful payments</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="statsPending">{{ number_format((int) ($stats['pending'] ?? 0)) }}</h3>
                <p>Pending</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
            <div class="small-box-footer">Awaiting confirmation</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3 id="statsFailed">{{ number_format((int) ($stats['failed'] ?? 0)) }}</h3>
                <p>Failed</p>
            </div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
            <div class="small-box-footer">Needs review</div>
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
        <form id="filterForm" class="row g-3" onsubmit="event.preventDefault(); applyFilters();">
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
                    @foreach(($packages ?? collect()) as $package)
                        <option value="{{ $package->id }}">{{ $package->name }}</option>
                    @endforeach
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
                    <th class="action-col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($payments ?? []) as $payment)
                    @php
                        $status = strtolower((string) ($payment->status ?? 'unknown'));
                        $reference = $payment->mpesa_receipt_number ?: ($payment->mpesa_checkout_request_id ?: ($payment->reference ?: 'N/A'));
                        $statusBadge = match ($status) {
                            'completed', 'confirmed', 'activated' => 'bg-success',
                            'pending' => 'bg-warning text-dark',
                            'failed' => 'bg-danger',
                            default => 'bg-secondary',
                        };
                        $canOpenInvoice = in_array($status, ['completed', 'confirmed', 'activated'], true);
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="payment-checkbox" value="{{ $payment->id }}"></td>
                        <td>
                            <div><strong>{{ optional($payment->created_at)->format('Y-m-d') }}</strong></div>
                            <small class="text-muted">{{ optional($payment->created_at)->format('H:i:s') }}</small>
                        </td>
                        <td><code>{{ $payment->phone ?? '-' }}</code></td>
                        <td>{{ $payment->customer_name ?? '-' }}</td>
                        <td><span class="badge bg-secondary">{{ $payment->package_name ?? optional($payment->package)->name ?? '-' }}</span></td>
                        <td><strong>KES {{ number_format((float) ($payment->amount ?? 0), 0) }}</strong></td>
                        <td><code class="text-primary">{{ $reference }}</code></td>
                        <td><span class="badge {{ $statusBadge }}">{{ ucfirst($status) }}</span></td>
                        <td class="action-col">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewPaymentDetails({{ (int) $payment->id }})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if($canOpenInvoice)
                                    <a href="{{ route('admin.payments.invoice', $payment) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark" title="Open Invoice">
                                        <i class="fas fa-file-invoice me-1"></i><span class="d-none d-xl-inline">Invoice</span>
                                    </a>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-dark" title="Invoice is available after payment confirmation" disabled>
                                        <i class="fas fa-file-invoice me-1"></i><span class="d-none d-xl-inline">Invoice</span>
                                    </button>
                                @endif
                                <button type="button" class="btn btn-sm btn-outline-secondary" title="Resend SMS" onclick='resendReceipt(@json($payment->phone ?? ""), @json($reference))'>
                                    <i class="fas fa-sms"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center text-muted py-4">No payments available.</td>
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
            <button class="btn btn-sm btn-danger" id="bulkAction">
                <i class="fas fa-trash me-1"></i>Delete Selected
            </button>
            <button class="btn btn-sm btn-outline-primary ms-2" id="bulkExport">
                <i class="fas fa-download me-1"></i>Export Selected
            </button>
        </div>
        <div class="float-end">
            Showing {{ number_format((int) (($payments ?? collect())->count())) }} payments
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
        <div id="revenueChart" style="min-height: 320px;"></div>
    </div>
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
                        <p class="mb-0"><strong class="text-primary" id="detailRef">-</strong></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="badge bg-secondary fs-6" id="detailStatus">Unknown</span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted">Date & Time</label>
                        <p class="mb-0" id="detailDateTime">-</p>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">Amount</label>
                        <p class="mb-0"><strong id="detailAmount">KES 0</strong></p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted">Phone Number</label>
                        <p class="mb-0" id="detailPhone">-</p>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">Customer Name</label>
                        <p class="mb-0" id="detailCustomer">-</p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted">Package Purchased</label>
                    <p class="mb-0"><span class="badge bg-secondary" id="detailPackage">-</span></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted">M-Pesa Response</label>
                    <pre class="bg-light p-2 rounded small mb-0" id="detailResponse" style="max-height: 180px; overflow-y: auto;">{}</pre>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted">Router Assigned</label>
                    <p class="mb-0" id="detailRouter">-</p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted">Session Duration</label>
                    <p class="mb-0" id="detailSession">-</p>
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
@endpush
