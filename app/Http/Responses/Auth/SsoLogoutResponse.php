<?php

namespace App\Http\Responses\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Fortify;

class SsoLogoutResponse implements LogoutResponse
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $logoutRedirectUri = (string) config('sso.logout_redirect_uri');

        if ((bool) config('sso.enabled') && $logoutRedirectUri !== '') {
            Log::info('User logged out; redirecting to configured SSO logout destination.', [
                'host' => $request->getHost(),
                'logout_redirect_uri' => $logoutRedirectUri,
            ]);

            if ($request->header('X-Inertia')) {
                return Inertia::location($logoutRedirectUri);
            }

            return redirect()->away($logoutRedirectUri);
        }

        return redirect(Fortify::redirects('logout', '/'));
    }
}
