<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class EditProfile extends Page
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'Profile';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Edit Profile';

    protected string $view = 'filament.pages.edit-profile';

    public ?int $selectedUserId = null;
    public ?User $selectedUser = null;
    public ?array $data = ['search' => ''];
    public string $search = '';
    public string $activeTab = 'staff';

    public function mount(): void
    {
        if ($this->isAdmin()) {
            $this->selectedUserId = User::whereIn('role', ['admin', 'teacher'])
                ->orderByRaw("CASE WHEN role = 'admin' THEN 1 WHEN role = 'teacher' THEN 2 ELSE 3 END")
                ->orderBy('name')
                ->value('id') ?? auth()->id();
            $this->fillSelectedUser();
        } else {
            $this->data = [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ];
            $this->form->fill($this->data);
        }
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['staff', 'student']) ? $tab : 'staff';
        $this->search = '';

        $users = $this->activeTab === 'staff' ? $this->getStaffUsers() : $this->getStudentUsers();

        if ($users->isNotEmpty()) {
            $this->selectUser($users->first()->id);
        } else {
            $this->selectedUserId = null;
            $this->selectedUser = null;
        }
    }

    public function getStaffUsers(): \Illuminate\Support\Collection
    {
        $search = trim($this->search);
        $query = User::query()->whereIn('role', ['admin', 'teacher']);

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        return $query->orderByRaw("CASE WHEN role = 'admin' THEN 1 WHEN role = 'teacher' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->limit(15)
            ->get();
    }

    public function getStudentUsers(): \Illuminate\Support\Collection
    {
        $search = trim($this->search);
        $query = User::query()->where('role', 'student');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('student_id', 'like', "%$search%");
            });
        }

        return $query->orderBy('name')
            ->limit(15)
            ->get();
    }

    public function getUsers(): \Illuminate\Support\Collection
    {
        return $this->activeTab === 'staff' ? $this->getStaffUsers() : $this->getStudentUsers();
    }

    public function updatedSearch(): void
    {
        $search = trim($this->search);

        if ($search === '') {
            $users = $this->getUsers();

            if ($users->isNotEmpty()) {
                $this->selectUser($users->first()->id);
            }

            return;
        }

        $staffUsers = $this->getStaffUsers();
        $studentUsers = $this->getStudentUsers();

        if ($staffUsers->isNotEmpty()) {
            $this->activeTab = 'staff';
            $this->selectUser($staffUsers->first()->id);

            return;
        }

        if ($studentUsers->isNotEmpty()) {
            $this->activeTab = 'student';
            $this->selectUser($studentUsers->first()->id);

            return;
        }
    }

    public function selectUser(int $userId): void
    {
        $this->selectedUserId = $userId;
        $this->fillSelectedUser();
    }

    protected function fillSelectedUser(): void
    {
        if (! $this->selectedUserId) {
            return;
        }

        $user = User::find($this->selectedUserId);

        if (! $user) {
            return;
        }

        $this->selectedUser = $user;
        $this->data = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'xl' => 2,
            ])
            ->components([
                Section::make('Profile Information')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('role')
                            ->label('Role')
                            ->disabled(),
                    ]),
                Section::make('Change Password')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('current_password')
                            ->password()
                            ->revealable()
                            ->label('Current Password'),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->label('New Password')
                            ->rules([
                                Password::min(8)
                                    ->mixedCase()
                                    ->numbers()
                                    ->symbols(),
                            ]),
                        TextInput::make('password_confirmation')
                            ->password()
                            ->revealable()
                            ->same('password')
                            ->label('Confirm New Password'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $currentUser = auth()->user();
        $targetUser = $this->isAdmin() && $this->selectedUserId !== $currentUser->id
            ? User::find($this->selectedUserId)
            : $currentUser;

        if (! $targetUser) {
            Notification::make()
                ->title('Error')
                ->body('Selected user does not exist.')
                ->danger()
                ->send();

            return;
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $targetUser->id],
            'current_password' => ['nullable'],
            'password' => ['nullable', 'string', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()],
            'password_confirmation' => ['nullable', 'same:password'],
        ];

        if (! $this->isAdmin() || $targetUser->id === $currentUser->id) {
            $rules['current_password'][] = 'required_with:password';
        }

        $validated = Validator::make($data, $rules)->validate();

        $targetUser->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $passwordChanged = false;

        if (! empty($validated['password'])) {
            if ($targetUser->id === $currentUser->id) {
                if (! Hash::check($validated['current_password'] ?? '', $currentUser->password)) {
                    Notification::make()
                        ->title('Error')
                        ->body('Current password is incorrect.')
                        ->danger()
                        ->send();

                    return;
                }
            }

            $targetUser->update([
                'password' => Hash::make($validated['password']),
            ]);

            $passwordChanged = true;
        }

        Notification::make()
            ->title('Success')
            ->body($passwordChanged
                ? 'User profile and password updated successfully.'
                : 'Profile updated successfully.')
            ->success()
            ->send();

        $this->fillSelectedUser();
    }

    public function isAdmin(): bool
    {
        return auth()->user()?->role === 'admin';
    }
}
