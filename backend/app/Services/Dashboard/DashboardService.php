<?php

namespace App\Services\Dashboard;

use App\Dashboard\WidgetCatalogue;
use App\Models\Client;
use App\Models\DashboardLayout;
use App\Models\DashboardWidget;
use App\Models\User;
use App\Repositories\Contracts\DashboardLayoutRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DashboardService
{
    public function __construct(protected DashboardLayoutRepositoryInterface $layouts) {}

    public function listForUser(User $user, ?int $clientId = null)
    {
        return $this->layouts->visibleToUser($user->agency_id, $user->id, $clientId);
    }

    /**
     * What the client portal reads: the first dashboard an agency team member
     * has explicitly shared for this client. If none exists yet, auto-provisions
     * one (owned by the agency's owner, since client-portal layouts aren't
     * personally owned by any one team member) so a brand-new client isn't
     * looking at an empty portal on day one.
     */
    public function clientFacingLayout(Client $client): DashboardLayout
    {
        $existing = $this->layouts->sharedForClient($client->id)->first();

        if ($existing) {
            return $existing;
        }

        $owner = $client->agency->owner;

        return $this->createLayout($owner, [
            'name' => "{$client->name} — Overview",
            'client_id' => $client->id,
            'is_shared' => true,
            'is_default' => true,
        ], withDefaultWidgets: true);
    }

    /** Creates a layout, optionally pre-populated with the default widget set (used on first client visit). */
    public function createLayout(User $user, array $data, bool $withDefaultWidgets = false): DashboardLayout
    {
        return DB::transaction(function () use ($user, $data, $withDefaultWidgets) {
            /** @var DashboardLayout $layout */
            $layout = $this->layouts->create([
                'uuid' => Str::uuid(),
                'agency_id' => $user->agency_id,
                'client_id' => $data['client_id'] ?? null,
                'user_id' => $user->id,
                'name' => $data['name'],
                'is_default' => $data['is_default'] ?? false,
                'is_shared' => $data['is_shared'] ?? false,
            ]);

            if ($withDefaultWidgets) {
                foreach (WidgetCatalogue::defaultWidgetTypes() as $i => $type) {
                    $this->addWidget($layout, $type, ['sort_order' => $i]);
                }
            }

            return $layout->load('widgets');
        });
    }

    public function duplicateLayout(DashboardLayout $source, User $user, string $newName): DashboardLayout
    {
        return DB::transaction(function () use ($source, $user, $newName) {
            $copy = $this->layouts->create([
                'uuid' => Str::uuid(),
                'agency_id' => $source->agency_id,
                'client_id' => $source->client_id,
                'user_id' => $user->id,
                'name' => $newName,
            ]);

            foreach ($source->widgets as $widget) {
                DashboardWidget::create([
                    'uuid' => Str::uuid(),
                    'dashboard_layout_id' => $copy->id,
                    'widget_type' => $widget->widget_type,
                    'config' => $widget->config,
                    'pos_x' => $widget->pos_x,
                    'pos_y' => $widget->pos_y,
                    'width' => $widget->width,
                    'height' => $widget->height,
                    'sort_order' => $widget->sort_order,
                ]);
            }

            return $copy->load('widgets');
        });
    }

    public function rename(DashboardLayout $layout, string $name): DashboardLayout
    {
        return $this->layouts->update($layout, ['name' => $name]);
    }

    public function setShared(DashboardLayout $layout, bool $shared): DashboardLayout
    {
        return $this->layouts->update($layout, ['is_shared' => $shared]);
    }

    public function delete(DashboardLayout $layout): void
    {
        $this->layouts->delete($layout);
    }

    public function addWidget(DashboardLayout $layout, string $widgetType, array $overrides = []): DashboardWidget
    {
        if (! WidgetCatalogue::exists($widgetType)) {
            throw ValidationException::withMessages(['widget_type' => "Unknown widget type [{$widgetType}]."]);
        }

        $size = WidgetCatalogue::defaultSize($widgetType);

        return DashboardWidget::create(array_merge([
            'uuid' => Str::uuid(),
            'dashboard_layout_id' => $layout->id,
            'widget_type' => $widgetType,
            'pos_x' => 0,
            'pos_y' => 0,
            'width' => $size['w'],
            'height' => $size['h'],
        ], $overrides));
    }

    public function removeWidget(DashboardWidget $widget): void
    {
        $widget->delete();
    }

    /**
     * Bulk-persists the grid after a drag/drop/resize session.
     * $positions: [['id' => widgetId, 'x' => .., 'y' => .., 'w' => .., 'h' => ..], ...]
     */
    public function savePositions(DashboardLayout $layout, array $positions): void
    {
        DB::transaction(function () use ($layout, $positions) {
            $widgetIds = $layout->widgets()->pluck('id')->all();

            foreach ($positions as $pos) {
                if (! in_array($pos['id'], $widgetIds, true)) {
                    continue; // ignore ids that don't belong to this layout
                }

                DashboardWidget::where('id', $pos['id'])->update([
                    'pos_x' => $pos['x'],
                    'pos_y' => $pos['y'],
                    'width' => $pos['w'],
                    'height' => $pos['h'],
                ]);
            }
        });
    }

    public function resetToDefault(DashboardLayout $layout): DashboardLayout
    {
        return DB::transaction(function () use ($layout) {
            $layout->widgets()->delete();

            foreach (WidgetCatalogue::defaultWidgetTypes() as $i => $type) {
                $this->addWidget($layout, $type, ['sort_order' => $i]);
            }

            return $layout->fresh('widgets');
        });
    }
}
