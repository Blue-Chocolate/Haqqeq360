<?php

namespace App\Filament\Resources\RequestProgramResource\Pages;

use App\Filament\Resources\RequestProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRequestProgram extends EditRecord
{
    protected static string $resource = RequestProgramResource::class;

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
        return 'Program request updated successfully';
    }
}