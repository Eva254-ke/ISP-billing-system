<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Tests\TestCase;

class RadiusHealthCheckTest extends TestCase
{
    /**
     * @var array<int, \Socket>
     */
    private array $reservedSockets = [];

    protected function tearDown(): void
    {
        foreach ($this->reservedSockets as $socket) {
            @socket_close($socket);
        }

        $this->reservedSockets = [];

        parent::tearDown();
    }

    public function test_radius_health_check_fails_when_local_udp_ports_are_unbound(): void
    {
        if (!function_exists('socket_create')) {
            $this->markTestSkipped('PHP sockets extension is not available.');
        }

        [$authPort] = $this->reserveUdpPort(false);
        [$acctPort] = $this->reserveUdpPort(false);

        $this->configureRadiusHealthCheck($authPort, $acctPort);

        $this->artisan('radius:health-check')
            ->expectsOutputToContain("No local process appears to be bound to the RADIUS auth UDP port {$authPort}")
            ->expectsOutputToContain("No local process appears to be bound to the RADIUS accounting UDP port {$acctPort}")
            ->assertExitCode(SymfonyCommand::FAILURE);
    }

    public function test_radius_health_check_passes_when_local_udp_ports_are_in_use(): void
    {
        if (!function_exists('socket_create')) {
            $this->markTestSkipped('PHP sockets extension is not available.');
        }

        [$authPort] = $this->reserveUdpPort(true);
        [$acctPort] = $this->reserveUdpPort(true);

        $this->configureRadiusHealthCheck($authPort, $acctPort);

        $this->artisan('radius:health-check')
            ->expectsOutputToContain('DB connectivity: OK')
            ->expectsOutputToContain('Radius health check passed.')
            ->assertExitCode(SymfonyCommand::SUCCESS);
    }

    private function configureRadiusHealthCheck(int $authPort, int $acctPort): void
    {
        config()->set('app.env', 'testing');
        config()->set('radius.enabled', true);
        config()->set('radius.shared_secret', 'test-radius-secret');
        config()->set('radius.server_ip', '127.0.0.1');
        config()->set('radius.auth_port', $authPort);
        config()->set('radius.acct_port', $acctPort);
        config()->set('radius.db_connection', 'radius');
        config()->set('database.connections.radius', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('radius');
        $schema = Schema::connection('radius');

        foreach (['radcheck', 'radreply', 'radacct'] as $table) {
            if ($schema->hasTable($table)) {
                $schema->drop($table);
            }
        }

        $schema->create('radcheck', function (Blueprint $table): void {
            $table->id();
            $table->string('username');
            $table->string('attribute');
            $table->string('op', 2)->nullable();
            $table->string('value')->nullable();
        });

        $schema->create('radreply', function (Blueprint $table): void {
            $table->id();
            $table->string('username');
            $table->string('attribute');
            $table->string('op', 2)->nullable();
            $table->string('value')->nullable();
        });

        $schema->create('radacct', function (Blueprint $table): void {
            $table->id();
            $table->string('username')->nullable();
        });
    }

    /**
     * @return array{0:int,1:?Socket}
     */
    private function reserveUdpPort(bool $keepOpen): array
    {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        $this->assertNotFalse($socket, 'Failed to create a UDP socket for the test.');

        $bound = socket_bind($socket, '127.0.0.1', 0);
        $this->assertTrue($bound, 'Failed to bind a UDP socket for the test.');

        $resolvedHost = null;
        $port = null;
        $resolved = socket_getsockname($socket, $resolvedHost, $port);
        $this->assertTrue($resolved, 'Failed to read the bound UDP socket port for the test.');

        if ($keepOpen) {
            $this->reservedSockets[] = $socket;
            return [(int) $port, $socket];
        }

        socket_close($socket);

        return [(int) $port, null];
    }
}
