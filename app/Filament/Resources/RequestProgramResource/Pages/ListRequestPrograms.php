<?php

namespace App\Filament\Resources\RequestProgramResource\Pages;

use App\Filament\Resources\RequestProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRequestPrograms extends ListRecords
{
    protected static string $resource = RequestProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge($this->getModel()::count()),
            
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge($this->getModel()::where('status', 'pending')->count())
                ->badgeColor('warning'),
            
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved'))
                ->badge($this->getModel()::where('status', 'approved')->count())
                ->badgeColor('success'),
            
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected'))
                ->badge($this->getModel()::where('status', 'rejected')->count())
                ->badgeColor('danger'),
        ];
    }
}