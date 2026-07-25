<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the /client/* route group. Unlike EnsureAgencyOwnsResource (which scopes
 * a whole agency to team members), this scopes a single client user to exactly
 * one client — spec: the Client role only ever "Accesses Own Dashboard", never
 * another client's or the wider agency's data. There's no route param to check
 * against; the client is derived entirely from the authenticated user's own
 * client_id, so there's no id a client user could tamper with to see another
 * client's data even by guessing.
 */
class EnsureClientAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->user_type !== 'client' || ! $user->client_id) {
            return response()->json(['message' => 'This area is only available to client portal accounts.'], 403);
        }

        $client = Client::find($user->client_id);

        if (! $client || $client->status === 'archived') {
            return response()->json(['message' => 'This client account is no longer active.'], 403);
        }

        // Makes the resolved Client available to controllers via $request->attributes->get('portal_client')
        // rather than every controller re-querying Client::find($user->client_id).
        $request->attributes->set('portal_client', $client);

        return $next($request);
    }
}
