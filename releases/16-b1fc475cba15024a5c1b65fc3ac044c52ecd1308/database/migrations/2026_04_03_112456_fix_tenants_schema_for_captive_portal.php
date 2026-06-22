<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Add core columns first (no after() to avoid dependency issues)
            if (!Schema::hasColumn('tenants', 'currency')) {
                $table->string('currency')->default('KES')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'status')) {
                $table->string('status')->default('active')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'logo_url')) {
                $table->string('logo_url')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'brand_color_primary')) {
                $table->string('brand_color_primary')->default('#7C3AED')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'brand_color_secondary')) {
                $table->string('brand_color_secondary')->default('#06B6D4')->nullable();
            }
            
            // Captive portal config columns
            if (!Schema::hasColumn('tenants', 'captive_portal_enabled')) {
                $table->boolean('captive_portal_enabled')->default(false);
            }
            if (!Schema::hasColumn('tenants', 'captive_portal_title')) {
                $table->string('captive_portal_title')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'captive_portal_welcome_message')) {
                $table->text('captive_portal_welcome_message')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'captive_portal_terms_url')) {
                $table->string('captive_portal_terms_url')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'captive_portal_support_phone')) {
                $table->string('captive_portal_support_phone')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'captive_portal_support_email')) {
                $table->string('captive_portal_support_email')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'captive_portal_custom_css')) {
                $table->text('captive_portal_custom_css')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'captive_portal_redirect_url')) {
                $table->string('captive_portal_redirect_url')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'captive_portal_session_timeout_minutes')) {
                $table->integer('captive_portal_session_timeout_minutes')->default(60);
            }
            if (!Schema::hasColumn('tenants', 'captive_portal_grace_period_minutes')) {
                $table->integer('captive_portal_grace_period_minutes')->default(5);
            }
            if (!Schema::hasColumn('tenants', 'captive_portal_allow_voucher_redemption')) {
                $table->boolean('captive_portal_allow_voucher_redemption')->default(true);
            }
            if (!Schema::hasColumn('tenants', 'captive_portal_allow_mpese_code_reconnect')) {
                $table->boolean('captive_portal_allow_mpese_code_reconnect')->default(true);
            }
            if (!Schema::hasColumn('tenants', 'captive_portal_show_package_descriptions')) {
                $table->boolean('captive_portal_show_package_descriptions')->default(true);
            }
            if (!Schema::hasColumn('tenants', 'captive_portal_default_language')) {
                $table->string('captive_portal_default_language')->default('en');
            }
            if (!Schema::hasColumn('tenants', 'captive_portal_analytics_enabled')) {
                $table->boolean('captive_portal_analytics_enabled')->default(false);
            }
            
            // IntaSend payment config
            if (!Schema::hasColumn('tenants', 'intasend_public_key')) {
                $table->string('intasend_public_key')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'intasend_secret_key')) {
                $table->string('intasend_secret_key')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'intasend_mode')) {
                $table->string('intasend_mode')->default('sandbox');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'currency', 'status', 'logo_url', 'brand_color_primary', 'brand_color_secondary',
                'captive_portal_enabled', 'captive_portal_title', 'captive_portal_welcome_message',
                'captive_portal_terms_url', 'captive_portal_support_phone', 'captive_portal_support_email',
                'captive_portal_custom_css', 'captive_portal_redirect_url', 'captive_portal_session_timeout_minutes',
                'captive_portal_grace_period_minutes', 'captive_portal_allow_voucher_redemption',
                'captive_portal_allow_mpese_code_reconnect', 'captive_portal_show_package_descriptions',
                'captive_portal_default_language', 'captive_portal_analytics_enabled',
                'intasend_public_key', 'intasend_secret_key', 'intasend_mode',
            ]);
        });
    }
};
