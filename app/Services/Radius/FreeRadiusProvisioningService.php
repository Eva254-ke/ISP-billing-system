<?php

namespace App\Services\Radius;

use App\Models\Package;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FreeRadiusProvisioningService
{
    public function __construct(
        private ?RadiusIdentityResolver $identityResolver = null
    ) {}

    public function provisionUser(
        string $username,
        string $password,
        Package $package,
        ?\DateTimeInterface $expiresAt = null,
        ?string $callingStationId = null
    ): void
    {
        if (trim($username) === '' || trim($password) === '') {
            throw new \InvalidArgumentException('RADIUS username/password cannot be empty.');
        }

        $connection = (string) config('radius.db_connection', 'radius');
        $radcheck = (string) config('radius.tables.radcheck', 'radcheck');
        $radreply = (string) config('radius.tables.radreply', 'radreply');
        $cleartextPasswordAttribute = (string) config('radius.attributes.cleartext_password', 'Cleartext-Password');
        $callingStationIdAttribute = (string) config('radius.attributes.calling_station_id', 'Calling-Station-Id');
        $expirationAttribute = (string) config('radius.attributes.expiration', 'Expiration');
        $sessionTimeoutAttribute = (string) config('radius.attributes.session_timeout', 'Session-Timeout');
        $rateLimitAttribute = (string) config('radius.attributes.rate_limit', 'Mikrotik-Rate-Limit');
        $simultaneousUseAttribute = (string) config('radius.attributes.simultaneous_use', 'Simultaneous-Use');
        $simultaneousUse = max(1, (int) config('radius.simultaneous_use', 1));
        $normalizedCallingStationId = $this->resolveIdentityResolver()->normalizeMacAddress($callingStationId);

        $sessionTimeout = max(60, (int) $package->duration_in_minutes * 60);
        $rateLimit = $this->buildMikrotikRateLimit($package);
        $db = DB::connection($connection);

        $db->transaction(function () use ($db, $radcheck, $radreply, $username, $password, $sessionTimeout, $rateLimit, $simultaneousUse, $expiresAt, $normalizedCallingStationId, $cleartextPasswordAttribute, $callingStationIdAttribute, $expirationAttribute, $sessionTimeoutAttribute, $rateLimitAttribute, $simultaneousUseAttribute) {
            // Reset profile rows for idempotent provisioning
            $db
                ->table($radcheck)
                ->where('username', $username)
                ->whereIn('attribute', [$cleartextPasswordAttribute, $callingStationIdAttribute, $expirationAttribute, $simultaneousUseAttribute])
                ->delete();

            $db
                ->table($radreply)
                ->where('username', $username)
                ->whereIn('attribute', [$sessionTimeoutAttribute, $rateLimitAttribute])
                ->delete();

            $db
                ->table($radcheck)
                ->insert([
                    [
                        'username' => $username,
                        'attribute' => $cleartextPasswordAttribute,
                        'op' => ':=',
                        'value' => $password,
                    ],
                    [
                        'username' => $username,
                        'attribute' => $simultaneousUseAttribute,
                        'op' => ':=',
                        'value' => (string) $simultaneousUse,
                    ],
                ]);

            if ($normalizedCallingStationId !== null) {
                $db
                    ->table($radcheck)
                    ->insert([
                        'username' => $username,
                        'attribute' => $callingStationIdAttribute,
                        'op' => '==',
                        'value' => $normalizedCallingStationId,
                    ]);
            }

            if ($expiresAt) {
                $db
                    ->table($radcheck)
                    ->insert([
                        'username' => $username,
                        'attribute' => $expirationAttribute,
                        'op' => ':=',
                        'value' => $expiresAt->format('d M Y H:i:s'),
                    ]);
            }

            $db
                ->table($radreply)
                ->insert([
                    [
                        'username' => $username,
                        'attribute' => $sessionTimeoutAttribute,
                        'op' => ':=',
                        'value' => (string) $sessionTimeout,
                    ],
                    [
                        'username' => $username,
                        'attribute' => $rateLimitAttribute,
                        'op' => ':=',
                        'value' => $rateLimit,
                    ],
                ]);
        });

        Log::channel('radius')->info('Provisioned FreeRADIUS user profile', [
            'username' => $username,
            'package_id' => $package->id,
            'package_name' => $package->name,
            'session_timeout_seconds' => $sessionTimeout,
            'rate_limit' => $rateLimit,
            'simultaneous_use' => $simultaneousUse,
            'calling_station_id' => $normalizedCallingStationId,
            'expires_at' => $expiresAt?->format(\DateTimeInterface::ATOM),
            'connection' => $connection,
        ]);
    }

    public function revokeUser(string $username): void
    {
        $username = trim($username);
        if ($username === '') {
            throw new \InvalidArgumentException('RADIUS username cannot be empty.');
        }

        $connection = (string) config('radius.db_connection', 'radius');
        $radcheck = (string) config('radius.tables.radcheck', 'radcheck');
        $radreply = (string) config('radius.tables.radreply', 'radreply');
        $db = DB::connection($connection);

        [$radcheckDeleted, $radreplyDeleted] = $db->transaction(function () use ($db, $radcheck, $radreply, $username): array {
            $radcheckDeleted = $db
                ->table($radcheck)
                ->where('username', $username)
                ->delete();

            $radreplyDeleted = $db
                ->table($radreply)
                ->where('username', $username)
                ->delete();

            return [$radcheckDeleted, $radreplyDeleted];
        });

        Log::channel('radius')->info('Revoked FreeRADIUS user profile', [
            'username' => $username,
            'connection' => $connection,
            'radcheck_deleted' => $radcheckDeleted,
            'radreply_deleted' => $radreplyDeleted,
        ]);
    }

    private function buildMikrotikRateLimit(Package $package): string
    {
        $down = (int) ($package->download_limit_mbps ?? 0);
        $up = (int) ($package->upload_limit_mbps ?? 0);

        $downValue = $down > 0 ? $down . 'M' : '100M';
        $upValue = $up > 0 ? $up . 'M' : '100M';

        return $upValue . '/' . $downValue;
    }

    private function resolveIdentityResolver(): RadiusIdentityResolver
    {
        return $this->identityResolver ??= app(RadiusIdentityResolver::class);
    }
}
