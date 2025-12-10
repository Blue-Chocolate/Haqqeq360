<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use App\Models\Course;
use App\Models\Unit;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ViewUnit extends Page
{
    protected static string $resource = CourseResource::class;

    protected static string $view = 'filament.resources.course-resource.pages.view-unit';

    public Course $record;
    public Unit $unit;

    public function mount(int|string $record, int|string $unit): void
    {
        // Load the course
        $this->record = Course::findOrFail($record);
        
        // Load the unit - simplified query using only course_id
        $this->unit = Unit::where('id', $unit)
            ->where('course_id', $record)
            ->firstOrFail();
        
        // Set authorization if needed
        static::authorizeResourceAccess();
    }

    public function getTitle(): string | Htmlable
    {
        return $this->unit->title ?? 'View Unit';
    }

    public function getHeading(): string | Htmlable
    {
        return $this->unit->title ?? 'View Unit';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Unit ' . $this->unit->order . ' of ' . $this->record->title;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('knowledge_base')
                ->label('Knowledge Base')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn () => CourseResource::getUrl('knowledge-base', ['record' => $this->record->id])),
            
            \Filament\Actions\Action::make('back')
                ->label('Back to Course')
                ->icon('heroicon-o-arrow-left')
                ->url(CourseResource::getUrl('view', ['record' => $this->record->id])),
            
            \Filament\Actions\Action::make('edit')
                ->label('Edit Course')
                ->icon('heroicon-o-pencil')
                ->url(CourseResource::getUrl('edit', ['record' => $this->record->id])),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            CourseResource::getUrl('index') => 'Courses',
            CourseResource::getUrl('view', ['record' => $this->record->id]) => $this->record->title,
            '#' => $this->unit->title ?? 'Unit',
        ];
    }
}