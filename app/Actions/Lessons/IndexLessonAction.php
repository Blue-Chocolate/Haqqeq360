<?php

namespace App\Actions\Lessons;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexLessonAction
{
    public function execute(int $courseId, int $limit = 10, int $page = 1)
    {
        // Verify course exists
        $course = Course::find($courseId);
        
        if (!$course) {
            return null;
        }
        
        // Set max limit to prevent abuse
        $limit = min($limit, 100);
        
        // Get all lessons for this course through units
        $lessons = Lesson::whereHas('unit', function ($query) use ($courseId) {
            $query->where('course_id', $courseId);
        })
        ->with(['unit', 'assignments', 'test'])
        ->where('published', true)
        ->orderBy('order')
        ->paginate($limit, ['*'], 'page', $page);
        
        return $lessons;
    }
}
