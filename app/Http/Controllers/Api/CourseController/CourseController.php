<?php

namespace App\Http\Controllers\Api\CourseController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Actions\Courses\{
    IndexCourseAction,
    ShowCourseAction,
    SearchCourseAction,
    FilterCourseAction
};
use App\Actions\Lessons\{
    IndexLessonAction,
    ShowLessonAction
};

class CourseController extends Controller
{
    public function index(Request $request, IndexCourseAction $action)
    {
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $courses = $action->execute($limit, $page);
        
        return response()->json([
            'success' => true,
            'data' => $courses->items(),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
                'last_page' => $courses->lastPage(),
                'from' => $courses->firstItem(),
                'to' => $courses->lastItem(),
            ]
        ]);
    }

    public function show(Request $request, int $id, ShowCourseAction $action)
    {
        $course = $action->execute($id);
        
        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $course
        ]);
    }

    public function search(Request $request, SearchCourseAction $action)
    {
        $title = $request->input('title', '');
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        
        $courses = $action->execute($title, $limit, $page);
        
        return response()->json([
            'success' => true,
            'data' => $courses->items(),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
                'last_page' => $courses->lastPage(),
                'from' => $courses->firstItem(),
                'to' => $courses->lastItem(),
            ]
        ]);
    }

    public function filter(Request $request, FilterCourseAction $action)
    {
        $filters = $request->only(['level', 'mode', 'has_seats']);
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        
        $courses = $action->execute($filters, $limit, $page);
        
        return response()->json([
            'success' => true,
            'data' => $courses->items(),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
                'last_page' => $courses->lastPage(),
                'from' => $courses->firstItem(),
                'to' => $courses->lastItem(),
            ]
        ]);
    }

    public function lessons(Request $request, int $courseId, IndexLessonAction $action)
    {
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $result = $action->execute($courseId, $limit, $page);
        
        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $result->items(),
            'pagination' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage(),
                'from' => $result->firstItem(),
                'to' => $result->lastItem(),
            ]
        ]);
    }

    public function showLesson(Request $request, int $courseId, int $lessonId, ShowLessonAction $action)
    {
        $lesson = $action->execute($courseId, $lessonId);
        
        if (!$lesson) {
            return response()->json([
                'success' => false,
                'message' => 'Lesson not found or does not belong to this course'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $lesson
        ]);
    }
}