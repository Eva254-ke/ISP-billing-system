<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | This option defines the default queue connection that is utilized to
    | process queued jobs. Laravel supports a variety of backends via a
    | single, unified API, giving you convenient access to each backend.
    |
    | Supported: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection options for every queue backend
    | used by your application. An example configuration is provided for
    | each backend supported by Laravel. You're also free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis",
    |          "deferred", "background", "failover", "null"
    |
    */

    'connections' => [

        // ──────────────────────────────────────────────────────────────────
        // SYNC (Local Development Only - Jobs Run Immediately)
        // ──────────────────────────────────────────────────────────────────
        'sync' => [
            'driver' => 'sync',
        ],

        // ──────────────────────────────────────────────────────────────────
        // DATABASE (Local Dev + Production Fallback)
        // ──────────────────────────────────────────────────────────────────
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => env('DB_QUEUE_AFTER_COMMIT', true), // Dispatch after DB transaction
        ],

        // ──────────────────────────────────────────────────────────────────
        // REDIS (Production - High Performance, Priority Queues)
        // ──────────────────────────────────────────────────────────────────
        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            
            // Queue names for priority handling
            // Format: queue_name:priority_number (lower = higher priority)
            'queue' => [
                'critical' => 'critical:1',  // 🔴 M-Pesa callbacks, session activation
                'high' => 'high:2',          // 🟠 Disconnect, user actions
                'medium' => 'medium:3',      // 🟡 Usage sync, notifications
                'low' => 'low:4',            // 🟢 Reconciliation, reports, cleanup
            ],
            
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => (int) env('REDIS_QUEUE_BLOCK_FOR', 5), // Wait 5s for jobs
            'after_commit' => env('REDIS_QUEUE_AFTER_COMMIT', true), // Critical for data consistency
        ],

        // ──────────────────────────────────────────────────────────────────
        // BEANSTALKD (Alternative Production Option)
        // ──────────────────────────────────────────────────────────────────
        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => env('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => env('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,
            'after_commit' => false,
        ],

        // ──────────────────────────────────────────────────────────────────
        // AWS SQS (Cloud Production Option)
        // ──────────────────────────────────────────────────────────────────
        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        // ──────────────────────────────────────────────────────────────────
        // DEFERRED (For Delayed Jobs)
        // ──────────────────────────────────────────────────────────────────
        'deferred' => [
            'driver' => 'deferred',
        ],

        // ──────────────────────────────────────────────────────────────────
        // BACKGROUND (For Non-Critical Async Tasks)
        // ──────────────────────────────────────────────────────────────────
        'background' => [
            'driver' => 'background',
        ],

        // ──────────────────────────────────────────────────────────────────
        // FAILOVER (Production High Availability)
        // ──────────────────────────────────────────────────────────────────
        'failover' => [
            'driver' => 'failover',
            'connections' => [
                'redis',      // Primary: Redis
                'database',   // Fallback: Database if Redis fails
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control how and where failed jobs are stored. Laravel ships with
    | support for storing failed jobs in a simple file or in a database.
    |
    | Supported drivers: "database-uuids", "dynamodb", "file", "null"
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Priority Configuration (Omwenga WiFi SaaS)
    |--------------------------------------------------------------------------
    |
    | Define job-to-queue mappings for priority handling.
    | Jobs are assigned to queues based on business criticality.
    |
    */

    'priorities' => [
        // 🔴 CRITICAL: Must process within 5 seconds
        'critical' => [
            'jobs' => [
                \App\Jobs\ProcessMpesaCallback::class,
                \App\Jobs\ActivateSession::class,
            ],
            'workers' => 4,
            'timeout' => 30,
            'tries' => 3,
            'backoff' => [10, 30, 60], // Exponential: 10s, 30s, 60s
        ],

        // 🟠 HIGH: Must process within 10 seconds
        'high' => [
            'jobs' => [
                \App\Jobs\DisconnectSession::class,
                \App\Jobs\SendSmsNotification::class,
            ],
            'workers' => 2,
            'timeout' => 30,
            'tries' => 3,
            'backoff' => [5, 15, 30],
        ],

        // 🟡 MEDIUM: Can tolerate 1-2 minute delay
        'medium' => [
            'jobs' => [
                \App\Jobs\SyncSessionUsage::class,
                \App\Jobs\UpdateRouterHealth::class,
            ],
            'workers' => 1,
            'timeout' => 60,
            'tries' => 2,
            'backoff' => [30, 60],
        ],

        // 🟢 LOW: Background tasks, can run anytime
        'low' => [
            'jobs' => [
                \App\Jobs\ReconcilePayments::class,
                \App\Jobs\GenerateDailyReport::class,
                \App\Jobs\CleanupExpiredSessions::class,
            ],
            'workers' => 1,
            'timeout' => 300, // 5 minutes for reconciliation
            'tries' => 1,
            'backoff' => [60],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Monitoring & Alerts
    |--------------------------------------------------------------------------
    |
    | Configure thresholds for queue monitoring and alerting.
    | Useful for production observability.
    |
    */

    'monitoring' => [
        // Alert if queue has more than X pending jobs
        'max_pending_jobs' => [
            'critical' => 10,
            'high' => 50,
            'medium' => 100,
            'low' => 500,
        ],

        // Alert if job age exceeds X minutes
        'max_job_age_minutes' => [
            'critical' => 1,
            'high' => 5,
            'medium' => 15,
            'low' => 60,
        ],

        // Slack webhook for alerts (optional)
        'slack_webhook' => env('QUEUE_ALERTS_SLACK_WEBHOOK'),
    ],

];