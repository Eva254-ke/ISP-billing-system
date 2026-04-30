<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tenants', 'logo_url')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('logo_url')->nullable();
            });
        }

        if (!Schema::hasColumn('tenants', 'brand_color_primary')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('brand_color_primary')->nullable();
            });
        }

        if (!Schema::hasColumn('tenants', 'brand_color_secondary')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('brand_color_secondary')->nullable();
            });
        }

        if (!Schema::hasColumn('tenants', 'captive_portal_enabled')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->boolean('captive_portal_enabled')->default(true);
            });
        }

        if (!Schema::hasColumn('tenants', 'captive_portal_title')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('captive_portal_title')->nullable();
            });
        }

        if (!Schema::hasColumn('tenants', 'captive_portal_welcome_message')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->text('captive_portal_welcome_message')->nullable();
            });
        }

        if (!Schema::hasColumn('tenants', 'captive_portal_terms_url')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('captive_portal_terms_url')->nullable();
            });
        }

        if (!Schema::hasColumn('tenants', 'captive_portal_support_phone')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('captive_portal_support_phone')->nullable();
            });
        }

        if (!Schema::hasColumn('tenants', 'captive_portal_support_email')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('captive_portal_support_email')->nullable();
            });
        }

        if (!Schema::hasColumn('tenants', 'captive_portal_custom_css')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->text('captive_portal_custom_css')->nullable();
            });
        }

        if (!Schema::hasColumn('tenants', 'captive_portal_redirect_url')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('captive_portal_redirect_url')->nullable();
            });
        }

        if (!Schema::hasColumn('tenants', 'captive_portal_session_timeout_minutes')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->unsignedInteger('captive_portal_session_timeout_minutes')->default(120);
            });
        }

        if (!Schema::hasColumn('tenants', 'captive_portal_grace_period_minutes')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->unsignedInteger('captive_portal_grace_period_minutes')->default(5);
            });
        }

        if (!Schema::hasColumn('tenants', 'captive_portal_allow_voucher_redemption')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->boolean('captive_portal_allow_voucher_redemption')->default(true);
            });
        }

        if (!Schema::hasColumn('tenants', 'captive_portal_allow_mpese_code_reconnect')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->boolean('captive_portal_allow_mpese_code_reconnect')->default(true);
            });
        }

        if (!Schema::hasColumn('tenants', 'captive_portal_show_package_descriptions')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->boolean('captive_portal_show_package_descriptions')->default(true);
            });
        }

        if (!Schema::hasColumn('tenants', 'captive_portal_default_language')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('captive_portal_default_language')->default('en');
            });
        }

        if (!Schema::hasColumn('tenants', 'captive_portal_analytics_enabled')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->boolean('captive_portal_analytics_enabled')->default(false);
            });
        }
    }

    public function down(): void
    {
        $columns = [
            'logo_url',
            'brand_color_primary',
            'brand_color_secondary',
            'captive_portal_enabled',
            'captive_portal_title',
            'captive_portal_welcome_message',
            'captive_portal_terms_url',
            'captive_portal_support_phone',
            'captive_portal_support_email',
            'captive_portal_custom_css',
            'captive_portal_redirect_url',
            'captive_portal_session_timeout_minutes',
            'captive_portal_grace_period_minutes',
            'captive_portal_allow_voucher_redemption',
            'captive_portal_allow_mpese_code_reconnect',
            'captive_portal_show_package_descriptions',
            'captive_portal_default_language',
            'captive_portal_analytics_enabled',
        ];

        foreach ($columns as $column) {
            if (!Schema::hasColumn('tenants', $column)) {
                continue;
            }

            Schema::table('tenants', function (Blueprint $table) use ($column): void {
                $table->dropColumn($column);
            });
        }
    }
};
