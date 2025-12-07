<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_read', false)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('is_read', false)->count() > 10 ? 'danger' : 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Recipient Selection')
                    ->description('Choose whether to send to a single user or multiple users')
                    ->schema([
                        Forms\Components\Radio::make('send_to')
                            ->label('Send To')
                            ->options([
                                'single' => 'Single User',
                                'multiple' => 'Multiple Users',
                                'all' => 'All Users',
                            ])
                            ->default('single')
                            ->inline()
                            ->live()
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('user_id')
                            ->label('Select User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(fn (Forms\Get $get) => $get('send_to') === 'single')
                            ->visible(fn (Forms\Get $get) => $get('send_to') === 'single')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('user_ids')
                            ->label('Select Users')
                            ->options(User::all()->pluck('name', 'id'))
                            ->searchable()
                            ->multiple()
                            ->preload()
                            ->required(fn (Forms\Get $get) => $get('send_to') === 'multiple')
                            ->visible(fn (Forms\Get $get) => $get('send_to') === 'multiple')
                            ->columnSpanFull()
                            ->helperText('Hold Ctrl/Cmd to select multiple users'),

                        Forms\Components\Placeholder::make('all_users_info')
                            ->label('All Users Selected')
                            ->content(fn () => 'This notification will be sent to all ' . User::count() . ' users in the system.')
                            ->visible(fn (Forms\Get $get) => $get('send_to') === 'all')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Forms\Components\Section::make('Notification Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('message')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type')
                            ->options([
                                'system' => 'System',
                                'course' => 'Course',
                                'assignment' => 'Assignment',
                            ])
                            ->default('system')
                            ->required()
                            ->native(false),

                        Forms\Components\Toggle::make('is_read')
                            ->label('Mark as Read')
                            ->default(false)
                            ->helperText('Set to true if you want the notification to be marked as read immediately'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('message')
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 40 ? $state : null;
                    })
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'system',
                        'success' => 'course',
                        'warning' => 'assignment',
                    ])
                    ->icons([
                        'heroicon-o-cog-6-tooth' => 'system',
                        'heroicon-o-academic-cap' => 'course',
                        'heroicon-o-document-text' => 'assignment',
                    ]),

                Tables\Columns\IconColumn::make('is_read')
                    ->label('Read')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

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
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'system' => 'System',
                        'course' => 'Course',
                        'assignment' => 'Assignment',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_read')
                    ->label('Read Status')
                    ->placeholder('All notifications')
                    ->trueLabel('Read only')
                    ->falseLabel('Unread only'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created from'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created until'),
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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('markAsRead')
                        ->label('Mark as Read')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->hidden(fn (Notification $record): bool => $record->is_read)
                        ->action(fn (Notification $record) => $record->update(['is_read' => true]))
                        ->requiresConfirmation()
                        ->successNotificationTitle('Notification marked as read'),
                    Tables\Actions\Action::make('markAsUnread')
                        ->label('Mark as Unread')
                        ->icon('heroicon-o-x-mark')
                        ->color('warning')
                        ->hidden(fn (Notification $record): bool => !$record->is_read)
                        ->action(fn (Notification $record) => $record->update(['is_read' => false]))
                        ->requiresConfirmation()
                        ->successNotificationTitle('Notification marked as unread'),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('markAsRead')
                        ->label('Mark as Read')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_read' => true]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Notifications marked as read'),
                    Tables\Actions\BulkAction::make('markAsUnread')
                        ->label('Mark as Unread')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_read' => false]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Notifications marked as unread'),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Notification Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('User'),
                        Infolists\Components\TextEntry::make('title')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('message')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'system' => 'primary',
                                'course' => 'success',
                                'assignment' => 'warning',
                            }),
                        Infolists\Components\IconEntry::make('is_read')
                            ->label('Read Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
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
            'index' => Pages\ListNotifications::route('/'),
            'create' => Pages\CreateNotification::route('/create'),
            'view' => Pages\ViewNotification::route('/{record}'),
            'edit' => Pages\EditNotification::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user');
    }
}