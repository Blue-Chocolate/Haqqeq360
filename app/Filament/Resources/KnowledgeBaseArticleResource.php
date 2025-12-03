<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KnowledgeBaseArticleResource\Pages;
use App\Models\KnowledgeBaseArticle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class KnowledgeBaseArticleResource extends Resource
{
    protected static ?string $model = KnowledgeBaseArticle::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    
    protected static ?string $navigationLabel = 'الموسوعة التعليمية';
    
    protected static ?string $modelLabel = 'مقال';
    
    protected static ?string $pluralModelLabel = 'المقالات';
    
    protected static ?string $navigationGroup = 'إدارة المحتوى';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المقال')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان المقال')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => 
                                $set('slug', Str::slug($state))
                            ),
                        
                        Forms\Components\TextInput::make('slug')
                            ->label('الرابط')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('سيتم إنشاؤه تلقائياً من العنوان'),
                        
                        Forms\Components\Select::make('course_id')
                            ->label('الدورة المرتبطة')
                            ->relationship('course', 'title')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('اختياري: ربط المقال بدورة معينة'),
                        
                        Forms\Components\Textarea::make('excerpt')
                            ->label('المقتطف')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('ملخص قصير للمقال (سيتم إنشاؤه تلقائياً إذا ترك فارغاً)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('محتوى المقال')
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->label('المحتوى')
                            ->required()
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('articles/inline')
                            ->fileAttachmentsVisibility('public')
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

                Forms\Components\Section::make('الصورة البارزة والمرفقات')
                    ->schema([
                        Forms\Components\FileUpload::make('featured_image')
                            ->label('الصورة البارزة')
                            ->image()
                            ->disk('public')
                            ->directory('articles/featured')
                            ->maxSize(5120)
                            ->imageEditor()
                            ->columnSpanFull(),
                        
                        Forms\Components\Repeater::make('attachments')
                            ->label('المرفقات')
                            ->relationship('attachments')
                            ->schema([
                                Forms\Components\TextInput::make('file_name')
                                    ->label('اسم الملف')
                                    ->required(),
                                
                                Forms\Components\FileUpload::make('file_path')
                                    ->label('الملف')
                                    ->disk('public')
                                    ->directory('articles/attachments')
                                    ->maxSize(10240)
                                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/*'])
                                    ->required()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state) {
                                            $file = $state;
                                            if ($file instanceof \Illuminate\Http\UploadedFile) {
                                                $set('file_type', $file->getMimeType());
                                                $set('file_size', $file->getSize());
                                            }
                                        }
                                    }),
                                
                                Forms\Components\Hidden::make('file_type'),
                                Forms\Components\Hidden::make('file_size'),
                            ])
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['file_name'] ?? null)
                            ->columnSpanFull()
                            ->addActionLabel('إضافة مرفق')
                            ->reorderable(),
                    ]),

                Forms\Components\Section::make('التصنيف والنشر')
                    ->schema([
                        Forms\Components\Select::make('tags')
                            ->label('الوسوم')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('اسم الوسم')
                                    ->required(),
                                Forms\Components\ColorPicker::make('color')
                                    ->label('اللون')
                                    ->default('#3b82f6'),
                            ])
                            ->searchable(),
                        
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'draft' => 'مسودة',
                                'published' => 'منشور',
                            ])
                            ->default('draft')
                            ->required()
                            ->live(),
                        
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('تاريخ النشر')
                            ->visible(fn (Forms\Get $get) => $get('status') === 'published')
                            ->default(now())
                            ->required(fn (Forms\Get $get) => $get('status') === 'published'),
                        
                        Forms\Components\Hidden::make('created_by')
                            ->default(auth()->id()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label('الصورة')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.png')),
                
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('course.title')
                    ->label('الدورة')
                    ->searchable()
                    ->sortable()
                    ->placeholder('غير مرتبط بدورة')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('tags.name')
                    ->label('الوسوم')
                    ->badge()
                    ->separator(',')
                    ->colors([
                        'primary',
                        'success',
                        'warning',
                        'danger',
                        'info',
                    ]),
                
                Tables\Columns\TextColumn::make('author.name')
                    ->label('الكاتب')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('views_count')
                    ->label('المشاهدات')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('status')
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
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('published_at')
                    ->label('تاريخ النشر')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'published' => 'منشور',
                    ]),
                
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('الدورة')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('tags')
                    ->label('الوسم')
                    ->relationship('tags', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\Filter::make('published')
                    ->label('المنشورة فقط')
                    ->query(fn (Builder $query): Builder => $query->published()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('publish')
                        ->label('نشر')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update([
                                'status' => 'published',
                                'published_at' => now(),
                            ]);
                        })
                        ->color('success'),
                    Tables\Actions\BulkAction::make('draft')
                        ->label('تحويل لمسودة')
                        ->icon('heroicon-o-pencil')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['status' => 'draft']);
                        })
                        ->color('warning'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKnowledgeBaseArticles::route('/'),
            'create' => Pages\CreateKnowledgeBaseArticle::route('/create'),
            'edit' => Pages\EditKnowledgeBaseArticle::route('/{record}/edit'),
            'view' => Pages\ViewKnowledgeBaseArticle::route('/{record}'),
        ];
    }
}