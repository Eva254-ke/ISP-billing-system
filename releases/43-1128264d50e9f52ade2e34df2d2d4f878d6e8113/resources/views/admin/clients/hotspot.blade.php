@extends('admin.layouts.app')



@section('page-title', 'Hotspot Clients')



@section('content')

<!-- Page Header -->

<div class="d-flex justify-content-between align-items-center mb-4">

    <h2>

        <i class="fas fa-wifi me-2"></i>

        Hotspot Clients (Live)

        <span class="badge bg-success ms-2" id="liveIndicator">● Live</span>

    </h2>

    <div>

        <button class="btn btn-outline-primary me-2" onclick="refreshClients()">

            <i class="fas fa-sync-alt me-1"></i>Refresh

        </button>

        <button class="btn btn-outline-danger" onclick="bulkDisconnect()">

            <i class="fas fa-plug me-1"></i>Disconnect Selected

        </button>

    </div>

</div>



<!-- Stats Row -->

<div class="row mb-4">

    <div class="col-md-3">

        <div class="small-box bg-primary">

            <div class="inner">

                <h3>{{ number_format((int) ($stats['active_sessions'] ?? 0)) }}</h3>

                <p>Active Sessions</p>

            </div>

            <div class="icon"><i class="fas fa-users"></i></div>

            <a href="#" class="small-box-footer">View All <i class="fas fa-arrow-circle-right"></i></a>

        </div>

    </div>

    <div class="col-md-3">

        <div class="small-box bg-success">

            <div class="inner">

                <h3>{{ number_format((float) ($stats['total_bandwidth'] ?? 0), 2) }} GB</h3>

                <p>Total Bandwidth</p>

            </div>

            <div class="icon"><i class="fas fa-tachometer-alt"></i></div>

            <a href="#" class="small-box-footer">Details <i class="fas fa-arrow-circle-right"></i></a>

        </div>

    </div>

    <div class="col-md-3">

        <div class="small-box bg-warning">

            <div class="inner">

                <h3>{{ number_format((int) ($stats['new_last_hour'] ?? 0)) }}</h3>

                <p>New (Last Hour)</p>

            </div>

            <div class="icon"><i class="fas fa-clock"></i></div>

            <a href="#" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>

        </div>

    </div>

    <div class="col-md-3">

        <div class="small-box bg-info">

            <div class="inner">

                <h3>{{ number_format((int) ($stats['routers_online'] ?? 0)) }}</h3>

                <p>Routers Online</p>

            </div>

            <div class="icon"><i class="fas fa-server"></i></div>

            <a href="#" class="small-box-footer">Manage <i class="fas fa-arrow-circle-right"></i></a>

        </div>

    </div>

</div>



<!-- Filters Card -->

<div class="card mb-4">

    <div class="card-header">

        <h3 class="card-title">

            <i class="fas fa-filter me-2"></i>

            Filter Clients

        </h3>

    </div>

    <div class="card-body">

        <form class="row g-3">

            <div class="col-md-3">

                <label class="form-label">Router</label>

                <select class="form-select" id="routerFilter">

                    <option value="all">All Routers</option>

                </select>

            </div>

            <div class="col-md-3">

                <label class="form-label">Status</label>

                <select class="form-select" id="statusFilter">

                    <option value="all">All</option>
                    <option value="active">Active</option>
                    <option value="idle">Idle</option>
                    <option value="blocked">Blocked</option>

                </select>

            </div>

            <div class="col-md-3">

                <label class="form-label">Package</label>

                <select class="form-select" id="packageFilter">

                    <option value="all">All Packages</option>

                </select>

            </div>

            <div class="col-md-3">

                <label class="form-label">Search</label>

                <div class="input-group">

                    <input type="text" class="form-control" id="clientSearch" placeholder="Username, MAC, IP...">

                    <button class="btn btn-outline-secondary" type="button" onclick="searchClients()">

                        <i class="fas fa-search"></i>

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>



<!-- Clients Table -->

<div class="card">

    <div class="card-header">

        <h3 class="card-title">Active Sessions</h3>

        <div class="card-tools">

            <span class="badge bg-info">Auto-refresh: 30s</span>

        </div>

    </div>

    <div class="card-body table-responsive p-0">

        <table class="table table-hover table-striped data-table">

            <thead>

                <tr>

                    <th><input type="checkbox" id="selectAll"></th>

                    <th>Username</th>

                    <th>MAC Address</th>

                    <th>IP Address</th>

                    <th>Router</th>

                    <th>Package</th>
                    <th>Expiry</th>
                    <th>Session</th>
                    <th>Data Usage</th>
                    <th>Status</th>

                    <th class="action-col">Actions</th>
                </tr>

            </thead>

            <tbody>
                @forelse(($sessions ?? collect()) as $session)
                    @php
                        $username = $session->username ?: ($session->phone ?: 'guest');
                        $bytesTotal = (int) ($session->bytes_total ?? 0);
                        $dataMb = $bytesTotal / 1024 / 1024;
                        $displayUsage = $dataMb >= 1024
                            ? number_format($dataMb / 1024, 2) . ' GB'
                            : number_format($dataMb, 0) . ' MB';
                        $status = strtolower((string) ($session->status ?? 'unknown'));
                        $statusClass = match ($status) {
                            'active' => 'bg-success',
                            'pending' => 'bg-warning text-dark',
                            'terminated' => 'bg-danger',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="client-checkbox" value="{{ $session->id }}"></td>
                        <td>
                            <strong>{{ $username }}</strong>
                            <div class="text-muted small">{{ $session->phone ?? 'N/A' }}</div>
                        </td>
                        <td><code class="bg-light px-1">{{ $session->mac_address ?? '-' }}</code></td>
                        <td><code>{{ $session->ip_address ?? '-' }}</code></td>
                        <td>{{ optional($session->router)->name ?? '-' }}</td>
                        <td><span class="badge bg-secondary">{{ optional($session->package)->name ?? '-' }}</span></td>
                        <td>
                            <div class="fw-semibold">{{ optional($session->expires_at)->format('Y-m-d H:i') ?? '-' }}</div>
                            <span class="badge bg-warning text-dark">{{ optional($session->expires_at)->diffForHumans() ?? '-' }}</span>
                        </td>
                        <td>
                            <div class="fw-semibold text-success">{{ optional($session->started_at)->diffForHumans(null, true) ?? '-' }}</div>
                            <div class="text-muted small">Last online: {{ optional($session->last_activity_at ?? $session->started_at)->diffForHumans() ?? '-' }}</div>
                        </td>
                        <td>
                            <div class="progress" style="height: 6px; width: 100px;">
                                <div class="progress-bar bg-success" style="width: {{ min(100, (int) ($session->progress_percentage ?? 0)) }}%"></div>
                            </div>
                            <small>{{ $displayUsage }}</small>
                        </td>
                        <td><span class="badge {{ $statusClass }}">{{ ucfirst($status) }}</span></td>
                        <td class="action-col">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewClientDetails('{{ $username }}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-warning" title="Limit Speed" onclick="limitSpeed('{{ $username }}')">
                                    <i class="fas fa-tachometer-alt"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" title="Disconnect" onclick="confirmDisconnect('{{ $username }}', '{{ $session->mac_address ?? '' }}')">
                                    <i class="fas fa-plug"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center text-muted py-4">No hotspot sessions found</td>
                        <td></td>
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

    <div class="card-footer clearfix">

        <div class="float-start">
            <small class="text-muted">Last updated: <span id="lastUpdate">Just now</span></small>
        </div>
        <div class="float-end">
            Showing {{ number_format((int) (($sessions ?? collect())->count())) }} active clients
        </div>
    </div>

</div>



<!-- Bandwidth Chart -->

<div class="card mt-4">

    <div class="card-header">

        <h3 class="card-title">

            <i class="fas fa-chart-area me-2"></i>

            Bandwidth Usage (Last Hour)

        </h3>

    </div>

    <div class="card-body">

        <div id="bandwidthChart" style="min-height: 250px;"></div>

    </div>

</div>

@endsection



@push('scripts')

<!-- Client Details Modal -->

<div class="modal fade" id="clientDetailsModal" tabindex="-1">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">Client Details</h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="row mb-4">

                    <div class="col-md-6">

                        <label class="form-label text-muted">Username</label>

                        <p class="mb-0"><strong id="detailUsername">user001</strong></p>

                    </div>

                    <div class="col-md-6 text-md-end">

                        <span class="badge bg-success fs-6" id="detailStatus">Active</span>

                    </div>

                </div>

                

                <div class="row mb-3">

                    <div class="col-6">

                        <label class="form-label text-muted">MAC Address</label>

                        <p class="mb-0"><code id="detailMac">AA:BB:CC:11:22:33</code></p>

                    </div>

                    <div class="col-6">

                        <label class="form-label text-muted">IP Address</label>

                        <p class="mb-0"><code id="detailIp">10.5.50.1</code></p>

                    </div>

                </div>

                

                <div class="row mb-3">

                    <div class="col-6">

                        <label class="form-label text-muted">Router</label>

                        <p class="mb-0" id="detailRouter">Main Hotspot (192.168.88.1)</p>

                    </div>

                    <div class="col-6">

                        <label class="form-label text-muted">Package</label>

                        <p class="mb-0"><span class="badge bg-secondary" id="detailPackage">1 Hour Pass</span></p>

                    </div>

                </div>

                

                <div class="mb-3">
                    <label class="form-label text-muted">Session Time</label>
                    <p class="mb-0"><strong id="detailUptime">00:45:23</strong> (Connected: 2026-03-19 10:00:00)</p>
                </div>

                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted">Expiry</label>
                        <p class="mb-0" id="detailExpiry">2026-03-19 11:00</p>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">Last Online</label>
                        <p class="mb-0" id="detailLastOnline">Just now</p>
                    </div>
                </div>
                

                <div class="mb-3">

                    <label class="form-label text-muted">Data Usage</label>

                    <div class="row">

                        <div class="col-6">

                            <small>Download:</small>

                            <p class="mb-1"><strong id="detailDownload">180 MB</strong></p>

                        </div>

                        <div class="col-6">

                            <small>Upload:</small>

                            <p class="mb-1"><strong id="detailUpload">70 MB</strong></p>

                        </div>

                    </div>

                    <div class="progress" style="height: 8px;">

                        <div class="progress-bar bg-primary" id="detailProgress" style="width: 35%"></div>

                    </div>

                    <small class="text-muted">250 MB used of 1 GB limit</small>

                </div>

                

                <div class="mb-3">

                    <label class="form-label text-muted">Device Info</label>

                    <p class="mb-0">

                        <i class="fas fa-mobile-alt me-1"></i>

                        <span id="detailDevice">Samsung Galaxy S21 (Android 13)</span>

                    </p>

                </div>

                

                <div class="mb-3">

                    <label class="form-label text-muted">Last Activity</label>

                    <p class="mb-0" id="detailLastActivity">2 minutes ago - HTTPS request to google.com</p>

                </div>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-warning" onclick="limitSpeedFromModal()">

                    <i class="fas fa-tachometer-alt me-1"></i>Limit Speed

                </button>

                <button type="button" class="btn btn-danger" onclick="disconnectFromModal()">

                    <i class="fas fa-plug me-1"></i>Disconnect

                </button>

            </div>

        </div>

    </div>

</div>



<script>

// Refresh clients (Mock)

function refreshClients() {

    Swal.fire({

        title: 'Refreshing...',

        text: 'Fetching latest client data from routers',

        allowOutsideClick: false,

        didOpen: () => { Swal.showLoading(); }

    });

    

    setTimeout(() => {

        document.getElementById('lastUpdate').textContent = 'Just now';

        Swal.fire({

            icon: 'success',

            title: 'Updated!',

            text: 'Client list refreshed successfully',

            timer: 1500,

            showConfirmButton: false

        });

    }, 1200);

}



// Search clients

function searchClients() {

    const query = document.getElementById('clientSearch').value;

    if (!query) return;

    

    Swal.fire({

        title: 'Searching...',

        text: `Looking for "${query}"`,

        timer: 800,

        showConfirmButton: false

    });

}



// View client details

function viewClientDetails(username) {

    document.getElementById('detailUsername').textContent = username;

    const rows = Array.from(document.querySelectorAll('.data-table tbody tr'));
    const targetRow = rows.find((row) => {
        const rowUsername = row.cells?.[1]?.querySelector('strong')?.textContent?.trim();
        return rowUsername === username;
    });

    if (targetRow && targetRow.cells.length >= 10) {
        const mac = targetRow.cells[2].textContent.trim();
        const ip = targetRow.cells[3].textContent.trim();
        const router = targetRow.cells[4].textContent.trim();
        const packageName = targetRow.cells[5].textContent.trim();
        const expiry = targetRow.cells[6].querySelector('.fw-semibold')?.textContent?.trim() || targetRow.cells[6].textContent.trim();
        const uptime = targetRow.cells[7].querySelector('.fw-semibold')?.textContent?.trim() || '-';
        const lastOnlineText = targetRow.cells[7].querySelector('.text-muted')?.textContent?.replace('Last online:', '').trim() || '-';
        const usageText = targetRow.cells[8].querySelector('small')?.textContent?.trim() || '-';
        const progressWidth = targetRow.cells[8].querySelector('.progress-bar')?.style?.width || '0%';
        const status = targetRow.cells[9].textContent.trim();

        document.getElementById('detailStatus').className = `badge ${status.toLowerCase() === 'active' ? 'bg-success' : 'bg-secondary'} fs-6`;
        document.getElementById('detailStatus').textContent = status || 'Unknown';
        document.getElementById('detailMac').textContent = mac || '-';
        document.getElementById('detailIp').textContent = ip || '-';
        document.getElementById('detailRouter').textContent = router || '-';
        document.getElementById('detailPackage').className = 'badge bg-secondary';
        document.getElementById('detailPackage').textContent = packageName || '-';
        document.getElementById('detailUptime').textContent = uptime || '-';
        document.getElementById('detailExpiry').textContent = expiry || '-';
        document.getElementById('detailLastOnline').textContent = lastOnlineText || '-';
        document.getElementById('detailDownload').textContent = usageText;
        document.getElementById('detailUpload').textContent = '-';
        document.getElementById('detailProgress').style.width = progressWidth;
        document.getElementById('detailDevice').textContent = '-';
        document.getElementById('detailLastActivity').textContent = `${lastOnlineText || '-'} - session activity`;        
    }

    

    if (window.CBModal && window.CBModal.showById) {
        window.CBModal.showById('clientDetailsModal');
    } else if (window.bootstrap && window.bootstrap.Modal) {
        new bootstrap.Modal(document.getElementById('clientDetailsModal')).show();
    } else if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
        window.jQuery('#clientDetailsModal').modal('show');
    }
}



// Confirm disconnect

function confirmDisconnect(username, mac) {

    Swal.fire({

        title: 'Disconnect Client?',

        html: `

            <p>Are you sure you want to disconnect:</p>

            <p class="mb-0"><strong>${username}</strong></p>

            <p class="text-muted small">MAC: ${mac}</p>

        `,

        icon: 'warning',

        showCancelButton: true,

        confirmButtonText: 'Yes, Disconnect!',

        confirmButtonColor: '#EF4444',

        cancelButtonText: 'Cancel'

    }).then((result) => {

        if (result.isConfirmed) {

            Swal.fire({

                title: 'Disconnecting...',

                allowOutsideClick: false,

                didOpen: () => { Swal.showLoading(); }

            });

            setTimeout(() => {

                Swal.fire('Disconnected!', `Client ${username} has been disconnected.`, 'success')

                    .then(() => location.reload());

            }, 1500);

        }

    });

}



// Disconnect from modal

function disconnectFromModal() {

    const username = document.getElementById('detailUsername').textContent;

    const mac = document.getElementById('detailMac').textContent;

    confirmDisconnect(username, mac);

}



// Limit speed

function limitSpeed(username) {

    Swal.fire({

        title: 'Limit Speed for ' + username,

        html: `

            <div class="text-start">

                <label class="form-label">Download Limit (Mbps)</label>

                <input type="number" class="form-control mb-2" id="limitDownload" value="2" min="0.1" step="0.1">

                <label class="form-label">Upload Limit (Mbps)</label>

                <input type="number" class="form-control" id="limitUpload" value="1" min="0.1" step="0.1">

            </div>

        `,

        showCancelButton: true,

        confirmButtonText: 'Apply Limit',

        preConfirm: () => {

            return {

                download: document.getElementById('limitDownload').value,

                upload: document.getElementById('limitUpload').value

            };

        }

    }).then((result) => {

        if (result.isConfirmed) {

            Swal.fire({

                title: 'Applying...',

                allowOutsideClick: false,

                didOpen: () => { Swal.showLoading(); }

            });

            setTimeout(() => {

                Swal.fire('Limit Applied!', `Speed limited to ${result.value.download}↓ / ${result.value.upload}↑ Mbps`, 'success');

            }, 1500);

        }

    });

}



function limitSpeedFromModal() {

    const username = document.getElementById('detailUsername').textContent;

    limitSpeed(username);

}



// Send message to client (MikroTik Walled Garden message)

function sendClientMessage(username) {

    Swal.fire({

        title: 'Send Message to ' + username,

        html: `

            <textarea class="form-control" id="clientMessage" rows="3" placeholder="Enter message..."></textarea>

            <small class="text-muted">Message will appear in client's browser</small>

        `,

        showCancelButton: true,

        confirmButtonText: 'Send',

        preConfirm: () => {

            return document.getElementById('clientMessage').value;

        }

    }).then((result) => {

        if (result.isConfirmed && result.value) {

            Swal.fire({

                title: 'Sending...',

                allowOutsideClick: false,

                didOpen: () => { Swal.showLoading(); }

            });

            setTimeout(() => {

                Swal.fire('Sent!', 'Message delivered to client', 'success');

            }, 1200);

        }

    });

}



// Bulk disconnect

function bulkDisconnect() {

    const selected = document.querySelectorAll('.client-checkbox:checked');

    if (selected.length === 0) {

        Swal.fire('Info', 'No clients selected', 'info');

        return;

    }

    Swal.fire({

        title: 'Disconnect Selected?',

        text: `Disconnect ${selected.length} client(s)? This cannot be undone.`,

        icon: 'warning',

        showCancelButton: true,

        confirmButtonText: 'Yes, Disconnect All!',

        confirmButtonColor: '#EF4444'

    }).then((result) => {

        if (result.isConfirmed) {

            Swal.fire({

                title: 'Disconnecting...',

                allowOutsideClick: false,

                didOpen: () => { Swal.showLoading(); }

            });

            setTimeout(() => {

                Swal.fire('Disconnected!', `${selected.length} clients have been disconnected.`, 'success')

                    .then(() => location.reload());

            }, 2000);

        }

    });

}



// Select all checkboxes

document.getElementById('selectAll').addEventListener('change', function() {

    document.querySelectorAll('.client-checkbox').forEach(cb => {

        cb.checked = this.checked;

    });

});



// Auto-refresh indicator animation

setInterval(() => {

    const indicator = document.getElementById('liveIndicator');

    indicator.style.opacity = indicator.style.opacity === '0.5' ? '1' : '0.5';

}, 1000);



// Auto-refresh clients every 30 seconds (Mock)

setInterval(() => {

    // In production: AJAX call to fetch live data

    document.getElementById('lastUpdate').textContent = 'Just now';

}, 30000);



// Bandwidth Chart

document.addEventListener('DOMContentLoaded', function() {

    var options = {

        chart: {

            type: 'area',

            height: 250,

            toolbar: { show: false },

            animations: { enabled: true }

        },

        series: [

            { name: 'Download', data: [120, 180, 250, 320, 280, 450, 380, 520, 480, 620, 550, 720] },

            { name: 'Upload', data: [40, 60, 80, 120, 100, 150, 130, 180, 160, 220, 190, 250] }

        ],

        colors: ['#2563EB', '#06B6D4'],

        xaxis: {

            categories: ['00:00', '00:05', '00:10', '00:15', '00:20', '00:25', '00:30', '00:35', '00:40', '00:45', '00:50', '00:55'],

            labels: { style: { colors: '#64748b', fontSize: '10px' } }

        },

        yaxis: {

            labels: {

                formatter: function(val) { return val + ' MB/s'; },

                style: { colors: '#64748b', fontSize: '10px' }

            }

        },

        fill: {

            type: 'gradient',

            gradient: {

                shadeIntensity: 1,

                opacityFrom: 0.5,

                opacityTo: 0.2,

                stops: [0, 100]

            }

        },

        grid: { borderColor: '#e2e8f0', strokeDashArray: 3 },

        tooltip: {

            theme: 'light',

            y: { formatter: val => val + ' MB/s' }

        },

        legend: { position: 'top', horizontalAlign: 'right' }

    };

    new ApexCharts(document.querySelector("#bandwidthChart"), options).render();

});



// Initialize DataTable

$(document).ready(function() {
    const tableEl = $('.data-table');
    if ($.fn.DataTable.isDataTable(tableEl)) {
        tableEl.DataTable().destroy();
    }
    tableEl.DataTable({
        responsive: false,
        autoWidth: false,
        paging: true,
        searching: true,
        order: [[7, 'desc']], // Sort by session
        columnDefs: [
            { targets: [0, -1], orderable: false, searchable: false }
        ]
    });
});
</script>

@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statsBoxes = document.querySelectorAll('.row.mb-4 .small-box .inner h3');
    const tbody = document.querySelector('.data-table tbody');
    const tableEl = $('.data-table');
    const updatedAt = document.getElementById('lastUpdate');

    async function getJson(url) {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) {
            throw new Error(`Request failed: ${res.status}`);
        }
        return res.json();
    }

    function statusBadge(status) {
        const normalized = String(status || '').toLowerCase();
        if (normalized === 'active') return '<span class="badge bg-success">Active</span>';
        if (normalized === 'pending') return '<span class="badge bg-warning text-dark">Pending</span>';
        if (normalized === 'terminated') return '<span class="badge bg-danger">Terminated</span>';
        return `<span class="badge bg-secondary">${normalized || 'unknown'}</span>`;
    }

    function renderRows(rows) {
        if (!tbody) return;
        if (!rows.length) {
            tbody.innerHTML = `
                <tr>
                    <td class="text-center text-muted py-4">No hotspot sessions found</td>
                    <td></td>
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
            const expires = row.expires_at ? new Date(row.expires_at).toLocaleString('en-KE') : '-';
            const started = row.started_at ? new Date(row.started_at).toLocaleString('en-KE') : '-';
            const totalGb = (Number(row.bytes_total || 0) / (1024 * 1024 * 1024)).toFixed(2);

            return `
                <tr>
                    <td><input type="checkbox" class="client-checkbox" value="${row.id || index + 1}"></td>
                    <td><strong>${row.username || row.phone || 'guest'}</strong></td>
                    <td><code class="bg-light px-1">${row.mac_address || '-'}</code></td>
                    <td><code>${row.ip_address || '-'}</code></td>
                    <td>${row.router || '-'}</td>
                    <td><span class="badge bg-secondary">${row.package || '-'}</span></td>
                    <td><div class="fw-semibold">${expires}</div></td>
                    <td><div class="fw-semibold text-success">${started}</div></td>
                    <td><small>${totalGb} GB</small></td>
                    <td>${statusBadge(row.status)}</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewClientDetails('${row.username || row.phone || 'guest'}')"><i class="fas fa-eye"></i></button>
                            <button class="btn btn-sm btn-outline-danger" title="Disconnect" onclick="confirmDisconnect('${row.username || row.phone || 'guest'}', '${row.mac_address || ''}')"><i class="fas fa-plug"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderStats(stats, rows) {
        if (statsBoxes.length >= 4) {
            statsBoxes[0].textContent = Number(stats.hotspot_active || 0).toLocaleString();
            statsBoxes[1].textContent = stats.total_bandwidth || '0 GB';
            statsBoxes[2].textContent = Number((rows || []).filter(r => r.started_at && new Date(r.started_at) >= new Date(Date.now() - 3600000)).length).toLocaleString();
            statsBoxes[3].textContent = Number(stats.routers_online || 0).toLocaleString();
        }
    }

    async function loadClients() {
        try {
            const status = document.getElementById('statusFilter')?.value;
            const search = document.getElementById('clientSearch')?.value?.trim() || '';

            const sessionsUrl = `/admin/api/clients/sessions?mode=hotspot&limit=200${status && status !== 'all' ? `&status=${encodeURIComponent(status)}` : ''}${search ? `&search=${encodeURIComponent(search)}` : ''}`;
            const [statsPayload, sessionsPayload] = await Promise.all([
                getJson('/admin/api/clients/stats?mode=hotspot'),
                getJson(sessionsUrl)
            ]);

            const rows = Array.isArray(sessionsPayload?.data) ? sessionsPayload.data : [];
            renderStats(statsPayload?.data || {}, rows);
            renderRows(rows);

            if ($.fn.DataTable.isDataTable(tableEl)) {
                tableEl.DataTable().destroy();
            }
            tableEl.DataTable({ responsive: false, autoWidth: false, paging: true, searching: false, order: [[7, 'desc']], columnDefs: [{ targets: [0, -1], orderable: false, searchable: false }] });

            if (updatedAt) {
                updatedAt.textContent = new Date().toLocaleTimeString('en-KE');
            }
        } catch (error) {
            console.error('Failed to load hotspot clients:', error);
        }
    }

    window.refreshClients = loadClients;
    window.searchClients = loadClients;
    document.getElementById('statusFilter')?.addEventListener('change', loadClients);

    loadClients();
    setInterval(loadClients, 30000);
});
</script>
@endpush
