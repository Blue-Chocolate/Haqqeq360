<?php

namespace App\Http\Controllers\Api\KnowledgeBaseController;

use App\Http\Controllers\Controller;
use App\Http\Resources\KnowledgeBaseArticleResource;
use App\Http\Resources\ArticleTagResource;
use App\Models\KnowledgeBaseArticle;
use App\Models\ArticleTag;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class KnowledgeBaseController extends Controller
{
    /**
     * Display a listing of articles
     * GET /api/v1/knowledge-base
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Debug: Check total articles in database
            $totalArticles = KnowledgeBaseArticle::count();
            $publishedArticles = KnowledgeBaseArticle::where('status', 'published')->count();
            
            Log::info('Knowledge Base Index Called', [
                'total_articles' => $totalArticles,
                'published_articles' => $publishedArticles,
                'request_params' => $request->all()
            ]);
            
            // Simple query first - no filters
            $query = KnowledgeBaseArticle::query();
            
            // Check if published scope is the issue
            // Temporarily remove published scope for testing
            // $query->published();
            
            // Use simple where instead
            $query->where('status', 'published')
                  ->whereNotNull('published_at')
                  ->where('published_at', '<=', now());
            
            $query->with(['course:id,title,slug', 'tags', 'author:id,name']);
            
            // Search functionality
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%")
                      ->orWhere('excerpt', 'like', "%{$search}%");
                });
            }
            
            // Filter by course
            if ($courseId = $request->input('course_id')) {
                $query->where('course_id', $courseId);
            }
            
            // Filter by tag
            if ($tagId = $request->input('tag_id')) {
                $query->whereHas('tags', function ($q) use ($tagId) {
                    $q->where('article_tags.id', $tagId);
                });
            }
            
            // Sorting
            $sortBy = $request->input('sort_by', 'published_at');
            $sortOrder = $request->input('sort_order', 'desc');
            
            $allowedSorts = ['published_at', 'views_count', 'title', 'created_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            }
            
            // Get query SQL for debugging
            $sql = $query->toSql();
            $bindings = $query->getBindings();
            
            Log::info('Query Details', [
                'sql' => $sql,
                'bindings' => $bindings
            ]);
            
            $perPage = min($request->input('per_page', 12), 50);
            $articles = $query->paginate($perPage);
            
            Log::info('Query Results', [
                'count' => $articles->count(),
                'total' => $articles->total()
            ]);
            
            // Get enrolled courses (if authenticated)
            $enrolledCourses = [];
            if ($user = $request->user()) {
                $enrolledCourses = $user->enrollments()
                    ->with('course:id,title,slug')
                    ->get()
                    ->pluck('course')
                    ->filter()
                    ->map(function ($course) {
                        return [
                            'id' => $course->id,
                            'title' => $course->title,
                            'slug' => $course->slug,
                        ];
                    });
            }
            
            return response()->json([
                'success' => true,
                'message' => 'تم جلب المقالات بنجاح',
                'debug' => [
                    'total_in_db' => $totalArticles,
                    'published_in_db' => $publishedArticles,
                    'query_count' => $articles->count(),
                    'current_time' => now()->toDateTimeString(),
                ],
                'data' => [
                    'articles' => KnowledgeBaseArticleResource::collection($articles->items()),
                    'enrolled_courses' => $enrolledCourses,
                ],
                'pagination' => [
                    'current_page' => $articles->currentPage(),
                    'last_page' => $articles->lastPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                    'from' => $articles->firstItem(),
                    'to' => $articles->lastItem(),
                    'next_page_url' => $articles->nextPageUrl(),
                    'prev_page_url' => $articles->previousPageUrl(),
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Knowledge Base Index Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المقالات',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
    
    /**
     * Display the specified article
     * GET /api/v1/knowledge-base/article/{slug}
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        try {
            Log::info('Article Show Called', ['slug' => $slug]);
            
            $article = KnowledgeBaseArticle::query()
                ->where('slug', $slug)
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->with([
                    'course:id,title,slug,description',
                    'tags',
                    'author:id,name',
                    'attachments'
                ])
                ->first();
            
            if (!$article) {
                Log::warning('Article not found', ['slug' => $slug]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'المقال غير موجود أو غير منشور',
                    'debug' => [
                        'slug' => $slug,
                        'total_articles' => KnowledgeBaseArticle::where('slug', $slug)->count(),
                        'published_articles' => KnowledgeBaseArticle::where('slug', $slug)->where('status', 'published')->count(),
                    ]
                ], 404);
            }
            
            // Increment views count
            $article->increment('views_count');
            
            // Get related articles
            $relatedArticles = $this->getRelatedArticles($article);
            
            return response()->json([
                'success' => true,
                'message' => 'تم جلب المقال بنجاح',
                'data' => [
                    'article' => new KnowledgeBaseArticleResource($article),
                    'related_articles' => KnowledgeBaseArticleResource::collection($relatedArticles),
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Article Show Error', [
                'slug' => $slug,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المقال',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Display articles for a specific course
     * GET /api/v1/knowledge-base/course/{course}
     */
    public function forCourse(Request $request, $courseId): JsonResponse
    {
        try {
            $course = Course::findOrFail($courseId);
            
            // Check enrollment
            if ($user = $request->user()) {
                $isEnrolled = $user->enrollments()
                    ->where('course_id', $course->id)
                    ->exists();
                
                if (!$isEnrolled) {
                    return response()->json([
                        'success' => false,
                        'message' => 'يجب أن تكون مسجلاً في هذه الدورة للوصول إلى موسوعتها'
                    ], 403);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تسجيل الدخول للوصول إلى هذا المحتوى'
                ], 401);
            }
            
            $perPage = min($request->input('per_page', 12), 50);
            $articles = KnowledgeBaseArticle::query()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->where('course_id', $course->id)
                ->with(['tags', 'author:id,name'])
                ->latest('published_at')
                ->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'message' => 'تم جلب مقالات الدورة بنجاح',
                'data' => [
                    'course' => [
                        'id' => $course->id,
                        'title' => $course->title,
                        'slug' => $course->slug,
                        'description' => $course->description,
                    ],
                    'articles' => KnowledgeBaseArticleResource::collection($articles->items()),
                ],
                'pagination' => [
                    'current_page' => $articles->currentPage(),
                    'last_page' => $articles->lastPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب مقالات الدورة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all tags
     * GET /api/v1/knowledge-base/tags
     */
    public function tags(): JsonResponse
    {
        try {
            $tags = ArticleTag::query()
                ->withCount(['articles' => function ($query) {
                    $query->where('status', 'published')
                          ->whereNotNull('published_at')
                          ->where('published_at', '<=', now());
                }])
                ->having('articles_count', '>', 0)
                ->orderBy('name')
                ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'تم جلب الوسوم بنجاح',
                'data' => [
                    'tags' => ArticleTagResource::collection($tags)
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الوسوم',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get articles by tag
     * GET /api/v1/knowledge-base/tag/{slug}
     */
    public function byTag(Request $request, string $slug): JsonResponse
    {
        try {
            $tag = ArticleTag::where('slug', $slug)->firstOrFail();
            
            $perPage = min($request->input('per_page', 12), 50);
            $articles = $tag->articles()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->with(['course:id,title,slug', 'tags', 'author:id,name'])
                ->latest('published_at')
                ->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'message' => 'تم جلب المقالات بنجاح',
                'data' => [
                    'tag' => new ArticleTagResource($tag),
                    'articles' => KnowledgeBaseArticleResource::collection($articles->items()),
                ],
                'pagination' => [
                    'current_page' => $articles->currentPage(),
                    'last_page' => $articles->lastPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                ]
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'الوسم غير موجود'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المقالات',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get popular articles (most viewed)
     * GET /api/v1/knowledge-base/popular
     */
    public function popular(Request $request): JsonResponse
    {
        try {
            $limit = min($request->input('limit', 10), 50);
            
            $articles = KnowledgeBaseArticle::query()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->with(['course:id,title,slug', 'tags', 'author:id,name'])
                ->orderBy('views_count', 'desc')
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'تم جلب المقالات الأكثر مشاهدة بنجاح',
                'data' => [
                    'articles' => KnowledgeBaseArticleResource::collection($articles)
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المقالات',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get recent articles
     * GET /api/v1/knowledge-base/recent
     */
    public function recent(Request $request): JsonResponse
    {
        try {
            $limit = min($request->input('limit', 10), 50);
            
            $articles = KnowledgeBaseArticle::query()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->with(['course:id,title,slug', 'tags', 'author:id,name'])
                ->latest('published_at')
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'تم جلب أحدث المقالات بنجاح',
                'data' => [
                    'articles' => KnowledgeBaseArticleResource::collection($articles)
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المقالات',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Search articles
     * POST /api/v1/knowledge-base/search
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2|max:100',
                'per_page' => 'nullable|integer|min:1|max:50',
            ]);
            
            $query = $request->input('query');
            $perPage = min($request->input('per_page', 12), 50);
            
            $articles = KnowledgeBaseArticle::query()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                      ->orWhere('content', 'like', "%{$query}%")
                      ->orWhere('excerpt', 'like', "%{$query}%");
                })
                ->with(['course:id,title,slug', 'tags', 'author:id,name'])
                ->latest('published_at')
                ->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'message' => 'تم البحث بنجاح',
                'data' => [
                    'query' => $query,
                    'articles' => KnowledgeBaseArticleResource::collection($articles->items()),
                ],
                'pagination' => [
                    'current_page' => $articles->currentPage(),
                    'last_page' => $articles->lastPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء البحث',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get related articles
     */
    private function getRelatedArticles(KnowledgeBaseArticle $article, int $limit = 3)
    {
        return KnowledgeBaseArticle::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('id', '!=', $article->id)
            ->where(function ($query) use ($article) {
                if ($article->course_id) {
                    $query->where('course_id', $article->course_id);
                }
                
                if ($article->tags->isNotEmpty()) {
                    $query->orWhereHas('tags', function ($q) use ($article) {
                        $q->whereIn('article_tags.id', $article->tags->pluck('id'));
                    });
                }
            })
            ->with(['course:id,title,slug', 'tags'])
            ->limit($limit)
            ->get();
    }
}