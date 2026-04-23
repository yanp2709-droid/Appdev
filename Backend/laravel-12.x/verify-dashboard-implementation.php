<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test the service
$service = app(\App\Services\DashboardWidgetService::class);

echo "=== Dashboard Customization Verification ===\n\n";

// 1. Check database table
echo "1. Database Table Check:\n";
$exists = \Illuminate\Support\Facades\Schema::hasTable('dashboard_widgets');
echo "   dashboard_widgets table exists: " . ($exists ? "✓ YES" : "✗ NO") . "\n";

if ($exists) {
    $count = \Illuminate\Support\Facades\DB::table('dashboard_widgets')->count();
    echo "   Current records: $count\n";
    $columns = \Illuminate\Support\Facades\Schema::getColumns('dashboard_widgets');
    echo "   Columns: " . implode(', ', array_map(fn($c) => $c['name'], $columns)) . "\n";
}

// 2. Check available widgets
echo "\n2. Available Widgets (" . count($service->getAvailableWidgets()) . " total):\n";
foreach (array_keys($service->getAvailableWidgets()) as $i => $widget) {
    echo "   " . ($i + 1) . ". $widget\n";
}

// 3. Check default widgets
echo "\n3. Default Widgets (" . count(\App\Services\DashboardWidgetService::DEFAULT_WIDGETS) . "):\n";
foreach (\App\Services\DashboardWidgetService::DEFAULT_WIDGETS as $widget) {
    echo "   • $widget\n";
}

// 4. Check routes
echo "\n4. Routes Check:\n";
$routes = \Illuminate\Support\Facades\Route::getRoutes();
$dashboardRoutes = [];
foreach ($routes as $route) {
    if (str_contains($route->uri() ?? '', 'filament/dashboard/widget')) {
        $dashboardRoutes[] = strtoupper($route->methods()[0] ?? 'GET') . ' ' . ($route->uri() ?? '');
    }
}
echo "   Registered routes: " . (count($dashboardRoutes) > 0 ? "✓ YES" : "✗ NO") . "\n";
foreach ($dashboardRoutes as $route) {
    echo "   • $route\n";
}

// 5. Check files exist
echo "\n5. Files Check:\n";
$files = [
    'app/Services/DashboardWidgetService.php',
    'app/Models/DashboardWidget.php',
    'app/Http/Controllers/Filament/DashboardWidgetController.php',
    'app/Filament/Widgets/CustomizableWidget.php',
];
foreach ($files as $file) {
    $exists = file_exists($file);
    echo "   " . ($exists ? "✓" : "✗") . " $file\n";
}

echo "\n=== ✓ All checks completed ===\n";
