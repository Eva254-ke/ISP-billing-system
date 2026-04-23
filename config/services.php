<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS, Safaricom Daraja, MikroTik and more. This file 
    | provides the de facto location for this type of information, allowing 
    | packages to have a conventional file to locate the various service 
    | credentials.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Email Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
        'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
        'from' => env('MAIL_FROM_ADDRESS', 'hello@cloudbridge.network'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
        'from' => env('MAIL_FROM_ADDRESS', 'hello@cloudbridge.network'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Services
    |--------------------------------------------------------------------------
    */

    'africas_talking' => [
        'username' => env('AFRICAS_TALKING_USERNAME'),
        'api_key' => env('AFRICAS_TALKING_API_KEY'),
        'from' => env('AFRICAS_TALKING_FROM', 'CloudBridge'),
        'sandbox' => env('AFRICAS_TALKING_SANDBOX', false),
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Services - SAFARICOM DARAJA
    |--------------------------------------------------------------------------
    |
    | Direct Safaricom STK support for captive portal STK Push.
    | Default transaction type is Buy Goods (Till): CustomerBuyGoodsOnline.
    |
    */

    'mpesa' => [
        'stk_provider' => env('MPESA_STK_PROVIDER', 'daraja'),
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'passkey' => env('MPESA_PASSKEY'),
        
        'business_shortcode' => env('MPESA_BUSINESS_SHORTCODE'), // Your Paybill/Till
        // Optional: when blank, captive portal generates the callback per tenant.
        'callback_url' => env('MPESA_CALLBACK_URL'),
        'transaction_type' => env('MPESA_TRANSACTION_TYPE', 'CustomerBuyGoodsOnline'),
        'timeout' => (int) env('MPESA_TIMEOUT', 30),
        // Keep SSL verification enabled in production. Set MPESA_CA_BUNDLE to a PEM bundle
        // if the host OS trust store is missing the Safaricom certificate chain.
        'verify_ssl' => env('MPESA_VERIFY_SSL', true),
        'ca_bundle' => env('MPESA_CA_BUNDLE'),
        // Optional override. Defaults to business shortcode when omitted.
        'partyb' => env('MPESA_PARTYB'),
        
        'env' => env('MPESA_ENV', 'live'),
        'sandbox_url' => 'https://sandbox.safaricom.co.ke',
        'live_url' => 'https://api.safaricom.co.ke',
        
        // Aggregator settings (when approved)
        'is_aggregator' => env('MPESA_IS_AGGREGATOR', false),
        'aggregator_shortcode' => env('MPESA_AGGREGATOR_SHORTCODE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | MikroTik Router Management
    |--------------------------------------------------------------------------
    |
    | Settings for connecting to and managing tenant MikroTik routers.
    |
    */

    'mikrotik' => [
        // Default connection timeout (seconds)
        'timeout' => env('MIKROTIK_TIMEOUT', 10),
        
        // API port (default: 8728 for API, 8729 for API-SSL)
        'api_port' => env('MIKROTIK_API_PORT', 8728),
        
        // SSL/TLS settings
        'use_ssl' => env('MIKROTIK_USE_SSL', false),
        'verify_ssl' => env('MIKROTIK_VERIFY_SSL', true),
        
        // Default credentials for new routers (tenants should change these)
        'default_username' => env('MIKROTIK_DEFAULT_USERNAME', 'cloudbridge-api'),
        'default_password_length' => env('MIKROTIK_DEFAULT_PASSWORD_LENGTH', 16),
        
        // Hotspot configuration defaults
        'hotspot' => [
            // Optional router profile fallback. Leave empty to let RouterOS/default package setting decide.
            'profile_name' => env('MIKROTIK_HOTSPOT_PROFILE', ''),
            'address_pool' => 'cloudbridge-pool',
            'pool_range' => env('MIKROTIK_POOL_RANGE', '10.5.50.100-10.5.50.200'),
            'idle_timeout' => env('MIKROTIK_IDLE_TIMEOUT', '5m'),
            'keepalive_timeout' => env('MIKROTIK_KEEPALIVE_TIMEOUT', '2m'),
            'rate_limit_default' => env('MIKROTIK_RATE_LIMIT', '10M/10M'),
        ],
        
        // CloudBridge server IPs (for MikroTik firewall whitelist)
        'server_ips' => explode(',', env('MIKROTIK_SERVER_IPS', '197.248.188.0/24,102.134.0.0/16')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Services
    |--------------------------------------------------------------------------
    |
    | For DNS management, CDN, and security features.
    |
    */

    'cloudflare' => [
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        
        // Turnstile (CAPTCHA alternative)
        'turnstile' => [
            'site_key' => env('CLOUDFLARE_TURNSTILE_SITE_KEY'),
            'secret_key' => env('CLOUDFLARE_TURNSTILE_SECRET_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Starlink API (Future Integration)
    |--------------------------------------------------------------------------
    |
    | Reserved for future Starlink monitoring/integration.
    |
    */

    'starlink' => [
        'api_key' => env('STARLINK_API_KEY'),
        'enabled' => env('STARLINK_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics & Monitoring
    |--------------------------------------------------------------------------
    */

    'sentry' => [
        'dsn' => env('SENTRY_LARAVEL_DSN'),
        'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV')),
        'release' => env('SENTRY_RELEASE'),
    ],

    'plausible' => [
        'domain' => env('PLAUSIBLE_DOMAIN'),
        'api_key' => env('PLAUSIBLE_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Social & OAuth Services
    |--------------------------------------------------------------------------
    */

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack Notifications
    |--------------------------------------------------------------------------
    */

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL', '#cloudbridge-alerts'),
        ],
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Services
    |--------------------------------------------------------------------------
    */

    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'eu-central-1'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    ],

    'digitalocean' => [
        'token' => env('DIGITALOcean_TOKEN'),
        'region' => env('DIGITALOCEAN_REGION', 'ams3'),
        'space_key' => env('DIGITALOCEAN_SPACE_KEY'),
        'space_secret' => env('DIGITALOCEAN_SPACE_SECRET'),
        'space_endpoint' => env('DIGITALOCEAN_SPACE_ENDPOINT'),
        'space_bucket' => env('DIGITALOCEAN_SPACE_BUCKET'),
    ],

];
