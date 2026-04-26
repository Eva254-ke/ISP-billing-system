<?php

namespace Tests\Unit;

use App\Models\Router;
use App\Models\UserSession;
use App\Services\Radius\RadiusAccountingService;
use App\Services\Radius\RadiusDisconnectService;
use Illuminate\Support\Facades\Process;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class RadiusDisconnectServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_disconnect_falls_back_to_session_context_when_accounting_record_is_missing(): void
    {
        config()->set('radius.disconnect_binary', 'radclient');
        config()->set('radius.disconnect_port', 3799);
        config()->set('radius.disconnect_timeout', 5);
        config()->set('radius.disconnect_secret', 'testing123');

        Process::fake([
            '*' => Process::result('Received Disconnect-ACK', '', 0),
        ]);

        $accountingService = Mockery::mock(RadiusAccountingService::class);
        $accountingService->shouldReceive('findOpenSessionForSession')
            ->once()
            ->with(Mockery::type(UserSession::class))
            ->andReturn(null);

        $service = new RadiusDisconnectService($accountingService);

        $router = new Router([
            'id' => 77,
            'ip_address' => '192.168.88.1',
            'radius_secret' => 'testing123',
        ]);

        $session = new UserSession([
            'id' => 501,
            'username' => 'cb0712345678p99',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'ip_address' => '10.0.0.25',
        ]);
        $session->setRelation('router', $router);

        $result = $service->disconnect($session);

        $this->assertTrue($result['success']);
        $this->assertSame('192.168.88.1', $result['nas_ip']);
        $this->assertFalse($result['used_accounting_record']);
        $this->assertSame('cb0712345678p99', $result['attributes']['User-Name'] ?? null);
        $this->assertSame('AA:BB:CC:DD:EE:FF', $result['attributes']['Calling-Station-Id'] ?? null);
        $this->assertSame('10.0.0.25', $result['attributes']['Framed-IP-Address'] ?? null);

        Process::assertRan(function ($process) {
            return is_array($process->command)
                && in_array('192.168.88.1:3799', $process->command, true)
                && str_contains((string) $process->input, 'User-Name = "cb0712345678p99"')
                && str_contains((string) $process->input, 'Calling-Station-Id = "AA:BB:CC:DD:EE:FF"')
                && str_contains((string) $process->input, 'Framed-IP-Address = "10.0.0.25"');
        });
    }

    public function test_disconnect_uses_stored_radius_metadata_when_runtime_session_context_is_missing(): void
    {
        config()->set('radius.disconnect_binary', 'radclient');
        config()->set('radius.disconnect_port', 3799);
        config()->set('radius.disconnect_timeout', 5);
        config()->set('radius.disconnect_secret', 'testing123');

        Process::fake([
            '*' => Process::result('Received Disconnect-ACK', '', 0),
        ]);

        $accountingService = Mockery::mock(RadiusAccountingService::class);
        $accountingService->shouldReceive('findOpenSessionForSession')
            ->once()
            ->with(Mockery::type(UserSession::class))
            ->andReturn(null);

        $service = new RadiusDisconnectService($accountingService);

        $router = new Router([
            'id' => 78,
            'ip_address' => '192.168.99.1',
            'radius_secret' => 'testing123',
        ]);

        $session = new UserSession([
            'id' => 777,
            'username' => '',
            'mac_address' => null,
            'ip_address' => null,
            'metadata' => [
                'radius' => [
                    'active_username' => 'cb0742939094p76',
                    'acct_session_id' => 'sess-777',
                    'calling_station_id' => 'AA:BB:CC:DD:EE:FF',
                    'framed_ip_address' => '10.0.0.25',
                ],
            ],
        ]);
        $session->setRelation('router', $router);

        $result = $service->disconnect($session);

        $this->assertTrue($result['success']);
        $this->assertSame('cb0742939094p76', $result['attributes']['User-Name'] ?? null);
        $this->assertSame('sess-777', $result['attributes']['Acct-Session-Id'] ?? null);
        $this->assertSame('AA:BB:CC:DD:EE:FF', $result['attributes']['Calling-Station-Id'] ?? null);
        $this->assertSame('10.0.0.25', $result['attributes']['Framed-IP-Address'] ?? null);

        Process::assertRan(function ($process) {
            return str_contains((string) $process->input, 'User-Name = "cb0742939094p76"')
                && str_contains((string) $process->input, 'Acct-Session-Id = "sess-777"')
                && str_contains((string) $process->input, 'Calling-Station-Id = "AA:BB:CC:DD:EE:FF"')
                && str_contains((string) $process->input, 'Framed-IP-Address = "10.0.0.25"');
        });
    }
}
