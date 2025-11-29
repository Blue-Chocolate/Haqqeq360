<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BootCampResource\Pages;
use App\Models\Bootcamp;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BootCampResource extends Resource
{
    protected static ?string $model = Bootcamp::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Training';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('cover_image')
                            ->image()
                            ->imageEditor()
                            ->directory('bootcamps/covers')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Bootcamp Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Instructor')
                            ->relationship('instructor', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('password')
                                    ->password()
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\TextInput::make('duration_weeks')
                            ->label('Duration (Weeks)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(52)
                            ->suffix('weeks'),

                        Forms\Components\Select::make('level')
                            ->options([
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                            ])
                            ->required()
                            ->default('beginner')
                            ->native(false),

                        Forms\Components\DatePicker::make('start_date')
                            ->native(false)
                            ->displayFormat('M d, Y')
                            ->minDate(now()),

                        Forms\Components\Select::make('mode')
                            ->options([
                                'online' => 'Online',
                                'hybrid' => 'Hybrid',
                                'offline' => 'Offline',
                            ])
                            ->required()
                            ->default('online')
                            ->native(false),

                        Forms\Components\TextInput::make('seats')
                            ->label('Total Seats')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->required()
                            ->helperText('Total number of seats available for this bootcamp')
                            ->suffix('seats'),

                        Forms\Components\Toggle::make('certificate')
                            ->label('Offers Certificate')
                            ->default(false)
                            ->inline(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('Cover')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.png')),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(30),

                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('level')
                    ->colors([
                        'success' => 'beginner',
                        'warning' => 'intermediate',
                        'danger' => 'advanced',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('mode')
                    ->colors([
                        'primary' => 'online',
                        'info' => 'hybrid',
                        'secondary' => 'offline',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_weeks')
                    ->label('Duration')
                    ->suffix(' weeks')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('seats')
                    ->label('Total Seats')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('enrollments_count')
                    ->counts('enrollments')
                    ->label('Enrolled')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('available_seats')
                    ->label('Available')
                    ->state(function (Bootcamp $record): int {
                        return max(0, $record->seats - $record->enrollments_count);
                    })
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->withCount('enrollments')
                            ->orderByRaw("(seats - enrollments_count) {$direction}");
                    }),

                Tables\Columns\IconColumn::make('certificate')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'beginner' => 'Beginner',
                        'intermediate' => 'Intermediate',
                        'advanced' => 'Advanced',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('mode')
                    ->options([
                        'online' => 'Online',
                        'hybrid' => 'Hybrid',
                        'offline' => 'Offline',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('certificate')
                    ->label('Offers Certificate')
                    ->placeholder('All bootcamps')
                    ->trueLabel('With certificate')
                    ->falseLabel('Without certificate'),

                Tables\Filters\Filter::make('start_date')
                    ->form([
                        Forms\Components\DatePicker::make('start_from')
                            ->label('Start Date From'),
                        Forms\Components\DatePicker::make('start_until')
                            ->label('Start Date Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['start_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('instructor')
                    ->relationship('instructor', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('seats_available')
                    ->label('Has Available Seats')
                    ->query(fn (Builder $query): Builder => 
                        $query->withCount('enrollments')
                              ->whereRaw('seats > enrollments_count')
                    ),

                Tables\Filters\Filter::make('fully_booked')
                    ->label('Fully Booked')
                    ->query(fn (Builder $query): Builder => 
                        $query->withCount('enrollments')
                              ->whereRaw('seats <= enrollments_count')
                    ),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Bootcamp Information')
                    ->schema([
                        Infolists\Components\ImageEntry::make('cover_image')
                            ->label('Cover Image')
                            ->columnSpanFull()
                            ->height(200),

                        Infolists\Components\TextEntry::make('title')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('description')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('instructor.name')
                            ->label('Instructor'),

                        Infolists\Components\TextEntry::make('level')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'beginner' => 'success',
                                'intermediate' => 'warning',
                                'advanced' => 'danger',
                            }),

                        Infolists\Components\TextEntry::make('mode')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'online' => 'primary',
                                'hybrid' => 'info',
                                'offline' => 'secondary',
                            }),

                        Infolists\Components\TextEntry::make('duration_weeks')
                            ->label('Duration')
                            ->suffix(' weeks'),

                        Infolists\Components\TextEntry::make('seats')
                            ->label('Total Seats'),

                        Infolists\Components\TextEntry::make('enrollments_count')
                            ->label('Enrolled Students')
                            ->state(fn (Bootcamp $record): int => $record->enrollments()->count()),

                        Infolists\Components\TextEntry::make('available_seats')
                            ->label('Available Seats')
                            ->state(function (Bootcamp $record): int {
                                $enrolled = $record->enrollments()->count();
                                return max(0, $record->seats - $enrolled);
                            })
                            ->badge()
                            ->color(function (Bootcamp $record): string {
                                $enrolled = $record->enrollments()->count();
                                $available = max(0, $record->seats - $enrolled);
                                return match (true) {
                                    $available === 0 => 'danger',
                                    $available <= 5 => 'warning',
                                    default => 'success',
                                };
                            }),

                        Infolists\Components\TextEntry::make('start_date')
                            ->date('F d, Y'),

                        Infolists\Components\IconEntry::make('certificate')
                            ->label('Certificate Offered')
                            ->boolean(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('deleted_at')
                            ->dateTime()
                            ->visible(fn ($record) => $record->trashed()),
                    ])
                    ->columns(3)
                    ->collapsed(),
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
            'index' => Pages\ListBootCamps::route('/'),
            'create' => Pages\CreateBootCamp::route('/create'),
            'view' => Pages\ViewBootCamp::route('/{record}'),
            'edit' => Pages\EditBootCamp::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->withCount('enrollments');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description', 'instructor.name'];
    }
}