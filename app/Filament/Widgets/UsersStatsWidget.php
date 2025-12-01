<?php

// app/Filament/Widgets/UsersStatsWidget.php
namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Filament\Resources\UserResource;
class UsersStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('All registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->url('/admin/users')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
        ];
    }
}