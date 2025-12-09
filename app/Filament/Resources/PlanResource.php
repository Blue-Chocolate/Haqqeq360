<?php

// app/Filament/Resources/PlanResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use App\Models\Workshop;
use App\Models\Bootcamp;
use App\Models\Program;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Get;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'الاشتراكات';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الخطة')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الخطة')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: الخطة المميزة، الباقة الأساسية'),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->maxLength(65535)
                            ->rows(3)
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('price')
                            ->label('السعر')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->helperText('الخطط النشطة فقط ستكون مرئية للمستخدمين'),
                    ])->columns(2),

                Forms\Components\Section::make('نوع الخطة')
                    ->description('اختر ما تنطبق عليه هذه الخطة')
                    ->schema([
                        Forms\Components\Select::make('planable_type')
                            ->label('النوع')
                            ->options([
                                'App\Models\Workshop' => 'ورشة عمل',
                                'App\Models\Bootcamp' => 'بوت كامب',
                                'App\Models\Program' => 'برنامج',
                                'App\Models\Course' => 'دورة',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('planable_id', null)),
                        
                        Forms\Components\Select::make('planable_id')
                            ->label('اختر العنصر')
                            ->options(function (Get $get) {
                                $type = $get('planable_type');
                                if (!$type) {
                                    return [];
                                }
                                
                                return match($type) {
                                    'App\Models\Workshop' => Workshop::pluck('title', 'id'),
                                    'App\Models\Bootcamp' => Bootcamp::pluck('title', 'id'),
                                    'App\Models\Program' => Program::pluck('title_ar', 'id'),
                                    'App\Models\Course' => Course::pluck('title', 'id'),
                                    default => [],
                                };
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('اختر ورشة العمل، البوت كامب، البرنامج، أو الدورة المحددة'),
                    ])->columns(2),

                Forms\Components\Section::make('الميزات')
                    ->schema([
                        Forms\Components\KeyValue::make('features')
                            ->label('ميزات الخطة')
                            ->keyLabel('اسم الميزة')
                            ->valueLabel('قيمة الميزة')
                            ->reorderable()
                            ->addActionLabel('إضافة ميزة')
                            ->helperText('أضف الميزات المتضمنة في هذه الخطة (مثال: "المدة": "3 أشهر"، "الدعم": "24/7")'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('الرقم')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الخطة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('plan_type')
                    ->label('النوع')
                    ->badge()
                    ->colors([
                        'primary' => 'Bootcamp',
                        'success' => 'Course',
                        'warning' => 'Workshop',
                        'info' => 'Program',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Bootcamp' => 'بوت كامب',
                        'Course' => 'دورة',
                        'Workshop' => 'ورشة عمل',
                        'Program' => 'برنامج',
                        default => $state,
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('planable.title')
                    ->label('العنصر')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('price')
                    ->label('السعر')
                    ->money('USD')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->counts('subscriptions')
                    ->label('الاشتراكات')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                
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
            ])
            ->filters([
                SelectFilter::make('planable_type')
                    ->label('النوع')
                    ->options([
                        'App\Models\Workshop' => 'ورشة عمل',
                        'App\Models\Bootcamp' => 'بوت كامب',
                        'App\Models\Program' => 'برنامج',
                        'App\Models\Course' => 'دورة',
                    ])
                    ->multiple(),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('حالة النشاط')
                    ->placeholder('الكل')
                    ->trueLabel('نشط فقط')
                    ->falseLabel('غير نشط فقط')
                    ->native(false),
                
                Filter::make('price')
                    ->label('السعر')
                    ->form([
                        Forms\Components\TextInput::make('price_from')
                            ->label('السعر الأدنى')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('price_to')
                            ->label('السعر الأقصى')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_from'],
                                fn (Builder $query, $price): Builder => $query->where('price', '>=', $price),
                            )
                            ->when(
                                $data['price_to'],
                                fn (Builder $query, $price): Builder => $query->where('price', '<=', $price),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['price_from'] ?? null) {
                            $indicators[] = 'الحد الأدنى: $' . number_format($data['price_from'], 2);
                        }
                        if ($data['price_to'] ?? null) {
                            $indicators[] = 'الحد الأقصى: $' . number_format($data['price_to'], 2);
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn (Plan $record) => $record->is_active ? 'إلغاء التفعيل' : 'تفعيل')
                        ->icon(fn (Plan $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn (Plan $record) => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->action(fn (Plan $record) => $record->update(['is_active' => !$record->is_active])),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('تفعيل المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('إلغاء تفعيل المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
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
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'خطة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الخطط';
    }
}