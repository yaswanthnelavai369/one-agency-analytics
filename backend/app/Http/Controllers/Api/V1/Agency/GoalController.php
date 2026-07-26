<?php

namespace App\Http\Controllers\Api\V1\Agency;

use App\Goals\GoalCatalogue;
use App\Http\Controllers\Controller;
use App\Http\Resources\GoalResource;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\GoalRepositoryInterface;
use App\Services\Goals\GoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    public function __construct(
        protected GoalService $service,
        protected GoalRepositoryInterface $goals,
        protected ClientRepositoryInterface $clients,
    ) {}

    public function catalogue(): JsonResponse
    {
        return response()->json(['data' => GoalCatalogue::all()]);
    }

    public function index(Request $request, int $client): JsonResponse
    {
        $clientModel = $this->clients->findForAgency($request->user()->agency_id, $client);
        abort_if(! $clientModel, 404);

        $goals = $this->service->listForClient($clientModel, $request->query('status'));

        return response()->json(['data' => GoalResource::collection($goals)]);
    }

    public function store(Request $request, int $client): JsonResponse
    {
        $clientModel = $this->clients->findForAgency($request->user()->agency_id, $client);
        abort_if(! $clientModel, 404);

        $data = $this->validated($request);
        $goal = $this->service->create($clientModel, $request->user(), $data);

        return response()->json(['data' => new GoalResource($goal)], 201);
    }

    public function show(Request $request, int $client, int $goal): JsonResponse
    {
        $model = $this->goals->findForClient($client, $goal);
        abort_if(! $model || $model->agency_id !== $request->user()->agency_id, 404);

        return response()->json(['data' => new GoalResource($model->load('progress'))]);
    }

    public function update(Request $request, int $client, int $goal): JsonResponse
    {
        $model = $this->goals->findForClient($client, $goal);
        abort_if(! $model || $model->agency_id !== $request->user()->agency_id, 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'target_value' => ['sometimes', 'numeric', 'min:0'],
            'deadline' => ['sometimes', 'nullable', 'date'],
        ]);

        $model = $this->service->update($model, $data);

        return response()->json(['data' => new GoalResource($model)]);
    }

    /** Manual goals only — auto-tracked goals get their progress from recompute, not this. */
    public function addProgress(Request $request, int $client, int $goal): JsonResponse
    {
        $model = $this->goals->findForClient($client, $goal);
        abort_if(! $model || $model->agency_id !== $request->user()->agency_id, 404);

        $data = $request->validate([
            'value' => ['required', 'numeric', 'min:0'],
            'mode' => ['sometimes', 'in:set,increment'],
        ]);

        $model = $this->service->addManualProgress($model, $data['value'], $data['mode'] ?? 'set', $request->user());

        return response()->json(['data' => new GoalResource($model)]);
    }

    public function recompute(Request $request, int $client, int $goal): JsonResponse
    {
        $model = $this->goals->findForClient($client, $goal);
        abort_if(! $model || $model->agency_id !== $request->user()->agency_id, 404);

        $model = $this->service->recomputeAutoProgress($model);

        return response()->json(['data' => new GoalResource($model)]);
    }

    public function archive(Request $request, int $client, int $goal): JsonResponse
    {
        $model = $this->goals->findForClient($client, $goal);
        abort_if(! $model || $model->agency_id !== $request->user()->agency_id, 404);

        $model = $this->service->archive($model);

        return response()->json(['data' => new GoalResource($model)]);
    }

    public function destroy(Request $request, int $client, int $goal): JsonResponse
    {
        $model = $this->goals->findForClient($client, $goal);
        abort_if(! $model || $model->agency_id !== $request->user()->agency_id, 404);

        $this->service->delete($model);

        return response()->json(['message' => 'Goal deleted.']);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'metric' => ['nullable', 'string'],
            'tracking_mode' => ['required', 'in:cumulative,snapshot,manual'],
            'target_value' => ['required', 'numeric', 'min:0'],
            'format' => ['sometimes', 'in:number,percent,currency'],
            'start_date' => ['sometimes', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
    }
}
