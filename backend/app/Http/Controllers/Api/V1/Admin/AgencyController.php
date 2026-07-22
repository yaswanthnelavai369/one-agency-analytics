<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgencyResource;
use App\Repositories\Contracts\AgencyRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Master Admin only — see routes/api.php ('role:Master Admin' + 'auth:sanctum').
 * Cross-tenant visibility into every agency on the platform.
 */
class AgencyController extends Controller
{
    public function __construct(protected AgencyRepositoryInterface $agencies) {}

    public function index(Request $request): JsonResponse
    {
        $agencies = $this->agencies->paginate(
            $request->integer('per_page', 15),
            $request->only(['search', 'status', 'sort'])
        );

        return response()->json([
            'data' => AgencyResource::collection($agencies),
            'meta' => [
                'current_page' => $agencies->currentPage(),
                'last_page' => $agencies->lastPage(),
                'total' => $agencies->total(),
            ],
        ]);
    }

    public function show(int $agency): JsonResponse
    {
        $agency = $this->agencies->withUsageCounts($agency);
        abort_if(! $agency, 404);

        return response()->json(new AgencyResource($agency));
    }

    public function suspend(int $agency): JsonResponse
    {
        $agency = $this->agencies->findOrFail($agency);
        $this->agencies->update($agency, ['status' => 'suspended']);

        return response()->json(['message' => 'Agency suspended.']);
    }

    public function reactivate(int $agency): JsonResponse
    {
        $agency = $this->agencies->findOrFail($agency);
        $this->agencies->update($agency, ['status' => 'active']);

        return response()->json(['message' => 'Agency reactivated.']);
    }
}
