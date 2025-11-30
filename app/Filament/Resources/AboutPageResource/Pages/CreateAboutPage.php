<?php

namespace App\Filament\Resources\AboutPageResource\Pages;

use App\Filament\Resources\AboutPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAboutPage extends CreateRecord
{
    protected static string $resource = AboutPageResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الصفحة بنجاح';
    }
}