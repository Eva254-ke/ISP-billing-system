<?php

namespace App\Services\Admin;

use App\Models\Payment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PaymentExportService
{
    /**
     * @var array<int, string>
     */
    private const SUCCESS_STATUSES = ['completed', 'confirmed', 'activated'];

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function sanitizeFilters(array $input): array
    {
        $dateRange = strtolower(trim((string) ($input['date_range'] ?? 'week')));
        if (!in_array($dateRange, ['today', 'yesterday', 'week', 'month', 'custom'], true)) {
            $dateRange = 'week';
        }

        $status = strtolower(trim((string) ($input['status'] ?? 'all')));
        if (!in_array($status, ['all', 'success', 'pending', 'failed', 'confirmed', 'completed', 'activated'], true)) {
            $status = 'all';
        }

        $dateFrom = trim((string) ($input['date_from'] ?? ''));
        $dateTo = trim((string) ($input['date_to'] ?? ''));
        $search = trim((string) ($input['search'] ?? ''));
        $packageId = max(0, (int) ($input['package_id'] ?? 0));
        $format = strtolower(trim((string) ($input['format'] ?? 'csv')));

        return [
            'date_range' => $dateRange,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status' => $status,
            'package_id' => $packageId,
            'search' => $search,
            'format' => $format === 'pdf' ? 'pdf' : 'csv',
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function paymentsQuery(?Tenant $tenant, array $filters): Builder
    {
        $query = Payment::query()
            ->when($tenant, fn (Builder $builder) => $builder->where('tenant_id', $tenant->id))
            ->with(['package', 'session']);

        $this->applyFilters($query, $filters);

        return $query->latest('created_at');
    }

    public function filename(string $format): string
    {
        $extension = strtolower($format) === 'pdf' ? 'pdf' : 'csv';

        return 'payments-export-' . now()->format('Ymd-His') . '.' . $extension;
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function csvRows(Collection $payments): array
    {
        $rows = [
            ['date', 'phone', 'customer', 'package', 'amount', 'currency', 'status', 'receipt'],
        ];

        foreach ($payments as $payment) {
            $rows[] = [
                (string) $payment->created_at?->toDateTimeString(),
                (string) ($payment->phone ?: $payment->mpesa_phone ?: ''),
                $payment->display_customer_name,
                (string) ($payment->package_name ?: $payment->package?->name ?: ''),
                number_format((float) $payment->amount, 2, '.', ''),
                (string) ($payment->currency ?: 'KES'),
                (string) ($payment->status ?: ''),
                (string) ($payment->mpesa_receipt_number ?: ($payment->mpesa_checkout_request_id ?: ($payment->reference ?: ''))),
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function pdfDocument(?Tenant $tenant, Collection $payments, array $filters): string
    {
        $rows = $payments->map(function (Payment $payment): string {
            $date = $payment->created_at?->format('Y-m-d H:i') ?: '-';
            $phone = $payment->phone ?: $payment->mpesa_phone ?: '-';
            $payer = $payment->display_customer_name;
            $package = $payment->package_name ?: $payment->package?->name ?: '-';
            $amount = ($payment->currency ?: 'KES') . ' ' . number_format((float) $payment->amount, 2);
            $status = ucfirst((string) ($payment->status ?: '-'));
            $receipt = $payment->mpesa_receipt_number ?: ($payment->mpesa_checkout_request_id ?: ($payment->reference ?: '-'));

            return implode(' ', [
                str_pad($this->truncate($date, 16), 16),
                str_pad($this->truncate($phone, 13), 13),
                str_pad($this->truncate($payer, 16), 16),
                str_pad($this->truncate($package, 14), 14),
                str_pad($this->truncate($amount, 12), 12),
                str_pad($this->truncate($status, 10), 10),
                str_pad($this->truncate($receipt, 16), 16),
            ]);
        })->values();

        $title = trim((string) ($tenant?->name ?: 'CloudBridge') . ' Payments Report');
        $summaryLines = [
            $title,
            'Exported: ' . now()->format('Y-m-d H:i:s'),
            'Period: ' . $this->periodLabel($filters),
            'Status: ' . $this->statusLabel((string) ($filters['status'] ?? 'all')),
            'Package: ' . $this->packageLabel($payments, (int) ($filters['package_id'] ?? 0)),
            'Search: ' . ($filters['search'] !== '' ? (string) $filters['search'] : 'None'),
            sprintf(
                'Rows: %d | Successful: %d | Pending: %d | Failed: %d | Total: %s %.2f',
                $payments->count(),
                $payments->whereIn('status', self::SUCCESS_STATUSES)->count(),
                $payments->where('status', 'pending')->count(),
                $payments->where('status', 'failed')->count(),
                $tenant?->currency ?: (string) ($payments->first()?->currency ?: 'KES'),
                (float) $payments->sum(fn (Payment $payment) => (float) $payment->amount)
            ),
        ];

        $tableHeader = 'DATE             PHONE         PAYER            PACKAGE        AMOUNT       STATUS     RECEIPT';
        $divider = str_repeat('-', strlen($tableHeader));

        $chunks = [];
        if ($rows->isEmpty()) {
            $chunks[] = collect();
        } else {
            $chunks[] = $rows->take(32);
            foreach ($rows->slice(32)->chunk(38) as $chunk) {
                $chunks[] = $chunk;
            }
        }

        $pageCount = count($chunks);
        $pages = [];

        foreach ($chunks as $pageIndex => $chunk) {
            $lines = $pageIndex === 0
                ? [...$summaryLines, $divider, $tableHeader, $divider]
                : [
                    $title . ' (continued)',
                    'Exported: ' . now()->format('Y-m-d H:i:s'),
                    $divider,
                    $tableHeader,
                    $divider,
                ];

            if ($chunk->isEmpty()) {
                $lines[] = 'No payments matched this export.';
            } else {
                foreach ($chunk as $line) {
                    $lines[] = $line;
                }
            }

            $lines[] = '';
            $lines[] = 'Page ' . ($pageIndex + 1) . ' of ' . $pageCount;

            $pages[] = $lines;
        }

        return $this->buildPdf($pages);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $dateRange = (string) ($filters['date_range'] ?? 'week');

        switch ($dateRange) {
            case 'today':
                $query->whereDate('created_at', now()->toDateString());
                break;
            case 'yesterday':
                $query->whereDate('created_at', now()->subDay()->toDateString());
                break;
            case 'month':
                $query->where('created_at', '>=', now()->startOfMonth());
                break;
            case 'custom':
                $this->applyCustomDateFilter($query, (string) ($filters['date_from'] ?? ''), (string) ($filters['date_to'] ?? ''));
                break;
            case 'week':
            default:
                $query->where('created_at', '>=', now()->startOfWeek());
                break;
        }

        $status = (string) ($filters['status'] ?? 'all');
        if ($status === 'success') {
            $query->whereIn('status', self::SUCCESS_STATUSES);
        } elseif ($status !== 'all' && $status !== '') {
            $query->where('status', $status);
        }

        $packageId = (int) ($filters['package_id'] ?? 0);
        if ($packageId > 0) {
            $query->where('package_id', $packageId);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('phone', 'like', '%' . $search . '%')
                    ->orWhere('mpesa_phone', 'like', '%' . $search . '%')
                    ->orWhere('customer_name', 'like', '%' . $search . '%')
                    ->orWhere('reference', 'like', '%' . $search . '%')
                    ->orWhere('mpesa_receipt_number', 'like', '%' . $search . '%')
                    ->orWhere('mpesa_checkout_request_id', 'like', '%' . $search . '%')
                    ->orWhere('package_name', 'like', '%' . $search . '%');
            });
        }
    }

    private function applyCustomDateFilter(Builder $query, string $dateFrom, string $dateTo): void
    {
        $from = $this->parseDateBoundary($dateFrom, true);
        $to = $this->parseDateBoundary($dateTo, false);

        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
            return;
        }

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }
    }

    private function parseDateBoundary(string $value, bool $startOfDay): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            $date = Carbon::parse($value);

            return $startOfDay ? $date->startOfDay() : $date->endOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<int, array<int, string>> $pages
     */
    private function buildPdf(array $pages): string
    {
        $objects = [];
        $pageObjectNumbers = [];
        $contentObjectNumbers = [];
        $nextObjectNumber = 4;

        foreach ($pages as $pageIndex => $lines) {
            $pageObjectNumbers[] = $nextObjectNumber++;
            $contentObjectNumbers[] = $nextObjectNumber++;
        }

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>';

        $kids = [];
        foreach ($pageObjectNumbers as $pageObjectNumber) {
            $kids[] = $pageObjectNumber . ' 0 R';
        }
        $objects[2] = '<< /Type /Pages /Count ' . count($pageObjectNumbers) . ' /Kids [' . implode(' ', $kids) . '] >>';

        foreach ($pages as $pageIndex => $lines) {
            $pageObjectNumber = $pageObjectNumbers[$pageIndex];
            $contentObjectNumber = $contentObjectNumbers[$pageIndex];
            $stream = $this->pageStream($lines);

            $objects[$pageObjectNumber] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $contentObjectNumber . ' 0 R >>';
            $objects[$contentObjectNumber] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
        }

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $objectCount = max(array_keys($objects));

        $pdf .= "xref\n0 " . ($objectCount + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= $objectCount; $i++) {
            $offset = $offsets[$i] ?? 0;
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size " . ($objectCount + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    /**
     * @param array<int, string> $lines
     */
    private function pageStream(array $lines): string
    {
        $stream = "BT\n/F1 10 Tf\n14 TL\n40 760 Td\n";

        foreach (array_values($lines) as $index => $line) {
            $escaped = $this->escapePdfText($line);
            if ($index > 0) {
                $stream .= "T*\n";
            }

            $stream .= '(' . $escaped . ") Tj\n";
        }

        $stream .= "ET";

        return $stream;
    }

    private function escapePdfText(string $value): string
    {
        $normalized = $this->normalizeText($value);

        return strtr($normalized, [
            '\\' => '\\\\',
            '(' => '\\(',
            ')' => '\\)',
            "\r" => '',
            "\n" => ' ',
            "\t" => ' ',
        ]);
    }

    private function normalizeText(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);

        if ($converted === false) {
            $converted = preg_replace('/[^\x20-\x7E]/', '?', $value) ?? '';
        }

        return $converted;
    }

    private function truncate(string $value, int $length): string
    {
        $normalized = $this->normalizeText($value);

        if (strlen($normalized) <= $length) {
            return $normalized;
        }

        if ($length <= 3) {
            return substr($normalized, 0, $length);
        }

        return substr($normalized, 0, $length - 3) . '...';
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function periodLabel(array $filters): string
    {
        return match ((string) ($filters['date_range'] ?? 'week')) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'month' => 'This month',
            'custom' => trim(implode(' to ', array_filter([
                (string) ($filters['date_from'] ?? ''),
                (string) ($filters['date_to'] ?? ''),
            ]))) ?: 'Custom range',
            default => 'This week',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'success' => 'Successful only',
            'pending' => 'Pending only',
            'failed' => 'Failed only',
            'confirmed', 'completed', 'activated' => ucfirst($status) . ' only',
            default => 'All statuses',
        };
    }

    private function packageLabel(Collection $payments, int $packageId): string
    {
        if ($packageId <= 0) {
            return 'All packages';
        }

        $packageName = (string) ($payments->first()?->package_name ?: $payments->first()?->package?->name ?: '');

        return $packageName !== '' ? $packageName : 'Selected package';
    }
}
