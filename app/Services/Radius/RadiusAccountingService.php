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
        return $this->findBestOpenSession(
            usernames: $this->uniqueNonEmptyStrings([$username]),
            acctSessionIds: [],
            macAddress: $this->identityResolver->normalizeMacAddress($macAddress),
            ipAddress: $this->normalizeIpAddress($ipAddress),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOpenSessionForSession(UserSession $session): ?array
    {
        $radiusMetadata = $this->sessionRadiusMetadata($session);

        return $this->findBestOpenSession(
            usernames: $this->uniqueNonEmptyStrings([
                $radiusMetadata['active_username'] ?? null,
                $radiusMetadata['username'] ?? null,
                $session->username,
            ]),
            acctSessionIds: $this->uniqueNonEmptyStrings([
                $radiusMetadata['acct_session_id'] ?? null,
            ]),
            macAddress: $this->identityResolver->normalizeMacAddress(
                $radiusMetadata['calling_station_id'] ?? $session->mac_address
            ),
            ipAddress: $this->normalizeIpAddress(
                $radiusMetadata['framed_ip_address'] ?? $session->ip_address
            ),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function syncActiveSession(UserSession $session): ?array
    {
        $record = $this->findOpenSessionForSession($session);

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
            'metadata' => $this->mergeRadiusMetadata($session, [
                'active_username' => $this->cleanValue($record['username'] ?? null),
                'acct_session_id' => $this->cleanValue($record['acctsessionid'] ?? null),
                'acct_unique_session_id' => $this->cleanValue($record['acctuniqueid'] ?? null),
                'calling_station_id' => $normalizedMac,
                'framed_ip_address' => $normalizedIp,
                'nas_ip_address' => $this->normalizeIpAddress($record['nasipaddress'] ?? null),
                'last_accounting_sync_at' => now()->toIso8601String(),
            ]),
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
     * @param  array<string>  $usernames
     * @param  array<string>  $acctSessionIds
     * @return array<string, mixed>|null
     */
    private function findBestOpenSession(
        array $usernames,
        array $acctSessionIds,
        ?string $macAddress,
        ?string $ipAddress
    ): ?array {
        if ($usernames === [] && $acctSessionIds === [] && $macAddress === null && $ipAddress === null) {
            return null;
        }

        $connection = (string) config('radius.db_connection', 'radius');
        $table = (string) config('radius.tables.radacct', 'radacct');

        $records = DB::connection($connection)
            ->table($table)
            ->whereNull('acctstoptime')
            ->where(function ($query) use ($usernames, $acctSessionIds, $macAddress, $ipAddress): void {
                if ($acctSessionIds !== []) {
                    $query->orWhereIn('acctsessionid', $acctSessionIds);
                }

                if ($usernames !== []) {
                    $query->orWhereIn('username', $usernames);
                }

                if ($macAddress !== null) {
                    $query->orWhere('callingstationid', $macAddress);
                }

                if ($ipAddress !== null) {
                    $query->orWhere('framedipaddress', $ipAddress);
                }
            })
            ->orderByDesc('acctupdatetime')
            ->orderByDesc('acctstarttime')
            ->limit(10)
            ->get();

        $bestMatch = null;
        $bestScore = 0;

        foreach ($records as $record) {
            $row = (array) $record;
            $score = $this->scoreRecord($row, $usernames, $acctSessionIds, $macAddress, $ipAddress);

            if ($score <= 0 || $score <= $bestScore) {
                continue;
            }

            $bestMatch = $row;
            $bestScore = $score;
        }

        return $bestMatch;
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

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string>  $usernames
     * @param  array<string>  $acctSessionIds
     */
    private function scoreRecord(
        array $record,
        array $usernames,
        array $acctSessionIds,
        ?string $macAddress,
        ?string $ipAddress
    ): int {
        $score = 0;

        $recordSessionId = $this->cleanValue($record['acctsessionid'] ?? null);
        $recordUsername = $this->cleanValue($record['username'] ?? null);
        $recordMac = $this->identityResolver->normalizeMacAddress($record['callingstationid'] ?? null);
        $recordIp = $this->normalizeIpAddress($record['framedipaddress'] ?? null);

        if ($recordSessionId !== null && in_array($recordSessionId, $acctSessionIds, true)) {
            $score += 100;
        }

        if ($recordMac !== null && $macAddress !== null && $recordMac === $macAddress) {
            $score += 60;
        }

        if ($recordIp !== null && $ipAddress !== null && $recordIp === $ipAddress) {
            $score += 50;
        }

        if ($recordUsername !== null && in_array($recordUsername, $usernames, true)) {
            $score += 25;
        }

        if (
            $score === 25
            && ($macAddress !== null || $ipAddress !== null || $acctSessionIds !== [])
            && !$this->matchesRecord($record, $macAddress, $ipAddress)
        ) {
            return 0;
        }

        return $score;
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

    private function cleanValue(mixed $value): ?string
    {
        $candidate = trim((string) $value);

        return $candidate === '' ? null : $candidate;
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<string>
     */
    private function uniqueNonEmptyStrings(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            $candidate = $this->cleanValue($value);
            if ($candidate === null) {
                continue;
            }

            $normalized[$candidate] = true;
        }

        return array_keys($normalized);
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionRadiusMetadata(UserSession $session): array
    {
        $metadata = is_array($session->metadata) ? $session->metadata : [];
        $radius = $metadata['radius'] ?? null;

        return is_array($radius) ? $radius : [];
    }

    /**
     * @param  array<string, mixed>  $radiusMetadata
     * @return array<string, mixed>
     */
    private function mergeRadiusMetadata(UserSession $session, array $radiusMetadata): array
    {
        $metadata = is_array($session->metadata) ? $session->metadata : [];
        $existingRadius = is_array($metadata['radius'] ?? null) ? $metadata['radius'] : [];

        $metadata['radius'] = array_merge(
            $existingRadius,
            array_filter(
                $radiusMetadata,
                static fn (mixed $value): bool => $value !== null && $value !== ''
            )
        );

        return $metadata;
    }
}
