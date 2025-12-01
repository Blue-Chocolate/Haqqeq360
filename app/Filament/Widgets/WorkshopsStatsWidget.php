<?php

namespace App\Filament\Widgets;

use App\Models\Workshop;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WorkshopsStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Workshops', Workshop::count())
                ->description('All workshop events')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('danger')
                ->url('/admin/workshops')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
        ];
    }
}