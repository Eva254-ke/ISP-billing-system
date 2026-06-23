<?php

namespace App\Console\Commands;

use App\Models\Package;
use App\Services\Radius\FreeRadiusProvisioningService;
use Illuminate\Console\Command;

class RadiusProvisionTest extends Command
{
    protected $signature = 'radius:provision-test
        {username : RADIUS username to provision}
        {--password= : Optional password (defaults to username)}
        {--package_id= : Package ID to use for timeout/rate-limit mapping}';

    protected $description = 'Provision a test user into FreeRADIUS radcheck/radreply tables.';

    public function handle(FreeRadiusProvisioningService $service): int
    {
        if (!(bool) config('radius.enabled', false)) {
            $this->error('RADIUS_ENABLED is false. Enable it in .env first.');
            return self::FAILURE;
        }

        $packageId = (int) ($this->option('package_id') ?? 0);
        if ($packageId <= 0) {
            $this->error('--package_id is required and must be a valid package id.');
            return self::FAILURE;
        }

        $package = Package::find($packageId);
        if (!$package) {
            $this->error('Package not found for id: ' . $packageId);
            return self::FAILURE;
        }

        $username = trim((string) $this->argument('username'));
        $password = trim((string) ($this->option('password') ?? $username));

        if ($username === '') {
            $this->error('Username cannot be empty.');
            return self::FAILURE;
        }

        try {
            $expiresAt = now()->addMinutes((int) $package->duration_in_minutes);

            $service->provisionUser(
                username: $username,
                password: $password,
                package: $package,
                expiresAt: $expiresAt,
            );

            $this->info('FreeRADIUS provisioning successful.');
            $this->line('Username: ' . $username);
            $this->line('Package: ' . $package->name . ' (#' . $package->id . ')');
            $this->line('Expires: ' . $expiresAt->toDateTimeString());

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Provisioning failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
