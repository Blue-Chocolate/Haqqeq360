<?php

namespace App\Filament\Resources\RequestProgramResource\Pages;

use App\Filament\Resources\RequestProgramResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRequestProgram extends CreateRecord
{
    protected static string $resource = RequestProgramResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Program request created successfully';
    }
}