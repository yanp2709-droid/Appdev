<div class="space-y-4">
    @forelse($widgets as $widget)
        <div class="relative group bg-white rounded-lg shadow">
            <!-- Widget Actions Overlay -->
            <div class="absolute top-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-20">
                <!-- Refresh Button -->
                <button
                    type="button"
                    onclick="refreshDashboard()"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-md bg-blue-500 hover:bg-blue-600 text-white shadow-lg transition-all"
                    title="Refresh Widget Data"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>

                <!-- Remove/Delete Button -->
                <button
                    type="button"
                    onclick="removeWidgetFromDashboard('{{ $widget->widget_class }}', '{{ addslashes($widget->widget_name) }}')"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-md bg-red-500 hover:bg-red-600 text-white shadow-lg transition-all"
                    title="Remove Widget"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Widget Content -->
            <livewire:{{ $widget->widget_class }} />
        </div>
    @empty
        <div class="text-center py-8 bg-white rounded-lg shadow">
            <p class="text-gray-500">No widgets added to your dashboard yet.</p>
            <p class="text-sm text-gray-400 mt-2">Use the "Add Widget" button above to get started.</p>
        </div>
    @endforelse
</div>

<script>
    function refreshDashboard() {
        location.reload();
    }

    function removeWidgetFromDashboard(widgetClass, widgetName) {
        if (!confirm(`Are you sure you want to remove the "${widgetName}" widget from your dashboard?`)) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        fetch('/filament/dashboard/widget/remove', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                widget: widgetClass,
            }),
        })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show success notification
                    const notification = document.createElement('div');
                    notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-pulse';
                    notification.textContent = 'Widget removed successfully!';
                    document.body.appendChild(notification);

                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    alert('Error: ' + (data.error || 'Failed to remove widget'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error removing widget. Please try again.');
            });
    }
</script>
