<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table("users", function (Blueprint $table) {
            if (!Schema::hasColumn("users", "last_login_at")) {
                $table->timestamp("last_login_at")->nullable()->after("email");
            }
            if (!Schema::hasColumn("users", "last_login_ip")) {
                $table->string("last_login_ip")->nullable()->after("last_login_at");
            }
        });
    }
    public function down(): void {
        Schema::table("users", function (Blueprint $table) {
            $table->dropColumn(["last_login_at", "last_login_ip"]);
        });
    }
};
