@php
    use App\Filament\Resources\CourseResource;
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Lesson Header Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-start justify-between mb-6">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 text-lg font-bold">
                            {{ $lesson->order }}
                        </span>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $lesson->title }}
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Unit {{ $lesson->unit->order }}: {{ $lesson->unit->title }}
                            </p>
                        </div>
                    </div>

                    {{-- Status Badge --}}
                    <div class="flex items-center gap-2 mt-4">
                        @if($lesson->published)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-success-100 dark:bg-success-900 text-success-700 dark:text-success-300">
                                <x-heroicon-s-check-circle class="w-4 h-4 mr-1" />
                                Published
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                <x-heroicon-s-pencil class="w-4 h-4 mr-1" />
                                Draft
                            </span>
                        @endif

                        @if($lesson->created_at)
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                Created {{ $lesson->created_at->diffForHumans() }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Lesson Content --}}
        @if($lesson->content)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <x-heroicon-o-document-text class="w-5 h-5" />
                Lesson Content
            </h3>
            <div class="prose dark:prose-invert max-w-none">
                {!! $lesson->content !!}
            </div>
        </div>
        @endif

        {{-- Resources Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Video Resource --}}
            @if($lesson->video_url)
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg shadow p-6 border border-purple-200 dark:border-purple-800">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-600 dark:bg-purple-700 flex items-center justify-center">
                        <x-heroicon-s-play-circle class="w-6 h-6 text-white" />
                    </div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Video Content</h4>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Watch the lesson video</p>
                <a href="{{ $lesson->video_url }}" 
                   target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition-colors">
                    <x-heroicon-s-play class="w-4 h-4" />
                    Watch Video
                </a>
            </div>
            @endif

            {{-- Attachment Resource --}}
            @if($lesson->attachment_path)
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg shadow p-6 border border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-600 dark:bg-blue-700 flex items-center justify-center">
                        <x-heroicon-s-paper-clip class="w-6 h-6 text-white" />
                    </div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Attachment</h4>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Download lesson materials</p>
                <a href="{{ Storage::url($lesson->attachment_path) }}" 
                   download
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                    <x-heroicon-s-arrow-down-tray class="w-4 h-4" />
                    Download
                </a>
            </div>
            @endif

            {{-- External Resource --}}
            @if($lesson->resource_link)
            <div class="bg-gradient-to-br from-cyan-50 to-cyan-100 dark:from-cyan-900/20 dark:to-cyan-800/20 rounded-lg shadow p-6 border border-cyan-200 dark:border-cyan-800">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-cyan-600 dark:bg-cyan-700 flex items-center justify-center">
                        <x-heroicon-s-link class="w-6 h-6 text-white" />
                    </div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">External Resource</h4>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Additional learning materials</p>
                <a href="{{ $lesson->resource_link }}" 
                   target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-sm font-medium transition-colors">
                    <x-heroicon-s-arrow-top-right-on-square class="w-4 h-4" />
                    Open Link
                </a>
            </div>
            @endif
        </div>

        {{-- Assignments Section --}}
        @if($lesson->assignments()->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-heroicon-o-clipboard-document-list class="w-5 h-5" />
                    Assignments ({{ $lesson->assignments()->count() }})
                </h3>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($lesson->assignments as $assignment)
                    <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $assignment->title }}
                                    </h4>
                                    @if($assignment->max_score)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200">
                                            {{ $assignment->max_score }} points
                                        </span>
                                    @endif
                                </div>

                                @if($assignment->description)
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        {!! \Illuminate\Support\Str::limit(strip_tags($assignment->description), 200) !!}
                                    </div>
                                @endif

                                <div class="flex flex-wrap gap-4 text-sm">
                                    @if($assignment->due_date)
                                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                            <x-heroicon-o-calendar class="w-4 h-4" />
                                            <span>Due: {{ $assignment->due_date->format('M d, Y h:i A') }}</span>
                                        </div>
                                    @endif

                                    @php
                                        $submissionCount = $assignment->submissions()->count();
                                    @endphp
                                    @if($submissionCount > 0)
                                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                            <x-heroicon-o-document-check class="w-4 h-4" />
                                            <span>{{ $submissionCount }} {{ $submissionCount === 1 ? 'Submission' : 'Submissions' }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="flex-shrink-0">
                                <a href="{{ CourseResource::getUrl('edit', ['record' => $record->id]) }}" 
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                                    <x-heroicon-s-pencil class="w-4 h-4" />
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <x-heroicon-o-clipboard-document-list class="w-16 h-16 text-gray-400 mx-auto mb-4" />
            <p class="text-gray-500 dark:text-gray-400 text-lg">No assignments for this lesson</p>
            <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Assignments can be added in the course editor</p>
        </div>
        @endif

        {{-- Lesson Navigation --}}
        @php
            $allLessons = $lesson->unit->lessons()->orderBy('order')->get();
            $currentIndex = $allLessons->search(function($item) use ($lesson) {
                return $item->id === $lesson->id;
            });
            $previousLesson = $currentIndex > 0 ? $allLessons[$currentIndex - 1] : null;
            $nextLesson = $currentIndex < $allLessons->count() - 1 ? $allLessons[$currentIndex + 1] : null;
        @endphp

        @if($previousLesson || $nextLesson)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between gap-4">
                @if($previousLesson)
                    <a href="{{ CourseResource::getUrl('lessons.view', ['record' => $record->id, 'lesson' => $previousLesson->id]) }}"
                       class="flex items-center gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors group flex-1">
                        <x-heroicon-o-arrow-left class="w-5 h-5 text-gray-400 group-hover:text-primary-600" />
                        <div class="text-left">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Previous Lesson</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $previousLesson->title }}</p>
                        </div>
                    </a>
                @else
                    <div class="flex-1"></div>
                @endif

                @if($nextLesson)
                    <a href="{{ CourseResource::getUrl('lessons.view', ['record' => $record->id, 'lesson' => $nextLesson->id]) }}"
                       class="flex items-center gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors group flex-1 justify-end">
                        <div class="text-right">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Next Lesson</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $nextLesson->title }}</p>
                        </div>
                        <x-heroicon-o-arrow-right class="w-5 h-5 text-gray-400 group-hover:text-primary-600" />
                    </a>
                @else
                    <div class="flex-1"></div>
                @endif
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>