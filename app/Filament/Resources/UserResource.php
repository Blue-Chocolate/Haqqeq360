<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('second_name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        
                        Forms\Components\FileUpload::make('avatar')
                            ->image()
                            ->directory('avatars')
                            ->imageEditor()
                            ->circleCropper(),
                    ])->columns(2),

                Forms\Components\Section::make('Account Information')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->maxLength(255)
                            ->revealable(),
                        
                        Forms\Components\Select::make('role')
                            ->options([
                                'learner' => 'Learner',
                                'instructor' => 'Instructor',
                                'admin' => 'Admin',
                            ])
                            ->default('learner')
                            ->required()
                            ->native(false),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive users cannot login to the system'),
                        
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At'),
                    ])->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('bio')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->first_name . ' ' . $record->second_name)),
                
                Tables\Columns\TextColumn::make('first_name')
                    ->label('First Name')
                    ->searchable(['first_name', 'second_name', 'name'])
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('second_name')
                    ->label('Last Name')
                    ->searchable(['first_name', 'second_name', 'name'])
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'primary' => 'learner',
                        'success' => 'instructor',
                        'danger' => 'admin',
                    ])
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),
                
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
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'learner' => 'Learner',
                        'instructor' => 'Instructor',
                        'admin' => 'Admin',
                    ])
                    ->multiple()
                    ->label('Role'),
                
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Status'),
                
                Tables\Filters\SelectFilter::make('email_verified')
                    ->options([
                        'verified' => 'Verified',
                        'unverified' => 'Unverified',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'verified') {
                            return $query->whereNotNull('email_verified_at');
                        } elseif ($data['value'] === 'unverified') {
                            return $query->whereNull('email_verified_at');
                        }
                        return $query;
                    })
                    ->label('Email Verification'),
                
                Tables\Filters\TrashedFilter::make(),
            ])
            ->filtersFormColumns(3)
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (User $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (User $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record) => $record->is_active ? 'Deactivate User' : 'Activate User')
                    ->modalDescription(fn (User $record) => $record->is_active 
                        ? 'Are you sure you want to deactivate this user? They will not be able to login.'
                        : 'Are you sure you want to activate this user? They will be able to login.')
                    ->action(function (User $record) {
                        $record->is_active = !$record->is_active;
                        $record->save();
                        
                        Notification::make()
                            ->title($record->is_active ? 'User Activated' : 'User Deactivated')
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => auth()->user()->role === 'admin'),
                
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                            
                            Notification::make()
                                ->title('Users Activated')
                                ->success()
                                ->send();
                        })
                        ->visible(fn () => auth()->user()->role === 'admin'),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                            
                            Notification::make()
                                ->title('Users Deactivated')
                                ->success()
                                ->send();
                        })
                        ->visible(fn () => auth()->user()->role === 'admin'),
                    
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add relation managers here if needed
            // e.g., RelationManagers\EnrollmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->first_name . ' ' . $record->second_name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'second_name', 'email', 'phone'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Email' => $record->email,
            'Phone' => $record->phone ?? 'N/A',
            'Role' => ucfirst($record->role),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['avatar']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}