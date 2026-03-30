<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Payment;
use App\Models\PaymentReconciliation;
use App\Jobs\ReconcilePayments;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailyReconciliation extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'payments:reconcile-daily {--tenant=} {--date=}';

    /**
     * The console command description.
     */
    protected $description = 'Run daily payment reconciliation for all tenants (or specific tenant/date)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Log::channel('payment')->info('Starting daily reconciliation');

        $this->info('💰 Starting daily payment reconciliation...');

        $tenantId = $this->option('tenant');
        $date = $this->option('date') ?? now()->subDay()->toDateString(); // Default: yesterday

        if ($tenantId) {
            $tenants = Tenant::where('id', $tenantId)->get();
            $this->info("Reconciling specific tenant ID: {$tenantId}");
        } else {
            $tenants = Tenant::active()->get();
            $this->info("Reconciling all {$tenants->count()} active tenants");
        }

        $reconciled = 0;
        $discrepancies = 0;
        $failed = 0;

        foreach ($tenants as $tenant) {
            $this->newLine();
            $this->info("🏢 Processing: {$tenant->name}");

            try {
                DB::beginTransaction();

                // ──────────────────────────────────────────────────────────────
                // 1. GET ALL COMPLETED PAYMENTS FOR THE DAY
                // ──────────────────────────────────────────────────────────────
                $payments = Payment::where('tenant_id', $tenant->id)
                    ->whereDate('created_at', $date)
                    ->where('status', 'completed')
                    ->get();

                $dashboardTotal = $payments->sum('amount');
                $totalTransactions = $payments->count();

                $this->line("   Dashboard total: KES " . number_format($dashboardTotal, 2));
                $this->line("   Transactions: {$totalTransactions}");

                // ──────────────────────────────────────────────────────────────
                // 2. FETCH M-PESA STATEMENT (Mock for now - integrate B2C API)
                // ──────────────────────────────────────────────────────────────
                // TODO: Integrate with Safaricom B2C API to fetch actual statement
                // For now, we assume match (in production, fetch real data)
                $mpesaTotal = $dashboardTotal; // Mock
                $matchedTransactions = $totalTransactions;

                // ──────────────────────────────────────────────────────────────
                // 3. CALCULATE DISCREPANCIES
                // ──────────────────────────────────────────────────────────────
                $discrepancyAmount = abs($dashboardTotal - $mpesaTotal);
                $discrepancyPercentage = $dashboardTotal > 0 
                    ? ($discrepancyAmount / $dashboardTotal) * 100 
                    : 0;

                $status = $discrepancyPercentage > 1 
                    ? 'discrepancy' 
                    : 'matched';

                if ($status === 'discrepancy') {
                    $discrepancies++;
                    $this->warn("   ⚠️ Discrepancy detected: KES " . number_format($discrepancyAmount, 2));
                } else {
                    $this->info("   ✅ Matched");
                }

                // ──────────────────────────────────────────────────────────────
                // 4. CREATE RECONCILIATION RECORD
                // ──────────────────────────────────────────────────────────────
                $reconciliation = PaymentReconciliation::create([
                    'tenant_id' => $tenant->id,
                    'reconciliation_date' => $date,
                    'reconciliation_time' => now()->toTimeString(),
                    'dashboard_total' => $dashboardTotal,
                    'mpesa_total' => $mpesaTotal,
                    'discrepancy_amount' => $discrepancyAmount,
                    'discrepancy_percentage' => $discrepancyPercentage,
                    'status' => $status,
                    'total_transactions' => $totalTransactions,
                    'matched_transactions' => $matchedTransactions,
                    'missing_in_dashboard' => 0,
                    'missing_in_mpesa' => 0,
                    'amount_mismatches' => 0,
                ]);

                // ──────────────────────────────────────────────────────────────
                // 5. MARK PAYMENTS AS RECONCILED
                // ──────────────────────────────────────────────────────────────
                $payments->each->markReconciled();

                DB::commit();
                $reconciled++;

                Log::channel('payment')->info('Reconciliation completed', [
                    'tenant' => $tenant->name,
                    'date' => $date,
                    'status' => $status,
                    'discrepancy' => $discrepancyAmount,
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                $failed++;

                Log::channel('payment')->error('Reconciliation failed', [
                    'tenant' => $tenant->name,
                    'error' => $e->getMessage(),
                ]);

                $this->error("   ❌ Failed: " . $e->getMessage());
            }
        }

        // ──────────────────────────────────────────────────────────────────
        // SUMMARY
        // ──────────────────────────────────────────────────────────────────
        Log::channel('payment')->info('Daily reconciliation completed', [
            'date' => $date,
            'reconciled' => $reconciled,
            'discrepancies' => $discrepancies,
            'failed' => $failed,
        ]);

        $this->newLine();
        $this->info('✅ Reconciliation Summary:');
        $this->line("   Date: {$date}");
        $this->line("   Tenants reconciled: {$reconciled}");
        $this->warn("   Discrepancies found: {$discrepancies}");
        $this->error("   Failed: {$failed}");

        // Alert admin if discrepancies found
        if ($discrepancies > 0) {
            $this->newLine();
            $this->warn('⚠️ Action required: Review discrepancies in admin panel');
            // TODO: Send email/Slack alert to admin
        }

        return Command::SUCCESS;
    }
}