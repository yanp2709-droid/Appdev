@php
    $type = $getState();
    $record = $getRecord();
    if (!$record) {
        return;
    }
@endphp

<div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
    <div class="mb-4">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $record->prompt }}
        </p>
    </div>

    @if ($record->type === 'mcq')
        <div class="space-y-2">
            @foreach ($record->options as $option)
                <div class="flex items-center">
                    <input type="radio" disabled 
                        @if($option->is_correct) checked @endif
                        class="h-4 w-4 border-gray-300 text-green-600 dark:border-gray-600">
                    <label class="ml-3 text-sm {{ $option->is_correct ? 'font-semibold text-green-600 dark:text-green-400' : 'text-gray-700 dark:text-gray-300' }}">
                        {{ $option->option_text }}
                        @if($option->is_correct)
                            <span class="ml-2 inline-block rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-200">
                                Correct
                            </span>
                        @endif
                    </label>
                </div>
            @endforeach
        </div>
    @elseif ($record->type === 'tf')
        <div class="space-y-2">
            @foreach ($record->options as $option)
                <div class="flex items-center">
                    <input type="radio" disabled 
                        @if($option->is_correct) checked @endif
                        class="h-4 w-4 border-gray-300 text-green-600 dark:border-gray-600">
                    <label class="ml-3 text-sm {{ $option->is_correct ? 'font-semibold text-green-600 dark:text-green-400' : 'text-gray-700 dark:text-gray-300' }}">
                        {{ $option->option_text }}
                        @if($option->is_correct)
                            <span class="ml-2 inline-block rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-200">
                                Correct
                            </span>
                        @endif
                    </label>
                </div>
            @endforeach
        </div>
    @elseif ($record->type === 'ordering')
        <div class="space-y-2">
            <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">Arrange these in the correct order:</p>
            @foreach ($record->options->sortBy('order_index') as $option)
                <div class="flex items-center rounded-lg bg-white p-2 dark:bg-gray-800">
                    <span class="mr-3 flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ $loop->iteration }}
                    </span>
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $option->option_text }}</span>
                </div>
            @endforeach
        </div>
    @elseif ($record->type === 'short_answer')
        <div class="rounded-lg bg-white p-3 dark:bg-gray-800">
            <p class="mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400">Expected Answer / Rubric:</p>
            <p class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{{ $record->answer_key }}</p>
        </div>
    @endif

    <div class="mt-4 border-t border-gray-200 pt-3 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Points: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $record->points }}</span>
        </p>
    </div>
</div>
