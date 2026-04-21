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

    public function mount(): void
    {
        if ($this->isAdmin()) {
            $this->selectedUserId = User::orderByRaw("CASE WHEN role = 'admin' THEN 1 WHEN role = 'teacher' THEN 2 ELSE 3 END")
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

    public function getUsers(): \Illuminate\Support\Collection
    {
        $search = trim($this->search);
        $query = User::query();

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
$q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('student_id', 'like', "%$search%");
            });
        }

        return $query->orderByRaw("CASE WHEN role = 'admin' THEN 1 WHEN role = 'teacher' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->limit(15) // Show fewer accounts initially // Show fewer accounts initially
            ->get();
}

    public function updatedSearch(): void
    {
        $users = $this->getUsers();
        if (!empty($this->search) && $users->isNotEmpty()) {
            $this->selectUser($users->first()->id);
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
            ->components([
                Section::make('Profile Information')
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
                    ->schema([
                        TextInput::make('current_password')
                            ->password()
                            ->label('Current Password'),
                        TextInput::make('password')
                            ->password()
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
