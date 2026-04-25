<x-filament-panels::page>
    @php
        $categories = $this->getCategories();
    @endphp

    <style>
        [x-cloak] {
            display: none !important;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 320px));
            gap: 24px;
            align-items: start;
        }

        .category-card {
            position: relative;
            background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
            border: 1px solid #d9e2ec;
            border-radius: 26px;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
            padding: 0 20px 20px;
            min-height: 410px;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            overflow: hidden;
        }

        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 88px;
            background: linear-gradient(135deg, #1a5fd4 0%, #4f86f7 100%);
            border-radius: 26px 26px 28px 28px;
        }

        .category-card:hover {
            transform: translateY(-4px);
            border-color: #cfd8e3;
            box-shadow: 0 22px 40px rgba(15, 23, 42, 0.12);
        }

        .category-open-link {
            position: absolute;
            inset: 0;
            border-radius: 24px;
            z-index: 1;
        }

        .category-actions {
            position: absolute;
            top: 16px;
            right: 16px;
            display: flex;
            gap: 8px;
            z-index: 2;
        }

        .category-edit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 11px;
            border-radius: 999px;
            background: #111827;
            color: #fff;
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
            min-width: 54px;
        }

        .category-edit:hover {
            background: #f59e0b;
            color: #111827;
        }

        .category-disable {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 11px;
            border-radius: 999px;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
            min-width: 62px;
            cursor: pointer;
        }

        .category-disable:hover {
            background: #ffe4e6;
            border-color: #e11d48;
            color: #9f1239;
        }

        .category-enable {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 11px;
            border-radius: 999px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
            min-width: 62px;
            cursor: pointer;
        }

        .category-enable:hover {
            background: #d1fae5;
            border-color: #34d399;
            color: #065f46;
        }

        .category-header {
            margin-top: 24px;
            position: relative;
            z-index: 2;
            padding: 52px 0 0;
        }

        .category-header::before {
            content: attr(data-card-mark);
            position: absolute;
            top: 16px;
            left: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 60px;
            height: 60px;
            padding: 0 18px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(8px);
            color: #ffffff;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        .category-title {
            margin: 16px 0 8px;
            text-align: left;
            font-size: 22px;
            line-height: 1.2;
            font-weight: 800;
            color: #0f172a;
        }

        .category-description {
            margin: 0 0 24px;
            max-width: 100%;
            min-height: 60px;
            text-align: left;
            font-size: 14px;
            line-height: 1.45;
            color: #334155;
            position: relative;
            z-index: 2;
        }

        .category-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            max-width: 100%;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .category-stat {
            background: #ffffff;
            border: 1px solid #dde7f2;
            border-radius: 18px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
            padding: 14px;
            min-height: 108px;
        }

        .category-stat-head {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .category-stat-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
            line-height: 1.1;
        }

        .category-stat-value {
            display: block;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
        }

        .category-created {
            margin-top: 26px;
            padding-top: 16px;
            border-top: 1px solid #e5edf5;
            text-align: left;
            font-size: 13px;
            color: #64748b;
            position: relative;
            z-index: 2;
        }

        .category-empty {
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 20px;
            padding: 28px;
            text-align: center;
            color: #64748b;
        }

        .status-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 1000;
        }

        .status-modal {
            width: 100%;
            max-width: 560px;
            background: #fff;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
            position: relative;
        }

        .status-modal-close {
            position: absolute;
            top: 18px;
            right: 18px;
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 999px;
            background: transparent;
            font-size: 28px;
            line-height: 1;
            color: #6b7280;
            cursor: pointer;
        }

        .status-modal-icon-wrap {
            width: 56px;
            height: 56px;
            border-radius: 999px;
            background: #fee2e2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
        }

        .status-modal-title {
            margin: 0;
            text-align: center;
            font-size: 20px;
            font-weight: 800;
            color: #111827;
        }

        .status-modal-copy {
            margin: 12px 0 28px;
            text-align: center;
            font-size: 15px;
            color: #6b7280;
        }

        .status-modal-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .status-modal-cancel,
        .status-modal-confirm {
            height: 48px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        .status-modal-cancel {
            background: #fff;
            color: #111827;
            border: 1px solid #e5e7eb;
        }

        .status-modal-confirm {
            background: #ef4444;
            color: #fff;
            border: none;
        }
    </style>

    <div
        x-data="{ statusModalOpen: false, categoryAction: 'disable', categoryId: null, categoryName: '' }"
        x-on:keydown.escape.window="statusModalOpen = false"
    >
    <div class="categories-grid">
        @forelse ($categories as $category)
            <div class="category-card">
                <a
                    href="{{ \App\Filament\Resources\Categories\CategoryResource::getUrl('questions', ['record' => $category]) }}"
                    class="category-open-link"
                    aria-label="Open {{ $category->name }}"
                ></a>

                <div class="category-header" data-card-mark="{{ strtoupper(\Illuminate\Support\Str::substr($category->name, 0, 2)) }}">
                    <div class="category-title">{{ $category->name }}</div>

                    <div class="category-description">
                        {{ $category->description ?: 'Open this quiz to view its question list.' }}
                    </div>
                </div>

                <div class="category-actions">
                    <button
                        type="button"
                        class="{{ $category->is_published ? 'category-disable' : 'category-enable' }}"
                        aria-label="{{ $category->is_published ? 'Disable' : 'Enable' }} {{ $category->name }}"
                        x-on:click.prevent.stop="statusModalOpen = true; categoryAction = '{{ $category->is_published ? 'disable' : 'enable' }}'; categoryId = {{ $category->id }}; categoryName = @js($category->name)"
                    >
                        {{ $category->is_published ? 'Disable' : 'Enable' }}
                    </button>

                    <a
                        href="{{ \App\Filament\Resources\Categories\CategoryResource::getUrl('edit', ['record' => $category]) }}"
                        class="category-edit"
                        aria-label="Edit {{ $category->name }}"
                    >
                        Edit
                    </a>
                </div>

                <div class="category-stats">
                    <div class="category-stat">
                        <div class="category-stat-head">
                            <span class="category-stat-label">Questions</span>
                        </div>
                        <span class="category-stat-value">{{ $category->questions_count }}</span>
                    </div>

                    <div class="category-stat">
                        <div class="category-stat-head">
                            <span class="category-stat-label">Time Limit</span>
                        </div>
                        <span class="category-stat-value">{{ $category->time_limit_minutes }} min</span>
                    </div>

                    <div class="category-stat">
                        <div class="category-stat-head">
                            <span class="category-stat-icon" aria-hidden="true" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M6 15l4-4 3 3 5-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14 7h4v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span class="category-stat-label">Highest Score</span>
                        </div>
                        <span class="category-stat-value">
                            {{ is_null($category->highest_score) ? 'N/A' : number_format((float) $category->highest_score, 2) . '%' }}
                        </span>
                    </div>

                    <div class="category-stat">
                        <div class="category-stat-head">
                            <span class="category-stat-icon" aria-hidden="true" style="background: linear-gradient(135deg, #fb7185 0%, #ef4444 100%);">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M6 9l4 4 3-3 5 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14 17h4v-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span class="category-stat-label">Lowest Score</span>
                        </div>
                        <span class="category-stat-value">
                            {{ is_null($category->lowest_score) ? 'N/A' : number_format((float) $category->lowest_score, 2) . '%' }}
                        </span>
                    </div>
                </div>

                <div class="category-created">
                    Created {{ optional($category->created_at)->format('M d, Y') }}
                </div>
            </div>
        @empty
            <div class="category-empty">
                No quizzes available yet.
            </div>
        @endforelse
    </div>

    <div
        x-cloak
        x-show="statusModalOpen"
        class="status-modal-overlay"
        x-on:click.self="statusModalOpen = false"
    >
        <div class="status-modal">
            <button
                type="button"
                class="status-modal-close"
                x-on:click="statusModalOpen = false"
                aria-label="Close quiz status confirmation"
            >
                ×
            </button>

            <div class="status-modal-icon-wrap" x-bind:style="categoryAction === 'enable' ? 'background: #d1fae5;' : 'background: #fee2e2;'">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path x-show="categoryAction === 'disable'" d="M8 7V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v1m-9 0h10m-9 0 1 11a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2l1-11m-6 3v6m4-6v6" stroke="#ef4444" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path x-show="categoryAction === 'enable'" d="M5 12l4 4L19 6" stroke="#059669" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <h3 class="status-modal-title">
                <span x-text="categoryAction === 'disable' ? 'Disable' : 'Enable'"></span>
                <span x-text="categoryName"></span>
            </h3>

            <p class="status-modal-copy" x-text="categoryAction === 'disable'
                ? 'This quiz will be hidden from students when taking quizzes, but existing history will stay available.'
                : 'This quiz will be visible to students when taking quizzes again.'">
            </p>

            <div class="status-modal-actions">
                <button
                    type="button"
                    class="status-modal-cancel"
                    x-on:click="statusModalOpen = false"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    class="status-modal-confirm"
                    x-bind:style="categoryAction === 'enable' ? 'background: #059669;' : ''"
                    x-on:click="categoryAction === 'disable' ? $wire.disableCategory(categoryId) : $wire.enableCategory(categoryId); statusModalOpen = false"
                >
                    <span x-text="categoryAction === 'disable' ? 'Disable' : 'Enable'"></span>
                </button>
            </div>
        </div>
    </div>
    </div>
</x-filament-panels::page>
