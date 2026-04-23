<?php

namespace App\Services\Radius;

class RadiusIdentityResolver
{
    public const ACCESS_MODE_PHONE = 'phone';
    public const ACCESS_MODE_MAC = 'mac';

    public function configuredAccessMode(): string
    {
        $mode = strtolower(trim((string) config('radius.access_mode', self::ACCESS_MODE_PHONE)));

        return in_array($mode, [self::ACCESS_MODE_PHONE, self::ACCESS_MODE_MAC], true)
            ? $mode
            : self::ACCESS_MODE_PHONE;
    }

    /**
     * @return array{
     *     access_mode:string,
     *     identity_type:string,
     *     fallback_used:bool,
     *     username:string,
     *     password:string,
     *     mac_address:?string
     * }
     */
    public function resolve(?string $phone, ?int $paymentId = null, ?string $macAddress = null): array
    {
        $accessMode = $this->configuredAccessMode();
        $normalizedMac = $this->normalizeMacAddress($macAddress);

        if ($accessMode === self::ACCESS_MODE_MAC && $normalizedMac !== null) {
            return [
                'access_mode' => $accessMode,
                'identity_type' => 'mac',
                'fallback_used' => false,
                'username' => $normalizedMac,
                'password' => $normalizedMac,
                'mac_address' => $normalizedMac,
            ];
        }

        return [
            'access_mode' => $accessMode,
            'identity_type' => 'phone',
            'fallback_used' => $accessMode === self::ACCESS_MODE_MAC,
            'username' => $this->resolvePhoneIdentity($phone, $paymentId),
            'password' => $this->resolvePhoneIdentity($phone, $paymentId),
            'mac_address' => $normalizedMac,
        ];
    }

    /**
     * @param  array<string, mixed>  $identity
     */
    public function shouldBypassRouterActivation(array $identity): bool
    {
        return (bool) config('radius.enabled', false)
            && ($identity['access_mode'] ?? null) === self::ACCESS_MODE_MAC
            && ($identity['identity_type'] ?? null) === 'mac'
            && trim((string) ($identity['username'] ?? '')) !== '';
    }

    /**
     * Pure RADIUS mode skips RouterOS API login/disconnect orchestration and lets
     * hotspot form auth plus RADIUS accounting own the session lifecycle.
     *
     * @param  array<string, mixed>  $identity
     */
    public function shouldUsePureRadiusFlow(array $identity): bool
    {
        return (bool) config('radius.enabled', false)
            && (bool) config('radius.pure_radius', false)
            && trim((string) ($identity['username'] ?? '')) !== '';
    }

    public function matchesMacIdentity(?string $username, ?string $macAddress): bool
    {
        $normalizedUsername = $this->normalizeMacAddress($username);
        $normalizedMac = $this->normalizeMacAddress($macAddress);

        return $normalizedUsername !== null
            && $normalizedMac !== null
            && $normalizedUsername === $normalizedMac;
    }

    public function normalizeMacAddress(?string $macAddress): ?string
    {
        $normalized = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', (string) $macAddress) ?? '');
        if (strlen($normalized) !== 12) {
            return null;
        }

        return implode(':', str_split($normalized, 2));
    }

    private function resolvePhoneIdentity(?string $phone, ?int $paymentId = null): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits !== '') {
            return 'cb' . $digits;
        }

        return 'cbu' . (int) $paymentId;
    }
}
