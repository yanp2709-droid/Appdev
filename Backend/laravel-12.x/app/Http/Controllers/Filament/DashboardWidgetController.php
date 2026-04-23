<?php

namespace App\Http\Controllers\Filament;

use App\Http\Controllers\Controller;
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
