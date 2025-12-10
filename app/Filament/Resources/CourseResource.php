<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Filament\Resources\CourseResource\RelationManagers;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'إدارة المنتجات';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Course Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $context, $state, callable $set) => 
                                $context === 'create' ? $set('slug', Str::slug($state)) : null
                            ),
                        
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('URL-friendly version of the title'),
                        
                        Forms\Components\Select::make('instructor_id')
                            ->label('Instructor')
                            ->relationship('instructor', 'name', function ($query) {
                                return $query->whereIn('role', ['instructor', 'admin']);
                            })
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
                                    ->unique('users', 'email')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('password')
                                    ->password()
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('role')
                                    ->options([
                                        'instructor' => 'Instructor',
                                        'admin' => 'Admin',
                                    ])
                                    ->default('instructor')
                                    ->required(),
                            ]),
                        
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        
                        Forms\Components\FileUpload::make('cover_image')
                            ->image()
                            ->imageEditor()
                            ->directory('course-covers')
                            ->visibility('public')
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0)
                            ->required(),
                        
                        Forms\Components\TextInput::make('discounted_price')
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->step(0.01)
                            ->helperText('Leave empty if no discount')
                            ->lte('price'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Course Details')
                    ->schema([
                        Forms\Components\TextInput::make('duration_weeks')
                            ->numeric()
                            ->suffix('weeks')
                            ->minValue(1)
                            ->maxValue(52)
                            ->helperText('Course duration in weeks'),
                        
                        Forms\Components\TextInput::make('seats')
                            ->label('Available Seats')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->helperText('Total number of seats available for this course')
                            ->required(),
                        
                        Forms\Components\Select::make('level')
                            ->options([
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                            ])
                            ->required()
                            ->default('beginner')
                            ->native(false),
                        
                        Forms\Components\Select::make('mode')
                            ->options([
                                'online' => 'Online',
                                'hybrid' => 'Hybrid',
                                'offline' => 'Offline',
                            ])
                            ->required()
                            ->default('online')
                            ->native(false),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                            ])
                            ->required()
                            ->default('draft')
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Course Content - Units & Lessons')
                    ->schema([
                        Forms\Components\Repeater::make('units')
                            ->relationship('units')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->helperText('Order of the unit in the course'),

                                Forms\Components\Repeater::make('lessons')
                                    ->relationship('lessons')
                                    ->schema([
                                        Forms\Components\TextInput::make('title')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Forms\Components\RichEditor::make('content')
                                            ->columnSpanFull()
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'link',
                                                'bulletList',
                                                'orderedList',
                                                'codeBlock',
                                            ]),

                                        Forms\Components\TextInput::make('order')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->helperText('Order of the lesson in the unit'),

                                        Forms\Components\TextInput::make('video_url')
                                            ->url()
                                            ->maxLength(255)
                                            ->placeholder('https://youtube.com/watch?v=...')
                                            ->helperText('YouTube, Vimeo, or other video URL'),

                                        Forms\Components\TextInput::make('resource_link')
                                            ->url()
                                            ->maxLength(255)
                                            ->placeholder('https://example.com/resource')
                                            ->helperText('External resource or reference link'),

                                        Forms\Components\FileUpload::make('attachment_path')
                                            ->label('Attachment')
                                            ->directory('lessons/attachments')
                                            ->maxSize(10240)
                                            ->acceptedFileTypes(['application/pdf', 'application/zip', 'application/x-rar'])
                                            ->helperText('PDF, ZIP, or RAR files (max 10MB)'),

                                        Forms\Components\Toggle::make('published')
                                            ->default(true)
                                            ->helperText('Is this lesson visible to students?'),
                                    ])
                                    ->orderColumn('order')
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Lesson')
                                    ->collapsed()
                                    ->collapsible()
                                    ->columnSpanFull()
                                    ->defaultItems(0)
                                    ->addActionLabel('Add Lesson')
                                    ->reorderable()
                                    ->cloneable(),
                            ])
                            ->orderColumn('order')
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Unit')
                            ->collapsed()
                            ->collapsible()
                            ->columnSpanFull()
                            ->defaultItems(0)
                            ->addActionLabel('Add Unit')
                            ->reorderable()
                            ->cloneable(),
                    ])
                    ->columnSpanFull()
                    ->collapsed()
                    ->collapsible(),

                Forms\Components\Section::make('Assignments')
                    ->schema([
                        Forms\Components\Repeater::make('assignments')
                            ->relationship('assignments')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Select::make('unit_id')
                                            ->label('Unit')
                                            ->options(function (Get $get, $livewire) {
                                                $courseId = $livewire->getRecord()?->id;
                                                if (!$courseId) {
                                                    return [];
                                                }
                                                return \App\Models\Unit::where('course_id', $courseId)
                                                    ->orderBy('order')
                                                    ->pluck('title', 'id')
                                                    ->toArray();
                                            })
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn (callable $set) => $set('lesson_id', null))
                                            ->searchable()
                                            ->preload(),

                                        Forms\Components\Select::make('lesson_id')
                                            ->label('Lesson')
                                            ->options(function (Get $get) {
                                                $unitId = $get('unit_id');
                                                if (!$unitId) {
                                                    return [];
                                                }
                                                return \App\Models\Lesson::where('unit_id', $unitId)
                                                    ->orderBy('order')
                                                    ->pluck('title', 'id')
                                                    ->toArray();
                                            })
                                            ->required()
                                            ->live()
                                            ->searchable()
                                            ->preload()
                                            ->disabled(fn (Get $get) => !$get('unit_id'))
                                            ->helperText('Select a unit first'),

                                        Forms\Components\Placeholder::make('lesson_info')
                                            ->label('')
                                            ->content(function (Get $get) {
                                                $unitId = $get('unit_id');
                                                $lessonId = $get('lesson_id');
                                                
                                                if ($unitId && $lessonId) {
                                                    $unit = \App\Models\Unit::find($unitId);
                                                    $lesson = \App\Models\Lesson::find($lessonId);
                                                    return '📍 ' . $unit?->title . ' → ' . $lesson?->title;
                                                }
                                                
                                                return 'Select unit and lesson';
                                            }),
                                    ]),

                                Forms\Components\TextInput::make('title')
                                    ->label('Assignment Title')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\RichEditor::make('description')
                                    ->label('Assignment Description')
                                    ->required()
                                    ->columnSpanFull()
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'link',
                                        'bulletList',
                                        'orderedList',
                                    ]),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\DateTimePicker::make('due_date')
                                            ->label('Due Date')
                                            ->native(false)
                                            ->required(),

                                        Forms\Components\TextInput::make('max_score')
                                            ->label('Maximum Score')
                                            ->numeric()
                                            ->default(100)
                                            ->minValue(0)
                                            ->required(),
                                    ]),

                                Forms\Components\FileUpload::make('attachment_path')
                                    ->label('Assignment Attachment')
                                    ->directory('assignments/attachments')
                                    ->maxSize(5120)
                                    ->helperText('Additional files for the assignment (max 5MB)')
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('published')
                                    ->label('Published')
                                    ->default(true)
                                    ->helperText('Is this assignment active?'),
                            ])
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $livewire): array {
                                $data['course_id'] = $livewire->getRecord()?->id;
                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $livewire): array {
                                $data['course_id'] = $livewire->getRecord()?->id;
                                return $data;
                            })
                            ->itemLabel(function (array $state): ?string {
                                if (!isset($state['title'])) {
                                    return 'New Assignment';
                                }
                                
                                $unit = isset($state['unit_id']) ? \App\Models\Unit::find($state['unit_id']) : null;
                                $lesson = isset($state['lesson_id']) ? \App\Models\Lesson::find($state['lesson_id']) : null;
                                
                                $location = '';
                                if ($unit && $lesson) {
                                    $location = " (Unit {$unit->order} → Lesson {$lesson->order})";
                                }
                                
                                return $state['title'] . $location;
                            })
                            ->collapsed()
                            ->collapsible()
                            ->columnSpanFull()
                            ->defaultItems(0)
                            ->addActionLabel('Add Assignment')
                            ->cloneable()
                            ->reorderable(false)
                            ->orderColumn(false),
                    ])
                    ->columnSpanFull()
                    ->collapsed()
                    ->collapsible()
                    ->description('Add assignments to specific lessons in your course'),
                Forms\Components\Section::make('Discussions')
    ->schema([
        Forms\Components\Repeater::make('discussions')
            ->relationship('discussions')
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Select::make('unit_id')
                            ->label('Unit')
                            ->options(function (Get $get, $livewire) {
                                $courseId = $livewire->getRecord()?->id;
                                if (!$courseId) {
                                    return [];
                                }
                                return \App\Models\Unit::where('course_id', $courseId)
                                    ->orderBy('order')
                                    ->pluck('title', 'id')
                                    ->toArray();
                            })
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('lesson_id', null))
                            ->searchable()
                            ->preload()
                            ->helperText('Optional: Select a unit to link this discussion'),

                        Forms\Components\Select::make('lesson_id')
                            ->label('Lesson')
                            ->options(function (Get $get) {
                                $unitId = $get('unit_id');
                                if (!$unitId) {
                                    return [];
                                }
                                return \App\Models\Lesson::where('unit_id', $unitId)
                                    ->orderBy('order')
                                    ->pluck('title', 'id')
                                    ->toArray();
                            })
                            ->live()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Get $get) => !$get('unit_id'))
                            ->helperText('Optional: Select a lesson'),

                        Forms\Components\Placeholder::make('discussion_location')
                            ->label('')
                            ->content(function (Get $get) {
                                $unitId = $get('unit_id');
                                $lessonId = $get('lesson_id');
                                
                                if ($unitId && $lessonId) {
                                    $unit = \App\Models\Unit::find($unitId);
                                    $lesson = \App\Models\Lesson::find($lessonId);
                                    return '📍 ' . $unit?->title . ' → ' . $lesson?->title;
                                } elseif ($unitId) {
                                    $unit = \App\Models\Unit::find($unitId);
                                    return '📍 ' . $unit?->title . ' (General Unit Discussion)';
                                }
                                
                                return '📍 General Course Discussion';
                            }),
                    ]),

                Forms\Components\TextInput::make('title')
                    ->label('Discussion Title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('content')
                    ->label('Discussion Content')
                    ->required()
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'link',
                        'bulletList',
                        'orderedList',
                        'blockquote',
                    ]),

                Forms\Components\FileUpload::make('image')
                    ->label('Discussion Image')
                    ->image()
                    ->imageEditor()
                    ->directory('discussions')
                    ->maxSize(2048)
                    ->helperText('Optional image for the discussion (max 2MB)')
                    ->columnSpanFull(),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Author')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => auth()->id())
                            ->helperText('User who started this discussion'),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Published At')
                            ->native(false)
                            ->helperText('Leave empty to publish immediately'),
                    ]),

                Forms\Components\Toggle::make('is_published')
                    ->label('Published')
                    ->default(true)
                    ->helperText('Is this discussion visible to students?')
                    ->columnSpanFull(),

                Forms\Components\Placeholder::make('stats')
                    ->label('Discussion Stats')
                    ->content(function ($record) {
                        if (!$record) {
                            return 'Save the discussion to see stats';
                        }
                        
                        $commentsCount = $record->comments()->count();
                        $likesCount = $record->likes()->count();
                        
                        return "💬 {$commentsCount} Comments | 👍 {$likesCount} Likes";
                    })
                    ->columnSpanFull(),
            ])
            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $livewire): array {
                $data['course_id'] = $livewire->getRecord()?->id;
                if (empty($data['user_id'])) {
                    $data['user_id'] = auth()->id();
                }
                if (empty($data['published_at']) && $data['is_published']) {
                    $data['published_at'] = now();
                }
                return $data;
            })
            ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $livewire): array {
                $data['course_id'] = $livewire->getRecord()?->id;
                if (empty($data['published_at']) && $data['is_published']) {
                    $data['published_at'] = now();
                }
                return $data;
            })
            ->itemLabel(function (array $state): ?string {
                if (!isset($state['title'])) {
                    return 'New Discussion';
                }
                
                $status = ($state['is_published'] ?? false) ? '✅' : '📝';
                $title = $state['title'];
                
                $unit = isset($state['unit_id']) ? \App\Models\Unit::find($state['unit_id']) : null;
                $lesson = isset($state['lesson_id']) ? \App\Models\Lesson::find($state['lesson_id']) : null;
                
                $location = '';
                if ($unit && $lesson) {
                    $location = " (U{$unit->order} → L{$lesson->order})";
                } elseif ($unit) {
                    $location = " (Unit {$unit->order})";
                }
                
                return $status . ' ' . $title . $location;
            })
            ->collapsed()
            ->collapsible()
            ->columnSpanFull()
            ->defaultItems(0)
            ->addActionLabel('Add Discussion')
            ->cloneable()
            ->reorderable(false)
            ->orderColumn(false),
    ])
    ->columnSpanFull()
    ->collapsed()
    ->collapsible()
    ->description('Manage course discussions. Link them to specific lessons or keep them as general course discussions.'),
    Forms\Components\Section::make('Case Studies')
    ->schema([
        Forms\Components\Repeater::make('caseStudies')
            ->relationship('caseStudies')
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Select::make('unit_id')
                            ->label('Unit')
                            ->options(function (Get $get, $livewire) {
                                $courseId = $livewire->getRecord()?->id;
                                if (!$courseId) {
                                    return [];
                                }
                                return \App\Models\Unit::where('course_id', $courseId)
                                    ->orderBy('order')
                                    ->pluck('title', 'id')
                                    ->toArray();
                            })
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('lesson_id', null))
                            ->searchable()
                            ->preload()
                            ->helperText('Optional: Link to specific unit'),

                        Forms\Components\Select::make('lesson_id')
                            ->label('Lesson')
                            ->options(function (Get $get) {
                                $unitId = $get('unit_id');
                                if (!$unitId) {
                                    return [];
                                }
                                return \App\Models\Lesson::where('unit_id', $unitId)
                                    ->orderBy('order')
                                    ->pluck('title', 'id')
                                    ->toArray();
                            })
                            ->live()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Get $get) => !$get('unit_id'))
                            ->helperText('Optional: Link to specific lesson'),

                        Forms\Components\Placeholder::make('case_study_location')
                            ->label('')
                            ->content(function (Get $get) {
                                $unitId = $get('unit_id');
                                $lessonId = $get('lesson_id');
                                
                                if ($unitId && $lessonId) {
                                    $unit = \App\Models\Unit::find($unitId);
                                    $lesson = \App\Models\Lesson::find($lessonId);
                                    return '📍 ' . $unit?->title . ' → ' . $lesson?->title;
                                } elseif ($unitId) {
                                    $unit = \App\Models\Unit::find($unitId);
                                    return '📍 ' . $unit?->title . ' (Unit Case Study)';
                                }
                                
                                return '📍 General Course Case Study';
                            }),
                    ]),

                Forms\Components\TextInput::make('title')
                    ->label('Case Study Title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->placeholder('e.g., Real-World Marketing Campaign Analysis'),

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
                        'blockquote',
                        'codeBlock',
                    ])
                    ->helperText('Provide the complete case study scenario, background, and questions'),

                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Select::make('instructor_id')
                            ->label('Instructor')
                            ->relationship('instructor', 'name', function ($query) {
                                return $query->whereIn('role', ['instructor', 'admin']);
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => auth()->id())
                            ->helperText('Instructor responsible for this case study'),

                        Forms\Components\TextInput::make('duration')
                            ->label('Duration (Minutes)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(60)
                            ->suffix('minutes')
                            ->helperText('Expected time to complete'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'open' => 'Open',
                                'closed' => 'Closed',
                            ])
                            ->default('open')
                            ->required()
                            ->native(false)
                            ->helperText('Open: students can submit | Closed: read-only'),
                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DateTimePicker::make('available_from')
                            ->label('Available From')
                            ->native(false)
                            ->helperText('When students can start viewing this case study'),

                        Forms\Components\DateTimePicker::make('available_until')
                            ->label('Available Until')
                            ->native(false)
                            ->helperText('Deadline for submissions (leave empty for no deadline)'),
                    ]),

                Forms\Components\Textarea::make('guidelines')
                    ->label('Submission Guidelines')
                    ->rows(3)
                    ->columnSpanFull()
                    ->placeholder('Provide instructions for students on how to approach and submit their answers...')
                    ->helperText('Optional: Instructions, requirements, and evaluation criteria'),

                Forms\Components\FileUpload::make('attachment')
                    ->label('Case Study Attachment')
                    ->directory('case-studies/attachments')
                    ->maxSize(10240)
                    ->acceptedFileTypes(['application/pdf', 'application/zip', 'application/x-rar', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->helperText('Optional: Additional materials (PDF, DOCX, ZIP, RAR - max 10MB)')
                    ->columnSpanFull(),

                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('max_score')
                            ->label('Maximum Score')
                            ->numeric()
                            ->default(100)
                            ->minValue(0)
                            ->helperText('Total points for this case study'),

                        Forms\Components\TextInput::make('passing_score')
                            ->label('Passing Score')
                            ->numeric()
                            ->default(50)
                            ->minValue(0)
                            ->helperText('Minimum points to pass'),

                        Forms\Components\TextInput::make('max_attempts')
                            ->label('Maximum Attempts')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->helperText('How many times students can submit'),
                    ]),

                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Toggle::make('allow_late_submission')
                            ->label('Allow Late Submission')
                            ->default(false)
                            ->helperText('Accept submissions after deadline'),

                        Forms\Components\Toggle::make('show_model_answer')
                            ->label('Show Model Answer')
                            ->default(false)
                            ->helperText('Show example answer after deadline'),

                        Forms\Components\Toggle::make('peer_review_enabled')
                            ->label('Enable Peer Review')
                            ->default(false)
                            ->helperText('Students can review each other\'s work'),
                    ]),

                Forms\Components\RichEditor::make('model_answer')
                    ->label('Model Answer (Optional)')
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'bulletList',
                        'orderedList',
                        'link',
                    ])
                    ->helperText('Provide an example or ideal answer for reference')
                    ->visible(fn (Get $get) => $get('show_model_answer')),

                Forms\Components\Placeholder::make('stats')
                    ->label('Case Study Stats')
                    ->content(function ($record) {
                        if (!$record) {
                            return 'Save the case study to see stats';
                        }
                        
                        $answersCount = $record->answers()->count();
                        $pendingCount = $record->answers()->where('status', 'pending')->count();
                        $gradedCount = $record->answers()->where('status', 'graded')->count();
                        
                        return "📝 {$answersCount} Total Submissions | ⏳ {$pendingCount} Pending | ✅ {$gradedCount} Graded";
                    })
                    ->columnSpanFull(),
            ])
            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $livewire): array {
                $data['course_id'] = $livewire->getRecord()?->id;
                if (empty($data['instructor_id'])) {
                    $data['instructor_id'] = auth()->id();
                }
                return $data;
            })
            ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $livewire): array {
                $data['course_id'] = $livewire->getRecord()?->id;
                return $data;
            })
            ->itemLabel(function (array $state): ?string {
                if (!isset($state['title'])) {
                    return 'New Case Study';
                }
                
                $status = ($state['status'] ?? 'open') === 'open' ? '🟢' : '🔴';
                $title = $state['title'];
                
                $unit = isset($state['unit_id']) ? \App\Models\Unit::find($state['unit_id']) : null;
                $lesson = isset($state['lesson_id']) ? \App\Models\Lesson::find($state['lesson_id']) : null;
                
                $location = '';
                if ($unit && $lesson) {
                    $location = " (U{$unit->order} → L{$lesson->order})";
                } elseif ($unit) {
                    $location = " (Unit {$unit->order})";
                }
                
                return $status . ' ' . $title . $location;
            })
            ->collapsed()
            ->collapsible()
            ->columnSpanFull()
            ->defaultItems(0)
            ->addActionLabel('Add Case Study')
            ->cloneable()
            ->reorderable(false)
            ->orderColumn(false),
    ])
    ->columnSpanFull()
    ->collapsed()
    ->collapsible()
    ->description('Create real-world case studies for students to analyze and solve. Case studies can be linked to specific lessons or remain general course materials.'),
                Forms\Components\Section::make('Tests & Quizzes')
                    ->schema([
                        Forms\Components\Repeater::make('tests')
                            ->relationship('tests')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Select::make('unit_id')
                                            ->label('Unit')
                                            ->options(function (Get $get, $livewire) {
                                                $courseId = $livewire->getRecord()?->id;
                                                if (!$courseId) {
                                                    return [];
                                                }
                                                return \App\Models\Unit::where('course_id', $courseId)
                                                    ->orderBy('order')
                                                    ->pluck('title', 'id')
                                                    ->toArray();
                                            })
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn (callable $set) => $set('lesson_id', null))
                                            ->searchable()
                                            ->preload(),

                                        Forms\Components\Select::make('lesson_id')
                                            ->label('Lesson')
                                            ->options(function (Get $get) {
                                                $unitId = $get('unit_id');
                                                if (!$unitId) {
                                                    return [];
                                                }
                                                return \App\Models\Lesson::where('unit_id', $unitId)
                                                    ->orderBy('order')
                                                    ->pluck('title', 'id')
                                                    ->toArray();
                                            })
                                            ->required()
                                            ->live()
                                            ->searchable()
                                            ->preload()
                                            ->disabled(fn (Get $get) => !$get('unit_id'))
                                            ->helperText('Select a unit first'),

                                        Forms\Components\Placeholder::make('test_location')
                                            ->label('')
                                            ->content(function (Get $get) {
                                                $unitId = $get('unit_id');
                                                $lessonId = $get('lesson_id');
                                                
                                                if ($unitId && $lessonId) {
                                                    $unit = \App\Models\Unit::find($unitId);
                                                    $lesson = \App\Models\Lesson::find($lessonId);
                                                    return '📍 ' . $unit?->title . ' → ' . $lesson?->title;
                                                }
                                                
                                                return 'Select unit and lesson';
                                            }),
                                    ]),
                                    

                                Forms\Components\TextInput::make('title')
                                    ->label('Test Title')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('description')
                                    ->label('Test Description')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('duration_minutes')
                                            ->label('Duration (Minutes)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->suffix('minutes')
                                            ->helperText('Leave empty for no time limit'),

                                        Forms\Components\TextInput::make('passing_score')
                                            ->label('Passing Score (%)')
                                            ->required()
                                            ->numeric()
                                            ->default(50)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('%'),

                                        Forms\Components\TextInput::make('max_attempts')
                                            ->label('Maximum Attempts')
                                            ->required()
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1),
                                    ]),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Toggle::make('shuffle_questions')
                                            ->label('Shuffle Questions')
                                            ->helperText('Randomize question order'),

                                        Forms\Components\Toggle::make('show_correct_answers')
                                            ->label('Show Correct Answers')
                                            ->default(true)
                                            ->helperText('After submission'),

                                        Forms\Components\Toggle::make('show_results_immediately')
                                            ->label('Show Results Immediately')
                                            ->default(true),
                                    ]),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\DateTimePicker::make('available_from')
                                            ->label('Available From')
                                            ->native(false)
                                            ->helperText('Leave empty for immediate availability'),

                                        Forms\Components\DateTimePicker::make('available_until')
                                            ->label('Available Until')
                                            ->native(false)
                                            ->helperText('Leave empty for no end date'),
                                    ]),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Inactive tests are hidden from students')
                                    ->columnSpanFull(),

                                Forms\Components\Placeholder::make('questions_note')
                                    ->content('⚠️ Questions can be added after creating the test by clicking "Edit Test" or using the Questions tab.')
                                    ->columnSpanFull(),
                            ])
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $livewire): array {
                                $courseId = $livewire->getRecord()?->id;
                                $data['testable_type'] = 'App\\Models\\Course';
                                $data['testable_id'] = $courseId;
                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $livewire): array {
                                $courseId = $livewire->getRecord()?->id;
                                $data['testable_type'] = 'App\\Models\\Course';
                                $data['testable_id'] = $courseId;
                                return $data;
                            })
                            ->itemLabel(function (array $state): ?string {
                                if (!isset($state['title'])) {
                                    return 'New Test';
                                }
                                
                                $unit = isset($state['unit_id']) ? \App\Models\Unit::find($state['unit_id']) : null;
                                $lesson = isset($state['lesson_id']) ? \App\Models\Lesson::find($state['lesson_id']) : null;
                                
                                $location = '';
                                if ($unit && $lesson) {
                                    $location = " (Unit {$unit->order} → Lesson {$lesson->order})";
                                }
                                
                                return $state['title'] . $location;
                            })
                            ->collapsed()
                            ->collapsible()
                            ->columnSpanFull()
                            ->defaultItems(0)
                            ->addActionLabel('Add Test')
                            ->cloneable()
                            ->reorderable(false)
                            ->orderColumn(false),
                    ])
                    ->columnSpanFull()
                    ->collapsed()
                    ->collapsible()
                    ->description('Add tests and quizzes to specific lessons in your course'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('enrollments'))
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->title) . '&color=7F9CF5&background=EBF4FF&size=128'),
                
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('instructor.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('units_count')
                    ->counts('units')
                    ->label('Units')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('discounted_price')
                    ->money('USD')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('discounted_price')
                    ->money('USD')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                
                Tables\Columns\BadgeColumn::make('level')
                    ->colors([
                        'success' => 'beginner',
                        'warning' => 'intermediate',
                        'danger' => 'advanced',
                    ])
                    ->icons([
                        'heroicon-o-star' => 'beginner',
                        'heroicon-o-fire' => 'intermediate',
                        'heroicon-o-rocket-launch' => 'advanced',
                    ])
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('mode')
                    ->colors([
                        'info' => 'online',
                        'warning' => 'hybrid',
                        'secondary' => 'offline',
                    ])
                    ->icons([
                        'heroicon-o-globe-alt' => 'online',
                        'heroicon-o-arrow-path' => 'hybrid',
                        'heroicon-o-building-office-2' => 'offline',
                    ])
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('duration_weeks')
                    ->suffix(' weeks')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('seats')
                    ->label('Total Seats')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('enrollments_count')
                    ->label('Enrolled')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('available_seats')
                    ->label('Available')
                    ->state(function (Course $record): int {
                        return max(0, $record->seats - $record->enrollments_count);
                    })
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'published',
                    ])
                    ->icons([
                        'heroicon-o-pencil' => 'draft',
                        'heroicon-o-check-circle' => 'published',
                    ])
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
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('instructor')
                    ->relationship('instructor', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\Filter::make('seats_available')
                    ->label('Has Available Seats')
                    ->query(fn (Builder $query): Builder => 
                        $query->withCount('enrollments')
                              ->whereRaw('seats > (SELECT COUNT(*) FROM enrollments WHERE enrollable_type = ? AND enrollable_id = courses.id)', [Course::class])
                    ),
                
                Tables\Filters\Filter::make('fully_booked')
                    ->label('Fully Booked')
                    ->query(fn (Builder $query): Builder => 
                        $query->withCount('enrollments')
                              ->whereRaw('seats <= (SELECT COUNT(*) FROM enrollments WHERE enrollable_type = ? AND enrollable_id = courses.id)', [Course::class])
                    ),
                
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
    Tables\Actions\ViewAction::make()
        ->url(fn (Course $record): string => static::getUrl('edit', ['record' => $record])),                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                
                Tables\Actions\Action::make('publish')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Course $record) => $record->update(['status' => 'published']))
                    ->visible(fn (Course $record) => $record->status === 'draft'),
                
                Tables\Actions\Action::make('unpublish')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Course $record) => $record->update(['status' => 'draft']))
                    ->visible(fn (Course $record) => $record->status === 'published'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'published']))
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('Unpublish Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'draft']))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EnrollmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'view' => Pages\ViewCourse::route('/{record}'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
            'view-unit' => Pages\ViewUnit::route('/{record}/units/{unit}'),
            'lessons.view' => Pages\ViewLesson::route('/{record}/lessons/{lesson}'),
            'knowledge-base' => Pages\ManageKnowledgeBase::route('/{record}/knowledge-base'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->withTrashed();
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'published')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}