<x-filament-panels::page>
    @php
        $quizCards = collect($this->getQuizCards());
        $overall = $this->getOverallQuizSummary();
        $maxAttempts = max(1, (int) $quizCards->max('total_attempts'));
        $topQuiz = $quizCards->sortByDesc('total_attempts')->first();
        $leastQuiz = $quizCards->sortBy('total_attempts')->first();
    @endphp

    <style>
        .quiz-analytics-shell {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .quiz-hero {
            border: 1px solid #d7dde5;
            border-radius: 24px;
            padding: 28px;
            background:
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.18), transparent 36%),
                linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.06);
        }

        .quiz-hero-kicker {
            margin: 0 0 8px;
            color: #b45309;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 12px;
            font-weight: 800;
        }

        .quiz-hero-title {
            margin: 0;
            font-size: clamp(26px, 3vw, 38px);
            font-weight: 800;
            color: #0f172a;
        }

        .quiz-hero-copy {
            margin: 10px 0 0;
            max-width: 850px;
            color: #475569;
            font-size: 15px;
            line-height: 1.6;
        }

        .quiz-metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 16px;
        }

        .quiz-metric {
            border-radius: 20px;
            border: 1px solid #e5e7eb;
            background: #fff;
            padding: 18px;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
        }

        .quiz-metric-label {
            display: block;
            margin-bottom: 10px;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .quiz-metric-value {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.05;
        }

        .quiz-metric-note {
            margin-top: 8px;
            color: #64748b;
            font-size: 13px;
        }

        .quiz-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) minmax(320px, 0.9fr);
            gap: 20px;
        }

        .quiz-panel {
            border: 1px solid #d7dde5;
            border-radius: 22px;
            background: #fff;
            padding: 22px;
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.06);
        }

        .quiz-panel-title {
            margin: 0 0 4px;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
        }

        .quiz-panel-subtitle {
            margin: 0 0 18px;
            color: #64748b;
            font-size: 14px;
        }

        .quiz-bar-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .quiz-bar-row {
            display: grid;
            grid-template-columns: 170px minmax(0, 1fr) 78px;
            gap: 12px;
            align-items: center;
        }

        .quiz-bar-name {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .quiz-bar-track {
            height: 18px;
            border-radius: 999px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .quiz-bar-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #f59e0b 0%, #ea580c 100%);
        }

        .quiz-bar-value {
            font-size: 13px;
            font-weight: 800;
            color: #334155;
            text-align: right;
        }

        .quiz-split {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .quiz-summary-card {
            border-radius: 18px;
            padding: 16px;
            border: 1px solid transparent;
        }

        .quiz-summary-card.high {
            background: #ecfdf5;
            border-color: #bbf7d0;
        }

        .quiz-summary-card.low {
            background: #fff1f2;
            border-color: #fecdd3;
        }

        .quiz-summary-card.users {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .quiz-summary-card.attempts {
            background: #fffbeb;
            border-color: #fde68a;
        }

        .quiz-summary-label {
            display: block;
            margin-bottom: 8px;
            color: #475569;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .quiz-summary-value {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.05;
        }

        .quiz-table-wrap {
            overflow-x: auto;
        }

        .quiz-table {
            width: 100%;
            border-collapse: collapse;
        }

        .quiz-table th,
        .quiz-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            font-size: 14px;
            vertical-align: top;
        }

        .quiz-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 800;
        }

        .quiz-table td {
            color: #0f172a;
        }

        .quiz-table-empty {
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 1024px) {
            .quiz-layout {
                grid-template-columns: 1fr;
            }

            .quiz-bar-row {
                grid-template-columns: 1fr;
            }

            .quiz-bar-value {
                text-align: left;
            }
        }
    </style>

    <div class="quiz-analytics-shell">
        <div class="quiz-hero">
            <p class="quiz-hero-kicker">Overall Analytics</p>
            <h1 class="quiz-hero-title">Quiz Analytics Dashboard</h1>
            <p class="quiz-hero-copy">
                Track each quiz's attempt volume in one place. This page highlights the most attempted quiz,
                the least attempted quiz, and the total number of students behind the data.
            </p>
        </div>

        <div class="quiz-metric-grid">
            <div class="quiz-metric">
                <span class="quiz-metric-label">Total Quizzes</span>
                <div class="quiz-metric-value">{{ \App\Models\Category::count() }}</div>
                <div class="quiz-metric-note">Quiz topics created in the system</div>
            </div>

            <div class="quiz-metric">
                <span class="quiz-metric-label">Total Attempts</span>
                <div class="quiz-metric-value">{{ number_format((int) ($overall['total_attempts'] ?? 0)) }}</div>
                <div class="quiz-metric-note">Submitted, in progress, and expired attempts</div>
            </div>

            <div class="quiz-metric">
                <span class="quiz-metric-label">Total Students</span>
                <div class="quiz-metric-value">{{ number_format((int) ($overall['total_students'] ?? 0)) }}</div>
                <div class="quiz-metric-note">Students registered in the platform</div>
            </div>
        </div>

        <div class="quiz-layout">
            <div class="quiz-panel">
                <h2 class="quiz-panel-title">Attempts by Quiz</h2>
                <p class="quiz-panel-subtitle">The bars below show how many attempts each quiz received.</p>

                <div class="quiz-bar-list">
                    @forelse ($quizCards as $card)
                        @php
                            $barWidth = max(8, (($card['total_attempts'] ?? 0) / $maxAttempts) * 100);
                        @endphp

                        <div class="quiz-bar-row">
                            <div class="quiz-bar-name">{{ $card['category_name'] }}</div>
                            <div class="quiz-bar-track">
                                <div class="quiz-bar-fill" style="width: {{ $barWidth }}%;"></div>
                            </div>
                            <div class="quiz-bar-value">{{ number_format((int) ($card['total_attempts'] ?? 0)) }}</div>
                        </div>
                    @empty
                        <p class="quiz-panel-subtitle">No quiz attempts found yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="quiz-panel">
                <h2 class="quiz-panel-title">Quick Highlights</h2>
                <p class="quiz-panel-subtitle">A few at-a-glance widgets from the overall quiz data.</p>

                <div class="quiz-split">
                    <div class="quiz-summary-card users">
                        <span class="quiz-summary-label">Most Attempted Quiz</span>
                        <div class="quiz-summary-value">
                            {{ $topQuiz['category_name'] ?? 'N/A' }}
                        </div>
                        <div class="quiz-metric-note">
                            {{ number_format((int) ($topQuiz['total_attempts'] ?? 0)) }} total attempts
                        </div>
                    </div>

                    <div class="quiz-summary-card attempts">
                        <span class="quiz-summary-label">Less Attempted Quiz</span>
                        <div class="quiz-summary-value">
                            {{ $leastQuiz['category_name'] ?? 'N/A' }}
                        </div>
                        <div class="quiz-metric-note">
                            {{ number_format((int) ($leastQuiz['total_attempts'] ?? 0)) }} total attempts
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="quiz-panel">
            <h2 class="quiz-panel-title">Quiz Score Table</h2>
            <p class="quiz-panel-subtitle">
                This table groups the key analytics for each quiz: attempts, users who tried it, and the best and worst scores.
            </p>

            <div class="quiz-table-wrap">
                <table class="quiz-table">
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Attempts</th>
                            <th>Users Attempted</th>
                            <th>Highest Score</th>
                            <th>Lowest Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($quizCards as $card)
                            <tr>
                                <td><strong>{{ $card['category_name'] }}</strong></td>
                                <td>{{ number_format((int) ($card['total_attempts'] ?? 0)) }}</td>
                                <td>{{ number_format((int) ($card['attempted_users'] ?? 0)) }}</td>
                                <td>{{ is_null($card['highest_score'] ?? null) ? 'None' : round((float) $card['highest_score']) }}</td>
                                <td>{{ is_null($card['lowest_score'] ?? null) ? 'None' : round((float) $card['lowest_score']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="quiz-table-empty" colspan="5">No quiz attempt data is available yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>
