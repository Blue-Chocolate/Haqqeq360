<?php

namespace App\Filament\Resources\CaseStudyResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class AnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'answers';

    protected static ?string $title = 'Student Submissions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('learner_id')
                    ->relationship('learner', 'name')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\Textarea::make('answer_text')
                    ->label('Answer')
                    ->rows(5)
                    ->columnSpanFull()
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\DateTimePicker::make('submitted_at')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('learner.name')
            ->columns([
                Tables\Columns\TextColumn::make('learner.name')
                    ->label('Student Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('learner.email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('answer_text')
                    ->label('Answer Preview')
                    ->limit(50)
                    ->html()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('files_count')
                    ->counts('files')
                    ->label('Files')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('View Submission')
                    ->modalContent(fn ($record) => view(
                        'filament.resources.case-study.view-answer',
                        ['answer' => $record->load('files', 'learner')]
                    ))
                    ->modalWidth('5xl'),

                Tables\Actions\Action::make('downloadFiles')
                    ->label('Download Files')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn ($record) => $record->files_count > 0)
                    ->action(function ($record) {
                        // Download first file or implement zip download
                        $file = $record->files()->first();
                        if ($file) {
                            return Storage::disk('public')->download(
                                $file->file_path,
                                $file->original_name
                            );
                        }
                    }),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('submitted_at', 'desc');
    }
}