<?php

namespace App\Http\Controllers\Api\V1\Agency;

use App\Dashboard\WidgetCatalogue;
use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardLayoutResource;
use App\Models\DashboardWidget;
use App\Repositories\Contracts\DashboardLayoutRepositoryInterface;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $service,
        protected DashboardLayoutRepositoryInterface $layouts,
    ) {}

    public function widgetCatalogue(): JsonResponse
    {
        return response()->json(['data' => WidgetCatalogue::forPicker()]);
    }

    public function index(Request $request): JsonResponse
    {
        $layouts = $this->service->listForUser($request->user(), $request->integer('client_id') ?: null);

        return response()->json(['data' => DashboardLayoutResource::collection($layouts)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_id' => ['nullable', 'integer'],
            'is_default' => ['sometimes', 'boolean'],
            'is_shared' => ['sometimes', 'boolean'],
            'with_default_widgets' => ['sometimes', 'boolean'],
        ]);

        $layout = $this->service->createLayout($request->user(), $data, (bool) ($data['with_default_widgets'] ?? false));

        return response()->json(['data' => new DashboardLayoutResource($layout)], 201);
    }

    public function show(Request $request, int $dashboard): JsonResponse
    {
        $layout = $this->layouts->findVisibleToUser($dashboard, $request->user()->agency_id, $request->user()->id);
        abort_if(! $layout, 404);

        return response()->json(['data' => new DashboardLayoutResource($layout)]);
    }

    public function update(Request $request, int $dashboard): JsonResponse
    {
        $layout = $this->layouts->findVisibleToUser($dashboard, $request->user()->agency_id, $request->user()->id);
        abort_if(! $layout || $layout->user_id !== $request->user()->id, 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'is_shared' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['name'])) {
            $layout = $this->service->rename($layout, $data['name']);
        }
        if (isset($data['is_shared'])) {
            $layout = $this->service->setShared($layout, $data['is_shared']);
        }

        return response()->json(['data' => new DashboardLayoutResource($layout->load('widgets'))]);
    }

    public function duplicate(Request $request, int $dashboard): JsonResponse
    {
        $layout = $this->layouts->findVisibleToUser($dashboard, $request->user()->agency_id, $request->user()->id);
        abort_if(! $layout, 404);

        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $copy = $this->service->duplicateLayout($layout, $request->user(), $data['name']);

        return response()->json(['data' => new DashboardLayoutResource($copy)], 201);
    }

    public function reset(Request $request, int $dashboard): JsonResponse
    {
        $layout = $this->layouts->findVisibleToUser($dashboard, $request->user()->agency_id, $request->user()->id);
        abort_if(! $layout || $layout->user_id !== $request->user()->id, 404);

        $layout = $this->service->resetToDefault($layout);

        return response()->json(['data' => new DashboardLayoutResource($layout)]);
    }

    public function destroy(Request $request, int $dashboard): JsonResponse
    {
        $layout = $this->layouts->findVisibleToUser($dashboard, $request->user()->agency_id, $request->user()->id);
        abort_if(! $layout || $layout->user_id !== $request->user()->id, 404);

        $this->service->delete($layout);

        return response()->json(['message' => 'Dashboard deleted.']);
    }

    public function addWidget(Request $request, int $dashboard): JsonResponse
    {
        $layout = $this->layouts->findVisibleToUser($dashboard, $request->user()->agency_id, $request->user()->id);
        abort_if(! $layout, 404);

        $data = $request->validate(['widget_type' => ['required', 'string']]);
        $widget = $this->service->addWidget($layout, $data['widget_type']);

        return response()->json(['data' => $widget], 201);
    }

    public function removeWidget(Request $request, int $dashboard, int $widget): JsonResponse
    {
        $layout = $this->layouts->findVisibleToUser($dashboard, $request->user()->agency_id, $request->user()->id);
        abort_if(! $layout, 404);

        $model = DashboardWidget::where('dashboard_layout_id', $layout->id)->find($widget);
        abort_if(! $model, 404);

        $this->service->removeWidget($model);

        return response()->json(['message' => 'Widget removed.']);
    }

    /** Bulk position save after a drag/drop/resize session. */
    public function savePositions(Request $request, int $dashboard): JsonResponse
    {
        $layout = $this->layouts->findVisibleToUser($dashboard, $request->user()->agency_id, $request->user()->id);
        abort_if(! $layout, 404);

        $data = $request->validate([
            'positions' => ['required', 'array'],
            'positions.*.id' => ['required', 'integer'],
            'positions.*.x' => ['required', 'integer', 'min:0'],
            'positions.*.y' => ['required', 'integer', 'min:0'],
            'positions.*.w' => ['required', 'integer', 'min:1'],
            'positions.*.h' => ['required', 'integer', 'min:1'],
        ]);

        $this->service->savePositions($layout, collect($data['positions'])->map(fn ($p) => [
            'id' => $p['id'], 'x' => $p['x'], 'y' => $p['y'], 'w' => $p['w'], 'h' => $p['h'],
        ])->all());

        return response()->json(['message' => 'Layout saved.']);
    }
}
