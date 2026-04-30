<?php

namespace App\Services\Admin;

use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TenantBackupService
{
    /**
     * @var array<int, string>
     */
    private const TABLES = [
        'routers',
        'packages',
        'vouchers',
        'payments',
        'user_sessions',
        'audit_logs',
        'payment_reconciliations',
    ];

    /**
     * @return array<string, mixed>
     */
    public function latestBackupMetadata(?Tenant $tenant): array
    {
        if (!$tenant) {
            return [
                'available' => false,
                'label' => 'Tenant backup is available once a tenant is selected.',
                'filename' => null,
                'generated_at' => null,
                'generated_at_label' => 'No tenant selected',
                'size_label' => null,
            ];
        }

        $directory = $this->backupDirectory($tenant);
        if (!is_dir($directory)) {
            return [
                'available' => false,
                'label' => 'No backup generated yet',
                'filename' => null,
                'generated_at' => null,
                'generated_at_label' => 'No backup generated yet',
                'size_label' => null,
            ];
        }

        $files = collect(File::files($directory))
            ->sortByDesc(fn (\SplFileInfo $file) => $file->getMTime())
            ->values();

        $latest = $files->first();
        if (!$latest instanceof \SplFileInfo) {
            return [
                'available' => false,
                'label' => 'No backup generated yet',
                'filename' => null,
                'generated_at' => null,
                'generated_at_label' => 'No backup generated yet',
                'size_label' => null,
            ];
        }

        $generatedAt = date(DATE_ATOM, $latest->getMTime());

        return [
            'available' => true,
            'label' => $latest->getFilename(),
            'filename' => $latest->getFilename(),
            'generated_at' => $generatedAt,
            'generated_at_label' => date('d M Y, H:i:s', $latest->getMTime()),
            'size_label' => $this->formatBytes((int) $latest->getSize()),
        ];
    }

    /**
     * @return array{path: string, filename: string, size_bytes: int}
     */
    public function createBackup(Tenant $tenant): array
    {
        $snapshot = $this->buildSnapshot($tenant);
        $directory = $this->backupDirectory($tenant);
        File::ensureDirectoryExists($directory);

        $filename = sprintf(
            'cloudbridge-%s-backup-%s.json',
            Str::slug($tenant->subdomain ?: $tenant->name ?: ('tenant-' . $tenant->id)),
            now()->format('Ymd-His')
        );
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        File::put($path, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return [
            'path' => $path,
            'filename' => $filename,
            'size_bytes' => (int) filesize($path),
        ];
    }

    /**
     * @return array{restored_tables: array<string, int>, restored_tenant: bool}
     */
    public function restoreBackup(Tenant $tenant, UploadedFile $file): array
    {
        $payload = json_decode((string) file_get_contents($file->getRealPath()), true);
        if (!is_array($payload)) {
            throw new \RuntimeException('The uploaded backup file is not valid JSON.');
        }

        if ((string) Arr::get($payload, 'format') !== 'cloudbridge-tenant-backup') {
            throw new \RuntimeException('The uploaded file is not a CloudBridge tenant backup.');
        }

        $backupTenantId = (int) Arr::get($payload, 'tenant.id', 0);
        if ($backupTenantId > 0 && $backupTenantId !== (int) $tenant->id) {
            throw new \RuntimeException('This backup belongs to a different tenant and cannot be restored here.');
        }

        $tables = (array) Arr::get($payload, 'tables', []);
        $tenantAttributes = (array) Arr::get($payload, 'tenant', []);
        $restoredTables = [];

        DB::transaction(function () use ($tenant, $tables, $tenantAttributes, &$restoredTables): void {
            Schema::disableForeignKeyConstraints();

            try {
                $this->wipeTenantData($tenant);

                $updatableTenantAttributes = Arr::except($tenantAttributes, [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]);
                if ($updatableTenantAttributes !== []) {
                    DB::table('tenants')->where('id', $tenant->id)->update($updatableTenantAttributes);
                }

                foreach (self::TABLES as $table) {
                    if (!Schema::hasTable($table)) {
                        continue;
                    }

                    $rows = array_map(
                        static fn ($row): array => is_array($row) ? $row : (array) $row,
                        (array) ($tables[$table] ?? [])
                    );

                    if ($rows === []) {
                        $restoredTables[$table] = 0;
                        continue;
                    }

                    DB::table($table)->insert($rows);
                    $restoredTables[$table] = count($rows);
                }
            } finally {
                Schema::enableForeignKeyConstraints();
            }
        });

        return [
            'restored_tables' => $restoredTables,
            'restored_tenant' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSnapshot(Tenant $tenant): array
    {
        $tables = [];

        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $tables[$table] = DB::table($table)
                ->where('tenant_id', $tenant->id)
                ->orderBy('id')
                ->get()
                ->map(static fn ($row): array => (array) $row)
                ->all();
        }

        $tenantRow = (array) DB::table('tenants')
            ->where('id', $tenant->id)
            ->first();

        return [
            'format' => 'cloudbridge-tenant-backup',
            'version' => 1,
            'generated_at' => now()->toIso8601String(),
            'tenant' => $tenantRow,
            'notes' => 'Admin users are intentionally excluded so a restore does not lock the current admin out.',
            'tables' => $tables,
        ];
    }

    private function wipeTenantData(Tenant $tenant): void
    {
        foreach (array_reverse(self::TABLES) as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->where('tenant_id', $tenant->id)->delete();
        }
    }

    private function backupDirectory(Tenant $tenant): string
    {
        return storage_path('app/backups/tenant-' . $tenant->id);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return number_format($bytes) . ' B';
    }
}
