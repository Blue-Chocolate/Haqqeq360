<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Support\Enums\FontWeight;

class ViewCourse extends ViewRecord
{
    protected static string $resource = CourseResource::class;

    protected static string $view = 'filament.resources.course-resource.pages.view-course';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Course Statistics Section
                Components\Section::make('Course Overview')
                    ->schema([
                        Components\Split::make([
                            Components\Grid::make(2)
                                ->schema([
                                    Components\ImageEntry::make('cover_image')
                                        ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->title) . '&color=7F9CF5&background=EBF4FF&size=256')
                                        ->height(200)
                                        ->columnSpan(2),
                                    
                                    Components\TextEntry::make('title')
                                        ->size(Components\TextEntry\TextEntrySize::Large)
                                        ->weight(FontWeight::Bold)
                                        ->columnSpan(2),
                                    
                                    Components\TextEntry::make('description')
                                        ->columnSpan(2),
                                ]),
                        ]),
                    ]),

                // Statistics Grid
                Components\Section::make('Course Statistics')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('units_count')
                                    ->label('Total Units')
                                    ->state(fn ($record) => $record->units()->count())
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-book-open'),
                                
                                Components\TextEntry::make('lessons_count')
                                    ->label('Total Lessons')
                                    ->state(fn ($record) => $record->lessons()->count())
                                    ->badge()
                                    ->color('success')
                                    ->icon('heroicon-o-academic-cap'),
                                
                                Components\TextEntry::make('enrollments_count')
                                    ->label('Enrolled Students')
                                    ->state(fn ($record) => $record->enrollments()->count())
                                    ->badge()
                                    ->color('warning')
                                    ->icon('heroicon-o-users'),
                                
                                Components\TextEntry::make('available_seats')
                                    ->label('Available Seats')
                                    ->state(fn ($record) => max(0, $record->seats - $record->enrollments()->count()))
                                    ->badge()
                                    ->color(fn ($state): string => match (true) {
                                        $state === 0 => 'danger',
                                        $state <= 5 => 'warning',
                                        default => 'success',
                                    })
                                    ->icon('heroicon-o-ticket'),
                            ]),
                    ]),

                // Course Details Section
                Components\Section::make('Course Details')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('instructor.name')
                                    ->label('Instructor')
                                    ->icon('heroicon-o-user'),
                                
                                Components\TextEntry::make('level')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'beginner' => 'success',
                                        'intermediate' => 'warning',
                                        'advanced' => 'danger',
                                    }),
                                
                                Components\TextEntry::make('mode')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'online' => 'info',
                                        'hybrid' => 'warning',
                                        'offline' => 'secondary',
                                    }),
                                
                                Components\TextEntry::make('duration_weeks')
                                    ->suffix(' weeks')
                                    ->icon('heroicon-o-clock'),
                                
                                Components\TextEntry::make('seats')
                                    ->label('Total Seats')
                                    ->icon('heroicon-o-user-group'),
                                
                                Components\TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'draft' => 'secondary',
                                        'published' => 'success',
                                    }),
                            ]),
                    ])
                    ->columns(3),

                // Pricing Section
                Components\Section::make('Pricing')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('price')
                                    ->money('USD')
                                    ->icon('heroicon-o-currency-dollar'),
                                
                                Components\TextEntry::make('discounted_price')
                                    ->money('USD')
                                    ->icon('heroicon-o-tag')
                                    ->placeholder('No discount'),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}