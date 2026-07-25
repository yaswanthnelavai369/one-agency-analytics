<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientPortalController extends Controller
{
    /** "Who am I / what agency and branding am I under" — the client portal's landing call. */
    public function me(Request $request): JsonResponse
    {
        $client = $request->attributes->get('portal_client');
        $client->loadMissing('agency');

        return response()->json([
            'user' => new UserResource($request->user()),
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'website' => $client->website,
                'industry' => $client->industry,
                'status' => $client->status,
            ],
            'agency_branding' => [
                'name' => $client->agency->brand_name ?? $client->agency->name,
                'logo' => $client->agency->logo_path,
                'primary_color' => $client->agency->primary_color,
                'secondary_color' => $client->agency->secondary_color,
                'font_family' => $client->agency->font_family,
                'hide_platform_branding' => $client->agency->hide_platform_branding,
            ],
        ]);
    }
}
