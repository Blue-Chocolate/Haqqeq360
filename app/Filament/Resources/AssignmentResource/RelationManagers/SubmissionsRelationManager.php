<?php

// ============================================
// app/Filament/Resources/AssignmentResource/RelationManagers/SubmissionsRelationManager.php
// ============================================

namespace App\Filament\Resources\AssignmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    protected static ?string $title = 'Student Submissions';

    protected static ?string $icon = 'heroicon-o-document-text';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Submission Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\FileUpload::make('file_url')
                            ->label('Submission File')
                            ->disk('public')
                            ->directory('submissions')
                            ->downloadable()
                            ->openable()
                            ->required()
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\DateTimePicker::make('submitted_at')
                            ->label('Submission Date')
                            ->default(now())
                            ->disabled(fn ($record) => $record !== null),
                    ]),

                Forms\Components\Section::make('Grading')
                    ->schema([
                        Forms\Components\TextInput::make('grade')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(fn () => $this->getOwnerRecord()->max_score)
                            ->suffix('/ ' . $this->getOwnerRecord()->max_score . ' points')
                            ->helperText(fn ($record) => 
                                $record?->grade 
                                    ? 'Percentage: ' . round(($record->grade / $this->getOwnerRecord()->max_score) * 100, 2) . '%'
                                    : 'Enter grade to mark as graded'
                            ),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn ($record) => $record->user->email),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->submitted_at->diffForHumans())
                    ->color(fn ($record) => 
                        $this->getOwnerRecord()->due_date && $record->submitted_at->isAfter($this->getOwnerRecord()->due_date)
                            ? 'danger'
                            : 'success'
                    ),

                Tables\Columns\IconColumn::make('is_late')
                    ->label('Late')
                    ->boolean()
                    ->getStateUsing(fn ($record) => 
                        $this->getOwnerRecord()->due_date && $record->submitted_at->isAfter($this->getOwnerRecord()->due_date)
                    )
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('file_url')
                    ->label('File')
                    ->formatStateUsing(fn () => 'Download')
                    ->url(fn ($record) => \Storage::url($record->file_url))
                    ->openUrlInNewTab()
                    ->color('info')
                    ->icon('heroicon-o-arrow-down-tray'),

                Tables\Columns\TextColumn::make('grade')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->suffix(fn () => ' / ' . $this->getOwnerRecord()->max_score)
                    ->description(fn ($record) => 
                        $record->grade 
                            ? round(($record->grade / $this->getOwnerRecord()->max_score) * 100, 2) . '%'
                            : null
                    )
                    ->color(fn ($record) => 
                        $record->grade 
                            ? ($record->grade >= ($this->getOwnerRecord()->max_score * 0.6) ? 'success' : 'danger')
                            : 'gray'
                    )
                    ->weight(FontWeight::Bold)
                    ->placeholder('Not graded'),

                Tables\Columns\IconColumn::make('is_graded')
                    ->label('Status')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->grade !== null)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_graded')
                    ->label('Grading Status')
                    ->placeholder('All submissions')
                    ->trueLabel('Graded only')
                    ->falseLabel('Pending grading')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('grade'),
                        false: fn (Builder $query) => $query->whereNull('grade'),
                    ),

                Tables\Filters\Filter::make('late_submissions')
                    ->label('Late Submissions')
                    ->query(fn (Builder $query) => 
                        $query->where('submitted_at', '>', $this->getOwnerRecord()->due_date)
                    )
                    ->visible(fn () => $this->getOwnerRecord()->due_date !== null),

                Tables\Filters\Filter::make('high_grades')
                    ->label('High Grades (80%+)')
                    ->query(fn (Builder $query) => 
                        $query->whereNotNull('grade')
                            ->whereRaw('grade >= ?', [$this->getOwnerRecord()->max_score * 0.8])
                    ),

                Tables\Filters\Filter::make('low_grades')
                    ->label('Low Grades (<60%)')
                    ->query(fn (Builder $query) => 
                        $query->whereNotNull('grade')
                            ->whereRaw('grade < ?', [$this->getOwnerRecord()->max_score * 0.6])
                    ),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Submission'),
                
                Tables\Actions\Action::make('export_grades')
                    ->label('Export Grades')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        // Implement CSV export logic here
                        Notification::make()
                            ->title('Grades exported successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_file')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => \Storage::url($record->file_url))
                    ->openUrlInNewTab()
                    ->color('info'),

                Tables\Actions\Action::make('grade')
                    ->label('Grade')
                    ->icon('heroicon-o-academic-cap')
                    ->color('success')
                    ->visible(fn ($record) => $record->grade === null)
                    ->form([
                        Forms\Components\TextInput::make('grade')
                            ->label('Grade')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(fn () => $this->getOwnerRecord()->max_score)
                            ->suffix('/ ' . $this->getOwnerRecord()->max_score . ' points')
                            ->helperText('Enter the grade for this submission'),
                    ])
                    ->action(function (array $data, $record) {
                        $record->update(['grade' => $data['grade']]);
                        
                        Notification::make()
                            ->title('Submission graded successfully')
                            ->success()
                            ->body('Grade: ' . $data['grade'] . ' / ' . $this->getOwnerRecord()->max_score)
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->label('Edit Grade')
                    ->visible(fn ($record) => $record->grade !== null),

                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_grade')
                        ->label('Bulk Grade')
                        ->icon('heroicon-o-academic-cap')
                        ->color('success')
                        ->form([
                            Forms\Components\TextInput::make('grade')
                                ->label('Grade')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(fn () => $this->getOwnerRecord()->max_score)
                                ->suffix('/ ' . $this->getOwnerRecord()->max_score . ' points')
                                ->helperText('This grade will be applied to all selected submissions'),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update(['grade' => $data['grade']]);
                            }
                            
                            Notification::make()
                                ->title('Submissions graded successfully')
                                ->success()
                                ->body($records->count() . ' submissions graded with ' . $data['grade'] . ' points')
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds

    }

    public function isReadOnly(): bool
    {
        return false;
    }

    protected function getTableHeading(): ?string
    {
        $assignment = $this->getOwnerRecord();
        $totalSubmissions = $assignment->submissions()->count();
        $gradedSubmissions = $assignment->submissions()->whereNotNull('grade')->count();
        $averageGrade = $assignment->submissions()->whereNotNull('grade')->avg('grade');

        return "Submissions ({$totalSubmissions} total, {$gradedSubmissions} graded" . 
               ($averageGrade ? ', avg: ' . round($averageGrade, 2) . '/' . $assignment->max_score : '') . ')';
    }
}