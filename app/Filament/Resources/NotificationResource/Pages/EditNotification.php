<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotification extends EditRecord
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('toggleRead')
                ->label(fn ($record) => $record->is_read ? 'Mark as Unread' : 'Mark as Read')
                ->icon(fn ($record) => $record->is_read ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn ($record) => $record->is_read ? 'warning' : 'success')
                ->action(function ($record) {
                    $record->update(['is_read' => !$record->is_read]);
                    $this->refreshFormData(['is_read']);
                })
                ->successNotificationTitle(fn ($record) => 'Notification marked as ' . ($record->is_read ? 'read' : 'unread')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Notification updated successfully';
    }
}