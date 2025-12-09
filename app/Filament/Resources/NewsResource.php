<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsResource\Pages;
use App\Models\News;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class NewsResource extends Resource
{
    protected static ?string $model = News::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    
    protected static ?string $navigationGroup = 'إدارة صفحات الموقع';
    
    protected static ?string $navigationLabel = 'الأخبار';
    
    protected static ?string $pluralModelLabel = 'الأخبار';
    
    protected static ?string $modelLabel = 'خبر';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الخبر الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الخبر')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('حالة النشر')
                            ->options([
                                'draft' => 'مسودة',
                                'published' => 'منشور',
                            ])
                            ->required()
                            ->default('draft')
                            ->native(false)
                            ->helperText('اختر "منشور" لإظهار الخبر على الموقع'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('محتوى الخبر')
                    ->schema([
                        Forms\Components\Textarea::make('content')
                            ->label('نص الخبر')
                            ->required()
                            ->rows(8)
                            ->maxLength(5000)
                            ->helperText('حد أقصى 5000 حرف')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('صورة الخبر')
                    ->schema([
                        Forms\Components\FileUpload::make('image_path')
                            ->label('صورة الخبر')
                            ->directory('news')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->maxSize(5120) // 5MB
                            ->imagePreviewHeight('250')
                            ->helperText('الحجم الأقصى: 5 ميجابايت | النسب الموصى بها: 16:9')
                            ->nullable()
                            ->downloadable(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('الصورة')
                    ->square()
                    ->size(60)
                    ->defaultImageUrl(url('/images/default-news.png')),

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('content')
                    ->label('المحتوى')
                    ->limit(60)
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'draft' => 'مسودة',
                        'published' => 'منشور',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'published',
                        'warning' => 'draft',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d M Y - h:i A')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime('d M Y - h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('حالة النشر')
                    ->options([
                        'draft' => 'مسودة',
                        'published' => 'منشور',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('has_image')
                    ->label('يحتوي على صورة')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('image_path')),

                Tables\Filters\Filter::make('no_image')
                    ->label('بدون صورة')
                    ->query(fn (Builder $query): Builder => $query->whereNull('image_path')),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'من: ' . \Carbon\Carbon::parse($data['created_from'])->format('d/m/Y');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'إلى: ' . \Carbon\Carbon::parse($data['created_until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),

                Tables\Filters\TrashedFilter::make()
                    ->label('المحذوفات')
                    ->placeholder('بدون محذوفات')
                    ->trueLabel('مع المحذوفات فقط')
                    ->falseLabel('بدون محذوفات'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),

                Tables\Actions\EditAction::make()
                    ->label('تعديل'),

                Tables\Actions\Action::make('toggleStatus')
                    ->label(fn (News $record) => $record->status === 'published' ? 'إلغاء النشر' : 'نشر')
                    ->icon(fn (News $record) => $record->status === 'published' ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (News $record) => $record->status === 'published' ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function (News $record) {
                        try {
                            $newStatus = $record->status === 'published' ? 'draft' : 'published';
                            $record->update(['status' => $newStatus]);

                            Notification::make()
                                ->success()
                                ->title('تم التحديث')
                                ->body($newStatus === 'published' ? 'تم نشر الخبر بنجاح.' : 'تم إلغاء نشر الخبر.')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('خطأ')
                                ->body('حدث خطأ أثناء تحديث حالة الخبر.')
                                ->send();
                        }
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('تم الحذف')
                            ->body('تم حذف الخبر بنجاح.')
                    )
                    ->errorNotification(
                        Notification::make()
                            ->danger()
                            ->title('خطأ')
                            ->body('حدث خطأ أثناء حذف الخبر.')
                    ),

                Tables\Actions\RestoreAction::make()
                    ->label('استعادة')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('تمت الاستعادة')
                            ->body('تمت استعادة الخبر بنجاح.')
                    ),

                Tables\Actions\ForceDeleteAction::make()
                    ->label('حذف نهائي')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('تم الحذف النهائي')
                            ->body('تم حذف الخبر نهائياً من قاعدة البيانات.')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('تم الحذف')
                                ->body('تم حذف الأخبار المحددة بنجاح.')
                        ),

                    Tables\Actions\RestoreBulkAction::make()
                        ->label('استعادة المحدد'),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label('حذف نهائي للمحدد'),

                    Tables\Actions\BulkAction::make('publish')
                        ->label('نشر المحدد')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('نشر الأخبار المحددة')
                        ->modalDescription('هل أنت متأكد من نشر جميع الأخبار المحددة؟')
                        ->modalSubmitActionLabel('نعم، نشر الكل')
                        ->action(function ($records) {
                            try {
                                $count = $records->count();
                                $records->each->update(['status' => 'published']);
                                
                                Notification::make()
                                    ->success()
                                    ->title('تم النشر')
                                    ->body("تم نشر {$count} من الأخبار بنجاح.")
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('خطأ')
                                    ->body('حدث خطأ أثناء نشر الأخبار.')
                                    ->send();
                            }
                        }),

                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('إلغاء نشر المحدد')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            try {
                                $count = $records->count();
                                $records->each->update(['status' => 'draft']);
                                
                                Notification::make()
                                    ->success()
                                    ->title('تم إلغاء النشر')
                                    ->body("تم إلغاء نشر {$count} من الأخبار بنجاح.")
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('خطأ')
                                    ->body('حدث خطأ أثناء إلغاء نشر الأخبار.')
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('لا توجد أخبار')
            ->emptyStateDescription('ابدأ بإنشاء خبر جديد')
            ->emptyStateIcon('heroicon-o-newspaper')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('إنشاء خبر جديد')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'edit'   => Pages\EditNews::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'published')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::where('status', 'published')->count();
        return $count > 0 ? 'success' : 'gray';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'content'];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}