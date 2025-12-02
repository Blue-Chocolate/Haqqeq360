<?php 
namespace App\Filament\Resources\TestAttemptResource\Pages;

use App\Filament\Resources\TestAttemptResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewTestAttempt extends ViewRecord
{
    protected static string $resource = TestAttemptResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Test Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('test.title')
                            ->label('Test'),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Student'),
                        Infolists\Components\TextEntry::make('attempt_number')
                            ->label('Attempt #')
                            ->badge(),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->label())
                            ->color(fn ($state) => $state->color()),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Score')
                    ->schema([
                        Infolists\Components\TextEntry::make('score')
                            ->formatStateUsing(fn ($state, $record) => 
                                $state !== null ? "{$state} / {$record->total_points}" : 'Not graded'
                            ),
                        Infolists\Components\TextEntry::make('percentage')
                            ->suffix('%')
                            ->formatStateUsing(fn ($state) => 
                                $state !== null ? number_format($state, 2) : 'N/A'
                            ),
                        Infolists\Components\IconEntry::make('passed')
                            ->boolean(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('started_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('submitted_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('graded_at')
                            ->dateTime(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Answers')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('answers')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('question.question_text')
                                    ->label('Question')
                                    ->columnSpanFull(),
                                Infolists\Components\TextEntry::make('selectedOption.option_text')
                                    ->label('Selected Answer')
                                    ->visible(fn ($record) => $record->selected_option_id !== null),
                                Infolists\Components\TextEntry::make('written_answer')
                                    ->label('Written Answer')
                                    ->visible(fn ($record) => $record->written_answer !== null)
                                    ->columnSpanFull(),
                                Infolists\Components\IconEntry::make('is_correct')
                                    ->label('Correct')
                                    ->boolean(),
                                Infolists\Components\TextEntry::make('points_earned')
                                    ->label('Points')
                                    ->formatStateUsing(fn ($state, $record) => 
                                        $state !== null ? "{$state} / {$record->question->points}" : 'Not graded'
                                    ),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }
}