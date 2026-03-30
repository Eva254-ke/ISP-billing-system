// database/migrations/xxxx_create_payments_table.php

public function up(): void
{
    Schema::create('payments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
        $table->foreignId('session_id')->constrained()->onDelete('cascade');
        $table->string('reference')->unique(); // IntaSend Reference
        $table->string('mpesa_code')->nullable(); // M-Pesa Confirmation Code
        $table->decimal('amount', 10, 2); // Gross amount
        $table->decimal('fee', 10, 2)->default(0); // IntaSend fee
        $table->decimal('net_amount', 10, 2); // Amount to tenant
        $table->string('status')->default('pending'); // pending, completed, failed, payout_sent
        $table->string('phone');
        $table->text('response_data')->nullable(); // Full webhook payload
        $table->timestamp('paid_at')->nullable();
        $table->timestamp('payout_sent_at')->nullable();
        $table->timestamps();
    });
}