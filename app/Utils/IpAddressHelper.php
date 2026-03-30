<?php

namespace App\Utils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IpAddressHelper
{
    /**
     * Trusted proxy IPs.
     */
    private static array $trustedProxies = [
        '127.0.0.1',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
    ];

    /**
     * Get the real client IP address.
     */
    public static function getClientIp(Request $request): string
    {
        $ip = null;

        if ($cfIp = $request->header('CF-Connecting-IP')) {
            $ip = $cfIp;
        }

        if (! $ip) {
            $headers = [
                'HTTP_X_REAL_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR',
            ];

            foreach ($headers as $header) {
                $value = $request->server($header);

                if ($value) {
                    $ips = array_map('trim', explode(',', $value));
                    $ip = $ips[0];
                    break;
                }
            }
        }

        if ($ip && ! self::isValidIp($ip)) {
            $ip = $request->ip();
        }

        if ($ip) {
            self::logIpDetection($request, $ip);
        }

        return $ip ?? '0.0.0.0';
    }

    /**
     * Validate IP address.
     */
    public static function isValidIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * Placeholder VPN check.
     */
    public static function isLikelyVpn(string $ip): bool
    {
        return false;
    }

    /**
     * Get basic IP metadata.
     */
    public static function getIpMetadata(string $ip): array
    {
        return [
            'ip' => $ip,
            'country' => null,
            'region' => null,
            'city' => null,
            'isp' => null,
            'is_vpn' => self::isLikelyVpn($ip),
            'latitude' => null,
            'longitude' => null,
            'timezone' => null,
        ];
    }

    /**
     * Log proxy-related IP detection.
     */
    private static function logIpDetection(Request $request, string $detectedIp): void
    {
        $remoteAddr = $request->server('REMOTE_ADDR');

        if ($detectedIp !== $remoteAddr) {
            Log::channel('security')->info('Proxy/VPN detected', [
                'detected_ip' => $detectedIp,
                'remote_addr' => $remoteAddr,
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'headers' => [
                    'x_forwarded_for' => $request->header('X-Forwarded-For'),
                    'x_real_ip' => $request->header('X-Real-IP'),
                    'cf_connecting_ip' => $request->header('CF-Connecting-IP'),
                ],
            ]);
        }
    }

    /**
     * Get a browser fingerprint from request headers.
     */
    public static function getBrowserFingerprint(Request $request): string
    {
        $components = [
            $request->userAgent() ?? '',
            $request->header('Accept-Language') ?? '',
            $request->header('Accept-Encoding') ?? '',
            $request->header('Accept') ?? '',
            $request->server('HTTP_DNT') ?? '',
            $request->header('Sec-CH-UA') ?? '',
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }

    /**
     * Check if request is from a trusted proxy.
     */
    public static function isTrustedProxy(string $ip): bool
    {
        foreach (self::$trustedProxies as $trusted) {
            if (str_contains($trusted, '/')) {
                if (self::ipInCidr($ip, $trusted)) {
                    return true;
                }
            } elseif ($ip === $trusted) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IPv4 address is within a CIDR range.
     */
    private static function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);

        return (ip2long($ip) & ~((1 << (32 - (int) $mask)) - 1))
            === (ip2long($subnet) & ~((1 << (32 - (int) $mask)) - 1));
    }
}
