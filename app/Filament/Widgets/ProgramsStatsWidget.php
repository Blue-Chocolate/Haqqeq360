<?php
namespace App\Filament\Widgets;

use App\Models\Program;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Filament\Resources\ProgramResource;

class ProgramsStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Programs', Program::count())
                ->description('All available programs')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary')
                ->url('/admin/programs')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
        ];
    }
}