<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestProgramResource\Pages;
use App\Models\RequestProgram;
use App\Mail\RequestProgramStatusUpdated;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;

class RequestProgramResource extends Resource
{
    protected static ?string $model = RequestProgram::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Program Requests';

    protected static ?string $navigationGroup = 'إدارة الطلبات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\TextInput::make('requested_by')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('program_name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\DatePicker::make('requested_date')
                            ->required()
                            ->default(now()),
                    ])->columns(2),

                Forms\Components\Section::make('Request Details')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                        
                        Forms\Components\TagsInput::make('requested_features')
                            ->placeholder('Add features')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->default('pending')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $record) {
                                if ($record && in_array($state, ['approved', 'rejected'])) {
                                    // Send email when status changes
                                    Mail::to($record->user->email)->send(
                                        new RequestProgramStatusUpdated($record)
                                    );
                                }
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('requested_by')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('program_name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('requested_date')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                
                Tables\Filters\Filter::make('requested_date')
                    ->form([
                        Forms\Components\DatePicker::make('requested_from'),
                        Forms\Components\DatePicker::make('requested_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['requested_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('requested_date', '>=', $date),
                            )
                            ->when(
                                $data['requested_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('requested_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (RequestProgram $record) => $record->status !== 'approved')
                        ->action(function (RequestProgram $record) {
                            $record->update(['status' => 'approved']);
                            
                            Mail::to($record->user->email)->send(
                                new RequestProgramStatusUpdated($record)
                            );
                            
                            Notification::make()
                                ->title('Request Approved')
                                ->success()
                                ->send();
                        }),
                    
                    Tables\Actions\Action::make('reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (RequestProgram $record) => $record->status !== 'rejected')
                        ->action(function (RequestProgram $record) {
                            $record->update(['status' => 'rejected']);
                            
                            Mail::to($record->user->email)->send(
                                new RequestProgramStatusUpdated($record)
                            );
                            
                            Notification::make()
                                ->title('Request Rejected')
                                ->success()
                                ->send();
                        }),
                    
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => 'approved']);
                                
                                Mail::to($record->user->email)->send(
                                    new RequestProgramStatusUpdated($record)
                                );
                            }
                            
                            Notification::make()
                                ->title('Requests Approved')
                                ->success()
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => 'rejected']);
                                
                                Mail::to($record->user->email)->send(
                                    new RequestProgramStatusUpdated($record)
                                );
                            }
                            
                            Notification::make()
                                ->title('Requests Rejected')
                                ->success()
                                ->send();
                        }),
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
            'index' => Pages\ListRequestPrograms::route('/'),
            'create' => Pages\CreateRequestProgram::route('/create'),
            'view' => Pages\ViewRequestProgram::route('/{record}'),
            'edit' => Pages\EditRequestProgram::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}