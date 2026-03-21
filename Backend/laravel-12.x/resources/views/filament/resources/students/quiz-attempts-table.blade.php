@php
    $attempts = $record->quizAttempts()
        ->with(['quiz.category', 'answers.questionOption', 'answers.answer'])
        ->orderByDesc('id')
        ->get();
@endphp

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
    <div class="border-b border-gray-200 bg-white px-6 py-4">
        <div class="text-sm font-medium text-gray-900">Attempt Records</div>
    </div>

    <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left">
            <tr>
                <th class="border-b border-gray-200 px-6 py-4 font-semibold text-gray-900">Category</th>
                <th class="border-b border-gray-200 px-6 py-4 font-semibold text-gray-900">Answers</th>
                <th class="border-b border-gray-200 px-6 py-4 font-semibold text-gray-900">Attempt #</th>
                <th class="border-b border-gray-200 px-6 py-4 font-semibold text-gray-900">Score</th>
                <th class="border-b border-gray-200 px-6 py-4 font-semibold text-gray-900">Answered Items</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($attempts as $attempt)
                @php
                    $attemptNumber = $record->quizAttempts()
                        ->whereHas('quiz', fn ($query) => $query->where('category_id', $attempt->quiz?->category_id))
                        ->where('id', '<=', $attempt->id)
                        ->count();

                    $answers = $attempt->answers
                        ->sortBy('question_id')
                        ->values()
                        ->map(function ($answer, $index) {
                            $value = $answer->questionOption?->option_text
                                ?? $answer->answer?->answer_text
                                ?? $answer->text_answer
                                ?? 'No answer';

                            return 'Q' . ($index + 1) . ': ' . $value;
                        })
                        ->implode(', ');

                    $score = $attempt->score ?? 0;
                    $totalItems = $attempt->total_items ?? 0;
                    $scorePercent = is_numeric($attempt->score_percent) ? number_format((float) $attempt->score_percent, 2) . '%' : 'N/A';
                @endphp

                <tr class="align-top">
                    <td class="border-b border-gray-200 px-6 py-4 font-medium text-gray-900">
                        {{ $attempt->quiz->category->name ?? 'Unknown Category' }}
                    </td>
                    <td class="border-b border-gray-200 px-6 py-4 text-gray-700">
                        @if (blank($answers))
                            <span class="text-gray-500">No answers submitted.</span>
                        @else
                            <div class="max-w-xl whitespace-normal break-words">
                                {{ $answers }}
                            </div>
                        @endif
                    </td>
                    <td class="border-b border-gray-200 px-6 py-4 text-gray-700">
                        {{ $attemptNumber }}
                    </td>
                    <td class="border-b border-gray-200 px-6 py-4 text-gray-700">
                        @if ($totalItems > 0)
                            {{ $score }}/{{ $totalItems }} ({{ $scorePercent }})
                        @else
                            {{ $scorePercent }}
                        @endif
                    </td>
                    <td class="border-b border-gray-200 px-6 py-4 text-gray-700">
                        {{ $attempt->answered_count ?? 0 }}/{{ $attempt->total_items ?? 0 }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-6 py-6 text-center text-gray-500" colspan="5">
                        No quiz attempts found for this student.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
