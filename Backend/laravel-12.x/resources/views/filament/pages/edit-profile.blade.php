<x-filament-panels::page>
    <div class="space-y-6">
        @if ($this->isAdmin())
            <div style="background: #18181b; border: 1px solid #27272a; border-radius: 16px; overflow: hidden;">

                {{-- Header --}}
                <div style="background: #1c1c1f; border-bottom: 1px solid #27272a; padding: 20px 24px; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <div style="font-size: 15px; font-weight: 600; color: #f4f4f5;">Account Management</div>
                        <div style="font-size: 13px; color: #71717a; margin-top: 2px;">Search users, edit profile details, and reset passwords securely.</div>
                    </div>
                    <span style="font-size: 11px; font-weight: 600; padding: 4px 12px; border-radius: 20px; background: #1e3a5f; color: #60a5fa; border: 1px solid #1d4ed8;">
                        Admin view
                    </span>
                </div>

                {{-- Main Content --}}
                <div style="display: flex; align-items: stretch; min-height: 520px;">

                    {{-- Search Sidebar --}}
                    <div style="width: 280px; flex-shrink: 0; border-right: 1px solid #27272a; padding: 16px;">

                        <div style="position: relative; margin-bottom: 12px;">
                            <input
                                type="text"
                                wire:model.live="search"
                                placeholder="Search name, Gmail, or student ID…"
                                style="width: 100%; padding: 8px 12px 8px 32px; background: #09090b; border: 1px solid #3f3f46; border-radius: 8px; color: #f4f4f5; font-size: 13px; outline: none;"
                            />
                            <svg style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: #52525b;" fill="none" stroke="#52525b" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <div style="font-size: 10px; font-weight: 600; color: #52525b; text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 8px;">All accounts</div>

                        <div style="display: flex; flex-direction: column; gap: 3px; max-height: 440px; overflow-y: auto;">
                            @forelse ($this->getUsers() as $user)
                                <button
                                    wire:click="selectUser({{ $user->id }})"
                                    type="button"
                                    style="width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 8px; border: 1px solid {{ $selectedUserId === $user->id ? '#1d4ed8' : 'transparent' }}; background: {{ $selectedUserId === $user->id ? '#1e3a5f' : 'transparent' }}; cursor: pointer; text-align: left; transition: background 0.15s;"
                                    onmouseover="if('{{ $selectedUserId }}' !== '{{ $user->id }}') this.style.background='#27272a'"
                                    onmouseout="if('{{ $selectedUserId }}' !== '{{ $user->id }}') this.style.background='transparent'"
                                >
                                    <div style="flex-shrink: 0; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;
                                        background: {{ $user->role === 'admin' ? '#1e3a5f' : ($user->role === 'teacher' ? '#14532d' : '#3b0764') }};
                                        color: {{ $user->role === 'admin' ? '#60a5fa' : ($user->role === 'teacher' ? '#4ade80' : '#c084fc') }};">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-size: 13px; font-weight: 600; color: #f4f4f5; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $user->name }}</div>
                                        <div style="font-size: 11px; color: #71717a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $user->email }}</div>
                                        @if ($user->role === 'student' && $user->student_id)
                                            <div style="font-size: 10px; color: #71717a; background: #27272a; border: 1px solid #3f3f46; padding: 1px 6px; border-radius: 20px; margin-top: 2px; display: inline-block;">
                                                {{ $user->student_id }}
                                            </div>
                                        @endif
                                    </div>
                                    <span style="font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 20px; flex-shrink: 0;
                                        background: {{ $user->role === 'admin' ? '#1e3a5f' : ($user->role === 'teacher' ? '#14532d' : '#3b0764') }};
                                        color: {{ $user->role === 'admin' ? '#60a5fa' : ($user->role === 'teacher' ? '#4ade80' : '#c084fc') }};">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </button>
                            @empty
                                <div style="text-align: center; padding: 40px 16px;">
                                    <div style="width: 40px; height: 40px; background: #27272a; border: 1px solid #3f3f46; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                                        <svg style="width: 18px; height: 18px;" fill="none" stroke="#52525b" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div style="font-size: 13px; font-weight: 600; color: #a1a1aa;">No accounts found</div>
                                    <div style="font-size: 12px; color: #52525b; margin-top: 4px;">Try a different name, email, or student ID</div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Form Panel --}}
                    <div style="flex: 1; padding: 24px;">
                        @if (! $selectedUser)
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; text-align: center; padding: 64px 32px;">
                                <div style="width: 48px; height: 48px; background: #27272a; border: 1px solid #3f3f46; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                                    <svg style="width: 20px; height: 20px;" fill="none" stroke="#52525b" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div style="font-size: 14px; font-weight: 600; color: #a1a1aa;">No user selected</div>
                                <div style="font-size: 13px; color: #52525b; margin-top: 4px;">Choose an account from the list to view and edit details.</div>
                            </div>
                        @else
                            {{-- User Header --}}
                            <div style="display: flex; align-items: center; gap: 12px; padding-bottom: 20px; margin-bottom: 20px; border-bottom: 1px solid #27272a;">
                                <div style="width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0;
                                    background: {{ $selectedUser->role === 'admin' ? '#1e3a5f' : ($selectedUser->role === 'teacher' ? '#14532d' : '#3b0764') }};
                                    color: {{ $selectedUser->role === 'admin' ? '#60a5fa' : ($selectedUser->role === 'teacher' ? '#4ade80' : '#c084fc') }};">
                                    {{ strtoupper(substr($selectedUser->name, 0, 2)) }}
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 14px; font-weight: 600; color: #f4f4f5;">{{ $selectedUser->name }}</div>
                                    <div style="font-size: 12px; color: #71717a;">{{ $selectedUser->email }}</div>
                                </div>
                                <span style="font-size: 10px; font-weight: 600; padding: 4px 10px; border-radius: 20px; flex-shrink: 0;
                                    background: {{ $selectedUser->role === 'admin' ? '#1e3a5f' : ($selectedUser->role === 'teacher' ? '#14532d' : '#3b0764') }};
                                    color: {{ $selectedUser->role === 'admin' ? '#60a5fa' : ($selectedUser->role === 'teacher' ? '#4ade80' : '#c084fc') }};
                                    border: 1px solid {{ $selectedUser->role === 'admin' ? '#1d4ed8' : ($selectedUser->role === 'teacher' ? '#166534' : '#7e22ce') }};">
                                    {{ ucfirst($selectedUser->role) }}
                                </span>
                            </div>

                            {{-- Form --}}
                            <form wire:submit.prevent="save" class="space-y-4">
                                {{ $this->form }}

                                <div style="display: flex; align-items: center; gap: 8px; padding-top: 16px; border-top: 1px solid #27272a;">
                                    <button
                                        type="submit"
                                        style="padding: 8px 16px; background: #16a34a; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                                        Save changes
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="fillSelectedUser"
                                        style="padding: 8px 12px; background: transparent; color: #a1a1aa; border: 1px solid #3f3f46; border-radius: 8px; font-size: 13px; cursor: pointer;">
                                        Reset
                                    </button>
                                    <div style="margin-left: auto; display: flex; align-items: center; gap: 6px; font-size: 12px; color: #52525b;">
                                        <svg style="width: 12px; height: 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        Passwords are not shown for security
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>

                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

