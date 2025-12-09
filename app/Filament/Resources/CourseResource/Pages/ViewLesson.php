<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use App\Models\Course;
use App\Models\Lesson;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ViewLesson extends Page
{
    protected static string $resource = CourseResource::class;

    protected static string $view = 'filament.resources.course-resource.pages.view-lesson';

    public Course $record;
    public Lesson $lesson;


    public function getTitle(): string | Htmlable
    {
        return $this->lesson->title ?? 'View Lesson';
    }

    public function getHeading(): string | Htmlable
    {
        return $this->lesson->title ?? 'View Lesson';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Lesson ' . $this->lesson->order . ' • ' . $this->lesson->unit->title . ' • ' . $this->record->title;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back_to_unit')
                ->label('Back to Unit')
                ->icon('heroicon-o-arrow-left')
                ->url(CourseResource::getUrl('view-unit', [
                    'record' => $this->record->id,
                    'unit' => $this->lesson->unit_id
                ])),
            
            \Filament\Actions\Action::make('edit')
                ->label('Edit Lesson')
                ->icon('heroicon-o-pencil')
                ->url(CourseResource::getUrl('edit', ['record' => $this->record->id]))
                ->color('gray'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            CourseResource::getUrl('index') => 'Courses',
            CourseResource::getUrl('view', ['record' => $this->record->id]) => $this->record->title,
            CourseResource::getUrl('view-unit', [
                'record' => $this->record->id,
                'unit' => $this->lesson->unit_id    
            ]) => $this->lesson->unit->title ?? 'Unit',
            '#' => $this->lesson->title ?? 'Lesson',
        ];
    }
}