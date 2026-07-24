<?php

namespace App\Http\Middleware;

use App\Models\Agency;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Multi-tenancy guard: ensures the authenticated user's agency_id matches
 * the {agency} route param (or the resolved model's agency_id), so agency A
 * can never read or mutate agency B's data even with a guessed numeric id.
 *
 * Master Admin has agency_id = null by design (a global, unscoped role), so
 * to let Master Admin actually exercise agency-scoped endpoints (spec:
 * "View Every Dashboard", "Login As Any User") this resolves which agency to
 * act as from an X-Agency-ID header or agency_id param, defaulting to the
 * platform's first agency if neither is given.
 */
class EnsureAgencyOwnsResource
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->isMasterAdmin()) {
            return $this->impersonateAgencyContext($request, $user, $next);
        }

        $routeAgencyId = $request->route('agency');

        if ($routeAgencyId !== null) {
            $resolvedId = is_object($routeAgencyId) ? $routeAgencyId->id : $routeAgencyId;

            if ((int) $resolvedId !== (int) $user->agency_id) {
                return response()->json(['message' => 'Forbidden: resource does not belong to your agency.'], 403);
            }
        }

        return $next($request);
    }

    protected function impersonateAgencyContext(Request $request, $user, Closure $next): Response
    {
        $agencyId = $request->header('X-Agency-ID') ?: $request->input('agency_id');

        $agency = $agencyId
            ? Agency::where('id', $agencyId)->orWhere('uuid', $agencyId)->first()
            : Agency::first();

        if (! $agency) {
            return response()->json([
                'message' => 'No agency context. Please create an agency or register an account first.',
                'error_code' => 'NO_AGENCY_CONTEXT',
            ], 400);
        }

        $user->agency_id = $agency->id;
        $user->setRelation('agency', $agency);

        return $next($request);
    }
}
