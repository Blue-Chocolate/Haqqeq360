<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Courses'),
            
            'published' => Tab::make('Published')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'published'))
                ->badge(fn () => static::getResource()::getModel()::where('status', 'published')->count()),
            
            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft'))
                ->badge(fn () => static::getResource()::getModel()::where('status', 'draft')->count()),
            
            'beginner' => Tab::make('Beginner')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('level', 'beginner'))
                ->badge(fn () => static::getResource()::getModel()::where('level', 'beginner')->count()),
            
            'intermediate' => Tab::make('Intermediate')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('level', 'intermediate'))
                ->badge(fn () => static::getResource()::getModel()::where('level', 'intermediate')->count()),
            
            'advanced' => Tab::make('Advanced')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('level', 'advanced'))
                ->badge(fn () => static::getResource()::getModel()::where('level', 'advanced')->count()),
        ];
    }
}
