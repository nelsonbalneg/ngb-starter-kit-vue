<?php

use App\Http\Middleware\EnsureActiveSessionAccount;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetOrganizationPermissionContext;
use App\Http\Middleware\CheckFeatureAvailability;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->preventRequestsDuringMaintenance(except: [
            'api/sso-features',
            'api/sso-maintenance',
            'api/sso-user-access',
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/sso-features',
            'api/sso-maintenance',
            'api/sso-user-access',
        ]);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->redirectGuestsTo(
            fn (Request $request) => (bool) config('sso.enabled') && ! $request->expectsJson()
                ? route('sso.redirect')
                : route('login'),
        );

        $middleware->web(append: [
            HandleAppearance::class,
            EnsureActiveSessionAccount::class,
            SetOrganizationPermissionContext::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            CheckFeatureAvailability::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
