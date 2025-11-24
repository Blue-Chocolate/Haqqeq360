<?php

namespace App\Filament\Resources\RequestProgramResource\Pages;

use App\Filament\Resources\RequestProgramResource;
use App\Mail\RequestProgramStatusUpdated;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;

class ViewRequestProgram extends ViewRecord
{
    protected static string $resource = RequestProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Actions\Action::make('approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status !== 'approved')
                ->action(function () {
                    $this->record->update(['status' => 'approved']);
                    
                    Mail::to($this->record->user->email)->send(
                        new RequestProgramStatusUpdated($this->record)
                    );
                    
                    Notification::make()
                        ->title('Request Approved')
                        ->success()
                        ->send();
                }),
            
            Actions\Action::make('reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status !== 'rejected')
                ->action(function () {
                    $this->record->update(['status' => 'rejected']);
                    
                    Mail::to($this->record->user->email)->send(
                        new RequestProgramStatusUpdated($this->record)
                    );
                    
                    Notification::make()
                        ->title('Request Rejected')
                        ->success()
                        ->send();
                }),
            
            Actions\DeleteAction::make(),
        ];
    }
}