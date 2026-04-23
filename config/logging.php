<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\UidProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [

        // ──────────────────────────────────────────────────────────────────
        // DEFAULT STACK (Combines multiple channels)
        // ──────────────────────────────────────────────────────────────────
        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'single,security')),
            'ignore_exceptions' => env('LOG_STACK_IGNORE_EXCEPTIONS', false),
        ],

        // ──────────────────────────────────────────────────────────────────
        // APPLICATION LOGS (General app errors, info, debug)
        // ──────────────────────────────────────────────────────────────────
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'info'), // Production: info, Dev: debug
            'replace_placeholders' => true,
            'processors' => [PsrLogMessageProcessor::class, UidProcessor::class],
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => env('LOG_DAILY_DAYS', 30), // Keep 30 days of logs
            'replace_placeholders' => true,
            'processors' => [PsrLogMessageProcessor::class, UidProcessor::class],
        ],

        // ──────────────────────────────────────────────────────────────────
        // 🔐 SECURITY LOGS (Audit trail, logins, payments, admin actions)
        // ──────────────────────────────────────────────────────────────────
        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => env('LOG_SECURITY_LEVEL', 'info'),
            'days' => env('LOG_SECURITY_DAYS', 90), // Keep 90 days for compliance
            'replace_placeholders' => true,
            'processors' => [
                PsrLogMessageProcessor::class,
                UidProcessor::class,
                // Add custom processor for sensitive data masking if needed
            ],
            // Optional: Add JSON formatter for easier parsing by log aggregators
            // 'formatter' => \Monolog\Formatter\JsonFormatter::class,
        ],

        // ──────────────────────────────────────────────────────────────────
        // 💰 PAYMENT LOGS (M-Pesa transactions, reconciliation)
        // ──────────────────────────────────────────────────────────────────
        'payment' => [
            'driver' => 'daily',
            'path' => storage_path('logs/payment.log'),
            'level' => env('LOG_PAYMENT_LEVEL', 'info'),
            'days' => env('LOG_PAYMENT_DAYS', 365), // Keep 1 year for financial audit
            'replace_placeholders' => true,
            'processors' => [PsrLogMessageProcessor::class, UidProcessor::class],
        ],

        'radius' => [
            'driver' => 'daily',
            'path' => storage_path('logs/radius.log'),
            'level' => env('LOG_RADIUS_LEVEL', 'info'),
            'days' => env('LOG_RADIUS_DAYS', 30),
            'replace_placeholders' => true,
            'processors' => [PsrLogMessageProcessor::class, UidProcessor::class],
        ],

        // ──────────────────────────────────────────────────────────────────
        // 🧾 INTASEND LOGS (Gateway requests, responses, callback diagnostics)
        // ──────────────────────────────────────────────────────────────────
        // ──────────────────────────────────────────────────────────────────
        // 📡 MIKROTIK LOGS (Router API calls, session management)
        // ──────────────────────────────────────────────────────────────────
        'mikrotik' => [
            'driver' => 'daily',
            'path' => storage_path('logs/mikrotik.log'),
            'level' => env('LOG_MIKROTIK_LEVEL', 'warning'), // Only log warnings+ in prod
            'days' => env('LOG_MIKROTIK_DAYS', 14),
            'replace_placeholders' => true,
            'processors' => [PsrLogMessageProcessor::class, UidProcessor::class],
        ],

        // ──────────────────────────────────────────────────────────────────
        // 🚨 ERROR LOGS (Only errors and above, separate file)
        // ──────────────────────────────────────────────────────────────────
        'error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/error.log'),
            'level' => 'error',
            'days' => 90,
            'replace_placeholders' => true,
            'processors' => [PsrLogMessageProcessor::class, UidProcessor::class],
        ],

        // ──────────────────────────────────────────────────────────────────
        // 📧 NOTIFICATION LOGS (SMS, email delivery)
        // ──────────────────────────────────────────────────────────────────
        'notification' => [
            'driver' => 'daily',
            'path' => storage_path('logs/notification.log'),
            'level' => env('LOG_NOTIFICATION_LEVEL', 'info'),
            'days' => 30,
            'replace_placeholders' => true,
            'processors' => [PsrLogMessageProcessor::class],
        ],

        // ──────────────────────────────────────────────────────────────────
        // EXTERNAL SERVICES (Slack, Papertrail, etc.)
        // ──────────────────────────────────────────────────────────────────
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'CloudBridge Alerts'),
            'emoji' => env('LOG_SLACK_EMOJI', ':warning:'),
            'level' => env('LOG_SLACK_LEVEL', 'critical'), // Only critical alerts to Slack
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
            'level' => 'emergency',
        ],

    ],

];
