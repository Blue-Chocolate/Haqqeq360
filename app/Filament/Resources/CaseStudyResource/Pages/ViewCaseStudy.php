<?php

namespace App\Filament\Resources\CaseStudyResource\Pages;

use App\Filament\Resources\CaseStudyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCaseStudy extends ViewRecord
{
    protected static string $resource = CaseStudyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->color('primary'),
            
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Case Study')
                ->modalDescription('Are you sure you want to delete this case study? All submissions will be permanently deleted.')
                ->successNotificationTitle('Case Study deleted'),
            
            Actions\Action::make('toggleStatus')
                ->label(fn ($record) => $record->status === 'open' ? 'Close' : 'Open')
                ->icon(fn ($record) => $record->status === 'open' ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                ->color(fn ($record) => $record->status === 'open' ? 'danger' : 'success')
                ->requiresConfirmation()
                ->modalHeading(fn ($record) => $record->status === 'open' ? 'Close Case Study' : 'Open Case Study')
                ->modalDescription(fn ($record) => $record->status === 'open' 
                    ? 'Students will no longer be able to submit or edit answers.' 
                    : 'Students will be able to submit and edit answers.')
                ->action(function ($record) {
                    $record->status = $record->status === 'open' ? 'closed' : 'open';
                    $record->save();
                })
                ->successNotificationTitle(fn ($record) => 'Case study ' . ($record->status === 'open' ? 'opened' : 'closed')),
            
            Actions\Action::make('exportSubmissions')
                ->label('Export Submissions')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->visible(fn ($record) => $record->answers()->count() > 0)
                ->action(function ($record) {
                    return static::exportSubmissionsToCSV($record);
                }),
        ];
    }

    protected static function exportSubmissionsToCSV($caseStudy)
    {
        $submissions = $caseStudy->answers()
            ->with(['learner', 'files'])
            ->get();

        $filename = 'case-study-' . $caseStudy->id . '-submissions-' . now()->format('Y-m-d') . '.csv';
        $filepath = storage_path('app/public/' . $filename);

        $handle = fopen($filepath, 'w');

        // Headers
        fputcsv($handle, [
            'ID',
            'Student Name',
            'Email',
            'Answer Text',
            'Files Count',
            'Submitted At',
            'Time Taken (minutes)'
        ]);

        // Data
        foreach ($submissions as $submission) {
            $timeTaken = $submission->created_at->diffInMinutes($submission->submitted_at);
            
            fputcsv($handle, [
                $submission->id,
                $submission->learner->name,
                $submission->learner->email,
                strip_tags($submission->answer_text),
                $submission->files->count(),
                $submission->submitted_at?->format('Y-m-d H:i:s'),
                $timeTaken
            ]);
        }

        fclose($handle);

        return response()->download($filepath)->deleteFileAfterSend();
    }
}