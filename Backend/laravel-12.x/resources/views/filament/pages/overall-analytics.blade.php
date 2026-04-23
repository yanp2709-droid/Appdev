<x-filament-panels::page>
    <div class="stats-shell">
        <div class="stats-panel">
            <h2 class="stats-panel-title">Platform Overview</h2>
            <p class="stats-panel-subtitle">This dashboard provides a summary of overall platform analytics, such as total quizzes, total users, and recent activity.</p>
            <ul style="margin-top: 24px;">
                <li><strong>Total Quizzes:</strong> {{ \App\Models\Quiz::count() }}</li>
                <li><strong>Total Users:</strong> {{ \App\Models\User::count() }}</li>
                <li><strong>Total Attempts:</strong> {{ \App\Models\Attempt_answer::count() }}</li>
                <li><strong>Recent Activity:</strong> See latest attempts and quiz creations below.</li>
            </ul>
        </div>
        <div class="stats-panel" style="margin-top: 24px;">
            <h3 class="stats-panel-title">Recent Quiz Attempts</h3>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Quiz</th>
                        <th>Score</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (\App\Models\Attempt_answer::latest()->take(5)->get() as $attempt)
                        <tr>
                            <td>{{ $attempt->user->name ?? 'Unknown' }}</td>
                            <td>{{ $attempt->quiz->title ?? 'Untitled' }}</td>
                            <td>{{ number_format($attempt->score, 2) }}%</td>
                            <td>{{ $attempt->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
