<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Recompute name from first_name and last_name
        $data['name'] = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

        // Handle password change with current password verification
        if (!empty($data['password'])) {
            // Verify current password
            if (empty($data['current_password']) || !Hash::check($data['current_password'], $this->record->password)) {
                Notification::make()
                    ->title('Incorrect Current Password')
                    ->body('The current password you entered is wrong. Please try again.')
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'current_password' => 'The current password is incorrect.',
                ]);
            }

            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // Remove non-database fields
        unset($data['current_password'], $data['passwordConfirmation']);

        return $data;
    }
}

