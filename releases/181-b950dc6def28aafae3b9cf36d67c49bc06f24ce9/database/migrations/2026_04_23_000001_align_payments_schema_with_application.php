<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'type')) {
                $table->string('type')->nullable();
            }

            if (! Schema::hasColumn('payments', 'reference')) {
                $table->string('reference')->nullable();
            }

            if (! Schema::hasColumn('payments', 'reconnect_count')) {
                $table->unsignedInteger('reconnect_count')->default(0);
            }

            if (! Schema::hasColumn('payments', 'parent_payment_id')) {
                $table->unsignedBigInteger('parent_payment_id')->nullable();
            }

            if (! Schema::hasColumn('payments', 'callback_payload')) {
                $table->json('callback_payload')->nullable();
            }

            if (! Schema::hasColumn('payments', 'activated_at')) {
                $table->timestamp('activated_at')->nullable();
            }

            if (! Schema::hasColumn('payments', 'mpesa_code')) {
                $table->string('mpesa_code')->nullable();
            }
        });

        $this->ensureStatusSupportsActivated();
        $this->backfillLegacyPayments();
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        if (Schema::hasColumn('payments', 'mpesa_code')
            || Schema::hasColumn('payments', 'activated_at')
            || Schema::hasColumn('payments', 'callback_payload')
            || Schema::hasColumn('payments', 'parent_payment_id')
            || Schema::hasColumn('payments', 'reconnect_count')
            || Schema::hasColumn('payments', 'reference')
            || Schema::hasColumn('payments', 'type')) {
            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'mpesa_code')) {
                    $table->dropColumn('mpesa_code');
                }

                if (Schema::hasColumn('payments', 'activated_at')) {
                    $table->dropColumn('activated_at');
                }

                if (Schema::hasColumn('payments', 'callback_payload')) {
                    $table->dropColumn('callback_payload');
                }

                if (Schema::hasColumn('payments', 'parent_payment_id')) {
                    $table->dropColumn('parent_payment_id');
                }

                if (Schema::hasColumn('payments', 'reconnect_count')) {
                    $table->dropColumn('reconnect_count');
                }

                if (Schema::hasColumn('payments', 'reference')) {
                    $table->dropColumn('reference');
                }

                if (Schema::hasColumn('payments', 'type')) {
                    $table->dropColumn('type');
                }
            });
        }

        $this->restoreOriginalStatusEnum();
    }

    private function ensureStatusSupportsActivated(): void
    {
        if (! Schema::hasColumn('payments', 'status')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE `payments` MODIFY `status` ENUM('initiated', 'pending', 'confirmed', 'completed', 'activated', 'failed', 'refunded', 'cancelled') NOT NULL DEFAULT 'initiated'"
        );
    }

    private function restoreOriginalStatusEnum(): void
    {
        if (! Schema::hasColumn('payments', 'status')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('payments')
            ->where('status', 'activated')
            ->update(['status' => 'completed']);

        DB::statement(
            "ALTER TABLE `payments` MODIFY `status` ENUM('initiated', 'pending', 'confirmed', 'completed', 'failed', 'refunded', 'cancelled') NOT NULL DEFAULT 'initiated'"
        );
    }

    private function backfillLegacyPayments(): void
    {
        DB::table('payments')
            ->select([
                'id',
                'status',
                'type',
                'payment_channel',
                'reference',
                'mpesa_receipt_number',
                'mpesa_checkout_request_id',
                'callback_data',
                'callback_payload',
                'completed_at',
                'activated_at',
                'mpesa_code',
            ])
            ->orderBy('id')
            ->chunkById(200, function ($payments): void {
                foreach ($payments as $payment) {
                    $updates = [];

                    if ($payment->type === null || $payment->type === '') {
                        $updates['type'] = $this->resolvePaymentType($payment->payment_channel);
                    }

                    if ($payment->reference === null || $payment->reference === '') {
                        $updates['reference'] = $payment->mpesa_receipt_number
                            ?: ($payment->mpesa_checkout_request_id ?: ('PAY-' . $payment->id));
                    }

                    if ($payment->callback_payload === null && $payment->callback_data !== null) {
                        $updates['callback_payload'] = $this->normalizeJsonValue($payment->callback_data);
                    }

                    if ($payment->activated_at === null
                        && in_array((string) $payment->status, ['completed', 'activated'], true)
                        && $payment->completed_at !== null) {
                        $updates['activated_at'] = $payment->completed_at;
                    }

                    if (($payment->mpesa_code === null || $payment->mpesa_code === '')
                        && $payment->mpesa_receipt_number !== null
                        && $payment->mpesa_receipt_number !== '') {
                        $updates['mpesa_code'] = strtoupper((string) $payment->mpesa_receipt_number);
                    }

                    if ($updates !== []) {
                        DB::table('payments')
                            ->where('id', $payment->id)
                            ->update($updates);
                    }
                }
            });
    }

    private function resolvePaymentType(?string $paymentChannel): string
    {
        return match ($paymentChannel) {
            'captive_portal' => 'captive_portal',
            'session_extension' => 'session_extension',
            'voucher' => 'voucher',
            default => 'admin',
        };
    }

    private function normalizeJsonValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $encoded === false ? null : $encoded;
    }
};
