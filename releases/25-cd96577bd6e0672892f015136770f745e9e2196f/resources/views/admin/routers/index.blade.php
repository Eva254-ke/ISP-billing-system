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
                <h3>{{ number_format((int) ($stats['online'] ?? 0)) }}</h3>
                <p>Online</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ number_format((int) ($stats['offline'] ?? 0)) }}</h3>
                <p>Offline</p>
            </div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ number_format((int) ($stats['total_users'] ?? 0)) }}</h3>
                <p>Total Users</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format((int) ($stats['total'] ?? 0)) }}</h3>
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
            <button type="button" class="btn btn-tool" onclick="refreshRouters()">
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
                @forelse(($routers ?? collect()) as $router)
                    @php
                        $status = strtolower((string) ($router->status ?? 'offline'));
                        $isOnline = in_array($status, ['online', 'warning'], true);
                        $cpu = is_null($router->cpu_usage) ? null : (int) $router->cpu_usage;
                        $memory = is_null($router->memory_usage) ? null : (int) $router->memory_usage;
                        $progressWidth = max((int) ($cpu ?? 0), (int) ($memory ?? 0));
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="router-checkbox" value="{{ $router->id }}"></td>
                        <td>
                            <strong>{{ $router->name }}</strong>
                            <div class="text-muted small">{{ $router->location ?? 'N/A' }}</div>
                        </td>
                        <td><code>{{ $router->ip_address }}</code></td>
                        <td><span class="badge {{ str_contains(strtolower((string) $router->name), 'pppoe') ? 'bg-info' : 'bg-primary' }}">{{ str_contains(strtolower((string) $router->name), 'pppoe') ? 'PPPoE' : 'Hotspot' }}</span></td>
                        <td>{{ $router->location ?? 'N/A' }}</td>
                        <td>
                            <span class="status-dot {{ $isOnline ? 'online' : 'offline' }}"></span>
                            <span class="{{ $isOnline ? 'text-success' : 'text-danger' }}">{{ ucfirst($status) }}</span>
                        </td>
                        <td>{{ number_format((int) ($router->active_sessions ?? 0)) }}</td>
                        <td>
                            @if(is_null($cpu) && is_null($memory))
                                <span class="text-muted">-- / --</span>
                            @else
                                <div class="progress progress-xs" style="height: 6px;">
                                    <div class="progress-bar {{ $progressWidth >= 80 ? 'bg-danger' : 'bg-success' }}" style="width: {{ $progressWidth }}%"></div>
                                </div>
                                <small class="text-muted">{{ $cpu ?? '--' }}% / {{ $memory ?? '--' }}%</small>
                            @endif
                        </td>
                        <td>{{ optional($router->last_seen_at)->diffForHumans() ?? '-' }}</td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary" title="View" onclick="viewRouter({{ $router->id }})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-success" title="Test" onclick="testConnection({{ $router->id }})">
                                    <i class="fas fa-plug"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center text-muted py-4">No routers available.</td>
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
            <button class="btn btn-sm btn-danger" id="bulkDelete">
                <i class="fas fa-trash me-1"></i>Delete Selected
            </button>
        </div>
        <div class="float-end">
            Showing {{ number_format((int) (($routers ?? collect())->count())) }} routers
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

    // Refresh Routers
    function loadRouters() {
        if (typeof window.refreshRouters === 'function') {
            window.refreshRouters();
            return;
        }

        Swal.fire({
            title: 'Refreshing...',
            text: 'Checking router connections',
            timer: 1000,
            showConfirmButton: false,
        });
    }

</script>
@include('admin.routers.modals.add')
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableEl = $('.data-table');
    const tbody = document.querySelector('.data-table tbody');
    const statsBoxes = document.querySelectorAll('.row .small-box .inner h3');

    async function getJson(url) {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) {
            throw new Error(`Request failed: ${res.status}`);
        }
        return res.json();
    }

    function statusBadge(status) {
        const normalized = String(status || '').toLowerCase();
        const isOnline = normalized === 'online' || normalized === 'warning';
        const colorClass = isOnline ? 'text-success' : 'text-danger';
        const dotClass = isOnline ? 'online' : 'offline';
        return `<span class="status-dot ${dotClass}"></span><span class="${colorClass}">${normalized || 'unknown'}</span>`;
    }

    function renderRows(rows) {
        if (!tbody) return;

        if (!rows.length) {
            tbody.innerHTML = `
                <tr>
                    <td class="text-center text-muted py-4">No routers found</td>
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

        tbody.innerHTML = rows.map((row, i) => `
            <tr>
                <td><input type="checkbox" class="router-checkbox" value="${row.id || i + 1}"></td>
                <td><strong>${row.name || 'Router'}</strong><div class="text-muted small">${row.location || 'N/A'}</div></td>
                <td><code>${row.ip || '-'}</code></td>
                <td><span class="badge ${(String(row.name || '').toLowerCase().includes('pppoe')) ? 'bg-info' : 'bg-primary'}">${(String(row.name || '').toLowerCase().includes('pppoe')) ? 'PPPoE' : 'Hotspot'}</span></td>
                <td>${row.location || 'N/A'}</td>
                <td>${statusBadge(row.status)}</td>
                <td>${Number(row.users || 0).toLocaleString()}</td>
                <td>
                    ${(row.cpu == null && row.memory == null)
                        ? '<span class="text-muted">-- / --</span>'
                        : `<div class="progress progress-xs" style="height: 6px;"><div class="progress-bar ${Math.max(Number(row.cpu || 0), Number(row.memory || 0)) >= 80 ? 'bg-danger' : 'bg-success'}" style="width: ${Math.max(Number(row.cpu || 0), Number(row.memory || 0))}%"></div></div><small class="text-muted">${row.cpu ?? '--'}% / ${row.memory ?? '--'}%</small>`}
                </td>
                <td>${row.last_seen_at ? new Date(row.last_seen_at).toLocaleTimeString('en-KE', { hour: '2-digit', minute: '2-digit' }) : '-'}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" title="View" onclick="viewRouter(${row.id || 0})"><i class="fas fa-eye"></i></button>
                        <button class="btn btn-sm btn-outline-success" title="Test" onclick="testConnection(${row.id || 0})"><i class="fas fa-plug"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderStats(rows) {
        const online = rows.filter(r => ['online', 'warning'].includes(String(r.status || '').toLowerCase())).length;
        const offline = rows.filter(r => !['online', 'warning'].includes(String(r.status || '').toLowerCase())).length;
        const users = rows.reduce((sum, r) => sum + Number(r.users || 0), 0);
        if (statsBoxes.length >= 4) {
            statsBoxes[0].textContent = online.toLocaleString();
            statsBoxes[1].textContent = offline.toLocaleString();
            statsBoxes[2].textContent = users.toLocaleString();
            statsBoxes[3].textContent = rows.length.toLocaleString();
        }

        const footerCount = document.querySelector('.card-footer .float-end');
        if (footerCount) {
            footerCount.textContent = `Showing ${rows.length.toLocaleString()} routers`;
        }
    }

    async function loadRouters() {
        try {
            const payload = await getJson('/admin/api/routers/status?live=1');
            const rows = Array.isArray(payload?.data) ? payload.data : [];
            renderRows(rows);
            renderStats(rows);

            if ($.fn.DataTable.isDataTable(tableEl)) {
                tableEl.DataTable().destroy();
            }
            tableEl.DataTable({ responsive: true, autoWidth: false, paging: false, searching: true, order: [[1, 'asc']] });
        } catch (error) {
            console.error('Failed to load routers:', error);
        }
    }

    window.refreshRouters = loadRouters;
    loadRouters();
});
</script>
@endpush
