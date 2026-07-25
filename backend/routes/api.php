<?php

use App\Http\Controllers\Api\V1\Admin\AgencyController as AdminAgencyController;
use App\Http\Controllers\Api\V1\Agency\AIChatController;
use App\Http\Controllers\Api\V1\Agency\AnomalyController;
use App\Http\Controllers\Api\V1\Agency\ClientController;
use App\Http\Controllers\Api\V1\Agency\DashboardController;
use App\Http\Controllers\Api\V1\Agency\HealthScoreController;
use App\Http\Controllers\Api\V1\Agency\IntegrationController;
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

    // --- OAuth callbacks: providers redirect here directly, so this is outside
    // auth:sanctum. The signed `state` param (see IntegrationService) verifies
    // which agency/client/user initiated the connection — not route middleware.
    Route::get('/integrations/{provider}/callback', [IntegrationController::class, 'callback'])
        ->name('integrations.callback');

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

            Route::middleware('permission:integrations.view')->get('/integrations/catalogue', [IntegrationController::class, 'catalogue']);
            Route::middleware('permission:integrations.view')->get('/clients/{client}/integrations', [IntegrationController::class, 'index']);
            Route::middleware('permission:integrations.connect')->post('/clients/{client}/integrations/{provider}/connect', [IntegrationController::class, 'connect']);
            Route::middleware('permission:integrations.view')->post('/clients/{client}/integrations/{integration}/sync', [IntegrationController::class, 'syncNow']);
            Route::middleware('permission:integrations.disconnect')->delete('/clients/{client}/integrations/{integration}', [IntegrationController::class, 'disconnect']);

            Route::middleware('permission:dashboards.view')->get('/dashboards/widget-catalogue', [DashboardController::class, 'widgetCatalogue']);
            Route::middleware('permission:dashboards.view')->get('/dashboards', [DashboardController::class, 'index']);
            Route::middleware('permission:dashboards.create')->post('/dashboards', [DashboardController::class, 'store']);
            Route::middleware('permission:dashboards.view')->get('/dashboards/{dashboard}', [DashboardController::class, 'show']);
            Route::middleware('permission:dashboards.edit')->put('/dashboards/{dashboard}', [DashboardController::class, 'update']);
            Route::middleware('permission:dashboards.create')->post('/dashboards/{dashboard}/duplicate', [DashboardController::class, 'duplicate']);
            Route::middleware('permission:dashboards.edit')->post('/dashboards/{dashboard}/reset', [DashboardController::class, 'reset']);
            Route::middleware('permission:dashboards.delete')->delete('/dashboards/{dashboard}', [DashboardController::class, 'destroy']);
            Route::middleware('permission:dashboards.edit')->post('/dashboards/{dashboard}/widgets', [DashboardController::class, 'addWidget']);
            Route::middleware('permission:dashboards.edit')->put('/dashboards/{dashboard}/widgets/positions', [DashboardController::class, 'savePositions']);
            Route::middleware('permission:dashboards.edit')->delete('/dashboards/{dashboard}/widgets/{widget}', [DashboardController::class, 'removeWidget']);

            Route::middleware('permission:health.view')->get('/clients/{client}/health-score', [HealthScoreController::class, 'show']);
            Route::middleware('permission:health.recalculate')->post('/clients/{client}/health-score/recalculate', [HealthScoreController::class, 'recalculate']);

            Route::middleware('permission:ai_chat.view')->get('/clients/{client}/ai-chat', [AIChatController::class, 'show']);
            Route::middleware('permission:ai_chat.send')->post('/clients/{client}/ai-chat/messages', [AIChatController::class, 'sendMessage']);
            Route::middleware('permission:ai_chat.view')->get('/ai-chat/quick-prompts', [AIChatController::class, 'quickPrompts']);

            Route::middleware('permission:anomalies.view')->get('/clients/{client}/anomalies', [AnomalyController::class, 'index']);
            Route::middleware('permission:anomalies.manage')->post('/clients/{client}/anomalies/detect', [AnomalyController::class, 'detect']);
            Route::middleware('permission:anomalies.manage')->post('/clients/{client}/anomalies/{anomaly}/acknowledge', [AnomalyController::class, 'acknowledge']);
            Route::middleware('permission:anomalies.manage')->post('/clients/{client}/anomalies/{anomaly}/resolve', [AnomalyController::class, 'resolve']);

            // Route::apiResource('/team', TeamMemberController::class); // invite/list/update/remove
            // Route::put('/branding', [AgencyBrandingController::class, 'update']);
            // Route::post('/domain', [AgencyBrandingController::class, 'requestCustomDomain']);
        });

        // --- Client portal: a client user viewing their own data ---
        // Route::prefix('client')->group(function () { ... });
    });
});
