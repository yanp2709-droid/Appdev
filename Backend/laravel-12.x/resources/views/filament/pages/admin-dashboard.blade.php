<x-filament-panels::page>
    @php
        $widgets = $this->getDashboardWidgets();
        $widgetCatalog = $this->getWidgetCatalog();
        $activeWidgetClasses = $this->getActiveWidgetClasses();
    @endphp

    <style>
        .dashboard-shell {
            border: 1px solid #dbe4f0;
            border-radius: 30px;
            padding: 24px;
            background:
                radial-gradient(circle at top right, rgba(26, 95, 212, 0.08), transparent 18rem),
                linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 24px 50px rgba(15, 23, 42, 0.08);
        }

        .dashboard-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        .dashboard-title {
            margin: 0;
            font-size: clamp(24px, 3vw, 38px);
            font-weight: 800;
            color: #111827;
        }

        .dashboard-kicker {
            margin: 0 0 8px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #1a5fd4;
        }

        .dashboard-copy {
            margin: 10px 0 0;
            max-width: 720px;
            font-size: 14px;
            line-height: 1.6;
            color: #64748b;
        }

        .dashboard-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            border-radius: 999px;
            padding: 11px 18px;
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            background: #1a5fd4;
            box-shadow: 0 12px 24px rgba(26, 95, 212, 0.22);
            transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
        }

        .dashboard-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 28px rgba(26, 95, 212, 0.28);
        }

        .dashboard-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 18px;
            align-items: start;
        }

        .dashboard-layout.is-open {
            grid-template-columns: minmax(0, 1fr) 268px;
        }

        .dashboard-canvas {
            min-height: 680px;
            border-radius: 26px;
            background: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
            padding: 12px;
            position: relative;
            border: 1px solid #dbe4f0;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 18px;
            align-items: start;
        }

        .dashboard-widget-shell {
            position: relative;
            min-width: 0;
            border-radius: 22px;
            background: linear-gradient(180deg, #ffffff 0%, #fdfefe 100%);
            box-shadow: 0 18px 34px rgba(15, 23, 42, 0.08);
            border: 1px solid #dbe4f0;
            overflow: visible;
            padding-top: 16px;
        }

        .dashboard-widget-shell.is-dragging {
            opacity: 0.55;
        }

        .dashboard-widget-actions {
            position: absolute;
            top: 14px;
            right: 14px;
            z-index: 10;
            display: flex;
            gap: 8px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .dashboard-widget-shell:hover .dashboard-widget-actions {
            opacity: 1;
        }

        .dashboard-widget-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border: 0;
            border-radius: 999px;
            color: #fff;
            box-shadow: 0 8px 14px rgba(15, 23, 42, 0.12);
            transition: transform 0.15s ease, opacity 0.15s ease;
        }

        .dashboard-widget-action svg {
            width: 14px;
            height: 14px;
        }

        .dashboard-widget-action:hover {
            transform: translateY(-1px);
            opacity: 0.95;
        }

        .dashboard-widget-action.refresh {
            background: #1a5fd4;
        }

        .dashboard-widget-action.remove {
            background: #ef4444;
        }

        .dashboard-widget-handle {
            position: absolute;
            top: 14px;
            left: 14px;
            z-index: 10;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 999px;
            border: 1px solid #dbe4f0;
            background: rgba(255, 255, 255, 0.96);
            color: #64748b;
            cursor: grab;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .dashboard-widget-shell:hover .dashboard-widget-handle {
            opacity: 1;
        }

        .dashboard-empty {
            border: 1px dashed #bfd0e6;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.8);
            padding: 40px 30px;
            color: #64748b;
            text-align: center;
        }

        .dashboard-drawer {
            position: sticky;
            top: 18px;
            max-height: calc(100vh - 40px);
            overflow: auto;
            border-radius: 24px;
            border: 1px solid #dbe4f0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.14);
            transform: translateX(10px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .dashboard-drawer.is-open {
            transform: translateX(0);
            opacity: 1;
            pointer-events: auto;
        }

        .dashboard-drawer-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .dashboard-drawer-title {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: #111827;
        }

        .dashboard-drawer-copy {
            margin: 6px 0 0;
            font-size: 13px;
            line-height: 1.5;
            color: #64748b;
        }

        .dashboard-drawer-body {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 16px;
        }

        .dashboard-widget-option {
            border-radius: 18px;
            border: 1px solid #dbe4f0;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            padding: 16px;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.06);
            cursor: grab;
            transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
        }

        .dashboard-widget-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.1);
            border-color: #cbd5e1;
        }

        .dashboard-widget-option.is-dragging {
            opacity: 0.45;
            cursor: grabbing;
            transform: scale(0.98);
        }

        .dashboard-drop-indicator {
            grid-column: span 6;
            min-height: 100px;
            border: 2px dashed #1a5fd4;
            border-radius: 20px;
            background: rgba(26, 95, 212, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a5fd4;
            font-size: 14px;
            font-weight: 700;
            pointer-events: none;
            transition: all 0.15s ease;
        }

        .dashboard-widget-shell.is-drop-target {
            outline: 2px dashed #3b82f6;
            outline-offset: 4px;
        }

        .dashboard-widget-option-title {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
            color: #111827;
        }

        .dashboard-widget-option-copy {
            margin: 6px 0 0;
            font-size: 13px;
            line-height: 1.5;
            color: #64748b;
        }

        .dashboard-widget-option-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 12px;
        }

        .dashboard-drawer-empty {
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            background: #f8fafc;
            padding: 16px;
            color: #64748b;
            font-size: 14px;
        }

        .dashboard-widget-shell .fi-wi-chart-filter {
            margin-right: 36px;
        }

        .dashboard-widget-shell .fi-section,
        .dashboard-widget-shell .fi-wi-stats-overview-stat,
        .dashboard-widget-shell .fi-ta,
        .dashboard-widget-shell .fi-wi-chart {
            border-radius: 18px !important;
        }

        @media (max-width: 1279px) {
            .dashboard-layout.is-open {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767px) {
            .dashboard-grid {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }

            .dashboard-toolbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

    <div class="dashboard-shell" data-dashboard-builder data-component-id="{{ $this->getId() }}">
        <div class="dashboard-toolbar">
            <div>
                <p class="dashboard-kicker">Dashboard Builder</p>
                <h1 class="dashboard-title">Arrange Your Classroom Insights</h1>
                <p class="dashboard-copy">Keep the most important widgets at the top, group related analytics together, and give the dashboard a cleaner Google Classroom-inspired rhythm.</p>
            </div>

            <button
                type="button"
                class="dashboard-button"
                onclick="window.dashboardToggleDrawer()"
            >
                Add Widgets
            </button>
        </div>

        <div class="dashboard-layout {{ $this->widgetDrawerOpen ? 'is-open' : '' }}">
            <section
                class="dashboard-canvas"
                data-dashboard-canvas
            >
                @if ($widgets->isNotEmpty())
                    <div class="dashboard-grid">
                        @foreach ($widgets as $widget)
                            <div
                                class="dashboard-widget-shell {{ $this->getWidgetSpanClass($widget->widget_name) }}"
                                style="grid-column: span {{ preg_replace('/[^0-9]/', '', $this->getWidgetSpanClass($widget->widget_name)) }}"
                                data-dashboard-widget
                                data-widget-name="{{ $widget->widget_name }}"
                                draggable="true"
                                ondragstart="window.dashboardWidgetDragStart(event, this)"
                                ondragend="window.dashboardWidgetDragEnd(event, this)"
                            >
                                <div class="dashboard-widget-handle" title="Drag to move">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h.01M8 12h.01M8 18h.01M16 6h.01M16 12h.01M16 18h.01"></path>
                                    </svg>
                                </div>

                                <div class="dashboard-widget-actions">
                                    <button
                                        type="button"
                                        class="dashboard-widget-action refresh"
                                        onclick="window.dashboardWidgetRefresh('{{ $widget->widget_name }}')"
                                        title="Refresh Widget"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        class="dashboard-widget-action remove"
                                        onclick="window.dashboardWidgetRemove('{{ $widget->widget_name }}')"
                                        title="Remove Widget"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>

                                @livewire($widget->widget_class, [], key($widget->id . '-' . $widget->widget_class))
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="dashboard-empty">
                        <p class="mb-2 text-base font-semibold text-slate-700">No widgets are active yet.</p>
                        <p class="mb-0">Open the widget drawer and add one of the widgets from the collection.</p>
                    </div>
                @endif
            </section>

            <aside class="dashboard-drawer {{ $this->widgetDrawerOpen ? 'is-open' : '' }}" data-dashboard-drawer>
                <div class="dashboard-drawer-header">
                    <div>
                        <h2 class="dashboard-drawer-title">Widget Collection</h2>
                        <p class="dashboard-drawer-copy">Drag widgets to the dashboard to place them.</p>
                    </div>

                    <button type="button" class="dashboard-button" onclick="window.dashboardToggleDrawer()">
                        Close
                    </button>
                </div>

                <div class="dashboard-drawer-body">
                    @php
                        $availableWidgets = collect($widgetCatalog)
                            ->reject(fn (array $widget) => in_array($widget['class'], $activeWidgetClasses, true));
                    @endphp

                    @forelse ($availableWidgets as $widgetName => $widget)
                        <div
                            class="dashboard-widget-option"
                            data-widget-name="{{ $widgetName }}"
                            draggable="true"
                            ondragstart="window.dashboardDrawerDragStart(event, this)"
                            ondragend="window.dashboardDrawerDragEnd(event, this)"
                            title="Drag to dashboard"
                        >
                            <h3 class="dashboard-widget-option-title">{{ $widget['label'] }}</h3>
                            <p class="dashboard-widget-option-copy">{{ $widget['description'] }}</p>
                        </div>
                    @empty
                        <div class="dashboard-drawer-empty">
                            All widgets are already on the dashboard.
                        </div>
                    @endforelse
                </div>
            </aside>
        </div>
    </div>

    <script>
        (function () {
            const builder = document.querySelector('[data-dashboard-builder]');
            const drawer = document.querySelector('[data-dashboard-drawer]');
            const canvas = document.querySelector('[data-dashboard-canvas]');
            const componentId = builder?.dataset.componentId || null;
            const dashboardWidgets = () => Array.from(document.querySelectorAll('[data-dashboard-widget]'));
            const drawerOptions = () => Array.from(document.querySelectorAll('.dashboard-widget-option[data-widget-name]'));

            const state = window.dashboardWidgetDragState || {
                draggedElement: null,
                draggedWidget: null,
                isDrawerDrag: false,
            };

            window.dashboardWidgetDragState = state;

            function getDashboardComponent() {
                if (!componentId || !window.Livewire) {
                    return null;
                }

                return window.Livewire.find(componentId);
            }

            async function callDashboard(method, ...params) {
                const component = getDashboardComponent();

                if (!component) {
                    throw new Error('Dashboard component not ready');
                }

                return component.call(method, ...params);
            }

            function getWidgetOrder() {
                if (!canvas) {
                    return [];
                }

                return Array.from(canvas.querySelectorAll('[data-dashboard-widget]'))
                    .map((element) => element.dataset.widgetName)
                    .filter(Boolean);
            }

            function reloadDashboard() {
                window.location.reload();
            }

            function isInsideCanvas(event) {
                if (!canvas) {
                    return false;
                }

                const rect = canvas.getBoundingClientRect();
                return event.clientX >= rect.left
                    && event.clientX <= rect.right
                    && event.clientY >= rect.top
                    && event.clientY <= rect.bottom;
            }

            function getWidgetTargetFromPoint(event) {
                const element = document.elementFromPoint(event.clientX, event.clientY);
                return element?.closest?.('[data-dashboard-widget]') || null;
            }

            function clearDropIndicators() {
                document.querySelectorAll('.dashboard-drop-indicator').forEach((el) => el.remove());
                document.querySelectorAll('.dashboard-widget-shell.is-drop-target').forEach((el) => el.classList.remove('is-drop-target'));
            }

            function showDropIndicator(grid, beforeElement) {
                clearDropIndicators();
                const indicator = document.createElement('div');
                indicator.className = 'dashboard-drop-indicator';
                indicator.textContent = 'Drop here';
                if (beforeElement) {
                    grid.insertBefore(indicator, beforeElement);
                } else {
                    grid.appendChild(indicator);
                }
            }

            window.dashboardToggleDrawer = function () {
                if (!builder || !drawer) {
                    return;
                }

                drawer.classList.toggle('is-open');
                builder.querySelector('.dashboard-layout')?.classList.toggle('is-open');
            };

            window.dashboardWidgetAdd = async function (widgetName, targetWidgetName = null, position = 'after') {
                await callDashboard('placeWidget', widgetName, targetWidgetName, position);

                reloadDashboard();
            };

            window.dashboardWidgetRemove = async function (widgetName) {
                await callDashboard('removeWidget', widgetName);

                reloadDashboard();
            };

            window.dashboardWidgetRefresh = async function (widgetName) {
                await callDashboard('refreshWidget', widgetName);

                reloadDashboard();
            };

            /* Drawer drag: adding new widgets */
            window.dashboardDrawerDragStart = function (event, element) {
                state.isDrawerDrag = true;
                state.draggedWidget = element.dataset.widgetName || null;
                state.draggedElement = null;

                element.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'copyMove';
                event.dataTransfer.setData('text/plain', state.draggedWidget || '');
            };

            window.dashboardDrawerDragEnd = function (event, element) {
                element.classList.remove('is-dragging');
                state.isDrawerDrag = false;
                state.draggedWidget = null;
                clearDropIndicators();
            };

            /* Dashboard drag: reordering existing widgets */
            window.dashboardWidgetDragStart = function (event, element) {
                state.isDrawerDrag = false;
                state.draggedElement = element;
                state.draggedWidget = element.dataset.widgetName || null;

                element.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', state.draggedWidget || '');
            };

            window.dashboardWidgetDragOver = function (event) {
                event.preventDefault();
                event.dataTransfer.dropEffect = state.isDrawerDrag ? 'copy' : 'move';
            };

            window.dashboardWidgetDrop = async function (event, target) {
                event.preventDefault();
                event.stopPropagation();
                clearDropIndicators();

                const widgetName = event.dataTransfer.getData('text/plain') || state.draggedWidget;

                if (!widgetName || !target) {
                    return;
                }

                const targetRect = target.getBoundingClientRect();
                const position = event.clientY < targetRect.top + targetRect.height / 2 ? 'before' : 'after';
                const targetWidgetName = target.dataset.widgetName || null;

                if (state.isDrawerDrag) {
                    /* Adding a new widget from drawer before/after an existing widget */
                    await window.dashboardWidgetAdd(widgetName, targetWidgetName, position);
                    return;
                }

                /* Reordering existing widgets */
                if (target === state.draggedElement) {
                    return;
                }

                const dragged = state.draggedElement;
                if (!dragged || !canvas) {
                    return;
                }

                if (position === 'before') {
                    target.before(dragged);
                } else {
                    target.after(dragged);
                }

                await callDashboard('reorderWidgets', getWidgetOrder());
                reloadDashboard();
            };

            window.dashboardWidgetCanvasDrop = async function (event) {
                event.preventDefault();
                event.stopPropagation();
                clearDropIndicators();

                const widgetName = event.dataTransfer.getData('text/plain') || state.draggedWidget;
                const widgetTarget = getWidgetTargetFromPoint(event);

                if (!widgetName) {
                    return;
                }

                if (widgetTarget) {
                    return;
                }

                if (state.isDrawerDrag) {
                    /* Adding a new widget from drawer to the end of the grid */
                    await window.dashboardWidgetAdd(widgetName);
                    return;
                }

                /* Reordering existing widget to the end */
                const dragged = state.draggedElement;
                const grid = canvas?.querySelector('.dashboard-grid');

                if (!dragged || !grid) {
                    return;
                }

                grid.appendChild(dragged);
                await callDashboard('reorderWidgets', getWidgetOrder());
                reloadDashboard();
            };

            window.dashboardWidgetDragEnd = function (event, element) {
                element.classList.remove('is-dragging');
                state.draggedElement = null;
                state.draggedWidget = null;
                clearDropIndicators();
            };

            /* Canvas-level dragover/drop for both drawer and dashboard drags */
            if (canvas) {
                canvas.addEventListener('dragover', function (event) {
                    if (!isInsideCanvas(event)) {
                        return;
                    }

                    event.preventDefault();
                    event.dataTransfer.dropEffect = state.isDrawerDrag ? 'copy' : 'move';
                });

                canvas.addEventListener('drop', function (event) {
                    if (isInsideCanvas(event)) {
                        window.dashboardWidgetCanvasDrop(event);
                    }
                });

                /* Highlight drop targets when dragging from drawer */
                canvas.addEventListener('dragenter', function (event) {
                    if (!state.isDrawerDrag) return;
                    const target = event.target?.closest?.('[data-dashboard-widget]');
                    if (target) {
                        target.classList.add('is-drop-target');
                    }
                });

                canvas.addEventListener('dragleave', function (event) {
                    const target = event.target?.closest?.('[data-dashboard-widget]');
                    if (target) {
                        target.classList.remove('is-drop-target');
                    }
                });
            }

            dashboardWidgets().forEach((widget) => {
                widget.addEventListener('dragover', window.dashboardWidgetDragOver);
                widget.addEventListener('drop', function (event) {
                    window.dashboardWidgetDrop(event, widget);
                });
            });

        })();
    </script>
</x-filament-panels::page>
