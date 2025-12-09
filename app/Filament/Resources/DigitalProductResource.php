<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DigitalProductResource\Pages;
use App\Models\DigitalProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class DigitalProductResource extends Resource
{
    protected static ?string $model = DigitalProduct::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    
    protected static ?string $navigationGroup = 'إدارة المنتجات';
    
    protected static ?string $navigationLabel = 'المنتجات الرقمية';
    
    protected static ?string $modelLabel = 'منتج رقمي';
    
    protected static ?string $pluralModelLabel = 'المنتجات الرقمية';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('معلومات المنتج الأساسية')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('العنوان')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                            if ($operation !== 'create') {
                                return;
                            }
                            $set('slug', \Illuminate\Support\Str::slug($state));
                        }),

                    Forms\Components\TextInput::make('slug')
                        ->label('الرابط الثابت')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->rules(['alpha_dash'])
                        ->helperText('يجب أن يحتوي على حروف وأرقام وشرطات فقط'),

                    Forms\Components\Select::make('type')
                        ->label('نوع المنتج')
                        ->options([
                            'e-book' => 'كتاب إلكتروني',
                            'audio' => 'ملف صوتي',
                            'video' => 'ملف فيديو',
                            'software' => 'برنامج',
                        ])
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'draft' => 'مسودة',
                            'published' => 'منشور',
                        ])
                        ->default('draft')
                        ->required()
                        ->native(false),
                ])
                ->columns(2),

            Forms\Components\Section::make('التفاصيل')
                ->schema([
                    Forms\Components\Textarea::make('description')
                        ->label('الوصف')
                        ->required()
                        ->rows(5)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('الملف الرقمي')
                ->schema([
                    Forms\Components\FileUpload::make('file_path')
                        ->label('ملف المنتج')
                        ->directory('digital_products')
                        ->required()
                        ->maxSize(102400) // 100MB
                        ->acceptedFileTypes(['application/pdf', 'audio/*', 'video/*', 'application/zip'])
                        ->helperText('الحجم الأقصى: 100 ميجابايت')
                        ->downloadable()
                        ->previewable(false),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'e-book' => 'كتاب إلكتروني',
                        'audio' => 'ملف صوتي',
                        'video' => 'ملف فيديو',
                        'software' => 'برنامج',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'e-book',
                        'info' => 'audio',
                        'success' => 'video',
                        'warning' => 'software',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'draft' => 'مسودة',
                        'published' => 'منشور',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'published',
                        'danger'  => 'draft',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'published' => 'منشور',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'e-book' => 'كتاب إلكتروني',
                        'audio' => 'ملف صوتي',
                        'video' => 'ملف فيديو',
                        'software' => 'برنامج',
                    ])
                    ->multiple(),

                Tables\Filters\TrashedFilter::make()
                    ->label('المحذوفات'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('تم الحذف')
                            ->body('تم حذف المنتج بنجاح.')
                    )
                    ->errorNotification(
                        Notification::make()
                            ->danger()
                            ->title('خطأ')
                            ->body('حدث خطأ أثناء حذف المنتج.')
                    ),
                    
                Tables\Actions\RestoreAction::make()
                    ->label('استعادة')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('تمت الاستعادة')
                            ->body('تمت استعادة المنتج بنجاح.')
                    ),
                    
                Tables\Actions\ForceDeleteAction::make()
                    ->label('حذف نهائي')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('تم الحذف النهائي')
                            ->body('تم حذف المنتج نهائياً.')
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
                                ->body('تم حذف المنتجات المحددة بنجاح.')
                        ),
                        
                    Tables\Actions\RestoreBulkAction::make()
                        ->label('استعادة المحدد'),
                        
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label('حذف نهائي للمحدد'),

                    Tables\Actions\BulkAction::make('publish')
                        ->label('نشر المحدد')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            try {
                                $records->each->update(['status' => 'published']);
                                Notification::make()
                                    ->success()
                                    ->title('تم النشر')
                                    ->body('تم نشر المنتجات المحددة بنجاح.')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('خطأ')
                                    ->body('حدث خطأ أثناء نشر المنتجات.')
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('لا توجد منتجات رقمية')
            ->emptyStateDescription('ابدأ بإنشاء منتج رقمي جديد')
            ->emptyStateIcon('heroicon-o-archive-box');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDigitalProducts::route('/'),
            'create' => Pages\CreateDigitalProduct::route('/create'),
            'edit'   => Pages\EditDigitalProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'published')->count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description'];
    }
}