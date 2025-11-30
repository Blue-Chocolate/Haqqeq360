<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AboutPageResource\Pages;
use App\Models\AboutPage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class AboutPageResource extends Resource
{
    protected static ?string $model = AboutPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';

    protected static ?string $navigationLabel = 'صفحة من نحن';

    protected static ?string $modelLabel = 'صفحة من نحن';

    protected static ?string $pluralModelLabel = 'صفحات من نحن';

    protected static ?string $navigationGroup = 'إدارة المحتوى';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('قسم الهيرو (Hero Section)')
                    ->description('EPIC 11.1 - تفاصيل القسم الرئيسي')
                    ->schema([
                        Forms\Components\TextInput::make('hero_title')
                            ->label('العنوان الرئيسي')
                            ->required()
                            ->maxLength(255)
                            ->default('من نحن في أكاديمية حقق 360')
                            ->helperText('US-ABOUT-HERO-01: عنوان واضح يعكس هوية الأكاديمية'),
                        
                        Forms\Components\Textarea::make('hero_description')
                            ->label('الوصف')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('حد أقصى 30 كلمة')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $wordCount = str_word_count(strip_tags($state ?? ''));
                                if ($wordCount > 30) {
                                    $set('hero_description', null);
                                }
                            }),
                        
                        Forms\Components\FileUpload::make('hero_background_image')
                            ->label('صورة الخلفية')
                            ->image()
                            ->imageEditor()
                            ->directory('about-page/hero')
                            ->maxSize(2048)
                            ->helperText('US-ABOUT-HERO-02: صورة بجودة محسنة للويب (ERR-ABOUT-HERO-02: يتم استخدام لون ثابت عند الفشل)')
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('hero_overlay_opacity')
                            ->label('شفافية الطبقة (Overlay %)')
                            ->numeric()
                            ->default(40)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->helperText('FR-ABOUT-HERO-02: التوصية 40% لضمان وضوح النص (القيمة من 0 إلى 100)'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('قسم من نحن')
                    ->description('EPIC 11.2 - تعريف الأكاديمية')
                    ->schema([
                        Forms\Components\RichEditor::make('about_content')
                            ->label('نص من نحن')
                            ->maxLength(1000)
                            ->helperText('US-ABOUT-CONT-01: حد أقصى 120 كلمة (ERR-ABOUT-CONT-01: Placeholder عند الغياب)')
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'italic',
                                'orderedList',
                                'redo',
                                'undo',
                            ])
                            ->columnSpanFull(),
                        
                        Forms\Components\Toggle::make('show_about_icons')
                            ->label('عرض الأيقونات')
                            ->helperText('FR-ABOUT-CONT-02: استخدام أيقونات أو تنسيق بصري اختياري')
                            ->default(false),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('قسم الرؤية')
                    ->description('EPIC 11.3 - رؤية الأكاديمية')
                    ->schema([
                        Forms\Components\Toggle::make('show_vision_section')
                            ->label('عرض قسم الرؤية')
                            ->default(true)
                            ->live()
                            ->helperText('FR-ABOUT-VIS-03: إخفاء القسم عند عدم التوفر')
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('vision_title')
                            ->label('عنوان الرؤية')
                            ->required()
                            ->maxLength(255)
                            ->default('رؤيتنا')
                            ->visible(fn (Forms\Get $get) => $get('show_vision_section'))
                            ->helperText('US-ABOUT-VIS-01: عرض عنوان "رؤيتنا"'),
                        
                        Forms\Components\Textarea::make('vision_content')
                            ->label('نص الرؤية')
                            ->rows(4)
                            ->maxLength(500)
                            ->visible(fn (Forms\Get $get) => $get('show_vision_section'))
                            ->helperText('حد أقصى 60 كلمة (ERR-ABOUT-VIS-01: نص افتراضي عند الغياب)')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $wordCount = str_word_count(strip_tags($state ?? ''));
                                if ($wordCount > 60) {
                                    $set('vision_content', null);
                                }
                            }),
                        
                        Forms\Components\FileUpload::make('vision_icon')
                            ->label('أيقونة الرؤية')
                            ->image()
                            ->directory('about-page/vision')
                            ->maxSize(1024)
                            ->visible(fn (Forms\Get $get) => $get('show_vision_section'))
                            ->helperText('اختياري: عنصر دعم بصري')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('إعدادات النشر')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('مفعل')
                            ->default(true)
                            ->helperText('FR-ABOUT-HERO-03: التحكم في العرض'),
                        
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'draft' => 'مسودة',
                                'published' => 'منشور',
                            ])
                            ->default('published')
                            ->required(),
                        
                        Forms\Components\TextInput::make('display_order')
                            ->label('ترتيب العرض')
                            ->numeric()
                            ->default(0)
                            ->helperText('رقم أقل = أولوية أعلى'),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('hero_title')
                    ->label('العنوان الرئيسي')
                    ->searchable()
                    ->limit(50)
                    ->sortable(),
                
                Tables\Columns\ImageColumn::make('hero_background_image')
                    ->label('صورة الخلفية')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.jpg')),
                
                Tables\Columns\IconColumn::make('show_vision_section')
                    ->label('عرض الرؤية')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('مفعل')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
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
                
                Tables\Columns\TextColumn::make('display_order')
                    ->label('الترتيب')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'published' => 'منشور',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('مفعل')
                    ->placeholder('الكل')
                    ->trueLabel('مفعل')
                    ->falseLabel('غير مفعل'),
                
                Tables\Filters\TernaryFilter::make('show_vision_section')
                    ->label('عرض قسم الرؤية')
                    ->placeholder('الكل')
                    ->trueLabel('معروض')
                    ->falseLabel('مخفي'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('display_order', 'asc')
            ->poll('30s');
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
            'index' => Pages\ListAboutPages::route('/'),
            'create' => Pages\CreateAboutPage::route('/create'),
            'edit' => Pages\EditAboutPage::route('/{record}/edit'),
            'view' => Pages\ViewAboutPage::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}