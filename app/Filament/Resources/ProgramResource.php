<?php

// ============================================
// ProgramResource.php
// ============================================

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramResource\Pages;
use App\Filament\Resources\ProgramResource\RelationManagers;
use App\Models\Program;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'إدارة المنتجات';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الأساسية')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('النوع')
                            ->options([
                                'program' => 'برنامج',
                                'diploma' => 'دبلوم',
                                'certificate' => 'شهادة',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('title_ar')
                            ->label('العنوان بالعربية')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $context, $state, callable $set) => 
                                $context === 'create' ? $set('slug', Str::slug($state)) : null
                            ),

                        Forms\Components\TextInput::make('title_en')
                            ->label('العنوان بالإنجليزية')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->label('الرابط')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('نسخة صديقة لمحركات البحث من العنوان'),

                        Forms\Components\Textarea::make('description_ar')
                            ->label('الوصف بالعربية')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description_en')
                            ->label('الوصف بالإنجليزية')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('cover_image_url')
                            ->label('صورة الغلاف')
                            ->image()
                            ->imageEditor()
                            ->directory('programs/covers')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('تفاصيل البرنامج')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('التصنيف')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('اسم التصنيف')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->label('الوصف')
                                    ->rows(4),
                            ]),

                        Forms\Components\Select::make('difficulty_level')
                            ->label('مستوى الصعوبة')
                            ->options([
                                'beginner' => 'مبتدئ',
                                'intermediate' => 'متوسط',
                                'advanced' => 'متقدم',
                            ])
                            ->native(false),

                        Forms\Components\Select::make('delivery_mode')
                            ->label('نمط التعليم')
                            ->options([
                                'online' => 'أونلاين',
                                'hybrid' => 'هجين',
                                'offline' => 'حضوري',
                            ])
                            ->required()
                            ->default('online')
                            ->native(false),

                        Forms\Components\TextInput::make('duration_weeks')
                            ->label('المدة بالأسابيع')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('أسبوع'),

                        Forms\Components\TextInput::make('duration_days')
                            ->label('المدة بالأيام')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('يوم'),

                        Forms\Components\TextInput::make('max_participants')
                            ->label('الحد الأقصى للمشاركين')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->helperText('العدد الأقصى للمشاركين المسموح به'),

                        Forms\Components\TextInput::make('current_enrollments')
                            ->label('عدد المسجلين الحالي')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('التسعير')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('السعر')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->prefix('$'),

                        Forms\Components\TextInput::make('discounted_price')
                            ->label('السعر بعد الخصم')
                            ->numeric()
                            ->minValue(0)
                            ->lte('price')
                            ->prefix('$')
                            ->helperText('يجب أن يكون أقل من السعر الأصلي'),

                        Forms\Components\Select::make('currency')
                            ->label('العملة')
                            ->options([
                                'USD' => 'دولار أمريكي (USD)',
                                'EUR' => 'يورو (EUR)',
                                'SAR' => 'ريال سعودي (SAR)',
                                'AED' => 'درهم إماراتي (AED)',
                                'EGP' => 'جنيه مصري (EGP)',
                            ])
                            ->required()
                            ->default('USD')
                            ->native(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('حالة النشر')
                    ->schema([
                        Forms\Components\Toggle::make('is_published')
                            ->label('منشور')
                            ->default(false)
                            ->inline(false)
                            ->helperText('هل البرنامج مرئي للطلاب؟'),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('مميز')
                            ->default(false)
                            ->inline(false)
                            ->helperText('عرض البرنامج في القسم المميز'),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('تاريخ النشر')
                            ->native(false),

                        Forms\Components\Select::make('created_by')
                            ->label('أنشئ بواسطة')
                            ->relationship('creator', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('محتوى البرنامج')
                    ->schema([
                        Forms\Components\Repeater::make('units')
                            ->label('الوحدات')
                            ->relationship('units')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('عنوان الوحدة')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('order')
                                    ->label('الترتيب')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->helperText('ترتيب الوحدة في البرنامج'),

                                Forms\Components\Repeater::make('lessons')
                                    ->label('الدروس')
                                    ->relationship('lessons')
                                    ->schema([
                                        Forms\Components\TextInput::make('title')
                                            ->label('عنوان الدرس')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Forms\Components\RichEditor::make('content')
                                            ->label('المحتوى')
                                            ->columnSpanFull()
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'link',
                                                'bulletList',
                                                'orderedList',
                                                'codeBlock',
                                            ]),

                                        Forms\Components\TextInput::make('order')
                                            ->label('الترتيب')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->helperText('ترتيب الدرس في الوحدة'),

                                        Forms\Components\TextInput::make('video_url')
                                            ->label('رابط الفيديو')
                                            ->url()
                                            ->maxLength(255)
                                            ->placeholder('https://youtube.com/watch?v=...')
                                            ->helperText('رابط يوتيوب، فيميو، أو أي فيديو آخر'),

                                        Forms\Components\TextInput::make('resource_link')
                                            ->label('رابط المصدر')
                                            ->url()
                                            ->maxLength(255)
                                            ->placeholder('https://example.com/resource')
                                            ->helperText('رابط مصدر خارجي أو مرجع'),

                                        Forms\Components\FileUpload::make('attachment_path')
                                            ->label('المرفق')
                                            ->directory('lessons/attachments')
                                            ->maxSize(10240)
                                            ->acceptedFileTypes(['application/pdf', 'application/zip', 'application/x-rar'])
                                            ->helperText('ملفات PDF أو ZIP أو RAR (حد أقصى 10 ميجابايت)'),

                                        Forms\Components\Toggle::make('published')
                                            ->label('منشور')
                                            ->default(true)
                                            ->helperText('هل هذا الدرس مرئي للطلاب؟'),

                                        Forms\Components\Section::make('الواجب')
                                            ->schema([
                                                Forms\Components\TextInput::make('assignment.title')
                                                    ->label('عنوان الواجب')
                                                    ->maxLength(255),

                                                Forms\Components\RichEditor::make('assignment.description')
                                                    ->label('وصف الواجب')
                                                    ->columnSpanFull()
                                                    ->toolbarButtons([
                                                        'bold',
                                                        'italic',
                                                        'link',
                                                        'bulletList',
                                                        'orderedList',
                                                    ]),

                                                Forms\Components\DateTimePicker::make('assignment.due_date')
                                                    ->label('تاريخ التسليم')
                                                    ->native(false),

                                                Forms\Components\TextInput::make('assignment.max_score')
                                                    ->label('الدرجة القصوى')
                                                    ->numeric()
                                                    ->default(100)
                                                    ->minValue(0),

                                                Forms\Components\FileUpload::make('assignment.attachment_path')
                                                    ->label('مرفق الواجب')
                                                    ->directory('assignments/attachments')
                                                    ->maxSize(5120)
                                                    ->helperText('ملفات إضافية للواجب (حد أقصى 5 ميجابايت)'),

                                                Forms\Components\Toggle::make('assignment.published')
                                                    ->label('منشور')
                                                    ->default(true)
                                                    ->helperText('هل هذا الواجب نشط؟'),
                                            ])
                                            ->columns(2)
                                            ->collapsed()
                                            ->collapsible(),
                                    ])
                                    ->orderColumn('order')
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'درس')
                                    ->collapsed()
                                    ->collapsible()
                                    ->columnSpanFull()
                                    ->defaultItems(0)
                                    ->addActionLabel('إضافة درس')
                                    ->reorderable()
                                    ->cloneable(),
                            ])
                            ->orderColumn('order')
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'وحدة')
                            ->collapsed()
                            ->collapsible()
                            ->columnSpanFull()
                            ->defaultItems(0)
                            ->addActionLabel('إضافة وحدة')
                            ->reorderable()
                            ->cloneable(),
                    ])
                    ->columnSpanFull()
                    ->collapsed()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image_url')
                    ->label('الغلاف')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.png')),

                Tables\Columns\TextColumn::make('title_ar')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(30),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('النوع')
                    ->colors([
                        'primary' => 'program',
                        'success' => 'diploma',
                        'warning' => 'certificate',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'program' => 'برنامج',
                        'diploma' => 'دبلوم',
                        'certificate' => 'شهادة',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('التصنيف')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('units_count')
                    ->counts('units')
                    ->label('الوحدات')
                    ->badge()
                    ->color('info'),

                Tables\Columns\BadgeColumn::make('difficulty_level')
                    ->label('المستوى')
                    ->colors([
                        'success' => 'beginner',
                        'warning' => 'intermediate',
                        'danger' => 'advanced',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                        default => '—',
                    })
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('delivery_mode')
                    ->label('النمط')
                    ->colors([
                        'info' => 'online',
                        'warning' => 'hybrid',
                        'secondary' => 'offline',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'online' => 'أونلاين',
                        'hybrid' => 'هجين',
                        'offline' => 'حضوري',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_weeks')
                    ->label('المدة')
                    ->suffix(' أسبوع')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('السعر')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('discounted_price')
                    ->label('سعر الخصم')
                    ->money('USD')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('max_participants')
                    ->label('الحد الأقصى')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('current_enrollments')
                    ->label('المسجلين')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('منشور')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('مميز')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('تاريخ النشر')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'program' => 'برنامج',
                        'diploma' => 'دبلوم',
                        'certificate' => 'شهادة',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('difficulty_level')
                    ->label('المستوى')
                    ->options([
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('delivery_mode')
                    ->label('نمط التعليم')
                    ->options([
                        'online' => 'أونلاين',
                        'hybrid' => 'هجين',
                        'offline' => 'حضوري',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('حالة النشر')
                    ->placeholder('الكل')
                    ->trueLabel('منشور')
                    ->falseLabel('غير منشور'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('مميز')
                    ->placeholder('الكل')
                    ->trueLabel('مميز')
                    ->falseLabel('غير مميز'),

                Tables\Filters\SelectFilter::make('category')
                    ->label('التصنيف')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
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
            'index' => Pages\ListPrograms::route('/'),
            'create' => Pages\CreateProgram::route('/create'),
            'view' => Pages\ViewProgram::route('/{record}'),
            'edit' => Pages\EditProgram::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'برنامج';
    }

    public static function getPluralModelLabel(): string
    {
        return 'البرامج';
    }
}