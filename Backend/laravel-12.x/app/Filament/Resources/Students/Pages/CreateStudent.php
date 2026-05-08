<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Services\AcademicYearService;
use Illuminate\Support\Facades\Hash;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $studentId = preg_replace('/\D+/', '', (string) ($data['student_id'] ?? ''));
        $section = trim((string) ($data['section'] ?? ''));

        if (! preg_match('/^230\d{5}$/', $studentId)) {
            throw ValidationException::withMessages([
                'student_id' => 'Student ID must be exactly 8 digits and start with 230.',
            ]);
        }

        if (! preg_match('/^AI\d{2}$/', $section)) {
            throw ValidationException::withMessages([
                'section' => 'Section must follow the AIxx pattern, such as AI33.',
            ]);
        }

        $data['role'] = 'student';
        $data['name'] = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        $data['password'] = Hash::make($data['password'] ?? 'password');
        $data['student_id'] = $studentId;
        $data['email'] = $studentId . '@lnu.edu.ph';
        $data['course'] = 'BSIT';
        $data['academic_year'] = app(AcademicYearService::class)->getSelectedAcademicYear();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
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
