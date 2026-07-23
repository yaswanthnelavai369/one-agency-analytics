<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Multi-tenancy guard: ensures the authenticated user's agency_id matches
 * the {agency} route param (or the resolved model's agency_id), so agency A
 * can never read or mutate agency B's data even with a guessed numeric id.
 * Master Admin bypasses this check.
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
            $agencyId = $request->header('X-Agency-ID') ?: $request->input('agency_id');
            if ($agencyId) {
                $agency = \App\Models\Agency::where('id', $agencyId)->orWhere('uuid', $agencyId)->first();
                if ($agency) {
                    $user->agency_id = $agency->id;
                    $user->setRelation('agency', $agency);
                }
            } else {
                $agency = \App\Models\Agency::first();
                if ($agency) {
                    $user->agency_id = $agency->id;
                    $user->setRelation('agency', $agency);
                }
            }

            if (is_null($user->agency_id)) {
                return response()->json([
                    'message' => 'No agency context. Please create an agency or register an account first.',
                    'error_code' => 'NO_AGENCY_CONTEXT'
                ], 400);
            }

            return $next($request);
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
}
