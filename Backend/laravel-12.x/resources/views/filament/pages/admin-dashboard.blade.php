<x-filament-panels::page>
    @php
        $widgets = $this->getDashboardWidgets();
        $widgetCatalog = $this->getWidgetCatalog();
        $activeWidgetClasses = $this->getActiveWidgetClasses();
    @endphp

    <style>
        .dashboard-shell {
            border: 1px solid #e5e7eb;
            border-radius: 24px;
            padding: 20px;
            background: #fff;
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.06);
        }

        .dashboard-toolbar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 16px;
            margin-bottom: 18px;
        }

        .dashboard-title {
            margin: 0;
            font-size: clamp(24px, 3vw, 42px);
            font-weight: 800;
            color: #111827;
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
            background: #f59e0b;
            box-shadow: 0 10px 20px rgba(245, 158, 11, 0.2);
            transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
        }

        .dashboard-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 24px rgba(245, 158, 11, 0.25);
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
            border-radius: 24px;
            background: #fff;
            padding: 4px 0 0;
            position: relative;
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
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
            border: 1px solid #e5e7eb;
            overflow: visible;
        }

        .dashboard-widget-shell.is-dragging {
            opacity: 0.55;
        }

        .dashboard-widget-actions {
            position: absolute;
            top: 10px;
            right: 10px;
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
            width: 22px;
            height: 22px;
            border: 0;
            border-radius: 6px;
            color: #fff;
            box-shadow: 0 6px 12px rgba(15, 23, 42, 0.12);
            transition: transform 0.15s ease, opacity 0.15s ease;
        }

        .dashboard-widget-action svg {
            width: 12px;
            height: 12px;
        }

        .dashboard-widget-action:hover {
            transform: translateY(-1px);
            opacity: 0.95;
        }

        .dashboard-widget-action.refresh {
            background: #3b82f6;
        }

        .dashboard-widget-action.remove {
            background: #ef4444;
        }

        .dashboard-widget-handle {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 10;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: rgba(255, 255, 255, 0.92);
            color: #64748b;
            cursor: grab;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .dashboard-widget-shell:hover .dashboard-widget-handle {
            opacity: 1;
        }

        .dashboard-empty {
            border: 1px dashed #cbd5e1;
            border-radius: 18px;
            background: #fff;
            padding: 30px;
            color: #64748b;
            text-align: center;
        }

        .dashboard-drawer {
            position: sticky;
            top: 18px;
            max-height: calc(100vh - 40px);
            overflow: auto;
            border-radius: 18px;
            border: 1px solid #e5e7eb;
            background: #fff;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.14);
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
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            background: #fff;
            padding: 14px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
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
                        <p class="dashboard-drawer-copy">Click Add Widget to place a widget on the dashboard.</p>
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
                        >
                            <h3 class="dashboard-widget-option-title">{{ $widget['label'] }}</h3>
                            <p class="dashboard-widget-option-copy">{{ $widget['description'] }}</p>

                            <div class="dashboard-widget-option-footer">
                                <button
                                    type="button"
                                    class="dashboard-button"
                                    onclick="window.dashboardWidgetAdd('{{ $widgetName }}')"
                                >
                                    Add Widget
                                </button>
                            </div>
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

            const state = window.dashboardWidgetDragState || {
                draggedElement: null,
                draggedWidget: null,
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

            window.dashboardWidgetDragStart = function (event, element) {
                state.draggedElement = element;
                state.draggedWidget = element.dataset.widgetName || null;

                element.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', state.draggedWidget || '');
            };

            window.dashboardWidgetDragOver = function (event) {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
            };

            window.dashboardWidgetDrop = async function (event, target) {
                event.preventDefault();
                event.stopPropagation();

                const widgetName = event.dataTransfer.getData('text/plain') || state.draggedWidget;

                if (!widgetName || !target || target === state.draggedElement) {
                    return;
                }

                const targetRect = target.getBoundingClientRect();
                const position = event.clientY < targetRect.top + targetRect.height / 2 ? 'before' : 'after';
                const targetWidgetName = target.dataset.widgetName || null;

                if (canvas) {
                    const dragged = state.draggedElement;
                    if (!dragged) {
                        return;
                    }

                    if (position === 'before') {
                        target.before(dragged);
                    } else {
                        target.after(dragged);
                    }

                    await callDashboard('reorderWidgets', getWidgetOrder());

                    reloadDashboard();
                }
            };

            window.dashboardWidgetCanvasDrop = async function (event) {
                event.preventDefault();
                event.stopPropagation();

                const widgetName = event.dataTransfer.getData('text/plain') || state.draggedWidget;
                const widgetTarget = getWidgetTargetFromPoint(event);

                if (!widgetName) {
                    return;
                }

                if (widgetTarget) {
                    return;
                }

                if (canvas) {
                    const dragged = state.draggedElement;
                    const grid = canvas.querySelector('.dashboard-grid');

                    if (!dragged || !grid) {
                        return;
                    }

                    grid.appendChild(dragged);

                    await callDashboard('reorderWidgets', getWidgetOrder());

                    reloadDashboard();
                }
            };

            window.dashboardWidgetDragEnd = function (event, element) {
                element.classList.remove('is-dragging');
                state.draggedElement = null;
                state.draggedWidget = null;
            };

            if (canvas) {
                canvas.addEventListener('dragover', function (event) {
                    if (!isInsideCanvas(event)) {
                        return;
                    }

                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                });

                canvas.addEventListener('drop', function (event) {
                    if (isInsideCanvas(event)) {
                        window.dashboardWidgetCanvasDrop(event);
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
