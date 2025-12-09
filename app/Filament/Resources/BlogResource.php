<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogResource\Pages;
use App\Models\Blog;
use App\Models\BlogCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlogResource extends Resource
{
    protected static ?string $model = Blog::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'المدونات';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('العنوان')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('content')
                ->label('المحتوى')
                ->required(),
            Forms\Components\FileUpload::make('image_path')
                ->label('الصورة')
                ->image()
                ->directory('blogs/images')
                ->nullable(),
            Forms\Components\Select::make('blog_category_id')
                ->label('الفئة')
                ->options(BlogCategory::pluck('name', 'id'))
                ->required()
                ->searchable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->label('المعرف')->sortable(),
            Tables\Columns\TextColumn::make('title')->label('العنوان')->searchable()->sortable(),
            Tables\Columns\ImageColumn::make('image_path')->label('الصورة'),
            Tables\Columns\TextColumn::make('blogCategory.name')->label('الفئة')->sortable(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogs::route('/'),
            'create' => Pages\CreateBlog::route('/create'),
            'edit' => Pages\EditBlog::route('/{record}/edit'),
        ];
    }
}