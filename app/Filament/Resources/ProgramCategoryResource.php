<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramCategoryResource\Pages;
use App\Filament\Resources\ProgramCategoryResource\RelationManagers;
use App\Models\ProgramCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;

class ProgramCategoryResource extends Resource
{
    protected static ?string $model = ProgramCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'إدارة المنتجات';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('معلومات التصنيف')
                ->schema([
                    TextInput::make('name')
                        ->label('اسم التصنيف')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('اسم فريد لتصنيف البرنامج'),

                    Textarea::make('description')
                        ->label('الوصف')
                        ->rows(4)
                        ->maxLength(1000)
                        ->helperText('وصف اختياري للتصنيف'),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('الرقم')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('اسم التصنيف')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(80)
                    ->placeholder('—')
                    ->wrap(),

                TextColumn::make('programs_count')
                    ->counts('programs')
                    ->label('عدد البرامج')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_programs')
                    ->label('يحتوي على برامج')
                    ->query(fn (Builder $query): Builder => $query->has('programs')),
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
                ])
            ])
            ->defaultSort('name', 'asc');
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
            'index' => Pages\ListProgramCategories::route('/'),
            'create' => Pages\CreateProgramCategory::route('/create'),
            'edit' => Pages\EditProgramCategory::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'تصنيف برنامج';
    }

    public static function getPluralModelLabel(): string
    {
        return 'تصنيفات البرامج';
    }
}