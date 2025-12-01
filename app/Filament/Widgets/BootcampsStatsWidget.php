<?php 

namespace App\Filament\Widgets;

use App\Models\Bootcamp;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BootcampsStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Bootcamps', Bootcamp::count())
                ->description('All bootcamp sessions')
                ->descriptionIcon('heroicon-m-fire')
                ->color('warning')
                ->url('/admin/boot-camps')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
        ];
    }
}