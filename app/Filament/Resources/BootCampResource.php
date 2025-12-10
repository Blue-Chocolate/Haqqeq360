<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BootCampResource\Pages;
use App\Models\Bootcamp;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BootCampResource extends Resource
{
    protected static ?string $model = Bootcamp::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'إدارة المنتجات';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('العنوان')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('cover_image')
                            ->label('صورة الغلاف')
                            ->image()
                            ->imageEditor()
                            ->directory('bootcamps/covers')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('تفاصيل المعسكر')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('المدرب')
                            ->relationship('instructor', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('الاسم')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('password')
                                    ->label('كلمة المرور')
                                    ->password()
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\TextInput::make('duration_weeks')
                            ->label('المدة (بالأسابيع)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(52)
                            ->suffix('أسبوع'),

                        Forms\Components\Select::make('level')
                            ->label('المستوى')
                            ->options([
                                'beginner' => 'مبتدئ',
                                'intermediate' => 'متوسط',
                                'advanced' => 'متقدم',
                            ])
                            ->required()
                            ->default('beginner')
                            ->native(false),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ البدء')
                            ->native(false)
                            ->displayFormat('M d, Y')
                            ->minDate(now()),

                        Forms\Components\Select::make('mode')
                            ->label('نمط التعليم')
                            ->options([
                                'online' => 'أونلاين',
                                'hybrid' => 'هجين',
                                'offline' => 'حضوري',
                            ])
                            ->required()
                            ->default('online')
                            ->native(false),

                        Forms\Components\TextInput::make('seats')
                            ->label('إجمالي المقاعد')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->required()
                            ->helperText('العدد الإجمالي للمقاعد المتاحة لهذا المعسكر')
                            ->suffix('مقعد'),

                        Forms\Components\Toggle::make('certificate')
                            ->label('يوفر شهادة')
                            ->default(false)
                            ->inline(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('الغلاف')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.png')),

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(30),

                Tables\Columns\TextColumn::make('instructor.name')
                    ->label('المدرب')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('level')
                    ->label('المستوى')
                    ->colors([
                        'success' => 'beginner',
                        'warning' => 'intermediate',
                        'danger' => 'advanced',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                    })
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('mode')
                    ->label('النمط')
                    ->colors([
                        'primary' => 'online',
                        'info' => 'hybrid',
                        'secondary' => 'offline',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'online' => 'أونلاين',
                        'hybrid' => 'هجين',
                        'offline' => 'حضوري',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_weeks')
                    ->label('المدة')
                    ->suffix(' أسبوع')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('seats')
                    ->label('إجمالي المقاعد')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('enrollments_count')
                    ->counts('enrollments')
                    ->label('المسجلين')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('available_seats')
                    ->label('المتاح')
                    ->state(function (Bootcamp $record): int {
                        return max(0, $record->seats - $record->enrollments_count);
                    })
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->withCount('enrollments')
                            ->orderByRaw("(seats - enrollments_count) {$direction}");
                    }),

                Tables\Columns\IconColumn::make('certificate')
                    ->label('شهادة')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البدء')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('تاريخ الحذف')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label('المستوى')
                    ->options([
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('mode')
                    ->label('نمط التعليم')
                    ->options([
                        'online' => 'أونلاين',
                        'hybrid' => 'هجين',
                        'offline' => 'حضوري',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('certificate')
                    ->label('يوفر شهادة')
                    ->placeholder('جميع المعسكرات')
                    ->trueLabel('مع شهادة')
                    ->falseLabel('بدون شهادة'),

                Tables\Filters\Filter::make('start_date')
                    ->label('تاريخ البدء')
                    ->form([
                        Forms\Components\DatePicker::make('start_from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('start_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['start_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('instructor')
                    ->label('المدرب')
                    ->relationship('instructor', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('seats_available')
                    ->label('يوجد مقاعد متاحة')
                    ->query(fn (Builder $query): Builder => 
                        $query->withCount('enrollments')
                              ->whereRaw('seats > enrollments_count')
                    ),

                Tables\Filters\Filter::make('fully_booked')
                    ->label('مكتمل الحجز')
                    ->query(fn (Builder $query): Builder => 
                        $query->withCount('enrollments')
                              ->whereRaw('seats <= enrollments_count')
                    ),

                Tables\Filters\TrashedFilter::make()
                    ->label('المحذوفات'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
                Tables\Actions\ForceDeleteAction::make()
                    ->label('حذف نهائي'),
                Tables\Actions\RestoreAction::make()
                    ->label('استعادة'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label('حذف نهائي للمحدد'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label('استعادة المحدد'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات المعسكر')
                    ->schema([
                        Infolists\Components\ImageEntry::make('cover_image')
                            ->label('صورة الغلاف')
                            ->columnSpanFull()
                            ->height(200),

                        Infolists\Components\TextEntry::make('title')
                            ->label('العنوان')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('description')
                            ->label('الوصف')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('instructor.name')
                            ->label('المدرب'),

                        Infolists\Components\TextEntry::make('level')
                            ->label('المستوى')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'beginner' => 'مبتدئ',
                                'intermediate' => 'متوسط',
                                'advanced' => 'متقدم',
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'beginner' => 'success',
                                'intermediate' => 'warning',
                                'advanced' => 'danger',
                            }),

                        Infolists\Components\TextEntry::make('mode')
                            ->label('نمط التعليم')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'online' => 'أونلاين',
                                'hybrid' => 'هجين',
                                'offline' => 'حضوري',
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'online' => 'primary',
                                'hybrid' => 'info',
                                'offline' => 'secondary',
                            }),

                        Infolists\Components\TextEntry::make('duration_weeks')
                            ->label('المدة')
                            ->suffix(' أسبوع'),

                        Infolists\Components\TextEntry::make('seats')
                            ->label('إجمالي المقاعد'),

                        Infolists\Components\TextEntry::make('enrollments_count')
                            ->label('الطلاب المسجلين')
                            ->state(fn (Bootcamp $record): int => $record->enrollments()->count()),

                        Infolists\Components\TextEntry::make('available_seats')
                            ->label('المقاعد المتاحة')
                            ->state(function (Bootcamp $record): int {
                                $enrolled = $record->enrollments()->count();
                                return max(0, $record->seats - $enrolled);
                            })
                            ->badge()
                            ->color(function (Bootcamp $record): string {
                                $enrolled = $record->enrollments()->count();
                                $available = max(0, $record->seats - $enrolled);
                                return match (true) {
                                    $available === 0 => 'danger',
                                    $available <= 5 => 'warning',
                                    default => 'success',
                                };
                            }),

                        Infolists\Components\TextEntry::make('start_date')
                            ->label('تاريخ البدء')
                            ->date('F d, Y'),

                        Infolists\Components\IconEntry::make('certificate')
                            ->label('يوفر شهادة')
                            ->boolean(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('البيانات الوصفية')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('تاريخ التحديث')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('deleted_at')
                            ->label('تاريخ الحذف')
                            ->dateTime()
                            ->visible(fn ($record) => $record->trashed()),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ]);
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
            'index' => Pages\ListBootCamps::route('/'),
            'create' => Pages\CreateBootCamp::route('/create'),
            'view' => Pages\ViewBootCamp::route('/{record}'),
            'edit' => Pages\EditBootCamp::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->withCount('enrollments');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description', 'instructor.name'];
    }

    public static function getModelLabel(): string
    {
        return 'معسكر';
    }

    public static function getPluralModelLabel(): string
    {
        return 'معسكرات';
    }
}