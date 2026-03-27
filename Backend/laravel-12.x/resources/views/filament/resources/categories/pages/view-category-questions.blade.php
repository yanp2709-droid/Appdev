<x-filament-panels::page>
    @php
        $questions = $this->getQuestions();
    @endphp

    <style>
        .questions-shell {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .questions-hero {
            border: 1px solid #dbe2ea;
            border-radius: 24px;
            padding: 24px;
            background: linear-gradient(135deg, #fff7ed 0%, #ffffff 45%, #eff6ff 100%);
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
        }

        .questions-hero-title {
            margin: 0;
            color: #0f172a;
            font-size: 28px;
            font-weight: 800;
        }

        .questions-hero-copy {
            margin: 8px 0 0;
            color: #475569;
            font-size: 15px;
        }

        .questions-table-wrap {
            overflow-x: auto;
            border: 1px solid #dbe2ea;
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.06);
        }

        .questions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .questions-table th,
        .questions-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .questions-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .questions-table td {
            color: #0f172a;
            font-size: 14px;
        }

        .questions-check-col {
            width: 52px;
            text-align: center;
        }

        .questions-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #111827;
            cursor: pointer;
        }

        .questions-pill {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .questions-empty {
            padding: 28px;
            text-align: center;
            color: #64748b;
        }

        .questions-row-actions {
            width: 120px;
        }

        .questions-edit-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 999px;
            background: #111827;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }

        .questions-edit-link:hover {
            background: #f59e0b;
            color: #111827;
        }

        .questions-action-head {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .questions-delete-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 999px;
            background: #dc2626;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            line-height: 1;
            min-height: 32px;
        }

        .questions-delete-link:hover {
            background: #b91c1c;
        }
    </style>

    <div class="questions-shell">
        <div class="questions-hero">
            <h1 class="questions-hero-title">{{ $this->getRecord()->name }}</h1>
            <p class="questions-hero-copy">
                Here is the list of questions under this category. It follows the Question menu format, without showing the Category column.
            </p>
        </div>

        <div class="questions-table-wrap">
            <table class="questions-table">
                <thead>
                    <tr>
                        <th class="questions-check-col"></th>
                        <th>Question</th>
                        <th>Type</th>
                        <th>Points</th>
                        <th>Created</th>
                        <th class="questions-row-actions">
                            <div class="questions-action-head">
                                <span>Action</span>
                                @if (count($this->selectedQuestionIds) > 0)
                                    <button
                                        type="button"
                                        wire:click="deleteSelectedQuestions"
                                        class="questions-delete-link"
                                    >
                                        Delete
                                    </button>
                                @endif
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($questions as $question)
                        <tr>
                            <td class="questions-check-col">
                                <input
                                    type="checkbox"
                                    value="{{ $question->id }}"
                                    wire:model.live="selectedQuestionIds"
                                    class="questions-checkbox"
                                >
                            </td>
                            <td>{{ $question->question_text }}</td>
                            <td>
                                <span class="questions-pill">
                                    {{ match ($question->question_type) {
                                        'mcq' => 'MCQ',
                                        'tf' => 'True / False',
                                        'ordering' => 'Ordering',
                                        'short_answer' => 'Short Answer',
                                        default => ucfirst((string) $question->question_type),
                                    } }}
                                </span>
                            </td>
                            <td>{{ $question->points }}</td>
                            <td>{{ optional($question->created_at)->format('M d, Y') }}</td>
                            <td class="questions-row-actions">
                                <a
                                    href="{{ \App\Filament\Resources\Questions\QuestionResource::getUrl('edit', ['record' => $question]) }}"
                                    class="questions-edit-link"
                                >
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="questions-empty">
                                This category does not have any questions yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
