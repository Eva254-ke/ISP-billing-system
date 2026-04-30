/**
 * CloudBridge Networks - Admin JavaScript
 * Production Ready
 */

document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const sidebarToggleClasses = ['sidebar-collapsed', 'sidebar-collapse'];

    function notifyLayoutChanged() {
        setTimeout(() => {
            window.dispatchEvent(new Event('resize'));
            document.dispatchEvent(new Event('cb:layout-changed'));

            if (window.jQuery) {
                window.jQuery(window).trigger('resize');
            }
        }, 260);
    }

    function setSidebarCollapsed(collapsed) {
        sidebarToggleClasses.forEach(className => body.classList.toggle(className, collapsed));
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', String(!collapsed));
        }
        notifyLayoutChanged();
    }

    function isSidebarCollapsed() {
        return sidebarToggleClasses.some(className => body.classList.contains(className));
    }

    if (window.flatpickr) {
        flatpickr('.date-picker', {
            dateFormat: 'Y-m-d',
            allowInput: true,
        });
    }

    const userDropdownToggle = document.querySelector('.js-user-dropdown-toggle');
    const hasBootstrapDropdown = window.bootstrap && window.bootstrap.Dropdown;

    if (userDropdownToggle && !hasBootstrapDropdown) {
        const dropdownMenu = userDropdownToggle.nextElementSibling;

        const closeUserMenu = () => {
            if (!dropdownMenu) return;
            dropdownMenu.classList.remove('show');
            userDropdownToggle.classList.remove('show');
            userDropdownToggle.setAttribute('aria-expanded', 'false');
        };

        userDropdownToggle.addEventListener('click', function (e) {
            e.preventDefault();
            if (!dropdownMenu) return;

            const isOpen = dropdownMenu.classList.contains('show');
            closeUserMenu();

            if (!isOpen) {
                dropdownMenu.classList.add('show');
                userDropdownToggle.classList.add('show');
                userDropdownToggle.setAttribute('aria-expanded', 'true');
            }
        });

        document.addEventListener('click', function (e) {
            if (!dropdownMenu) return;
            if (userDropdownToggle.contains(e.target) || dropdownMenu.contains(e.target)) return;
            closeUserMenu();
        });
    }

    document.querySelectorAll('.alert-auto-dismiss').forEach(function (alert) {
        setTimeout(function () {
            alert.classList.add('fade');
            setTimeout(() => alert.remove(), 150);
        }, 5000);
    });

    const toggleBtn = document.getElementById('sidebarToggle');
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            const isMobile = window.innerWidth <= 992;

            if (isMobile) {
                body.classList.toggle('sidebar-mobile-open');
                overlay.classList.toggle('active');
                toggleBtn.setAttribute('aria-expanded', String(body.classList.contains('sidebar-mobile-open')));
                notifyLayoutChanged();
                return;
            }

            setSidebarCollapsed(!isSidebarCollapsed());
        });
    }

    overlay.addEventListener('click', function () {
        body.classList.remove('sidebar-mobile-open');
        overlay.classList.remove('active');
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', 'false');
        }
        notifyLayoutChanged();
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 992) {
            overlay.classList.remove('active');
            body.classList.remove('sidebar-mobile-open');
            if (toggleBtn) {
                toggleBtn.setAttribute('aria-expanded', String(!isSidebarCollapsed()));
            }
        }
    });

    document.querySelectorAll('.btn-close').forEach(button => {
        if (!button.getAttribute('aria-label')) {
            button.setAttribute('aria-label', 'Close');
        }

        if (!button.getAttribute('title')) {
            button.setAttribute('title', 'Close');
        }
    });

    if (window.innerWidth <= 992) {
        document.querySelectorAll('.nav-treeview').forEach(menu => {
            menu.style.display = 'none';
        });

        document.querySelectorAll('.has-treeview > .nav-link').forEach(link => {
            link.addEventListener('click', function (e) {
                const treeview = this.nextElementSibling;

                if (treeview && treeview.classList.contains('nav-treeview')) {
                    e.preventDefault();
                    treeview.style.display = treeview.style.display === 'block' ? 'none' : 'block';
                    this.parentElement.classList.toggle('menu-open');
                }
            });
        });
    }

    document.querySelectorAll('[data-card-widget="collapse"]').forEach(button => {
        button.addEventListener('click', function () {
            const card = this.closest('.card');
            if (!card) return;

            card.classList.toggle('collapsed-card');

            const cardBody = card.querySelector('.card-body');
            const footer = card.querySelector('.card-footer');
            const icon = this.querySelector('i');
            const isCollapsed = card.classList.contains('collapsed-card');

            if (cardBody) cardBody.classList.toggle('d-none');
            if (footer) footer.classList.toggle('d-none');

            if (icon) {
                icon.classList.toggle('fa-minus', !isCollapsed);
                icon.classList.toggle('fa-plus', isCollapsed);
            }

            notifyLayoutChanged();
        });
    });

    const paymentsPage = document.getElementById('paymentsPage');

    if (paymentsPage) {
        const paymentsApiUrl = paymentsPage.dataset.paymentsUrl || '';
        const paymentShowBaseUrl = paymentsPage.dataset.paymentShowBaseUrl || '';
        const exportUrl = paymentsPage.dataset.exportUrl || '';
        const revenueStatuses = ['completed', 'confirmed', 'activated'];
        const tableNode = paymentsPage.querySelector('.data-table');
        const tableEl = window.jQuery && tableNode ? window.jQuery(tableNode) : null;
        const tableBody = paymentsPage.querySelector('.data-table tbody');
        const footerCount = paymentsPage.querySelector('.card-footer .float-end');
        const chartHost = document.getElementById('revenueChart');
        const selectAll = document.getElementById('selectAll');
        const dateRangeField = document.getElementById('dateRange');
        const dateFromField = document.getElementById('dateFrom');
        const dateToField = document.getElementById('dateTo');
        const statusField = document.getElementById('statusFilter');
        const packageField = document.getElementById('packageFilter');
        const searchField = document.getElementById('paymentSearch');
        let paymentRows = [];
        let revenueChart = null;
        let initialDailyRevenue = [];

        try {
            initialDailyRevenue = JSON.parse(paymentsPage.dataset.dailyRevenue || '[]');
        } catch (error) {
            initialDailyRevenue = [];
        }

        const notify = (message, icon = 'info', title = 'Info') => {
            if (window.Swal) {
                window.Swal.fire(title, message, icon);
                return;
            }

            window.alert(message);
        };

        const money = (value) => `KES ${Number(value || 0).toLocaleString()}`;
        const normalizeStatus = (value) => String(value || '').toLowerCase();
        const isRevenueStatus = (value) => revenueStatuses.includes(normalizeStatus(value));

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');

        const escapeJsString = (value) => String(value ?? '')
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/\r/g, '\\r')
            .replace(/\n/g, '\\n');

        const paymentReference = (row) => row.reference || row.mpesa_receipt_number || row.mpesa_checkout_request_id || `PAY-${row.id || 'NA'}`;

        const formatDateTime = (value) => {
            if (!value) {
                return '-';
            }

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return value;
            }

            return date.toLocaleString('en-KE', {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        };

        const formatDateParts = (value) => {
            if (!value) {
                return { date: '-', time: '-' };
            }

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return { date: value, time: '-' };
            }

            return {
                date: date.toLocaleDateString('en-CA'),
                time: date.toLocaleTimeString('en-GB'),
            };
        };

        const parseDateInput = (value) => {
            if (!/^\d{4}-\d{2}-\d{2}$/.test(String(value || ''))) {
                return null;
            }

            const [year, month, day] = String(value).split('-').map(Number);
            return new Date(year, month - 1, day);
        };

        const startOfDay = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0, 0);
        const endOfDay = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate(), 23, 59, 59, 999);
        const addDays = (date, days) => {
            const next = new Date(date);
            next.setDate(next.getDate() + days);
            return next;
        };
        const toDateKey = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        const currentFilters = () => ({
            dateRange: dateRangeField?.value || 'week',
            dateFrom: dateFromField?.value || '',
            dateTo: dateToField?.value || '',
            status: statusField?.value || 'all',
            packageId: packageField?.value || 'all',
            search: searchField?.value?.trim().toLowerCase() || '',
        });

        const rangeBounds = (filters) => {
            const today = startOfDay(new Date());

            if (filters.dateRange === 'today') {
                return { start: today, end: endOfDay(today) };
            }

            if (filters.dateRange === 'yesterday') {
                const yesterday = addDays(today, -1);
                return { start: yesterday, end: endOfDay(yesterday) };
            }

            if (filters.dateRange === 'month') {
                return {
                    start: new Date(today.getFullYear(), today.getMonth(), 1),
                    end: endOfDay(today),
                };
            }

            if (filters.dateRange === 'custom') {
                let from = parseDateInput(filters.dateFrom);
                let to = parseDateInput(filters.dateTo);

                if (from && !to) {
                    to = from;
                } else if (!from && to) {
                    from = to;
                }

                if (!from && !to) {
                    return { start: addDays(today, -6), end: endOfDay(today) };
                }

                if (from && to && from > to) {
                    const swap = from;
                    from = to;
                    to = swap;
                }

                return {
                    start: startOfDay(from || today),
                    end: endOfDay(to || from || today),
                };
            }

            const weekDay = today.getDay();
            const diffToMonday = weekDay === 0 ? -6 : 1 - weekDay;
            return {
                start: addDays(today, diffToMonday),
                end: endOfDay(today),
            };
        };

        const rowMatchesStatus = (row, filterStatus) => {
            const status = normalizeStatus(row.status);

            if (!filterStatus || filterStatus === 'all') {
                return true;
            }

            if (filterStatus === 'success') {
                return isRevenueStatus(status);
            }

            return status === filterStatus;
        };

        const rowMatchesPackage = (row, packageId) => {
            if (!packageId || packageId === 'all') {
                return true;
            }

            return String(row.package_id || '') === String(packageId);
        };

        const rowMatchesSearch = (row, query) => {
            if (!query) {
                return true;
            }

            return [
                row.phone,
                row.customer_name,
                row.package_name,
                row.reference,
                row.mpesa_receipt_number,
                row.mpesa_checkout_request_id,
            ].some((value) => String(value || '').toLowerCase().includes(query));
        };

        const rowMatchesDate = (row, filters) => {
            const createdAt = row.created_at ? new Date(row.created_at) : null;
            if (!createdAt || Number.isNaN(createdAt.getTime())) {
                return false;
            }

            const bounds = rangeBounds(filters);
            return createdAt >= bounds.start && createdAt <= bounds.end;
        };

        const filteredRows = (rows) => {
            const filters = currentFilters();

            return rows.filter((row) => rowMatchesStatus(row, filters.status)
                && rowMatchesPackage(row, filters.packageId)
                && rowMatchesSearch(row, filters.search)
                && rowMatchesDate(row, filters));
        };

        const summarizeRows = (rows) => {
            const todayKey = toDateKey(new Date());
            let revenueTotal = 0;
            let revenueToday = 0;
            let pending = 0;
            let failed = 0;

            rows.forEach((row) => {
                const status = normalizeStatus(row.status);
                const amount = Number(row.amount || 0);

                if (status === 'pending') {
                    pending += 1;
                }

                if (status === 'failed') {
                    failed += 1;
                }

                if (!isRevenueStatus(status)) {
                    return;
                }

                revenueTotal += amount;

                const createdAt = row.created_at ? new Date(row.created_at) : null;
                if (createdAt && !Number.isNaN(createdAt.getTime()) && toDateKey(createdAt) === todayKey) {
                    revenueToday += amount;
                }
            });

            return { revenueTotal, revenueToday, pending, failed };
        };

        const chartSeries = (rows) => {
            const bounds = rangeBounds(currentFilters());
            const totals = new Map();
            const days = [];
            let cursor = startOfDay(bounds.start);
            let guard = 0;

            while (cursor <= startOfDay(bounds.end) && guard < 366) {
                const key = toDateKey(cursor);
                days.push(new Date(cursor));
                totals.set(key, 0);
                cursor = addDays(cursor, 1);
                guard += 1;
            }

            rows.forEach((row) => {
                if (!isRevenueStatus(row.status)) {
                    return;
                }

                const createdAt = row.created_at ? new Date(row.created_at) : null;
                if (!createdAt || Number.isNaN(createdAt.getTime())) {
                    return;
                }

                const key = toDateKey(createdAt);
                if (!totals.has(key)) {
                    return;
                }

                totals.set(key, Number(totals.get(key) || 0) + Number(row.amount || 0));
            });

            const labelFormat = days.length <= 7
                ? { weekday: 'short' }
                : { month: 'short', day: 'numeric' };

            return {
                categories: days.map((date) => date.toLocaleDateString('en-KE', labelFormat)),
                series: days.map((date) => Number(totals.get(toDateKey(date)) || 0)),
            };
        };

        const ensureApex = async () => {
            if (window.ApexCharts) {
                return;
            }

            await new Promise((resolve, reject) => {
                const existing = document.querySelector('script[data-apex-fallback="true"]');
                if (existing) {
                    existing.addEventListener('load', resolve, { once: true });
                    existing.addEventListener('error', reject, { once: true });
                    return;
                }

                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
                script.dataset.apexFallback = 'true';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        };

        const renderChart = async (payload) => {
            if (!chartHost) {
                return;
            }

            try {
                await ensureApex();
            } catch (error) {
                chartHost.innerHTML = '<div class="text-center text-muted py-5">Revenue chart is unavailable right now.</div>';
                return;
            }

            if (revenueChart) {
                revenueChart.destroy();
            }

            const categories = Array.isArray(payload?.categories) && payload.categories.length ? payload.categories : ['No Data'];
            const data = Array.isArray(payload?.series) && payload.series.length ? payload.series : [0];
            const highestValue = Math.max(...data, 0);
            const suggestedMax = highestValue > 0 ? Math.ceil((highestValue * 1.2) / 100) * 100 : 100;

            revenueChart = new window.ApexCharts(chartHost, {
                chart: {
                    type: 'area',
                    height: 320,
                    parentHeightOffset: 0,
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    animations: { enabled: false },
                },
                series: [{ name: 'Revenue (KES)', data }],
                noData: { text: 'No revenue available for this range' },
                colors: ['#2563EB'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                markers: {
                    size: 4,
                    strokeWidth: 0,
                    hover: { size: 6 },
                },
                fill: {
                    type: 'solid',
                    opacity: 0.12,
                },
                grid: {
                    borderColor: '#E2E8F0',
                    strokeDashArray: 4,
                    padding: { left: 8, right: 8, top: 8, bottom: 0 },
                },
                xaxis: {
                    categories,
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: {
                        rotate: categories.length > 10 ? -45 : 0,
                        trim: false,
                        style: {
                            colors: '#64748B',
                            fontSize: '12px',
                        },
                    },
                },
                yaxis: {
                    min: 0,
                    max: suggestedMax,
                    tickAmount: 4,
                    labels: {
                        formatter: (value) => `KES ${Number(value || 0).toLocaleString()}`,
                        style: {
                            colors: '#64748B',
                            fontSize: '12px',
                        },
                    },
                },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: (value) => money(value),
                    },
                },
            });

            revenueChart.render();
        };

        const statusBadge = (status) => {
            const normalized = normalizeStatus(status);

            if (isRevenueStatus(normalized)) {
                return '<span class="badge bg-success">Success</span>';
            }

            if (normalized === 'pending') {
                return '<span class="badge bg-warning text-dark">Pending</span>';
            }

            if (normalized === 'failed') {
                return '<span class="badge bg-danger">Failed</span>';
            }

            return `<span class="badge bg-secondary">${escapeHtml(normalized || 'unknown')}</span>`;
        };

        const destroyDataTable = () => {
            if (!tableNode || !tableEl || !(window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable)) {
                return;
            }

            if (window.jQuery.fn.DataTable.isDataTable(tableNode)) {
                tableEl.DataTable().destroy();
            }
        };

        const refreshDataTable = () => {
            if (!tableNode || !tableEl || !(window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable)) {
                return;
            }

            destroyDataTable();
            tableEl.DataTable({
                responsive: true,
                autoWidth: false,
                paging: true,
                searching: false,
                order: [[1, 'desc']],
                columnDefs: [
                    { targets: [0, -1], orderable: false, searchable: false },
                ],
            });
        };

        const renderRows = (rows) => {
            if (!tableBody) {
                return;
            }

            if (selectAll) {
                selectAll.checked = false;
            }

            if (!rows.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td class="text-center text-muted py-4">No payments found</td>
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

            tableBody.innerHTML = rows.map((row, index) => {
                const parts = formatDateParts(row.created_at);
                const reference = paymentReference(row);

                return `
                    <tr>
                        <td><input type="checkbox" class="payment-checkbox" value="${row.id || index + 1}"></td>
                        <td><div><strong>${parts.date}</strong></div><small class="text-muted">${parts.time}</small></td>
                        <td><code>${escapeHtml(row.phone || '-')}</code></td>
                        <td>${escapeHtml(row.customer_name || '-')}</td>
                        <td><span class="badge bg-secondary">${escapeHtml(row.package_name || 'Package')}</span></td>
                        <td><strong>${money(row.amount || 0)}</strong></td>
                        <td><code class="text-primary">${escapeHtml(reference)}</code></td>
                        <td>${statusBadge(row.status)}</td>
                        <td class="action-col">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary" title="View Details" onclick="viewPaymentDetails(${Number(row.id || 0)})"><i class="fas fa-eye"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" title="Resend SMS" onclick="resendReceipt('${escapeJsString(row.phone || '')}', '${escapeJsString(reference)}')"><i class="fas fa-sms"></i></button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        };

        const renderStats = (summary) => {
            const revenueTotal = document.getElementById('statsRevenueTotal');
            const revenueToday = document.getElementById('statsRevenueToday');
            const pending = document.getElementById('statsPending');
            const failed = document.getElementById('statsFailed');

            if (revenueTotal) {
                revenueTotal.textContent = money(summary.revenueTotal);
            }

            if (revenueToday) {
                revenueToday.textContent = money(summary.revenueToday);
            }

            if (pending) {
                pending.textContent = Number(summary.pending || 0).toLocaleString();
            }

            if (failed) {
                failed.textContent = Number(summary.failed || 0).toLocaleString();
            }
        };

        const refreshView = async () => {
            const rows = filteredRows(paymentRows);
            destroyDataTable();
            renderRows(rows);
            renderStats(summarizeRows(rows));
            await renderChart(chartSeries(rows));
            refreshDataTable();

            if (footerCount) {
                footerCount.textContent = `Showing ${rows.length.toLocaleString()} payments`;
            }
        };

        const loadPayments = async () => {
            if (!paymentsApiUrl) {
                return;
            }

            try {
                const response = await fetch(`${paymentsApiUrl}?limit=500`, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    throw new Error(`Request failed: ${response.status}`);
                }

                const payload = await response.json();
                paymentRows = Array.isArray(payload?.data) ? payload.data : [];
                await refreshView();
            } catch (error) {
                console.error('Failed to load payments:', error);
            }
        };

        const toggleCustomDates = () => {
            const showCustom = dateRangeField?.value === 'custom';
            document.querySelectorAll('.custom-date').forEach((element) => {
                element.style.display = showCustom ? 'block' : 'none';
            });
        };

        const openModal = (id) => {
            const modal = document.getElementById(id);
            if (!modal) {
                return;
            }

            if (window.CBModal && window.CBModal.showById) {
                window.CBModal.showById(id);
                return;
            }

            if (window.bootstrap && window.bootstrap.Modal) {
                new window.bootstrap.Modal(modal).show();
                return;
            }

            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
                window.jQuery(modal).modal('show');
            }
        };

        window.applyFilters = () => {
            refreshView();
        };

        window.searchPayments = () => {
            refreshView();
        };

        const buildExportUrl = (format) => {
            if (!exportUrl) {
                return '';
            }

            const params = new URLSearchParams();
            params.set('format', format === 'pdf' ? 'pdf' : 'csv');
            params.set('date_range', String(dateRangeField?.value || 'week'));

            const dateFromValue = String(dateFromField?.value || '');
            const dateToValue = String(dateToField?.value || '');
            if (dateFromValue !== '') {
                params.set('date_from', dateFromValue);
            }
            if (dateToValue !== '') {
                params.set('date_to', dateToValue);
            }

            const statusValue = String(statusField?.value || 'all');
            if (statusValue !== 'all') {
                params.set('status', statusValue);
            }

            const packageValue = String(packageField?.value || 'all');
            if (packageValue !== '' && packageValue !== 'all') {
                params.set('package_id', packageValue);
            }

            const searchValue = String(searchField?.value || '').trim();
            if (searchValue !== '') {
                params.set('search', searchValue);
            }

            return `${exportUrl}?${params.toString()}`;
        };

        window.exportPayments = (format) => {
            const target = buildExportUrl(format);
            if (target !== '') {
                window.location.href = target;
                return;
            }

            notify('Payments export is unavailable right now.');
        };

        window.copyRef = () => {
            const ref = document.getElementById('detailRef')?.textContent?.trim() || '';
            if (!ref) {
                notify('No payment reference is available to copy.');
                return;
            }

            if (!navigator.clipboard) {
                notify('Clipboard access is unavailable in this browser.');
                return;
            }

            navigator.clipboard.writeText(ref)
                .then(() => {
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'success',
                            title: 'Copied',
                            text: 'Reference copied to clipboard',
                            timer: 1400,
                            showConfirmButton: false,
                        });
                    }
                })
                .catch(() => {
                    notify('Clipboard access is unavailable in this browser.');
                });
        };

        window.resendReceipt = (phone, reference) => {
            const label = [phone, reference].filter(Boolean).join(' / ');
            notify(`Receipt resend is not wired to an SMS gateway yet.${label ? ` Reference: ${label}` : ''}`);
        };

        window.resendReceiptFromModal = () => {
            const phone = document.getElementById('detailPhone')?.textContent?.trim() || '';
            const reference = document.getElementById('detailRef')?.textContent?.trim() || '';
            window.resendReceipt(phone, reference);
        };

        window.viewPaymentDetails = async (paymentId) => {
            if (!paymentId || !paymentShowBaseUrl) {
                notify('Payment details could not be loaded for this row.');
                return;
            }

            try {
                const response = await fetch(`${paymentShowBaseUrl}/${paymentId}`, {
                    headers: { Accept: 'application/json' },
                });
                const payload = await response.json().catch(() => ({}));

                if (!response.ok || !payload?.success) {
                    throw new Error(payload?.message || 'Failed to load payment details');
                }

                const row = payload.data || {};
                const status = normalizeStatus(row.status);
                const statusClass = isRevenueStatus(status)
                    ? 'bg-success'
                    : (status === 'pending' ? 'bg-warning text-dark' : (status === 'failed' ? 'bg-danger' : 'bg-secondary'));
                const sessionBits = [];

                if (row.session_duration_label) {
                    sessionBits.push(row.session_duration_label);
                }

                if (row.session_expires_at) {
                    sessionBits.push(`expires ${formatDateTime(row.session_expires_at)}`);
                }

                document.getElementById('detailRef').textContent = row.reference || `PAY-${paymentId}`;
                const detailStatus = document.getElementById('detailStatus');
                detailStatus.className = `badge fs-6 ${statusClass}`;
                detailStatus.textContent = status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown';
                document.getElementById('detailDateTime').textContent = formatDateTime(row.created_at || row.initiated_at);
                document.getElementById('detailAmount').textContent = money(row.amount || 0);
                document.getElementById('detailPhone').textContent = row.phone || '-';
                document.getElementById('detailCustomer').textContent = row.customer_name || '-';
                document.getElementById('detailPackage').textContent = row.package_name || '-';
                document.getElementById('detailResponse').textContent = JSON.stringify(row.callback_payload || {}, null, 2);
                document.getElementById('detailRouter').textContent = row.router_label || '-';
                document.getElementById('detailSession').textContent = sessionBits.length ? sessionBits.join(' | ') : '-';

                openModal('paymentDetailsModal');
            } catch (error) {
                notify(error.message || 'Failed to load payment details', 'error', 'Error');
            }
        };

        document.getElementById('bulkAction')?.addEventListener('click', () => {
            const selected = document.querySelectorAll('.payment-checkbox:checked');
            if (!selected.length) {
                notify('No payments selected.');
                return;
            }

            notify('Bulk payment deletion is disabled on this production page.');
        });

        document.getElementById('bulkExport')?.addEventListener('click', () => {
            const selected = document.querySelectorAll('.payment-checkbox:checked');
            if (!selected.length) {
                notify('No payments selected.');
                return;
            }

            window.exportPayments('csv');
        });

        selectAll?.addEventListener('change', () => {
            document.querySelectorAll('.payment-checkbox').forEach((checkbox) => {
                checkbox.checked = selectAll.checked;
            });
        });

        dateRangeField?.addEventListener('change', () => {
            toggleCustomDates();
            if (dateRangeField.value !== 'custom') {
                refreshView();
            }
        });
        dateFromField?.addEventListener('change', refreshView);
        dateToField?.addEventListener('change', refreshView);
        statusField?.addEventListener('change', refreshView);
        packageField?.addEventListener('change', refreshView);
        searchField?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                refreshView();
            }
        });

        document.addEventListener('cb:layout-changed', () => {
            if (revenueChart) {
                revenueChart.updateOptions({ chart: { height: 320 } }, false, false);
            }
        });

        toggleCustomDates();
        refreshDataTable();
        renderChart({
            categories: Array.isArray(initialDailyRevenue) && initialDailyRevenue.length
                ? initialDailyRevenue.map((entry) => entry.label || '')
                : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            series: Array.isArray(initialDailyRevenue) && initialDailyRevenue.length
                ? initialDailyRevenue.map((entry) => Number(entry.amount || 0))
                : [0, 0, 0, 0, 0, 0, 0],
        });
        loadPayments();
    }
});
