<div style="display:block;height:100%;">
    <div style="height:100%;border-radius:0.75rem;background:#ffffff;padding:1.5rem;box-shadow:0 1px 2px rgba(0,0,0,0.08);border:1px solid rgba(17,24,39,0.08);min-height:158px;">
        <div style="display:flex;flex-direction:column;gap:0.75rem;height:100%;justify-content:space-between;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;">
                <span style="font-size:0.875rem;font-weight:500;color:#6b7280;">{{ $metricTitle }}</span>
                <div style="width:0.625rem;height:0.625rem;border-radius:9999px;background:{{ $accentColor }};flex-shrink:0;margin-top:0.35rem;"></div>
            </div>

            <div style="display:flex;flex-direction:column;gap:0.25rem;">
                <div style="font-size:1.875rem;line-height:2.25rem;font-weight:600;letter-spacing:-0.025em;color:#111827;">
                    {{ $metricValue }}
                </div>
                <div style="font-size:0.875rem;font-weight:500;color:{{ $accentColor }};">
                    {{ $metricDescription }}
                </div>
            </div>
        </div>
    </div>
</div>
