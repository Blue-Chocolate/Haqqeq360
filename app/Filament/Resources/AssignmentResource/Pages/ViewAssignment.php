<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;

class ViewAssignment extends ViewRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            
            Actions\Action::make('download_attachment')
                ->label('Download Attachment')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->visible(fn ($record) => $record->attachment_path !== null)
                ->url(fn ($record) => \Storage::url($record->attachment_path))
                ->openUrlInNewTab(),
            
            Actions\Action::make('toggle_publish')
                ->label(fn ($record) => $record->published ? 'Unpublish' : 'Publish')
                ->icon(fn ($record) => $record->published ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                ->color(fn ($record) => $record->published ? 'warning' : 'success')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update(['published' => !$record->published]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title($record->published ? 'Assignment published' : 'Assignment unpublished')
                        ->success()
                        ->send();
                }),
        ];
    }
}