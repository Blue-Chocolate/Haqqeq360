<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogCategoryResource\Pages;
use App\Filament\Resources\BlogCategoryResource\RelationManagers;
use App\Models\BlogCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BlogCategoryResource extends Resource
{
    protected static ?string $model = BlogCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'فئات المدونات';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('الاسم')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->label('الوصف')
                ->maxLength(500),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->label('المعرف')->sortable(),
            Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('description')->label('الوصف')->limit(50),
            Tables\Columns\TextColumn::make('created_at')->label('تم إنشاؤه في')->dateTime()->sortable(),
        ])
        ->filters([])
        ->actions([
            Tables\Actions\EditAction::make()->label('تحرير'),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make()->label('حذف'),
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
            'index' => Pages\ListBlogCategories::route('/'),
            'create' => Pages\CreateBlogCategory::route('/create'),
            'edit' => Pages\EditBlogCategory::route('/{record}/edit'),
        ];
    }
}