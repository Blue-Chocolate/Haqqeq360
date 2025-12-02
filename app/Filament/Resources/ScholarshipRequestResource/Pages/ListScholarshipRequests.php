<?php

namespace App\Filament\Resources\ScholarshipRequestResource\Pages;

use App\Filament\Resources\ScholarshipRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListScholarshipRequests extends ListRecords
{
    protected static string $resource = ScholarshipRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('طلب منحة جديد')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل'),
            
            'pending' => Tab::make('قيد المراجعة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => static::getModel()::where('status', 'pending')->count())
                ->badgeColor('warning'),
            
            'under_review' => Tab::make('تحت الدراسة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'under_review'))
                ->badge(fn () => static::getModel()::where('status', 'under_review')->count())
                ->badgeColor('info'),
            
            'approved' => Tab::make('مقبول')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved'))
                ->badge(fn () => static::getModel()::where('status', 'approved')->count())
                ->badgeColor('success'),
            
            'rejected' => Tab::make('مرفوض')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected'))
                ->badge(fn () => static::getModel()::where('status', 'rejected')->count())
                ->badgeColor('danger'),
        ];
    }
}