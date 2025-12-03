<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoursePublishRequestResource\Pages;
use App\Models\CoursePublishRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CoursePublishRequestResource extends Resource
{
    protected static ?string $model = CoursePublishRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    
    protected static ?string $navigationLabel = 'طلبات نشر الدورات';
    
    protected static ?string $modelLabel = 'طلب نشر';
    
    protected static ?string $pluralModelLabel = 'طلبات النشر';
    
    protected static ?string $navigationGroup = 'إدارة الطلبات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الطلب')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->label('الدورة')
                            ->relationship('course', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $course = \App\Models\Course::find($state);
                                    if ($course) {
                                        $set('category', $course->category ?? null);
                                    }
                                }
                            })
                            ->helperText('اختر الدورة المراد نشرها'),

                        Forms\Components\Select::make('user_id')
                            ->label('مقدم الطلب')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->id()),

                        Forms\Components\TextInput::make('category')
                            ->label('التصنيف')
                            ->maxLength(255)
                            ->helperText('تصنيف الدورة'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('المحتوى والمرفقات')
                    ->schema([
                        Forms\Components\FileUpload::make('attachment_path')
                            ->label('مرفقات إضافية')
                            ->directory('course-publish-attachments')
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/zip'])
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->helperText('أي ملفات إضافية تدعم طلب النشر (حد أقصى 10MB)'),

                        Forms\Components\RichEditor::make('uploaded_content')
                            ->label('المحتوى المرفوع')
                            ->columnSpanFull()
                            ->helperText('وصف أو ملاحظات حول محتوى الدورة'),
                    ]),

                Forms\Components\Section::make('حالة الطلب')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'pending' => 'قيد المراجعة',
                                'approved' => 'مقبول',
                                'rejected' => 'مرفوض',
                            ])
                            ->required()
                            ->default('pending')
                            ->native(false)
                            ->live(),

                        Forms\Components\RichEditor::make('admin_notes')
                            ->label('ملاحظات الإدارة')
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get) => in_array($get('status'), ['approved', 'rejected']))
                            ->helperText('ملاحظات أو تعليقات من الإدارة حول قرار الطلب'),
                    ]),
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

                Tables\Columns\TextColumn::make('course.name')
                    ->label('اسم الدورة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('مقدم الطلب')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('التصنيف')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'قيد المراجعة',
                        'approved' => 'مقبول',
                        'rejected' => 'مرفوض',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('attachment_path')
                    ->label('مرفقات')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-x-mark')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('uploaded_content')
                    ->label('محتوى')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-x-mark')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('admin_notes')
                    ->label('ملاحظات')
                    ->boolean()
                    ->trueIcon('heroicon-o-chat-bubble-left-right')
                    ->falseIcon('heroicon-o-x-mark')
                    ->alignCenter()
                    ->toggleable(),

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
                        'approved' => 'مقبول',
                        'rejected' => 'مرفوض',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('course_id')
                    ->label('الدورة')
                    ->relationship('course', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('مقدم الطلب')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\Filter::make('has_attachments')
                    ->label('يحتوي على مرفقات')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('attachment_path')),

                Tables\Filters\Filter::make('has_admin_notes')
                    ->label('يحتوي على ملاحظات إدارية')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('admin_notes')),

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
                        ->label('قبول وأنشر')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\RichEditor::make('admin_notes')
                                ->label('ملاحظات الموافقة')
                                ->helperText('أضف أي ملاحظات أو تعليقات للموافقة'),
                        ])
                        ->action(function (CoursePublishRequest $record, array $data) {
                            $record->update([
                                'status' => 'approved',
                                'admin_notes' => $data['admin_notes'] ?? null,
                            ]);
                            
                            // Optionally publish the course
                            if ($record->course) {
                                $record->course->update(['is_published' => true]);
                            }
                            
                            Notification::make()
                                ->success()
                                ->title('تم قبول الطلب')
                                ->body('تم قبول طلب نشر الدورة بنجاح')
                                ->send();
                        })
                        ->visible(fn (CoursePublishRequest $record) => $record->status === 'pending'),

                    Tables\Actions\Action::make('reject')
                        ->label('رفض')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\RichEditor::make('admin_notes')
                                ->label('سبب الرفض')
                                ->required()
                                ->helperText('يرجى توضيح سبب رفض طلب النشر'),
                        ])
                        ->action(function (CoursePublishRequest $record, array $data) {
                            $record->update([
                                'status' => 'rejected',
                                'admin_notes' => $data['admin_notes'],
                            ]);
                            
                            Notification::make()
                                ->warning()
                                ->title('تم رفض الطلب')
                                ->body('تم رفض طلب نشر الدورة')
                                ->send();
                        })
                        ->visible(fn (CoursePublishRequest $record) => $record->status === 'pending'),

                    Tables\Actions\Action::make('view_course')
                        ->label('عرض الدورة')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn (CoursePublishRequest $record): string => 
                            route('filament.admin.resources.courses.view', $record->course_id)
                        )
                        ->openUrlInNewTab()
                        ->visible(fn (CoursePublishRequest $record) => $record->course_id),

                    Tables\Actions\Action::make('reset_status')
                        ->label('إعادة للمراجعة')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (CoursePublishRequest $record) {
                            $record->update(['status' => 'pending']);
                            Notification::make()
                                ->info()
                                ->title('تم إعادة الطلب للمراجعة')
                                ->send();
                        })
                        ->visible(fn (CoursePublishRequest $record) => $record->status !== 'pending'),

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
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['status' => 'approved']);
                                if ($record->course) {
                                    $record->course->update(['is_published' => true]);
                                }
                            });
                            
                            Notification::make()
                                ->success()
                                ->title('تم قبول الطلبات المحددة')
                                ->body(count($records) . ' طلب تم قبولها')
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('reject_selected')
                        ->label('رفض المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $records->each->update(['status' => 'rejected']);
                            
                            Notification::make()
                                ->warning()
                                ->title('تم رفض الطلبات المحددة')
                                ->body(count($records) . ' طلب تم رفضها')
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
            'index' => Pages\ListCoursePublishRequests::route('/'),
            'create' => Pages\CreateCoursePublishRequest::route('/create'),
            'view' => Pages\ViewCoursePublishRequest::route('/{record}'),
            'edit' => Pages\EditCoursePublishRequest::route('/{record}/edit'),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['course', 'user']);
    }
}