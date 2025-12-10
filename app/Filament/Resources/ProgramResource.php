<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramResource\Pages;
use App\Models\Program;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
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
                            ->unique(ignoreRecord: true),

                        Forms\Components\Textarea::make('description_ar')
                            ->label('الوصف بالعربية')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description_en')
                            ->label('الوصف بالإنجليزية')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('cover_image_url')
                            ->label('صورة الغلاف')
                            ->image()
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
                            ->preload(),

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
                                'blended' => 'هجين',
                                'in_person' => 'حضوري',
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

                        Forms\Components\TextInput::make('seats')
                            ->label('عدد المقاعد')
                            ->numeric()
                            ->minValue(1),

                        Forms\Components\TextInput::make('max_participants')
                            ->label('الحد الأقصى للمشاركين')
                            ->numeric()
                            ->minValue(1),

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
                            ->minValue(0),

                        Forms\Components\TextInput::make('discounted_price')
                            ->label('السعر بعد الخصم')
                            ->numeric()
                            ->minValue(0)
                            ->lte('price'),

                        Forms\Components\Select::make('currency')
                            ->label('العملة')
                            ->options([
                                'SAR' => 'ريال سعودي (SAR)',
                                'USD' => 'دولار أمريكي (USD)',
                                'EUR' => 'يورو (EUR)',
                                'AED' => 'درهم إماراتي (AED)',
                                'EGP' => 'جنيه مصري (EGP)',
                            ])
                            ->required()
                            ->default('SAR')
                            ->native(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('حالة النشر')
                    ->schema([
                        Forms\Components\Toggle::make('is_published')
                            ->label('منشور')
                            ->default(false),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('مميز')
                            ->default(false),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('تاريخ النشر')
                            ->native(false),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image_url')
                    ->label('الغلاف')
                    ->circular(),

                Tables\Columns\TextColumn::make('title_ar')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('التصنيف')
                    ->searchable()
                    ->sortable(),

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
                    }),

                Tables\Columns\BadgeColumn::make('delivery_mode')
                    ->label('النمط')
                    ->colors([
                        'info' => 'online',
                        'warning' => 'blended',
                        'secondary' => 'in_person',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'online' => 'أونلاين',
                        'blended' => 'هجين',
                        'in_person' => 'حضوري',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('duration_weeks')
                    ->label('المدة')
                    ->suffix(' أسبوع')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('السعر')
                    ->formatStateUsing(fn ($state, $record) => number_format($state, 2) . ' ' . $record->currency)
                    ->sortable(),

                Tables\Columns\TextColumn::make('seats')
                    ->label('المقاعد')
                    ->numeric(),

                Tables\Columns\TextColumn::make('current_enrollments')
                    ->label('المسجلين')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('منشور')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('مميز')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('difficulty_level')
                    ->label('المستوى')
                    ->options([
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                    ]),

                Tables\Filters\SelectFilter::make('delivery_mode')
                    ->label('نمط التعليم')
                    ->options([
                        'online' => 'أونلاين',
                        'blended' => 'هجين',
                        'in_person' => 'حضوري',
                    ]),

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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
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