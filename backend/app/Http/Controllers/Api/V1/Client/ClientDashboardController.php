<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardLayoutResource;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Read-only: the client portal never edits the layout (spec: "Access Own Dashboard" only). */
class ClientDashboardController extends Controller
{
    public function __construct(protected DashboardService $service) {}

    public function show(Request $request): JsonResponse
    {
        $client = $request->attributes->get('portal_client');
        $layout = $this->service->clientFacingLayout($client);

        return response()->json(['data' => new DashboardLayoutResource($layout->load('widgets'))]);
    }
}
