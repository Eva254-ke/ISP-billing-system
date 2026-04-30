@extends('admin.layouts.app')

@section('page-title', 'System Logs')

@push('styles')
<style>
    .cb-log-toolbar,
    .cb-log-summary-card,
    .cb-log-feed-card,
    .cb-log-side-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.06);
    }

    .cb-log-toolbar {
        background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
    }

    .cb-log-source-grid {
        display: grid;
        gap: 0.75rem;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }

    .cb-log-source-button {
        align-items: flex-start;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 1rem;
        color: #1f2937;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        padding: 0.95rem 1rem;
        text-align: left;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        width: 100%;
    }

    .cb-log-source-button:hover,
    .cb-log-source-button:focus {
        border-color: rgba(37, 99, 235, 0.45);
        box-shadow: 0 12px 24px rgba(37, 99, 235, 0.12);
        transform: translateY(-1px);
    }

    .cb-log-source-button.is-active {
        background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
        border-color: transparent;
        color: #fff;
    }

    .cb-log-source-button__meta {
        display: flex;
        justify-content: space-between;
        width: 100%;
    }

    .cb-log-source-button__desc {
        color: inherit;
        font-size: 0.82rem;
        line-height: 1.4;
        opacity: 0.86;
    }

    .cb-log-summary-card .card-body {
        min-height: 134px;
    }

    .cb-log-summary-card__eyebrow {
        color: #64748b;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
    }

    .cb-log-summary-card__value {
        color: #0f172a;
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 0.4rem;
    }

    .cb-log-summary-card__hint {
        color: #64748b;
        font-size: 0.92rem;
        margin-bottom: 0;
    }

    .cb-log-entry {
        background: #fff;
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-left-width: 4px;
        border-radius: 1rem;
        margin-bottom: 1rem;
        overflow: hidden;
    }

    .cb-log-entry.is-critical {
        border-left-color: #b91c1c;
    }

    .cb-log-entry.is-error {
        border-left-color: #dc2626;
    }

    .cb-log-entry.is-warning {
        border-left-color: #d97706;
    }

    .cb-log-entry.is-info {
        border-left-color: #2563eb;
    }

    .cb-log-entry summary {
        cursor: pointer;
        list-style: none;
        padding: 1rem 1rem 0.9rem;
    }

    .cb-log-entry summary::-webkit-details-marker {
        display: none;
    }

    .cb-log-entry__head {
        align-items: center;
        display: flex;
        gap: 0.75rem;
        justify-content: space-between;
        margin-bottom: 0.6rem;
    }

    .cb-log-entry__meta {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }

    .cb-log-entry__time,
    .cb-log-entry__file,
    .cb-log-entry__env {
        color: #64748b;
        font-size: 0.82rem;
    }

    .cb-log-entry__message {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 600;
        line-height: 1.5;
        word-break: break-word;
    }

    .cb-log-entry__body {
        border-top: 1px solid rgba(226, 232, 240, 0.8);
        padding: 0 1rem 1rem;
    }

    .cb-log-entry__block + .cb-log-entry__block {
        margin-top: 0.9rem;
    }

    .cb-log-entry__label {
        color: #475569;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        margin: 0.95rem 0 0.45rem;
        text-transform: uppercase;
    }

    .cb-log-entry pre {
        background: #0f172a;
        border-radius: 0.9rem;
        color: #e2e8f0;
        font-size: 0.83rem;
        margin-bottom: 0;
        max-height: 240px;
        overflow: auto;
        padding: 0.9rem;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .cb-log-file-button {
        align-items: flex-start;
        border: 0;
        border-bottom: 1px solid rgba(226, 232, 240, 0.85);
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        text-align: left;
        width: 100%;
    }

    .cb-log-file-button.active {
        background: #eff6ff;
        color: #0f172a;
    }

    .cb-log-file-button:last-child {
        border-bottom: 0;
    }

    .cb-log-empty {
        background: linear-gradient(135deg, #fff 0%, #f8fafc 100%);
        border: 1px dashed rgba(148, 163, 184, 0.55);
        border-radius: 1rem;
        padding: 2rem 1.5rem;
        text-align: center;
    }

    .cb-log-empty__icon {
        color: #94a3b8;
        font-size: 2rem;
        margin-bottom: 0.75rem;
    }

    @media (max-width: 767.98px) {
        .cb-log-entry__head {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
@endpush

@section('content')
@php
    $filters = $logSnapshot['filters'] ?? ['source' => 'all', 'level' => 'all', 'search' => '', 'limit' => 120, 'file' => null];
    $summary = $logSnapshot['summary'] ?? ['total' => 0, 'critical' => 0, 'errors' => 0, 'warnings' => 0, 'channels' => 0, 'latest_event_label' => 'No events found'];
    $selectedChannel = $logSnapshot['selected_channel'] ?? null;
    $entries = $logSnapshot['entries'] ?? [];
    $files = $logSnapshot['files'] ?? [];
    $channels = $logSnapshot['channels'] ?? [];
    $levelOptions = $logSnapshot['level_options'] ?? [];
    $generatedAt = $logSnapshot['generated_at'] ?? now()->toIso8601String();
@endphp

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">System Logs</h2>
        <p class="text-muted mb-0">One place to inspect payments, routers, RADIUS, scheduler jobs, and application failures.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
            <i class="fas fa-rotate-right me-1"></i>Refresh now
        </button>
        <button type="button" class="btn btn-outline-primary" id="autoRefreshToggle" data-enabled="false">
            <i class="fas fa-tower-broadcast me-1"></i>Auto refresh off
        </button>
    </div>
</div>

<form id="logsFilterForm" method="GET" action="{{ route('admin.logs.index') }}">
    <input type="hidden" name="source" id="sourceInput" value="{{ $filters['source'] ?? 'all' }}">
    <input type="hidden" name="file" id="fileInput" value="{{ $filters['file'] ?? '' }}">

    <div class="card cb-log-toolbar mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                <div>
                    <h3 class="h5 mb-1">Observability Workspace</h3>
                    <p class="text-muted mb-0">
                        @if(($filters['source'] ?? 'all') === 'all')
                            Showing the newest file from each channel so cross-system incidents surface quickly.
                        @else
                            Showing <strong>{{ $selectedChannel['label'] ?? 'selected logs' }}</strong>. Use the file list to switch between newer and older log files.
                        @endif
                    </p>
                </div>
                <div class="text-lg-end">
                    <div class="small text-muted">Reading from</div>
                    <div><code>{{ $logSnapshot['log_directory'] ?? storage_path('logs') }}</code></div>
                    <div class="small text-muted mt-1">Rendered {{ \Illuminate\Support\Carbon::parse($generatedAt)->timezone(config('app.timezone', 'UTC'))->format('d M Y, H:i:s') }}</div>
                </div>
            </div>

            <div class="cb-log-source-grid mb-4">
                @foreach($channels as $channel)
                    <button
                        type="button"
                        class="cb-log-source-button {{ ($channel['active'] ?? false) ? 'is-active' : '' }} js-source-filter"
                        data-source="{{ $channel['key'] }}"
                    >
                        <div class="cb-log-source-button__meta">
                            <span><i class="{{ $channel['icon'] }} me-2"></i>{{ $channel['label'] }}</span>
                            <span class="badge {{ ($channel['active'] ?? false) ? 'bg-light text-dark' : 'bg-secondary' }}">
                                {{ number_format((int) ($channel['file_count'] ?? 0)) }} files
                            </span>
                        </div>
                        <div class="cb-log-source-button__desc">{{ $channel['description'] }}</div>
                    </button>
                @endforeach
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label for="searchInput" class="form-label">Search message or context</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input
                            type="search"
                            name="search"
                            id="searchInput"
                            class="form-control"
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="Search phone, receipt, timeout, router..."
                        >
                    </div>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label for="levelSelect" class="form-label">Severity</label>
                    <select name="level" id="levelSelect" class="form-select">
                        @foreach($levelOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['level'] ?? 'all') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label for="limitSelect" class="form-label">Events</label>
                    <select name="limit" id="limitSelect" class="form-select">
                        @foreach([40, 80, 120, 200, 300] as $limit)
                            <option value="{{ $limit }}" @selected((int) ($filters['limit'] ?? 120) === $limit)>{{ $limit }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-filter me-1"></i>Apply filters
                    </button>
                    <a href="{{ route('admin.logs.index') }}" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card cb-log-summary-card h-100">
            <div class="card-body">
                <div class="cb-log-summary-card__eyebrow">Events In View</div>
                <div class="cb-log-summary-card__value">{{ number_format((int) ($summary['total'] ?? 0)) }}</div>
                <p class="cb-log-summary-card__hint">Current result set after source, severity, file, and search filters.</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card cb-log-summary-card h-100">
            <div class="card-body">
                <div class="cb-log-summary-card__eyebrow">Errors</div>
                <div class="cb-log-summary-card__value text-danger">{{ number_format((int) ($summary['errors'] ?? 0)) }}</div>
                <p class="cb-log-summary-card__hint">Includes `error`, `critical`, `alert`, and `emergency` events.</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card cb-log-summary-card h-100">
            <div class="card-body">
                <div class="cb-log-summary-card__eyebrow">Warnings</div>
                <div class="cb-log-summary-card__value text-warning">{{ number_format((int) ($summary['warnings'] ?? 0)) }}</div>
                <p class="cb-log-summary-card__hint">Signals that need attention before they become customer-facing failures.</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card cb-log-summary-card h-100">
            <div class="card-body">
                <div class="cb-log-summary-card__eyebrow">Latest Event</div>
                <div class="cb-log-summary-card__value fs-4">{{ $summary['latest_event_label'] ?? 'No events found' }}</div>
                <p class="cb-log-summary-card__hint">{{ number_format((int) ($summary['channels'] ?? 0)) }} channels represented in this view.</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card cb-log-feed-card">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    <i class="fas fa-stream me-2"></i>
                    Event Stream
                </h3>
                <span class="text-muted small">Showing {{ number_format(count($entries)) }} entries</span>
            </div>
            <div class="card-body">
                @forelse($entries as $entry)
                    @php
                        $entryLevel = strtolower((string) ($entry['level'] ?? 'info'));
                        $entryCardClass = match ($entryLevel) {
                            'critical', 'alert', 'emergency' => 'is-critical',
                            'error' => 'is-error',
                            'warning' => 'is-warning',
                            default => 'is-info',
                        };
                        $entryBadgeClass = match ($entryLevel) {
                            'critical', 'alert', 'emergency' => 'bg-danger',
                            'error' => 'bg-danger',
                            'warning' => 'bg-warning text-dark',
                            'notice' => 'bg-secondary',
                            'debug' => 'bg-dark',
                            default => 'bg-primary',
                        };
                    @endphp
                    <details class="cb-log-entry {{ $entryCardClass }}" @if($loop->first) open @endif>
                        <summary>
                            <div class="cb-log-entry__head">
                                <div class="cb-log-entry__meta">
                                    <span class="badge bg-light text-dark">{{ $entry['source_label'] ?? 'System' }}</span>
                                    <span class="badge {{ $entryBadgeClass }}">{{ $entry['level_label'] ?? 'Info' }}</span>
                                    <span class="cb-log-entry__time">{{ $entry['timestamp_label'] ?? 'Unknown time' }}</span>
                                    @if(!empty($entry['environment']))
                                        <span class="cb-log-entry__env">{{ $entry['environment'] }}</span>
                                    @endif
                                </div>
                                <span class="cb-log-entry__file">{{ $entry['file'] ?? '' }}</span>
                            </div>
                            <div class="cb-log-entry__message">{{ $entry['message'] ?? 'Log entry' }}</div>
                        </summary>

                        @if(!empty($entry['context_pretty']) || !empty($entry['details_pretty']))
                            <div class="cb-log-entry__body">
                                @if(!empty($entry['context_pretty']))
                                    <div class="cb-log-entry__block">
                                        <div class="cb-log-entry__label">Context</div>
                                        <pre>{{ $entry['context_pretty'] }}</pre>
                                    </div>
                                @endif

                                @if(!empty($entry['details_pretty']))
                                    <div class="cb-log-entry__block">
                                        <div class="cb-log-entry__label">Details</div>
                                        <pre>{{ $entry['details_pretty'] }}</pre>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </details>
                @empty
                    <div class="cb-log-empty">
                        <div class="cb-log-empty__icon"><i class="fas fa-file-circle-question"></i></div>
                        <h4 class="h5">No matching log entries</h4>
                        <p class="text-muted mb-0">Try a broader source, clear the search term, or switch to another file on the right.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card cb-log-side-card mb-4">
            <div class="card-header border-0">
                <h3 class="card-title mb-0">
                    <i class="fas fa-file-lines me-2"></i>
                    Files In Scope
                </h3>
            </div>
            <div class="list-group list-group-flush">
                @forelse($files as $file)
                    <button
                        type="button"
                        class="list-group-item list-group-item-action cb-log-file-button {{ ($file['selected'] ?? false) ? 'active' : '' }} js-file-filter"
                        data-source="{{ $file['channel'] ?? 'all' }}"
                        data-file="{{ $file['name'] }}"
                    >
                        <strong>{{ $file['name'] }}</strong>
                        <span class="small text-muted">{{ $file['channel_label'] ?? 'System' }} • {{ $file['size_label'] ?? '0 B' }}</span>
                        <span class="small text-muted">Updated {{ $file['last_modified_label'] ?? 'Unknown' }}</span>
                    </button>
                @empty
                    <div class="p-3 text-muted">No log files were found in the configured directory yet.</div>
                @endforelse
            </div>
        </div>

        <div class="card cb-log-side-card">
            <div class="card-header border-0">
                <h3 class="card-title mb-0">
                    <i class="fas fa-layer-group me-2"></i>
                    Channel Inventory
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach($channels as $channel)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="fw-semibold"><i class="{{ $channel['icon'] }} me-2 text-muted"></i>{{ $channel['label'] }}</div>
                                    <div class="small text-muted">{{ $channel['last_updated_label'] ?? 'No log file yet' }}</div>
                                </div>
                                <span class="badge {{ ($channel['active'] ?? false) ? 'bg-primary' : 'bg-secondary' }}">
                                    {{ number_format((int) ($channel['file_count'] ?? 0)) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('logsFilterForm');
        var sourceInput = document.getElementById('sourceInput');
        var fileInput = document.getElementById('fileInput');
        var autoRefreshToggle = document.getElementById('autoRefreshToggle');
        var autoRefreshKey = 'cloudbridge.admin.logs.autorefresh';
        var autoRefreshDelay = 30000;
        var autoRefreshTimer = null;

        function syncAutoRefreshButton() {
            var enabled = window.localStorage.getItem(autoRefreshKey) === '1';
            autoRefreshToggle.dataset.enabled = enabled ? 'true' : 'false';
            autoRefreshToggle.classList.toggle('btn-primary', enabled);
            autoRefreshToggle.classList.toggle('btn-outline-primary', !enabled);
            autoRefreshToggle.innerHTML = enabled
                ? '<i class="fas fa-tower-broadcast me-1"></i>Auto refresh on'
                : '<i class="fas fa-tower-broadcast me-1"></i>Auto refresh off';

            if (autoRefreshTimer) {
                window.clearTimeout(autoRefreshTimer);
                autoRefreshTimer = null;
            }

            if (enabled) {
                autoRefreshTimer = window.setTimeout(function () {
                    window.location.reload();
                }, autoRefreshDelay);
            }
        }

        document.querySelectorAll('.js-source-filter').forEach(function (button) {
            button.addEventListener('click', function () {
                sourceInput.value = button.dataset.source || 'all';
                fileInput.value = '';
                form.submit();
            });
        });

        document.querySelectorAll('.js-file-filter').forEach(function (button) {
            button.addEventListener('click', function () {
                sourceInput.value = button.dataset.source || sourceInput.value || 'all';
                fileInput.value = button.dataset.file || '';
                form.submit();
            });
        });

        autoRefreshToggle.addEventListener('click', function () {
            var enabled = window.localStorage.getItem(autoRefreshKey) === '1';
            window.localStorage.setItem(autoRefreshKey, enabled ? '0' : '1');
            syncAutoRefreshButton();
        });

        syncAutoRefreshButton();
    });
</script>
@endpush
