<div style="display:block;height:100%;">
    <div style="position:relative;height:100%;border-radius:0.75rem;background:#ffffff;padding:1.5rem;box-shadow:0 4px 12px rgba(15,23,42,0.04),0 1px 2px rgba(15,23,42,0.02);border:1px solid rgba(17,24,39,0.06);min-height:158px;overflow:hidden;">
        <div style="position:absolute;top:0;left:0;right:0;height:6px;background:#d97706;border-radius:0.75rem 0.75rem 0 0;"></div>
        <div style="display:flex;flex-direction:column;gap:0.75rem;height:100%;justify-content:space-between;padding-top:6px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;">
                <span style="font-size:0.875rem;font-weight:600;color:#d97706;">Average Score</span>
            </div>

            <div style="display:flex;flex-direction:column;gap:0.25rem;">
                <div style="font-size:1.875rem;line-height:2.25rem;font-weight:700;letter-spacing:-0.025em;color:#111827;">
                    @if (! $averageData['has_data'])
                        No data available
                    @else
                        {{ number_format($averageData['average_score'], 2) }}%
                    @endif
                </div>

                <div style="display:flex;align-items:center;gap:0.25rem;font-size:0.875rem;color:#d97706;">
                    <span>Class average</span>
                    <x-heroicon-o-chart-bar style="width:1rem;height:1rem;" />
                </div>

                <div style="font-size:0.75rem;color:#6b7280;">
                    @if ($averageData['has_data'])
                        {{ $averageData['attempt_count'] }} submitted attempt(s)
                    @else
                        No submitted records found for this academic year
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
