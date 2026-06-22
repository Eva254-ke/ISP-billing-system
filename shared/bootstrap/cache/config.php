<?php return array (
  'view' => 
  array (
    'paths' => 
    array (
      0 => '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/resources/views',
    ),
    'compiled' => '/var/www/cloudbridge/shared/storage/framework/views',
  ),
  'cors' => 
  array (
    'paths' => 
    array (
      0 => 'api/*',
      1 => 'sanctum/csrf-cookie',
    ),
    'allowed_methods' => 
    array (
      0 => '*',
    ),
    'allowed_origins' => 
    array (
      0 => '*',
    ),
    'allowed_origins_patterns' => 
    array (
    ),
    'allowed_headers' => 
    array (
      0 => '*',
    ),
    'exposed_headers' => 
    array (
    ),
    'max_age' => 0,
    'supports_credentials' => false,
  ),
  'broadcasting' => 
  array (
    'default' => 'null',
    'connections' => 
    array (
      'reverb' => 
      array (
        'driver' => 'reverb',
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'host' => NULL,
          'port' => 443,
          'scheme' => 'https',
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'pusher' => 
      array (
        'driver' => 'pusher',
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'cluster' => NULL,
          'host' => 'api-mt1.pusher.com',
          'port' => 443,
          'scheme' => 'https',
          'encrypted' => true,
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'ably' => 
      array (
        'driver' => 'ably',
        'key' => NULL,
      ),
      'log' => 
      array (
        'driver' => 'log',
      ),
      'null' => 
      array (
        'driver' => 'null',
      ),
    ),
  ),
  'concurrency' => 
  array (
    'default' => 'process',
  ),
  'hashing' => 
  array (
    'driver' => 'bcrypt',
    'bcrypt' => 
    array (
      'rounds' => '12',
      'verify' => true,
      'limit' => NULL,
    ),
    'argon' => 
    array (
      'memory' => 65536,
      'threads' => 1,
      'time' => 4,
      'verify' => true,
    ),
    'rehash_on_login' => true,
  ),
  'app' => 
  array (
    'name' => 'CloudBridge Networks',
    'env' => 'production',
    'debug' => false,
    'url' => 'https://app.cloubridge.com',
    'frontend_url' => 'http://localhost:3000',
    'asset_url' => 'https://app.cloubridge.com',
    'timezone' => 'Africa/Nairobi',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_KE',
    'cipher' => 'AES-256-CBC',
    'key' => 'base64:TTAHvyKsr5+tAcPZmwQVgN3CUNuN7Q8ZYYU5L+Jut9E=',
    'previous_keys' => 
    array (
    ),
    'maintenance' => 
    array (
      'driver' => 'cache',
      'store' => 'database',
    ),
    'providers' => 
    array (
      0 => 'Illuminate\\Auth\\AuthServiceProvider',
      1 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
      2 => 'Illuminate\\Bus\\BusServiceProvider',
      3 => 'Illuminate\\Cache\\CacheServiceProvider',
      4 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
      5 => 'Illuminate\\Cookie\\CookieServiceProvider',
      6 => 'Illuminate\\Database\\DatabaseServiceProvider',
      7 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
      8 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
      9 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
      10 => 'Illuminate\\Hashing\\HashServiceProvider',
      11 => 'Illuminate\\Mail\\MailServiceProvider',
      12 => 'Illuminate\\Notifications\\NotificationServiceProvider',
      13 => 'Illuminate\\Pagination\\PaginationServiceProvider',
      14 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
      15 => 'Illuminate\\Queue\\QueueServiceProvider',
      16 => 'Illuminate\\Redis\\RedisServiceProvider',
      17 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
      18 => 'Illuminate\\Session\\SessionServiceProvider',
      19 => 'Illuminate\\Translation\\TranslationServiceProvider',
      20 => 'Illuminate\\Validation\\ValidationServiceProvider',
      21 => 'Illuminate\\View\\ViewServiceProvider',
      22 => 'App\\Providers\\AppServiceProvider',
      23 => 'App\\Providers\\AuthServiceProvider',
      24 => 'App\\Providers\\EventServiceProvider',
      25 => 'App\\Providers\\RouteServiceProvider',
      26 => 'App\\Providers\\MikroTikServiceProvider',
      27 => 'App\\Providers\\AppServiceProvider',
    ),
    'aliases' => 
    array (
      'App' => 'Illuminate\\Support\\Facades\\App',
      'Arr' => 'Illuminate\\Support\\Arr',
      'Artisan' => 'Illuminate\\Support\\Facades\\Artisan',
      'Auth' => 'Illuminate\\Support\\Facades\\Auth',
      'Blade' => 'Illuminate\\Support\\Facades\\Blade',
      'Broadcast' => 'Illuminate\\Support\\Facades\\Broadcast',
      'Bus' => 'Illuminate\\Support\\Facades\\Bus',
      'Cache' => 'Illuminate\\Support\\Facades\\Cache',
      'Config' => 'Illuminate\\Support\\Facades\\Config',
      'Cookie' => 'Illuminate\\Support\\Facades\\Cookie',
      'Crypt' => 'Illuminate\\Support\\Facades\\Crypt',
      'Date' => 'Illuminate\\Support\\Facades\\Date',
      'DB' => 'Illuminate\\Support\\Facades\\DB',
      'Eloquent' => 'Illuminate\\Database\\Eloquent\\Model',
      'Event' => 'Illuminate\\Support\\Facades\\Event',
      'File' => 'Illuminate\\Support\\Facades\\File',
      'Gate' => 'Illuminate\\Support\\Facades\\Gate',
      'Hash' => 'Illuminate\\Support\\Facades\\Hash',
      'Http' => 'Illuminate\\Support\\Facades\\Http',
      'Js' => 'Illuminate\\Support\\Js',
      'Lang' => 'Illuminate\\Support\\Facades\\Lang',
      'Log' => 'Illuminate\\Support\\Facades\\Log',
      'Mail' => 'Illuminate\\Support\\Facades\\Mail',
      'Notification' => 'Illuminate\\Support\\Facades\\Notification',
      'Password' => 'Illuminate\\Support\\Facades\\Password',
      'Process' => 'Illuminate\\Support\\Facades\\Process',
      'Queue' => 'Illuminate\\Support\\Facades\\Queue',
      'RateLimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
      'Redirect' => 'Illuminate\\Support\\Facades\\Redirect',
      'Request' => 'Illuminate\\Support\\Facades\\Request',
      'Response' => 'Illuminate\\Support\\Facades\\Response',
      'Route' => 'Illuminate\\Support\\Facades\\Route',
      'Schema' => 'Illuminate\\Support\\Facades\\Schema',
      'Session' => 'Illuminate\\Support\\Facades\\Session',
      'Storage' => 'Illuminate\\Support\\Facades\\Storage',
      'Str' => 'Illuminate\\Support\\Str',
      'URL' => 'Illuminate\\Support\\Facades\\URL',
      'Validator' => 'Illuminate\\Support\\Facades\\Validator',
      'View' => 'Illuminate\\Support\\Facades\\View',
    ),
  ),
  'auth' => 
  array (
    'defaults' => 
    array (
      'guard' => 'web',
      'passwords' => 'users',
    ),
    'guards' => 
    array (
      'web' => 
      array (
        'driver' => 'session',
        'provider' => 'users',
      ),
    ),
    'providers' => 
    array (
      'users' => 
      array (
        'driver' => 'eloquent',
        'model' => 'App\\Models\\User',
      ),
    ),
    'passwords' => 
    array (
      'users' => 
      array (
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
      ),
    ),
    'password_timeout' => 10800,
  ),
  'cache' => 
  array (
    'default' => 'file',
    'stores' => 
    array (
      'array' => 
      array (
        'driver' => 'array',
        'serialize' => false,
      ),
      'session' => 
      array (
        'driver' => 'session',
        'key' => '_cache',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'cache',
        'lock_connection' => NULL,
        'lock_table' => NULL,
      ),
      'file' => 
      array (
        'driver' => 'file',
        'path' => '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/storage/framework/cache/data',
        'lock_path' => '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/storage/framework/cache/data',
      ),
      'memcached' => 
      array (
        'driver' => 'memcached',
        'persistent_id' => NULL,
        'sasl' => 
        array (
          0 => NULL,
          1 => NULL,
        ),
        'options' => 
        array (
        ),
        'servers' => 
        array (
          0 => 
          array (
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 100,
          ),
        ),
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
      ),
      'dynamodb' => 
      array (
        'driver' => 'dynamodb',
        'key' => NULL,
        'secret' => NULL,
        'region' => 'us-east-1',
        'table' => 'cache',
        'endpoint' => NULL,
      ),
      'octane' => 
      array (
        'driver' => 'octane',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'stores' => 
        array (
          0 => 'database',
          1 => 'array',
        ),
      ),
    ),
    'prefix' => 'cloudbridge-networks-cache-',
    'serializable_classes' => false,
  ),
  'database' => 
  array (
    'default' => 'mysql',
    'connections' => 
    array (
      'sqlite' => 
      array (
        'driver' => 'sqlite',
        'url' => NULL,
        'database' => 'defaultdb',
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => NULL,
        'journal_mode' => NULL,
        'synchronous' => NULL,
        'transaction_mode' => 'DEFERRED',
      ),
      'mysql' => 
      array (
        'driver' => 'mysql',
        'url' => NULL,
        'host' => 'db-mysql-lon1-71685-do-user-17788951-0.m.db.ondigitalocean.com',
        'port' => '25060',
        'database' => 'defaultdb',
        'username' => 'doadmin',
        'password' => 'AVNS_jxWFuOPP2l5KxAMl-VA',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'mariadb' => 
      array (
        'driver' => 'mariadb',
        'url' => NULL,
        'host' => 'db-mysql-lon1-71685-do-user-17788951-0.m.db.ondigitalocean.com',
        'port' => '25060',
        'database' => 'defaultdb',
        'username' => 'doadmin',
        'password' => 'AVNS_jxWFuOPP2l5KxAMl-VA',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'pgsql' => 
      array (
        'driver' => 'pgsql',
        'url' => NULL,
        'host' => 'db-mysql-lon1-71685-do-user-17788951-0.m.db.ondigitalocean.com',
        'port' => '25060',
        'database' => 'defaultdb',
        'username' => 'doadmin',
        'password' => 'AVNS_jxWFuOPP2l5KxAMl-VA',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
      ),
      'sqlsrv' => 
      array (
        'driver' => 'sqlsrv',
        'url' => NULL,
        'host' => 'db-mysql-lon1-71685-do-user-17788951-0.m.db.ondigitalocean.com',
        'port' => '25060',
        'database' => 'defaultdb',
        'username' => 'doadmin',
        'password' => 'AVNS_jxWFuOPP2l5KxAMl-VA',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
      ),
      'radius' => 
      array (
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'freeradius',
        'username' => 'radius',
        'password' => '',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
    ),
    'migrations' => 
    array (
      'table' => 'migrations',
      'update_date_on_publish' => true,
    ),
    'redis' => 
    array (
      'client' => 'phpredis',
      'options' => 
      array (
        'cluster' => 'redis',
        'prefix' => 'cloudbridge-networks-database-',
        'persistent' => false,
      ),
      'default' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '0',
        'max_retries' => 3,
        'backoff_algorithm' => 'decorrelated_jitter',
        'backoff_base' => 100,
        'backoff_cap' => 1000,
      ),
      'cache' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '1',
        'max_retries' => 3,
        'backoff_algorithm' => 'decorrelated_jitter',
        'backoff_base' => 100,
        'backoff_cap' => 1000,
      ),
    ),
  ),
  'filesystems' => 
  array (
    'default' => 'local',
    'disks' => 
    array (
      'local' => 
      array (
        'driver' => 'local',
        'root' => '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/storage/app/private',
        'serve' => true,
        'throw' => false,
        'report' => false,
      ),
      'public' => 
      array (
        'driver' => 'local',
        'root' => '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/storage/app/public',
        'url' => 'https://app.cloubridge.com/storage',
        'visibility' => 'public',
        'throw' => false,
        'report' => false,
      ),
      's3' => 
      array (
        'driver' => 's3',
        'key' => NULL,
        'secret' => NULL,
        'region' => NULL,
        'bucket' => NULL,
        'url' => NULL,
        'endpoint' => NULL,
        'use_path_style_endpoint' => false,
        'throw' => false,
        'report' => false,
      ),
    ),
    'links' => 
    array (
      '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/public/storage' => '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/storage/app/public',
    ),
  ),
  'logging' => 
  array (
    'default' => 'stack',
    'deprecations' => 
    array (
      'channel' => 'null',
      'trace' => false,
    ),
    'channels' => 
    array (
      'stack' => 
      array (
        'driver' => 'stack',
        'channels' => 
        array (
          0 => 'single',
          1 => 'security',
        ),
        'ignore_exceptions' => false,
      ),
      'single' => 
      array (
        'driver' => 'single',
        'path' => '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/storage/logs/laravel.log',
        'level' => 'error',
        'replace_placeholders' => true,
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
          1 => 'Monolog\\Processor\\UidProcessor',
        ),
      ),
      'daily' => 
      array (
        'driver' => 'daily',
        'path' => '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/storage/logs/laravel.log',
        'level' => 'error',
        'days' => 30,
        'replace_placeholders' => true,
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
          1 => 'Monolog\\Processor\\UidProcessor',
        ),
      ),
      'slack' => 
      array (
        'driver' => 'slack',
        'url' => NULL,
        'username' => 'CloudBridge Alerts',
        'emoji' => ':warning:',
        'level' => 'critical',
        'replace_placeholders' => true,
      ),
      'papertrail' => 
      array (
        'driver' => 'monolog',
        'level' => 'error',
        'handler' => 'Monolog\\Handler\\SyslogUdpHandler',
        'handler_with' => 
        array (
          'host' => NULL,
          'port' => NULL,
          'connectionString' => 'tls://:',
        ),
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'stderr' => 
      array (
        'driver' => 'monolog',
        'level' => 'error',
        'handler' => 'Monolog\\Handler\\StreamHandler',
        'formatter' => NULL,
        'handler_with' => 
        array (
          'stream' => 'php://stderr',
        ),
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'syslog' => 
      array (
        'driver' => 'syslog',
        'level' => 'error',
        'facility' => 8,
        'replace_placeholders' => true,
      ),
      'errorlog' => 
      array (
        'driver' => 'errorlog',
        'level' => 'error',
        'replace_placeholders' => true,
      ),
      'null' => 
      array (
        'driver' => 'monolog',
        'handler' => 'Monolog\\Handler\\NullHandler',
      ),
      'emergency' => 
      array (
        'path' => '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/storage/logs/laravel.log',
        'level' => 'emergency',
      ),
      'security' => 
      array (
        'driver' => 'daily',
        'path' => '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/storage/logs/security.log',
        'level' => 'info',
        'days' => 90,
        'replace_placeholders' => true,
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
          1 => 'Monolog\\Processor\\UidProcessor',
        ),
      ),
      'payment' => 
      array (
        'driver' => 'daily',
        'path' => '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/storage/logs/payment.log',
        'level' => 'info',
        'days' => 365,
        'replace_placeholders' => true,
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
          1 => 'Monolog\\Processor\\UidProcessor',
        ),
      ),
      'intasend' => 
      array (
        'driver' => 'daily',
        'path' => '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/storage/logs/intasend.log',
        'level' => 'info',
        'days' => 90,
        'replace_placeholders' => true,
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
          1 => 'Monolog\\Processor\\UidProcessor',
        ),
      ),
      'mikrotik' => 
      array (
        'driver' => 'daily',
        'path' => '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/storage/logs/mikrotik.log',
        'level' => 'warning',
        'days' => 14,
        'replace_placeholders' => true,
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
          1 => 'Monolog\\Processor\\UidProcessor',
        ),
      ),
      'error' => 
      array (
        'driver' => 'daily',
        'path' => '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/storage/logs/error.log',
        'level' => 'error',
        'days' => 90,
        'replace_placeholders' => true,
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
          1 => 'Monolog\\Processor\\UidProcessor',
        ),
      ),
      'notification' => 
      array (
        'driver' => 'daily',
        'path' => '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/storage/logs/notification.log',
        'level' => 'info',
        'days' => 30,
        'replace_placeholders' => true,
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
    ),
  ),
  'mail' => 
  array (
    'default' => 'smtp',
    'mailers' => 
    array (
      'smtp' => 
      array (
        'transport' => 'smtp',
        'scheme' => NULL,
        'url' => NULL,
        'host' => 'smtp.gmail.com',
        'port' => '587',
        'username' => '',
        'password' => '',
        'timeout' => NULL,
        'local_domain' => 'app.cloubridge.com',
      ),
      'ses' => 
      array (
        'transport' => 'ses',
      ),
      'postmark' => 
      array (
        'transport' => 'postmark',
      ),
      'resend' => 
      array (
        'transport' => 'resend',
      ),
      'sendmail' => 
      array (
        'transport' => 'sendmail',
        'path' => '/usr/sbin/sendmail -bs -i',
      ),
      'log' => 
      array (
        'transport' => 'log',
        'channel' => NULL,
      ),
      'array' => 
      array (
        'transport' => 'array',
      ),
      'failover' => 
      array (
        'transport' => 'failover',
        'mailers' => 
        array (
          0 => 'smtp',
          1 => 'log',
        ),
        'retry_after' => 60,
      ),
      'roundrobin' => 
      array (
        'transport' => 'roundrobin',
        'mailers' => 
        array (
          0 => 'ses',
          1 => 'postmark',
        ),
        'retry_after' => 60,
      ),
    ),
    'from' => 
    array (
      'address' => 'hello@cloudbridge.com',
      'name' => 'CloudBridge Networks',
    ),
    'markdown' => 
    array (
      'theme' => 'default',
      'paths' => 
      array (
        0 => '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/resources/views/vendor/mail',
      ),
      'extensions' => 
      array (
      ),
    ),
  ),
  'queue' => 
  array (
    'default' => 'database',
    'connections' => 
    array (
      'sync' => 
      array (
        'driver' => 'sync',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => true,
      ),
      'beanstalkd' => 
      array (
        'driver' => 'beanstalkd',
        'host' => 'localhost',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 0,
        'after_commit' => false,
      ),
      'sqs' => 
      array (
        'driver' => 'sqs',
        'key' => NULL,
        'secret' => NULL,
        'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
        'queue' => 'default',
        'suffix' => NULL,
        'region' => 'us-east-1',
        'after_commit' => false,
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 
        array (
          'critical' => 'critical:1',
          'high' => 'high:2',
          'medium' => 'medium:3',
          'low' => 'low:4',
        ),
        'retry_after' => 90,
        'block_for' => 5,
        'after_commit' => true,
      ),
      'deferred' => 
      array (
        'driver' => 'deferred',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'connections' => 
        array (
          0 => 'redis',
          1 => 'database',
        ),
      ),
      'background' => 
      array (
        'driver' => 'background',
      ),
    ),
    'batching' => 
    array (
      'database' => 'mysql',
      'table' => 'job_batches',
    ),
    'failed' => 
    array (
      'driver' => 'database-uuids',
      'database' => 'mysql',
      'table' => 'failed_jobs',
    ),
    'priorities' => 
    array (
      'critical' => 
      array (
        'jobs' => 
        array (
          0 => 'App\\Jobs\\ProcessMpesaCallback',
          1 => 'App\\Jobs\\ActivateSession',
        ),
        'workers' => 4,
        'timeout' => 30,
        'tries' => 3,
        'backoff' => 
        array (
          0 => 10,
          1 => 30,
          2 => 60,
        ),
      ),
      'high' => 
      array (
        'jobs' => 
        array (
          0 => 'App\\Jobs\\DisconnectSession',
          1 => 'App\\Jobs\\SendSmsNotification',
        ),
        'workers' => 2,
        'timeout' => 30,
        'tries' => 3,
        'backoff' => 
        array (
          0 => 5,
          1 => 15,
          2 => 30,
        ),
      ),
      'medium' => 
      array (
        'jobs' => 
        array (
          0 => 'App\\Jobs\\SyncSessionUsage',
          1 => 'App\\Jobs\\UpdateRouterHealth',
        ),
        'workers' => 1,
        'timeout' => 60,
        'tries' => 2,
        'backoff' => 
        array (
          0 => 30,
          1 => 60,
        ),
      ),
      'low' => 
      array (
        'jobs' => 
        array (
          0 => 'App\\Jobs\\ReconcilePayments',
          1 => 'App\\Jobs\\GenerateDailyReport',
          2 => 'App\\Jobs\\CleanupExpiredSessions',
        ),
        'workers' => 1,
        'timeout' => 300,
        'tries' => 1,
        'backoff' => 
        array (
          0 => 60,
        ),
      ),
    ),
    'monitoring' => 
    array (
      'max_pending_jobs' => 
      array (
        'critical' => 10,
        'high' => 50,
        'medium' => 100,
        'low' => 500,
      ),
      'max_job_age_minutes' => 
      array (
        'critical' => 1,
        'high' => 5,
        'medium' => 15,
        'low' => 60,
      ),
      'slack_webhook' => NULL,
    ),
  ),
  'radius' => 
  array (
    'enabled' => false,
    'server_ip' => '127.0.0.1',
    'auth_port' => 1812,
    'acct_port' => 1813,
    'shared_secret' => '',
    'db_connection' => 'radius',
    'tables' => 
    array (
      'radcheck' => 'radcheck',
      'radreply' => 'radreply',
      'radacct' => 'radacct',
    ),
    'attributes' => 
    array (
      'cleartext_password' => 'Cleartext-Password',
      'expiration' => 'Expiration',
      'session_timeout' => 'Session-Timeout',
      'rate_limit' => 'Mikrotik-Rate-Limit',
      'simultaneous_use' => 'Simultaneous-Use',
    ),
    'simultaneous_use' => 1,
  ),
  'services' => 
  array (
    'postmark' => 
    array (
      'key' => NULL,
      'message_stream_id' => NULL,
      'from' => 'hello@cloudbridge.com',
    ),
    'resend' => 
    array (
      'key' => NULL,
      'from' => 'hello@cloudbridge.com',
    ),
    'ses' => 
    array (
      'key' => NULL,
      'secret' => NULL,
      'region' => 'us-east-1',
    ),
    'slack' => 
    array (
      'notifications' => 
      array (
        'bot_user_oauth_token' => NULL,
        'channel' => '#cloudbridge-alerts',
      ),
      'webhook_url' => NULL,
    ),
    'mailgun' => 
    array (
      'domain' => NULL,
      'secret' => NULL,
      'endpoint' => 'api.mailgun.net',
      'scheme' => 'https',
    ),
    'africas_talking' => 
    array (
      'username' => NULL,
      'api_key' => NULL,
      'from' => 'CloudBridge',
      'sandbox' => false,
    ),
    'twilio' => 
    array (
      'sid' => NULL,
      'token' => NULL,
      'from' => NULL,
    ),
    'intasend' => 
    array (
      'public_key' => NULL,
      'secret_key' => NULL,
      'webhook_secret' => NULL,
      'env' => 'sandbox',
      'sandbox_url' => 'https://sandbox.intasend.com/api/v1/',
      'live_url' => 'https://payment.intasend.com/api/v1/',
      'callback_url' => 'https://app.cloudbridge.network/api/payment/callback',
      'auto_payout' => true,
      'payout_fee' => 10,
      'transaction_fee_percent' => 1.0,
      'absorb_fee' => false,
    ),
    'paystack' => 
    array (
      'public_key' => 'pk_live_d7ddf89ba7f83ef9e48655912904dbf51525f907',
      'secret_key' => 'sk_live_224b555ea1ab900314d2fdef4cd33383bb575458',
      'merchant_email' => NULL,
      'base_url' => 'https://api.paystack.co',
      'env' => 'sandbox',
      'mobile_money_provider' => 'mpesa',
      'callback_url' => 'https://app.cloubridge.com/api/paystack/webhook',
      'success_url' => 'https://app.cloubridge.com/portal/payment/success',
    ),
    'mpesa' => 
    array (
      'consumer_key' => NULL,
      'consumer_secret' => NULL,
      'passkey' => NULL,
      'business_shortcode' => NULL,
      'callback_url' => NULL,
      'env' => 'sandbox',
      'sandbox_url' => 'https://sandbox.safaricom.co.ke',
      'live_url' => 'https://api.safaricom.co.ke',
      'is_aggregator' => false,
      'aggregator_shortcode' => NULL,
    ),
    'mikrotik' => 
    array (
      'timeout' => 10,
      'api_port' => '8728',
      'use_ssl' => false,
      'verify_ssl' => true,
      'default_username' => 'cloudbridge-api',
      'default_password_length' => '16',
      'hotspot' => 
      array (
        'profile_name' => 'cloudbridge-profile',
        'address_pool' => 'cloudbridge-pool',
        'pool_range' => '10.5.50.100-10.5.50.200',
        'idle_timeout' => '5m',
        'keepalive_timeout' => '2m',
        'rate_limit_default' => '10M/10M',
      ),
      'server_ips' => 
      array (
        0 => '197.248.188.0/24',
        1 => '102.134.0.0/16',
      ),
    ),
    'cloudflare' => 
    array (
      'api_token' => NULL,
      'zone_id' => NULL,
      'account_id' => NULL,
      'turnstile' => 
      array (
        'site_key' => NULL,
        'secret_key' => NULL,
      ),
    ),
    'starlink' => 
    array (
      'api_key' => NULL,
      'enabled' => false,
    ),
    'sentry' => 
    array (
      'dsn' => NULL,
      'environment' => 'production',
      'release' => NULL,
    ),
    'plausible' => 
    array (
      'domain' => NULL,
      'api_key' => NULL,
    ),
    'google' => 
    array (
      'client_id' => NULL,
      'client_secret' => NULL,
      'redirect' => NULL,
    ),
    'facebook' => 
    array (
      'client_id' => NULL,
      'client_secret' => NULL,
      'redirect' => NULL,
    ),
    'github' => 
    array (
      'client_id' => NULL,
      'client_secret' => NULL,
      'redirect' => NULL,
    ),
    'aws' => 
    array (
      'key' => NULL,
      'secret' => NULL,
      'region' => 'eu-central-1',
      'bucket' => NULL,
      'url' => NULL,
      'endpoint' => NULL,
      'use_path_style_endpoint' => false,
    ),
    'digitalocean' => 
    array (
      'token' => NULL,
      'region' => 'ams3',
      'space_key' => NULL,
      'space_secret' => NULL,
      'space_endpoint' => NULL,
      'space_bucket' => NULL,
    ),
  ),
  'session' => 
  array (
    'driver' => 'file',
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => '/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/storage/framework/sessions',
    'connection' => NULL,
    'table' => 'sessions',
    'store' => NULL,
    'lottery' => 
    array (
      0 => 2,
      1 => 100,
    ),
    'cookie' => 'cloudbridge-networks-session',
    'path' => '/',
    'domain' => NULL,
    'secure' => true,
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
    'serialization' => 'json',
  ),
  'routeros-api' => 
  array (
    'host' => '192.168.88.1',
    'user' => 'admin',
    'pass' => NULL,
    'port' => 8728,
    'attempts' => 10,
    'delay' => 1,
    'timeout' => 10,
    'socket_timeout' => 30,
    'socket_blocking' => true,
    'socket_options' => 
    array (
    ),
    'ssl' => false,
    'ssl_options' => 
    array (
      'ciphers' => 'ADH:ALL',
      'verify_peer' => false,
      'verify_peer_name' => false,
      'allow_self_signed' => false,
    ),
    'ssh_port' => 22,
    'ssh_timeout' => 30,
    'ssh_private_key' => '~/.ssh/id_rsa.pub',
    'legacy' => false,
  ),
  'tinker' => 
  array (
    'commands' => 
    array (
    ),
    'alias' => 
    array (
    ),
    'dont_alias' => 
    array (
      0 => 'App\\Nova',
    ),
    'trust_project' => 'always',
  ),
);
