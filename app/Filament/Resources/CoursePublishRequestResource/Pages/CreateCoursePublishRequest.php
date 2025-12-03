<?php

namespace App\Filament\Resources\CoursePublishRequestResource\Pages;

use App\Filament\Resources\CoursePublishRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCoursePublishRequest extends CreateRecord
{
    protected static string $resource = CoursePublishRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء طلب النشر بنجاح';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }
}