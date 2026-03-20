<div class="space-y-4">
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="text-sm text-gray-500">Student</div>
        <div class="text-base font-semibold text-gray-900">{{ $student->name ?: trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) }}</div>
        <div class="text-sm text-gray-600">{{ $student->email }}</div>
        <div class="text-xs text-gray-500">Student ID: {{ $student->student_id ?? 'N/A' }}</div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                <tr>
                    <th class="px-4 py-3">Attempt #</th>
                    <th class="px-4 py-3">Quiz</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Score</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($attempts as $index => $attempt)
                    <tr>
                        <td class="px-4 py-3 text-gray-700">{{ $index + 1 }}</td>
                        <td class="px-4 py-3 text-gray-900">{{ $attempt->quiz->title ?? 'Untitled Quiz' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $attempt->quiz->category->name ?? 'Unknown' }}</td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ optional($attempt->submitted_at ?? $attempt->started_at)->format('M d, Y H:i') ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $attempt->score_percent !== null ? number_format($attempt->score_percent, 1) . '%' : 'N/A' }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $status = $attempt->status ?? 'unknown';
                                $statusLabel = ucfirst(str_replace('_', ' ', $status));
                                $statusClasses = match ($status) {
                                    'submitted' => 'bg-green-100 text-green-800',
                                    'in_progress' => 'bg-amber-100 text-amber-800',
                                    'expired' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClasses }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-center text-gray-500" colspan="6">No quiz attempts found for this student.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
