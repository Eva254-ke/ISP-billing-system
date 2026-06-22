<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\Package;
use App\Models\UserSession;
use App\Services\MikroTik\SessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    public function __construct(
        protected SessionManager $sessionManager
    ) {}

    /**
     * List all vouchers for tenant
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        
        $vouchers = $tenant->vouchers()
            ->with(['package', 'router'])
            ->latest()
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $vouchers->items(),
            'pagination' => [
                'current_page' => $vouchers->currentPage(),
                'per_page' => $vouchers->perPage(),
                'total' => $vouchers->total(),
            ],
        ]);
    }

    /**
     * Generate bulk vouchers
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
            'quantity' => 'required|integer|min:1|max:1000',
            'validity_hours' => 'nullable|integer|min:1|max:8760',
            'prefix' => 'nullable|string|max:10',
        ]);

        $tenant = $request->user()->tenant;
        $package = Package::findOrFail($request->package_id);

        // Check package belongs to tenant
        if ($package->tenant_id !== $tenant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found',
            ], 404);
        }

        DB::beginTransaction();
        
        try {
            $batchId = Str::uuid();
            $batchName = "Batch-" . now()->format('Ymd-His');
            $prefix = $request->prefix ?? 'CB-WIFI-';
            $validityHours = $request->validity_hours ?? 24;

            $vouchers = [];
            
            for ($i = 0; $i < $request->quantity; $i++) {
                $code = $prefix . strtoupper(Str::random(6));
                
                $voucher = Voucher::create([
                    'tenant_id' => $tenant->id,
                    'package_id' => $package->id,
                    'code' => $code,
                    'prefix' => $prefix,
                    'status' => 'unused',
                    'valid_from' => now(),
                    'valid_until' => now()->addHours($validityHours),
                    'validity_hours' => $validityHours,
                    'batch_id' => $batchId,
                    'batch_name' => $batchName,
                ]);
                
                $vouchers[] = $voucher;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Generated {$request->quantity} vouchers",
                'data' => [
                    'batch_id' => $batchId,
                    'batch_name' => $batchName,
                    'quantity' => count($vouchers),
                    'package' => $package->name,
                    'validity_hours' => $validityHours,
                    'vouchers' => $vouchers, // Return all for printing
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate vouchers',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Validate voucher code
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $voucher = Voucher::where('code', $request->code)
            ->where('status', 'unused')
            ->where('valid_until', '>', now())
            ->first();

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired voucher',
                'valid' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher is valid',
            'valid' => true,
            'data' => [
                'code' => $voucher->code,
                'package' => $voucher->package->name,
                'duration' => $voucher->package->duration_formatted,
                'price' => $voucher->package->price,
                'valid_until' => $voucher->valid_until->toIso8601String(),
            ],
        ]);
    }

    /**
     * Redeem voucher (activate session)
     */
    public function redeem(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'phone' => 'required|string',
            'mac_address' => 'required|string',
            'router_id' => 'required|exists:routers,id',
        ]);

        $tenant = $request->user()->tenant;

        DB::beginTransaction();
        
        try {
            $voucher = Voucher::where('code', $request->code)
                ->where('tenant_id', $tenant->id)
                ->where('status', 'unused')
                ->where('valid_until', '>', now())
                ->lockForUpdate()
                ->first();

            if (!$voucher) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired voucher',
                ], 400);
            }

            // Create session record
            $session = UserSession::create([
                'tenant_id' => $tenant->id,
                'router_id' => $request->router_id,
                'package_id' => $voucher->package_id,
                'username' => $voucher->code,
                'phone' => $request->phone,
                'mac_address' => $request->mac_address,
                'status' => 'pending',
                'started_at' => now(),
                'expires_at' => now()->addMinutes($voucher->package->duration_in_minutes),
                'grace_period_seconds' => 300,
                'voucher_id' => $voucher->id,
            ]);

            // Activate session on MikroTik
            $result = $this->sessionManager->activateSession($session, $voucher->package);

            if (!$result['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to activate session on router',
                    'error' => $result['error'] ?? null,
                ], 500);
            }

            // Mark voucher as used
            $voucher->markAsUsed($request->phone, $request->mac_address, $request->router_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Voucher redeemed successfully',
                'data' => [
                    'session_id' => $session->id,
                    'username' => $session->username,
                    'expires_at' => $session->expires_at->toIso8601String(),
                    'package' => $voucher->package->name,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to redeem voucher',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get voucher statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        
        $stats = [
            'total' => $tenant->vouchers()->count(),
            'unused' => $tenant->vouchers()->unused()->count(),
            'used' => $tenant->vouchers()->used()->count(),
            'expired' => $tenant->vouchers()->expired()->count(),
            'revenue' => $tenant->vouchers()
                ->used()
                ->join('packages', 'vouchers.package_id', '=', 'packages.id')
                ->sum('packages.price'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}