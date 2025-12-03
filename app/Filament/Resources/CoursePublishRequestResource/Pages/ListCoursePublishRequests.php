<?php

namespace App\Filament\Resources\CoursePublishRequestResource\Pages;

use App\Filament\Resources\CoursePublishRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCoursePublishRequests extends ListRecords
{
    protected static string $resource = CoursePublishRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('طلب نشر جديد')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل')
                ->badge(fn () => static::getModel()::count()),
            
            'pending' => Tab::make('قيد المراجعة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => static::getModel()::where('status', 'pending')->count())
                ->badgeColor('warning'),
            
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