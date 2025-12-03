
<div class="space-y-6">
    {{-- Student Information --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-lg font-semibold mb-3">Student Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Name</p>
                <p class="font-medium">{{ $answer->learner->name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                <p class="font-medium">{{ $answer->learner->email }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Submitted At</p>
                <p class="font-medium">{{ $answer->submitted_at->format('M d, Y h:i A') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Time Taken</p>
                <p class="font-medium">
                    {{ $answer->created_at->diffInMinutes($answer->submitted_at) }} minutes
                </p>
            </div>
        </div>
    </div>

    {{-- Answer Text --}}
    @if($answer->answer_text)
    <div>
        <h3 class="text-lg font-semibold mb-3">Written Answer</h3>
        <div class="bg-white dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="prose dark:prose-invert max-w-none">
                {!! nl2br(e($answer->answer_text)) !!}
            </div>
        </div>
    </div>
    @endif

    {{-- Attached Files --}}
    @if($answer->files->count() > 0)
    <div>
        <h3 class="text-lg font-semibold mb-3">Attached Files ({{ $answer->files->count() }})</h3>
        <div class="space-y-2">
            @foreach($answer->files as $file)
            <div class="flex items-center justify-between bg-white dark:bg-gray-900 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <div>
                        <p class="font-medium">{{ $file->original_name }}</p>
                        <p class="text-sm text-gray-500">
                            Uploaded {{ $file->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('filament.admin.resources.case-studies.download-file', ['record' => $answer->case_study_id, 'file' => $file->id]) }}" 
                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                   target="_blank">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- No Content Warning --}}
    @if(!$answer->answer_text && $answer->files->count() === 0)
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
        <p class="text-yellow-800 dark:text-yellow-200">
            This submission appears to be empty. No text or files were provided.
        </p>
    </div>
    @endif
</div>