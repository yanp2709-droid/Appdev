<x-filament-panels::page>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap');

        .profile-editor {
            --bg-card: rgba(18, 18, 24, 0.95);
            --bg-surface: rgba(255, 255, 255, 0.04);
            --bg-hover: rgba(255, 255, 255, 0.07);
            --border: rgba(255, 255, 255, 0.09);
            --border-hover: rgba(255, 255, 255, 0.16);
            --text-1: #f0f0f2;
            --text-2: #9899a6;
            --text-3: #5c5d6b;
            --accent-sky: #38bdf8;
            --accent-emerald: #34d399;
            --accent-violet: #a78bfa;
            --radius-sm: 10px;
            --radius-md: 14px;
            --radius-lg: 20px;
            --radius-xl: 26px;
            --ff: 'DM Sans', sans-serif;
            --fm: 'DM Mono', monospace;
            --shadow-card: 0 24px 64px rgba(0, 0, 0, 0.45);
        }

        /* ── Sidebar Card ── */
        .profile-editor-sidebar {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 24px;
            box-shadow: var(--shadow-card);
            position: relative;
            overflow: hidden;
            font-family: var(--ff);
        }

        .profile-editor-sidebar::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 50% at 100% 0%, rgba(56, 189, 248, 0.1) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 0% 100%, rgba(52, 211, 153, 0.07) 0%, transparent 60%);
            pointer-events: none;
        }

        .profile-editor-sidebar > * {
            position: relative;
        }

        .profile-editor-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            padding: 4px 12px;
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.25);
            color: var(--accent-sky);
        }

        .profile-editor-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent-sky);
        }

        .profile-editor-sidebar-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-1);
            margin-top: 14px;
            line-height: 1.3;
        }

        .profile-editor-sidebar-desc {
            font-size: 13px;
            color: var(--text-2);
            margin-top: 6px;
            line-height: 1.6;
        }

        /* ── Search ── */
        .profile-editor-search-wrap {
            margin-top: 20px;
            position: relative;
        }

        .profile-editor-search-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.4;
            pointer-events: none;
        }

        .profile-editor-search {
            width: 100%;
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 11px 14px 11px 38px;
            font-family: var(--ff);
            font-size: 13px;
            color: var(--text-1);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .profile-editor-search::placeholder {
            color: var(--text-3);
        }

        .profile-editor-search:focus {
            border-color: rgba(56, 189, 248, 0.5);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.08);
        }

        /* ── Tabs ── */
        .profile-editor-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 12px;
        }

        .profile-editor-tab {
            border-radius: var(--radius-sm);
            padding: 9px 14px;
            font-family: var(--ff);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.02em;
            cursor: pointer;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-2);
            transition: all 0.18s;
        }

        .profile-editor-tab:hover {
            background: var(--bg-hover);
            color: var(--text-1);
        }

        .profile-editor-tab-staff-active {
            background: rgba(56, 189, 248, 0.1);
            border-color: rgba(56, 189, 248, 0.35);
            color: var(--accent-sky);
        }

        .profile-editor-tab-student-active {
            background: rgba(167, 139, 250, 0.1);
            border-color: rgba(167, 139, 250, 0.35);
            color: var(--accent-violet);
        }

        /* ── List Header ── */
        .profile-editor-list-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 18px 0 10px;
        }

        .profile-editor-list-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--text-3);
        }

        .profile-editor-count {
            font-size: 11px;
            font-family: var(--fm);
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 3px 10px;
            color: var(--text-2);
        }

        /* ── User List ── */
        .profile-editor-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 380px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .profile-editor-list::-webkit-scrollbar {
            width: 4px;
        }

        .profile-editor-list::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 999px;
        }

        /* ── User Card ── */
        .profile-editor-user {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 13px 14px;
            cursor: pointer;
            transition: all 0.18s;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            width: 100%;
            text-align: left;
        }

        .profile-editor-user:hover {
            background: var(--bg-hover);
            border-color: var(--border-hover);
            transform: translateY(-1px);
        }

        .profile-editor-user-selected {
            border-color: rgba(56, 189, 248, 0.4) !important;
            background: rgba(56, 189, 248, 0.06) !important;
            box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.15);
        }

        /* ── Avatars ── */
        .profile-editor-avatar {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--fm);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .avatar-admin    { background: rgba(56, 189, 248, 0.15); color: var(--accent-sky); }
        .avatar-teacher  { background: rgba(52, 211, 153, 0.15); color: var(--accent-emerald); }
        .avatar-student  { background: rgba(167, 139, 250, 0.15); color: var(--accent-violet); }

        .profile-editor-user-name  { font-size: 13px; font-weight: 600; color: var(--text-1); }
        .profile-editor-user-email { font-size: 12px; color: var(--text-2); margin-top: 2px; }

        .profile-editor-role-tag {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-2);
            white-space: nowrap;
        }

        .profile-editor-sid-tag {
            display: inline-block;
            margin-top: 6px;
            font-family: var(--fm);
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0.1em;
            padding: 3px 10px;
            border-radius: 999px;
            background: rgba(167, 139, 250, 0.1);
            border: 1px solid rgba(167, 139, 250, 0.2);
            color: var(--accent-violet);
        }

        /* ── Summary Card ── */
        .profile-editor-summary {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 22px 24px;
            box-shadow: var(--shadow-card);
            position: relative;
            overflow: hidden;
            font-family: var(--ff);
        }

        .profile-editor-summary::after {
            content: '';
            position: absolute;
            top: -60px;
            right: -40px;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .profile-editor-summary-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .profile-editor-summary-identity {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .profile-editor-summary-avatar {
            width: 52px;
            height: 52px;
            border-radius: 15px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--fm);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.05em;
            border: 1px solid rgba(56, 189, 248, 0.25);
        }

        .profile-editor-summary-sublabel {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--text-3);
        }

        .profile-editor-summary-name  { font-size: 21px; font-weight: 600; margin-top: 4px; color: var(--text-1); }
        .profile-editor-summary-email { font-size: 13px; color: var(--text-2); margin-top: 2px; }

        /* ── Meta Grid ── */
        .profile-editor-meta-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 18px;
        }

        .profile-editor-meta-cell {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 12px 14px;
        }

        .profile-editor-meta-key {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--text-3);
        }

        .profile-editor-meta-val {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-1);
            margin-top: 6px;
            word-break: break-all;
        }

        /* ── Form Shell ── */
        .profile-editor-form-shell {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 24px;
            box-shadow: var(--shadow-card);
            font-family: var(--ff);
        }

        .profile-editor-form-header-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--text-3);
        }

        .profile-editor-form-title    { font-size: 18px; font-weight: 600; margin-top: 4px; color: var(--text-1); }
        .profile-editor-form-subtitle { font-size: 13px; color: var(--text-2); margin-top: 4px; }

        /* ── Section Blocks ── */
        .profile-editor-form-shell .fi-section {
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.025);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .profile-editor-form-shell .fi-section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 18px;
            background: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid var(--border);
        }

        .profile-editor-section-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        }

        .profile-editor-section-icon-info { background: rgba(56, 189, 248, 0.12); }
        .profile-editor-section-icon-lock { background: rgba(52, 211, 153, 0.12); }

        /* ── Form Footer ── */
        .profile-editor-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid var(--border);
        }

        .profile-editor-btn-save {
            background: rgba(52, 211, 153, 0.15);
            border: 1px solid rgba(52, 211, 153, 0.35);
            color: var(--accent-emerald);
            border-radius: var(--radius-sm);
            padding: 10px 22px;
            font-family: var(--ff);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.18s;
        }

        .profile-editor-btn-save:hover {
            background: rgba(52, 211, 153, 0.25);
            transform: translateY(-1px);
        }

        .profile-editor-btn-reset {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-2);
            border-radius: var(--radius-sm);
            padding: 10px 18px;
            font-family: var(--ff);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.18s;
        }

        .profile-editor-btn-reset:hover {
            background: var(--bg-hover);
            color: var(--text-1);
        }

        .profile-editor-footer-note {
            margin-left: auto;
            font-size: 12px;
            color: var(--text-3);
        }

        /* ── Empty State ── */
        .profile-editor-empty {
            padding: 18px;
            text-align: center;
            font-size: 13px;
            color: var(--text-3);
            border: 1px dashed var(--border);
            border-radius: var(--radius-md);
        }

        /* ── Personal Profile (non-admin) ── */
        .profile-editor-personal-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            padding: 4px 12px;
            background: rgba(52, 211, 153, 0.1);
            border: 1px solid rgba(52, 211, 153, 0.25);
            color: var(--accent-emerald);
        }

        .profile-editor-personal-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent-emerald);
        }

        .profile-editor-personal-title { font-size: 22px; font-weight: 600; color: var(--text-1); margin-top: 14px; }
        .profile-editor-personal-desc  { font-size: 13px; color: var(--text-2); margin-top: 6px; line-height: 1.6; }
    </style>

    @if ($this->isAdmin())
        <div class="profile-editor grid gap-5 xl:grid-cols-[320px_minmax(0,1fr)]">

            {{-- ── Sidebar ── --}}
            <div class="profile-editor-sidebar">

                <span class="profile-editor-badge">Account Manager</span>

                <h2 class="profile-editor-sidebar-title">Edit Profiles<br>With Control</h2>
                <p class="profile-editor-sidebar-desc">
                    Search staff or students, switch context instantly, and manage profile details separately from passwords.
                </p>

                {{-- Search --}}
                <div class="profile-editor-search-wrap">
                    <svg class="profile-editor-search-icon" width="14" height="14" viewBox="0 0 16 16" fill="none">
                        <circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M11 11l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search name, email, or student ID…"
                        class="profile-editor-search"
                    />
                </div>

                {{-- Tabs --}}
                <div class="profile-editor-tabs">
                    <button
                        type="button"
                        wire:click="switchTab('staff')"
                        @class([
                            'profile-editor-tab',
                            'profile-editor-tab-staff-active' => $activeTab === 'staff',
                        ])
                    >
                        Staff
                    </button>
                    <button
                        type="button"
                        wire:click="switchTab('student')"
                        @class([
                            'profile-editor-tab',
                            'profile-editor-tab-student-active' => $activeTab === 'student',
                        ])
                    >
                        Students
                    </button>
                </div>

                {{-- List header --}}
                <div class="profile-editor-list-header">
                    <span class="profile-editor-list-label">
                        {{ $activeTab === 'staff' ? 'Admin and Teachers' : 'Student Accounts' }}
                    </span>
                    <span class="profile-editor-count">
                        {{ $this->getUsers()->count() }} shown
                    </span>
                </div>

                {{-- User list --}}
                <div class="profile-editor-list">
                    @forelse ($this->getUsers() as $user)
                        @php
                            $isSelected = $selectedUserId === $user->id;
                            $avatarClass = match ($user->role) {
                                'admin'   => 'avatar-admin',
                                'teacher' => 'avatar-teacher',
                                default   => 'avatar-student',
                            };
                        @endphp

                        <button
                            type="button"
                            wire:click="selectUser({{ $user->id }})"
                            @class([
                                'profile-editor-user',
                                'profile-editor-user-selected' => $isSelected,
                            ])
                        >
                            <div class="profile-editor-avatar {{ $avatarClass }}">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>

                            <div style="flex:1;min-width:0">
                                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px">
                                    <div>
                                        <div class="profile-editor-user-name">{{ $user->name }}</div>
                                        <div class="profile-editor-user-email">{{ $user->email }}</div>
                                    </div>
                                    <span class="profile-editor-role-tag">{{ ucfirst($user->role) }}</span>
                                </div>

                                @if ($user->role === 'student' && $user->student_id)
                                    <span class="profile-editor-sid-tag">
                                        ID: {{ $user->student_id }}
                                    </span>
                                @endif
                            </div>
                        </button>

                    @empty
                        <div class="profile-editor-empty">No users found.</div>
                    @endforelse
                </div>
            </div>

            {{-- ── Main Column ── --}}
            <div class="flex flex-col gap-5">

                {{-- Summary Card --}}
                @if ($selectedUser)
                    @php
                        $summaryAvatarClass = match ($selectedUser->role) {
                            'admin'   => 'avatar-admin',
                            'teacher' => 'avatar-teacher',
                            default   => 'avatar-student',
                        };
                    @endphp

                    <div class="profile-editor-summary">
                        <div class="profile-editor-summary-top">
                            <div class="profile-editor-summary-identity">
                                <div class="profile-editor-summary-avatar profile-editor-avatar {{ $summaryAvatarClass }}"
                                     style="width:52px;height:52px;border-radius:15px;font-size:14px;">
                                    {{ strtoupper(substr($selectedUser->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="profile-editor-summary-sublabel">Selected Account</div>
                                    <div class="profile-editor-summary-name">{{ $selectedUser->name }}</div>
                                    <div class="profile-editor-summary-email">{{ $selectedUser->email }}</div>
                                </div>
                            </div>

                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                                <span class="profile-editor-role-tag">{{ ucfirst($selectedUser->role) }}</span>
                                @if ($selectedUser->role === 'student' && $selectedUser->student_id)
                                    <span class="profile-editor-sid-tag">{{ $selectedUser->student_id }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="profile-editor-meta-grid">
                            <div class="profile-editor-meta-cell">
                                <div class="profile-editor-meta-key">Role</div>
                                <div class="profile-editor-meta-val">{{ ucfirst($selectedUser->role) }}</div>
                            </div>
                            <div class="profile-editor-meta-cell">
                                <div class="profile-editor-meta-key">Email</div>
                                <div class="profile-editor-meta-val" style="font-size:11px">{{ $selectedUser->email }}</div>
                            </div>
                            <div class="profile-editor-meta-cell">
                                <div class="profile-editor-meta-key">Security</div>
                                <div class="profile-editor-meta-val">Password section separated</div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Form Shell --}}
                <div class="profile-editor-form-shell">
                    <div class="profile-editor-form-header-label">Editor Workspace</div>
                    <div class="profile-editor-form-title">Profile Details &amp; Password</div>
                    <p class="profile-editor-form-subtitle">
                        Update account information in one section and password controls in the other.
                    </p>

                    <form wire:submit.prevent="save">
                        {{ $this->form }}

                        <div class="profile-editor-footer">
                            <button type="submit" class="profile-editor-btn-save">
                                Save Changes
                            </button>

                            <button
                                type="button"
                                wire:click="selectUser({{ $selectedUserId ?? 0 }})"
                                class="profile-editor-btn-reset"
                            >
                                Reset
                            </button>

                            <span class="profile-editor-footer-note">
                                Changes apply to the currently selected account only.
                            </span>
                        </div>
                    </form>
                </div>

            </div>
        </div>

    @else
        {{-- ── Personal Profile (non-admin) ── --}}
        <div class="profile-editor profile-editor-form-shell" style="border-radius:26px;padding:24px">

            <span class="profile-editor-personal-badge">Personal Profile</span>

            <h2 class="profile-editor-personal-title">Manage Your Account</h2>
            <p class="profile-editor-personal-desc">
                Update your information and password from separate sections designed for quick, safe edits.
            </p>

            <form wire:submit.prevent="save">
                {{ $this->form }}

                <div class="profile-editor-footer">
                    <button type="submit" class="profile-editor-btn-save">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    @endif
</x-filament-panels::page>