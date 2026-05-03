<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\TeacherResource;
use App\Services\AcademicYearService;
use Filament\Resources\Pages\CreateRecord;

class CreateTeacher extends CreateRecord
{
    protected static string $resource = TeacherResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = 'teacher';
        $data['is_protected'] = false;
        $data['academic_year'] = app(AcademicYearService::class)->getSelectedAcademicYear();

        return $data;
    }
}
