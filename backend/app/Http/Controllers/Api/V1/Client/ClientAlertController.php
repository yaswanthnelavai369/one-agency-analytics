<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnomalyResource;
use App\Services\Anomaly\AnomalyDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only (spec: client role "Receives Notifications", doesn't manage the
 * alert triage workflow — acknowledge/resolve stay an agency-side action).
 */
class ClientAlertController extends Controller
{
    public function __construct(protected AnomalyDetectionService $service) {}

    public function index(Request $request): JsonResponse
    {
        $client = $request->attributes->get('portal_client');
        $anomalies = $this->service->list($client, $request->query('status', 'open'));

        return response()->json(['data' => AnomalyResource::collection($anomalies)]);
    }
}
