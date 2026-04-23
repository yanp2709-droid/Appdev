<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Hash;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set role to student
        $data['role'] = 'student';
        // Set the required 'name' field
        $data['name'] = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        // Set a default password (hashed)
        $data['password'] = Hash::make('password');
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        // Redirect to the students table after creation
        return StudentResource::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Success!')
            ->body('Student account created successfully!');
    }
}
