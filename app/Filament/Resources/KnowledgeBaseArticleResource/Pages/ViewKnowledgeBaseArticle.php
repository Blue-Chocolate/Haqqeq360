<?php 

namespace App\Filament\Resources\KnowledgeBaseArticleResource\Pages;

use App\Filament\Resources\KnowledgeBaseArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewKnowledgeBaseArticle extends ViewRecord
{
    protected static string $resource = KnowledgeBaseArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات المقال')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('العنوان'),
                        Infolists\Components\TextEntry::make('slug')
                            ->label('الرابط'),
                        Infolists\Components\TextEntry::make('course.title')
                            ->label('الدورة')
                            ->placeholder('غير مرتبط بدورة'),
                        Infolists\Components\TextEntry::make('author.name')
                            ->label('الكاتب'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->colors([
                                'warning' => 'draft',
                                'success' => 'published',
                            ])
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'draft' => 'مسودة',
                                'published' => 'منشور',
                                default => $state,
                            }),
                        Infolists\Components\TextEntry::make('views_count')
                            ->label('عدد المشاهدات')
                            ->badge()
                            ->color('success'),
                        Infolists\Components\TextEntry::make('published_at')
                            ->label('تاريخ النشر')
                            ->dateTime('Y-m-d H:i'),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('المحتوى')
                    ->schema([
                        Infolists\Components\ImageEntry::make('featured_image')
                            ->label('الصورة البارزة')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('excerpt')
                            ->label('المقتطف')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('content')
                            ->label('المحتوى')
                            ->html()
                            ->columnSpanFull(),
                    ]),
                
                Infolists\Components\Section::make('الوسوم')
                    ->schema([
                        Infolists\Components\TextEntry::make('tags.name')
                            ->label('الوسوم')
                            ->badge()
                            ->separator(','),
                    ])
                    ->collapsible(),
                
                Infolists\Components\Section::make('المرفقات')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('attachments')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('file_name')
                                    ->label('اسم الملف'),
                                Infolists\Components\TextEntry::make('file_size_formatted')
                                    ->label('الحجم'),
                                Infolists\Components\TextEntry::make('file_type')
                                    ->label('النوع'),
                            ])
                            ->columns(3),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}