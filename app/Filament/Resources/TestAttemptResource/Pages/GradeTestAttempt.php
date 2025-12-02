<?php

namespace App\Filament\Resources\TestAttemptResource\Pages;

use App\Enums\QuestionType;
use App\Enums\TestAttemptStatus;
use App\Filament\Resources\TestAttemptResource;
use App\Models\TestAnswer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class GradeTestAttempt extends EditRecord
{
    protected static string $resource = TestAttemptResource::class;

    protected static ?string $title = 'Grade Test Submission';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Student Information')
                    ->schema([
                        Forms\Components\Placeholder::make('student_name')
                            ->label('Student')
                            ->content(fn ($record) => $record->user->name),

                        Forms\Components\Placeholder::make('test_name')
                            ->label('Test')
                            ->content(fn ($record) => $record->test->title),

                        Forms\Components\Placeholder::make('submitted')
                            ->label('Submitted At')
                            ->content(fn ($record) => $record->submitted_at?->format('M d, Y h:i A')),

                        Forms\Components\Placeholder::make('current_score')
                            ->label('Current Score')
                            ->content(fn ($record) => 
                                $record->percentage !== null 
                                    ? number_format($record->percentage, 2) . '%' 
                                    : 'Not graded'
                            ),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Grade Answers')
                    ->description('Grade written answers and review auto-graded questions')
                    ->schema([
                        Forms\Components\Repeater::make('answers')
                            ->relationship('answers')
                            ->schema([
                                Forms\Components\Placeholder::make('question_info')
                                    ->label('Question')
                                    ->content(function ($record) {
                                        if (!$record) return '';
                                        $type = $record->question->type->label();
                                        $points = $record->question->points;
                                        return "{$record->question->question_text} ({$type} - {$points} points)";
                                    })
                                    ->columnSpanFull(),

                                // For MCQ and True/False - Show selected answer (read-only)
                                Forms\Components\Placeholder::make('selected_answer')
                                    ->label('Selected Answer')
                                    ->content(function ($record) {
                                        if (!$record || !$record->selectedOption) return 'No answer';
                                        $correct = $record->question->getCorrectOption();
                                        $isCorrect = $record->selected_option_id === $correct?->id;
                                        $icon = $isCorrect ? '✓' : '✗';
                                        return "{$icon} {$record->selectedOption->option_text}";
                                    })
                                    ->visible(fn ($record) => 
                                        $record && $record->question->type !== QuestionType::WRITTEN
                                    ),

                                Forms\Components\Placeholder::make('correct_answer')
                                    ->label('Correct Answer')
                                    ->content(fn ($record) => 
                                        $record?->question->getCorrectOption()?->option_text ?? 'N/A'
                                    )
                                    ->visible(fn ($record) => 
                                        $record && $record->question->type !== QuestionType::WRITTEN
                                    ),

                                // For Written answers
                                Forms\Components\Textarea::make('written_answer')
                                    ->label('Student Answer')
                                    ->disabled()
                                    ->visible(fn ($record) => 
                                        $record && $record->question->type === QuestionType::WRITTEN
                                    )
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('points_earned')
                                    ->label('Points Earned')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(fn ($record) => $record?->question->points ?? 100)
                                    ->helperText(fn ($record) => 
                                        "Maximum: {$record?->question->points} points"
                                    )
                                    ->disabled(fn ($record) => 
                                        $record && $record->question->type !== QuestionType::WRITTEN
                                    )
                                    ->default(fn ($record) => $record?->points_earned),

                                Forms\Components\Textarea::make('feedback')
                                    ->label('Feedback (Optional)')
                                    ->rows(2)
                                    ->visible(fn ($record) => 
                                        $record && $record->question->type === QuestionType::WRITTEN
                                    )
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->reorderable(false)
                            ->addable(false)
                            ->deletable(false)
                            ->collapsible()
                            ->itemLabel(fn ($state) => 
                                isset($state['question_info']) 
                                    ? strip_tags($state['question_info']) 
                                    : 'Question'
                            ),
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Update each answer's grading
        if (isset($data['answers'])) {
            foreach ($data['answers'] as $answerData) {
                $answer = TestAnswer::find($answerData['id']);
                
                if ($answer && $answer->question->type === QuestionType::WRITTEN) {
                    $answer->manualGrade(
                        points: $answerData['points_earned'] ?? 0,
                        feedback: $answerData['feedback'] ?? null,
                        gradedBy: auth()->id()
                    );
                }
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Recalculate the test attempt score
        $this->record->calculateScore();
        
        // Update status to graded
        $this->record->update([
            'status' => TestAttemptStatus::GRADED,
            'graded_at' => now(),
        ]);

        Notification::make()
            ->title('Test graded successfully')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Save Grades')
                ->icon('heroicon-o-check'),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}