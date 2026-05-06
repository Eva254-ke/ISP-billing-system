<?php

namespace App\Services\Radius;

use App\Models\Router;
use App\Models\UserSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class RadiusDisconnectService
{
    public function __construct(
        private readonly RadiusAccountingService $radiusAccountingService
    ) {}

    /**
     * @return array{
     *     success:bool,
     *     error:?string,
     *     nas_ip:?string,
     *     port:int,
     *     used_accounting_record:bool,
     *     attributes:array<string, string>
     * }
     */
    public function disconnect(UserSession $session): array
    {
        $router = $session->router;
        $accountingRecord = $this->radiusAccountingService->findOpenSessionForSession($session);

        $nasIp = $this->resolveNasIp($accountingRecord, $router);
        if ($nasIp === null) {
            return $this->failure($session, 'Unable to resolve the NAS IP for the disconnect request.', $accountingRecord);
        }

        $secret = $this->resolveSharedSecret($router);
        if ($secret === '') {
            return $this->failure($session, 'RADIUS disconnect shared secret is empty.', $accountingRecord, $nasIp);
        }

        $binary = trim((string) config('radius.disconnect_binary', 'radclient'));
        if ($binary === '') {
            return $this->failure($session, 'RADIUS disconnect binary is not configured.', $accountingRecord, $nasIp);
        }

        $port = max(1, (int) config('radius.disconnect_port', 3799));
        $timeout = max(1, (int) config('radius.disconnect_timeout', 5));
        $attributes = $this->buildAttributes($session, $accountingRecord, $nasIp);
        if (!$this->hasTargetingAttributes($attributes)) {
            return $this->failure(
                $session,
                'Cannot issue a RADIUS disconnect without a username, Acct-Session-Id, MAC, or IP.',
                $accountingRecord,
                $nasIp
            );
        }

        $payload = $this->buildPayload($attributes);
        $username = $attributes['User-Name'] ?? trim((string) $session->username);

        // Determine source IP for RADIUS disconnect
        $sourceIp = $this->resolveSourceIp($router);
        $commandArgs = [
            $binary,
            '-x',
            '-r',
            '1',
            '-t',
            (string) $timeout,
        ];
        
        // Add source IP binding if available
        if ($sourceIp !== null) {
            $commandArgs[] = '-S';
            $commandArgs[] = $sourceIp;
        }
        
        $commandArgs = array_merge($commandArgs, [
            "{$nasIp}:{$port}",
            'disconnect',
            $secret,
        ]);

        $result = Process::path(base_path())
            ->timeout($timeout + 5)
            ->input($payload)
            ->run($commandArgs);

        $combinedOutput = trim($result->output() . "\n" . $result->errorOutput());
        $success = $result->successful() && !$this->looksLikeNak($combinedOutput);

        if ($success) {
            Log::channel('radius')->info('RADIUS disconnect acknowledged', [
                'session_id' => $session->id,
                'username' => $username,
                'nas_ip' => $nasIp,
                'port' => $port,
                'used_accounting_record' => $accountingRecord !== null,
                'attribute_names' => array_keys($attributes),
            ]);

            return [
                'success' => true,
                'error' => null,
                'nas_ip' => $nasIp,
                'port' => $port,
                'used_accounting_record' => $accountingRecord !== null,
                'attributes' => $attributes,
            ];
        }

        $error = $combinedOutput !== ''
            ? $combinedOutput
            : 'radclient returned a non-success result.';

        Log::channel('radius')->warning('RADIUS disconnect failed', [
            'session_id' => $session->id,
            'username' => $username,
            'nas_ip' => $nasIp,
            'port' => $port,
            'used_accounting_record' => $accountingRecord !== null,
            'attribute_names' => array_keys($attributes),
            'exit_code' => $result->exitCode(),
            'error' => $error,
        ]);

        // Try fallback MikroTik API disconnect if enabled
        $fallbackResult = ['success' => false, 'error' => 'Fallback disabled'];
        if (config('radius.disconnect_fallback_to_api', true)) {
            $fallbackResult = $this->attemptMikrotikDisconnect($session, $router);
        }
        
        if ($fallbackResult['success']) {
            Log::channel('radius')->info('Fallback MikroTik disconnect succeeded', [
                'session_id' => $session->id,
                'username' => $username,
                'nas_ip' => $nasIp,
                'fallback_method' => 'mikrotik_api',
            ]);
            
            return [
                'success' => true,
                'error' => null,
                'nas_ip' => $nasIp,
                'port' => $port,
                'used_accounting_record' => $accountingRecord !== null,
                'attributes' => $attributes,
                'fallback_used' => 'mikrotik_api',
            ];
        }

        Log::channel('radius')->warning('Both RADIUS and MikroTik disconnect failed', [
            'session_id' => $session->id,
            'username' => $username,
            'nas_ip' => $nasIp,
            'radius_error' => $error,
            'mikrotik_error' => $fallbackResult['error'] ?? 'Unknown',
        ]);

        return [
            'success' => false,
            'error' => $error . ' | MikroTik fallback: ' . ($fallbackResult['error'] ?? 'Unknown'),
            'nas_ip' => $nasIp,
            'port' => $port,
            'used_accounting_record' => $accountingRecord !== null,
            'attributes' => $attributes,
            'fallback_attempted' => true,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $accountingRecord
     * @return array<string, string>
     */
    private function buildAttributes(UserSession $session, ?array $accountingRecord, string $nasIp): array
    {
        $record = $accountingRecord ?? [];
        $radiusMetadata = $this->sessionRadiusMetadata($session);

        return array_filter([
            'User-Name' => $this->cleanValue(
                $record['username']
                ?? $radiusMetadata['active_username']
                ?? $radiusMetadata['username']
                ?? $session->username
            ),
            'Acct-Session-Id' => $this->cleanValue(
                $record['acctsessionid']
                ?? $radiusMetadata['acct_session_id']
                ?? null
            ),
            'Acct-Unique-Session-Id' => $this->cleanValue(
                $record['acctuniqueid']
                ?? $radiusMetadata['acct_unique_session_id']
                ?? null
            ),
            'Calling-Station-Id' => $this->cleanValue(
                $record['callingstationid']
                ?? $radiusMetadata['calling_station_id']
                ?? $session->mac_address
            ),
            'Framed-IP-Address' => $this->normalizeIpAddress(
                $record['framedipaddress']
                ?? $radiusMetadata['framed_ip_address']
                ?? $session->ip_address
            ),
            'NAS-IP-Address' => $this->normalizeIpAddress(
                $record['nasipaddress']
                ?? $radiusMetadata['nas_ip_address']
                ?? $nasIp
            ) ?? $nasIp,
            'NAS-Port-Id' => $this->cleanValue($record['nasportid'] ?? null),
            'NAS-Port-Type' => $this->cleanValue($record['nasporttype'] ?? null),
            'Class' => $this->cleanValue($record['class'] ?? null),
        ], static fn (?string $value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function buildPayload(array $attributes): string
    {
        $lines = [];

        foreach ($attributes as $attribute => $value) {
            $lines[] = sprintf('%s = "%s"', $attribute, addcslashes($value, "\\\""));
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param  array<string, mixed>|null  $accountingRecord
     */
    private function resolveNasIp(?array $accountingRecord, ?Router $router): ?string
    {
        $nasIp = $this->normalizeIpAddress($accountingRecord['nasipaddress'] ?? null);
        if ($nasIp !== null) {
            return $nasIp;
        }

        return $this->normalizeIpAddress($router?->ip_address);
    }

    private function resolveSourceIp(?Router $router): ?string
    {
        // Try configured source IP first
        $sourceIp = trim((string) config('radius.disconnect_source_ip', ''));
        if ($sourceIp !== '' && filter_var($sourceIp, FILTER_VALIDATE_IP)) {
            return $sourceIp;
        }

        // Try to get the server's IP that can reach the NAS
        $serverIp = trim((string) config('radius.server_ip', ''));
        if ($serverIp !== '' && filter_var($serverIp, FILTER_VALIDATE_IP)) {
            return $serverIp;
        }

        // Fallback: try to determine the best source IP based on NAS IP
        $nasIp = $this->normalizeIpAddress($router?->ip_address);
        if ($nasIp !== null) {
            // If NAS is private, use a private source IP
            if (filter_var($nasIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                // Public NAS - use public server IP or first available interface
                return $this->getServerPublicIp() ?? null;
            } else {
                // Private NAS - use appropriate private network IP
                return $this->getServerPrivateIp($nasIp) ?? null;
            }
        }

        return null;
    }

    private function getServerPublicIp(): ?string
    {
        // Try to get the server's public IP from configuration or interface
        $publicIp = trim((string) env('SERVER_PUBLIC_IP', ''));
        if ($publicIp !== '' && filter_var($publicIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $publicIp;
        }

        // Fallback: try to detect from network interfaces
        // This is a simple implementation - you might want to improve this
        return null;
    }

    private function getServerPrivateIp(string $targetNetwork): ?string
    {
        // Simple logic to determine which private IP to use based on target
        // You might want to enhance this based on your network topology
        if (str_starts_with($targetNetwork, '192.168.')) {
            return '192.168.1.1'; // Adjust based on your network
        }
        if (str_starts_with($targetNetwork, '10.')) {
            return '10.0.0.1'; // Adjust based on your network
        }
        if (str_starts_with($targetNetwork, '172.')) {
            return '172.16.0.1'; // Adjust based on your network
        }
        
        return null;
    }

    private function resolveSharedSecret(?Router $router): string
    {
        $routerSecret = trim((string) ($router?->radius_secret ?? ''));
        if ($routerSecret !== '') {
            return $routerSecret;
        }

        return trim((string) config('radius.disconnect_secret', config('radius.shared_secret', '')));
    }

    private function looksLikeNak(string $output): bool
    {
        $normalized = strtoupper($output);

        return str_contains($normalized, 'DISCONNECT-NAK') || str_contains($normalized, 'COA-NAK');
    }

    private function cleanValue(mixed $value): ?string
    {
        $candidate = trim((string) $value);

        return $candidate === '' ? null : $candidate;
    }

    private function normalizeIpAddress(mixed $value): ?string
    {
        $candidate = trim((string) $value);
        if ($candidate === '') {
            return null;
        }

        return filter_var($candidate, FILTER_VALIDATE_IP) ? $candidate : null;
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function hasTargetingAttributes(array $attributes): bool
    {
        foreach (['User-Name', 'Acct-Session-Id', 'Calling-Station-Id', 'Framed-IP-Address'] as $attribute) {
            if (($attributes[$attribute] ?? '') !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Attempt MikroTik API disconnect as fallback
     */
    private function attemptMikrotikDisconnect(UserSession $session, ?Router $router): array
    {
        if (!$router) {
            return ['success' => false, 'error' => 'Router not available for MikroTik fallback'];
        }

        try {
            $mikrotikService = new \App\Services\MikroTik\MikroTikService();
            
            // Try to disconnect by MAC address first
            if ($session->mac_address) {
                $result = $mikrotikService->removeHotspotUser($router, $session->mac_address);
                if ($result) {
                    return ['success' => true, 'method' => 'mac_address'];
                }
            }

            // Try to disconnect by IP address
            if ($session->ip_address) {
                $result = $mikrotikService->removeHotspotUserByIp($router, $session->ip_address);
                if ($result) {
                    return ['success' => true, 'method' => 'ip_address'];
                }
            }

            // Try to disconnect by username
            if ($session->username) {
                $result = $mikrotikService->removeHotspotUserByUsername($router, $session->username);
                if ($result) {
                    return ['success' => true, 'method' => 'username'];
                }
            }

            return ['success' => false, 'error' => 'No valid identifier for MikroTik disconnect'];

        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'MikroTik API error: ' . $e->getMessage()];
        }
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
     * @param  array<string, mixed>|null  $accountingRecord
     * @return array{
     *     success:false,
     *     error:string,
     *     nas_ip:?string,
     *     port:int,
     *     used_accounting_record:bool,
     *     attributes:array<string, string>
     * }
     */
    private function failure(
        UserSession $session,
        string $error,
        ?array $accountingRecord = null,
        ?string $nasIp = null
    ): array {
        Log::channel('radius')->warning('RADIUS disconnect could not be prepared', [
            'session_id' => $session->id,
            'username' => $session->username,
            'nas_ip' => $nasIp,
            'used_accounting_record' => $accountingRecord !== null,
            'error' => $error,
        ]);

        return [
            'success' => false,
            'error' => $error,
            'nas_ip' => $nasIp,
            'port' => max(1, (int) config('radius.disconnect_port', 3799)),
            'used_accounting_record' => $accountingRecord !== null,
            'attributes' => [],
        ];
    }
}
