<?php

// app/Filament/Resources/TestAttemptResource/Pages/ListTestAttempts.php

namespace App\Filament\Resources\TestAttemptResource\Pages;

use App\Filament\Resources\TestAttemptResource;
use Filament\Resources\Pages\ListRecords;

class ListTestAttempts extends ListRecords
{
    protected static string $resource = TestAttemptResource::class;
}