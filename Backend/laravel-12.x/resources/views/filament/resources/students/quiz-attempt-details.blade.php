<div class="space-y-6">
    <!-- Attempt Header -->
    <div class="rounded-lg border border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6">
        <div class="mb-2">
            <h3 class="text-lg font-bold text-gray-900">{{ $attempt->quiz->title ?? 'Untitled Quiz' }}</h3>
            <p class="text-sm text-gray-600">{{ $attempt->quiz->category->name ?? 'Unknown Category' }}</p>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-4">
            <div>
                <span class="text-xs font-semibold uppercase text-gray-500">Score</span>
                <p class="text-2xl font-bold {{ $attempt->score_percent >= 70 ? 'text-green-600' : ($attempt->score_percent >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ is_numeric($attempt->score_percent) ? round($attempt->score_percent, 2) : 'N/A' }}%
                </p>
            </div>
            <div>
                <span class="text-xs font-semibold uppercase text-gray-500">Status</span>
                @php
                    $status = $attempt->status ?? 'unknown';
                    $statusClasses = match ($status) {
                        'submitted' => 'bg-green-100 text-green-800',
                        'in_progress' => 'bg-amber-100 text-amber-800',
                        'expired' => 'bg-red-100 text-red-800',
                        default => 'bg-gray-100 text-gray-700',
                    };
                @endphp
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Timeline and Dates -->
    <div class="rounded-lg border border-gray-200 p-4">
        <h4 class="mb-4 font-semibold text-gray-900">Timeline</h4>
        <div class="space-y-3">
            <div class="flex items-start">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600">1</div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">Started</p>
                    <p class="text-xs text-gray-600">{{ $attempt->started_at?->format('M d, Y \a\t H:i') ?? 'N/A' }}</p>
                </div>
            </div>
            @if($attempt->submitted_at)
                <div class="flex items-start">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 text-sm font-bold text-green-600">2</div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900">Submitted</p>
                        <p class="text-xs text-gray-600">{{ $attempt->submitted_at->format('M d, Y \a\t H:i') }}</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100 text-sm font-bold text-purple-600">⏱</div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900">Duration</p>
                        <p class="text-xs text-gray-600">{{ $attempt->started_at->diffInMinutes($attempt->submitted_at) }} minutes</p>
                    </div>
                </div>
            @endif
            @if($attempt->expires_at)
                <div class="flex items-start">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-100 text-sm font-bold text-orange-600">⏰</div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900">Expires</p>
                        <p class="text-xs text-gray-600">{{ $attempt->expires_at->format('M d, Y \a\t H:i') }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="rounded-lg border border-gray-200 p-4">
        <h4 class="mb-4 font-semibold text-gray-900">Performance Metrics</h4>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <div class="rounded-lg bg-blue-50 p-3 text-center">
                <p class="text-xs text-gray-600">Total Questions</p>
                <p class="mt-1 text-xl font-bold text-gray-900">{{ $attempt->total_items }}</p>
            </div>
            <div class="rounded-lg bg-indigo-50 p-3 text-center">
                <p class="text-xs text-gray-600">Answered</p>
                <p class="mt-1 text-xl font-bold text-gray-900">{{ $attempt->answered_count }}</p>
            </div>
            <div class="rounded-lg bg-green-50 p-3 text-center">
                <p class="text-xs text-gray-600">Correct</p>
                <p class="mt-1 text-xl font-bold text-green-600">{{ $attempt->correct_answers }}</p>
            </div>
            <div class="rounded-lg bg-red-50 p-3 text-center">
                <p class="text-xs text-gray-600">Incorrect</p>
                <p class="mt-1 text-xl font-bold text-red-600">{{ max(0, $attempt->total_items - $attempt->correct_answers) }}</p>
            </div>
        </div>
    </div>
</div>

