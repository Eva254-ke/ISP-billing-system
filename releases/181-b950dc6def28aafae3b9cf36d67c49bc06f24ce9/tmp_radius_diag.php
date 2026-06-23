<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$connection = config('radius.db_connection');

echo "radpostauth\n";
foreach (DB::connection($connection)->table(config('radius.tables.radpostauth'))->orderByDesc('authdate')->limit(10)->get() as $row) {
    echo ($row->authdate ?? '') . ' | ' . ($row->username ?? '') . ' | ' . ($row->reply ?? '') . PHP_EOL;
}

echo "radacct\n";
foreach (DB::connection($connection)->table(config('radius.tables.radacct'))->orderByDesc('radacctid')->limit(10)->get() as $row) {
    echo ($row->radacctid ?? '') . ' | ' . ($row->username ?? '') . ' | ' . ($row->acctstarttime ?? '') . ' | ' . ($row->acctstoptime ?? '') . ' | ' . ($row->framedipaddress ?? '') . ' | ' . ($row->callingstationid ?? '') . PHP_EOL;
}
