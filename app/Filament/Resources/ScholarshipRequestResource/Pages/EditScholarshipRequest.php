<?php

namespace App\Filament\Resources\ScholarshipRequestResource\Pages;

use App\Filament\Resources\ScholarshipRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScholarshipRequest extends EditRecord
{
    protected static string $resource = ScholarshipRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث طلب المنحة بنجاح';
    }
}