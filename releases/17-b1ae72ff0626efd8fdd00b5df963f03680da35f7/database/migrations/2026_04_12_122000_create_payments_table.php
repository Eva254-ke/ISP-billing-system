<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable("payments")) {
            Schema::create("payments", function (Blueprint $table) {
                $table->id();
                $table->foreignId("tenant_id")->constrained()->onDelete("cascade");
                $table->foreignId("user_id")->nullable()->constrained()->onDelete("set null");
                $table->foreignId("package_id")->nullable()->constrained()->onDelete("set null");
                $table->string("intasend_reference")->nullable();
                $table->string("status")->default("pending");
                $table->decimal("amount", 10, 2);
                $table->string("currency")->default("KES");
                $table->text("metadata")->nullable();
                $table->timestamp("paid_at")->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(["tenant_id", "status"]);
            });
        }
    }
    public function down(): void { Schema::dropIfExists("payments"); }
};
