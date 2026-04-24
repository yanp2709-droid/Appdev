<?php

namespace App\Http\Controllers\Filament;

use App\Http\Controllers\Controller;
use App\Models\DashboardWidget;
use App\Services\DashboardWidgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardWidgetController extends Controller
{
    protected DashboardWidgetService $widgetService;

    public function __construct(DashboardWidgetService $widgetService)
    {
        $this->widgetService = $widgetService;
    }

    /**
     * Remove a widget from the user's dashboard
     */
    public function removeWidget(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'widget' => 'required|string',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $widgetName = basename($validated['widget']);
            $this->widgetService->removeWidget($user, $widgetName);

            return response()->json([
                'success' => true,
                'message' => 'Widget removed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Refresh a widget
     */
    public function refreshWidget(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'widget' => 'required|string',
        ]);

        // Widget refresh is handled by the widget itself through livewire
        // This endpoint can be extended for server-side cache clearing if needed

        return response()->json([
            'success' => true,
            'message' => 'Widget refreshed',
        ]);
    }

    /**
     * Place a widget on the dashboard.
     */
    public function placeWidget(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'widget' => 'required|string',
            'target' => 'nullable|string',
            'position' => 'nullable|in:before,after',
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $widgetName = basename($validated['widget']);
            $targetWidgetName = isset($validated['target']) ? basename($validated['target']) : null;
            $position = $validated['position'] ?? 'after';

            $this->widgetService->addWidget($user, $widgetName);

            $widgetOrder = DashboardWidget::query()
                ->where('user_id', $user->id)
                ->where('is_visible', true)
                ->orderBy('order')
                ->pluck('widget_name')
                ->all();

            $widgetOrder = array_values(array_filter(
                $widgetOrder,
                fn (string $name) => $name !== $widgetName,
            ));

            if ($targetWidgetName && in_array($targetWidgetName, $widgetOrder, true)) {
                $targetIndex = array_search($targetWidgetName, $widgetOrder, true);

                if ($targetIndex !== false) {
                    $insertIndex = $position === 'before' ? $targetIndex : $targetIndex + 1;
                    array_splice($widgetOrder, $insertIndex, 0, [$widgetName]);
                } else {
                    $widgetOrder[] = $widgetName;
                }
            } else {
                $widgetOrder[] = $widgetName;
            }

            $this->widgetService->updateWidgetOrder($user, $widgetOrder);

            return response()->json([
                'success' => true,
                'message' => 'Widget placed successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reorder the widgets currently on the dashboard.
     */
    public function reorderWidgets(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'widgets' => 'required|array',
            'widgets.*' => 'string',
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $widgets = array_map('basename', $validated['widgets']);
            $this->widgetService->updateWidgetOrder($user, $widgets);

            return response()->json([
                'success' => true,
                'message' => 'Widget order updated',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get available widgets not yet added
     */
    public function getAvailableWidgets(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $available = $this->widgetService->getAvailableWidgetsForUser($user);

        return response()->json([
            'widgets' => array_map(fn($name) => $this->formatWidgetName($name), array_keys($available)),
        ]);
    }

    /**
     * Format widget name from camelCase to Title Case
     */
    protected function formatWidgetName(string $name): string
    {
        return (string) str($name)
            ->replaceMatches('/([A-Z])/', ' $1')
            ->trim()
            ->title();
    }
}
