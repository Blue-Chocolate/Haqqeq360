<?php

namespace App\Actions\Lessons;

use App\Models\Lesson;

class ShowLessonAction
{
    public function execute(int $courseId, int $lessonId)
    {
        // Find lesson that belongs to a unit in the specified course
        $lesson = Lesson::whereHas('unit', function ($query) use ($courseId) {
            $query->where('course_id', $courseId);
        })
        ->with(['unit', 'assignments', 'test'])
        ->where('id', $lessonId)
        ->where('published', true) // Only published lessons
        ->first();
        
        return $lesson;
    }
}