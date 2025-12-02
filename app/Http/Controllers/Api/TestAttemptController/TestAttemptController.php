<?php

namespace App\Http\Controllers\Api\TestAttemptController;

use App\Enums\QuestionType;
use App\Enums\TestAttemptStatus;
use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestAnswer;
use App\Models\TestAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestAttemptController extends Controller
{
    /**
     * Start a new test attempt
     */
    public function start(int $testId): JsonResponse
    {
        $test = Test::with('questions.options')->findOrFail($testId);

        // Check if test is available
        if (!$test->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'This test is not currently available.',
            ], 403);
        }

        // Check if user can attempt
        if (!$test->canUserAttempt(auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'You have reached the maximum number of attempts for this test.',
            ], 403);
        }

        // Get next attempt number
        $attemptNumber = $test->attempts()
            ->where('user_id', auth()->id())
            ->max('attempt_number') + 1;

        // Prepare question order
        $questions = $test->questions;
        if ($test->shuffle_questions) {
            $questions = $questions->shuffle();
        }
        $questionOrder = $questions->pluck('id')->toArray();

        // Create attempt
        $attempt = TestAttempt::create([
            'test_id' => $test->id,
            'user_id' => auth()->id(),
            'status' => TestAttemptStatus::IN_PROGRESS,
            'attempt_number' => $attemptNumber,
            'started_at' => now(),
            'question_order' => $questionOrder,
        ]);

        // Return test details with questions
        return response()->json([
            'success' => true,
            'message' => 'Test attempt started successfully.',
            'data' => [
                'attempt_id' => $attempt->id,
                'test' => [
                    'id' => $test->id,
                    'title' => $test->title,
                    'description' => $test->description,
                    'duration_minutes' => $test->duration_minutes,
                    'total_points' => $test->getTotalPoints(),
                    'passing_score' => $test->passing_score,
                ],
                'questions' => $questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'type' => $question->type->value,
                        'question_text' => $question->question_text,
                        'points' => $question->points,
                        'is_required' => $question->is_required,
                        'options' => $question->type !== QuestionType::WRITTEN 
                            ? $question->options->map(fn($opt) => [
                                'id' => $opt->id,
                                'option_text' => $opt->option_text,
                                // Don't send is_correct to frontend!
                            ]) 
                            : null,
                    ];
                }),
                'started_at' => $attempt->started_at,
                'expires_at' => $test->duration_minutes 
                    ? $attempt->started_at->addMinutes($test->duration_minutes) 
                    : null,
            ],
        ]);
    }

    /**
     * Get an active attempt
     */
    public function getAttempt(int $attemptId): JsonResponse
    {
        $attempt = TestAttempt::with(['test.questions.options', 'answers'])
            ->where('user_id', auth()->id())
            ->findOrFail($attemptId);

        // Check if attempt has expired
        if ($attempt->hasExpired() && $attempt->isInProgress()) {
            // Auto-submit expired attempt
            return $this->submit($attemptId, new Request());
        }

        if ($attempt->isInProgress()) {
            $questions = $attempt->test->questions;
            
            // Apply question order if shuffled
            if ($attempt->question_order) {
                $questions = collect($attempt->question_order)
                    ->map(fn($id) => $questions->firstWhere('id', $id))
                    ->filter();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'attempt_id' => $attempt->id,
                    'status' => $attempt->status->value,
                    'test' => [
                        'id' => $attempt->test->id,
                        'title' => $attempt->test->title,
                        'description' => $attempt->test->description,
                        'duration_minutes' => $attempt->test->duration_minutes,
                    ],
                    'questions' => $questions->map(function ($question) use ($attempt) {
                        $answer = $attempt->answers->firstWhere('question_id', $question->id);
                        
                        return [
                            'id' => $question->id,
                            'type' => $question->type->value,
                            'question_text' => $question->question_text,
                            'points' => $question->points,
                            'is_required' => $question->is_required,
                            'options' => $question->type !== QuestionType::WRITTEN 
                                ? $question->options->map(fn($opt) => [
                                    'id' => $opt->id,
                                    'option_text' => $opt->option_text,
                                ]) 
                                : null,
                            'current_answer' => $answer ? [
                                'selected_option_id' => $answer->selected_option_id,
                                'written_answer' => $answer->written_answer,
                            ] : null,
                        ];
                    }),
                    'started_at' => $attempt->started_at,
                    'remaining_time_seconds' => $attempt->getRemainingTime(),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'This attempt is not in progress.',
        ], 400);
    }

    /**
     * Save answer for a question
     */
    public function saveAnswer(int $attemptId, Request $request): JsonResponse
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'selected_option_id' => 'nullable|exists:question_options,id',
            'written_answer' => 'nullable|string',
        ]);

        $attempt = TestAttempt::where('user_id', auth()->id())
            ->findOrFail($attemptId);

        // Check if attempt is still in progress
        if (!$attempt->isInProgress()) {
            return response()->json([
                'success' => false,
                'message' => 'This attempt has already been submitted.',
            ], 400);
        }

        // Check if attempt has expired
        if ($attempt->hasExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'This attempt has expired.',
            ], 400);
        }

        // Verify question belongs to this test
        $question = Question::where('test_id', $attempt->test_id)
            ->findOrFail($request->question_id);

        // Validate answer based on question type
        if ($question->type === QuestionType::WRITTEN) {
            if (!$request->has('written_answer')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Written answer is required for this question.',
                ], 422);
            }
        } else {
            if (!$request->has('selected_option_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected option is required for this question.',
                ], 422);
            }
        }

        // Save or update answer
        $answer = TestAnswer::updateOrCreate(
            [
                'test_attempt_id' => $attempt->id,
                'question_id' => $question->id,
            ],
            [
                'selected_option_id' => $request->selected_option_id,
                'written_answer' => $request->written_answer,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Answer saved successfully.',
            'data' => [
                'answer_id' => $answer->id,
                'question_id' => $question->id,
            ],
        ]);
    }

    /**
     * Submit the test attempt
     */
    public function submit(int $attemptId, Request $request): JsonResponse
    {
        $attempt = TestAttempt::with(['test', 'answers.question'])
            ->where('user_id', auth()->id())
            ->findOrFail($attemptId);

        // Check if already submitted
        if (!$attempt->isInProgress()) {
            return response()->json([
                'success' => false,
                'message' => 'This attempt has already been submitted.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Auto-grade MCQ and True/False questions
            foreach ($attempt->answers as $answer) {
                if ($answer->question->isAutoGradable()) {
                    $answer->autoGrade();
                }
            }

            // Update attempt status
            $attempt->update([
                'status' => TestAttemptStatus::SUBMITTED,
                'submitted_at' => now(),
            ]);

            // Calculate score for auto-graded questions
            $autoGradedComplete = $attempt->answers()
                ->whereHas('question', function ($query) {
                    $query->whereIn('type', [QuestionType::MCQ, QuestionType::TRUE_FALSE]);
                })
                ->whereNotNull('points_earned')
                ->count();

            $totalAutoGradable = $attempt->test->questions()
                ->whereIn('type', [QuestionType::MCQ, QuestionType::TRUE_FALSE])
                ->count();

            // If all questions are auto-gradable, mark as graded
            if ($totalAutoGradable > 0 && $autoGradedComplete === $totalAutoGradable) {
                $attempt->calculateScore();
                $attempt->update([
                    'status' => TestAttemptStatus::GRADED,
                    'graded_at' => now(),
                ]);
            }

            DB::commit();

            // Prepare response
            $responseData = [
                'attempt_id' => $attempt->id,
                'status' => $attempt->status->value,
                'submitted_at' => $attempt->submitted_at,
            ];

            // Include results if test settings allow
            if ($attempt->test->show_results_immediately && $attempt->isGraded()) {
                $responseData = array_merge($responseData, [
                    'score' => $attempt->score,
                    'total_points' => $attempt->total_points,
                    'percentage' => round($attempt->percentage, 2),
                    'passed' => $attempt->passed,
                    'passing_score' => $attempt->test->passing_score,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $attempt->isGraded() 
                    ? 'Test submitted and graded successfully.' 
                    : 'Test submitted successfully. Waiting for manual grading.',
                'data' => $responseData,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit test: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get test result
     */
    public function getResult(int $attemptId): JsonResponse
    {
        $attempt = TestAttempt::with([
            'test',
            'answers.question.options',
            'answers.selectedOption'
        ])
            ->where('user_id', auth()->id())
            ->findOrFail($attemptId);

        // Check if submitted
        if ($attempt->isInProgress()) {
            return response()->json([
                'success' => false,
                'message' => 'This attempt has not been submitted yet.',
            ], 400);
        }

        // Build response
        $data = [
            'attempt_id' => $attempt->id,
            'attempt_number' => $attempt->attempt_number,
            'status' => $attempt->status->value,
            'test' => [
                'id' => $attempt->test->id,
                'title' => $attempt->test->title,
            ],
            'submitted_at' => $attempt->submitted_at,
            'graded_at' => $attempt->graded_at,
        ];

        // Add score if graded
        if ($attempt->isGraded()) {
            $data['score'] = $attempt->score;
            $data['total_points'] = $attempt->total_points;
            $data['percentage'] = round($attempt->percentage, 2);
            $data['passed'] = $attempt->passed;
            $data['passing_score'] = $attempt->test->passing_score;

            // Add detailed answers if test allows
            if ($attempt->test->show_correct_answers) {
                $data['answers'] = $attempt->answers->map(function ($answer) {
                    $result = [
                        'question_id' => $answer->question_id,
                        'question_text' => $answer->question->question_text,
                        'type' => $answer->question->type->value,
                        'points_possible' => $answer->question->points,
                        'points_earned' => $answer->points_earned,
                        'is_correct' => $answer->is_correct,
                    ];

                    if ($answer->question->type !== QuestionType::WRITTEN) {
                        $result['your_answer'] = $answer->selectedOption?->option_text;
                        $result['correct_answer'] = $answer->question->getCorrectOption()?->option_text;
                    } else {
                        $result['your_answer'] = $answer->written_answer;
                        $result['feedback'] = $answer->feedback;
                    }

                    if ($answer->question->explanation) {
                        $result['explanation'] = $answer->question->explanation;
                    }

                    return $result;
                });
            }
        } else {
            $data['message'] = 'Your test is awaiting manual grading by the instructor.';
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get all attempts for the authenticated user
     */
    public function myAttempts(): JsonResponse
    {
        $attempts = TestAttempt::with('test')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $attempts->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'test_id' => $attempt->test_id,
                    'test_title' => $attempt->test->title,
                    'attempt_number' => $attempt->attempt_number,
                    'status' => $attempt->status->value,
                    'score' => $attempt->score,
                    'total_points' => $attempt->total_points,
                    'percentage' => $attempt->percentage,
                    'passed' => $attempt->passed,
                    'started_at' => $attempt->started_at,
                    'submitted_at' => $attempt->submitted_at,
                    'graded_at' => $attempt->graded_at,
                ];
            }),
        ]);
    }
}