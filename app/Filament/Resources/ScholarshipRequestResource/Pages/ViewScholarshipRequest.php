<?php

namespace App\Filament\Resources\ScholarshipRequestResource\Pages;

use App\Filament\Resources\ScholarshipRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewScholarshipRequest extends ViewRecord
{
    protected static string $resource = ScholarshipRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات المتقدم')
                    ->schema([
                        Infolists\Components\TextEntry::make('applicant_name')
                            ->label('اسم المتقدم'),
                        
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('المستخدم المسجل')
                            ->default('غير مسجل'),
                        
                        Infolists\Components\TextEntry::make('number_of_participants')
                            ->label('عدد المشاركين')
                            ->badge()
                            ->color('info'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('تفاصيل البرنامج')
                    ->schema([
                        Infolists\Components\TextEntry::make('program_type')
                            ->label('نوع البرنامج')
                            ->formatStateUsing(fn (string $state): string => match($state) {
                                'course' => 'دورة تدريبية',
                                'bootcamp' => 'معسكر تدريبي',
                                'workshop' => 'ورشة عمل',
                                'training_program' => 'برنامج تدريبي',
                                'certification' => 'شهادة احترافية',
                                default => $state,
                            })
                            ->badge(),
                        
                        Infolists\Components\TextEntry::make('skills_and_needs')
                            ->label('المهارات والاحتياجات')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('الحالة والمرفقات')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('حالة الطلب')
                            ->formatStateUsing(fn (string $state): string => match($state) {
                                'pending' => 'قيد المراجعة',
                                'under_review' => 'تحت الدراسة',
                                'approved' => 'مقبول',
                                'rejected' => 'مرفوض',
                                'waitlisted' => 'قائمة الانتظار',
                                default => $state,
                            })
                            ->badge()
                            ->color(fn (string $state): string => match($state) {
                                'pending' => 'warning',
                                'under_review' => 'info',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'waitlisted' => 'gray',
                                default => 'gray',
                            }),
                        
                        Infolists\Components\TextEntry::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->visible(fn ($record) => $record->status === 'rejected')
                            ->columnSpanFull(),
                        
                        Infolists\Components\TextEntry::make('attachments')
                            ->label('المرفقات')
                            ->formatStateUsing(fn ($state) => $state ? 'يوجد مرفقات' : 'لا يوجد')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'gray'),
                        
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('تاريخ التقديم')
                            ->dateTime('Y-m-d H:i'),
                        
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime('Y-m-d H:i'),
                    ])
                    ->columns(3),
            ]);
    }
}