@extends('admin.layouts.app')

@section('page-title', 'Generate Vouchers')

@section('content')
<div class="cb-page-header">
    <div class="cb-page-heading">
        <h2>Generate Vouchers</h2>
        <p>Create a new voucher batch for {{ $tenant?->name ?? 'the selected scope' }} and print it immediately if you need to hand out codes on the spot.</p>
    </div>
    <div class="cb-page-actions">
        <a href="{{ route('admin.vouchers.index') }}" class="btn btn-outline-secondary">Back to Vouchers</a>
    </div>
</div>

@if($packages->isEmpty())
    <div class="cb-section-card">
        <h3 class="cb-section-title">No active packages available</h3>
        <p class="cb-section-copy">Create or activate a package first so the voucher batch can point to a real plan.</p>
        <div class="cb-stacked-actions">
            <a href="{{ route('admin.packages.create') }}" class="btn btn-primary">Create Package</a>
            <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary">Open Packages</a>
        </div>
    </div>
@else
    <div class="row g-4">
        <div class="col-xl-8">
            <div class="cb-section-card">
                <form id="generateVoucherPageForm">
                    <div>
                        <h3 class="cb-section-title">Batch setup</h3>
                        <p class="cb-section-copy">Pick the package, decide how many codes to create, and choose whether you want to print them now.</p>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Package</label>
                            <select class="form-select" name="package_id" id="voucherPackageField" required>
                                @foreach($packages as $package)
                                    <option value="{{ $package->id }}" data-package-name="{{ $package->name }}" data-package-price="{{ $package->currency ?? 'KES' }} {{ number_format((float) $package->price, 2) }}">
                                        {{ $package->name }} ({{ $package->currency ?? 'KES' }} {{ number_format((float) $package->price, 2) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" min="1" max="1000" class="form-control" name="quantity" id="voucherQuantityField" value="10" required>
                            <small class="cb-field-note">Use smaller batches when staff are issuing codes manually.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Validity (hours)</label>
                            <input type="number" min="1" max="8760" class="form-control" name="validity_hours" id="voucherValidityField" value="24" required>
                            <small class="cb-field-note">The expiry countdown starts from first use.</small>
                        </div>
                        <div class="col-12">
                            <div class="cb-radio-row" role="group" aria-label="Quick quantity">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setVoucherQuantity(1)">1</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setVoucherQuantity(5)">5</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setVoucherQuantity(10)">10</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setVoucherQuantity(50)">50</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setVoucherQuantity(100)">100</button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prefix</label>
                            <input type="text" class="form-control" name="prefix" id="voucherPrefixField" value="CB-WIFI" maxlength="20" placeholder="CB-WIFI">
                            <small class="cb-field-note">Letters, numbers, and hyphens only. The separator is added automatically.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Batch Label</label>
                            <input type="text" class="form-control" name="batch_label" id="voucherBatchLabelField" placeholder="Front Desk - Evening Shift">
                            <small class="cb-field-note">This helps you trace who issued the batch later.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Output</label>
                            <div class="cb-radio-row">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="output_format" id="voucherOutputNone" value="none" checked>
                                    <label class="form-check-label" for="voucherOutputNone">Save only</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="output_format" id="voucherOutputList" value="list">
                                    <label class="form-check-label" for="voucherOutputList">Print list</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="output_format" id="voucherOutputCard" value="card">
                                    <label class="form-check-label" for="voucherOutputCard">Print cards</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-4 mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Code preview:</strong>
                        <code id="voucherCodePreview">CB-WIFI-123456</code>
                    </div>

                    <div class="cb-stacked-actions">
                        <a href="{{ route('admin.vouchers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="generateVoucherSubmitButton">
                            Generate Codes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="cb-card-grid">
                <div class="cb-section-card cb-section-card--compact">
                    <h3 class="cb-section-title">What happens</h3>
                    <p class="cb-section-copy">Each voucher gets a unique six-digit code tied to the package you selected.</p>
                    <p class="cb-section-copy">Unused vouchers stay available until they are redeemed or expire.</p>
                    <p class="cb-section-copy">If you choose print output, the batch opens in a print-friendly view right after generation.</p>
                </div>
                <div class="cb-metric-card">
                    <div class="cb-metric-label">Selected Package</div>
                    <div class="cb-metric-value" id="voucherSummaryPackage">{{ $packages->first()?->name ?? '-' }}</div>
                    <div class="cb-metric-note" id="voucherSummaryPrice">{{ ($packages->first()?->currency ?? 'KES') . ' ' . number_format((float) ($packages->first()?->price ?? 0), 2) }}</div>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
@if($packages->isNotEmpty())
<script>
const voucherGenerateForm = document.getElementById('generateVoucherPageForm');
const voucherPackageField = document.getElementById('voucherPackageField');
const voucherPrefixField = document.getElementById('voucherPrefixField');
const voucherCodePreview = document.getElementById('voucherCodePreview');
const voucherSummaryPackage = document.getElementById('voucherSummaryPackage');
const voucherSummaryPrice = document.getElementById('voucherSummaryPrice');
const voucherSubmitButton = document.getElementById('generateVoucherSubmitButton');

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function selectedPackageOption() {
    return voucherPackageField?.options[voucherPackageField.selectedIndex] || null;
}

function updateVoucherSummary() {
    const option = selectedPackageOption();
    const packageName = option?.dataset.packageName || option?.textContent || '-';
    const packagePrice = option?.dataset.packagePrice || '-';
    const prefix = String(voucherPrefixField?.value || 'CB-WIFI')
        .trim()
        .toUpperCase()
        .replace(/[^A-Z0-9-]/g, '')
        .replace(/^-+|-+$/g, '') || 'CB-WIFI';

    if (voucherSummaryPackage) {
        voucherSummaryPackage.textContent = packageName;
    }

    if (voucherSummaryPrice) {
        voucherSummaryPrice.textContent = packagePrice;
    }

    if (voucherCodePreview) {
        voucherCodePreview.textContent = `${prefix}-123456`;
    }
}

function setVoucherQuantity(value) {
    const field = document.getElementById('voucherQuantityField');
    if (field) {
        field.value = String(value);
        field.focus();
    }
}

function buildPrintMarkup(format, batchName, packageName, validityHours, vouchers) {
    const generatedAt = new Date().toLocaleString('en-KE');
    const rows = Array.isArray(vouchers) ? vouchers : [];

    if (format === 'card') {
        return `
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>${escapeHtml(batchName)} - Voucher Cards</title>
                <style>
                    body { font-family: Inter, Arial, sans-serif; margin: 24px; color: #0f172a; }
                    h1 { font-size: 24px; margin: 0 0 8px; }
                    p { margin: 0 0 16px; color: #475569; }
                    .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
                    .card { border: 1px dashed #94a3b8; border-radius: 8px; padding: 16px; page-break-inside: avoid; }
                    .card h2 { margin: 0 0 8px; font-size: 18px; }
                    .code { margin: 12px 0; padding: 12px; border: 1px solid #dbe3ef; border-radius: 4px; background: #f8fafc; font-size: 24px; font-weight: 700; letter-spacing: 0.08em; text-align: center; }
                    .meta { font-size: 13px; color: #475569; }
                </style>
            </head>
            <body>
                <h1>${escapeHtml(packageName)}</h1>
                <p>Batch: ${escapeHtml(batchName)} | Valid for ${escapeHtml(validityHours)} hours from first use | Printed ${escapeHtml(generatedAt)}</p>
                <div class="grid">
                    ${rows.map((voucher) => `
                        <section class="card">
                            <h2>${escapeHtml(packageName)}</h2>
                            <div class="code">${escapeHtml(voucher.code_display || voucher.code || '-')}</div>
                            <div class="meta">Valid for ${escapeHtml(validityHours)} hours from first use.</div>
                        </section>
                    `).join('')}
                </div>
            </body>
            </html>
        `;
    }

    return `
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>${escapeHtml(batchName)} - Voucher List</title>
            <style>
                body { font-family: Inter, Arial, sans-serif; margin: 24px; color: #0f172a; }
                h1 { font-size: 24px; margin: 0 0 8px; }
                p { margin: 0 0 16px; color: #475569; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #dbe3ef; padding: 10px 12px; text-align: left; font-size: 13px; }
                th { background: #f8fafc; }
                code { font-size: 14px; font-weight: 700; }
            </style>
        </head>
        <body>
            <h1>${escapeHtml(packageName)}</h1>
            <p>Batch: ${escapeHtml(batchName)} | Valid for ${escapeHtml(validityHours)} hours from first use | Printed ${escapeHtml(generatedAt)}</p>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Voucher Code</th>
                        <th>Valid Until</th>
                    </tr>
                </thead>
                <tbody>
                    ${rows.map((voucher, index) => `
                        <tr>
                            <td>${index + 1}</td>
                            <td><code>${escapeHtml(voucher.code_display || voucher.code || '-')}</code></td>
                            <td>${voucher.valid_until ? escapeHtml(new Date(voucher.valid_until).toLocaleString('en-KE')) : '-'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </body>
        </html>
    `;
}

function openVoucherPrintWindow(format, batchName, packageName, validityHours, vouchers) {
    const printWindow = window.open('', '_blank', 'noopener,noreferrer,width=960,height=720');
    if (!printWindow) {
        return false;
    }

    printWindow.document.open();
    printWindow.document.write(buildPrintMarkup(format, batchName, packageName, validityHours, vouchers));
    printWindow.document.close();
    printWindow.focus();
    window.setTimeout(() => {
        printWindow.print();
    }, 250);

    return true;
}

voucherPackageField?.addEventListener('change', updateVoucherSummary);
voucherPrefixField?.addEventListener('input', updateVoucherSummary);
updateVoucherSummary();

voucherGenerateForm?.addEventListener('submit', async function (event) {
    event.preventDefault();

    const form = event.currentTarget;
    const outputFormat = form.querySelector('input[name="output_format"]:checked')?.value || 'none';
    const payload = {
        package_id: Number(form.querySelector('[name="package_id"]').value || 0),
        quantity: Number(form.querySelector('[name="quantity"]').value || 0),
        validity_hours: Number(form.querySelector('[name="validity_hours"]').value || 0),
        prefix: String(form.querySelector('[name="prefix"]').value || '').trim(),
        batch_label: String(form.querySelector('[name="batch_label"]').value || '').trim(),
    };

    const originalLabel = voucherSubmitButton?.textContent || 'Generate Codes';
    if (voucherSubmitButton) {
        voucherSubmitButton.disabled = true;
        voucherSubmitButton.textContent = 'Generating...';
    }

    try {
        const response = await fetch('/admin/api/vouchers/generate', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(payload),
        });

        const json = await response.json().catch(() => ({}));
        if (!response.ok || !json?.success) {
            throw new Error(json?.message || 'Failed to generate vouchers');
        }

        const batchName = json?.data?.batch_name || 'Voucher Batch';
        const packageName = json?.data?.package?.name || selectedPackageOption()?.dataset.packageName || 'WiFi Package';
        const validityHours = json?.data?.validity_hours || payload.validity_hours;
        const vouchers = Array.isArray(json?.data?.vouchers) ? json.data.vouchers : [];
        const printStarted = outputFormat !== 'none'
            ? openVoucherPrintWindow(outputFormat, batchName, packageName, validityHours, vouchers)
            : true;

        if (window.Swal) {
            await Swal.fire({
                icon: 'success',
                title: 'Voucher batch generated',
                html: `
                    <div class="text-start">
                        <p><strong>Batch:</strong> ${escapeHtml(batchName)}</p>
                        <p><strong>Package:</strong> ${escapeHtml(packageName)}</p>
                        <p><strong>Codes:</strong> ${escapeHtml(vouchers.length || payload.quantity)}</p>
                        <p><strong>Validity:</strong> ${escapeHtml(validityHours)} hours</p>
                        ${outputFormat !== 'none' && !printStarted ? '<p class="text-muted mb-0">Printing was blocked by the browser. Please allow pop-ups and try again.</p>' : ''}
                    </div>
                `,
                confirmButtonText: 'Open Voucher List',
            });
        } else {
            alert('Voucher batch generated successfully.');
        }

        window.location.href = @json(route('admin.vouchers.index'));
    } catch (error) {
        if (window.Swal) {
            Swal.fire('Error', error?.message || 'Failed to generate vouchers', 'error');
        } else {
            alert(error?.message || 'Failed to generate vouchers');
        }
    } finally {
        if (voucherSubmitButton) {
            voucherSubmitButton.disabled = false;
            voucherSubmitButton.textContent = originalLabel;
        }
    }
});
</script>
@endif
@endpush
