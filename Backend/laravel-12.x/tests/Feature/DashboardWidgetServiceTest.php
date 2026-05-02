<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DashboardWidgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardWidgetServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_gracefully_handles_missing_dashboard_widgets_table(): void
    {
        $user = User::factory()->admin()->create();
        $service = app(DashboardWidgetService::class);

        Schema::dropIfExists('dashboard_widgets');

        $this->assertFalse($service->isStorageReady());
        $this->assertCount(0, $service->getUserWidgets($user));
        $this->assertSame(
            DashboardWidgetService::AVAILABLE_WIDGETS,
            $service->getAvailableWidgetsForUser($user),
        );

        $service->initializeDefaultWidgets($user);
        $service->resetWidgetCollection($user);

        $this->assertFalse($service->removeWidget($user, 'TotalStudentsWidget'));
    }
}
