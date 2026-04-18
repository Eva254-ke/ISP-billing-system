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
                    <th class="action-col">Actions</th>
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
                        $routerTypeSource = strtolower(trim((string) ($router->model ?: $router->name)));
                        $routerType = match (true) {
                            str_contains($routerTypeSource, 'hotspot + pppoe'),
                            (str_contains($routerTypeSource, 'hotspot') && str_contains($routerTypeSource, 'pppoe')) => 'both',
                            str_contains($routerTypeSource, 'pppoe') => 'pppoe',
                            default => 'hotspot',
                        };
                        $routerTypeLabel = match ($routerType) {
                            'pppoe' => 'PPPoE',
                            'both' => 'Hotspot + PPPoE',
                            default => 'Hotspot',
                        };
                        $routerTypeClass = match ($routerType) {
                            'pppoe' => 'bg-info',
                            'both' => 'bg-secondary',
                            default => 'bg-primary',
                        };
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="router-checkbox" value="{{ $router->id }}"></td>
                        <td>
                            <strong>{{ $router->name }}</strong>
                            <div class="text-muted small">{{ $router->location ?? 'N/A' }}</div>
                        </td>
                        <td><code>{{ $router->ip_address }}</code></td>
                        <td><span class="badge {{ $routerTypeClass }}">{{ $routerTypeLabel }}</span></td>
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
                        <td class="action-col">
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
@include('admin.routers.modals.add')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableEl = window.jQuery ? window.jQuery('.data-table') : null;
    const tbody = document.querySelector('.data-table tbody');
    const statsBoxes = document.querySelectorAll('.row .small-box .inner h3');
    const selectAll = document.getElementById('selectAll');
    const bulkDelete = document.getElementById('bulkDelete');
    const addRouterForm = document.getElementById('addRouterForm');
    const saveRouterButton = document.getElementById('saveRouter');
    const footerCount = document.querySelector('.card-footer .float-end');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const viewBaseUrl = @json(url('/admin/routers'));

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function showAlert(title, text, icon = 'info') {
        if (window.Swal) {
            return Swal.fire(title, text, icon);
        }

        window.alert([title, text].filter(Boolean).join('\n'));
        return Promise.resolve();
    }

    async function requestJson(url, options = {}) {
        const headers = {
            Accept: 'application/json',
            ...(options.headers || {}),
        };

        if (options.body && !headers['Content-Type']) {
            headers['Content-Type'] = 'application/json';
        }

        if (csrfToken && !headers['X-CSRF-TOKEN']) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers,
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(payload?.message || `Request failed: ${response.status}`);
        }

        return payload;
    }

    function routerTypeMeta(type) {
        const normalized = String(type || '').toLowerCase();

        if (normalized === 'pppoe') {
            return { label: 'PPPoE', className: 'bg-info' };
        }

        if (normalized === 'both') {
            return { label: 'Hotspot + PPPoE', className: 'bg-secondary' };
        }

        return { label: 'Hotspot', className: 'bg-primary' };
    }

    function statusBadge(status) {
        const normalized = String(status || '').toLowerCase();
        const isOnline = normalized === 'online' || normalized === 'warning';
        const colorClass = isOnline ? 'text-success' : 'text-danger';
        const dotClass = isOnline ? 'online' : 'offline';
        return `<span class="status-dot ${dotClass}"></span><span class="${colorClass}">${escapeHtml(normalized || 'unknown')}</span>`;
    }

    function bindSelection() {
        if (!selectAll) return;

        const checkboxes = document.querySelectorAll('.router-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                if (!this.checked) {
                    selectAll.checked = false;
                    return;
                }

                const totalChecked = document.querySelectorAll('.router-checkbox:checked').length;
                selectAll.checked = totalChecked === checkboxes.length && checkboxes.length > 0;
            });
        });
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
            bindSelection();
            return;
        }

        tbody.innerHTML = rows.map((row, index) => {
            const type = routerTypeMeta(row.type);
            const utilization = Math.max(Number(row.cpu || 0), Number(row.memory || 0));
            const lastSeen = row.last_seen_at
                ? new Date(row.last_seen_at).toLocaleString('en-KE', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
                : '-';

            return `
                <tr>
                    <td><input type="checkbox" class="router-checkbox" value="${Number(row.id || index + 1)}"></td>
                    <td><strong>${escapeHtml(row.name || 'Router')}</strong><div class="text-muted small">${escapeHtml(row.location || 'N/A')}</div></td>
                    <td><code>${escapeHtml(row.ip || '-')}</code></td>
                    <td><span class="badge ${type.className}">${type.label}</span></td>
                    <td>${escapeHtml(row.location || 'N/A')}</td>
                    <td>${statusBadge(row.status)}</td>
                    <td>${Number(row.users || 0).toLocaleString()}</td>
                    <td>
                        ${(row.cpu == null && row.memory == null)
                            ? '<span class="text-muted">-- / --</span>'
                            : `<div class="progress progress-xs" style="height: 6px;"><div class="progress-bar ${utilization >= 80 ? 'bg-danger' : 'bg-success'}" style="width: ${utilization}%"></div></div><small class="text-muted">${row.cpu ?? '--'}% / ${row.memory ?? '--'}%</small>`}
                    </td>
                    <td>${escapeHtml(lastSeen)}</td>
                    <td class="action-col">
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" type="button" title="View router" onclick="viewRouter(${Number(row.id || 0)})"><i class="fas fa-eye"></i></button>
                            <button class="btn btn-sm btn-outline-success" type="button" title="Test connection" onclick="testConnection(${Number(row.id || 0)})"><i class="fas fa-plug"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        bindSelection();
    }

    function renderStats(rows) {
        const online = rows.filter(row => ['online', 'warning'].includes(String(row.status || '').toLowerCase())).length;
        const offline = rows.length - online;
        const users = rows.reduce((sum, row) => sum + Number(row.users || 0), 0);

        if (statsBoxes.length >= 4) {
            statsBoxes[0].textContent = online.toLocaleString();
            statsBoxes[1].textContent = offline.toLocaleString();
            statsBoxes[2].textContent = users.toLocaleString();
            statsBoxes[3].textContent = rows.length.toLocaleString();
        }

        if (footerCount) {
            footerCount.textContent = `Showing ${rows.length.toLocaleString()} routers`;
        }
    }

    function refreshDataTable() {
        if (!tableEl || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable) {
            return;
        }

        if (window.jQuery.fn.DataTable.isDataTable(tableEl)) {
            tableEl.DataTable().destroy();
        }

        tableEl.DataTable({
            responsive: true,
            autoWidth: false,
            paging: false,
            searching: true,
            order: [[1, 'asc']],
            columnDefs: [
                { targets: [0, -1], orderable: false, searchable: false },
            ],
        });
    }

    async function loadRouters() {
        try {
            const payload = await requestJson('/admin/api/routers/status?live=1');
            const rows = Array.isArray(payload?.data) ? payload.data : [];
            renderRows(rows);
            renderStats(rows);
            refreshDataTable();
        } catch (error) {
            console.error('Failed to load routers:', error);
            showAlert('Refresh failed', error.message || 'Unable to load routers right now.', 'error');
        }
    }

    window.viewRouter = function (id) {
        if (!id) return;
        window.location.assign(`${viewBaseUrl}/${id}`);
    };

    window.testConnection = async function (id) {
        if (!id) return;

        if (window.Swal) {
            Swal.fire({
                title: 'Testing router...',
                text: 'Checking API connectivity',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });
        }

        try {
            const payload = await requestJson('/admin/api/routers/test', {
                method: 'POST',
                body: JSON.stringify({ router_id: id }),
            });
            const details = payload?.data || {};
            const lines = [
                details.cpu != null ? `CPU Load: ${details.cpu}%` : null,
                details.memory != null ? `Memory Usage: ${details.memory}%` : null,
                details.uptime ? `Uptime: ${details.uptime}` : null,
            ].filter(Boolean);

            await showAlert('Router reachable', lines.join('\n') || (payload?.message || 'Router is online'), 'success');
            await loadRouters();
        } catch (error) {
            await showAlert('Connection failed', error.message || 'Router is offline or unreachable.', 'error');
        }
    };

    window.refreshRouters = loadRouters;

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.router-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    if (bulkDelete) {
        bulkDelete.addEventListener('click', async function () {
            const selectedIds = Array.from(document.querySelectorAll('.router-checkbox:checked'))
                .map(checkbox => Number(checkbox.value || 0))
                .filter(Boolean);

            if (!selectedIds.length) {
                await showAlert('No routers selected', 'Select at least one router to delete.', 'info');
                return;
            }

            if (window.Swal) {
                const result = await Swal.fire({
                    title: 'Delete selected routers?',
                    text: `You are about to delete ${selectedIds.length} router(s). This cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    confirmButtonColor: '#EF4444',
                });

                if (!result.isConfirmed) {
                    return;
                }
            }

            try {
                const payload = await requestJson('/admin/api/routers/bulk-delete', {
                    method: 'POST',
                    body: JSON.stringify({ router_ids: selectedIds }),
                });
                await showAlert('Routers deleted', payload?.message || 'Selected routers were deleted.', 'success');
                if (selectAll) {
                    selectAll.checked = false;
                }
                await loadRouters();
            } catch (error) {
                await showAlert('Delete failed', error.message || 'Unable to delete the selected routers.', 'error');
            }
        });
    }

    function hideModal(id) {
        if (window.CBModal && window.CBModal.hideById) {
            window.CBModal.hideById(id);
            return;
        }

        const modal = document.getElementById(id);
        if (!modal) return;

        if (window.bootstrap && window.bootstrap.Modal) {
            const modalApi = window.bootstrap.Modal;
            let instance = null;

            if (typeof modalApi.getOrCreateInstance === 'function') {
                instance = modalApi.getOrCreateInstance(modal);
            } else if (typeof modalApi.getInstance === 'function') {
                instance = modalApi.getInstance(modal) || new modalApi(modal);
            } else {
                try {
                    instance = new modalApi(modal);
                } catch (_) {
                    instance = null;
                }
            }

            if (instance && typeof instance.hide === 'function') {
                instance.hide();
                return;
            }
        }

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
            window.jQuery(modal).modal('hide');
        }
    }

    if (saveRouterButton && addRouterForm) {
        saveRouterButton.addEventListener('click', async function () {
            if (!addRouterForm.reportValidity()) {
                return;
            }

            const formData = new FormData(addRouterForm);
            const payload = {
                name: String(formData.get('name') || '').trim(),
                type: String(formData.get('type') || 'hotspot'),
                ip: String(formData.get('ip') || '').trim(),
                port: Number(formData.get('port') || 8728),
                username: String(formData.get('username') || '').trim(),
                password: String(formData.get('password') || ''),
                location: String(formData.get('location') || '').trim(),
                notes: String(formData.get('notes') || '').trim(),
            };

            const originalLabel = saveRouterButton.innerHTML;
            saveRouterButton.disabled = true;
            saveRouterButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

            try {
                const response = await requestJson('/admin/api/routers', {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });
                addRouterForm.reset();
                addRouterForm.querySelector('input[name="port"]').value = '8728';
                addRouterForm.querySelector('input[name="username"]').value = 'admin';
                hideModal('addRouterModal');
                await showAlert('Router added', response?.message || 'Router saved successfully.', 'success');
                await loadRouters();
            } catch (error) {
                await showAlert('Save failed', error.message || 'Unable to save the router.', 'error');
            } finally {
                saveRouterButton.disabled = false;
                saveRouterButton.innerHTML = originalLabel;
            }
        });
    }

    loadRouters();
});
</script>
@endpush
