<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Quiz_attempt;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class StudentStatsWidget extends BaseWidget
{
    protected static ?string $heading = 'Student Overall Analytics';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('role', 'student')
                    ->orderByDesc('created_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Student Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('id')
                    ->label('Total Attempts')
                    ->formatStateUsing(function ($record) {
                        return Quiz_attempt::where('student_id', $record->id)->count();
                    }),

                Tables\Columns\TextColumn::make('id')
                    ->label('Avg Score')
                    ->formatStateUsing(function ($record) {
                        $avg = Quiz_attempt::where('student_id', $record->id)
                            ->where('status', 'submitted')
                            ->where('attempt_type', Quiz_attempt::TYPE_GRADED)
                            ->avg('score_percent') ?? 0;
                        return round($avg, 2) . '%';
                    })
                    ->color(function ($record) {
                        $avg = Quiz_attempt::where('student_id', $record->id)
                            ->where('status', 'submitted')
                            ->where('attempt_type', Quiz_attempt::TYPE_GRADED)
                            ->avg('score_percent') ?? 0;
                        return $avg >= 70 ? 'success' : ($avg >= 50 ? 'warning' : 'danger');
                    }),
            ]);
    }
}
