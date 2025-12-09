<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommonQuestionsResource\Pages;
use App\Models\CommonQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommonQuestionsResource extends Resource
{
    protected static ?string $model = CommonQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'إدارة صفحات الموقع';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات السؤال')
                    ->schema([
                        Forms\Components\TextInput::make('question')
                            ->label('السؤال')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('أدخل السؤال الشائع')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('answer')
                            ->label('الإجابة')
                            ->required()
                            ->rows(6)
                            ->placeholder('أدخل الإجابة على السؤال')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'draft' => 'مسودة',
                                'published' => 'منشور',
                            ])
                            ->default('draft')
                            ->required()
                            ->native(false)
                            ->helperText('المسودات لن تظهر للزوار'),
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

                Tables\Columns\TextColumn::make('question')
                    ->label('السؤال')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->wrap()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('answer')
                    ->label('الإجابة')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'published',
                    ])
                    ->icons([
                        'heroicon-o-pencil' => 'draft',
                        'heroicon-o-check-circle' => 'published',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'مسودة',
                        'published' => 'منشور',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'published' => 'منشور',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
                Tables\Actions\Action::make('publish')
                    ->label('نشر')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (CommonQuestion $record) => $record->update(['status' => 'published']))
                    ->visible(fn (CommonQuestion $record) => $record->status === 'draft'),
                Tables\Actions\Action::make('unpublish')
                    ->label('إلغاء النشر')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (CommonQuestion $record) => $record->update(['status' => 'draft']))
                    ->visible(fn (CommonQuestion $record) => $record->status === 'published'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                    Tables\Actions\BulkAction::make('publish')
                        ->label('نشر المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'published'])),
                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('إلغاء نشر المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'draft'])),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommonQuestions::route('/'),
            'create' => Pages\CreateCommonQuestions::route('/create'),
            'edit' => Pages\EditCommonQuestions::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'سؤال شائع';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الأسئلة الشائعة';
    }

    public static function getNavigationLabel(): string
    {
        return 'الأسئلة الشائعة';
    }
}