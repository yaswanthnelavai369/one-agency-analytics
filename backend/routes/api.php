<?php

use App\Http\Controllers\Api\V1\Admin\AgencyController as AdminAgencyController;
use App\Http\Controllers\Api\V1\Agency\ClientController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
| Every route is namespaced under /api/v1 for forward compatibility.
| Auth: Sanctum personal access tokens (SPA/mobile clients use bearer tokens).
| Tenancy: routes under /agency/* require 'agency.owns' which checks the
| authenticated user's agency_id against the resource being accessed.
| Authorization: 'permission:<module>.<action>' maps to RBACService checks.
*/

Route::prefix('v1')->group(function () {

    // --- Public auth endpoints ---
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/2fa/verify', [TwoFactorController::class, 'verify']);
    // Route::get('/auth/google/redirect', ...);   // Socialite - added when Google integration module ships
    // Route::get('/auth/microsoft/redirect', ...); // Socialite - added when Microsoft integration module ships

    // --- Authenticated endpoints ---
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/logout-all', [AuthController::class, 'logoutAllDevices']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::prefix('auth/2fa')->group(function () {
            Route::post('/setup', [TwoFactorController::class, 'setup']);
            Route::post('/confirm', [TwoFactorController::class, 'confirm']);
            Route::post('/disable', [TwoFactorController::class, 'disable']);
        });

        // --- Master Admin: cross-tenant platform management ---
        Route::prefix('admin')->middleware('role:Master Admin')->group(function () {
            Route::get('/agencies', [AdminAgencyController::class, 'index']);
            Route::get('/agencies/{agency}', [AdminAgencyController::class, 'show']);
            Route::post('/agencies/{agency}/suspend', [AdminAgencyController::class, 'suspend']);
            Route::post('/agencies/{agency}/reactivate', [AdminAgencyController::class, 'reactivate']);
            // Route::apiResource('/plans', PlanController::class);
            // Route::get('/analytics/platform', [PlatformAnalyticsController::class, 'index']);
        });

        // --- Agency panel: scoped to the caller's own agency ---
        Route::prefix('agency')->middleware('agency.owns')->group(function () {
            Route::middleware('permission:clients.view')->get('/clients', [ClientController::class, 'index']);
            Route::middleware('permission:clients.create')->post('/clients', [ClientController::class, 'store']);
            Route::middleware('permission:clients.view')->get('/clients/{client}', [ClientController::class, 'show']);
            Route::middleware('permission:clients.edit')->put('/clients/{client}', [ClientController::class, 'update']);
            Route::middleware('permission:clients.delete')->delete('/clients/{client}', [ClientController::class, 'destroy']);

            // Route::apiResource('/team', TeamMemberController::class); // invite/list/update/remove
            // Route::put('/branding', [AgencyBrandingController::class, 'update']);
            // Route::post('/domain', [AgencyBrandingController::class, 'requestCustomDomain']);
        });

        // --- Client portal: a client user viewing their own data ---
        // Route::prefix('client')->group(function () { ... });
    });
});
