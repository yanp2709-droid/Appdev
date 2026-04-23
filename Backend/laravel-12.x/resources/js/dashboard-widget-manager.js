/**
 * Dashboard Widget Manager
 * Handles widget removal and refresh functionality on the dashboard
 */

class DashboardWidgetManager {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        this.init();
    }

    init() {
        this.attachWidgetActions();
        this.observeNewWidgets();
    }

    attachWidgetActions() {
        // Find all widget headers and add action buttons
        document.querySelectorAll('[data-widget-class]').forEach(widget => {
            this.addWidgetActions(widget);
        });
    }

    addWidgetActions(widgetElement) {
        const widgetClass = widgetElement.getAttribute('data-widget-class');
        const widgetName = widgetElement.getAttribute('data-widget-name');

        // Create action buttons container
        const actionContainer = document.createElement('div');
        actionContainer.className = 'flex gap-2 absolute top-4 right-4 z-10 opacity-0 hover:opacity-100 transition-opacity duration-200 group-hover:opacity-100';

        // Refresh button
        const refreshBtn = this.createButton(
            'Refresh',
            'refresh',
            '#arrowPath',
            () => this.refreshWidget(widgetClass)
        );

        // Delete button
        const deleteBtn = this.createButton(
            'Remove',
            'delete',
            '#xMark',
            () => this.removeWidget(widgetClass, widgetName, widgetElement)
        );

        actionContainer.appendChild(refreshBtn);
        actionContainer.appendChild(deleteBtn);

        // Find the widget header
        const header = widgetElement.querySelector('[role="heading"]') || widgetElement.firstChild;
        if (header) {
            header.style.position = 'relative';
            header.classList.add('group');
            header.appendChild(actionContainer);
        } else {
            widgetElement.appendChild(actionContainer);
        }
    }

    createButton(title, type, iconHref, clickHandler) {
        const button = document.createElement('button');
        button.type = 'button';
        button.title = title;
        button.className = `inline-flex items-center justify-center w-10 h-10 rounded-lg text-white shadow-lg hover:shadow-xl transition-all ${
            type === 'refresh' ? 'bg-primary-500 hover:bg-primary-600' : 'bg-danger-500 hover:bg-danger-600'
        }`;

        // SVG icon
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('class', 'w-5 h-5');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('stroke', 'currentColor');
        svg.setAttribute('viewBox', '0 0 24 24');

        const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
        use.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', iconHref);
        svg.appendChild(use);

        button.appendChild(svg);
        button.addEventListener('click', clickHandler);

        return button;
    }

    async refreshWidget(widgetClass) {
        try {
            const response = await fetch('/filament/dashboard/widget/refresh', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({ widget: widgetClass }),
            });

            if (!response.ok) throw new Error('Failed to refresh widget');

            // Reload the page to reflect changes
            location.reload();
        } catch (error) {
            console.error('Error refreshing widget:', error);
            alert('Error refreshing widget. Please try again.');
        }
    }

    async removeWidget(widgetClass, widgetName, widgetElement) {
        if (!confirm(`Are you sure you want to remove the ${widgetName} widget?`)) {
            return;
        }

        try {
            const response = await fetch('/filament/dashboard/widget/remove', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({ widget: widgetClass }),
            });

            if (!response.ok) throw new Error('Failed to remove widget');

            // Fade out and remove the widget
            widgetElement.style.opacity = '0';
            widgetElement.style.transform = 'scale(0.95)';
            widgetElement.style.transition = 'all 0.3s ease';

            setTimeout(() => {
                location.reload();
            }, 300);
        } catch (error) {
            console.error('Error removing widget:', error);
            alert('Error removing widget. Please try again.');
        }
    }

    observeNewWidgets() {
        // Use MutationObserver to detect when new widgets are added
        const observer = new MutationObserver(() => {
            this.attachWidgetActions();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new DashboardWidgetManager();
});
