<?php

namespace App\Filament\Resources\ScholarshipRequestResource\Pages;

use App\Filament\Resources\ScholarshipRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateScholarshipRequest extends CreateRecord
{
    protected static string $resource = ScholarshipRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء طلب المنحة بنجاح';
    }
}