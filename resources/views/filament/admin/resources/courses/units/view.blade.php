<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Unit Information Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 text-lg font-bold">
                            {{ $unit->order }}
                        </span>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $unit->title }}
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $unit->lessons()->count() }} Lessons in this unit
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Unit Statistics --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-info-50 dark:bg-info-900/20 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-info-600 dark:text-info-400 font-medium">Total Lessons</p>
                            <p class="text-2xl font-bold text-info-700 dark:text-info-300">
                                {{ $unit->lessons()->count() }}
                            </p>
                        </div>
                        <x-heroicon-o-academic-cap class="w-10 h-10 text-info-500" />
                    </div>
                </div>

                <div class="bg-success-50 dark:bg-success-900/20 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-success-600 dark:text-success-400 font-medium">Published</p>
                            <p class="text-2xl font-bold text-success-700 dark:text-success-300">
                                {{ $unit->lessons()->where('published', true)->count() }}
                            </p>
                        </div>
                        <x-heroicon-o-check-circle class="w-10 h-10 text-success-500" />
                    </div>
                </div>

                <div class="bg-warning-50 dark:bg-warning-900/20 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-warning-600 dark:text-warning-400 font-medium">Assignments</p>
                            <p class="text-2xl font-bold text-warning-700 dark:text-warning-300">
                                {{ \App\Models\Assignment::whereHas('lesson', function($query) use ($unit) {
                                    $query->where('unit_id', $unit->id);
                                })->count() }}
                            </p>
                        </div>
                        <x-heroicon-o-clipboard-document-list class="w-10 h-10 text-warning-500" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Unit Navigation (Other Units) --}}
        @if($record->units()->count() > 1)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Other Units in this Course
            </h3>
            <div class="flex gap-2 overflow-x-auto pb-2">
                @foreach($record->units()->orderBy('order')->get() as $otherUnit)
<a href="{{ \App\Filament\Resources\CourseResource::getUrl('units.view', ['record' => $record->id, 'unit' => $otherUnit->id]) }}"
                       class="flex-shrink-0 px-4 py-2 rounded-lg border-2 transition-all duration-200 {{ $otherUnit->id === $unit->id ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 font-semibold' : 'border-gray-200 dark:border-gray-700 hover:border-primary-300 text-gray-700 dark:text-gray-300' }}">
                        <span class="text-sm">Unit {{ $otherUnit->order }}</span>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Lessons List --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Lessons
                </h3>
            </div>

            @if($unit->lessons()->count() > 0)
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($unit->lessons()->orderBy('order')->get() as $lesson)
                        <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-4 flex-1">
                                    {{-- Lesson Number --}}
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold">
                                            {{ $lesson->order }}
                                        </span>
                                    </div>

                                    {{-- Lesson Content --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-2">
                                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ $lesson->title }}
                                            </h4>
                                            @if($lesson->published)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-success-100 dark:bg-success-900 text-success-700 dark:text-success-300">
                                                    <x-heroicon-s-check-circle class="w-3 h-3 mr-1" />
                                                    Published
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                    <x-heroicon-s-pencil class="w-3 h-3 mr-1" />
                                                    Draft
                                                </span>
                                            @endif
                                        </div>

                                        @if($lesson->content)
                                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                                                {!! \Illuminate\Support\Str::limit(strip_tags($lesson->content), 150) !!}
                                            </div>
                                        @endif

                                        {{-- Lesson Resources --}}
                                        <div class="flex flex-wrap gap-3 text-xs">
                                            @if($lesson->video_url)
                                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded">
                                                    <x-heroicon-s-play-circle class="w-4 h-4" />
                                                    Video
                                                </span>
                                            @endif

                                            @if($lesson->attachment_path)
                                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded">
                                                    <x-heroicon-s-paper-clip class="w-4 h-4" />
                                                    Attachment
                                                </span>
                                            @endif

                                            @if($lesson->resource_link)
                                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 rounded">
                                                    <x-heroicon-s-link class="w-4 h-4" />
                                                    Resource
                                                </span>
                                            @endif

                                            @php
                                                $assignmentCount = $lesson->assignments()->count();
                                            @endphp
                                            @if($assignmentCount > 0)
                                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 rounded">
                                                    <x-heroicon-s-clipboard-document-list class="w-4 h-4" />
                                                    {{ $assignmentCount }} {{ $assignmentCount === 1 ? 'Assignment' : 'Assignments' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="flex-shrink-0 flex gap-2">
                                    <a href="{{ CourseResource::getUrl('edit', ['record' => $record->id]) }}" 
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                                        <x-heroicon-s-pencil class="w-4 h-4" />
                                    </a>
                                </div>
                            </div>

                            {{-- Assignments for this lesson --}}
                            @if($lesson->assignments()->count() > 0)
                                <div class="mt-4 pl-14">
                                    <div class="bg-orange-50 dark:bg-orange-900/10 rounded-lg p-4 border border-orange-200 dark:border-orange-800">
                                        <h5 class="text-sm font-semibold text-orange-900 dark:text-orange-200 mb-2 flex items-center gap-2">
                                            <x-heroicon-s-clipboard-document-list class="w-4 h-4" />
                                            Assignments
                                        </h5>
                                        <div class="space-y-2">
                                            @foreach($lesson->assignments as $assignment)
                                                <div class="flex items-center justify-between text-sm">
                                                    <span class="text-gray-700 dark:text-gray-300">{{ $assignment->title }}</span>
                                                    <div class="flex items-center gap-3">
                                                        @if($assignment->due_date)
                                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                                Due: {{ $assignment->due_date->format('M d, Y') }}
                                                            </span>
                                                        @endif
                                                        @if($assignment->max_score)
                                                            <span class="text-xs px-2 py-0.5 bg-orange-200 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded">
                                                                {{ $assignment->max_score }} pts
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-12 text-center">
                    <x-heroicon-o-academic-cap class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <p class="text-gray-500 dark:text-gray-400 text-lg">No lessons in this unit yet</p>
                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Add lessons to get started</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>