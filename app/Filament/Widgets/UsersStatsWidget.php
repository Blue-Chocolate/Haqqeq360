<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
{
    return array_filter([
        Stat::make('Total Users', User::count())
            ->description('All registered users')
            ->color('success'),

        auth()->user()->can('viewAny', User::class) 
            ? Stat::make('New Users', User::whereDate('created_at', today())->count())
                ->description('Registered today')
                ->color('info')
            : null,

        auth()->user()->role === 'admin'
            ? Stat::make('Admins', User::where('role', 'admin')->count())
                ->color('danger')
            : null,
    ]);
}
}