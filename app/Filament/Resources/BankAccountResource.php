<?php

// ============================================
// BankAccountResource.php
// ============================================

namespace App\Filament\Resources;

use App\Filament\Resources\BankAccountResource\Pages;
use App\Models\BankAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'المالية';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات البنك')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->label('اسم البنك')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: البنك الأهلي المصري'),
                        
                        Forms\Components\TextInput::make('beneficiary_name')
                            ->label('اسم المستفيد')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('الاسم كما يظهر في الحساب البنكي'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('تفاصيل الحساب')
                    ->schema([
                        Forms\Components\TextInput::make('account_number')
                            ->label('رقم الحساب')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('0123456789'),
                        
                        Forms\Components\TextInput::make('iban')
                            ->label('رقم الآيبان (IBAN)')
                            ->maxLength(255)
                            ->placeholder('مثال: EG380002000156789012345678901'),
                        
                        Forms\Components\TextInput::make('swift_code')
                            ->label('كود السويفت (SWIFT)')
                            ->maxLength(255)
                            ->placeholder('مثال: NBEGEGCXXXX'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('معلومات إضافية')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->maxLength(65535)
                            ->rows(4)
                            ->columnSpanFull()
                            ->placeholder('أي ملاحظات أو تعليمات إضافية'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->helperText('الحسابات النشطة فقط ستكون مرئية للمستخدمين')
                            ->inline(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bank_name')
                    ->label('اسم البنك')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('beneficiary_name')
                    ->label('المستفيد')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('account_number')
                    ->label('رقم الحساب')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('تم نسخ رقم الحساب')
                    ->copyMessageDuration(1500),
                
                Tables\Columns\TextColumn::make('iban')
                    ->label('الآيبان')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('تم نسخ رقم الآيبان')
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('swift_code')
                    ->label('كود السويفت')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('تم نسخ كود السويفت')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->sortable(),
                
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
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('حالة النشاط')
                    ->placeholder('جميع الحسابات')
                    ->trueLabel('النشطة فقط')
                    ->falseLabel('غير النشطة فقط'),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'حساب بنكي';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الحسابات البنكية';
    }
}