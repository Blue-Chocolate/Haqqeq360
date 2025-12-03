<?php

namespace App\Filament\Resources\KnowledgeBaseArticleResource\Pages;

use App\Filament\Resources\KnowledgeBaseArticleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeBaseArticle extends CreateRecord
{
    protected static string $resource = KnowledgeBaseArticleResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء المقال بنجاح';
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        // Auto-generate excerpt if not provided
        if (empty($data['excerpt']) && !empty($data['content'])) {
            $data['excerpt'] = \Illuminate\Support\Str::limit(strip_tags($data['content']), 200);
        }
        
        return $data;
    }
}