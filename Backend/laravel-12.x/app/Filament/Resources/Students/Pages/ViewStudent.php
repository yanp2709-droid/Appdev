<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Personal Information')
                    ->description('Basic account and profile details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Full Name')
                            ->weight(FontWeight::Bold)
                            ->columnSpanFull(),

                        TextEntry::make('email')
                            ->label('Email Address')
                            ->copyable()
                            ->icon('heroicon-m-envelope'),

                        TextEntry::make('student_id')
                            ->label('Student ID')
                            ->icon('heroicon-m-identification')
                            ->default('N/A'),

                        TextEntry::make('section')
                            ->label('Section')
                            ->icon('heroicon-m-squares-2x2')
                            ->default('N/A'),

                        TextEntry::make('year_level')
                            ->label('Year Level')
                            ->icon('heroicon-m-academic-cap')
                            ->default('N/A'),

                        TextEntry::make('course')
                            ->label('Course')
                            ->icon('heroicon-m-book-open')
                            ->default('N/A'),

                        TextEntry::make('email_verified_at')
                            ->label('Email Status')
                            ->badge()
                            ->state(function (User $record): string {
                                return $record->email_verified_at ? 'Verified' : 'Unverified';
                            })
                            ->color(fn (User $record): string => $record->email_verified_at ? 'success' : 'warning')
                            ->columnSpanFull(),

                        TextEntry::make('created_at')
                            ->label('Account Created')
                            ->dateTime()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
