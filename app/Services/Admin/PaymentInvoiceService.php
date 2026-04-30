<?php

namespace App\Services\Admin;

use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PaymentInvoiceService
{
    /**
     * @return array<string, mixed>
     */
    public function buildInvoice(Payment $payment): array
    {
        $payment->loadMissing(['tenant', 'package', 'session.router']);

        $tenant = $payment->tenant;
        if (!$tenant) {
            throw new \RuntimeException('The selected payment is not attached to a tenant.');
        }

        $billing = $this->billingSettings($tenant);
        $invoiceRecord = $this->ensureInvoiceNumber($payment, $tenant, $billing);
        $payment->refresh();
        $payment->loadMissing(['tenant', 'package', 'session.router']);

        $amount = (float) $payment->amount;
        $taxEnabled = (bool) ($billing['tax_enabled'] ?? false);
        $taxRate = max(0, (float) ($billing['tax_rate'] ?? 0));
        $inclusive = (string) ($billing['tax_inclusive'] ?? 'inclusive') === 'inclusive';

        $subtotal = $amount;
        $taxAmount = 0.0;
        $total = $amount;

        if ($taxEnabled && $taxRate > 0) {
            if ($inclusive) {
                $subtotal = round($amount / (1 + ($taxRate / 100)), 2);
                $taxAmount = round($amount - $subtotal, 2);
                $total = $amount;
            } else {
                $subtotal = $amount;
                $taxAmount = round($subtotal * ($taxRate / 100), 2);
                $total = round($subtotal + $taxAmount, 2);
            }
        }

        $currency = (string) ($payment->currency ?: ($billing['sys_currency'] ?? $tenant->currency ?: 'KES'));
        $currencySymbol = (string) ($billing['sys_currency_symbol'] ?? $currency);
        $termsDays = max(0, (int) ($billing['invoice_terms'] ?? 0));
        $issuedAt = $payment->created_at ?? now();
        $dueAt = $issuedAt->copy()->addDays($termsDays);

        return [
            'tenant' => $tenant,
            'payment' => $payment,
            'number' => (string) ($invoiceRecord['number'] ?? ''),
            'issued_at' => $issuedAt,
            'due_at' => $dueAt,
            'template' => (string) ($billing['invoice_template'] ?? 'modern'),
            'currency' => $currency,
            'currency_symbol' => $currencySymbol,
            'tax_enabled' => $taxEnabled,
            'tax_label' => (string) ($billing['tax_label'] ?? 'Tax'),
            'tax_rate' => $taxRate,
            'tax_number' => (string) ($billing['tax_number'] ?? ''),
            'tax_inclusive' => $inclusive,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'invoice_address' => (string) ($billing['invoice_address'] ?? ''),
            'invoice_email' => (string) ($billing['invoice_email'] ?? ''),
            'invoice_footer_note' => (string) ($billing['invoice_footer_note'] ?? ''),
            'receipt_enabled' => (bool) ($billing['receipt_enabled'] ?? false),
            'customer_name' => (string) ($payment->customer_name ?: $payment->phone ?: 'Customer'),
            'line_description' => (string) ($payment->package_name ?: ($payment->package?->name ?: 'Internet access payment')),
            'reference' => (string) ($payment->mpesa_receipt_number ?: ($payment->mpesa_checkout_request_id ?: ($payment->reference ?: 'PAY-' . $payment->id))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ensureInvoiceNumber(Payment $payment, Tenant $tenant, array $billing): array
    {
        $metadata = is_array($payment->metadata) ? $payment->metadata : [];
        $existing = (array) Arr::get($metadata, 'invoice', []);

        if (!empty($existing['number'])) {
            return $existing;
        }

        DB::transaction(function () use ($payment, $tenant, &$existing, $billing): void {
            /** @var Tenant $lockedTenant */
            $lockedTenant = Tenant::query()->whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            $lockedMetadata = is_array($lockedPayment->metadata) ? $lockedPayment->metadata : [];
            $currentInvoice = (array) Arr::get($lockedMetadata, 'invoice', []);
            if (!empty($currentInvoice['number'])) {
                $existing = $currentInvoice;
                return;
            }

            $tenantSettings = (array) ($lockedTenant->settings ?? []);
            $billingSettings = array_merge($billing, (array) Arr::get($tenantSettings, 'billing', []));
            $prefix = trim((string) ($billingSettings['invoice_prefix'] ?? 'INV-'));
            $nextNumber = max(1, (int) ($billingSettings['invoice_next_number'] ?? 10001));
            $number = $prefix . $nextNumber;

            $currentInvoice = [
                'number' => $number,
                'sequence' => $nextNumber,
                'issued_at' => now()->toIso8601String(),
            ];

            Arr::set($lockedMetadata, 'invoice', $currentInvoice);
            $lockedPayment->metadata = $lockedMetadata;
            $lockedPayment->save();

            $billingSettings['invoice_next_number'] = (string) ($nextNumber + 1);
            $tenantSettings['billing'] = $billingSettings;
            $tenantSettings['admin_settings'] = array_merge(
                (array) Arr::get($tenantSettings, 'admin_settings', []),
                ['invoice_next_number' => (string) ($nextNumber + 1)]
            );
            $lockedTenant->settings = $tenantSettings;
            $lockedTenant->save();

            $existing = $currentInvoice;
        });

        return $existing;
    }

    /**
     * @return array<string, mixed>
     */
    private function billingSettings(Tenant $tenant): array
    {
        $tenantSettings = (array) ($tenant->settings ?? []);
        $adminSettings = (array) Arr::get($tenantSettings, 'admin_settings', []);
        $billingSettings = (array) Arr::get($tenantSettings, 'billing', []);

        $defaults = [
            'tax_enabled' => false,
            'tax_label' => 'VAT',
            'tax_rate' => '0',
            'tax_inclusive' => 'inclusive',
            'tax_number' => '',
            'invoice_template' => 'modern',
            'invoice_prefix' => 'INV-',
            'invoice_next_number' => '10001',
            'invoice_address' => trim(implode("\n", array_filter([
                $tenant->name,
                $tenant->contact_phone,
                $tenant->contact_email,
            ], static fn ($value): bool => trim((string) $value) !== ''))),
            'invoice_footer_note' => 'Thank you for your business.',
            'invoice_terms' => '0',
            'invoice_email' => (string) ($tenant->contact_email ?: ''),
            'receipt_enabled' => true,
            'sys_currency' => (string) ($tenant->currency ?: 'KES'),
            'sys_currency_symbol' => (string) ($tenant->currency ?: 'KES'),
        ];

        return array_merge($defaults, $adminSettings, $billingSettings);
    }
}
