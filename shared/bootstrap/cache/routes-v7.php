<?php

app('router')->setCompiledRoutes(
    array (
  'compiled' => 
  array (
    0 => false,
    1 => 
    array (
      '/up' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::94b1VMtyoQrVB9rw',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::mOJIbrAfdhDvTym8',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/login' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'login',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'login.post',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/logout' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'logout',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/portal' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'portal.home',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/portal/payment/initiate' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'portal.payment.initiate',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/portal/voucher/redeem' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'portal.voucher.redeem',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/wifi' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'wifi.packages',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/wifi/pay' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'wifi.pay',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/wifi/reconnect' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'wifi.reconnect.form',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'wifi.reconnect',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/wifi/extend' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'wifi.extend',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/wifi' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::MYszu6LUtgRePYIs',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/mpesa/callback' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.mpesa.callback.legacy',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/dashboard' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.dashboard',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/routers' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.routers.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/routers/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.routers.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/packages' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.packages.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/packages/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.packages.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/vouchers' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.vouchers.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/vouchers/generate' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.vouchers.generate',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/vouchers/print' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.vouchers.print',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/payments' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.payments.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/payments/export' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.payments.export',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/clients/hotspot' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.clients.hotspot',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/clients/pppoe' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.clients.pppoe',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/clients/customers' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.clients.customers',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/settings' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.settings',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/settings/mpesa' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.settings.mpesa',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/settings/sms' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.settings.sms',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/settings/branding' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.settings.branding',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/settings/account' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.settings.account',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/api/settings' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.settings.show',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.settings.save',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/api/settings/mikrotik/test' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.settings.mikrotik.test',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/api/routers/status' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.routers.status',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/api/routers/test' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.routers.test',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/api/packages/stats' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.packages.stats',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/api/dashboard/summary' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.dashboard.summary',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/api/packages' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.packages.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.packages.create',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/api/clients/stats' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.clients.stats',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/api/clients/sessions' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.clients.sessions',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/api/clients/disconnect' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.clients.disconnect',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/api/payments' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.payments.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/api/payments/stats' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.payments.stats',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/api/vouchers/generate' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.vouchers.generate',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/api/vouchers' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.vouchers.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/health' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Yo4E2mNION8kC4Zi',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/health' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.health',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/payment/callback' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.payment.callback',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/payment/paystack/callback' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.payment.paystack.callback',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/paystack/webhook' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.paystack.webhook',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/intasend/callback' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'intasend.callback',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/user' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.user',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/mikrotik/routers' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'mikrotik.routers.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/mikrotik/sessions' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'mikrotik.sessions.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/mikrotik/sessions/active' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'mikrotik.sessions.active',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/mikrotik/sessions/expiring' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'mikrotik.sessions.expiring',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/mikrotik/sessions/search' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'mikrotik.sessions.search',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/mikrotik/sessions/disconnect-bulk' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'mikrotik.sessions.disconnect-bulk',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/payments' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'payments.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/payments/stats' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'payments.stats',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/payments/initiate' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'payments.initiate',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/packages' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'packages.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'packages.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/vouchers' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'vouchers.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/vouchers/stats' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'vouchers.stats',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/vouchers/generate' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'vouchers.generate',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/vouchers/validate' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'vouchers.validate',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/vouchers/redeem' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'vouchers.redeem',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/vouchers/export' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'vouchers.export',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/settings/payment' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'settings.payment',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'settings.payment.update',
          ),
          1 => NULL,
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/settings/commission' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'settings.commission',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/settings/profile' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'settings.profile.update',
          ),
          1 => NULL,
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/reports/revenue' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'reports.revenue',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/reports/dashboard' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'reports.dashboard',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
    ),
    2 => 
    array (
      0 => '{^(?|/portal/pa(?|ckage/([^/]++)(*:34)|yment/s(?|tatus/([^/]++)(*:65)|uccess(?:/([^/]++))?(*:92)))|/wifi/status/([^/]++)(?|(*:125)|/check(*:139))|/a(?|dmin/(?|routers/([^/]++)(?|(*:180)|/edit(*:193))|packages/([^/]++)(?|(*:222)|/edit(*:235))|api/(?|packages/([^/]++)(?|(*:271)|/status(*:286)|(*:294))|vouchers/(?|([^/]++)(?|(*:326))|bulk\\-delete(*:347))))|pi/(?|m(?|pesa/callback(?:/([^/]++))?(*:395)|ikrotik/(?|routers/(?|([0-9]+)/ping(*:438)|([0-9]+)/sessions(*:463)|([0-9]+)/system(*:486)|([0-9]+)(?|(*:505)))|sessions/(?|([0-9]+)/disconnect(*:546)|([0-9]+)/extend(*:569))))|pa(?|yments/(?|([0-9]+)(*:603)|([0-9]+)/retry(*:625)|([0-9]+)/refund(*:648)|([0-9]+)/payout(*:671))|ckages/(?|([0-9]+)(?|(*:701))|([0-9]+)/toggle(*:725)|([0-9]+)/duplicate(*:751)))|vouchers/([0-9]+)/print(*:784)))|/storage/(.*)(?|(*:810))|/(.*)(*:824)|/api/(.*)(*:841))/?$}sDu',
    ),
    3 => 
    array (
      34 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'portal.package.show',
          ),
          1 => 
          array (
            0 => 'package',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      65 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'portal.payment.status',
          ),
          1 => 
          array (
            0 => 'checkoutId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      92 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'portal.payment.success',
            'payment' => NULL,
          ),
          1 => 
          array (
            0 => 'payment',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      125 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'wifi.status',
          ),
          1 => 
          array (
            0 => 'phone',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      139 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'wifi.status.check',
          ),
          1 => 
          array (
            0 => 'phone',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      180 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.routers.show',
          ),
          1 => 
          array (
            0 => 'router',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      193 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.routers.edit',
          ),
          1 => 
          array (
            0 => 'router',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      222 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.packages.show',
          ),
          1 => 
          array (
            0 => 'package',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      235 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.packages.edit',
          ),
          1 => 
          array (
            0 => 'package',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      271 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.packages.update',
          ),
          1 => 
          array (
            0 => 'package',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      286 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.packages.status',
          ),
          1 => 
          array (
            0 => 'package',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      294 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.packages.delete',
          ),
          1 => 
          array (
            0 => 'package',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      326 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.vouchers.show',
          ),
          1 => 
          array (
            0 => 'voucher',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.vouchers.delete',
          ),
          1 => 
          array (
            0 => 'voucher',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      347 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.api.vouchers.bulk-delete',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      395 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::6ND4U20ToiBmGdbZ',
            'tenant' => NULL,
          ),
          1 => 
          array (
            0 => 'tenant',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      438 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'mikrotik.routers.ping',
          ),
          1 => 
          array (
            0 => 'router',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      463 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'mikrotik.routers.sessions',
          ),
          1 => 
          array (
            0 => 'router',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      486 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'mikrotik.routers.system',
          ),
          1 => 
          array (
            0 => 'router',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      505 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'mikrotik.routers.update',
          ),
          1 => 
          array (
            0 => 'router',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'mikrotik.routers.destroy',
          ),
          1 => 
          array (
            0 => 'router',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      546 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'mikrotik.sessions.disconnect',
          ),
          1 => 
          array (
            0 => 'session',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      569 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'mikrotik.sessions.extend',
          ),
          1 => 
          array (
            0 => 'session',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      603 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'payments.show',
          ),
          1 => 
          array (
            0 => 'payment',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      625 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'payments.retry',
          ),
          1 => 
          array (
            0 => 'payment',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      648 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'payments.refund',
          ),
          1 => 
          array (
            0 => 'payment',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      671 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'payments.payout',
          ),
          1 => 
          array (
            0 => 'payment',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      701 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'packages.show',
          ),
          1 => 
          array (
            0 => 'package',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'packages.update',
          ),
          1 => 
          array (
            0 => 'package',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'packages.destroy',
          ),
          1 => 
          array (
            0 => 'package',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      725 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'packages.toggle',
          ),
          1 => 
          array (
            0 => 'package',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      751 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'packages.duplicate',
          ),
          1 => 
          array (
            0 => 'package',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      784 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'vouchers.print',
          ),
          1 => 
          array (
            0 => 'batch',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      810 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'storage.local',
          ),
          1 => 
          array (
            0 => 'path',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'storage.local.upload',
          ),
          1 => 
          array (
            0 => 'path',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      824 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::dUU8aSOyxRMhqroW',
          ),
          1 => 
          array (
            0 => 'fallbackPlaceholder',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      841 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::hjfrt6AFLyEjG37w',
          ),
          1 => 
          array (
            0 => 'fallbackPlaceholder',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => NULL,
          1 => NULL,
          2 => NULL,
          3 => NULL,
          4 => false,
          5 => false,
          6 => 0,
        ),
      ),
    ),
    4 => NULL,
  ),
  'attributes' => 
  array (
    'generated::94b1VMtyoQrVB9rw' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'up',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:867:"function () {
                    $exception = null;

                    try {
                        \\Illuminate\\Support\\Facades\\Event::dispatch(new \\Illuminate\\Foundation\\Events\\DiagnosingHealth);
                    } catch (\\Throwable $e) {
                        if (app()->hasDebugModeEnabled()) {
                            throw $e;
                        }

                        report($e);

                        $exception = $e->getMessage();
                    }

                    return response(\\Illuminate\\Support\\Facades\\View::file(\'/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/vendor/laravel/framework/src/Illuminate/Foundation/Configuration\'.\'/../resources/health-up.blade.php\', [
                        \'exception\' => $exception,
                    ]), status: $exception ? 500 : 200);
                }";s:5:"scope";s:54:"Illuminate\\Foundation\\Configuration\\ApplicationBuilder";s:4:"this";N;s:4:"self";s:32:"00000000000004b30000000000000000";}}',
        'as' => 'generated::94b1VMtyoQrVB9rw',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::mOJIbrAfdhDvTym8' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => '/',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:35:"fn() => \\redirect()->route(\'login\')";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000044c0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::mOJIbrAfdhDvTym8',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'login' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'login',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:268:"function () {
    if (\\Illuminate\\Support\\Facades\\Auth::check() && \\in_array(\\Illuminate\\Support\\Facades\\Auth::user()?->role, [\'super_admin\', \'tenant_admin\'], true)) {
        return \\redirect()->route(\'admin.dashboard\');
    }

    return \\view(\'admin.auth.login\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000048a0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'login',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'login.post' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'login',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:1094:"function (\\Illuminate\\Http\\Request $request) {
    $credentials = $request->validate([
        \'email\' => [\'required\', \'email\'],
        \'password\' => [\'required\', \'string\'],
    ]);

    if (\\Illuminate\\Support\\Facades\\Auth::attempt($credentials, (bool) $request->boolean(\'remember\'))) {
        $request->session()->regenerate();

        $user = \\Illuminate\\Support\\Facades\\Auth::user();
        $isAdmin = \\in_array($user?->role, [\'super_admin\', \'tenant_admin\'], true);
        $isActive = (bool) ($user?->is_active ?? true);

        if (!$isAdmin || !$isActive) {
            \\Illuminate\\Support\\Facades\\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return \\back()->with(\'error\', \'Access denied. Admin account required.\')->onlyInput(\'email\');
        }

        if (\\method_exists($user, \'recordLogin\')) {
            $user->recordLogin($request);
        }

        return \\redirect()->route(\'admin.dashboard\');
    }

    return \\back()->with(\'error\', \'Invalid email or password.\')->onlyInput(\'email\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000003e50000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'login.post',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'logout' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'logout',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:220:"function (\\Illuminate\\Http\\Request $request) {
    \\Illuminate\\Support\\Facades\\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return \\redirect()->route(\'login\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000003e70000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'logout',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'portal.home' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'portal',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:28:"fn() => \\view(\'portal.home\')";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004950000000000000000";}}',
        'as' => 'portal.home',
        'namespace' => NULL,
        'prefix' => '/portal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'portal.package.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'portal/package/{package}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:64:"fn($package) => \\view(\'portal.payment\', [\'package\' => $package])";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000048c0000000000000000";}}',
        'as' => 'portal.package.show',
        'namespace' => NULL,
        'prefix' => '/portal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'portal.payment.initiate' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'portal/payment/initiate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:38:"fn() => \\view(\'portal.payment_status\')";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000048e0000000000000000";}}',
        'as' => 'portal.payment.initiate',
        'namespace' => NULL,
        'prefix' => '/portal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'portal.payment.status' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'portal/payment/status/{checkoutId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:49:"fn($checkoutId) => \\view(\'portal.payment_status\')";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000003f10000000000000000";}}',
        'as' => 'portal.payment.status',
        'namespace' => NULL,
        'prefix' => '/portal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'portal.payment.success' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'portal/payment/success/{payment?}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:71:"fn($payment = null) => \\view(\'portal.success\', [\'payment\' => $payment])";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000003f30000000000000000";}}',
        'as' => 'portal.payment.success',
        'namespace' => NULL,
        'prefix' => '/portal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'portal.voucher.redeem' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'portal/voucher/redeem',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:31:"fn() => \\view(\'portal.voucher\')";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000003f50000000000000000";}}',
        'as' => 'portal.voucher.redeem',
        'namespace' => NULL,
        'prefix' => '/portal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'wifi.packages' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'wifi',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\CaptivePortalController@packages',
        'controller' => 'App\\Http\\Controllers\\CaptivePortalController@packages',
        'as' => 'wifi.packages',
        'namespace' => NULL,
        'prefix' => '/wifi',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'wifi.pay' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'wifi/pay',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\CaptivePortalController@pay',
        'controller' => 'App\\Http\\Controllers\\CaptivePortalController@pay',
        'as' => 'wifi.pay',
        'namespace' => NULL,
        'prefix' => '/wifi',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'wifi.status' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'wifi/status/{phone}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\CaptivePortalController@status',
        'controller' => 'App\\Http\\Controllers\\CaptivePortalController@status',
        'as' => 'wifi.status',
        'namespace' => NULL,
        'prefix' => '/wifi',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'wifi.status.check' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'wifi/status/{phone}/check',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\CaptivePortalController@checkStatus',
        'controller' => 'App\\Http\\Controllers\\CaptivePortalController@checkStatus',
        'as' => 'wifi.status.check',
        'namespace' => NULL,
        'prefix' => '/wifi',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'wifi.reconnect.form' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'wifi/reconnect',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:34:"fn() => \\view(\'captive.reconnect\')";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004900000000000000000";}}',
        'as' => 'wifi.reconnect.form',
        'namespace' => NULL,
        'prefix' => '/wifi',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'wifi.reconnect' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'wifi/reconnect',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\CaptivePortalController@reconnect',
        'controller' => 'App\\Http\\Controllers\\CaptivePortalController@reconnect',
        'as' => 'wifi.reconnect',
        'namespace' => NULL,
        'prefix' => '/wifi',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'wifi.extend' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'wifi/extend',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\CaptivePortalController@extend',
        'controller' => 'App\\Http\\Controllers\\CaptivePortalController@extend',
        'as' => 'wifi.extend',
        'namespace' => NULL,
        'prefix' => '/wifi',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::MYszu6LUtgRePYIs' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/wifi',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:258:"function () {
    $tenantId = (int) (\\Illuminate\\Support\\Facades\\Auth::user()?->tenant_id ?? 0);
    if ($tenantId > 0) {
        return \\redirect()->route(\'wifi.packages\', [\'tenant_id\' => $tenantId]);
    }

    return \\redirect()->route(\'wifi.packages\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000003e90000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::MYszu6LUtgRePYIs',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.mpesa.callback.legacy' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/mpesa/callback',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:188:"function (\\Illuminate\\Http\\Request $request) {
    \\Log::info(\'M-Pesa Callback Received\', $request->all());
    return \\response()->json([\'ResultCode\' => 0, \'ResultDesc\' => \'Accepted\']);
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000003eb0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.mpesa.callback.legacy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.dashboard' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/dashboard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@dashboard',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@dashboard',
        'as' => 'admin.dashboard',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.routers.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/routers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@routers',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@routers',
        'as' => 'admin.routers.index',
        'namespace' => NULL,
        'prefix' => 'admin/routers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.routers.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/routers/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@routersCreate',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@routersCreate',
        'as' => 'admin.routers.create',
        'namespace' => NULL,
        'prefix' => 'admin/routers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.routers.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/routers/{router}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@routersShow',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@routersShow',
        'as' => 'admin.routers.show',
        'namespace' => NULL,
        'prefix' => 'admin/routers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.routers.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/routers/{router}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@routersEdit',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@routersEdit',
        'as' => 'admin.routers.edit',
        'namespace' => NULL,
        'prefix' => 'admin/routers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.packages.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/packages',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@packages',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@packages',
        'as' => 'admin.packages.index',
        'namespace' => NULL,
        'prefix' => 'admin/packages',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.packages.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/packages/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@packagesCreate',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@packagesCreate',
        'as' => 'admin.packages.create',
        'namespace' => NULL,
        'prefix' => 'admin/packages',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.packages.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/packages/{package}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@packagesShow',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@packagesShow',
        'as' => 'admin.packages.show',
        'namespace' => NULL,
        'prefix' => 'admin/packages',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.packages.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/packages/{package}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@packagesEdit',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@packagesEdit',
        'as' => 'admin.packages.edit',
        'namespace' => NULL,
        'prefix' => 'admin/packages',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.vouchers.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/vouchers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@vouchersIndex',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@vouchersIndex',
        'as' => 'admin.vouchers.index',
        'namespace' => NULL,
        'prefix' => 'admin/vouchers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.vouchers.generate' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/vouchers/generate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@vouchersGenerate',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@vouchersGenerate',
        'as' => 'admin.vouchers.generate',
        'namespace' => NULL,
        'prefix' => 'admin/vouchers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.vouchers.print' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/vouchers/print',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@vouchersPrint',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@vouchersPrint',
        'as' => 'admin.vouchers.print',
        'namespace' => NULL,
        'prefix' => 'admin/vouchers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.payments.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/payments',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@payments',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@payments',
        'as' => 'admin.payments.index',
        'namespace' => NULL,
        'prefix' => 'admin/payments',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.payments.export' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/payments/export',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@paymentsExport',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@paymentsExport',
        'as' => 'admin.payments.export',
        'namespace' => NULL,
        'prefix' => 'admin/payments',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.clients.hotspot' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/clients/hotspot',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@clientsHotspot',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@clientsHotspot',
        'as' => 'admin.clients.hotspot',
        'namespace' => NULL,
        'prefix' => 'admin/clients',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.clients.pppoe' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/clients/pppoe',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@clientsPppoe',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@clientsPppoe',
        'as' => 'admin.clients.pppoe',
        'namespace' => NULL,
        'prefix' => 'admin/clients',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.clients.customers' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/clients/customers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@clientsCustomers',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@clientsCustomers',
        'as' => 'admin.clients.customers',
        'namespace' => NULL,
        'prefix' => 'admin/clients',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.settings' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/settings',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@settingsIndex',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@settingsIndex',
        'as' => 'admin.settings',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.settings.mpesa' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/settings/mpesa',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@settingsMpesa',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@settingsMpesa',
        'as' => 'admin.settings.mpesa',
        'namespace' => NULL,
        'prefix' => 'admin/settings',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.settings.sms' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/settings/sms',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@settingsSms',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@settingsSms',
        'as' => 'admin.settings.sms',
        'namespace' => NULL,
        'prefix' => 'admin/settings',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.settings.branding' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/settings/branding',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@settingsBranding',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@settingsBranding',
        'as' => 'admin.settings.branding',
        'namespace' => NULL,
        'prefix' => 'admin/settings',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.settings.account' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/settings/account',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AdminPageController@settingsAccount',
        'controller' => 'App\\Http\\Controllers\\Admin\\AdminPageController@settingsAccount',
        'as' => 'admin.settings.account',
        'namespace' => NULL,
        'prefix' => 'admin/settings',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.settings.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/api/settings',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:2:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}s:21:"buildMikrotikCommands";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:1181:"function (array $settings): array {
            $radiusServer = \\trim((string) ($settings[\'radius_server\'] ?? \\config(\'radius.server_ip\', \'127.0.0.1\')));
            $radiusAuthPort = (int) ($settings[\'radius_port\'] ?? \\config(\'radius.auth_port\', 1812));
            $radiusAcctPort = (int) ($settings[\'radius_acct_port\'] ?? \\config(\'radius.acct_port\', 1813));
            $radiusSecret = \\trim((string) ($settings[\'radius_secret\'] ?? \\config(\'radius.shared_secret\', \'\')));

            $safeServer = $radiusServer !== \'\' ? $radiusServer : \'YOUR_RADIUS_SERVER_IP\';
            $safeSecret = $radiusSecret !== \'\' ? $radiusSecret : \'YOUR_SHARED_SECRET\';

            return [
                \'/radius add service=hotspot,ppp address=\' . $safeServer . \' protocol=udp authentication-port=\' . \\max(1, $radiusAuthPort) . \' accounting-port=\' . \\max(1, $radiusAcctPort) . \' secret=\' . $safeSecret . \' timeout=300ms\',
                \'/ip hotspot profile set [find] use-radius=yes\',
                \'/ppp aaa set use-radius=yes accounting=yes interim-update=1m\',
                \'/radius incoming set accept=yes port=3799\',
                \'/radius monitor 0 once\',
            ];
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004b10000000000000000";}}s:8:"function";s:688:"function () use ($resolveTenant, $buildMikrotikCommands) {
            $tenant = $resolveTenant();

            $settings = [];
            if ($tenant) {
                $tenantSettings = (array) ($tenant->settings ?? []);
                $settings = (array) ($tenantSettings[\'admin_settings\'] ?? []);
            } else {
                $settings = (array) \\cache()->get(\'admin_settings_global\', []);
            }

            return \\response()->json([
                \'success\' => true,
                \'data\' => [
                    \'settings\' => $settings,
                    \'mikrotik_commands\' => $buildMikrotikCommands($settings),
                ],
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000044b0000000000000000";}}',
        'as' => 'admin.api.settings.show',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.settings.save' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/api/settings',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:2:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}s:21:"buildMikrotikCommands";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:1181:"function (array $settings): array {
            $radiusServer = \\trim((string) ($settings[\'radius_server\'] ?? \\config(\'radius.server_ip\', \'127.0.0.1\')));
            $radiusAuthPort = (int) ($settings[\'radius_port\'] ?? \\config(\'radius.auth_port\', 1812));
            $radiusAcctPort = (int) ($settings[\'radius_acct_port\'] ?? \\config(\'radius.acct_port\', 1813));
            $radiusSecret = \\trim((string) ($settings[\'radius_secret\'] ?? \\config(\'radius.shared_secret\', \'\')));

            $safeServer = $radiusServer !== \'\' ? $radiusServer : \'YOUR_RADIUS_SERVER_IP\';
            $safeSecret = $radiusSecret !== \'\' ? $radiusSecret : \'YOUR_SHARED_SECRET\';

            return [
                \'/radius add service=hotspot,ppp address=\' . $safeServer . \' protocol=udp authentication-port=\' . \\max(1, $radiusAuthPort) . \' accounting-port=\' . \\max(1, $radiusAcctPort) . \' secret=\' . $safeSecret . \' timeout=300ms\',
                \'/ip hotspot profile set [find] use-radius=yes\',
                \'/ppp aaa set use-radius=yes accounting=yes interim-update=1m\',
                \'/radius incoming set accept=yes port=3799\',
                \'/radius monitor 0 once\',
            ];
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004b10000000000000000";}}s:8:"function";s:834:"function (\\Illuminate\\Http\\Request $request) use ($resolveTenant, $buildMikrotikCommands) {
            $settings = (array) $request->input(\'settings\', []);

            $tenant = $resolveTenant();
            if ($tenant) {
                $tenantSettings = (array) ($tenant->settings ?? []);
                $tenantSettings[\'admin_settings\'] = $settings;
                $tenant->settings = $tenantSettings;
                $tenant->save();
            } else {
                \\cache()->forever(\'admin_settings_global\', $settings);
            }

            return \\response()->json([
                \'success\' => true,
                \'message\' => \'Settings saved successfully\',
                \'data\' => [
                    \'mikrotik_commands\' => $buildMikrotikCommands($settings),
                ],
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004ae0000000000000000";}}',
        'as' => 'admin.api.settings.save',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.settings.mikrotik.test' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/api/settings/mikrotik/test',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:2:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}s:21:"buildMikrotikCommands";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:1181:"function (array $settings): array {
            $radiusServer = \\trim((string) ($settings[\'radius_server\'] ?? \\config(\'radius.server_ip\', \'127.0.0.1\')));
            $radiusAuthPort = (int) ($settings[\'radius_port\'] ?? \\config(\'radius.auth_port\', 1812));
            $radiusAcctPort = (int) ($settings[\'radius_acct_port\'] ?? \\config(\'radius.acct_port\', 1813));
            $radiusSecret = \\trim((string) ($settings[\'radius_secret\'] ?? \\config(\'radius.shared_secret\', \'\')));

            $safeServer = $radiusServer !== \'\' ? $radiusServer : \'YOUR_RADIUS_SERVER_IP\';
            $safeSecret = $radiusSecret !== \'\' ? $radiusSecret : \'YOUR_SHARED_SECRET\';

            return [
                \'/radius add service=hotspot,ppp address=\' . $safeServer . \' protocol=udp authentication-port=\' . \\max(1, $radiusAuthPort) . \' accounting-port=\' . \\max(1, $radiusAcctPort) . \' secret=\' . $safeSecret . \' timeout=300ms\',
                \'/ip hotspot profile set [find] use-radius=yes\',
                \'/ppp aaa set use-radius=yes accounting=yes interim-update=1m\',
                \'/radius incoming set accept=yes port=3799\',
                \'/radius monitor 0 once\',
            ];
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004b10000000000000000";}}s:8:"function";s:2210:"function (\\Illuminate\\Http\\Request $request, \\App\\Services\\MikroTik\\MikroTikService $mikroTikService) use ($resolveTenant, $buildMikrotikCommands) {
            $tenant = $resolveTenant();

            $settings = (array) $request->input(\'settings\', []);
            $commands = $buildMikrotikCommands($settings);

            $router = \\App\\Models\\Router::query()
                ->when($tenant, fn ($query) => $query->where(\'tenant_id\', $tenant->id))
                ->orderByDesc(\'status\')
                ->orderBy(\'id\')
                ->first();

            if (!$router) {
                return \\response()->json([
                    \'success\' => false,
                    \'message\' => \'No router found for this tenant. Add a router first.\',
                    \'commands\' => $commands,
                ], 404);
            }

            $isOnline = $mikroTikService->pingRouter($router);

            if (!$isOnline) {
                return \\response()->json([
                    \'success\' => false,
                    \'message\' => \'Router is offline or unreachable\',
                    \'router\' => [
                        \'id\' => $router->id,
                        \'name\' => $router->name,
                        \'ip_address\' => $router->ip_address,
                    ],
                    \'commands\' => $commands,
                ], 503);
            }

            $systemInfo = $mikroTikService->getRouterSystemInfo($router);

            return \\response()->json([
                \'success\' => true,
                \'message\' => \'MikroTik API connection successful\',
                \'router\' => [
                    \'id\' => $router->id,
                    \'name\' => $router->name,
                    \'ip_address\' => $router->ip_address,
                    \'status\' => $router->status,
                ],
                \'data\' => [
                    \'cpu\' => $systemInfo[\'cpu_load\'] ?? null,
                    \'memory\' => $systemInfo[\'memory_usage\'] ?? null,
                    \'uptime\' => $systemInfo[\'uptime\'] ?? null,
                    \'version\' => $systemInfo[\'version\'] ?? null,
                ],
                \'commands\' => $commands,
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004b00000000000000000";}}',
        'as' => 'admin.api.settings.mikrotik.test',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.routers.status' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/api/routers/status',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:2883:"function (\\Illuminate\\Http\\Request $request, \\App\\Services\\MikroTik\\MikroTikService $mikroTikService) use ($resolveTenant) {
            $tenant = $resolveTenant();
            $live = $request->boolean(\'live\', true);

            $query = \\App\\Models\\Router::query();
            if ($tenant) {
                $query->where(\'tenant_id\', $tenant->id);
            }

            $routers = $query
                ->orderBy(\'name\')
                ->limit(100)
                ->get()
                ->map(function (\\App\\Models\\Router $router) use ($live, $mikroTikService) {
                    $status = (string) ($router->status ?? \\App\\Models\\Router::STATUS_OFFLINE);
                    $cpu = $router->cpu_usage;
                    $memory = $router->memory_usage;

                    if ($live) {
                        $isOnline = $mikroTikService->pingRouter($router);
                        $status = $isOnline ? \\App\\Models\\Router::STATUS_ONLINE : \\App\\Models\\Router::STATUS_OFFLINE;

                        if ($isOnline) {
                            $systemInfo = $mikroTikService->getRouterSystemInfo($router);
                            $cpu = isset($systemInfo[\'cpu_load\']) ? (int) $systemInfo[\'cpu_load\'] : $cpu;
                            $memory = isset($systemInfo[\'memory_usage\']) ? (int) $systemInfo[\'memory_usage\'] : $memory;

                            if ((int) $cpu >= \\App\\Models\\Router::HEALTHY_CPU_THRESHOLD || (int) $memory >= \\App\\Models\\Router::HEALTHY_MEMORY_THRESHOLD) {
                                $router->markWarning(\'High resource usage\');
                                $status = \\App\\Models\\Router::STATUS_WARNING;
                            }
                        }
                    }

                    return [
                        \'id\' => $router->id,
                        \'name\' => $router->name,
                        \'ip\' => $router->ip_address,
                        \'status\' => $status,
                        \'users\' => (int) ($router->active_sessions ?? 0),
                        \'cpu\' => $cpu,
                        \'memory\' => $memory,
                        \'last_seen_at\' => $router->last_seen_at?->toIso8601String(),
                    ];
                });

            $online = $routers->filter(fn ($router) => \\in_array($router[\'status\'], [\\App\\Models\\Router::STATUS_ONLINE, \\App\\Models\\Router::STATUS_WARNING], true))->count();
            $offline = $routers->where(\'status\', \\App\\Models\\Router::STATUS_OFFLINE)->count();

            return \\response()->json([
                \'success\' => true,
                \'data\' => $routers,
                \'summary\' => [
                    \'online\' => $online,
                    \'offline\' => $offline,
                    \'total\' => $routers->count(),
                    \'live\' => $live,
                ],
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004230000000000000000";}}',
        'as' => 'admin.api.routers.status',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.routers.test' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/api/routers/test',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:1471:"function (\\Illuminate\\Http\\Request $request, \\App\\Services\\MikroTik\\MikroTikService $mikroTikService) use ($resolveTenant) {
            $tenant = $resolveTenant();

            $router = \\App\\Models\\Router::query()
                ->when($tenant, fn ($q) => $q->where(\'tenant_id\', $tenant->id))
                ->find($request->input(\'router_id\'));

            if (!$router) {
                return \\response()->json([
                    \'success\' => false,
                    \'message\' => \'Router not found\',
                ], 404);
            }

            $isOnline = $mikroTikService->pingRouter($router);

            if (!$isOnline) {
                return \\response()->json([
                    \'success\' => false,
                    \'message\' => \'Router is offline or unreachable\',
                    \'router\' => $router->id,
                ], 503);
            }

            $systemInfo = $mikroTikService->getRouterSystemInfo($router);

            return \\response()->json([
                \'success\' => true,
                \'message\' => \'Router is online\',
                \'router\' => $router->id,
                \'data\' => [
                    \'status\' => $router->status,
                    \'cpu\' => $systemInfo[\'cpu_load\'] ?? $router->cpu_usage,
                    \'memory\' => $systemInfo[\'memory_usage\'] ?? $router->memory_usage,
                    \'uptime\' => $systemInfo[\'uptime\'] ?? null,
                ],
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004250000000000000000";}}',
        'as' => 'admin.api.routers.test',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.packages.stats' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/api/packages/stats',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:984:"function () use ($resolveTenant) {
            $tenant = $resolveTenant();

            $packages = \\App\\Models\\Package::query()->when($tenant, fn ($q) => $q->where(\'tenant_id\', $tenant->id));
            $payments = \\App\\Models\\Payment::query()->when($tenant, fn ($q) => $q->where(\'tenant_id\', $tenant->id));

            return \\response()->json([
                \'total\' => (clone $packages)->count(),
                \'active\' => (clone $packages)->where(\'is_active\', true)->count(),
                \'revenue_today\' => (float) (clone $payments)
                    ->whereDate(\'created_at\', \\now()->toDateString())
                    ->whereIn(\'status\', [\'completed\', \'confirmed\'])
                    ->sum(\'amount\'),
                \'revenue_week\' => (float) (clone $payments)
                    ->where(\'created_at\', \'>=\', \\now()->startOfWeek())
                    ->whereIn(\'status\', [\'completed\', \'confirmed\'])
                    ->sum(\'amount\'),
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004270000000000000000";}}',
        'as' => 'admin.api.packages.stats',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.dashboard.summary' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/api/dashboard/summary',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:2652:"function () use ($resolveTenant) {
            $tenant = $resolveTenant();

            $payments = \\App\\Models\\Payment::query()->when($tenant, fn ($query) => $query->where(\'tenant_id\', $tenant->id));
            $packages = \\App\\Models\\Package::query()->when($tenant, fn ($query) => $query->where(\'tenant_id\', $tenant->id));
            $routers = \\App\\Models\\Router::query()->when($tenant, fn ($query) => $query->where(\'tenant_id\', $tenant->id));
            $sessions = \\App\\Models\\UserSession::query()->when($tenant, fn ($query) => $query->where(\'tenant_id\', $tenant->id));

            $successStatuses = [\'completed\', \'confirmed\'];
            $weeklyRevenue = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = \\now()->subDays($i);
                $weeklyRevenue[] = [
                    \'date\' => $day->toDateString(),
                    \'label\' => $day->format(\'D\'),
                    \'amount\' => (float) (clone $payments)
                        ->whereDate(\'created_at\', $day->toDateString())
                        ->whereIn(\'status\', $successStatuses)
                        ->sum(\'amount\'),
                ];
            }

            $thisWeekTx = (clone $payments)->where(\'created_at\', \'>=\', \\now()->startOfWeek());
            $successThisWeek = (clone $thisWeekTx)->whereIn(\'status\', $successStatuses)->count();
            $totalThisWeek = (clone $thisWeekTx)->count();

            return \\response()->json([
                \'success\' => true,
                \'data\' => [
                    \'revenue_today\' => (float) (clone $payments)
                        ->whereDate(\'created_at\', \\now()->toDateString())
                        ->whereIn(\'status\', $successStatuses)
                        ->sum(\'amount\'),
                    \'revenue_week\' => (float) (clone $payments)
                        ->where(\'created_at\', \'>=\', \\now()->startOfWeek())
                        ->whereIn(\'status\', $successStatuses)
                        ->sum(\'amount\'),
                    \'active_sessions\' => (clone $sessions)->where(\'status\', \'active\')->count(),
                    \'packages_total\' => (clone $packages)->count(),
                    \'routers_online\' => (clone $routers)->where(\'status\', \'online\')->count(),
                    \'routers_total\' => (clone $routers)->count(),
                    \'transactions_week\' => $totalThisWeek,
                    \'success_rate_week\' => $totalThisWeek > 0
                        ? \\round(($successThisWeek / $totalThisWeek) * 100)
                        : 0,
                    \'weekly_revenue\' => $weeklyRevenue,
                ],
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004290000000000000000";}}',
        'as' => 'admin.api.dashboard.summary',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.packages.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/api/packages',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:1319:"function () use ($resolveTenant) {
            $tenant = $resolveTenant();

            $packages = \\App\\Models\\Package::query()
                ->when($tenant, fn ($query) => $query->where(\'tenant_id\', $tenant->id))
                ->orderBy(\'sort_order\')
                ->orderBy(\'price\')
                ->limit(200)
                ->get()
                ->map(fn (\\App\\Models\\Package $package) => [
                    \'id\' => $package->id,
                    \'name\' => $package->name,
                    \'description\' => $package->description,
                    \'price\' => (float) $package->price,
                    \'duration_value\' => $package->duration_value,
                    \'duration_unit\' => $package->duration_unit,
                    \'download_limit_mbps\' => $package->download_limit_mbps,
                    \'upload_limit_mbps\' => $package->upload_limit_mbps,
                    \'mikrotik_profile_name\' => $package->mikrotik_profile_name,
                    \'is_active\' => (bool) $package->is_active,
                    \'total_sales\' => (int) ($package->total_sales ?? 0),
                    \'sort_order\' => $package->sort_order,
                ]);

            return \\response()->json([
                \'success\' => true,
                \'data\' => $packages,
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000042b0000000000000000";}}',
        'as' => 'admin.api.packages.index',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.packages.create' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/api/packages',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:2746:"function (\\Illuminate\\Http\\Request $request) use ($resolveTenant) {
            $tenant = $resolveTenant();

            if (!$tenant) {
                return \\response()->json([
                    \'success\' => false,
                    \'message\' => \'Tenant not found\',
                ], 404);
            }

            $validated = $request->validate([
                \'name\' => \'required|string|max:120\',
                \'description\' => \'nullable|string|max:255\',
                \'price\' => \'required|numeric|min:0\',
                \'duration_value\' => \'required|integer|min:1|max:100000\',
                \'duration_unit\' => \'required|in:minutes,hours,days,weeks,months\',
                \'download_limit_mbps\' => \'nullable|integer|min:1|max:100000\',
                \'upload_limit_mbps\' => \'nullable|integer|min:1|max:100000\',
                \'mikrotik_profile_name\' => \'nullable|string|max:120\',
                \'is_active\' => \'nullable|boolean\',
            ]);

            $codeBase = \\strtoupper(\\preg_replace(\'/[^A-Z0-9]+/\', \'-\', (string) $validated[\'name\']));
            $codeBase = \\trim($codeBase, \'-\');
            if ($codeBase === \'\') {
                $codeBase = \'PKG\';
            }

            $code = $codeBase;
            $attempt = 1;
            while (\\App\\Models\\Package::query()->where(\'code\', $code)->exists()) {
                $attempt++;
                $code = $codeBase . \'-\' . $attempt;
            }

            $sortOrder = (int) (\\App\\Models\\Package::query()
                ->where(\'tenant_id\', $tenant->id)
                ->max(\'sort_order\') ?? 0) + 1;

            $package = \\App\\Models\\Package::create([
                \'tenant_id\' => $tenant->id,
                \'name\' => $validated[\'name\'],
                \'description\' => $validated[\'description\'] ?? null,
                \'code\' => $code,
                \'price\' => $validated[\'price\'],
                \'currency\' => \'KES\',
                \'duration_value\' => $validated[\'duration_value\'],
                \'duration_unit\' => $validated[\'duration_unit\'],
                \'download_limit_mbps\' => $validated[\'download_limit_mbps\'] ?? null,
                \'upload_limit_mbps\' => $validated[\'upload_limit_mbps\'] ?? null,
                \'mikrotik_profile_name\' => $validated[\'mikrotik_profile_name\'] ?? null,
                \'is_active\' => (bool) ($validated[\'is_active\'] ?? true),
                \'sort_order\' => $sortOrder,
            ]);

            return \\response()->json([
                \'success\' => true,
                \'message\' => \'Package created successfully\',
                \'data\' => [
                    \'id\' => $package->id,
                    \'name\' => $package->name,
                ],
            ], 201);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000042d0000000000000000";}}',
        'as' => 'admin.api.packages.create',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.packages.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'admin/api/packages/{package}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:1880:"function (\\Illuminate\\Http\\Request $request, \\App\\Models\\Package $package) use ($resolveTenant) {
            $tenant = $resolveTenant();

            if (!$tenant || (int) $package->tenant_id !== (int) $tenant->id) {
                return \\response()->json([
                    \'success\' => false,
                    \'message\' => \'Package not found\',
                ], 404);
            }

            $validated = $request->validate([
                \'name\' => \'required|string|max:120\',
                \'description\' => \'nullable|string|max:255\',
                \'price\' => \'required|numeric|min:0\',
                \'duration_value\' => \'required|integer|min:1|max:100000\',
                \'duration_unit\' => \'required|in:minutes,hours,days,weeks,months\',
                \'download_limit_mbps\' => \'nullable|integer|min:1|max:100000\',
                \'upload_limit_mbps\' => \'nullable|integer|min:1|max:100000\',
                \'mikrotik_profile_name\' => \'nullable|string|max:120\',
                \'is_active\' => \'nullable|boolean\',
            ]);

            $package->update([
                \'name\' => $validated[\'name\'],
                \'description\' => $validated[\'description\'] ?? null,
                \'price\' => $validated[\'price\'],
                \'duration_value\' => $validated[\'duration_value\'],
                \'duration_unit\' => $validated[\'duration_unit\'],
                \'download_limit_mbps\' => $validated[\'download_limit_mbps\'] ?? null,
                \'upload_limit_mbps\' => $validated[\'upload_limit_mbps\'] ?? null,
                \'mikrotik_profile_name\' => $validated[\'mikrotik_profile_name\'] ?? null,
                \'is_active\' => (bool) ($validated[\'is_active\'] ?? false),
            ]);

            return \\response()->json([
                \'success\' => true,
                \'message\' => \'Package updated successfully\',
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000042f0000000000000000";}}',
        'as' => 'admin.api.packages.update',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.packages.status' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'admin/api/packages/{package}/status',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:774:"function (\\Illuminate\\Http\\Request $request, \\App\\Models\\Package $package) use ($resolveTenant) {
            $tenant = $resolveTenant();

            if (!$tenant || (int) $package->tenant_id !== (int) $tenant->id) {
                return \\response()->json([
                    \'success\' => false,
                    \'message\' => \'Package not found\',
                ], 404);
            }

            $validated = $request->validate([
                \'is_active\' => \'required|boolean\',
            ]);

            $package->update([
                \'is_active\' => (bool) $validated[\'is_active\'],
            ]);

            return \\response()->json([
                \'success\' => true,
                \'message\' => \'Package status updated\',
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004310000000000000000";}}',
        'as' => 'admin.api.packages.status',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.packages.delete' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/api/packages/{package}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:553:"function (\\App\\Models\\Package $package) use ($resolveTenant) {
            $tenant = $resolveTenant();

            if (!$tenant || (int) $package->tenant_id !== (int) $tenant->id) {
                return \\response()->json([
                    \'success\' => false,
                    \'message\' => \'Package not found\',
                ], 404);
            }

            $package->delete();

            return \\response()->json([
                \'success\' => true,
                \'message\' => \'Package deleted successfully\',
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004330000000000000000";}}',
        'as' => 'admin.api.packages.delete',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.clients.stats' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/api/clients/stats',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:992:"function () use ($resolveTenant) {
            $tenant = $resolveTenant();

            $mode = \\request()->query(\'mode\');

            $sessions = \\App\\Models\\UserSession::query()
                ->when($tenant, fn ($q) => $q->where(\'tenant_id\', $tenant->id))
                ->when($mode === \'pppoe\', fn ($q) => $q->where(\'username\', \'like\', \'pppoe%\'))
                ->when($mode === \'hotspot\', fn ($q) => $q->where(function ($inner) {
                    $inner->whereNull(\'username\')->orWhere(\'username\', \'not like\', \'pppoe%\');
                }));

            $activeSessions = (clone $sessions)->where(\'status\', \'active\')->count();
            $totalBytes = (int) (clone $sessions)->sum(\'bytes_total\');
            $totalGb = \\round($totalBytes / (1024 * 1024 * 1024), 2);

            return \\response()->json([
                \'hotspot_active\' => $activeSessions,
                \'pppoe_active\' => 0,
                \'total_bandwidth\' => $totalGb . \' GB\',
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004350000000000000000";}}',
        'as' => 'admin.api.clients.stats',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.clients.sessions' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/api/clients/sessions',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:2519:"function (\\Illuminate\\Http\\Request $request) use ($resolveTenant) {
            $tenant = $resolveTenant();

            $limit = \\min(\\max((int) $request->integer(\'limit\', 150), 1), 500);
            $status = $request->string(\'status\')->toString();
            $search = \\trim($request->string(\'search\')->toString());
            $mode = $request->string(\'mode\')->toString();

            $sessions = \\App\\Models\\UserSession::query()
                ->when($tenant, fn ($query) => $query->where(\'tenant_id\', $tenant->id))
                ->when($mode === \'pppoe\', fn ($query) => $query->where(\'username\', \'like\', \'pppoe%\'))
                ->when($mode === \'hotspot\', fn ($query) => $query->where(function ($inner) {
                    $inner->whereNull(\'username\')->orWhere(\'username\', \'not like\', \'pppoe%\');
                }))
                ->when($status !== \'\', fn ($query) => $query->where(\'status\', $status))
                ->when($search !== \'\', function ($query) use ($search) {
                    $query->where(function ($inner) use ($search) {
                        $inner->where(\'username\', \'like\', "%{$search}%")
                            ->orWhere(\'phone\', \'like\', "%{$search}%")
                            ->orWhere(\'ip_address\', \'like\', "%{$search}%")
                            ->orWhere(\'mac_address\', \'like\', "%{$search}%");
                    });
                })
                ->with([\'package\', \'router\'])
                ->latest(\'created_at\')
                ->limit($limit)
                ->get()
                ->map(function (\\App\\Models\\UserSession $session) {
                    return [
                        \'id\' => $session->id,
                        \'username\' => $session->username,
                        \'phone\' => $session->phone,
                        \'status\' => $session->status,
                        \'ip_address\' => $session->ip_address,
                        \'mac_address\' => $session->mac_address,
                        \'router\' => $session->router?->name,
                        \'package\' => $session->package?->name,
                        \'expires_at\' => $session->expires_at?->toIso8601String(),
                        \'started_at\' => $session->started_at?->toIso8601String(),
                        \'bytes_total\' => (int) ($session->bytes_total ?? 0),
                    ];
                });

            return \\response()->json([
                \'success\' => true,
                \'data\' => $sessions,
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004370000000000000000";}}',
        'as' => 'admin.api.clients.sessions',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.clients.disconnect' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/api/clients/disconnect',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:1027:"function (\\Illuminate\\Http\\Request $request) use ($resolveTenant) {
            $tenant = $resolveTenant();

            $session = \\App\\Models\\UserSession::query()
                ->when($tenant, fn ($q) => $q->where(\'tenant_id\', $tenant->id))
                ->where(\'username\', $request->input(\'username\'))
                ->where(\'status\', \'active\')
                ->first();

            if (!$session) {
                return \\response()->json([
                    \'success\' => false,
                    \'message\' => \'Active session not found\',
                ], 404);
            }

            $session->update([
                \'status\' => \'terminated\',
                \'terminated_at\' => \\now(),
                \'termination_reason\' => \'admin_disconnect\',
            ]);

            return \\response()->json([
                \'success\' => true,
                \'message\' => "Client {$request->input(\'username\')} disconnected",
                \'router\' => $request->input(\'router_id\'),
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004390000000000000000";}}',
        'as' => 'admin.api.clients.disconnect',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.payments.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/api/payments',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:1475:"function (\\Illuminate\\Http\\Request $request) use ($resolveTenant) {
            $tenant = $resolveTenant();

            $limit = \\min(\\max((int) $request->integer(\'limit\', 100), 1), 500);
            $status = $request->string(\'status\')->toString();

            $payments = \\App\\Models\\Payment::query()
                ->when($tenant, fn ($query) => $query->where(\'tenant_id\', $tenant->id))
                ->when($status !== \'\', fn ($query) => $query->where(\'status\', $status))
                ->with(\'package\')
                ->latest(\'created_at\')
                ->limit($limit)
                ->get()
                ->map(function (\\App\\Models\\Payment $payment) {
                    return [
                        \'id\' => $payment->id,
                        \'phone\' => $payment->phone,
                        \'package_name\' => $payment->package_name,
                        \'package_id\' => $payment->package_id,
                        \'amount\' => (float) $payment->amount,
                        \'currency\' => $payment->currency,
                        \'status\' => $payment->status,
                        \'reference\' => $payment->mpesa_receipt_number ?: $payment->mpesa_checkout_request_id,
                        \'created_at\' => $payment->created_at?->toIso8601String(),
                    ];
                });

            return \\response()->json([
                \'success\' => true,
                \'data\' => $payments,
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000043b0000000000000000";}}',
        'as' => 'admin.api.payments.index',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.payments.stats' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/api/payments/stats',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:1549:"function () use ($resolveTenant) {
            $tenant = $resolveTenant();

            $payments = \\App\\Models\\Payment::query()->when($tenant, fn ($query) => $query->where(\'tenant_id\', $tenant->id));
            $successStatuses = [\'completed\', \'confirmed\'];

            $daily = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = \\now()->subDays($i);
                $daily[] = [
                    \'date\' => $day->toDateString(),
                    \'label\' => $day->format(\'D\'),
                    \'amount\' => (float) (clone $payments)
                        ->whereDate(\'created_at\', $day->toDateString())
                        ->whereIn(\'status\', $successStatuses)
                        ->sum(\'amount\'),
                ];
            }

            return \\response()->json([
                \'success\' => true,
                \'data\' => [
                    \'revenue_total\' => (float) (clone $payments)
                        ->whereIn(\'status\', $successStatuses)
                        ->sum(\'amount\'),
                    \'revenue_today\' => (float) (clone $payments)
                        ->whereDate(\'created_at\', \\now()->toDateString())
                        ->whereIn(\'status\', $successStatuses)
                        ->sum(\'amount\'),
                    \'pending\' => (clone $payments)->where(\'status\', \'pending\')->count(),
                    \'failed\' => (clone $payments)->where(\'status\', \'failed\')->count(),
                    \'daily_revenue\' => $daily,
                ],
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000043d0000000000000000";}}',
        'as' => 'admin.api.payments.stats',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.vouchers.generate' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/api/vouchers/generate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:4164:"function (\\Illuminate\\Http\\Request $request) use ($resolveTenant) {
            $tenant = $resolveTenant();

            if (!$tenant) {
                return \\response()->json([
                    \'success\' => false,
                    \'message\' => \'Tenant not found\',
                ], 404);
            }

            $validated = $request->validate([
                \'package_id\' => \'required|integer|exists:packages,id\',
                \'quantity\' => \'required|integer|min:1|max:1000\',
                \'validity_hours\' => \'required|integer|min:1|max:8760\',
                \'prefix\' => \'nullable|string|max:20\',
                \'batch_label\' => \'nullable|string|max:120\',
            ]);

            $package = \\App\\Models\\Package::query()
                ->where(\'tenant_id\', $tenant->id)
                ->where(\'is_active\', true)
                ->find($validated[\'package_id\']);

            if (!$package) {
                return \\response()->json([
                    \'success\' => false,
                    \'message\' => \'Selected package is not available for this tenant\',
                ], 422);
            }

            $prefix = \\strtoupper(\\trim((string) ($validated[\'prefix\'] ?? \'CB-WIFI\')));
            $prefix = \\preg_replace(\'/[^A-Z0-9-]/\', \'\', $prefix);
            $prefix = \\trim((string) $prefix, \'-\');
            if ($prefix === \'\') {
                $prefix = \'CB-WIFI\';
            }

            $batchId = (string) \\Illuminate\\Support\\Str::uuid();
            $batchName = \\trim((string) ($validated[\'batch_label\'] ?? \'\'));
            if ($batchName === \'\') {
                $batchName = \'Batch-\' . \\now()->format(\'Ymd-His\');
            }

            $quantity = (int) $validated[\'quantity\'];
            $validityHours = (int) $validated[\'validity_hours\'];

            $vouchers = \\Illuminate\\Support\\Facades\\DB::transaction(function () use ($tenant, $package, $quantity, $validityHours, $prefix, $batchId, $batchName) {
                $created = \\collect();

                for ($i = 0; $i < $quantity; $i++) {
                    do {
                        $code = \\strtoupper(\\Illuminate\\Support\\Str::random(6));
                        $exists = \\App\\Models\\Voucher::query()
                            ->where(\'tenant_id\', $tenant->id)
                            ->where(\'prefix\', $prefix)
                            ->where(\'code\', $code)
                            ->exists();
                    } while ($exists);

                    $created->push(\\App\\Models\\Voucher::create([
                        \'tenant_id\' => $tenant->id,
                        \'package_id\' => $package->id,
                        \'code\' => $code,
                        \'prefix\' => $prefix,
                        \'status\' => \'unused\',
                        \'valid_from\' => \\now(),
                        \'valid_until\' => \\now()->copy()->addHours($validityHours),
                        \'validity_hours\' => $validityHours,
                        \'batch_id\' => $batchId,
                        \'batch_name\' => $batchName,
                        \'captive_portal_redeemable\' => true,
                    ]));
                }

                return $created;
            });

            return \\response()->json([
                \'success\' => true,
                \'message\' => "Generated {$quantity} voucher(s)",
                \'data\' => [
                    \'batch_id\' => $batchId,
                    \'batch_name\' => $batchName,
                    \'package\' => [
                        \'id\' => $package->id,
                        \'name\' => $package->name,
                    ],
                    \'validity_hours\' => $validityHours,
                    \'vouchers\' => $vouchers->map(fn ($voucher) => [
                        \'id\' => $voucher->id,
                        \'code\' => $voucher->code,
                        \'code_display\' => $voucher->code_display,
                        \'created_at\' => $voucher->created_at?->toIso8601String(),
                        \'valid_until\' => $voucher->valid_until?->toIso8601String(),
                    ]),
                ],
            ], 201);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000043f0000000000000000";}}',
        'as' => 'admin.api.vouchers.generate',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.vouchers.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/api/vouchers/{voucher}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:1178:"function (\\App\\Models\\Voucher $voucher) use ($resolveTenant) {
            $tenant = $resolveTenant();

            if (!$tenant || (int) $voucher->tenant_id !== (int) $tenant->id) {
                return \\response()->json([
                    \'success\' => false,
                    \'message\' => \'Voucher not found\',
                ], 404);
            }

            $voucher->load([\'package\', \'router\']);

            return \\response()->json([
                \'success\' => true,
                \'data\' => [
                    \'id\' => $voucher->id,
                    \'code\' => $voucher->code,
                    \'code_display\' => $voucher->code_display,
                    \'status\' => $voucher->status,
                    \'package_name\' => $voucher->package?->name,
                    \'created_at\' => $voucher->created_at?->toIso8601String(),
                    \'valid_until\' => $voucher->valid_until?->toIso8601String(),
                    \'used_by_phone\' => $voucher->used_by_phone,
                    \'used_at\' => $voucher->used_at?->toIso8601String(),
                    \'router_name\' => $voucher->router?->name,
                ],
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004410000000000000000";}}',
        'as' => 'admin.api.vouchers.show',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.vouchers.delete' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/api/vouchers/{voucher}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:813:"function (\\App\\Models\\Voucher $voucher) use ($resolveTenant) {
            $tenant = $resolveTenant();

            if (!$tenant || (int) $voucher->tenant_id !== (int) $tenant->id) {
                return \\response()->json([
                    \'success\' => false,
                    \'message\' => \'Voucher not found\',
                ], 404);
            }

            if (\\strtolower((string) $voucher->status) === \'used\') {
                return \\response()->json([
                    \'success\' => false,
                    \'message\' => \'Used vouchers cannot be deleted\',
                ], 422);
            }

            $voucher->delete();

            return \\response()->json([
                \'success\' => true,
                \'message\' => \'Voucher deleted successfully\',
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004430000000000000000";}}',
        'as' => 'admin.api.vouchers.delete',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.vouchers.bulk-delete' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/api/vouchers/bulk-delete',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:1603:"function (\\Illuminate\\Http\\Request $request) use ($resolveTenant) {
            $tenant = $resolveTenant();

            if (!$tenant) {
                return \\response()->json([
                    \'success\' => false,
                    \'message\' => \'Tenant not found\',
                ], 404);
            }

            $validated = $request->validate([
                \'voucher_ids\' => \'required|array|min:1\',
                \'voucher_ids.*\' => \'integer\',
            ]);

            $vouchers = \\App\\Models\\Voucher::query()
                ->where(\'tenant_id\', $tenant->id)
                ->whereIn(\'id\', $validated[\'voucher_ids\'])
                ->get();

            if ($vouchers->isEmpty()) {
                return \\response()->json([
                    \'success\' => false,
                    \'message\' => \'No matching vouchers found\',
                ], 404);
            }

            $blocked = $vouchers->filter(fn ($voucher) => \\strtolower((string) $voucher->status) === \'used\')->count();
            $toDelete = $vouchers->reject(fn ($voucher) => \\strtolower((string) $voucher->status) === \'used\');

            foreach ($toDelete as $voucher) {
                $voucher->delete();
            }

            return \\response()->json([
                \'success\' => true,
                \'message\' => "Deleted {$toDelete->count()} voucher(s)" . ($blocked > 0 ? "; skipped {$blocked} used voucher(s)" : \'\'),
                \'data\' => [
                    \'deleted\' => $toDelete->count(),
                    \'skipped_used\' => $blocked,
                ],
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004450000000000000000";}}',
        'as' => 'admin.api.vouchers.bulk-delete',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.api.vouchers.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/api/vouchers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:1:{s:13:"resolveTenant";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:737:"function () {
            $user = \\Illuminate\\Support\\Facades\\Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return \\App\\Models\\Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === \'super_admin\') {
                $requestedTenantId = (int) \\request()->input(\'tenant_id\', \\request()->query(\'tenant_id\', 0));
                if ($requestedTenantId > 0) {
                    return \\App\\Models\\Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004aa0000000000000000";}}s:8:"function";s:1861:"function (\\Illuminate\\Http\\Request $request) use ($resolveTenant) {
            $tenant = $resolveTenant();

            $limit = \\min(\\max((int) $request->integer(\'limit\', 100), 1), 500);
            $status = $request->string(\'status\')->toString();
            $packageId = $request->integer(\'package_id\');
            $search = \\trim($request->string(\'search\')->toString());

            $vouchers = \\App\\Models\\Voucher::query()
                ->when($tenant, fn ($query) => $query->where(\'tenant_id\', $tenant->id))
                ->when($status !== \'\', fn ($query) => $query->where(\'status\', $status))
                ->when($packageId > 0, fn ($query) => $query->where(\'package_id\', $packageId))
                ->when($search !== \'\', fn ($query) => $query->where(\'code\', \'like\', "%{$search}%"))
                ->with(\'package\')
                ->latest(\'created_at\')
                ->limit($limit)
                ->get()
                ->map(function (\\App\\Models\\Voucher $voucher) {
                    return [
                        \'id\' => $voucher->id,
                        \'code\' => $voucher->code,
                        \'code_display\' => $voucher->code_display,
                        \'status\' => $voucher->status,
                        \'package_id\' => $voucher->package_id,
                        \'package_name\' => $voucher->package?->name,
                        \'used_by_phone\' => $voucher->used_by_phone,
                        \'valid_until\' => $voucher->valid_until?->toIso8601String(),
                        \'used_at\' => $voucher->used_at?->toIso8601String(),
                        \'created_at\' => $voucher->created_at?->toIso8601String(),
                    ];
                });

            return \\response()->json([
                \'success\' => true,
                \'data\' => $vouchers,
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004470000000000000000";}}',
        'as' => 'admin.api.vouchers.index',
        'namespace' => NULL,
        'prefix' => 'admin/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::dUU8aSOyxRMhqroW' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => '{fallbackPlaceholder}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:173:"function () {
    if (\\request()->is(\'admin*\')) {
        return \\response()->view(\'admin.errors.404\', [], 404);
    }
    return \\response()->view(\'errors.404\', [], 404);
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000003ed0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::dUU8aSOyxRMhqroW',
      ),
      'fallback' => true,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'fallbackPlaceholder' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Yo4E2mNION8kC4Zi' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'health',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:277:"function () {
    return \\response()->json([
        \'status\' => \'healthy\',
        \'app\' => \\config(\'app.name\'),
        \'env\' => \\config(\'app.env\'),
        \'php\' => \\phpversion(),
        \'laravel\' => \\app()->version(),
        \'timestamp\' => \\now()->toISOString()
    ]);
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004030000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::Yo4E2mNION8kC4Zi',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.health' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/health',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:668:"function () {
    return \\response()->json([
        \'status\' => \'healthy\',
        \'timestamp\' => \\now()->toISOString(),
        \'app\' => [
            \'name\' => \\config(\'app.name\'),
            \'env\' => \\config(\'app.env\'),
            \'version\' => \\file_exists(\\base_path(\'VERSION\')) ? \\trim(\\file_get_contents(\\base_path(\'VERSION\'))) : \'dev\',
        ],
        \'runtime\' => [
            \'php\' => \\phpversion(),
            \'laravel\' => \\app()->version(),
        ],
        \'services\' => [
            \'database\' => \\config(\'database.default\'),
            \'queue\' => \\config(\'queue.default\'),
            \'cache\' => \\config(\'cache.default\'),
        ],
    ]);
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000044e0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'api.health',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.payment.callback' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/payment/callback',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\PaymentController@callback',
        'controller' => 'App\\Http\\Controllers\\Api\\PaymentController@callback',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'api.payment.callback',
        'excluded_middleware' => 
        array (
          0 => 'auth:sanctum',
          1 => 'web',
          2 => 'throttle:api',
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.payment.paystack.callback' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/payment/paystack/callback',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\CaptivePortalController@paystackCallback',
        'controller' => 'App\\Http\\Controllers\\CaptivePortalController@paystackCallback',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'api.payment.paystack.callback',
        'excluded_middleware' => 
        array (
          0 => 'auth:sanctum',
          1 => 'web',
          2 => 'throttle:api',
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.paystack.webhook' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/paystack/webhook',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\CaptivePortalController@paystackCallback',
        'controller' => 'App\\Http\\Controllers\\CaptivePortalController@paystackCallback',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'api.paystack.webhook',
        'excluded_middleware' => 
        array (
          0 => 'auth:sanctum',
          1 => 'web',
          2 => 'throttle:api',
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'intasend.callback' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/intasend/callback',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\PaymentController@callback',
        'controller' => 'App\\Http\\Controllers\\Api\\PaymentController@callback',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'intasend.callback',
        'excluded_middleware' => 
        array (
          0 => 'auth:sanctum',
          1 => 'web',
          2 => 'throttle:api',
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::6ND4U20ToiBmGdbZ' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/mpesa/callback/{tenant?}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:418:"function (\\Illuminate\\Http\\Request $request, ?int $tenantId = null) {
    \\Log::channel(\'payment\')->warning(\'Legacy callback received\', [
        \'tenant_id\' => $tenantId,
        \'ip\' => $request->ip(),
        \'user_agent\' => $request->userAgent(),
    ]);
    
    return \\response()->json([
        \'ResultCode\' => 1,
        \'ResultDesc\' => \'Deprecated endpoint. Use /api/payment/callback\',
    ], 410); // Gone
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004540000000000000000";}}',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'excluded_middleware' => 
        array (
          0 => 'auth:sanctum',
          1 => 'web',
          2 => 'throttle:api',
        ),
        'as' => 'generated::6ND4U20ToiBmGdbZ',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.user' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/user',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:1484:"function (\\Illuminate\\Http\\Request $request) {
        $user = $request->user()->load(\'tenant\');
        $tenant = $user->tenant->loadCount([\'routers\', \'packages\', \'userSessions\']);
        
        return \\response()->json([
            \'success\' => true,
            \'data\' => [
                \'user\' => [
                    \'id\' => $user->id,
                    \'name\' => $user->name,
                    \'email\' => $user->email,
                    \'role\' => $user->role,
                    \'permissions\' => $user->permissions ?? [],
                    \'last_login\' => $user->last_login_at,
                ],
                \'tenant\' => [
                    \'id\' => $tenant->id,
                    \'name\' => $tenant->name,
                    \'business_name\' => $tenant->business_name,
                    \'till_number\' => $tenant->till_number,
                    \'status\' => $tenant->status,
                    \'counts\' => [
                        \'routers\' => $tenant->routers_count,
                        \'packages\' => $tenant->packages_count,
                        \'sessions\' => $tenant->user_sessions_count,
                    ],
                    \'subscription\' => [
                        \'plan\' => $tenant->plan ?? \'starter\',
                        \'status\' => $tenant->subscription_status ?? \'active\',
                        \'next_billing_date\' => $tenant->next_billing_date,
                    ],
                ],
            ],
        ]);
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004580000000000000000";}}',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'api.user',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'mikrotik.routers.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/mikrotik/routers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\MikroTik\\RouterController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\MikroTik\\RouterController@index',
        'as' => 'mikrotik.routers.index',
        'namespace' => NULL,
        'prefix' => 'api/mikrotik',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'mikrotik.routers.ping' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/mikrotik/routers/{router}/ping',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\MikroTik\\RouterController@ping',
        'controller' => 'App\\Http\\Controllers\\Api\\MikroTik\\RouterController@ping',
        'as' => 'mikrotik.routers.ping',
        'namespace' => NULL,
        'prefix' => 'api/mikrotik',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'router' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'mikrotik.routers.sessions' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/mikrotik/routers/{router}/sessions',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\MikroTik\\RouterController@sessions',
        'controller' => 'App\\Http\\Controllers\\Api\\MikroTik\\RouterController@sessions',
        'as' => 'mikrotik.routers.sessions',
        'namespace' => NULL,
        'prefix' => 'api/mikrotik',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'router' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'mikrotik.routers.system' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/mikrotik/routers/{router}/system',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\MikroTik\\RouterController@system',
        'controller' => 'App\\Http\\Controllers\\Api\\MikroTik\\RouterController@system',
        'as' => 'mikrotik.routers.system',
        'namespace' => NULL,
        'prefix' => 'api/mikrotik',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'router' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'mikrotik.routers.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/mikrotik/routers/{router}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\MikroTik\\RouterController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\MikroTik\\RouterController@update',
        'as' => 'mikrotik.routers.update',
        'namespace' => NULL,
        'prefix' => 'api/mikrotik',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'router' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'mikrotik.routers.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/mikrotik/routers/{router}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\MikroTik\\RouterController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\MikroTik\\RouterController@destroy',
        'as' => 'mikrotik.routers.destroy',
        'namespace' => NULL,
        'prefix' => 'api/mikrotik',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'router' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'mikrotik.sessions.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/mikrotik/sessions',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\MikroTik\\SessionController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\MikroTik\\SessionController@index',
        'as' => 'mikrotik.sessions.index',
        'namespace' => NULL,
        'prefix' => 'api/mikrotik',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'mikrotik.sessions.active' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/mikrotik/sessions/active',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\MikroTik\\SessionController@active',
        'controller' => 'App\\Http\\Controllers\\Api\\MikroTik\\SessionController@active',
        'as' => 'mikrotik.sessions.active',
        'namespace' => NULL,
        'prefix' => 'api/mikrotik',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'mikrotik.sessions.expiring' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/mikrotik/sessions/expiring',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\MikroTik\\SessionController@expiring',
        'controller' => 'App\\Http\\Controllers\\Api\\MikroTik\\SessionController@expiring',
        'as' => 'mikrotik.sessions.expiring',
        'namespace' => NULL,
        'prefix' => 'api/mikrotik',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'mikrotik.sessions.search' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/mikrotik/sessions/search',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\MikroTik\\SessionController@search',
        'controller' => 'App\\Http\\Controllers\\Api\\MikroTik\\SessionController@search',
        'as' => 'mikrotik.sessions.search',
        'namespace' => NULL,
        'prefix' => 'api/mikrotik',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'mikrotik.sessions.disconnect' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/mikrotik/sessions/{session}/disconnect',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\MikroTik\\SessionController@disconnect',
        'controller' => 'App\\Http\\Controllers\\Api\\MikroTik\\SessionController@disconnect',
        'as' => 'mikrotik.sessions.disconnect',
        'namespace' => NULL,
        'prefix' => 'api/mikrotik',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'session' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'mikrotik.sessions.disconnect-bulk' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/mikrotik/sessions/disconnect-bulk',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\MikroTik\\SessionController@bulkDisconnect',
        'controller' => 'App\\Http\\Controllers\\Api\\MikroTik\\SessionController@bulkDisconnect',
        'as' => 'mikrotik.sessions.disconnect-bulk',
        'namespace' => NULL,
        'prefix' => 'api/mikrotik',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'mikrotik.sessions.extend' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/mikrotik/sessions/{session}/extend',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\MikroTik\\SessionController@extend',
        'controller' => 'App\\Http\\Controllers\\Api\\MikroTik\\SessionController@extend',
        'as' => 'mikrotik.sessions.extend',
        'namespace' => NULL,
        'prefix' => 'api/mikrotik',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'session' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'payments.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/payments',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\PaymentController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\PaymentController@index',
        'as' => 'payments.index',
        'namespace' => NULL,
        'prefix' => 'api/payments',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'payments.stats' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/payments/stats',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\PaymentController@stats',
        'controller' => 'App\\Http\\Controllers\\Api\\PaymentController@stats',
        'as' => 'payments.stats',
        'namespace' => NULL,
        'prefix' => 'api/payments',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'payments.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/payments/{payment}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\PaymentController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\PaymentController@show',
        'as' => 'payments.show',
        'namespace' => NULL,
        'prefix' => 'api/payments',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'payment' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'payments.initiate' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/payments/initiate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\PaymentController@initiate',
        'controller' => 'App\\Http\\Controllers\\Api\\PaymentController@initiate',
        'as' => 'payments.initiate',
        'namespace' => NULL,
        'prefix' => 'api/payments',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'payments.retry' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/payments/{payment}/retry',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\PaymentController@retry',
        'controller' => 'App\\Http\\Controllers\\Api\\PaymentController@retry',
        'as' => 'payments.retry',
        'namespace' => NULL,
        'prefix' => 'api/payments',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'payment' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'payments.refund' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/payments/{payment}/refund',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
          2 => 'can:refund-payments',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\PaymentController@refund',
        'controller' => 'App\\Http\\Controllers\\Api\\PaymentController@refund',
        'as' => 'payments.refund',
        'namespace' => NULL,
        'prefix' => 'api/payments',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'payment' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'payments.payout' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/payments/{payment}/payout',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
          2 => 'can:manage-payouts',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\PaymentController@manualPayout',
        'controller' => 'App\\Http\\Controllers\\Api\\PaymentController@manualPayout',
        'as' => 'payments.payout',
        'namespace' => NULL,
        'prefix' => 'api/payments',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'payment' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'packages.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/packages',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\PackageController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\PackageController@index',
        'as' => 'packages.index',
        'namespace' => NULL,
        'prefix' => 'api/packages',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'packages.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/packages/{package}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\PackageController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\PackageController@show',
        'as' => 'packages.show',
        'namespace' => NULL,
        'prefix' => 'api/packages',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'package' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'packages.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/packages',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\PackageController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\PackageController@store',
        'as' => 'packages.store',
        'namespace' => NULL,
        'prefix' => 'api/packages',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'packages.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/packages/{package}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\PackageController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\PackageController@update',
        'as' => 'packages.update',
        'namespace' => NULL,
        'prefix' => 'api/packages',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'package' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'packages.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/packages/{package}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\PackageController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\PackageController@destroy',
        'as' => 'packages.destroy',
        'namespace' => NULL,
        'prefix' => 'api/packages',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'package' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'packages.toggle' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'api/packages/{package}/toggle',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\PackageController@toggle',
        'controller' => 'App\\Http\\Controllers\\Api\\PackageController@toggle',
        'as' => 'packages.toggle',
        'namespace' => NULL,
        'prefix' => 'api/packages',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'package' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'packages.duplicate' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/packages/{package}/duplicate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\PackageController@duplicate',
        'controller' => 'App\\Http\\Controllers\\Api\\PackageController@duplicate',
        'as' => 'packages.duplicate',
        'namespace' => NULL,
        'prefix' => 'api/packages',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'package' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'vouchers.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/vouchers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\VoucherController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\VoucherController@index',
        'as' => 'vouchers.index',
        'namespace' => NULL,
        'prefix' => 'api/vouchers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'vouchers.stats' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/vouchers/stats',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\VoucherController@stats',
        'controller' => 'App\\Http\\Controllers\\Api\\VoucherController@stats',
        'as' => 'vouchers.stats',
        'namespace' => NULL,
        'prefix' => 'api/vouchers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'vouchers.generate' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/vouchers/generate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\VoucherController@generate',
        'controller' => 'App\\Http\\Controllers\\Api\\VoucherController@generate',
        'as' => 'vouchers.generate',
        'namespace' => NULL,
        'prefix' => 'api/vouchers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'vouchers.validate' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/vouchers/validate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\VoucherController@validate',
        'controller' => 'App\\Http\\Controllers\\Api\\VoucherController@validate',
        'as' => 'vouchers.validate',
        'namespace' => NULL,
        'prefix' => 'api/vouchers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'vouchers.redeem' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/vouchers/redeem',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\VoucherController@redeem',
        'controller' => 'App\\Http\\Controllers\\Api\\VoucherController@redeem',
        'as' => 'vouchers.redeem',
        'namespace' => NULL,
        'prefix' => 'api/vouchers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'vouchers.print' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/vouchers/{batch}/print',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\VoucherController@print',
        'controller' => 'App\\Http\\Controllers\\Api\\VoucherController@print',
        'as' => 'vouchers.print',
        'namespace' => NULL,
        'prefix' => 'api/vouchers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'batch' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'vouchers.export' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/vouchers/export',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\VoucherController@export',
        'controller' => 'App\\Http\\Controllers\\Api\\VoucherController@export',
        'as' => 'vouchers.export',
        'namespace' => NULL,
        'prefix' => 'api/vouchers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'settings.payment' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/settings/payment',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:796:"function (\\Illuminate\\Http\\Request $request) {
            $tenant = $request->user()->tenant;
            
            return \\response()->json([
                \'success\' => true,
                \'data\' => [
                    \'intasend_configured\' => !empty($tenant->intasend_public_key),
                    \'till_number\' => $tenant->till_number,
                    \'auto_payout_enabled\' => $tenant->auto_payout_enabled ?? true,
                    \'commission\' => [
                        \'type\' => $tenant->commission_type,
                        \'rate\' => $tenant->commission_rate,
                        \'minimum\' => $tenant->minimum_commission,
                        \'frequency\' => $tenant->commission_frequency,
                    ],
                ],
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000047d0000000000000000";}}',
        'as' => 'settings.payment',
        'namespace' => NULL,
        'prefix' => 'api/settings',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'settings.payment.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/settings/payment',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:1075:"function (\\Illuminate\\Http\\Request $request) {
            $validated = $request->validate([
                \'till_number\' => \'nullable|string|max:20\',
                \'auto_payout_enabled\' => \'boolean\',
                \'commission_type\' => \'required|in:percentage,fixed\',
                \'commission_rate\' => \'required|numeric|min:0|max:100\',
                \'minimum_commission\' => \'required|numeric|min:0\',
                \'commission_frequency\' => \'required|in:monthly,weekly,per_transaction\',
            ]);
            
            $request->user()->tenant->update($validated);
            
            return \\response()->json([
                \'success\' => true,
                \'message\' => \'Payment settings updated\',
                \'data\' => $request->user()->tenant->only([
                    \'till_number\',
                    \'auto_payout_enabled\',
                    \'commission_type\',
                    \'commission_rate\',
                    \'minimum_commission\',
                    \'commission_frequency\',
                ]),
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004800000000000000000";}}',
        'as' => 'settings.payment.update',
        'namespace' => NULL,
        'prefix' => 'api/settings',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'settings.commission' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/settings/commission',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:1015:"function (\\Illuminate\\Http\\Request $request) {
            $tenant = $request->user()->tenant;
            
            return \\response()->json([
                \'success\' => true,
                \'data\' => [
                    \'current_period\' => [
                        \'start\' => $tenant->current_billing_period_start,
                        \'end\' => $tenant->current_billing_period_end,
                        \'sales_total\' => $tenant->current_period_sales,
                        \'commission_owed\' => $tenant->current_period_commission,
                        \'paid\' => $tenant->current_period_paid,
                        \'balance\' => $tenant->current_period_balance,
                    ],
                    \'lifetime\' => [
                        \'total_sales\' => $tenant->lifetime_sales,
                        \'total_commission\' => $tenant->lifetime_commission,
                        \'total_paid\' => $tenant->lifetime_paid,
                    ],
                ],
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004820000000000000000";}}',
        'as' => 'settings.commission',
        'namespace' => NULL,
        'prefix' => 'api/settings',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'settings.profile.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/settings/profile',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:901:"function (\\Illuminate\\Http\\Request $request) {
            $validated = $request->validate([
                \'business_name\' => \'required|string|max:255\',
                \'contact_name\' => \'required|string|max:255\',
                \'contact_email\' => \'required|email|max:255\',
                \'contact_phone\' => \'required|string|size:12\',
                \'location\' => \'nullable|string|max:255\',
            ]);
            
            $request->user()->tenant->update($validated);
            
            return \\response()->json([
                \'success\' => true,
                \'message\' => \'Profile updated\',
                \'data\' => $request->user()->tenant->only([
                    \'business_name\',
                    \'contact_name\',
                    \'contact_email\',
                    \'contact_phone\',
                    \'location\',
                ]),
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004840000000000000000";}}',
        'as' => 'settings.profile.update',
        'namespace' => NULL,
        'prefix' => 'api/settings',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'reports.revenue' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/reports/revenue',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:316:"function (\\Illuminate\\Http\\Request $request) {
            // Implementation: Generate report via queued job
            return \\response()->json([
                \'success\' => true,
                \'message\' => \'Report generation queued\',
                \'job_id\' => \'report-\' . \\uniqid(),
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004860000000000000000";}}',
        'as' => 'reports.revenue',
        'namespace' => NULL,
        'prefix' => 'api/reports',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'reports.dashboard' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/reports/dashboard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:api',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:921:"function (\\Illuminate\\Http\\Request $request) {
            $tenant = $request->user()->tenant;
            
            return \\response()->json([
                \'success\' => true,
                \'data\' => [
                    \'revenue\' => [
                        \'today\' => $tenant->today_revenue,
                        \'week\' => $tenant->week_revenue,
                        \'month\' => $tenant->month_revenue,
                    ],
                    \'sessions\' => [
                        \'active\' => $tenant->active_sessions_count,
                        \'today\' => $tenant->today_sessions_count,
                    ],
                    \'routers\' => [
                        \'online\' => $tenant->online_routers_count,
                        \'total\' => $tenant->routers_count,
                    ],
                    \'alerts\' => $tenant->pending_alerts,
                ],
            ]);
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004880000000000000000";}}',
        'as' => 'reports.dashboard',
        'namespace' => NULL,
        'prefix' => 'api/reports',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::hjfrt6AFLyEjG37w' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/{fallbackPlaceholder}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:201:"function () {
    return \\response()->json([
        \'success\' => false,
        \'message\' => \'API endpoint not found\',
        \'documentation\' => \'https://docs.cloudbridge.network/api\',
    ], 404);
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000004560000000000000000";}}',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::hjfrt6AFLyEjG37w',
      ),
      'fallback' => true,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'fallbackPlaceholder' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'storage.local' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'storage/{path}',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:3:{s:4:"disk";s:5:"local";s:6:"config";a:5:{s:6:"driver";s:5:"local";s:4:"root";s:93:"/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/storage/app/private";s:5:"serve";b:1;s:5:"throw";b:0;s:6:"report";b:0;}s:12:"isProduction";b:0;}s:8:"function";s:323:"function (\\Illuminate\\Http\\Request $request, string $path) use ($disk, $config, $isProduction) {
                    return (new \\Illuminate\\Filesystem\\ServeFile(
                        $disk,
                        $config,
                        $isProduction
                    ))($request, $path);
                }";s:5:"scope";s:47:"Illuminate\\Filesystem\\FilesystemServiceProvider";s:4:"this";N;s:4:"self";s:32:"00000000000004050000000000000000";}}',
        'as' => 'storage.local',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'path' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'storage.local.upload' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'storage/{path}',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:3:{s:4:"disk";s:5:"local";s:6:"config";a:5:{s:6:"driver";s:5:"local";s:4:"root";s:93:"/var/www/cloudbridge/releases/18-07da6b1583c681bc89b27abd3b69ce55be497c26/storage/app/private";s:5:"serve";b:1;s:5:"throw";b:0;s:6:"report";b:0;}s:12:"isProduction";b:0;}s:8:"function";s:325:"function (\\Illuminate\\Http\\Request $request, string $path) use ($disk, $config, $isProduction) {
                    return (new \\Illuminate\\Filesystem\\ReceiveFile(
                        $disk,
                        $config,
                        $isProduction
                    ))($request, $path);
                }";s:5:"scope";s:47:"Illuminate\\Filesystem\\FilesystemServiceProvider";s:4:"this";N;s:4:"self";s:32:"000000000000045a0000000000000000";}}',
        'as' => 'storage.local.upload',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'path' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
  ),
)
);
