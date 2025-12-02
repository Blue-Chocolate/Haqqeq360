<?php

namespace App\Filament\Resources;

use App\Enums\TestAttemptStatus;
use App\Filament\Resources\TestAttemptResource\Pages;
use App\Models\TestAttempt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestAttemptResource extends Resource
{
    protected static ?string $model = TestAttempt::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    
    protected static ?string $navigationGroup = 'Testing';
    
    protected static ?string $navigationLabel = 'Test Submissions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Attempt Information')
                    ->schema([
                        Forms\Components\TextInput::make('test.title')
                            ->label('Test')
                            ->disabled(),

                        Forms\Components\TextInput::make('user.name')
                            ->label('Student')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->options(TestAttemptStatus::options())
                            ->disabled(),

                        Forms\Components\TextInput::make('attempt_number')
                            ->label('Attempt #')
                            ->disabled(),

                        Forms\Components\TextInput::make('percentage')
                            ->label('Score')
                            ->suffix('%')
                            ->disabled(),

                        Forms\Components\Toggle::make('passed')
                            ->label('Passed')
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Timestamps')
                    ->schema([
                        Forms\Components\DateTimePicker::make('started_at')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('submitted_at')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('graded_at')
                            ->disabled(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('test.title')
                    ->label('Test')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('attempt_number')
                    ->label('Attempt')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (TestAttemptStatus $state): string => $state->label())
                    ->color(fn (TestAttemptStatus $state): string => $state->color())
                    ->sortable(),

                Tables\Columns\TextColumn::make('percentage')
                    ->label('Score')
                    ->formatStateUsing(fn (?float $state): string => 
                        $state !== null ? number_format($state, 2) . '%' : 'Not graded'
                    )
                    ->badge()
                    ->color(fn (?float $state, $record): string => 
                        $state === null ? 'gray' : ($record->passed ? 'success' : 'danger')
                    )
                    ->sortable(),

                Tables\Columns\IconColumn::make('passed')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('graded_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('test_id')
                    ->relationship('test', 'title')
                    ->searchable()
                    ->preload()
                    ->label('Test'),

                Tables\Filters\SelectFilter::make('status')
                    ->options(TestAttemptStatus::options())
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('passed')
                    ->label('Passed')
                    ->boolean()
                    ->trueLabel('Passed only')
                    ->falseLabel('Failed only')
                    ->native(false),

                Tables\Filters\Filter::make('needs_grading')
                    ->label('Needs Grading')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('status', TestAttemptStatus::SUBMITTED)
                            ->whereHas('answers', function ($q) {
                                $q->whereNull('points_earned');
                            })
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('grade')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->url(fn (TestAttempt $record): string => 
                        TestAttemptResource::getUrl('grade', ['record' => $record])
                    )
                    ->visible(fn (TestAttempt $record): bool => 
                        $record->status === TestAttemptStatus::SUBMITTED
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestAttempts::route('/'),
            'view' => Pages\ViewTestAttempt::route('/{record}'),
            'grade' => Pages\GradeTestAttempt::route('/{record}/grade'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}