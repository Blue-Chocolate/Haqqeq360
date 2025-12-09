<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CaseStudyResource\Pages;
use App\Filament\Resources\CaseStudyResource\RelationManagers;
use App\Models\CaseStudy;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class CaseStudyResource extends Resource
{
    protected static ?string $model = CaseStudy::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'إدارة التعليم';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات دراسة الحالة')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('العنوان')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->placeholder('أدخل عنوان دراسة الحالة'),

                        Forms\Components\Select::make('instructor_id')
                            ->label('المدرب')
                            ->options(User::where('role', 'instructor')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('المدرب المسؤول عن هذه الدراسة'),

                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'open' => 'مفتوحة',
                                'closed' => 'مغلقة',
                            ])
                            ->default('open')
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('duration')
                            ->label('المدة (بالدقائق)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('دقيقة')
                            ->helperText('المدة المتوقعة لإكمال دراسة الحالة'),

                        Forms\Components\RichEditor::make('content')
                            ->label('محتوى دراسة الحالة')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'link',
                                'redo',
                                'undo',
                            ]),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('الرقم')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('instructor.name')
                    ->label('المدرب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'success' => 'open',
                        'danger' => 'closed',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'open',
                        'heroicon-o-x-circle' => 'closed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'مفتوحة',
                        'closed' => 'مغلقة',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('duration')
                    ->label('المدة')
                    ->suffix(' دقيقة')
                    ->sortable(),

                Tables\Columns\TextColumn::make('answers_count')
                    ->counts('answers')
                    ->label('الإجابات')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

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
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'open' => 'مفتوحة',
                        'closed' => 'مغلقة',
                    ]),

                Tables\Filters\SelectFilter::make('instructor')
                    ->label('المدرب')
                    ->relationship('instructor', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('تفاصيل دراسة الحالة')
                    ->schema([
                        Components\TextEntry::make('title')
                            ->label('العنوان')
                            ->size(Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),

                        Components\TextEntry::make('instructor.name')
                            ->label('المدرب'),

                        Components\TextEntry::make('status')
                            ->label('الحالة')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'open' => 'مفتوحة',
                                'closed' => 'مغلقة',
                                default => $state,
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'open' => 'success',
                                'closed' => 'danger',
                            }),

                        Components\TextEntry::make('duration')
                            ->label('المدة')
                            ->suffix(' دقيقة'),

                        Components\TextEntry::make('answers_count')
                            ->label('إجمالي الإجابات')
                            ->badge()
                            ->color('primary'),

                        Components\TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Components\Section::make('المحتوى')
                    ->schema([
                        Components\TextEntry::make('content')
                            ->label('محتوى دراسة الحالة')
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AnswersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCaseStudies::route('/'),
            'create' => Pages\CreateCaseStudy::route('/create'),
            'view' => Pages\ViewCaseStudy::route('/{record}'),
            'edit' => Pages\EditCaseStudy::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'دراسة حالة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'دراسات الحالة';
    }
}