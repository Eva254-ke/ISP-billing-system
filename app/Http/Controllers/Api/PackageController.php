<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PackageController extends Controller
{
    /**
     * List all packages for tenant
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        $tenant = $user->tenant;
        
        $packages = $tenant->packages()
            ->active()
            ->ordered()
            ->get()
            ->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'description' => $package->description,
                    'code' => $package->code,
                    'price' => $package->price,
                    'currency' => $package->currency,
                    'duration' => $package->duration_formatted,
                    'duration_minutes' => $package->duration_in_minutes,
                    'bandwidth' => $package->bandwidth_formatted,
                    'data_limit_mb' => $package->data_limit_mb,
                    'is_featured' => $package->is_featured,
                    'total_sales' => $package->total_sales,
                    'total_revenue' => $package->total_revenue,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $packages,
        ]);
    }

    /**
     * Get single package details
     */
    public function show(Request $request, Package $package): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        $tenant = $user->tenant;
        
        // Authorization check
        if ($package->tenant_id !== $tenant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $package,
        ]);
    }

    /**
     * Create new package
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration_value' => 'required|integer|min:1',
            'duration_unit' => 'required|in:minutes,hours,days,weeks,months',
            'download_limit_mbps' => 'nullable|integer|min:1',
            'upload_limit_mbps' => 'nullable|integer|min:1',
            'data_limit_mb' => 'nullable|integer|min:1',
        ]);

        $user = $request->user();
        if (!$user || !$user->tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        $tenant = $user->tenant;

        $package = Package::create([
            'tenant_id' => $tenant->id,
            'name' => $request->name,
            'description' => $request->description,
            'code' => strtoupper(substr($request->name, 0, 3)) . '-' . uniqid(),
            'price' => $request->price,
            'currency' => 'KES',
            'duration_value' => $request->duration_value,
            'duration_unit' => $request->duration_unit,
            'download_limit_mbps' => $request->download_limit_mbps,
            'upload_limit_mbps' => $request->upload_limit_mbps,
            'data_limit_mb' => $request->data_limit_mb,
            'mikrotik_profile_name' => 'profile-' . strtolower(str_replace(' ', '-', $request->name)),
            'is_active' => $request->boolean('is_active', true),
            'is_featured' => $request->boolean('is_featured', false),
            'sort_order' => Package::max('sort_order') + 1,
        ]);

        Log::channel('security')->info('Package created', [
            'package_id' => $package->id,
            'name' => $package->name,
            'price' => $package->price,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Package created successfully',
            'data' => $package,
        ], 201);
    }

    /**
     * Update package
     */
    public function update(Request $request, Package $package): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        $tenant = $user->tenant;
        
        // Authorization check
        if ($package->tenant_id !== $tenant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found',
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $package->update($request->only([
            'name',
            'description',
            'price',
            'download_limit_mbps',
            'upload_limit_mbps',
            'data_limit_mb',
            'is_active',
            'is_featured',
            'sort_order',
        ]));

        Log::channel('security')->info('Package updated', [
            'package_id' => $package->id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Package updated successfully',
            'data' => $package,
        ]);
    }

    /**
     * Delete package
     */
    public function destroy(Request $request, Package $package): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        $tenant = $user->tenant;
        
        // Authorization check
        if ($package->tenant_id !== $tenant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found',
            ], 404);
        }

        // Check if package has active sessions
        if ($package->userSessions()->active()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete package with active sessions',
            ], 400);
        }

        $package->delete();

        Log::channel('security')->info('Package deleted', [
            'package_id' => $package->id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Package deleted successfully',
        ]);
    }
}
