<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'الاشتراكات';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المستخدم')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->required()
                            ->maxLength(20),
                    ])->columns(3),

                Forms\Components\Section::make('تفاصيل الاشتراك')
                    ->schema([
                        Forms\Components\Select::make('plan_id')
                            ->label('الخطة')
                            ->relationship('plan', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('category')
                            ->label('التصنيف')
                            ->options([
                                'bootcamp' => 'بوت كامب',
                                'courses' => 'دورات',
                                'workshops' => 'ورش عمل',
                                'programs' => 'برامج',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('title')
                            ->label('العنوان')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('amount')
                            ->label('المبلغ')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                    ])->columns(2),

                Forms\Components\Section::make('الإيصال والحالة')
                    ->schema([
                        Forms\Components\FileUpload::make('receipt_path')
                            ->label('الإيصال')
                            ->directory('receipts')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable(),

                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'pending' => 'قيد الانتظار',
                                'approved' => 'موافق عليه',
                                'rejected' => 'مرفوض',
                            ])
                            ->required()
                            ->default('pending')
                            ->native(false),

                        Forms\Components\Textarea::make('admin_notes')
                            ->label('ملاحظات المدير')
                            ->maxLength(65535)
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
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
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('البريد')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('التصنيف')
                    ->badge()
                    ->colors([
                        'primary' => 'bootcamp',
                        'success' => 'courses',
                        'warning' => 'workshops',
                        'info' => 'programs',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'bootcamp' => 'بوت كامب',
                        'courses' => 'دورات',
                        'workshops' => 'ورش عمل',
                        'programs' => 'برامج',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('plan.name')
                    ->label('الخطة')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'قيد الانتظار',
                        'approved' => 'موافق عليه',
                        'rejected' => 'مرفوض',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'approved' => 'موافق عليه',
                        'rejected' => 'مرفوض',
                    ])
                    ->multiple(),
                
                SelectFilter::make('category')
                    ->label('التصنيف')
                    ->options([
                        'bootcamp' => 'بوت كامب',
                        'courses' => 'دورات',
                        'workshops' => 'ورش عمل',
                        'programs' => 'برامج',
                    ])
                    ->multiple(),

                SelectFilter::make('plan_id')
                    ->label('الخطة')
                    ->relationship('plan', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Filter::make('amount')
                    ->label('المبلغ')
                    ->form([
                        Forms\Components\TextInput::make('amount_from')
                            ->label('من')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('amount_to')
                            ->label('إلى')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '<=', $amount),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\Action::make('approve')
                        ->label('موافقة')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Subscription $record) {
                            $record->update([
                                'status' => 'approved',
                                'approved_at' => now(),
                                'approved_by' => auth()->id(),
                            ]);
                        })
                        ->visible(fn (Subscription $record) => $record->status === 'pending'),
                    Tables\Actions\Action::make('reject')
                        ->label('رفض')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('admin_notes')
                                ->label('سبب الرفض')
                                ->required(),
                        ])
                        ->action(function (Subscription $record, array $data) {
                            $record->update([
                                'status' => 'rejected',
                                'admin_notes' => $data['admin_notes'],
                                'approved_by' => auth()->id(),
                            ]);
                        })
                        ->visible(fn (Subscription $record) => $record->status === 'pending'),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('الموافقة على المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status === 'pending') {
                                    $record->update([
                                        'status' => 'approved',
                                        'approved_at' => now(),
                                        'approved_by' => auth()->id(),
                                    ]);
                                }
                            });
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getModelLabel(): string
    {
        return 'اشتراك';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الاشتراكات';
    }
}