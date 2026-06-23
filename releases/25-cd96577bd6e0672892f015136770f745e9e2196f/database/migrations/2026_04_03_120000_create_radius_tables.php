<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $schema = Schema::connection('radius');

        if (!$schema->hasTable('radcheck')) {
            $schema->create('radcheck', function (Blueprint $table) {
                $table->unsignedInteger('id', true);
                $table->string('username', 64)->default('');
                $table->string('attribute', 64)->default('');
                $table->char('op', 2)->default(':=');
                $table->string('value', 253)->default('');
                $table->index('username', 'idx_radcheck_username');
                $table->index('attribute', 'idx_radcheck_attr');
            });
        }

        if (!$schema->hasTable('radreply')) {
            $schema->create('radreply', function (Blueprint $table) {
                $table->unsignedInteger('id', true);
                $table->string('username', 64)->default('');
                $table->string('attribute', 64)->default('');
                $table->char('op', 2)->default(':=');
                $table->string('value', 253)->default('');
                $table->index('username', 'idx_radreply_username');
                $table->index('attribute', 'idx_radreply_attr');
            });
        }

        if (!$schema->hasTable('radacct')) {
            $schema->create('radacct', function (Blueprint $table) {
                $table->unsignedBigInteger('radacctid', true);
                $table->string('acctsessionid', 64)->default('');
                $table->string('acctuniqueid', 32)->default('')->unique('uniq_acctuniqueid');
                $table->string('username', 64)->default('');
                $table->string('nasipaddress', 15)->default('');
                $table->dateTime('acctstarttime')->nullable();
                $table->dateTime('acctupdatetime')->nullable();
                $table->dateTime('acctstoptime')->nullable();
                $table->unsignedInteger('acctsessiontime')->nullable();
                $table->bigInteger('acctinputoctets')->nullable();
                $table->bigInteger('acctoutputoctets')->nullable();
                $table->string('calledstationid', 50)->default('');
                $table->string('callingstationid', 50)->default('');
                $table->string('acctterminatecause', 32)->default('');
                $table->string('framedipaddress', 15)->default('');
                $table->string('class', 64)->nullable();
                $table->index('username', 'idx_username');
                $table->index('acctstarttime', 'idx_acctstarttime');
                $table->index('acctstoptime', 'idx_acctstoptime');
                $table->index('nasipaddress', 'idx_nasipaddress');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('radius');

        if ($schema->hasTable('radacct')) {
            $schema->drop('radacct');
        }

        if ($schema->hasTable('radreply')) {
            $schema->drop('radreply');
        }

        if ($schema->hasTable('radcheck')) {
            $schema->drop('radcheck');
        }
    }
};
