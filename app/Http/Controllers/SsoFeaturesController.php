<?php

namespace App\Http\Controllers;

use App\Models\SystemFeature;
use App\Services\Features\FeatureService;
use Illuminate\Http\Request;

class SsoFeaturesController extends Controller
{
    public function __construct(private readonly FeatureService $features) {}

    public function handle(Request $request)
    {
        // 1. Authenticate using the OAuth client credentials signature
        $clientId = $request->header('X-SSO-Client-ID');
        $timestamp = $request->header('X-SSO-Timestamp');
        $signature = $request->header('X-SSO-Signature');

        if (!$clientId || !$timestamp || !$signature) {
            return response()->json(['message' => 'Missing signature headers.'], 401);
        }

        // Verify timestamp within 5 minutes to prevent replay attacks
        if (abs(time() - $timestamp) > 300) {
            return response()->json(['message' => 'Request expired.'], 401);
        }

        // Retrieve configured client ID and secret
        $configuredClientId = config('services.sso.client_id');
        $configuredClientSecret = config('services.sso.client_secret');

        if (!$configuredClientId || !$configuredClientSecret) {
            return response()->json(['message' => 'SSO client is not configured on this remote server.'], 500);
        }

        if (strtolower($clientId) !== strtolower($configuredClientId)) {
            return response()->json(['message' => 'Invalid Client ID.'], 401);
        }

        // Verify HMAC signature using configured client_secret
        $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $clientId, $configuredClientSecret);
        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json(['message' => 'Invalid signature verification.'], 401);
        }

        // 2. GET request: Return all features
        if ($request->isMethod('get')) {
            $featuresList = SystemFeature::orderBy('module_name')->orderBy('sort_order')->get();
            return response()->json($featuresList);
        }

        // 3. POST request: Update feature status/message
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'feature_key' => 'required|string',
                'status' => 'required|in:active,maintenance,disabled',
                'maintenance_message' => 'nullable|string|max:1000',
                'reason' => 'nullable|string|max:500',
            ]);

            $feature = SystemFeature::where('feature_key', $validated['feature_key'])->first();
            if (!$feature) {
                return response()->json(['message' => 'Feature not found.'], 404);
            }

            // Get default/admin actor or system user
            $actor = auth()->user() ?? \App\Models\User::where('email', 'admin@sso.test')->first() ?? \App\Models\User::first();

            if (!$actor) {
                return response()->json(['message' => 'No administrative actor found to log this update.'], 500);
            }

            $this->features->updateStatus(
                feature: $feature,
                status: $validated['status'],
                maintenanceMessage: $validated['maintenance_message'] ?? null,
                reason: $validated['reason'] ?? 'Updated via SSO Server',
                actor: $actor,
            );

            return response()->json([
                'message' => 'Feature updated successfully.',
                'feature' => $feature->fresh(),
            ]);
        }

        return response()->json(['message' => 'Method not allowed.'], 405);
    }
}
