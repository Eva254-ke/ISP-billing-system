<?php

namespace Tests\Feature;

use App\Models\Router;
use App\Services\MikroTik\MikroTikService;
use Tests\TestCase;

class MikroTikConnectivityDiagnosticsTest extends TestCase
{
    public function test_diagnostics_include_endpoint_and_private_ip_hints_for_remote_deployments(): void
    {
        config([
            'radius.enabled' => false,
        ]);

        $router = new Router([
            'name' => 'Main Hotspot',
            'ip_address' => '192.168.88.1',
            'api_port' => 8728,
            'api_ssl' => false,
            'status' => Router::STATUS_OFFLINE,
            'metadata' => [
                'last_connectivity_result' => 'tcp_unreachable',
                'last_connectivity_error' => 'Connection refused',
                'last_connectivity_error_type' => 'network_error',
                'api_port_reachable' => false,
            ],
        ]);

        $diagnostics = app(MikroTikService::class)->getConnectivityDiagnostics($router);

        $this->assertSame('api', $diagnostics['endpoint']['service']);
        $this->assertSame(8728, $diagnostics['endpoint']['port']);
        $this->assertNotEmpty($diagnostics['hints']);
        $this->assertTrue($this->containsHintFragment($diagnostics['hints'], '192.168.88.1'));
        $this->assertTrue($this->containsHintFragment($diagnostics['hints'], 'DigitalOcean'));
        $this->assertTrue($this->containsHintFragment($diagnostics['hints'], '/ip service api'));
    }

    public function test_diagnostics_include_loopback_radius_hint_for_remote_router_targets(): void
    {
        config([
            'radius.enabled' => true,
            'radius.server_ip' => '127.0.0.1',
        ]);

        $router = new Router([
            'name' => 'Edge Router',
            'ip_address' => '197.248.188.10',
            'api_port' => 8728,
            'api_ssl' => false,
            'status' => Router::STATUS_WARNING,
            'metadata' => [
                'last_connectivity_result' => 'api_failed_tcp_ok',
                'last_connectivity_error' => 'Socket timeout reached',
                'last_connectivity_error_type' => 'api_timeout',
                'api_port_reachable' => true,
            ],
        ]);

        $diagnostics = app(MikroTikService::class)->getConnectivityDiagnostics($router);

        $this->assertTrue($this->containsHintFragment($diagnostics['hints'], 'RADIUS_SERVER_IP is set to 127.0.0.1'));
        $this->assertTrue($this->containsHintFragment($diagnostics['hints'], 'reachable public or VPN IP'));
        $this->assertTrue($this->containsHintFragment($diagnostics['hints'], 'plain API'));
    }

    private function containsHintFragment(array $hints, string $fragment): bool
    {
        foreach ($hints as $hint) {
            if (str_contains((string) $hint, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
