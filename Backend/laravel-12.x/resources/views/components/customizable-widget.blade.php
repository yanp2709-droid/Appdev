<div class="relative group">
    <!-- Floating Action Buttons -->
    <div class="absolute top-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-10">
        <!-- Refresh Button -->
        <button
            type="button"
            onclick="location.reload()"
            class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-primary-500 hover:bg-primary-600 text-white shadow-lg hover:shadow-xl transition-all"
            title="Refresh Widget"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
        </button>

        <!-- Delete Button -->
        <button
            type="button"
            onclick="if(confirm('Are you sure you want to remove this widget?')) { removeWidget('{{ $widgetClass }}'); }"
            class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-danger-500 hover:bg-danger-600 text-white shadow-lg hover:shadow-xl transition-all"
            title="Remove Widget"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- Widget Content -->
    <div class="p-4">
        {{ $slot }}
    </div>
</div>

<script>
    function removeWidget(widgetClass) {
        fetch('{{ route("filament.dashboard.widget.remove") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                widget: widgetClass
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to remove widget'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error removing widget');
        });
    }
</script>
