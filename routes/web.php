<?php

use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\SiteAdministration\AuthenticationController;
use App\Http\Controllers\SiteAdministration\LookupController;
use App\Http\Controllers\SiteAdministration\OrganizationController;
use App\Http\Controllers\SiteAdministration\PermissionController;
use App\Http\Controllers\SiteAdministration\RoleController;
use App\Http\Controllers\SiteAdministration\RolePermissionController;
use App\Http\Controllers\SiteAdministration\SiteSettingController;
use App\Http\Controllers\SiteAdministration\UserAccessController;
use App\Http\Controllers\SiteAdministration\UserAdministrationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('auth/redirect', [SsoController::class, 'redirect'])
        ->name('sso.redirect');
    Route::get('auth/callback', [SsoController::class, 'callback'])
        ->name('sso.callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::prefix('site-administration')
        ->name('site-administration.')
        ->group(function (): void {
            Route::redirect('/', '/site-administration/organizations')
                ->name('index');

            Route::get('authentication', [AuthenticationController::class, 'index'])
                ->name('authentication.index');

            Route::get('organizations/users/search', [OrganizationController::class, 'searchUsers'])
                ->name('organizations.users.search');
            Route::resource('organizations', OrganizationController::class)
                ->except(['create', 'edit', 'show']);
            Route::post('organization-units', [OrganizationController::class, 'storeUnit'])
                ->name('organization-units.store');
            Route::put('organization-units/{unit}', [OrganizationController::class, 'updateUnit'])
                ->name('organization-units.update');
            Route::delete('organization-units/{unit}', [OrganizationController::class, 'destroyUnit'])
                ->name('organization-units.destroy');

            Route::resource('roles', RoleController::class)
                ->except(['create', 'edit', 'show']);

            Route::resource('permissions', PermissionController::class)
                ->except(['create', 'edit', 'show']);

            Route::get('role-permissions', [RolePermissionController::class, 'index'])
                ->name('role-permissions.index');
            Route::put('role-permissions/{role}', [RolePermissionController::class, 'update'])
                ->name('role-permissions.update');

            Route::resource('users', UserAdministrationController::class)
                ->only(['index', 'store', 'update', 'destroy']);
            Route::put('users/{user}/password', [UserAdministrationController::class, 'changePassword'])
                ->name('users.change-password');
            Route::post('users/{user}/reset-password', [UserAdministrationController::class, 'resetPassword'])
                ->name('users.reset-password');
            Route::post('users/{user}/lock', [UserAdministrationController::class, 'lock'])
                ->name('users.lock');
            Route::post('users/{user}/unlock', [UserAdministrationController::class, 'unlock'])
                ->name('users.unlock');

            Route::get('user-access/{user}', [UserAccessController::class, 'edit'])
                ->name('user-access.edit');
            Route::put('user-access/{user}', [UserAccessController::class, 'update'])
                ->name('user-access.update');

            Route::resource('lookups', LookupController::class)
                ->except(['create', 'edit', 'show']);

            Route::post('settings/branding', [SiteSettingController::class, 'updateBranding'])
                ->name('settings.branding.update');
            Route::post('settings/maintenance', [SiteSettingController::class, 'updateMaintenance'])
                ->name('settings.maintenance.update');

            Route::resource('settings', SiteSettingController::class)
                ->except(['create', 'edit', 'show']);
        });
});

require __DIR__.'/settings.php';
