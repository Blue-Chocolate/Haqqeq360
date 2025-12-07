<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('markAllAsRead')
                ->label('Mark All as Read')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->action(function () {
                    $this->getModel()::where('is_read', false)->update(['is_read' => true]);
                    $this->resetTableFiltersForm();
                })
                ->requiresConfirmation()
                ->modalHeading('Mark all notifications as read?')
                ->modalDescription('This will mark all unread notifications as read.')
                ->successNotificationTitle('All notifications marked as read'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => $this->getModel()::count()),
            
            'unread' => Tab::make('Unread')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_read', false))
                ->badge(fn () => $this->getModel()::where('is_read', false)->count())
                ->badgeColor('danger'),
            
            'read' => Tab::make('Read')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_read', true))
                ->badge(fn () => $this->getModel()::where('is_read', true)->count())
                ->badgeColor('success'),
            
            'system' => Tab::make('System')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'system'))
                ->badge(fn () => $this->getModel()::where('type', 'system')->count()),
            
            'course' => Tab::make('Course')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'course'))
                ->badge(fn () => $this->getModel()::where('type', 'course')->count()),
            
            'assignment' => Tab::make('Assignment')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'assignment'))
                ->badge(fn () => $this->getModel()::where('type', 'assignment')->count()),
        ];
    }
}