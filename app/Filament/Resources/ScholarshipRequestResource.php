<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScholarshipRequestResource\Pages;
use App\Models\ScholarshipRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ScholarshipRequestResource extends Resource
{
    protected static ?string $model = ScholarshipRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'طلبات المنح';
    
    protected static ?string $modelLabel = 'طلب منحة';
    
    protected static ?string $pluralModelLabel = 'طلبات المنح';
    
    protected static ?string $navigationGroup = 'إدارة الطلبات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المتقدم')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('المستخدم')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('اختياري - إذا كان المتقدم مستخدم مسجل'),

                        Forms\Components\TextInput::make('applicant_name')
                            ->label('اسم المتقدم')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('number_of_participants')
                            ->label('عدد المشاركين')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(100),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('تفاصيل البرنامج')
                    ->schema([
                        Forms\Components\Select::make('program_type')
                            ->label('نوع البرنامج')
                            ->options([
                                'course' => 'دورة تدريبية',
                                'bootcamp' => 'معسكر تدريبي',
                                'workshop' => 'ورشة عمل',
                                'training_program' => 'برنامج تدريبي',
                                'certification' => 'شهادة احترافية',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('skills_and_needs')
                            ->label('المهارات والاحتياجات')
                            ->rows(4)
                            ->columnSpanFull()
                            ->helperText('صف المهارات التي تود تطويرها والاحتياجات التدريبية'),
                    ]),

                Forms\Components\Section::make('المرفقات والحالة')
                    ->schema([
                        Forms\Components\FileUpload::make('attachments')
                            ->label('المرفقات')
                            ->directory('scholarship-attachments')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120)
                            ->downloadable()
                            ->openable()
                            ->helperText('الملفات المطلوبة: السيرة الذاتية، خطاب الدافع، أو أي مستندات داعمة'),

                        Forms\Components\Select::make('status')
                            ->label('حالة الطلب')
                            ->options([
                                'pending' => 'قيد المراجعة',
                                'under_review' => 'تحت الدراسة',
                                'approved' => 'مقبول',
                                'rejected' => 'مرفوض',
                                'waitlisted' => 'قائمة الانتظار',
                            ])
                            ->required()
                            ->default('pending')
                            ->native(false)
                            ->live(),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->rows(3)
                            ->visible(fn (Forms\Get $get) => $get('status') === 'rejected')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('رقم الطلب')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('اسم المتقدم')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم المسجل')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->default('غير مسجل'),

                Tables\Columns\TextColumn::make('number_of_participants')
                    ->label('عدد المشاركين')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('program_type')
                    ->label('نوع البرنامج')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'course' => 'دورة تدريبية',
                        'bootcamp' => 'معسكر تدريبي',
                        'workshop' => 'ورشة عمل',
                        'training_program' => 'برنامج تدريبي',
                        'certification' => 'شهادة احترافية',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'course' => 'success',
                        'bootcamp' => 'warning',
                        'workshop' => 'info',
                        'training_program' => 'primary',
                        'certification' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'قيد المراجعة',
                        'under_review' => 'تحت الدراسة',
                        'approved' => 'مقبول',
                        'rejected' => 'مرفوض',
                        'waitlisted' => 'قائمة الانتظار',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'pending' => 'warning',
                        'under_review' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'waitlisted' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('attachments')
                    ->label('مرفقات')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-x-mark')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التقديم')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد المراجعة',
                        'under_review' => 'تحت الدراسة',
                        'approved' => 'مقبول',
                        'rejected' => 'مرفوض',
                        'waitlisted' => 'قائمة الانتظار',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('program_type')
                    ->label('نوع البرنامج')
                    ->options([
                        'course' => 'دورة تدريبية',
                        'bootcamp' => 'معسكر تدريبي',
                        'workshop' => 'ورشة عمل',
                        'training_program' => 'برنامج تدريبي',
                        'certification' => 'شهادة احترافية',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('has_attachments')
                    ->label('يحتوي على مرفقات')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('attachments')),

                Tables\Filters\Filter::make('created_at')
                    ->label('تاريخ التقديم')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('من'),
                        Forms\Components\DatePicker::make('until')
                            ->label('إلى'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('approve')
                        ->label('قبول')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (ScholarshipRequest $record) {
                            $record->update(['status' => 'approved']);
                            Notification::make()
                                ->success()
                                ->title('تم قبول الطلب')
                                ->body('تم قبول طلب المنحة بنجاح')
                                ->send();
                        })
                        ->visible(fn (ScholarshipRequest $record) => $record->status !== 'approved'),

                    Tables\Actions\Action::make('reject')
                        ->label('رفض')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('سبب الرفض')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (ScholarshipRequest $record, array $data) {
                            $record->update([
                                'status' => 'rejected',
                                'rejection_reason' => $data['rejection_reason'] ?? null,
                            ]);
                            Notification::make()
                                ->warning()
                                ->title('تم رفض الطلب')
                                ->body('تم رفض طلب المنحة')
                                ->send();
                        })
                        ->visible(fn (ScholarshipRequest $record) => $record->status !== 'rejected'),

                    Tables\Actions\Action::make('under_review')
                        ->label('تحت الدراسة')
                        ->icon('heroicon-o-clock')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (ScholarshipRequest $record) {
                            $record->update(['status' => 'under_review']);
                            Notification::make()
                                ->info()
                                ->title('الطلب تحت الدراسة')
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('قبول المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['status' => 'approved']);
                            Notification::make()
                                ->success()
                                ->title('تم قبول الطلبات المحددة')
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('reject_selected')
                        ->label('رفض المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['status' => 'rejected']);
                            Notification::make()
                                ->warning()
                                ->title('تم رفض الطلبات المحددة')
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListScholarshipRequests::route('/'),
            'create' => Pages\CreateScholarshipRequest::route('/create'),
            'view' => Pages\ViewScholarshipRequest::route('/{record}'),
            'edit' => Pages\EditScholarshipRequest::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}