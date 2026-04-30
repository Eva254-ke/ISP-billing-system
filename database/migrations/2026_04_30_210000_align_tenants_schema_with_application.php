<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        $today = now()->toDateString();
        $nextMonth = now()->copy()->addMonth()->toDateString();

        if (!Schema::hasColumn('tenants', 'contact_email')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('contact_email')->nullable()->after('domain');
            });
        }

        if (!Schema::hasColumn('tenants', 'contact_phone')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('contact_phone')->nullable()->after('contact_email');
            });
        }

        if (!Schema::hasColumn('tenants', 'plan')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('plan')->default('starter')->after('status');
            });
        }

        if (!Schema::hasColumn('tenants', 'monthly_fee')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->decimal('monthly_fee', 10, 2)->default(0)->after('plan');
            });
        }

        if (!Schema::hasColumn('tenants', 'billing_cycle_start')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->date('billing_cycle_start')->nullable()->after('monthly_fee');
            });
        }

        if (!Schema::hasColumn('tenants', 'next_billing_date')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->date('next_billing_date')->nullable()->after('billing_cycle_start');
            });
        }

        if (!Schema::hasColumn('tenants', 'max_routers')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->unsignedInteger('max_routers')->default(1)->after('next_billing_date');
            });
        }

        if (!Schema::hasColumn('tenants', 'max_users')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->unsignedInteger('max_users')->default(100)->after('max_routers');
            });
        }

        if (!Schema::hasColumn('tenants', 'settings')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->json('settings')->nullable()->after('max_users');
            });
        }

        if (!Schema::hasColumn('tenants', 'trial_ends_at')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->timestamp('trial_ends_at')->nullable()->after('settings');
            });
        }

        if (!Schema::hasColumn('tenants', 'last_active_at')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->timestamp('last_active_at')->nullable()->after('trial_ends_at');
            });
        }

        if (!Schema::hasColumn('tenants', 'metadata')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->json('metadata')->nullable()->after('last_active_at');
            });
        }

        if (!Schema::hasColumn('tenants', 'payment_method')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('payment_method')->default('paybill')->after('metadata');
            });
        }

        if (!Schema::hasColumn('tenants', 'payment_shortcode')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('payment_shortcode')->nullable()->after('payment_method');
            });
        }

        if (!Schema::hasColumn('tenants', 'till_number')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('till_number')->nullable()->after('payment_shortcode');
            });
        }

        if (!Schema::hasColumn('tenants', 'payment_account_name')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('payment_account_name')->nullable()->after('till_number');
            });
        }

        if (!Schema::hasColumn('tenants', 'bank_account')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('bank_account')->nullable()->after('payment_account_name');
            });
        }

        if (!Schema::hasColumn('tenants', 'bank_code')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('bank_code')->nullable()->after('bank_account');
            });
        }

        if (!Schema::hasColumn('tenants', 'personal_phone')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('personal_phone')->nullable()->after('bank_code');
            });
        }

        if (!Schema::hasColumn('tenants', 'commission_type')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('commission_type')->default('percentage')->after('personal_phone');
            });
        }

        if (!Schema::hasColumn('tenants', 'commission_rate')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->decimal('commission_rate', 5, 2)->default(5)->after('commission_type');
            });
        }

        if (!Schema::hasColumn('tenants', 'minimum_commission')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->decimal('minimum_commission', 10, 2)->default(10)->after('commission_rate');
            });
        }

        if (!Schema::hasColumn('tenants', 'commission_frequency')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('commission_frequency')->default('monthly')->after('minimum_commission');
            });
        }

        if (!Schema::hasColumn('tenants', 'next_commission_date')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->date('next_commission_date')->nullable()->after('commission_frequency');
            });
        }

        if (!Schema::hasColumn('tenants', 'custom_callback_url')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('custom_callback_url')->nullable()->after('next_commission_date');
            });
        }

        if (Schema::hasColumn('tenants', 'email') && Schema::hasColumn('tenants', 'contact_email')) {
            DB::table('tenants')
                ->whereNull('contact_email')
                ->update(['contact_email' => DB::raw('email')]);
        }

        if (Schema::hasColumn('tenants', 'phone') && Schema::hasColumn('tenants', 'contact_phone')) {
            DB::table('tenants')
                ->whereNull('contact_phone')
                ->update(['contact_phone' => DB::raw('phone')]);
        }

        if (Schema::hasColumn('tenants', 'billing_cycle_start')) {
            DB::table('tenants')
                ->whereNull('billing_cycle_start')
                ->update(['billing_cycle_start' => $today]);
        }

        if (Schema::hasColumn('tenants', 'next_billing_date')) {
            DB::table('tenants')
                ->whereNull('next_billing_date')
                ->update(['next_billing_date' => $nextMonth]);
        }

        if (Schema::hasColumn('tenants', 'payment_account_name')) {
            DB::table('tenants')
                ->whereNull('payment_account_name')
                ->update(['payment_account_name' => DB::raw('name')]);
        }
    }

    public function down(): void
    {
    }
};
