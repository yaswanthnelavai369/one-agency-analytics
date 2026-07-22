<?php

namespace App\Http\Middleware;

use App\Services\RBAC\RBACService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage in routes: ->middleware('permission:reports.export')
 */
class CheckPermission
{
    public function __construct(protected RBACService $rbac) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        [$module, $action] = array_pad(explode('.', $permission, 2), 2, null);

        if (! $this->rbac->userCan($user, $module, $action)) {
            return response()->json(['message' => 'You do not have permission to perform this action.'], 403);
        }

        return $next($request);
    }
}
