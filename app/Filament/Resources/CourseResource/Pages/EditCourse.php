<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('knowledge_base')
                ->label('Knowledge Base')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn () => static::getResource()::getUrl('knowledge-base', ['record' => $this->record])),
            
            Actions\ViewAction::make(),
            
            Actions\Action::make('publish')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['status' => 'published']))
                ->visible(fn () => $this->record->status === 'draft'),
            
            Actions\Action::make('unpublish')
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['status' => 'draft']))
                ->visible(fn () => $this->record->status === 'published'),
            
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Course updated successfully';
    }
}