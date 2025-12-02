<?php

namespace App\Enums;

enum QuestionType: string
{
    case MCQ = 'mcq';
    case TRUE_FALSE = 'true_false';
    case WRITTEN = 'written';

    public function label(): string
    {
        return match($this) {
            self::MCQ => 'Multiple Choice',
            self::TRUE_FALSE => 'True/False',
            self::WRITTEN => 'Written Answer',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}