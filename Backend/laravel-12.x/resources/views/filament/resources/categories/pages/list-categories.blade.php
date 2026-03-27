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
            background: #fff;
            border: 1px solid #d9e2ec;
            border-radius: 24px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
            padding: 22px 20px 20px;
            min-height: 400px;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }

        .category-card:hover {
            transform: translateY(-2px);
            border-color: #cfd8e3;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
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

        .category-delete {
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

        .category-delete:hover {
            background: #ffe4e6;
            color: #fff;
            border-color: #e11d48;
            color: #9f1239;
        }

        .category-title {
            margin: 56px 0 18px;
            text-align: center;
            font-size: 22px;
            line-height: 1.2;
            font-weight: 800;
            color: #0f172a;
            position: relative;
            z-index: 2;
        }

        .category-description {
            margin: 0 auto 24px;
            max-width: 220px;
            min-height: 60px;
            text-align: center;
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
            max-width: 240px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .category-stat {
            background: #fff;
            border: 1px solid #d9e2ec;
            border-radius: 18px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
            padding: 14px 14px;
            min-height: 108px;
        }

        .category-stat-label {
            display: block;
            font-size: 14px;
            color: #475569;
            margin-bottom: 10px;
        }

        .category-stat-value {
            display: block;
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
        }

        .category-created {
            margin-top: 32px;
            text-align: center;
            font-size: 14px;
            color: #334155;
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

        .delete-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 1000;
        }

        .delete-modal {
            width: 100%;
            max-width: 560px;
            background: #fff;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
            position: relative;
        }

        .delete-modal-close {
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

        .delete-modal-icon-wrap {
            width: 56px;
            height: 56px;
            border-radius: 999px;
            background: #fee2e2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
        }

        .delete-modal-title {
            margin: 0;
            text-align: center;
            font-size: 20px;
            font-weight: 800;
            color: #111827;
        }

        .delete-modal-copy {
            margin: 12px 0 28px;
            text-align: center;
            font-size: 15px;
            color: #6b7280;
        }

        .delete-modal-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .delete-modal-cancel,
        .delete-modal-confirm {
            height: 48px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        .delete-modal-cancel {
            background: #fff;
            color: #111827;
            border: 1px solid #e5e7eb;
        }

        .delete-modal-confirm {
            background: #ef4444;
            color: #fff;
            border: none;
        }
    </style>

    <div
        x-data="{ deleteModalOpen: false, deleteCategoryId: null, deleteCategoryName: '' }"
        x-on:keydown.escape.window="deleteModalOpen = false"
    >
    <div class="categories-grid">
        @forelse ($categories as $category)
            <div class="category-card">
                <a
                    href="{{ \App\Filament\Resources\Categories\CategoryResource::getUrl('questions', ['record' => $category]) }}"
                    class="category-open-link"
                    aria-label="Open {{ $category->name }}"
                ></a>

                <div class="category-actions">
                    <button
                        type="button"
                        class="category-delete"
                        aria-label="Delete {{ $category->name }}"
                        x-on:click.prevent.stop="deleteModalOpen = true; deleteCategoryId = {{ $category->id }}; deleteCategoryName = @js($category->name)"
                    >
                        Delete
                    </button>

                    <a
                        href="{{ \App\Filament\Resources\Categories\CategoryResource::getUrl('edit', ['record' => $category]) }}"
                        class="category-edit"
                        aria-label="Edit {{ $category->name }}"
                    >
                        Edit
                    </a>
                </div>

                <div class="category-title">{{ $category->name }}</div>

                <div class="category-description">
                    {{ $category->description ?: 'Open this category to view its question list.' }}
                </div>

                <div class="category-stats">
                    <div class="category-stat">
                        <span class="category-stat-label">Questions</span>
                        <span class="category-stat-value">{{ $category->questions_count }}</span>
                    </div>

                    <div class="category-stat">
                        <span class="category-stat-label">Time Limit</span>
                        <span class="category-stat-value">{{ $category->time_limit_minutes }} min</span>
                    </div>
                </div>

                <div class="category-created">
                    Created {{ optional($category->created_at)->format('M d, Y') }}
                </div>
            </div>
        @empty
            <div class="category-empty">
                No categories available yet.
            </div>
        @endforelse
    </div>

    <div
        x-cloak
        x-show="deleteModalOpen"
        class="delete-modal-overlay"
        x-on:click.self="deleteModalOpen = false"
    >
        <div class="delete-modal">
            <button
                type="button"
                class="delete-modal-close"
                x-on:click="deleteModalOpen = false"
                aria-label="Close delete confirmation"
            >
                ×
            </button>

            <div class="delete-modal-icon-wrap">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M8 7V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v1m-9 0h10m-9 0 1 11a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2l1-11m-6 3v6m4-6v6" stroke="#ef4444" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <h3 class="delete-modal-title">
                Delete <span x-text="deleteCategoryName"></span>
            </h3>

            <p class="delete-modal-copy">
                Are you sure you would like to do this?
            </p>

            <div class="delete-modal-actions">
                <button
                    type="button"
                    class="delete-modal-cancel"
                    x-on:click="deleteModalOpen = false"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    class="delete-modal-confirm"
                    x-on:click="$wire.deleteCategory(deleteCategoryId); deleteModalOpen = false"
                >
                    Delete
                </button>
            </div>
        </div>
    </div>
    </div>
</x-filament-panels::page>
