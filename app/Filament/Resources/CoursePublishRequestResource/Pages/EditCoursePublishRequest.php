<?php

namespace App\Filament\Resources\CoursePublishRequestResource\Pages;

use App\Filament\Resources\CoursePublishRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCoursePublishRequest extends EditRecord
{
    protected static string $resource = CoursePublishRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            
            Actions\Action::make('approve')
                ->label('قبول')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->approve();
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'pending'),
            
            Actions\Action::make('reject')
                ->label('رفض')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->reject($this->record->admin_notes ?? 'تم الرفض');
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'pending'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث طلب النشر بنجاح';
    }
}