<?php

namespace App\Http\Middleware;

use App\Services\SiteAdministration\UserImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSessionAccount
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $message = match (true) {
            $user->locked_at !== null => $user->lockedOutMessage(),
            ! $user->is_active => 'This account is inactive. Please contact an administrator for assistance.',
            default => null,
        };

        if ($message === null) {
            return $next($request);
        }

        $impersonation = app(UserImpersonationService::class);

        if ($impersonation->isImpersonating($request)) {
            $impersonation->stop($request);

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return to_route('site-administration.authentication.index', ['tab' => 'users'])
                ->with('status', $message);
        }

        Auth::guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return to_route('login')->with('status', $message);
    }
}
