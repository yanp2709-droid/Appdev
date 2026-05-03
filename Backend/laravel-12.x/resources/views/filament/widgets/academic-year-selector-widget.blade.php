<div style="display:flex;align-items:center;justify-content:flex-end;gap:12px;padding:12px 16px;border:1px solid #d7dde5;border-radius:16px;background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);box-shadow:0 10px 22px rgba(15,23,42,0.05);">
    <div style="display:flex;flex-direction:column;gap:4px;">
        <span style="font-size:12px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:#b45309;">Academic Year</span>
        <span style="font-size:13px;color:#64748b;">Pick the school year to refresh this page.</span>
    </div>

    <select
        wire:model.live="selectedAcademicYear"
        wire:change="changeAcademicYear()"
        style="min-width:160px;height:40px;border:1px solid #d1d5db;border-radius:12px;padding:0 12px;background:#fff;font-size:14px;font-weight:700;color:#0f172a;outline:none;"
    >
        @foreach ($this->getAcademicYearOptions() as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
</div>
