<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * List all payments for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $payments = $tenant->payments()
            ->with(['package', 'session'])
            ->latest()
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $payments->items(),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    /**
     * Get single payment details.
     */
    public function show(Request $request, Payment $payment): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if ($payment->tenant_id !== $tenant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        $payment->load(['package', 'session']);

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    /**
     * Get payment statistics for the authenticated tenant.
     */
    public function stats(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $week = now()->startOfWeek();
        $month = now()->startOfMonth();

        $stats = [
            'today' => [
                'total' => $tenant->payments()->whereDate('created_at', today())->count(),
                'revenue' => $tenant->payments()
                    ->whereDate('created_at', today())
                    ->where('status', 'completed')
                    ->sum('amount'),
                'commission_owed' => $tenant->payments()
                    ->whereDate('created_at', today())
                    ->where('status', 'completed')
                    ->get()
                    ->sum(fn ($payment) => $tenant->calculateCommission($payment->amount)),
            ],
            'week' => [
                'total' => $tenant->payments()->where('created_at', '>=', $week)->count(),
                'revenue' => $tenant->payments()
                    ->where('created_at', '>=', $week)
                    ->where('status', 'completed')
                    ->sum('amount'),
            ],
            'month' => [
                'total' => $tenant->payments()->where('created_at', '>=', $month)->count(),
                'revenue' => $tenant->payments()
                    ->where('created_at', '>=', $month)
                    ->where('status', 'completed')
                    ->sum('amount'),
            ],
            'pending' => $tenant->payments()->where('status', 'pending')->count(),
            'failed' => $tenant->payments()->where('status', 'failed')->count(),
            'completed' => $tenant->payments()->where('status', 'completed')->count(),
            'total_revenue' => $tenant->payments()
                ->where('status', 'completed')
                ->sum('amount'),
            'total_commission_owed' => $tenant->commission_owed,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
