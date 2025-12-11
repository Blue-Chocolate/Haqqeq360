<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentResource\Pages;
use App\Filament\Resources\AssignmentResource\RelationManagers\SubmissionsRelationManager;
use App\Models\Assignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Academic';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Assignment Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('description')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('due_date')
                            ->native(false)
                            ->displayFormat('Y-m-d H:i')
                            ->seconds(false)
                            ->nullable(),

                        Forms\Components\TextInput::make('max_score')
                            ->required()
                            ->numeric()
                            ->default(100)
                            ->minValue(0)
                            ->maxValue(1000)
                            ->suffix('points'),

                        Forms\Components\Toggle::make('published')
                            ->default(true)
                            ->helperText('Only published assignments are visible to students')
                            ->inline(false),
                    ])->columns(2),

                Forms\Components\Section::make('Course Structure')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->relationship('course', 'title')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('unit_id', null)),

                        Forms\Components\Select::make('unit_id')
                            ->relationship('unit', 'title', fn (Builder $query, callable $get) => 
                                $query->where('course_id', $get('course_id'))
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('lesson_id', null)),

                        Forms\Components\Select::make('lesson_id')
                            ->relationship('lesson', 'title', fn (Builder $query, callable $get) => 
                                $query->where('unit_id', $get('unit_id'))
                            )
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])->columns(3),

                Forms\Components\Section::make('Attachment')
                    ->schema([
                        Forms\Components\FileUpload::make('attachment_path')
                            ->label('Assignment File/Instructions')
                            ->disk('public')
                            ->directory('assignments')
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'image/*'])
                            ->maxSize(10240) // 10MB
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (Assignment $record): string => 
                        $record->course->title . ' > ' . $record->unit->title
                    ),

                Tables\Columns\TextColumn::make('lesson.title')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->description(fn (Assignment $record): ?string => 
                        $record->due_date 
                            ? (now()->isAfter($record->due_date) ? 'Overdue' : 'Due in ' . now()->diffForHumans($record->due_date, true))
                            : null
                    )
                    ->color(fn (Assignment $record): string => 
                        $record->due_date && now()->isAfter($record->due_date) ? 'danger' : 'success'
                    ),

                Tables\Columns\TextColumn::make('max_score')
                    ->numeric()
                    ->sortable()
                    ->suffix(' pts')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('submissions_count')
                    ->counts('submissions')
                    ->label('Submissions')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('graded_submissions_count')
                    ->label('Graded')
                    ->badge()
                    ->color('success')
                    ->alignCenter()
                    ->getStateUsing(fn (Assignment $record): int => 
                        $record->submissions()->whereNotNull('grade')->count()
                    ),

                Tables\Columns\IconColumn::make('published')
                    ->boolean()
                    ->sortable()
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
                Tables\Filters\SelectFilter::make('course')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('unit')
                    ->relationship('unit', 'title')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('published')
                    ->label('Published Status')
                    ->boolean()
                    ->trueLabel('Published only')
                    ->falseLabel('Unpublished only')
                    ->native(false),

                Tables\Filters\Filter::make('overdue')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('due_date', '<', now())
                    )
                    ->label('Overdue Assignments'),

                Tables\Filters\Filter::make('upcoming')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('due_date', '>', now())
                            ->where('due_date', '<', now()->addWeek())
                    )
                    ->label('Due This Week'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_date', 'asc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Assignment Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight(FontWeight::Bold),

                        Infolists\Components\TextEntry::make('description')
                            ->html()
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('course.title')
                            ->label('Course'),

                        Infolists\Components\TextEntry::make('unit.title')
                            ->label('Unit'),

                        Infolists\Components\TextEntry::make('lesson.title')
                            ->label('Lesson'),

                        Infolists\Components\TextEntry::make('due_date')
                            ->dateTime('M d, Y H:i')
                            ->badge()
                            ->color(fn (Assignment $record): string => 
                                $record->due_date && now()->isAfter($record->due_date) ? 'danger' : 'success'
                            ),

                        Infolists\Components\TextEntry::make('max_score')
                            ->suffix(' points'),

                        Infolists\Components\IconEntry::make('published')
                            ->boolean(),
                    ])->columns(3),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('submissions_count')
                            ->label('Total Submissions')
                            ->getStateUsing(fn (Assignment $record): int => 
                                $record->submissions()->count()
                            ),

                        Infolists\Components\TextEntry::make('graded_count')
                            ->label('Graded Submissions')
                            ->getStateUsing(fn (Assignment $record): int => 
                                $record->submissions()->whereNotNull('grade')->count()
                            ),

                        Infolists\Components\TextEntry::make('pending_count')
                            ->label('Pending Grading')
                            ->getStateUsing(fn (Assignment $record): int => 
                                $record->submissions()->whereNull('grade')->count()
                            ),

                        Infolists\Components\TextEntry::make('average_grade')
                            ->label('Average Grade')
                            ->getStateUsing(fn (Assignment $record): string => 
                                number_format($record->submissions()->whereNotNull('grade')->avg('grade') ?? 0, 2) . ' / ' . $record->max_score
                            ),
                    ])->columns(4),

                Infolists\Components\Section::make('Attachment')
                    ->schema([
                        Infolists\Components\TextEntry::make('attachment_path')
                            ->label('File')
                            ->url(fn (Assignment $record): ?string => 
                                $record->attachment_path ? \Storage::url($record->attachment_path) : null
                            )
                            ->openUrlInNewTab()
                            ->placeholder('No attachment'),
                    ])
                    ->visible(fn (Assignment $record): bool => $record->attachment_path !== null),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SubmissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'view' => Pages\ViewAssignment::route('/{record}'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('published', true)
            ->where('due_date', '>', now())
            ->where('due_date', '<', now()->addWeek())
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}