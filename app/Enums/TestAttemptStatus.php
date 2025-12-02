<?php

namespace App\Enums;

enum TestAttemptStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case SUBMITTED = 'submitted';
    case GRADED = 'graded';

    public function label(): string
    {
        return match($this) {
            self::IN_PROGRESS => 'In Progress',
            self::SUBMITTED => 'Submitted',
            self::GRADED => 'Graded',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::IN_PROGRESS => 'warning',
            self::SUBMITTED => 'info',
            self::GRADED => 'success',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}