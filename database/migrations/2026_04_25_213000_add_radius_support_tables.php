<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!(bool) config('radius.enabled', false)) {
            return;
        }

        $schema = Schema::connection('radius');
        $tables = (array) config('radius.tables', []);
        $radpostauth = (string) ($tables['radpostauth'] ?? 'radpostauth');
        $nasreload = (string) ($tables['nasreload'] ?? 'nasreload');

        if ($radpostauth !== '' && !$schema->hasTable($radpostauth)) {
            $schema->create($radpostauth, function (Blueprint $table): void {
                $table->unsignedBigInteger('id', true);
                $table->string('username', 64);
                $table->string('pass', 64);
                $table->string('reply', 32);
                $table->timestamp('authdate')->useCurrent();
                $table->index('username', 'idx_radpostauth_username');
            });
        }

        if ($nasreload !== '' && !$schema->hasTable($nasreload)) {
            $schema->create($nasreload, function (Blueprint $table): void {
                $table->string('nasipaddress', 15);
                $table->dateTime('reloadtime');
                $table->primary('nasipaddress', 'pk_nasreload_nasipaddress');
            });
        }
    }

    public function down(): void
    {
        if (!(bool) config('radius.enabled', false)) {
            return;
        }

        $schema = Schema::connection('radius');
        $tables = (array) config('radius.tables', []);
        $radpostauth = (string) ($tables['radpostauth'] ?? 'radpostauth');
        $nasreload = (string) ($tables['nasreload'] ?? 'nasreload');

        if ($nasreload !== '' && $schema->hasTable($nasreload)) {
            $schema->drop($nasreload);
        }

        if ($radpostauth !== '' && $schema->hasTable($radpostauth)) {
            $schema->drop($radpostauth);
        }
    }
};
