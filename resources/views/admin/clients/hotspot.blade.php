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
                <h3>180</h3>
                <p>Active Sessions</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
            <a href="#" class="small-box-footer">View All <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>2.5 GB/s</h3>
                <p>Total Bandwidth</p>
            </div>
            <div class="icon"><i class="fas fa-tachometer-alt"></i></div>
            <a href="#" class="small-box-footer">Details <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>45</h3>
                <p>New (Last Hour)</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
            <a href="#" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>12</h3>
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
                    <option value="1" selected>Main Hotspot (192.168.88.1)</option>
                    <option value="2">PPPoE Server (192.168.88.2)</option>
                    <option value="3">Karen Branch (192.168.88.4)</option>
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
                    <option value="1hour">1 Hour</option>
                    <option value="3hours">3 Hours</option>
                    <option value="24hours">24 Hours</option>
                    <option value="weekly">Weekly</option>
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
                <!-- Client 1: Active -->
                <tr>
                    <td><input type="checkbox" class="client-checkbox" value="1"></td>
                    <td class="action-col">
                        <strong>user001</strong>
                        <div class="text-muted small">John M.</div>
                    </td>
                    <td><code class="bg-light px-1">AA:BB:CC:11:22:33</code></td>
                    <td><code>10.5.50.1</code></td>
                    <td>Main Hotspot</td>
                    <td><span class="badge bg-secondary">1 Hour Pass</span></td>
                                        <td>
                        <div class="fw-semibold">2026-03-19 11:00</div>
                        <span class="badge bg-warning text-dark">15 min left</span>
                    </td>
                    <td class="action-col">
                        <div class="fw-semibold text-success">00:45:23</div>
                        <div class="text-muted small">Last online: just now</div>
                    </td>
                    <td class="action-col">
                        <div class="progress" style="height: 6px; width: 100px;">
                            <div class="progress-bar bg-success" style="width: 35%"></div>
                        </div>
                        <small>250 MB / 1 GB</small>
                    </td>
                    <td><span class="badge bg-success">Active</span></td>
                    <td class="action-col">
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewClientDetails('user001')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning" title="Limit Speed" onclick="limitSpeed('user001')">
                                <i class="fas fa-tachometer-alt"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Disconnect" onclick="confirmDisconnect('user001', 'AA:BB:CC:11:22:33')">
                                <i class="fas fa-plug"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Client 2: Active -->
                <tr>
                    <td><input type="checkbox" class="client-checkbox" value="2"></td>
                    <td class="action-col">
                        <strong>user002</strong>
                        <div class="text-muted small">Jane K.</div>
                    </td>
                    <td><code class="bg-light px-1">AA:BB:CC:44:55:66</code></td>
                    <td><code>10.5.50.2</code></td>
                    <td>Main Hotspot</td>
                    <td><span class="badge bg-primary">24 Hours Pass</span></td>
                                        <td>
                        <div class="fw-semibold">2026-03-20 09:30</div>
                        <span class="badge bg-info">7 hrs left</span>
                    </td>
                    <td>
                        <div class="fw-semibold text-success">02:15:45</div>
                        <div class="text-muted small">Last online: 1 min ago</div>
                    </td>
                    <td>
                        <div class="progress" style="height: 6px; width: 100px;">
                            <div class="progress-bar bg-warning" style="width: 78%"></div>
                        </div>
                        <small>780 MB / 1 GB</small>
                    </td>
                    <td><span class="badge bg-success">Active</span></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewClientDetails('user002')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning" title="Limit Speed" onclick="limitSpeed('user002')">
                                <i class="fas fa-tachometer-alt"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Disconnect" onclick="confirmDisconnect('user002', 'AA:BB:CC:44:55:66')">
                                <i class="fas fa-plug"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Client 3: Idle -->
                <tr class="table-warning">
                    <td><input type="checkbox" class="client-checkbox" value="3"></td>
                    <td>
                        <strong>user003</strong>
                        <div class="text-muted small">Peter O.</div>
                    </td>
                    <td><code class="bg-light px-1">AA:BB:CC:77:88:99</code></td>
                    <td><code>10.5.50.3</code></td>
                    <td>Karen Branch</td>
                    <td><span class="badge bg-secondary">3 Hours Pass</span></td>
                                        <td>
                        <div class="fw-semibold">2026-03-19 12:10</div>
                        <span class="badge bg-warning text-dark">40 min left</span>
                    </td>
                    <td>
                        <div class="fw-semibold text-warning">01:30:12</div>
                        <div class="text-muted small">Last online: 5 min ago</div>
                    </td>
                    <td>
                        <div class="progress" style="height: 6px; width: 100px;">
                            <div class="progress-bar bg-info" style="width: 15%"></div>
                        </div>
                        <small>45 MB / 500 MB</small>
                    </td>
                    <td><span class="badge bg-warning text-dark">Idle</span></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewClientDetails('user003')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" title="Send Message" onclick="sendClientMessage('user003')">
                                <i class="fas fa-comment"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Disconnect" onclick="confirmDisconnect('user003', 'AA:BB:CC:77:88:99')">
                                <i class="fas fa-plug"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Client 4: Heavy User -->
                <tr>
                    <td><input type="checkbox" class="client-checkbox" value="4"></td>
                    <td>
                        <strong>user004</strong>
                        <div class="text-muted small">Mary W.</div>
                    </td>
                    <td><code class="bg-light px-1">AA:BB:CC:00:11:22</code></td>
                    <td><code>10.5.50.4</code></td>
                    <td>Main Hotspot</td>
                    <td><span class="badge bg-success">Weekly Pass</span></td>
                                        <td>
                        <div class="fw-semibold">2026-03-26</div>
                        <span class="badge bg-success">6 days left</span>
                    </td>
                    <td>
                        <div class="fw-semibold text-success">05:22:18</div>
                        <div class="text-muted small">Last online: just now</div>
                    </td>
                    <td>
                        <div class="progress" style="height: 6px; width: 100px;">
                            <div class="progress-bar bg-danger" style="width: 95%"></div>
                        </div>
                        <small>4.8 GB / 5 GB</small>
                    </td>
                    <td><span class="badge bg-success">Active</span></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewClientDetails('user004')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning" title="Limit Speed" onclick="limitSpeed('user004')">
                                <i class="fas fa-tachometer-alt"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Disconnect" onclick="confirmDisconnect('user004', 'AA:BB:CC:00:11:22')">
                                <i class="fas fa-plug"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Client 5: New Connection -->
                <tr>
                    <td><input type="checkbox" class="client-checkbox" value="5"></td>
                    <td>
                        <strong>user005</strong>
                        <div class="text-muted small">David L.</div>
                    </td>
                    <td><code class="bg-light px-1">AA:BB:CC:33:44:55</code></td>
                    <td><code>10.5.50.5</code></td>
                    <td>PPPoE Server</td>
                    <td><span class="badge bg-secondary">1 Hour Pass</span></td>
                                        <td>
                        <div class="fw-semibold">2026-03-19 10:30</div>
                        <span class="badge bg-warning text-dark">25 min left</span>
                    </td>
                    <td>
                        <div class="fw-semibold text-success">00:05:33</div>
                        <div class="text-muted small">Last online: just now</div>
                    </td>
                    <td>
                        <div class="progress" style="height: 6px; width: 100px;">
                            <div class="progress-bar bg-success" style="width: 5%"></div>
                        </div>
                        <small>12 MB / 500 MB</small>
                    </td>
                    <td><span class="badge bg-success">Active</span></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewClientDetails('user005')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning" title="Limit Speed" onclick="limitSpeed('user005')">
                                <i class="fas fa-tachometer-alt"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Disconnect" onclick="confirmDisconnect('user005', 'AA:BB:CC:33:44:55')">
                                <i class="fas fa-plug"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer clearfix">
        <div class="float-start">
            <small class="text-muted">Last updated: <span id="lastUpdate">Just now</span></small>
        </div>
        <div class="float-end">
            Showing 1-5 of 180 active clients
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
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
    
    // Mock data based on username
    if (username === 'user001') {
        document.getElementById('detailStatus').className = 'badge bg-success fs-6';
        document.getElementById('detailStatus').textContent = 'Active';
        document.getElementById('detailMac').textContent = 'AA:BB:CC:11:22:33';
        document.getElementById('detailIp').textContent = '10.5.50.1';
        document.getElementById('detailRouter').textContent = 'Main Hotspot (192.168.88.1)';
        document.getElementById('detailPackage').className = 'badge bg-secondary';
        document.getElementById('detailPackage').textContent = '1 Hour Pass';
        document.getElementById('detailUptime').textContent = '00:45:23';
        document.getElementById('detailExpiry').textContent = '2026-03-19 11:00';
        document.getElementById('detailLastOnline').textContent = 'Just now';
        document.getElementById('detailDownload').textContent = '180 MB';
        document.getElementById('detailUpload').textContent = '70 MB';
        document.getElementById('detailProgress').style.width = '35%';
        document.getElementById('detailDevice').textContent = 'Samsung Galaxy S21 (Android 13)';
        document.getElementById('detailLastActivity').textContent = '2 minutes ago - HTTPS request to google.com';
    }
    
    new bootstrap.Modal(document.getElementById('clientDetailsModal')).show();
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
        scrollX: true,
        scrollCollapse: true,
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
