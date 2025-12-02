<?php

namespace App\Filament\Resources\TestAttemptResource\Pages;

use App\Filament\Resources\TestAttemptResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTestAttempt extends EditRecord
{
    protected static string $resource = TestAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
