<?php

namespace App\Filament\Resources\CoursePublishRequestResource\Pages;

use App\Filament\Resources\CoursePublishRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewCoursePublishRequest extends ViewRecord
{
    protected static string $resource = CoursePublishRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Actions\Action::make('approve')
                ->label('قبول وأنشر')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->approve();
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('تم قبول الطلب ونشر الدورة')
                        ->send();
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'pending'),
            
            Actions\Action::make('reject')
                ->label('رفض')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->reject('تم رفض الطلب');
                    \Filament\Notifications\Notification::make()
                        ->warning()
                        ->title('تم رفض الطلب')
                        ->send();
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'pending'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الطلب')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('رقم الطلب'),
                        
                        Infolists\Components\TextEntry::make('course.name')
                            ->label('اسم الدورة')
                            ->weight('bold'),
                        
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('مقدم الطلب'),
                        
                        Infolists\Components\TextEntry::make('category')
                            ->label('التصنيف')
                            ->badge()
                            ->color('info'),
                        
                        Infolists\Components\TextEntry::make('status')
                            ->label('الحالة')
                            ->formatStateUsing(fn (string $state): string => match($state) {
                                'pending' => 'قيد المراجعة',
                                'approved' => 'مقبول',
                                'rejected' => 'مرفوض',
                                default => $state,
                            })
                            ->badge()
                            ->color(fn (string $state): string => match($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('المحتوى والمرفقات')
                    ->schema([
                        Infolists\Components\TextEntry::make('uploaded_content')
                            ->label('المحتوى المرفوع')
                            ->html()
                            ->visible(fn ($record) => $record->uploaded_content)
                            ->columnSpanFull(),
                        
                        Infolists\Components\TextEntry::make('attachment_path')
                            ->label('المرفقات')
                            ->formatStateUsing(fn ($state) => $state ? 'تحميل المرفق' : 'لا يوجد')
                            ->url(fn ($state) => $state ? asset('storage/' . $state) : null)
                            ->openUrlInNewTab()
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'gray'),
                    ]),

                Infolists\Components\Section::make('ملاحظات الإدارة')
                    ->schema([
                        Infolists\Components\TextEntry::make('admin_notes')
                            ->label('الملاحظات')
                            ->html()
                            ->placeholder('لا توجد ملاحظات')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->admin_notes),

                Infolists\Components\Section::make('التواريخ')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('تاريخ التقديم')
                            ->dateTime('Y-m-d H:i'),
                        
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime('Y-m-d H:i'),
                    ])
                    ->columns(2),
            ]);
    }
}