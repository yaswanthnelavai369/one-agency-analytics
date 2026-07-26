<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Goals\GoalCatalogue;
use App\Http\Controllers\Controller;
use App\Http\Resources\GoalResource;
use App\Repositories\Contracts\GoalRepositoryInterface;
use App\Services\Goals\GoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Spec gives the Client role "Create Goals" — but not edit/delete, which stay
 * an agency-side action so a client can't silently move their own goalposts.
 */
class ClientGoalController extends Controller
{
    public function __construct(
        protected GoalService $service,
        protected GoalRepositoryInterface $goals,
    ) {}

    public function catalogue(): JsonResponse
    {
        return response()->json(['data' => GoalCatalogue::all()]);
    }

    public function index(Request $request): JsonResponse
    {
        $client = $request->attributes->get('portal_client');
        $goals = $this->service->listForClient($client, $request->query('status'));

        return response()->json(['data' => GoalResource::collection($goals)]);
    }

    public function store(Request $request): JsonResponse
    {
        $client = $request->attributes->get('portal_client');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'metric' => ['nullable', 'string'],
            'tracking_mode' => ['required', 'in:cumulative,snapshot,manual'],
            'target_value' => ['required', 'numeric', 'min:0'],
            'format' => ['sometimes', 'in:number,percent,currency'],
            'deadline' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $goal = $this->service->create($client, $request->user(), $data);

        return response()->json(['data' => new GoalResource($goal)], 201);
    }

    public function show(Request $request, int $goal): JsonResponse
    {
        $client = $request->attributes->get('portal_client');
        $model = $this->goals->findForClient($client->id, $goal);
        abort_if(! $model, 404);

        return response()->json(['data' => new GoalResource($model->load('progress'))]);
    }
}
