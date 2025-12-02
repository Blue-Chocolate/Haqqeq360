<?php

namespace App\Filament\Resources\TestResource\RelationManagers;

use App\Enums\QuestionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $recordTitleAttribute = 'question_text';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Question Type')
                    ->options(QuestionType::options())
                    ->required()
                    ->live()
                    ->default(QuestionType::MCQ->value)
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('options', [])),

                Forms\Components\Textarea::make('question_text')
                    ->label('Question')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('explanation')
                    ->label('Explanation (Optional)')
                    ->helperText('Shown to students after answering')
                    ->rows(2)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('points')
                    ->label('Points')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(0.01),

                Forms\Components\TextInput::make('order')
                    ->label('Order')
                    ->numeric()
                    ->default(0)
                    ->helperText('Used to sort questions'),

                Forms\Components\Toggle::make('is_required')
                    ->label('Required')
                    ->default(true),

                // Options for MCQ and True/False
                Forms\Components\Repeater::make('options')
                    ->relationship('options')
                    ->schema([
                        Forms\Components\TextInput::make('option_text')
                            ->label('Option Text')
                            ->required()
                            ->columnSpan(2),

                        Forms\Components\Toggle::make('is_correct')
                            ->label('Correct Answer')
                            ->default(false)
                            ->columnSpan(1),

                        Forms\Components\Hidden::make('order')
                            ->default(0),
                    ])
                    ->columns(3)
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['option_text'] ?? null)
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question_text')
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (QuestionType $state): string => $state->label())
                    ->color(fn (QuestionType $state): string => match ($state) {
                        QuestionType::MCQ => 'info',
                        QuestionType::TRUE_FALSE => 'success',
                        QuestionType::WRITTEN => 'warning',
                    }),

                Tables\Columns\TextColumn::make('question_text')
                    ->label('Question')
                    ->limit(50)
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('options_count')
                    ->counts('options')
                    ->label('Options')
                    ->badge()
                    ->color('gray')
                    ->visible(fn ($record) => $record && $record->type !== QuestionType::WRITTEN),

                Tables\Columns\TextColumn::make('points')
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_required')
                    ->boolean()
                    ->label('Required'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(QuestionType::options()),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Auto-assign order based on existing questions count
                        if (!isset($data['order']) || $data['order'] === 0) {
                            $data['order'] = $this->getOwnerRecord()->questions()->max('order') + 1;
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('order')
            ->defaultSort('order');
    }
}