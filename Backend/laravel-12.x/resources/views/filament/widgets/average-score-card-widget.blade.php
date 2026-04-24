<div style="display:block;height:100%;">
    <div style="height:100%;border-radius:0.75rem;background:#ffffff;padding:1.5rem;box-shadow:0 1px 2px rgba(0,0,0,0.08);border:1px solid rgba(17,24,39,0.08);min-height:158px;">
        <div style="display:flex;flex-direction:column;gap:0.75rem;height:100%;justify-content:space-between;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;">
                <span style="font-size:0.875rem;font-weight:500;color:#6b7280;">Average Score</span>

                <select
                    wire:model.live="selectedSchoolYear"
                    aria-label="Select academic year"
                    style="width:2rem;height:2rem;border:1px solid #d1d5db;border-radius:0.5rem;background:#ffffff;padding:0.2rem 0.35rem;font-size:0.75rem;font-weight:600;color:#6b7280;box-shadow:0 1px 2px rgba(0,0,0,0.05);outline:none;margin-right:36px;"
                >
                    @forelse ($schoolYearOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @empty
                        <option value="">No data available</option>
                    @endforelse
                </select>
            </div>

            <div style="display:flex;flex-direction:column;gap:0.25rem;">
                <div style="font-size:1.875rem;line-height:2.25rem;font-weight:600;letter-spacing:-0.025em;color:#111827;">
                    @if (! $averageData['has_column'] || ! $averageData['has_data'])
                        No data available
                    @else
                        {{ number_format($averageData['average_score'], 2) }}%
                    @endif
                </div>

                <div style="display:flex;align-items:center;gap:0.25rem;font-size:0.875rem;color:#d97706;">
                    <span>A.Y. {{ $selectedSchoolYearLabel }} class average</span>
                    <x-heroicon-o-chart-bar style="width:1rem;height:1rem;" />
                </div>

                <div style="font-size:0.75rem;color:#6b7280;">
                    @if (! $averageData['has_column'])
                        The school_year column is not available yet
                    @elseif ($averageData['has_data'])
                        {{ $averageData['attempt_count'] }} submitted attempt(s)
                    @else
                        No submitted records found for this academic year
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
