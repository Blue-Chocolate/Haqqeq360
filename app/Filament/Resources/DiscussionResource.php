<?php

// app/Filament/Resources/DiscussionResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscussionResource\Pages;

use App\Actions\Discussion\CreateDiscussionAction;
use App\Actions\Discussion\DeleteDiscussionAction;
use App\Actions\Discussion\UpdateDiscussionAction;
use App\Models\Discussion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class DiscussionResource extends Resource
{
    protected static ?string $model = Discussion::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'المناقشات';

    protected static ?string $navigationGroup = 'المحتوى';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('العنوان')
                            ->required()
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => 'العنوان مطلوب.',
                                'max' => 'العنوان يجب ألا يتجاوز 255 حرفًا.',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('content')
                            ->label('المحتوى')
                            ->required()
                            ->validationMessages([
                                'required' => 'المحتوى مطلوب.',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('image')
                            ->label('الصورة')
                            ->image()
                            ->disk('public')
                            ->directory('discussions')
                            ->imageEditor()
                            ->validationMessages([
                                'image' => 'يجب أن يكون الملف صورة.',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('تاريخ النشر')
                            ->default(now())
                            ->required()
                            ->validationMessages([
                                'required' => 'تاريخ النشر مطلوب.',
                            ]),

                        Forms\Components\Toggle::make('is_published')
                            ->label('منشور')
                            ->default(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('الصورة')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.png')),

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('الكاتب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('منشور')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('تاريخ النشر')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('likes_count')
                    ->label('الإعجابات')
                    ->counts('likes')
                    ->sortable(),

                Tables\Columns\TextColumn::make('comments_count')
                    ->label('التعليقات')
                    ->counts('comments')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('منشور')
                    ->placeholder('كل المناقشات')
                    ->trueLabel('منشور فقط')
                    ->falseLabel('غير منشور فقط'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تحرير'),
                Tables\Actions\DeleteAction::make()->label('حذف')
                    ->using(function (Discussion $record) {
                        try {
                            app(DeleteDiscussionAction::class)->execute($record);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('خطأ في الحذف')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                            throw $e;
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف'),
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
            'index' => Pages\ListDiscussions::route('/'),
            'create' => Pages\CreateDiscussion::route('/create'),
            'edit' => Pages\EditDiscussion::route('/{record}/edit'),
        ];
    }
}