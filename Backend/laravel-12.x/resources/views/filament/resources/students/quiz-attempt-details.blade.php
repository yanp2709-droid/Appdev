@php
    $answers = $attempt->answers->sortBy('question_id')->values();
@endphp

<div class="space-y-4">
    <div class="rounded-lg border border-gray-200 p-4">
        <p class="text-sm text-gray-600">Category</p>
        <p class="text-base font-semibold text-gray-900">{{ $attempt->quiz->category->name ?? 'Unknown Category' }}</p>
        <p class="mt-3 text-sm text-gray-600">Score</p>
        <p class="text-xl font-bold text-gray-900">{{ is_numeric($attempt->score_percent) ? round($attempt->score_percent, 2) : 'N/A' }}%</p>
    </div>

    <div class="rounded-lg border border-gray-200 p-4">
        <h4 class="mb-3 text-sm font-semibold text-gray-900">Question Item Numbers and Answers</h4>

        @if($answers->isEmpty())
            <p class="text-sm text-gray-600">No answers submitted.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-gray-600">
                            <th class="px-2 py-2 font-medium">Item #</th>
                            <th class="px-2 py-2 font-medium">Question ID</th>
                            <th class="px-2 py-2 font-medium">Student Answer</th>
                            <th class="px-2 py-2 font-medium">Correct Answer</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($answers as $index => $answer)
                            @php
                                $studentAnswer = $answer->questionOption?->option_text
                                    ?? $answer->answer?->answer_text
                                    ?? $answer->text_answer
                                    ?? 'No answer';

                                $correctOptionTexts = $answer->question?->options
                                    ?->where('is_correct', true)
                                    ->pluck('option_text')
                                    ->filter()
                                    ->values();

                                $correctAnswer = ($correctOptionTexts && $correctOptionTexts->isNotEmpty())
                                    ? $correctOptionTexts->implode(', ')
                                    : ($answer->question?->answer_key ?? 'N/A');
                            @endphp
                            <tr class="border-b border-gray-100">
                                <td class="px-2 py-2">{{ $index + 1 }}</td>
                                <td class="px-2 py-2">{{ $answer->question_id }}</td>
                                <td class="px-2 py-2">{{ $studentAnswer }}</td>
                                <td class="px-2 py-2">{{ $correctAnswer }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
