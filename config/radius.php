<?php

return [
    'enabled' => env('RADIUS_ENABLED', false),
    'access_mode' => strtolower((string) env('RADIUS_ACCESS_MODE', 'phone')),
    // When RADIUS is enabled, default to RADIUS-managed session lifecycle unless
    // a deployment explicitly opts back into RouterOS API activation.
    'pure_radius' => (bool) env('RADIUS_PURE_RADIUS', env('RADIUS_ENABLED', false)),
    'portal_auto_login' => (bool) env('RADIUS_PORTAL_AUTO_LOGIN', true),
    // Allow some time for captive clients to re-open the hotspot portal and
    // complete the first RADIUS login before package time starts counting down.
    'pending_login_window_minutes' => max(1, (int) env('RADIUS_PENDING_LOGIN_WINDOW_MINUTES', 360)),

    // Router RADIUS client target
    'server_ip' => env('RADIUS_SERVER_IP', '127.0.0.1'),
    'auth_port' => (int) env('RADIUS_AUTH_PORT', 1812),
    'acct_port' => (int) env('RADIUS_ACCT_PORT', 1813),
    'timeout' => max(1, (int) env('RADIUS_TIMEOUT', 5)),
    'shared_secret' => env('RADIUS_SHARED_SECRET', ''),
    'disconnect_port' => (int) env('RADIUS_DISCONNECT_PORT', 3799),
    'disconnect_timeout' => max(1, (int) env('RADIUS_DISCONNECT_TIMEOUT', 5)),
    'disconnect_secret' => env('RADIUS_DISCONNECT_SECRET', env('RADIUS_SHARED_SECRET', '')),
    'disconnect_binary' => env('RADIUS_DISCONNECT_BINARY', 'radclient'),

    // FreeRADIUS SQL connection name from config/database.php
    'db_connection' => env('RADIUS_DB_CONNECTION', 'radius'),

    // Standard table names from FreeRADIUS SQL schema
    'tables' => [
        'radcheck' => env('RADIUS_TABLE_RADCHECK', 'radcheck'),
        'radreply' => env('RADIUS_TABLE_RADREPLY', 'radreply'),
        'radacct' => env('RADIUS_TABLE_RADACCT', 'radacct'),
        'radpostauth' => env('RADIUS_TABLE_RADPOSTAUTH', 'radpostauth'),
        'nasreload' => env('RADIUS_TABLE_NASRELOAD', 'nasreload'),
    ],

    // Attribute defaults used during provisioning
    'attributes' => [
        'cleartext_password' => 'Cleartext-Password',
        'calling_station_id' => 'Calling-Station-Id',
        'expiration' => 'Expiration',
        'session_timeout' => 'Session-Timeout',
        'rate_limit' => 'Mikrotik-Rate-Limit',
        'simultaneous_use' => 'Simultaneous-Use',
    ],

    'simultaneous_use' => max(1, (int) env('RADIUS_SIMULTANEOUS_USE', 1)),
];
