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

    protected static ?string $navigationGroup = 'Learning Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Case Study Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('instructor_id')
                            ->label('Instructor')
                            ->options(User::where('role', 'instructor')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'open' => 'Open',
                                'closed' => 'Closed',
                            ])
                            ->default('open')
                            ->required(),

                        Forms\Components\TextInput::make('duration')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('minutes'),

                        Forms\Components\RichEditor::make('content')
                            ->label('Case Study Content')
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
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('instructor.name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'open',
                        'danger' => 'closed',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'open',
                        'heroicon-o-x-circle' => 'closed',
                    ]),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->suffix(' min')
                    ->sortable(),

                Tables\Columns\TextColumn::make('answers_count')
                    ->counts('answers')
                    ->label('Submissions')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
                    ]),

                Tables\Filters\SelectFilter::make('instructor')
                    ->relationship('instructor', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Case Study Details')
                    ->schema([
                        Components\TextEntry::make('title')
                            ->size(Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),

                        Components\TextEntry::make('instructor.name')
                            ->label('Instructor'),

                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'open' => 'success',
                                'closed' => 'danger',
                            }),

                        Components\TextEntry::make('duration')
                            ->suffix(' minutes'),

                        Components\TextEntry::make('answers_count')
                            ->label('Total Submissions')
                            ->badge()
                            ->color('primary'),

                        Components\TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Components\Section::make('Content')
                    ->schema([
                        Components\TextEntry::make('content')
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
}