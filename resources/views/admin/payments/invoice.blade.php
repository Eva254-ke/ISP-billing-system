<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice['number'] ?? '' }}</title>
    @php
        $invoiceTenant = $invoice['tenant'];
        $invoicePayment = $invoice['payment'];
        $invoiceColor = trim((string) ($invoiceTenant->brand_color_primary ?? '#1E40AF'));
        if (preg_match('/^#(?:[0-9A-Fa-f]{3}){1,2}$/', $invoiceColor) !== 1) {
            $invoiceColor = '#1E40AF';
        }
        $logoUrl = trim((string) ($invoiceTenant->logo_url ?? ''));
        $currencySymbol = (string) ($invoice['currency_symbol'] ?? 'KES');
        $formatMoney = static fn (float $amount): string => $currencySymbol . ' ' . number_format($amount, 2);
    @endphp
    <style>
        :root {
            --invoice-primary: {{ $invoiceColor }};
            --invoice-text: #0f172a;
            --invoice-muted: #64748b;
            --invoice-border: #dbe2ea;
            --invoice-surface: #f8fafc;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef2f7;
            color: var(--invoice-text);
            font-family: "Segoe UI", Arial, sans-serif;
            line-height: 1.5;
            padding: 32px 16px;
        }

        .invoice-shell {
            max-width: 920px;
            margin: 0 auto;
        }

        .invoice-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .invoice-toolbar__meta {
            color: var(--invoice-muted);
            font-size: 0.9rem;
        }

        .invoice-toolbar button,
        .invoice-toolbar a {
            border: 0;
            border-radius: 999px;
            background: var(--invoice-primary);
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-weight: 600;
            padding: 0.75rem 1rem;
            text-decoration: none;
        }

        .invoice-card {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 24px;
            box-shadow: 0 28px 60px rgba(15, 23, 42, 0.08);
            padding: 32px;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 32px;
        }

        .invoice-brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .invoice-brand__mark {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: rgba(30, 64, 175, 0.08);
            border: 1px solid rgba(30, 64, 175, 0.18);
            display: grid;
            place-items: center;
            overflow: hidden;
        }

        .invoice-brand__mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .invoice-brand__mark span {
            color: var(--invoice-primary);
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.08em;
        }

        .invoice-brand h1 {
            margin: 0;
            font-size: 1.8rem;
        }

        .invoice-brand p,
        .invoice-header__aside p {
            margin: 0.35rem 0 0;
            color: var(--invoice-muted);
        }

        .invoice-header__aside {
            text-align: right;
        }

        .invoice-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(30, 64, 175, 0.08);
            border-radius: 999px;
            color: var(--invoice-primary);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            padding: 0.45rem 0.75rem;
            text-transform: uppercase;
        }

        .invoice-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .invoice-panel {
            background: var(--invoice-surface);
            border: 1px solid var(--invoice-border);
            border-radius: 18px;
            padding: 18px;
        }

        .invoice-panel__label {
            color: var(--invoice-muted);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .invoice-panel__value {
            font-size: 1rem;
            font-weight: 600;
        }

        .invoice-panel pre {
            font: inherit;
            margin: 0;
            white-space: pre-wrap;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .invoice-table th,
        .invoice-table td {
            border-bottom: 1px solid var(--invoice-border);
            padding: 14px 0;
            text-align: left;
        }

        .invoice-table th:last-child,
        .invoice-table td:last-child,
        .invoice-totals td:last-child {
            text-align: right;
        }

        .invoice-totals {
            margin-left: auto;
            margin-top: 18px;
            max-width: 320px;
            width: 100%;
        }

        .invoice-totals table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-totals td {
            padding: 8px 0;
        }

        .invoice-totals tr:last-child td {
            border-top: 2px solid var(--invoice-border);
            color: var(--invoice-primary);
            font-size: 1.1rem;
            font-weight: 700;
            padding-top: 12px;
        }

        .invoice-footer {
            margin-top: 28px;
            color: var(--invoice-muted);
            font-size: 0.92rem;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .invoice-toolbar {
                display: none;
            }

            .invoice-card {
                border: 0;
                border-radius: 0;
                box-shadow: none;
                padding: 0;
            }
        }

        @media (max-width: 720px) {
            .invoice-header,
            .invoice-grid {
                grid-template-columns: 1fr;
            }

            .invoice-header {
                flex-direction: column;
            }

            .invoice-header__aside {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-shell">
        <div class="invoice-toolbar">
            <div class="invoice-toolbar__meta">
                Payment #{{ $invoicePayment->id }} • {{ ucfirst((string) ($invoicePayment->status ?? 'unknown')) }}
            </div>
            <div>
                <button type="button" onclick="window.print()">Print invoice</button>
                <a href="{{ route('admin.payments.index') }}">Back to payments</a>
            </div>
        </div>

        <article class="invoice-card">
            <header class="invoice-header">
                <div class="invoice-brand">
                    <div class="invoice-brand__mark">
                        @if($logoUrl !== '')
                            <img src="{{ $logoUrl }}" alt="{{ $invoiceTenant->name }} logo">
                        @else
                            <span>{{ strtoupper(substr((string) $invoiceTenant->name, 0, 2)) }}</span>
                        @endif
                    </div>
                    <div>
                        <h1>{{ $invoiceTenant->name }}</h1>
                        <p>{{ $invoice['invoice_email'] ?: ($invoiceTenant->contact_email ?: 'Billing contact not set') }}</p>
                    </div>
                </div>

                <div class="invoice-header__aside">
                    <span class="invoice-badge">Invoice {{ $invoice['number'] }}</span>
                    <p><strong>Issued:</strong> {{ $invoice['issued_at']->format('d M Y, H:i') }}</p>
                    <p><strong>Due:</strong> {{ $invoice['due_at']->format('d M Y') }}</p>
                    <p><strong>Reference:</strong> {{ $invoice['reference'] }}</p>
                </div>
            </header>

            <section class="invoice-grid">
                <div class="invoice-panel">
                    <div class="invoice-panel__label">Bill To</div>
                    <div class="invoice-panel__value">{{ $invoice['customer_name'] }}</div>
                    <div class="text-muted">{{ $invoicePayment->phone ?: 'Phone not captured' }}</div>
                </div>
                <div class="invoice-panel">
                    <div class="invoice-panel__label">Billing Address</div>
                    <div class="invoice-panel__value"><pre>{{ $invoice['invoice_address'] }}</pre></div>
                    @if($invoice['tax_enabled'] && $invoice['tax_number'] !== '')
                        <div class="text-muted mt-2">{{ $invoice['tax_label'] }}: {{ $invoice['tax_number'] }}</div>
                    @endif
                </div>
            </section>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $invoice['line_description'] }}</td>
                        <td>{{ ucfirst((string) ($invoicePayment->status ?? 'unknown')) }}</td>
                        <td>{{ $formatMoney((float) $invoice['subtotal']) }}</td>
                    </tr>
                    @if($invoice['tax_enabled'] && (float) $invoice['tax_amount'] > 0)
                        <tr>
                            <td>{{ $invoice['tax_label'] }} ({{ number_format((float) $invoice['tax_rate'], 2) }}%)</td>
                            <td>{{ $invoice['tax_inclusive'] ? 'Included in package price' : 'Added on top' }}</td>
                            <td>{{ $formatMoney((float) $invoice['tax_amount']) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            <div class="invoice-totals">
                <table>
                    <tr>
                        <td>Subtotal</td>
                        <td>{{ $formatMoney((float) $invoice['subtotal']) }}</td>
                    </tr>
                    <tr>
                        <td>{{ $invoice['tax_label'] }}</td>
                        <td>{{ $formatMoney((float) $invoice['tax_amount']) }}</td>
                    </tr>
                    <tr>
                        <td>Total</td>
                        <td>{{ $formatMoney((float) $invoice['total']) }}</td>
                    </tr>
                </table>
            </div>

            <footer class="invoice-footer">
                <p>{{ $invoice['invoice_footer_note'] }}</p>
                <p>Generated from payment record {{ $invoicePayment->id }} on {{ now()->format('d M Y, H:i:s') }}.</p>
            </footer>
        </article>
    </div>
</body>
</html>
