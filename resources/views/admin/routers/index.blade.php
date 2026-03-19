@extends('admin.layouts.app')

@section('page-title', 'Routers')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>MikroTik Routers</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRouterModal" data-toggle="modal" data-target="#addRouterModal">
        <i class="fas fa-plus me-2"></i>Add Router
    </button>
</div>

<!-- Stats Row -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>3</h3>
                <p>Online</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>1</h3>
                <p>Offline</p>
            </div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>234</h3>
                <p>Total Users</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>4</h3>
                <p>Total Routers</p>
            </div>
            <div class="icon"><i class="fas fa-server"></i></div>
        </div>
    </div>
</div>

<!-- Routers Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Connected Routers</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
            <button type="button" class="btn btn-tool" onclick="loadRouters()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped data-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>Router Name</th>
                    <th>IP Address</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Users</th>
                    <th>CPU/Mem</th>
                    <th>Last Sync</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Router 1: Main Hotspot -->
                <tr>
                    <td><input type="checkbox" class="router-checkbox" value="1"></td>
                    <td>
                        <strong>Main Hotspot</strong>
                        <div class="text-muted small">Nairobi Office</div>
                    </td>
                    <td><code>192.168.88.1</code></td>
                    <td><span class="badge bg-primary">Hotspot</span></td>
                    <td>Nairobi HQ</td>
                    <td>
                        <span class="status-dot online"></span>
                        <span class="text-success">Online</span>
                    </td>
                    <td>180</td>
                    <td>
                        <div class="progress progress-xs" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: 45%"></div>
                        </div>
                        <small class="text-muted">45% / 62%</small>
                    </td>
                    <td>2 min ago</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" title="Test">
                                <i class="fas fa-plug"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Router 2: PPPoE Server -->
                <tr>
                    <td><input type="checkbox" class="router-checkbox" value="2"></td>
                    <td>
                        <strong>PPPoE Server</strong>
                        <div class="text-muted small">Westlands Branch</div>
                    </td>
                    <td><code>192.168.88.2</code></td>
                    <td><span class="badge bg-info">PPPoE</span></td>
                    <td>Westlands</td>
                    <td>
                        <span class="status-dot online"></span>
                        <span class="text-success">Online</span>
                    </td>
                    <td>54</td>
                    <td>
                        <div class="progress progress-xs" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: 32%"></div>
                        </div>
                        <small class="text-muted">32% / 48%</small>
                    </td>
                    <td>1 min ago</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" title="Test">
                                <i class="fas fa-plug"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Router 3: Backup (Offline) -->
                <tr>
                    <td><input type="checkbox" class="router-checkbox" value="3"></td>
                    <td>
                        <strong>Backup Router</strong>
                        <div class="text-muted small">Karen Branch</div>
                    </td>
                    <td><code>192.168.88.3</code></td>
                    <td><span class="badge bg-primary">Hotspot</span></td>
                    <td>Karen</td>
                    <td>
                        <span class="status-dot offline"></span>
                        <span class="text-danger">Offline</span>
                    </td>
                    <td>0</td>
                    <td>
                        <span class="text-muted">-- / --</span>
                    </td>
                    <td>2 hours ago</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning" title="Reconnect">
                                <i class="fas fa-sync"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Router 4: Karen Branch -->
                <tr>
                    <td><input type="checkbox" class="router-checkbox" value="4"></td>
                    <td>
                        <strong>Karen Branch</strong>
                        <div class="text-muted small">Karen Office</div>
                    </td>
                    <td><code>192.168.88.4</code></td>
                    <td><span class="badge bg-primary">Hotspot</span></td>
                    <td>Karen</td>
                    <td>
                        <span class="status-dot online"></span>
                        <span class="text-success">Online</span>
                    </td>
                    <td>45</td>
                    <td>
                        <div class="progress progress-xs" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: 28%"></div>
                        </div>
                        <small class="text-muted">28% / 41%</small>
                    </td>
                    <td>3 min ago</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" title="Test">
                                <i class="fas fa-plug"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete">
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
            Showing 1-4 of 4 routers
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Select All Checkboxes
    document.getElementById('selectAll').addEventListener('change', function() {
        document.querySelectorAll('.router-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
    });

    // Bulk Delete
    document.getElementById('bulkDelete').addEventListener('click', function() {
        const selected = document.querySelectorAll('.router-checkbox:checked');
        if (selected.length === 0) {
            Swal.fire('Info', 'No routers selected', 'info');
            return;
        }
        Swal.fire({
            title: 'Delete Selected Routers?',
            text: `You are about to delete ${selected.length} router(s). This cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete!',
            confirmButtonColor: '#EF4444'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Deleted!', 'Routers have been deleted.', 'success');
            }
        });
    });

    // Refresh Routers (Mock)
    function loadRouters() {
        Swal.fire({
            title: 'Refreshing...',
            text: 'Checking router connections',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        setTimeout(() => {
            Swal.fire('Success', 'Router status updated', 'success');
        }, 1500);
    }

    // Initialize DataTable
    $(document).ready(function() {
        $('.data-table').DataTable({
            responsive: true,
            autoWidth: false,
            paging: false,
            searching: true,
            order: [[1, 'asc']]
        });
    });
</script>
@include('admin.routers.modals.add')
@endpush
