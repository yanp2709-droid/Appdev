# Dashboard Customization Architecture

## System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                         USER INTERFACE                              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │         Filament Dashboard Page                            │   │
│  │  (app/Filament/Pages/AdminDashboard.php)                  │   │
│  │                                                             │   │
│  │  [+ Add Widget Button]  [Dashboard Title]                │   │
│  │  ┌──────────────────────────────────────────────────────┐ │   │
│  │  │  Widget 1         [↻ Refresh] [× Remove]            │ │   │
│  │  │  (Content here...)                                   │ │   │
│  │  └──────────────────────────────────────────────────────┘ │   │
│  │  ┌──────────────────────────────────────────────────────┐ │   │
│  │  │  Widget 2         [↻ Refresh] [× Remove]            │ │   │
│  │  │  (Content here...)                                   │ │   │
│  │  └──────────────────────────────────────────────────────┘ │   │
│  │  ┌──────────────────────────────────────────────────────┐ │   │
│  │  │  Widget 3         [↻ Refresh] [× Remove]            │ │   │
│  │  │  (Content here...)                                   │ │   │
│  │  └──────────────────────────────────────────────────────┘ │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
         ↓                      ↓                      ↓
    [Add Widget]        [Remove Widget]         [Refresh Widget]
         ↓                      ↓                      ↓
   ┌──────────┐         ┌──────────┐          ┌──────────┐
   │  POST    │         │  POST    │          │  POST    │
   │ /add     │         │ /remove  │          │ /refresh │
   └────┬─────┘         └────┬─────┘          └────┬─────┘
        │                    │                     │
        └────────────────────┼─────────────────────┘
                             ↓
        ┌────────────────────────────────────────┐
        │     API Routes (routes/web.php)        │
        │  (app/Http/Controllers/Filament/       │
        │   DashboardWidgetController.php)       │
        └────────────────┬───────────────────────┘
                         ↓
        ┌────────────────────────────────────────┐
        │  DashboardWidgetService                │
        │  (app/Services/                        │
        │   DashboardWidgetService.php)          │
        │                                        │
        │  • getAvailableWidgets()              │
        │  • getUserWidgets()                    │
        │  • addWidget()                         │
        │  • removeWidget()                      │
        │  • updateWidgetOrder()                │
        └────────┬───────────────────────────────┘
                 ↓
        ┌────────────────────────────────────────┐
        │      Database Layer                    │
        │  ┌──────────────────────────────────┐  │
        │  │   dashboard_widgets Table        │  │
        │  ├──────────────────────────────────┤  │
        │  │ id          (Primary Key)        │  │
        │  │ user_id     (Foreign Key)        │  │
        │  │ widget_class (Widget Class Path) │  │
        │  │ widget_name (Display Name)       │  │
        │  │ order       (Display Order)      │  │
        │  │ is_visible  (Visibility Toggle)  │  │
        │  │ settings    (JSON Settings)      │  │
        │  │ created_at, updated_at          │  │
        │  └──────────────────────────────────┘  │
        │                                        │
        │  ┌──────────────────────────────────┐  │
        │  │      users Table                 │  │
        │  └──────────────────────────────────┘  │
        └────────────────────────────────────────┘
```

## Data Flow Diagram

### 1. First Time User Visits Dashboard
```
User Visits Dashboard
        ↓
AdminDashboard::mount() called
        ↓
Check if user has widgets in DB
        ↓
    NO? → Call initializeDefaultWidgets()
        ↓
    Create 3 default widget records
        ↓
Dashboard renders with 3 widgets
```

### 2. User Adds a Widget
```
Click "+ Add Widget" Button
        ↓
Modal shows available widgets (not yet added)
        ↓
User selects widgets & clicks Save
        ↓
POST /filament/dashboard/add
        ↓
DashboardWidgetController::addWidget()
        ↓
DashboardWidgetService::addWidget()
        ↓
Create record in dashboard_widgets table
        ↓
is_visible = true
        ↓
Redirect to dashboard
        ↓
Dashboard refreshes with new widgets
```

### 3. User Removes a Widget
```
Hover over widget → X button appears
        ↓
Click X button → Confirmation dialog
        ↓
User confirms removal
        ↓
POST /filament/dashboard/widget/remove
        ↓
DashboardWidgetController::removeWidget()
        ↓
DashboardWidgetService::removeWidget()
        ↓
Update dashboard_widgets record
        ↓
Set is_visible = false
        ↓
Widget disappears from display
        ↓
Page refreshes
```

### 4. User Refreshes Widget Data
```
Hover over widget → ↻ button appears
        ↓
Click refresh button
        ↓
POST /filament/dashboard/widget/refresh
        ↓
DashboardWidgetController::refreshWidget()
        ↓
Dispatch refresh event
        ↓
Widget data reloaded
        ↓
Display updated immediately
```

## Component Interaction

```
                    ┌──────────────────────┐
                    │  AdminDashboard      │
                    │  (Filament Page)     │
                    └──────────┬───────────┘
                               │
                ┌──────────────┼──────────────┐
                │              │              │
                ↓              ↓              ↓
         ┌──────────┐    ┌──────────┐   ┌──────────┐
         │  User    │    │  Routes  │   │ Service  │
         │ Actions  │    │          │   │          │
         └──────────┘    └──────────┘   └──────────┘
                              │              ↑
                              ├──────────────┤
                              ↓              │
                    ┌──────────────────┐    │
                    │  Controller      │────┘
                    │  DashboardWidget │
                    │  Controller      │
                    └──────────┬───────┘
                               │
                               ↓
                    ┌──────────────────┐
                    │  DashboardWidget │
                    │  Service         │
                    │                  │
                    │  Validation      │
                    │  Logic           │
                    │  Database Ops    │
                    └──────────┬───────┘
                               │
                               ↓
                    ┌──────────────────┐
                    │  DashboardWidget │
                    │  Model (Eloquent)│
                    └──────────┬───────┘
                               │
                               ↓
                    ┌──────────────────┐
                    │  Database        │
                    │  dashboard_      │
                    │  widgets Table   │
                    └──────────────────┘
```

## Widget Lifecycle

```
CREATE (User adds widget)
    ├─ Create record in dashboard_widgets
    ├─ user_id = current user
    ├─ widget_class = Full\Class\Path
    ├─ widget_name = "WidgetName"
    ├─ is_visible = true
    └─ order = max_order + 1

READ (Dashboard loads)
    ├─ Query: WHERE user_id = ? AND is_visible = true
    ├─ Order by: order ASC
    ├─ Get widget_class for each
    └─ Instantiate and render widgets

UPDATE (User reorders or modifies)
    ├─ Update order field
    ├─ Update settings JSON (future)
    ├─ Update is_visible flag
    └─ Reload dashboard

DELETE (User removes widget)
    ├─ Set is_visible = false (soft delete)
    ├─ OR delete record (permanent)
    ├─ Remove from dashboard display
    └─ Update UI
```

## Security Flow

```
User Action
    ↓
Middleware checks auth
    ↓
Route validates CSRF token
    ↓
Controller validates input
    ↓
Service validates widget exists in AVAILABLE_WIDGETS
    ↓
Verify user_id matches auth user
    ↓
Database operation (scoped by user_id)
    ↓
Response with result
```

## Performance Optimizations

```
┌─ Database Queries
│  ├─ Indexed on (user_id, is_visible)
│  ├─ Single query to get all user widgets
│  └─ Prevents N+1 problems
│
├─ Caching Strategy
│  ├─ Widget list cached per user per session
│  ├─ Cache cleared on modifications
│  └─ Database as source of truth
│
├─ Frontend Optimization
│  ├─ Lazy load widgets
│  ├─ Minimal CSS/JS payload
│  └─ Event-driven updates
│
└─ API Efficiency
   ├─ Single endpoint for each action
   ├─ JSON responses
   └─ No unnecessary data transfer
```

## Extension Points

```
DashboardWidgetService
├─ Can extend for custom widget types
├─ Can add widget-specific settings
├─ Can implement widget groups
└─ Can add sharing functionality

WithWidgetActions Trait
├─ Can customize action buttons
├─ Can add custom actions
├─ Can modify button styling
└─ Can add confirmation dialogs

CustomizableWidget Base Class
├─ Can override default behavior
├─ Can add widget-specific features
├─ Can customize rendering
└─ Can implement custom logic

Database Schema
├─ settings JSON field for extensions
├─ Additional columns can be added
├─ Can store widget-specific data
└─ Can track widget analytics
```

## Error Handling Flow

```
User Action
    ↓
Try
    ├─ Validate input
    ├─ Check authentication
    ├─ Verify widget exists
    ├─ Database operation
    └─ Return success
    
Catch
    ├─ Log error
    ├─ Return error notification
    ├─ Suggest troubleshooting
    └─ Revert changes if needed
```

## Testing Strategy

```
Unit Tests
├─ DashboardWidgetService methods
├─ Model relationships
└─ Service business logic

Feature Tests
├─ Add widget flow
├─ Remove widget flow
├─ Dashboard rendering
└─ Permission checks

Integration Tests
├─ Database operations
├─ API endpoints
└─ Full user workflows

Manual Tests
├─ UI/UX verification
├─ Cross-browser compatibility
└─ Performance under load
```
