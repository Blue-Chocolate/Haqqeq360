<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Course Infolist --}}
        {{ $this->infolist }}

        {{-- Units Navigation Bar --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    Course Units
                </h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $record->units()->count() }} Units Total
                </span>
            </div>

            @if($record->units()->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($record->units()->orderBy('order')->get() as $unit)
                        <a href="{{ \App\Filament\Resources\CourseResource::getUrl('view-unit', ['record' => $record->id, 'unit' => $unit->id]) }}"
                           class="block p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border-2 border-transparent hover:border-primary-500 hover:shadow-md transition-all duration-200">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 text-sm font-semibold">
                                            {{ $unit->order }}
                                        </span>
                                        <h3 class="font-semibold text-gray-900 dark:text-white text-sm line-clamp-2">
                                            {{ $unit->title }}
                                        </h3>
                                    </div>
                                    
                                    <div class="flex items-center gap-3 text-xs text-gray-600 dark:text-gray-400 mt-2">
                                        <span class="flex items-center gap-1">
                                            <x-heroicon-o-academic-cap class="w-4 h-4" />
                                            {{ $unit->lessons()->count() }} Lessons
                                        </span>
                                    </div>
                                </div>
                                
                                <x-heroicon-o-chevron-right class="w-5 h-5 text-gray-400" />
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <x-heroicon-o-book-open class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                    <p class="text-gray-500 dark:text-gray-400">No units added yet</p>
                </div>
            @endif
        </div>

        {{-- Quick Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Recent Enrollments --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Recent Enrollments
                </h3>
                @if($record->enrollments()->latest()->limit(5)->count() > 0)
                    <div class="space-y-3">
                        @foreach($record->enrollments()->latest()->limit(5)->get() as $enrollment)
                            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700 last:border-0">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                        <x-heroicon-o-user class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $enrollment->user->name ?? 'Unknown User' }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $enrollment->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full bg-success-100 dark:bg-success-900 text-success-700 dark:text-success-300">
                                    {{ ucfirst($enrollment->status ?? 'enrolled') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No enrollments yet</p>
                @endif
            </div>

            {{-- Course Progress --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Course Progress
                </h3>
                <div class="space-y-4">
                    @php
                        $totalUnits = $record->units()->count();
                        $totalLessons = $record->units()->withCount('lessons')->get()->sum('lessons_count');
                        $totalAssignments = \App\Models\Assignment::whereHas('lesson.unit', function($query) use ($record) {
                            $query->where('course_id', $record->id);
                        })->count();
                        $enrolledCount = $record->enrollments()->count();
                        $seatsProgress = $record->seats > 0 ? ($enrolledCount / $record->seats) * 100 : 0;
                    @endphp

                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">Units Created</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $totalUnits }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-info-500 h-2 rounded-full" style="width: {{ min(100, $totalUnits * 10) }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">Lessons Created</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $totalLessons }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-success-500 h-2 rounded-full" style="width: {{ min(100, $totalLessons * 5) }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">Seat Capacity</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $enrolledCount }}/{{ $record->seats }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-warning-500 h-2 rounded-full" style="width: {{ $seatsProgress }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>