<x-filament-panels::page>
    @php
        $quizzes = $this->getQuizzes();
    @endphp

    <style>
        .quizzes-shell {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .quizzes-hero {
            border: 1px solid #dbe2ea;
            border-radius: 24px;
            padding: 24px;
            background: linear-gradient(135deg, #ecfeff 0%, #ffffff 50%, #eff6ff 100%);
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
        }

        .quizzes-hero-title {
            margin: 0;
            color: #0f172a;
            font-size: 28px;
            font-weight: 800;
        }

        .quizzes-hero-copy {
            margin: 8px 0 0;
            color: #475569;
            font-size: 15px;
        }

        .quizzes-table-wrap {
            overflow-x: auto;
            border: 1px solid #dbe2ea;
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.06);
        }

        .quizzes-table {
            width: 100%;
            border-collapse: collapse;
        }

        .quizzes-table th,
        .quizzes-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .quizzes-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .quizzes-empty {
            padding: 28px;
            text-align: center;
            color: #64748b;
        }

        .quizzes-pill {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .quizzes-status-active,
        .quizzes-status-disabled {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .quizzes-status-active {
            background: #ecfdf5;
            color: #047857;
        }

        .quizzes-status-disabled {
            background: #fff1f2;
            color: #be123c;
        }

        .quizzes-action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .quizzes-open-link,
        .quizzes-edit-link,
        .quizzes-disable-btn,
        .quizzes-enable-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            line-height: 1;
            min-height: 32px;
        }

        .quizzes-open-link {
            background: #111827;
            color: #fff;
        }

        .quizzes-open-link:hover {
            background: #f59e0b;
            color: #111827;
        }

        .quizzes-edit-link {
            background: #fff;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .quizzes-edit-link:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            color: #111827;
        }

        .quizzes-disable-btn {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
        }

        .quizzes-disable-btn:hover {
            background: #ffe4e6;
            border-color: #e11d48;
            color: #9f1239;
        }

        .quizzes-enable-btn {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
        }

        .quizzes-enable-btn:hover {
            background: #d1fae5;
            border-color: #34d399;
            color: #065f46;
        }
    </style>

    <div class="quizzes-shell">
        <div class="quizzes-hero">
            <h1 class="quizzes-hero-title">{{ $this->getRecord()->name }}</h1>
            <p class="quizzes-hero-copy">
                Create one or more quizzes under this subject, then open each quiz to manage its questions.
            </p>
        </div>

        <div class="quizzes-table-wrap">
            <table class="quizzes-table">
                <thead>
                    <tr>
                        <th>Quiz</th>
                        <th>Questions</th>
                        <th>Difficulty</th>
                        <th>Duration</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quizzes as $quiz)
                        <tr>
                            <td>{{ $quiz->title }}</td>
                            <td>
                                <span class="quizzes-pill">{{ $quiz->questions_count }}</span>
                            </td>
                            <td>{{ $quiz->difficulty }}</td>
                            <td>{{ $quiz->duration_minutes }} mins</td>
                            <td>{{ optional($quiz->created_at)->format('M d, Y') }}</td>
                            <td>
                                <div class="quizzes-action-group">
                                    <a
                                        href="{{ $this->getQuizQuestionsUrl($quiz) }}"
                                        class="quizzes-open-link"
                                    >
                                        Questions
                                    </a>

                                    <a
                                        href="{{ \App\Filament\Resources\Quizzes\QuizResource::getUrl('edit', ['record' => $quiz]) }}"
                                        class="quizzes-edit-link"
                                    >
                                        <span style="display:inline-flex;align-items:center;gap:6px;">
                                            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true" width="14" height="14">
                                                <path d="M13.5 3.5l3 3L7 16H4v-3L13.5 3.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                            </svg>
                                            Edit
                                        </span>
                                    </a>

                                    @if ($quiz->is_active)
                                        <button
                                            type="button"
                                            class="quizzes-disable-btn"
                                            wire:click="disableQuiz({{ $quiz->id }})"
                                        >
                                            Disable
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="quizzes-empty">
                                No quizzes have been created for this subject yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
