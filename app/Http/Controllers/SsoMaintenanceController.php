<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class SsoMaintenanceController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $signatureResponse = $this->validateSignature($request);

        if ($signatureResponse instanceof JsonResponse) {
            return $signatureResponse;
        }

        if ($request->isMethod('get')) {
            $isDown = app()->isDownForMaintenance();
            $data = [];
            $downFile = storage_path('framework/down');

            if ($isDown && file_exists($downFile)) {
                $data = json_decode((string) file_get_contents($downFile), true) ?: [];
            }

            return response()->json([
                'enabled' => $isDown,
                'status' => $data['status'] ?? ($isDown ? 503 : 200),
                'bypass_url' => isset($data['secret']) ? url('/'.$data['secret']) : null,
                'since' => isset($data['time']) ? date(DATE_ATOM, $data['time']) : null,
            ]);
        }

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'action' => ['required', 'in:up,down'],
                'maintenance_secret_key' => ['required', 'string'],
                'bypass_secret' => ['nullable', 'string'],
            ]);

            $configuredMaintenanceSecret = config('services.sso.maintenance_secret');

            if (
                is_string($configuredMaintenanceSecret)
                && $configuredMaintenanceSecret !== ''
                && ! hash_equals($configuredMaintenanceSecret, $validated['maintenance_secret_key'])
            ) {
                return response()->json(['message' => 'Invalid maintenance secret key.'], 401);
            }

            if ($validated['action'] === 'down') {
                $bypassSecret = $validated['bypass_secret'] ?? Str::random(32);

                Artisan::call('down', [
                    '--secret' => $bypassSecret,
                    '--status' => 503,
                ]);

                return response()->json([
                    'message' => 'Application put down successfully.',
                    'bypass_secret' => $bypassSecret,
                ]);
            }

            Artisan::call('up');

            return response()->json([
                'message' => 'Application brought live successfully.',
            ]);
        }

        return response()->json(['message' => 'Method not allowed.'], 405);
    }

    private function validateSignature(Request $request): ?JsonResponse
    {
        $clientId = $request->header('X-SSO-Client-ID');
        $timestamp = $request->header('X-SSO-Timestamp');
        $signature = $request->header('X-SSO-Signature');

        if (! $clientId || ! $timestamp || ! $signature) {
            return response()->json(['message' => 'Missing signature headers.'], 401);
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return response()->json(['message' => 'Request expired.'], 401);
        }

        $configuredClientId = config('services.sso.client_id');
        $configuredClientSecret = config('services.sso.client_secret');

        if (! $configuredClientId || ! $configuredClientSecret) {
            return response()->json(['message' => 'SSO client is not configured on this remote server.'], 500);
        }

        if (strtolower((string) $clientId) !== strtolower((string) $configuredClientId)) {
            return response()->json(['message' => 'Invalid Client ID.'], 401);
        }

        $expectedSignature = hash_hmac('sha256', $timestamp.'.'.$clientId, (string) $configuredClientSecret);

        if (! hash_equals($expectedSignature, (string) $signature)) {
            return response()->json(['message' => 'Invalid signature verification.'], 401);
        }

        return null;
    }
}
