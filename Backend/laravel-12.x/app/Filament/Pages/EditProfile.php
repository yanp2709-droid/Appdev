<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class EditProfile extends Page
{
    use InteractsWithForms;

    protected const DEFAULT_TEACHER_PROFILE = [
        'position' => 'Information Technology Instructor',
        'subjects_teaching' => [
            'Web Development',
            'Database Management Systems',
            'Object-Oriented Programming',
            'Computer Networks',
        ],
        'it_specialization' => 'Full Stack Web Development and Systems Analysis',
        'educational_background' => 'Master of Science in Information Technology, BS Information Technology',
        'skills_technologies' => [
            'PHP',
            'Laravel',
            'Flutter',
            'REST API Design',
            'MySQL',
            'Git',
        ],
        'certifications' => [
            'Cisco Networking Essentials',
            'Oracle Database Foundations',
            'AWS Cloud Practitioner',
        ],
        'years_experience' => 6,
        'professional_summary' => 'Passionate IT educator focused on practical software engineering, database design, and modern application development for classroom and capstone work.',
        'contact_info' => 'teacher@techquiz.edu | +63 912 345 6789',
        'office_schedule' => 'Mon-Wed 1:00 PM - 4:00 PM, Fri 9:00 AM - 12:00 PM',
        'department' => 'College of Computer Studies',
        'programming_languages' => [
            'PHP',
            'Dart',
            'JavaScript',
            'Java',
            'Python',
        ],
        'frameworks_tools' => [
            'Laravel',
            'Flutter',
            'Bootstrap',
            'Postman',
            'Docker',
        ],
        'database_experience' => [
            'MySQL',
            'SQLite',
            'PostgreSQL',
        ],
        'software_expertise' => [
            'Learning Management Systems',
            'Capstone Advising',
            'System Documentation',
            'UI Prototyping',
        ],
        'research_interests' => 'Educational technology, applied web systems, student analytics, and secure API-driven applications.',
        'current_projects' => 'Faculty quiz analytics dashboard, teacher profile management, and backend API improvements for assessment workflows.',
        'achievements' => [
            'Handled BSIT major subjects across multiple academic years',
            'Guided student teams in web and mobile capstone projects',
            'Built classroom-ready assessment and analytics tools',
        ],
        'portfolio_links' => [
            'https://github.com/techquiz-faculty',
            'https://portfolio.techquiz.edu/faculty',
        ],
    ];

    protected static bool $shouldRegisterNavigation = false;

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
            $this->ensureTeacherProfileDefaults(auth()->user());
            $this->data = [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'role' => auth()->user()->role,
            ];
            $this->fillFormData(auth()->user());
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

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
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

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%");
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
        $this->ensureTeacherProfileDefaults($user);
        $this->fillFormData($user);
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
                Section::make('Teacher Profile')
                    ->columnSpan(1)
                    ->hidden(fn (): bool => ! $this->isTeacherProfileContext())
                    ->schema([
                        TextInput::make('position')
                            ->label('Position')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('department')
                            ->label('Department')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('it_specialization')
                            ->label('IT Specialization')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('years_experience')
                            ->label('Years of Experience')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        TextInput::make('contact_info')
                            ->label('Contact Information')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('office_schedule')
                            ->label('Office Schedule')
                            ->required()
                            ->maxLength(255),
                        TagsInput::make('subjects_teaching')
                            ->label('Subjects Teaching')
                            ->required(),
                        TagsInput::make('programming_languages')
                            ->label('Programming Languages')
                            ->required(),
                        TagsInput::make('frameworks_tools')
                            ->label('Frameworks and Tools')
                            ->required(),
                        TagsInput::make('database_experience')
                            ->label('Database Experience')
                            ->required(),
                        TagsInput::make('skills_technologies')
                            ->label('Skills and Technologies')
                            ->required(),
                        TagsInput::make('software_expertise')
                            ->label('Software Expertise')
                            ->required(),
                        TagsInput::make('certifications')
                            ->label('Certifications')
                            ->required(),
                        TagsInput::make('achievements')
                            ->label('Achievements')
                            ->required(),
                        TagsInput::make('portfolio_links')
                            ->label('Portfolio Links')
                            ->required(),
                        Textarea::make('educational_background')
                            ->label('Educational Background')
                            ->required()
                            ->rows(3),
                        Textarea::make('professional_summary')
                            ->label('Professional Summary')
                            ->required()
                            ->rows(4),
                        Textarea::make('research_interests')
                            ->label('Research Interests')
                            ->required()
                            ->rows(3),
                        Textarea::make('current_projects')
                            ->label('Current Projects')
                            ->required()
                            ->rows(3),
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
            'password' => ['nullable', 'string', Password::min(8)->mixedCase()->numbers()->symbols()],
            'password_confirmation' => ['nullable', 'same:password'],
        ];

        if ($targetUser->isTeacher()) {
            $rules = array_merge($rules, [
                'position' => ['required', 'string', 'max:255'],
                'department' => ['required', 'string', 'max:255'],
                'it_specialization' => ['required', 'string', 'max:255'],
                'years_experience' => ['required', 'integer', 'min:0'],
                'contact_info' => ['required', 'string', 'max:255'],
                'office_schedule' => ['required', 'string', 'max:255'],
                'subjects_teaching' => ['required', 'array', 'min:1'],
                'programming_languages' => ['required', 'array', 'min:1'],
                'frameworks_tools' => ['required', 'array', 'min:1'],
                'database_experience' => ['required', 'array', 'min:1'],
                'skills_technologies' => ['required', 'array', 'min:1'],
                'software_expertise' => ['required', 'array', 'min:1'],
                'certifications' => ['required', 'array', 'min:1'],
                'achievements' => ['required', 'array', 'min:1'],
                'portfolio_links' => ['required', 'array', 'min:1'],
                'educational_background' => ['required', 'string'],
                'professional_summary' => ['required', 'string'],
                'research_interests' => ['required', 'string'],
                'current_projects' => ['required', 'string'],
            ]);
        }

        if (! $this->isAdmin() || $targetUser->id === $currentUser->id) {
            $rules['current_password'][] = 'required_with:password';
        }

        $validated = Validator::make($data, $rules)->validate();

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if ($targetUser->isTeacher()) {
            $updateData = array_merge($updateData, [
                'position' => $validated['position'],
                'department' => $validated['department'],
                'it_specialization' => $validated['it_specialization'],
                'years_experience' => (int) $validated['years_experience'],
                'contact_info' => $validated['contact_info'],
                'office_schedule' => $validated['office_schedule'],
                'subjects_teaching' => array_values($validated['subjects_teaching']),
                'programming_languages' => array_values($validated['programming_languages']),
                'frameworks_tools' => array_values($validated['frameworks_tools']),
                'database_experience' => array_values($validated['database_experience']),
                'skills_technologies' => array_values($validated['skills_technologies']),
                'software_expertise' => array_values($validated['software_expertise']),
                'certifications' => array_values($validated['certifications']),
                'achievements' => array_values($validated['achievements']),
                'portfolio_links' => array_values($validated['portfolio_links']),
                'educational_background' => $validated['educational_background'],
                'professional_summary' => $validated['professional_summary'],
                'research_interests' => $validated['research_interests'],
                'current_projects' => $validated['current_projects'],
            ]);
        }

        $targetUser->update($updateData);

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

    protected function fillFormData(User $user): void
    {
        $this->data = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'position' => $user->position,
            'department' => $user->department,
            'it_specialization' => $user->it_specialization,
            'years_experience' => $user->years_experience,
            'contact_info' => $user->contact_info,
            'office_schedule' => $user->office_schedule,
            'subjects_teaching' => $user->subjects_teaching ?? [],
            'programming_languages' => $user->programming_languages ?? [],
            'frameworks_tools' => $user->frameworks_tools ?? [],
            'database_experience' => $user->database_experience ?? [],
            'skills_technologies' => $user->skills_technologies ?? [],
            'software_expertise' => $user->software_expertise ?? [],
            'certifications' => $user->certifications ?? [],
            'achievements' => $user->achievements ?? [],
            'portfolio_links' => $user->portfolio_links ?? [],
            'educational_background' => $user->educational_background,
            'professional_summary' => $user->professional_summary,
            'research_interests' => $user->research_interests,
            'current_projects' => $user->current_projects,
        ];

        $this->form->fill($this->data);
    }

    protected function isTeacherProfileContext(): bool
    {
        if ($this->isAdmin()) {
            return $this->selectedUser?->isTeacher() ?? false;
        }

        return auth()->user()?->isTeacher() ?? false;
    }

    protected function ensureTeacherProfileDefaults(User $user): void
    {
        if (! $user->isTeacher()) {
            return;
        }

        $updates = [];

        foreach (self::DEFAULT_TEACHER_PROFILE as $field => $value) {
            $current = $user->{$field};

            if (is_array($value)) {
                if (empty($current)) {
                    $updates[$field] = $value;
                }

                continue;
            }

            if ($current === null || $current === '') {
                $updates[$field] = $value;
            }
        }

        if ($updates !== []) {
            $user->update($updates);
            $user->refresh();
        }
    }
}
