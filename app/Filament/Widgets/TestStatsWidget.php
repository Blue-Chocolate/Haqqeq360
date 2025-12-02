<?php

namespace App\Filament\Widgets;

use App\Enums\TestAttemptStatus;
use App\Models\Test;
use App\Models\TestAttempt;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TestStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalTests = Test::count();
        $activeTests = Test::where('is_active', true)->count();
        
        $pendingGrading = TestAttempt::where('status', TestAttemptStatus::SUBMITTED)
            ->whereHas('answers', function ($query) {
                $query->whereNull('points_earned');
            })
            ->count();
        
        $totalAttempts = TestAttempt::where('status', TestAttemptStatus::GRADED)->count();
        $passedAttempts = TestAttempt::where('status', TestAttemptStatus::GRADED)
            ->where('passed', true)
            ->count();
        
        $passRate = $totalAttempts > 0 
            ? round(($passedAttempts / $totalAttempts) * 100, 1) 
            : 0;

        return [
            Stat::make('Total Tests', $totalTests)
                ->description("{$activeTests} active")
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('success'),

            Stat::make('Pending Grading', $pendingGrading)
                ->description('Submissions need grading')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingGrading > 0 ? 'warning' : 'success'),

            Stat::make('Pass Rate', "{$passRate}%")
                ->description("{$passedAttempts} of {$totalAttempts} passed")
                ->descriptionIcon('heroicon-m-trophy')
                ->color($passRate >= 70 ? 'success' : 'danger'),
        ];
    }
}