<?php

namespace App\Services\Radius;

use App\Models\UserSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RadiusAccountingService
{
    public function __construct(
        private readonly RadiusIdentityResolver $identityResolver
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function findOpenSession(string $username, ?string $macAddress = null, ?string $ipAddress = null): ?array
    {
        $connection = (string) config('radius.db_connection', 'radius');
        $table = (string) config('radius.tables.radacct', 'radacct');
        $normalizedMac = $this->identityResolver->normalizeMacAddress($macAddress);
        $normalizedIp = $this->normalizeIpAddress($ipAddress);

        $records = DB::connection($connection)
            ->table($table)
            ->where('username', $username)
            ->whereNull('acctstoptime')
            ->orderByDesc('acctupdatetime')
            ->orderByDesc('acctstarttime')
            ->limit(5)
            ->get();

        foreach ($records as $record) {
            $row = (array) $record;

            if (!$this->matchesRecord($row, $normalizedMac, $normalizedIp)) {
                continue;
            }

            return $row;
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function syncActiveSession(UserSession $session): ?array
    {
        $record = $this->findOpenSession(
            username: (string) $session->username,
            macAddress: $session->mac_address,
            ipAddress: $session->ip_address
        );

        if ($record === null) {
            return null;
        }

        $startedAt = $this->parseDateTimeValue($record['acctstarttime'] ?? null) ?? $session->started_at ?? now();
        $lastActivityAt = $this->parseDateTimeValue($record['acctupdatetime'] ?? null)
            ?? $startedAt;
        $normalizedMac = $this->identityResolver->normalizeMacAddress($record['callingstationid'] ?? null);
        $normalizedIp = $this->normalizeIpAddress($record['framedipaddress'] ?? null);
        $bytesIn = max(0, (int) ($record['acctinputoctets'] ?? 0));
        $bytesOut = max(0, (int) ($record['acctoutputoctets'] ?? 0));

        $updates = [
            'status' => 'active',
            'started_at' => $startedAt,
            'last_activity_at' => $lastActivityAt,
            'last_synced_at' => now(),
            'sync_failed' => false,
            'bytes_in' => $bytesIn,
            'bytes_out' => $bytesOut,
            'bytes_total' => $bytesIn + $bytesOut,
        ];

        if ($normalizedMac !== null && $session->mac_address !== $normalizedMac) {
            $updates['mac_address'] = $normalizedMac;
        }

        if ($normalizedIp !== null && $session->ip_address !== $normalizedIp) {
            $updates['ip_address'] = $normalizedIp;
        }

        $session->update($updates);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function matchesRecord(array $record, ?string $macAddress, ?string $ipAddress): bool
    {
        $recordMac = $this->identityResolver->normalizeMacAddress($record['callingstationid'] ?? null);
        $recordIp = $this->normalizeIpAddress($record['framedipaddress'] ?? null);

        if ($macAddress !== null && $recordMac !== null && $recordMac === $macAddress) {
            return true;
        }

        if ($ipAddress !== null && $recordIp !== null && $recordIp === $ipAddress) {
            return true;
        }

        return $macAddress === null && $ipAddress === null;
    }

    private function normalizeIpAddress(?string $ipAddress): ?string
    {
        $candidate = trim((string) $ipAddress);
        if ($candidate === '') {
            return null;
        }

        return filter_var($candidate, FILTER_VALIDATE_IP) ? $candidate : null;
    }

    private function parseDateTimeValue(mixed $value): ?Carbon
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
