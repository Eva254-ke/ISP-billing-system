<?php

return [
    'enabled' => env('RADIUS_ENABLED', false),
    'access_mode' => strtolower((string) env('RADIUS_ACCESS_MODE', 'phone')),
    'pure_radius' => (bool) env('RADIUS_PURE_RADIUS', false),
    'portal_auto_login' => (bool) env('RADIUS_PORTAL_AUTO_LOGIN', true),

    // Router RADIUS client target
    'server_ip' => env('RADIUS_SERVER_IP', '127.0.0.1'),
    'auth_port' => (int) env('RADIUS_AUTH_PORT', 1812),
    'acct_port' => (int) env('RADIUS_ACCT_PORT', 1813),
    'timeout' => max(1, (int) env('RADIUS_TIMEOUT', 5)),
    'shared_secret' => env('RADIUS_SHARED_SECRET', ''),

    // FreeRADIUS SQL connection name from config/database.php
    'db_connection' => env('RADIUS_DB_CONNECTION', 'radius'),

    // Standard table names from FreeRADIUS SQL schema
    'tables' => [
        'radcheck' => env('RADIUS_TABLE_RADCHECK', 'radcheck'),
        'radreply' => env('RADIUS_TABLE_RADREPLY', 'radreply'),
        'radacct' => env('RADIUS_TABLE_RADACCT', 'radacct'),
    ],

    // Attribute defaults used during provisioning
    'attributes' => [
        'cleartext_password' => 'Cleartext-Password',
        'expiration' => 'Expiration',
        'session_timeout' => 'Session-Timeout',
        'rate_limit' => 'Mikrotik-Rate-Limit',
        'simultaneous_use' => 'Simultaneous-Use',
    ],

    'simultaneous_use' => max(1, (int) env('RADIUS_SIMULTANEOUS_USE', 1)),
];
