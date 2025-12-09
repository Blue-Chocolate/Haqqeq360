<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HeaderResource\Pages;
use App\Filament\Resources\HeaderResource\RelationManagers;
use App\Models\Header;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class HeaderResource extends Resource
{
    protected static ?string $model = Header::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'إدارة صفحات الموقع';
    
    protected static ?string $navigationLabel = 'رؤوس الصفحات';
    
    protected static ?string $modelLabel = 'رأس صفحة';
    
    protected static ?string $pluralModelLabel = 'رؤوس الصفحات';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('معلومات رأس الصفحة')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('الاسم')
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

                    Forms\Components\Textarea::make('short_description')
                        ->label('الوصف المختصر')
                        ->maxLength(500)
                        ->rows(3)
                        ->helperText('حد أقصى 500 حرف'),
                ])
                ->columns(2),

            Forms\Components\Section::make('المحتوى التفصيلي')
                ->schema([
                    Forms\Components\RichEditor::make('description')
                        ->label('الوصف التفصيلي')
                        ->toolbarButtons([
                            'blockquote',
                            'bold',
                            'bulletList',
                            'h2',
                            'h3',
                            'italic',
                            'link',
                            'orderedList',
                            'redo',
                            'strike',
                            'underline',
                            'undo',
                        ])
                        ->columnSpanFull(),
                ])
                ->collapsible(),

            Forms\Components\Section::make('الصورة')
                ->schema([
                    Forms\Components\FileUpload::make('image')
                        ->label('صورة الرأس')
                        ->directory('headers')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            '16:9',
                            '4:3',
                            '1:1',
                        ])
                        ->maxSize(5120) // 5MB
                        ->helperText('الحجم الأقصى: 5 ميجابايت')
                        ->imagePreviewHeight('250')
                        ->downloadable(),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('image')
                ->label('الصورة')
                ->square()
                ->size(60),

            Tables\Columns\TextColumn::make('name')
                ->label('الاسم')
                ->searchable()
                ->sortable()
                ->limit(40),

            Tables\Columns\TextColumn::make('slug')
                ->label('الرابط الثابت')
                ->searchable()
                ->sortable()
                ->limit(40)
                ->copyable()
                ->copyMessage('تم نسخ الرابط')
                ->copyMessageDuration(1500),

            Tables\Columns\TextColumn::make('short_description')
                ->label('الوصف المختصر')
                ->limit(50)
                ->toggleable(),

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
            Tables\Filters\Filter::make('has_image')
                ->label('يحتوي على صورة')
                ->query(fn (Builder $query): Builder => $query->whereNotNull('image')),

            Tables\Filters\Filter::make('no_image')
                ->label('بدون صورة')
                ->query(fn (Builder $query): Builder => $query->whereNull('image')),

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
                }),
        ])
        ->actions([
            Tables\Actions\ViewAction::make()
                ->label('عرض'),

            Tables\Actions\EditAction::make()
                ->label('تعديل'),

            Tables\Actions\DeleteAction::make()
                ->label('حذف')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('تم الحذف')
                        ->body('تم حذف رأس الصفحة بنجاح.')
                )
                ->errorNotification(
                    Notification::make()
                        ->danger()
                        ->title('خطأ')
                        ->body('حدث خطأ أثناء حذف رأس الصفحة.')
                ),

            Tables\Actions\Action::make('duplicate')
                ->label('نسخ')
                ->icon('heroicon-o-document-duplicate')
                ->color('info')
                ->requiresConfirmation()
                ->action(function (Header $record) {
                    try {
                        $newHeader = $record->replicate();
                        $newHeader->name = $record->name . ' (نسخة)';
                        $newHeader->slug = $record->slug . '-copy-' . time();
                        $newHeader->save();

                        Notification::make()
                            ->success()
                            ->title('تم النسخ')
                            ->body('تم نسخ رأس الصفحة بنجاح.')
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('خطأ')
                            ->body('حدث خطأ أثناء نسخ رأس الصفحة.')
                            ->send();
                    }
                }),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('حذف المحدد')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('تم الحذف')
                            ->body('تم حذف رؤوس الصفحات المحددة بنجاح.')
                    ),
            ]),
        ])
        ->defaultSort('created_at', 'desc')
        ->emptyStateHeading('لا توجد رؤوس صفحات')
        ->emptyStateDescription('ابدأ بإنشاء رأس صفحة جديد')
        ->emptyStateIcon('heroicon-o-rectangle-stack');
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
            'index' => Pages\ListHeaders::route('/'),
            'create' => Pages\CreateHeader::route('/create'),
            'edit' => Pages\EditHeader::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'short_description'];
    }
}