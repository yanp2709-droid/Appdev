<div class="h-full">
    <div class="flex h-full min-h-[156px] flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-sm font-medium text-slate-500">{{ $metricTitle }}</div>
                <div class="mt-2 text-4xl font-semibold tracking-tight text-slate-900">{{ $metricValue }}</div>
            </div>

            <div
                class="mt-1 h-10 w-10 rounded-full"
                style="background: {{ $accentSurface }}; color: {{ $accentColor }}"
            ></div>
        </div>

        <div class="mt-4 text-sm font-medium" style="color: {{ $accentColor }}">
            {{ $metricDescription }}
        </div>
    </div>
</div>
