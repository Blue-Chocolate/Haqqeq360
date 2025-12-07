<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification as FilamentNotification;

class CreateNotification extends CreateRecord
{
    protected static string $resource = NotificationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store the send_to type and user_ids for later use
        $this->sendTo = $data['send_to'] ?? 'single';
        $this->userIds = $data['user_ids'] ?? [];
        
        // Remove the temporary fields that aren't in the database
        unset($data['send_to'], $data['user_ids']);
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $originalNotification = $this->record;
        
        // Handle bulk creation based on send_to option
        if ($this->sendTo === 'multiple' && !empty($this->userIds)) {
            // Create notification for each selected user
            $createdCount = 0;
            
            foreach ($this->userIds as $userId) {
                if ($userId != $originalNotification->user_id) {
                    Notification::create([
                        'user_id' => $userId,
                        'title' => $originalNotification->title,
                        'message' => $originalNotification->message,
                        'type' => $originalNotification->type,
                        'is_read' => $originalNotification->is_read,
                    ]);
                    $createdCount++;
                }
            }
            
            FilamentNotification::make()
                ->success()
                ->title('Notifications sent successfully')
                ->body("Notification sent to " . ($createdCount + 1) . " users.")
                ->send();
                
        } elseif ($this->sendTo === 'all') {
            // Create notification for all users except the original one
            $allUsers = User::where('id', '!=', $originalNotification->user_id)->get();
            $createdCount = 0;
            
            foreach ($allUsers as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'title' => $originalNotification->title,
                    'message' => $originalNotification->message,
                    'type' => $originalNotification->type,
                    'is_read' => $originalNotification->is_read,
                ]);
                $createdCount++;
            }
            
            FilamentNotification::make()
                ->success()
                ->title('Notifications sent to all users')
                ->body("Notification sent to " . ($createdCount + 1) . " users.")
                ->send();
        } else {
            // Single user - show standard notification
            FilamentNotification::make()
                ->success()
                ->title('Notification created successfully')
                ->body("Notification sent to 1 user.")
                ->send();
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        // Return null to prevent double notification (we handle it in afterCreate)
        return null;
    }

    private $sendTo;
    private $userIds;
}