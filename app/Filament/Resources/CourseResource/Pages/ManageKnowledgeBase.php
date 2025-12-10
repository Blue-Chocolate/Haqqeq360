<?php

// ============================================================================
// File: app/Filament/Resources/CourseResource/Pages/ManageKnowledgeBase.php
// ============================================================================

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use App\Models\Course;
use App\Models\KnowledgeBaseArticle;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

class ManageKnowledgeBase extends ManageRelatedRecords
{
    protected static string $resource = CourseResource::class;

    protected static string $relationship = 'knowledgeBaseArticles';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationLabel(): string
    {
        return 'Knowledge Base';
    }

    public function getTitle(): string | Htmlable
    {
        return 'Knowledge Base Articles - ' . $this->getRecord()->title;
    }

    public function getBreadcrumb(): string
    {
        return 'Knowledge Base';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Article Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $context, $state, callable $set) => 
                                $context === 'create' ? $set('slug', Str::slug($state)) : null
                            )
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(KnowledgeBaseArticle::class, 'slug', ignoreRecord: true)
                            ->helperText('URL-friendly version of the title')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('featured_image')
                            ->image()
                            ->imageEditor()
                            ->directory('knowledge-base/featured-images')
                            ->visibility('public')
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('excerpt')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('Short description of the article. Leave empty to auto-generate from content.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Content')
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->required()
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                            ])
                            ->default('draft')
                            ->required()
                            ->native(false),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Publish Date')
                            ->native(false)
                            ->helperText('Leave empty to publish immediately when status is set to published'),

                        Forms\Components\Select::make('tags')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique('article_tags', 'name'),
                                Forms\Components\ColorPicker::make('color')
                                    ->default('#3b82f6'),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Attachments')
                    ->schema([
                        Forms\Components\Repeater::make('attachments')
                            ->relationship('attachments')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\FileUpload::make('file_path')
                                    ->label('File')
                                    ->directory('knowledge-base/attachments')
                                    ->maxSize(10240)
                                    ->downloadable()
                                    ->required(),

                                Forms\Components\Textarea::make('description')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Attachment')
                            ->defaultItems(0)
                            ->addActionLabel('Add Attachment')
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->title) . '&color=7F9CF5&background=EBF4FF&size=128')
                    ->size(60)
                    ->square(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('bold')
                    ->description(fn ($record) => Str::limit($record->excerpt, 80)),

                Tables\Columns\TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tags.name')
                    ->badge()
                    ->color(fn ($state, $record) => $record->tags->firstWhere('name', $state)?->color ?? 'gray')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'published',
                    ])
                    ->icons([
                        'heroicon-o-pencil' => 'draft',
                        'heroicon-o-check-circle' => 'published',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('views_count')
                    ->label('Views')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-eye')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->since()
                    ->placeholder('Not published'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('published')
                    ->query(fn ($query) => $query->published()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        $data['course_id'] = $this->getRecord()->id;
                        
                        if (empty($data['published_at']) && $data['status'] === 'published') {
                            $data['published_at'] = now();
                        }
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn ($record) => $record->title)
                    ->modalWidth('7xl')
                    ->infolist([
                        Infolists\Components\Section::make('Article Details')
                            ->schema([
                                Infolists\Components\ImageEntry::make('featured_image')
                                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->title) . '&color=7F9CF5&background=EBF4FF&size=512')
                                    ->height(200)
                                    ->columnSpanFull(),

                                Infolists\Components\TextEntry::make('title')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->columnSpanFull(),

                                Infolists\Components\TextEntry::make('excerpt')
                                    ->columnSpanFull()
                                    ->placeholder('No excerpt'),

                                Infolists\Components\TextEntry::make('author.name')
                                    ->label('Author')
                                    ->icon('heroicon-o-user'),

                                Infolists\Components\TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'draft' => 'secondary',
                                        'published' => 'success',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('published_at')
                                    ->dateTime()
                                    ->icon('heroicon-o-calendar')
                                    ->placeholder('Not published'),

                                Infolists\Components\TextEntry::make('views_count')
                                    ->label('Views')
                                    ->icon('heroicon-o-eye')
                                    ->badge()
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('tags.name')
                                    ->badge()
                                    ->color(fn ($state, $record) => $record->tags->firstWhere('name', $state)?->color ?? 'gray')
                                    ->columnSpanFull(),
                            ])
                            ->columns(4),

                        Infolists\Components\Section::make('Content')
                            ->schema([
                                Infolists\Components\TextEntry::make('content')
                                    ->html()
                                    ->columnSpanFull(),
                            ]),

                        Infolists\Components\Section::make('Attachments')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('attachments')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('title')
                                            ->weight('bold'),
                                        Infolists\Components\TextEntry::make('description')
                                            ->columnSpanFull(),
                                        Infolists\Components\TextEntry::make('file_path')
                                            ->label('File')
                                            ->url(fn ($record) => $record ? asset('storage/' . $record->file_path) : null)
                                            ->openUrlInNewTab()
                                            ->icon('heroicon-o-arrow-down-tray'),
                                    ])
                                    ->columns(1)
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn ($record) => $record->attachments->count() > 0),
                    ]),

                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        if (empty($data['published_at']) && $data['status'] === 'published') {
                            $data['published_at'] = now();
                        }
                        return $data;
                    }),

                Tables\Actions\DeleteAction::make(),

                Tables\Actions\Action::make('publish')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (KnowledgeBaseArticle $record) {
                        $record->update([
                            'status' => 'published',
                            'published_at' => $record->published_at ?? now(),
                        ]);

                        Notification::make()
                            ->title('Article Published')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (KnowledgeBaseArticle $record) => $record->status === 'draft'),

                Tables\Actions\Action::make('unpublish')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (KnowledgeBaseArticle $record) {
                        $record->update(['status' => 'draft']);

                        Notification::make()
                            ->title('Article Unpublished')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (KnowledgeBaseArticle $record) => $record->status === 'published'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('publish')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'status' => 'published',
                                    'published_at' => $record->published_at ?? now(),
                                ]);
                            });

                            Notification::make()
                                ->title('Articles Published')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('Unpublish Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['status' => 'draft']);

                            Notification::make()
                                ->title('Articles Unpublished')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->label('Create First Article')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        $data['course_id'] = $this->getRecord()->id;
                        
                        if (empty($data['published_at']) && $data['status'] === 'published') {
                            $data['published_at'] = now();
                        }
                        
                        return $data;
                    }),
            ])
            ->emptyStateHeading('No knowledge base articles yet')
            ->emptyStateDescription('Create your first article to help students learn better.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}