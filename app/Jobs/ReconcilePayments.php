<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\PaymentReconciliation;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReconcilePayments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 🟢 LOW PRIORITY
     */
    public int $timeout = 300;
    public int $tries = 1;
    public $backoff = 60;

    public int $tenantId;
    public string $reconciliationDate;

    public function __construct(Tenant $tenant, string $date = null)
    {
        $this->tenantId = $tenant->id;
        $this->reconciliationDate = $date ?? now()->toDateString();
        
        // ✅ Set queue in constructor
        $this->onQueue('low');
    }

    public function handle(): void
    {
        $tenant = \App\Models\Tenant::find($this->tenantId);

        if (!$tenant) {
            Log::channel('payment')->error('Tenant not found for reconciliation');
            return;
        }

        Log::channel('payment')->info('Starting reconciliation', [
            'tenant' => $tenant->name,
            'date' => $this->reconciliationDate,
        ]);

        try {
            DB::beginTransaction();

            $payments = Payment::where('tenant_id', $tenant->id)
                ->whereDate('created_at', $this->reconciliationDate)
                ->where('status', 'completed')
                ->get();

            $dashboardTotal = $payments->sum('amount');
            $mpesaTotal = $dashboardTotal; // Mock - replace with actual M-Pesa API call
            $discrepancyAmount = abs($dashboardTotal - $mpesaTotal);
            $discrepancyPercentage = $dashboardTotal > 0 ? ($discrepancyAmount / $dashboardTotal) * 100 : 0;

            PaymentReconciliation::create([
                'tenant_id' => $tenant->id,
                'reconciliation_date' => $this->reconciliationDate,
                'reconciliation_time' => now()->toTimeString(),
                'dashboard_total' => $dashboardTotal,
                'mpesa_total' => $mpesaTotal,
                'discrepancy_amount' => $discrepancyAmount,
                'discrepancy_percentage' => $discrepancyPercentage,
                'status' => $discrepancyPercentage > 1 ? 'discrepancy' : 'matched',
                'total_transactions' => $payments->count(),
                'matched_transactions' => $payments->count(),
            ]);

            $payments->each->markReconciled();

            DB::commit();

            Log::channel('payment')->info('Reconciliation completed', [
                'tenant' => $tenant->name,
                'status' => $discrepancyPercentage > 1 ? 'discrepancy' : 'matched',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::channel('payment')->error('Reconciliation failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}