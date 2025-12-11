<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAssignments extends ListRecords
{
    protected static string $resource = AssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Assignments')
                ->badge(fn () => \App\Models\Assignment::count()),

            'published' => Tab::make('Published')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('published', true))
                ->badge(fn () => \App\Models\Assignment::where('published', true)->count())
                ->badgeColor('success'),

            'unpublished' => Tab::make('Unpublished')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('published', false))
                ->badge(fn () => \App\Models\Assignment::where('published', false)->count())
                ->badgeColor('gray'),

            'overdue' => Tab::make('Overdue')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('due_date', '<', now())
                        ->where('published', true)
                )
                ->badge(fn () => \App\Models\Assignment::where('due_date', '<', now())
                    ->where('published', true)->count())
                ->badgeColor('danger'),

            'upcoming' => Tab::make('Due This Week')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('due_date', '>', now())
                        ->where('due_date', '<', now()->addWeek())
                        ->where('published', true)
                )
                ->badge(fn () => \App\Models\Assignment::where('due_date', '>', now())
                    ->where('due_date', '<', now()->addWeek())
                    ->where('published', true)->count())
                ->badgeColor('warning'),

            'pending_grading' => Tab::make('Pending Grading')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereHas('submissions', function (Builder $q) {
                        $q->whereNull('grade');
                    })
                )
                ->badge(fn () => \App\Models\Assignment::whereHas('submissions', function (Builder $q) {
                    $q->whereNull('grade');
                })->count())
                ->badgeColor('info'),
        ];
    }
}