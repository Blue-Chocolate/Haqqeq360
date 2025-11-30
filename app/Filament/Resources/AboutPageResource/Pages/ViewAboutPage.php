<?php

namespace App\Filament\Resources\AboutPageResource\Pages;

use App\Filament\Resources\AboutPageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAboutPage extends ViewRecord
{
    protected static string $resource = AboutPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }
}