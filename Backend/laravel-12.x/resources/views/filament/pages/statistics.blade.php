<x-filament-panels::page>
    @php
        $cards = $this->getCategoryCards();
        $detail = $this->getSelectedCategoryDetail();
        $summary = $detail['summary'] ?? null;
        $users = collect($detail['users'] ?? []);
        $highestScorer = $detail['highest_scorer'] ?? null;
        $lowestScorer = $detail['lowest_scorer'] ?? null;
        $maxScore = max(1, (float) $users->max('best_score'));
    @endphp

    <div class="stats-filters mb-6">
        <form wire:submit.prevent="updateFilters" class="flex gap-4 items-end">
            <div>
                <label for="dateFrom" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" id="dateFrom" wire:model.live="dateFrom"
                       class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="dateTo" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" id="dateTo" wire:model.live="dateTo"
                       class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="button" wire:click="resetFilters"
                    class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                Reset
            </button>
        </form>
    </div>

    <style>
        .stats-shell {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .stats-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
        }

        .stats-category-card {
            border: 1px solid #d7dde5;
            border-radius: 18px;
            background: linear-gradient(180deg, #ffffff 0%, #f7fafc 100%);
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
            padding: 18px;
            text-align: left;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            cursor: pointer;
        }

        .stats-category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.10);
            border-color: #f59e0b;
        }

        .stats-category-card.is-active {
            border-color: #d97706;
            background: linear-gradient(180deg, #fff8eb 0%, #fff2d6 100%);
            box-shadow: 0 14px 32px rgba(217, 119, 6, 0.16);
        }

        .stats-card-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }

        .stats-chip {
            background: #111827;
            color: #fff;
            font-size: 12px;
            padding: 6px 10px;
            border-radius: 999px;
            white-space: nowrap;
        }

        .stats-category-title {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
        }

        .stats-category-subtitle {
            margin: 6px 0 0;
            color: #475569;
            font-size: 14px;
        }

        .stats-metric-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .stats-mini-card {
            border-radius: 14px;
            padding: 12px;
            background: #fff;
            border: 1px solid #e5e7eb;
        }

        .stats-mini-label {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .stats-mini-value {
            color: #0f172a;
            font-size: 22px;
            font-weight: 700;
            line-height: 1.1;
        }

        .stats-detail-shell {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .stats-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
        }

        .stats-summary-card {
            border-radius: 16px;
            padding: 16px;
            border: 1px solid transparent;
        }

        .stats-summary-card.high { background: #ecfdf5; border-color: #a7f3d0; }
        .stats-summary-card.low { background: #fff1f2; border-color: #fecdd3; }
        .stats-summary-card.complete { background: #eff6ff; border-color: #bfdbfe; }
        .stats-summary-card.progress { background: #fffbeb; border-color: #fde68a; }
        .stats-summary-card.expired { background: #f3f4f6; border-color: #d1d5db; }

        .stats-layout {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(300px, 1fr);
            gap: 20px;
        }

        .stats-panel {
            border: 1px solid #d7dde5;
            border-radius: 20px;
            background: #fff;
            padding: 20px;
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.06);
        }

        .stats-panel-title {
            margin: 0 0 4px;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .stats-panel-subtitle {
            margin: 0 0 18px;
            color: #64748b;
            font-size: 14px;
        }

        .stats-chart {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .stats-bar-row {
            display: grid;
            grid-template-columns: 150px minmax(0, 1fr) 72px;
            gap: 12px;
            align-items: center;
        }

        .stats-bar-name {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .stats-bar-track {
            height: 18px;
            border-radius: 999px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .stats-bar-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #f59e0b 0%, #ea580c 100%);
        }

        .stats-bar-score {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            text-align: right;
        }

        .stats-scorer-stack {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .stats-scorer-card {
            border-radius: 18px;
            padding: 18px;
            border: 1px solid transparent;
        }

        .stats-scorer-card.high { background: #ecfdf5; border-color: #86efac; }
        .stats-scorer-card.low { background: #fff1f2; border-color: #fda4af; }

        .stats-scorer-label {
            margin: 0 0 8px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .stats-scorer-name {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }

        .stats-scorer-email {
            margin: 6px 0 12px;
            color: #475569;
            font-size: 14px;
        }

        .stats-scorer-value {
            font-size: 34px;
            font-weight: 800;
            line-height: 1;
        }

        .stats-table-wrap {
            overflow-x: auto;
        }

        .stats-table {
            width: 100%;
            border-collapse: collapse;
        }

        .stats-table th,
        .stats-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            font-size: 14px;
        }

        .stats-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
        }

        .stats-table td {
            color: #0f172a;
        }

        @media (max-width: 1024px) {
            .stats-layout {
                grid-template-columns: 1fr;
            }

            .stats-bar-row {
                grid-template-columns: 1fr;
            }

            .stats-bar-score {
                text-align: left;
            }
        }
    </style>

    <div class="stats-shell">
        <div class="stats-card-grid">
            @forelse ($cards as $card)
                <button
                    type="button"
                    wire:click="selectCategory({{ $card['category_id'] }})"
                    class="stats-category-card {{ $this->selectedCategoryId === $card['category_id'] ? 'is-active' : '' }}"
                >
                    <div class="stats-card-head">
                        <div>
                            <p class="stats-category-title">{{ $card['category_name'] }}</p>
                            <p class="stats-category-subtitle">
                                {{ $card['attempted_users'] }} users attempted this category
                            </p>
                        </div>

                        <span class="stats-chip">
                            {{ $card['total_attempts'] }} attempts
                        </span>
                    </div>

                    <div class="stats-metric-grid">
                        <div class="stats-mini-card">
                            <span class="stats-mini-label">High Score</span>
                            <span class="stats-mini-value">{{ number_format($card['highest_score'], 2) }}%</span>
                        </div>
                        <div class="stats-mini-card">
                            <span class="stats-mini-label">Lowest Score</span>
                            <span class="stats-mini-value">{{ number_format($card['lowest_score'], 2) }}%</span>
                        </div>
                        <div class="stats-mini-card">
                            <span class="stats-mini-label">Completion</span>
                            <span class="stats-mini-value">{{ number_format($card['completion_rate'], 2) }}%</span>
                        </div>
                        <div class="stats-mini-card">
                            <span class="stats-mini-label">In Progress / Expired</span>
                            <span class="stats-mini-value">{{ $card['in_progress_attempts'] }} / {{ $card['expired_attempts'] }}</span>
                        </div>
                    </div>
                </button>
            @empty
                <div class="stats-panel">
                    No category attempts found yet.
                </div>
            @endforelse
        </div>

        @if ($summary)
            <x-filament::section>
                <x-slot name="heading">
                    {{ $summary['category_name'] }} Details
                </x-slot>

                <x-slot name="description">
                    Category performance chart, highest and lowest scorers, and the users who attempted this category.
                </x-slot>

                <div class="stats-detail-shell">
                    <div class="stats-summary-grid">
                        <div class="stats-summary-card high">
                            <span class="stats-mini-label">High Score</span>
                            <div class="stats-mini-value">{{ number_format($summary['highest_score'], 2) }}%</div>
                        </div>
                        <div class="stats-summary-card low">
                            <span class="stats-mini-label">Lowest Score</span>
                            <div class="stats-mini-value">{{ number_format($summary['lowest_score'], 2) }}%</div>
                        </div>
                        <div class="stats-summary-card complete">
                            <span class="stats-mini-label">Completion Rate</span>
                            <div class="stats-mini-value">{{ number_format($summary['completion_rate'], 2) }}%</div>
                        </div>
                        <div class="stats-summary-card progress">
                            <span class="stats-mini-label">In Progress</span>
                            <div class="stats-mini-value">{{ $summary['in_progress_attempts'] }}</div>
                        </div>
                        <div class="stats-summary-card expired">
                            <span class="stats-mini-label">Expired Attempts</span>
                            <div class="stats-mini-value">{{ $summary['expired_attempts'] }}</div>
                        </div>
                    </div>

                    <div class="stats-layout">
                        <div class="stats-panel">
                            <div>
                                <div>
                                    <h3 class="stats-panel-title">User Score Chart</h3>
                                    <p class="stats-panel-subtitle">Best submitted score for each user in this category.</p>
                                </div>
                            </div>

                            <div class="stats-chart">
                                @forelse ($users as $user)
                                    @php
                                        $width = $user['best_score'] > 0 ? max(8, ($user['best_score'] / $maxScore) * 100) : 8;
                                    @endphp

                                    <div class="stats-bar-row">
                                        <div class="stats-bar-name">{{ $user['student_name'] }}</div>
                                        <div class="stats-bar-track">
                                            <div
                                                class="stats-bar-fill"
                                                style="width: {{ $width }}%;"
                                            ></div>
                                        </div>
                                        <div class="stats-bar-score">{{ number_format($user['best_score'], 2) }}%</div>
                                    </div>
                                @empty
                                    <p class="stats-panel-subtitle">No users attempted this category yet.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="stats-scorer-stack">
                            <div class="stats-scorer-card high">
                                <p class="stats-scorer-label">Highest Scorer</p>
                                @if ($highestScorer)
                                    <p class="stats-scorer-name">{{ $highestScorer['student_name'] }}</p>
                                    <p class="stats-scorer-email">{{ $highestScorer['student_email'] }}</p>
                                    <div class="stats-scorer-value">{{ number_format($highestScorer['best_score'], 2) }}%</div>
                                @else
                                    <p class="stats-panel-subtitle">No submitted score yet.</p>
                                @endif
                            </div>

                            <div class="stats-scorer-card low">
                                <p class="stats-scorer-label">Lowest Scorer</p>
                                @if ($lowestScorer)
                                    <p class="stats-scorer-name">{{ $lowestScorer['student_name'] }}</p>
                                    <p class="stats-scorer-email">{{ $lowestScorer['student_email'] }}</p>
                                    <div class="stats-scorer-value">{{ number_format($lowestScorer['lowest_score'], 2) }}%</div>
                                @else
                                    <p class="stats-panel-subtitle">No submitted score yet.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="stats-panel">
                        <div style="margin-bottom: 16px;">
                            <h3 class="stats-panel-title">Users Who Attempted This Category</h3>
                        </div>

                        <div class="stats-table-wrap">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Email</th>
                                        <th>Attempts</th>
                                        <th>Submitted</th>
                                        <th>In Progress</th>
                                        <th>Expired</th>
                                        <th>Best Score</th>
                                        <th>Lowest Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($users as $user)
                                        <tr>
                                            <td><strong>{{ $user['student_name'] }}</strong></td>
                                            <td>{{ $user['student_email'] }}</td>
                                            <td>{{ $user['total_attempts'] }}</td>
                                            <td>{{ $user['submitted_attempts'] }}</td>
                                            <td>{{ $user['in_progress_attempts'] }}</td>
                                            <td>{{ $user['expired_attempts'] }}</td>
                                            <td>{{ number_format($user['best_score'], 2) }}%</td>
                                            <td>{{ number_format($user['lowest_score'], 2) }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" style="text-align: center; color: #64748b;">
                                                No users attempted this category yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
