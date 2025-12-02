<?php

namespace App\Filament\Resources;

use App\Enums\QuestionType;
use App\Filament\Resources\TestResource\Pages;
use App\Filament\Resources\TestResource\RelationManagers\QuestionsRelationManager;
use App\Models\Test;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TestResource extends Resource
{
    protected static ?string $model = Test::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationGroup = 'Testing';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    
                    // Step 1: Basic Information
                    Forms\Components\Wizard\Step::make('Basic Information')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            
                            Forms\Components\Textarea::make('description')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),

                    // Step 2: Assignment
                    Forms\Components\Wizard\Step::make('Assignment')
                        ->schema([
                            Forms\Components\Select::make('testable_type')
                                ->label('Assign To')
                                ->options([
                                    'App\\Models\\Bootcamp' => 'Bootcamp',
                                    'App\\Models\\Workshop' => 'Workshop',
                                    'App\\Models\\Program' => 'Program',
                                    'App\\Models\\Course' => 'Course',
                                ])
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('testable_id', null))
                                ->columnSpanFull(),

                            Forms\Components\Select::make('testable_id')
                                ->label('Select Item')
                                ->options(function (Forms\Get $get) {
                                    $type = $get('testable_type');
                                    if (!$type || !class_exists($type)) {
                                        return [];
                                    }
                                    return $type::pluck('title', 'id')->toArray();
                                })
                                ->required()
                                ->searchable()
                                ->preload()
                                ->visible(fn (Forms\Get $get) => filled($get('testable_type')))
                                ->columnSpanFull(),
                        ]),

                    // Step 3: Questions
                    Forms\Components\Wizard\Step::make('Questions')
                        ->schema([
                            Forms\Components\Repeater::make('questions')
                                ->relationship('questions')
                                ->schema([
                                    Forms\Components\Select::make('type')
                                        ->label('Question Type')
                                        ->options(QuestionType::options())
                                        ->required()
                                        ->live()
                                        ->default(QuestionType::MCQ->value)
                                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                                            // Reset options when type changes
                                            if ($state === QuestionType::TRUE_FALSE->value) {
                                                $set('options', [
                                                    ['option_text' => 'True', 'is_correct' => false, 'order' => 0],
                                                    ['option_text' => 'False', 'is_correct' => false, 'order' => 1],
                                                ]);
                                            } elseif ($state === QuestionType::WRITTEN->value) {
                                                $set('options', []);
                                            }
                                        })
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('points')
                                        ->label('Points')
                                        ->numeric()
                                        ->required()
                                        ->default(1)
                                        ->minValue(0.01)
                                        ->columnSpan(1),

                                    Forms\Components\Textarea::make('question_text')
                                        ->label('Question')
                                        ->required()
                                        ->rows(2)
                                        ->columnSpanFull(),

                                    Forms\Components\Textarea::make('explanation')
                                        ->label('Explanation (Optional)')
                                        ->helperText('Shown to students after answering')
                                        ->rows(2)
                                        ->columnSpanFull(),

                                    // Options for MCQ and True/False
                                    Forms\Components\Repeater::make('options')
                                        ->schema([
                                            Forms\Components\TextInput::make('option_text')
                                                ->label('Option')
                                                ->required()
                                                ->columnSpan(2),

                                            Forms\Components\Toggle::make('is_correct')
                                                ->label('Correct')
                                                ->default(false)
                                                ->columnSpan(1),
                                        ])
                                        ->columns(3)
                                        ->reorderable()
                                        ->addActionLabel('Add Option')
                                        ->visible(fn (Forms\Get $get): bool => 
                                            in_array($get('type'), [QuestionType::MCQ->value, QuestionType::TRUE_FALSE->value])
                                        )
                                        ->minItems(fn (Forms\Get $get): int => 
                                            $get('type') === QuestionType::TRUE_FALSE->value ? 2 : 2
                                        )
                                        ->maxItems(fn (Forms\Get $get): int => 
                                            $get('type') === QuestionType::TRUE_FALSE->value ? 2 : 10
                                        )
                                        ->defaultItems(fn (Forms\Get $get): int => 
                                            $get('type') === QuestionType::TRUE_FALSE->value ? 2 : 4
                                        )
                                        ->default(fn (Forms\Get $get): array => 
                                            $get('type') === QuestionType::TRUE_FALSE->value 
                                                ? [
                                                    ['option_text' => 'True', 'is_correct' => false, 'order' => 0],
                                                    ['option_text' => 'False', 'is_correct' => false, 'order' => 1],
                                                ]
                                                : []
                                        )
                                        ->columnSpanFull(),

                                    Forms\Components\Hidden::make('order')
                                        ->default(0),

                                    Forms\Components\Toggle::make('is_required')
                                        ->label('Required')
                                        ->default(true)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->reorderable('order')
                                ->collapsible()
                                ->cloneable()
                                ->itemLabel(fn (array $state): ?string => 
                                    $state['question_text'] ?? 'New Question'
                                )
                                ->addActionLabel('Add Question')
                                ->defaultItems(1)
                                ->columnSpanFull()
                                ->mutateRelationshipDataBeforeCreateUsing(function (array $data, Forms\Get $get): array {
                                    // Auto-assign order
                                    if (!isset($data['order']) || $data['order'] === 0) {
                                        $existingQuestions = $get('../../questions') ?? [];
                                        $data['order'] = count($existingQuestions);
                                    }
                                    return $data;
                                }),
                        ]),

                    // Step 4: Test Settings
                    Forms\Components\Wizard\Step::make('Settings')
                        ->schema([
                            Forms\Components\Section::make('Test Settings')
                                ->schema([
                                    Forms\Components\TextInput::make('duration_minutes')
                                        ->label('Duration (Minutes)')
                                        ->numeric()
                                        ->minValue(1)
                                        ->suffix('minutes')
                                        ->helperText('Leave empty for no time limit'),

                                    Forms\Components\TextInput::make('passing_score')
                                        ->label('Passing Score (%)')
                                        ->required()
                                        ->numeric()
                                        ->default(50)
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->suffix('%'),

                                    Forms\Components\TextInput::make('max_attempts')
                                        ->label('Maximum Attempts')
                                        ->required()
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1),

                                    Forms\Components\Toggle::make('shuffle_questions')
                                        ->label('Shuffle Questions')
                                        ->helperText('Randomize question order for each student'),

                                    Forms\Components\Toggle::make('show_correct_answers')
                                        ->label('Show Correct Answers')
                                        ->default(true)
                                        ->helperText('Show correct answers after submission'),

                                    Forms\Components\Toggle::make('show_results_immediately')
                                        ->label('Show Results Immediately')
                                        ->default(true)
                                        ->helperText('Display score immediately after submission'),
                                ])
                                ->columns(3),

                            Forms\Components\Section::make('Availability')
                                ->schema([
                                    Forms\Components\DateTimePicker::make('available_from')
                                        ->label('Available From')
                                        ->helperText('Leave empty to make available immediately'),

                                    Forms\Components\DateTimePicker::make('available_until')
                                        ->label('Available Until')
                                        ->helperText('Leave empty for no end date'),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true)
                                        ->helperText('Inactive tests are hidden from students'),
                                ])
                                ->columns(3),
                        ]),
                ])
                ->columnSpanFull()
                ->skippable()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('testable_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('testable.title')
                    ->label('Assigned To')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('questions_count')
                    ->counts('questions')
                    ->label('Questions')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('attempts_count')
                    ->counts('attempts')
                    ->label('Attempts')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->formatStateUsing(fn (?int $state): string => $state ? "{$state} min" : 'No limit')
                    ->sortable(),

                Tables\Columns\TextColumn::make('passing_score')
                    ->label('Pass %')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('testable_type')
                    ->label('Type')
                    ->options([
                        'App\\Models\\Bootcamp' => 'Bootcamp',
                        'App\\Models\\Workshop' => 'Workshop',
                        'App\\Models\\Program' => 'Program',
                        'App\\Models\\Course' => 'Course',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTests::route('/'),
            'create' => Pages\CreateTest::route('/create'),
            'edit' => Pages\EditTest::route('/{record}/edit'),
            'view' => Pages\ViewTest::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}