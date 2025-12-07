<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

use App\Http\Controllers\Api\Auth\DumpAuth;

Route::post('/register', [DumpAuth::class, 'register']);
Route::post('/login',    [DumpAuth::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me',    [DumpAuth::class, 'me']);
    Route::post('/logout', [DumpAuth::class, 'logout']);
});
// Route::prefix('auth')->group(function () {
//     Route::post('/register', [AuthController::class, 'register']);
//     Route::post('/login', [AuthController::class, 'login']);
//     Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
//     Route::post('/resend-verification', [AuthController::class, 'resendVerificationEmail']);
//     Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
//     Route::post('/reset-password', [AuthController::class, 'resetPassword']);
// });

// Protected routes (requires authentication)
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
});


use App\Http\Controllers\Api\UserController\UserController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});

use App\Http\Controllers\Api\NotificationController\NotificationController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/{id}', [NotificationController::class, 'show']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markRead']); // wHEN USER click on the notification it is marked as read in database
    Route::patch('notifications/{id}/unread', [NotificationController::class, 'markUnread']);
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);
});

use App\Http\Controllers\Api\BootcampController\BootcampController;

Route::get('bootcamps', [BootcampController::class, 'index']);
Route::get('bootcamps/{id}', [BootcampController::class, 'show']);

use App\Http\Controllers\Api\EnrollmentController\EnrollmentController;
Route::middleware('auth:sanctum')->group(function () {
    Route::post('enrollments', [EnrollmentController::class, 'store']);
    Route::get('enrollments', [EnrollmentController::class, 'index']);
    Route::get('enrollments/{id}', [EnrollmentController::class, 'show']);
});

use App\Http\Controllers\Api\CourseController\CourseController;

Route::prefix('courses')->group(function () {

    // List all courses (paginated, optional limit)
    Route::get('/', [CourseController::class, 'index']);

    // Show a single course by ID
    Route::get('/{id}', [CourseController::class, 'show']);

    // Search courses by title
    Route::get('/search', [CourseController::class, 'search']);

    // Filter courses by level/mode
    Route::get('/filter', [CourseController::class, 'filter']);
});


use App\Http\Controllers\Api\ProgramController\ProgramController;
use App\Http\Controllers\Api\CategoryController\CategoryController;

// Programs routes
Route::prefix('programs')->group(function () {
    Route::get('/', [ProgramController::class, 'index']); // List programs
    Route::get('/{idOrSlug}', [ProgramController::class, 'show']); // Show program by ID or slug
});

// Categories routes
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']); // List categories
    Route::get('/{idOrSlug}', [CategoryController::class, 'show']); // Show category by ID or slug
});

use App\Http\Controllers\Api\RequestProgramController\RequestProgramController;


// API Routes - User can only create and view their requests
Route::middleware('auth:sanctum')->group(function () {
    Route::get('request-programs', [RequestProgramController::class, 'index']);
    Route::post('request-programs', [RequestProgramController::class, 'store']);
});

use App\Http\Controllers\Api\ContactUsController\ContactUsController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('contact-us', [ContactUsController::class, 'store']);
});
use App\Http\Controllers\Api\ScholarshipRequestController\ScholarshipRequestController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('scholarship-requests', [ScholarshipRequestController::class, 'index']);
    Route::post('scholarship-requests', [ScholarshipRequestController::class, 'store']);
});

use App\Http\Controllers\Api\HeaderController\HeaderController;

Route::get('headers/{idOrSlug}', [HeaderController::class, 'show']);
Route::get('headers', [HeaderController::class, 'index']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('headers', [HeaderController::class, 'store']);
});

use App\Http\Controllers\Api\ProgramCategoryController\ProgramCategoryController;

Route::get('/program-categories', [ProgramCategoryController::class, 'index']);          // recommended
Route::get('/program-categories-get', [ProgramCategoryController::class, 'indexWithGet']); // uses get()
Route::get('/program-categories/{id}', [ProgramCategoryController::class, 'show']);

use App\Http\Controllers\Api\BlogCategoryController\BlogCategoryController;
Route::get('/blog-categories', [BlogCategoryController::class, 'index']);          // recommended
Route::get('/blog-categories-get', [BlogCategoryController::class, 'indexWithGet']); // uses get()
Route::get('/blog-categories/{id}', [BlogCategoryController::class, 'show']);

use App\Http\Controllers\Api\BlogController\BlogController;
Route::get('/blogs', [BlogController::class, 'index']);          // recommended
Route::get('/blogs-get', [BlogController::class, 'indexWithGet']); // uses get()
Route::get('/blogs/{id}', [BlogController::class, 'show']);

use App\Http\Controllers\Api\NewsController\NewsController;

Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/{id}', [NewsController::class, 'show']);
Route::post('/news', [NewsController::class, 'store']);

use App\Http\Controllers\Api\SubscriberController\SubscriberController;

Route::post('/subscribe', [SubscriberController::class, 'store']);


use App\Http\Controllers\Api\WorkshopController\WorkshopController;

Route::get('/workshops', [WorkshopController::class, 'index']);
Route::get('/workshops/{id}', [WorkshopController::class, 'show']);

use App\Http\Controllers\Api\DigitalProductController\DigitalProductController;

Route::get('/digital-products', [DigitalProductController::class, 'index']);
Route::get('/digital-products/{id}', [DigitalProductController::class, 'show']);
Route::get('/digital-products/search/{keyword}', [DigitalProductController::class, 'search']); // ⭐ New Search Route

use App\Http\Controllers\Api\CommonQuestionController\CommonQuestionController;

Route::get('/common-questions', [CommonQuestionController::class, 'index']);
Route::get('/common-questions/{id}', [CommonQuestionController::class, 'show']);


use App\Http\Controllers\Api\TestimonialController\TestimonialController;

Route::get('/testimonials', [TestimonialController::class, 'index']);
Route::get('/testimonials/{id}', [TestimonialController::class, 'show']);
Route::post('/testimonials', [TestimonialController::class, 'store']);

use App\Http\Controllers\Api\AboutPageController\AboutPageController;


    
    // About Pages Routes
    Route::prefix('about-pages')->group(function () {
        
        // GET /api/v1/about-pages
        // Returns paginated list of published about pages
        // Query params: per_page (default: 15, max: 100), page (default: 1)
        Route::get('/', [AboutPageController::class, 'index'])
            ->name('api.about-pages.index');
        
        // GET /api/v1/about-pages/{id}
        // Returns single about page by ID
        Route::get('/{id}', [AboutPageController::class, 'show'])
            ->name('api.about-pages.show')
            ->where('id', '[0-9]+');
    });

use App\Http\Controllers\Api\WhyChooseFeatureController\WhyChooseFeatureController;
Route::get ('/why-choose-features', [WhyChooseFeatureController::class, 'index']);
Route::get ('/why-choose-features/{id}', [WhyChooseFeatureController::class, 'show']);

use App\Http\Controllers\Api\PartnersControllers\PartnersController;

Route::get('/partners', [PartnersController::class, 'index']);
Route::get('/partners/{id}', [PartnersController::class, 'show']);

use App\Http\Controllers\Api\TestController\TestController;
use App\Http\Controllers\Api\TestAttemptController\TestAttemptController;

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Test Routes
    Route::prefix('tests')->group(function () {
        Route::get('/', [TestController::class, 'index']); // Get all available tests
        Route::get('/{id}', [TestController::class, 'show']); // Get specific test details
        Route::get('/{testId}/history', [TestController::class, 'getAttemptHistory']); // Get user's attempt history
        
        // Get tests by entity type
        Route::get('/bootcamps/{id}', [TestController::class, 'getBootcampTests']);
        Route::get('/workshops/{id}', [TestController::class, 'getWorkshopTests']);
        Route::get('/programs/{id}', [TestController::class, 'getProgramTests']);
        Route::get('/courses/{id}', [TestController::class, 'getCourseTests']);
    });

    // Test Attempt Routes
    Route::prefix('test-attempts')->group(function () {
        Route::post('/{testId}/start', [TestAttemptController::class, 'start']); // Start new attempt
        Route::get('/{attemptId}', [TestAttemptController::class, 'getAttempt']); // Get active attempt
        Route::post('/{attemptId}/answer', [TestAttemptController::class, 'saveAnswer']); // Save answer
        Route::post('/{attemptId}/submit', [TestAttemptController::class, 'submit']); // Submit attempt
        Route::get('/{attemptId}/result', [TestAttemptController::class, 'getResult']); // Get results
        Route::get('/', [TestAttemptController::class, 'myAttempts']); // Get all user's attempts
    });
});

use App\Http\Controllers\Api\EvaluationController\EvaluationController;

use App\Http\Controllers\Api\EvaluationController\EvaluationResponseController;

Route::prefix('admin')->group(function () {
    
    // Evaluation Management
    Route::prefix('evaluations')->group(function () {
        Route::get('/products', [EvaluationController::class, 'getProducts']);
        Route::get('/standard-questions', [EvaluationController::class, 'getStandardQuestions']);
        Route::post('/', [EvaluationController::class, 'store']);
        Route::get('/by-product', [EvaluationController::class, 'getByProduct']);
        Route::put('/{evaluation}', [EvaluationController::class, 'update']);
        Route::delete('/{evaluation}', [EvaluationController::class, 'destroy']);
        Route::get('/{evaluation}/results', [EvaluationResponseController::class, 'getResults']);
    });
});

// Student Routes - Protected by auth middleware
Route::middleware(['auth:sanctum'])->prefix('student')->group(function () {
    
    // Student Evaluation Access
    Route::prefix('evaluations')->group(function () {
        Route::get('/available', [EvaluationResponseController::class, 'getAvailableEvaluations']);
        Route::post('/{evaluation}/submit', [EvaluationResponseController::class, 'submit']);
    });
});

use App\Http\Controllers\Api\CoursePublishRequestController\CoursePublishRequestController;

Route::middleware('auth:sanctum')->group(function () {
    // Course Publish Requests
    Route::get('/course-publish-requests', [CoursePublishRequestController::class, 'index']);
    Route::post('/course-publish-requests', [CoursePublishRequestController::class, 'store']);
    Route::get('/course-publish-requests/statistics', [CoursePublishRequestController::class, 'statistics']);
    Route::get('/course-publish-requests/{id}', [CoursePublishRequestController::class, 'show']);
    Route::put('/course-publish-requests/{id}', [CoursePublishRequestController::class, 'update']);
    Route::delete('/course-publish-requests/{id}', [CoursePublishRequestController::class, 'destroy']);
});

use App\Http\Controllers\Api\CaseStudyController\Instructor\CaseStudyController as InstructorCaseStudyController;
use App\http\Controllers\Api\CaseStudyController\Student\CaseStudyController  as LearnerCaseStudyController;

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Instructor Routes
    Route::middleware(['role:instructor,admin'])->prefix('instructor')->group(function () {
        
        Route::prefix('case-studies')->group(function () {
            Route::get('/', [InstructorCaseStudyController::class, 'index']);
            Route::post('/', [InstructorCaseStudyController::class, 'store']);
            Route::get('/{id}', [InstructorCaseStudyController::class, 'show']);
            Route::put('/{id}', [InstructorCaseStudyController::class, 'update']);
            Route::delete('/{id}', [InstructorCaseStudyController::class, 'destroy']);
            
            // Toggle status
            Route::patch('/{id}/toggle-status', [InstructorCaseStudyController::class, 'toggleStatus']);
            
            // View answers
            Route::get('/{id}/answers', [InstructorCaseStudyController::class, 'answers']);
            Route::get('/{caseStudyId}/answers/{answerId}', [InstructorCaseStudyController::class, 'viewAnswer']);
        });
    });

    // Learner Routes
    Route::middleware(['role:learner'])->prefix('learner')->group(function () {
        
        Route::prefix('case-studies')->group(function () {
            Route::get('/', [LearnerCaseStudyController::class, 'index']);
            Route::get('/{id}', [LearnerCaseStudyController::class, 'show']);
            
            // Submit answer
            Route::post('/{id}/answer', [LearnerCaseStudyController::class, 'submitAnswer']);
            
            // My submissions
            Route::get('/my/submissions', [LearnerCaseStudyController::class, 'mySubmissions']);
            
            // Update answer
            Route::put('/answers/{id}', [LearnerCaseStudyController::class, 'updateAnswer']);
            
            // File management
            Route::delete('/answers/{answerId}/files/{fileId}', [LearnerCaseStudyController::class, 'deleteFile']);
            Route::get('/answers/{answerId}/files/{fileId}/download', [LearnerCaseStudyController::class, 'downloadFile']);
        });
    });
});

use App\Http\Controllers\Api\KnowledgeBaseController\KnowledgeBaseController;


Route::prefix('learner')->group(function () {
    
    // Public Routes - لا تحتاج Authentication
    Route::prefix('knowledge-base')->group(function () {
        
        // Get all articles with filters
        // GET /api/v1/knowledge-base
        // Query params: search, course_id, tag_id, sort_by, sort_order, per_page
        Route::get('/', [KnowledgeBaseController::class, 'index'])
            ->name('index');
        
        // Get article by slug
        // GET /api/v1/knowledge-base/article/{slug}
        Route::get('/article/{slug}', [KnowledgeBaseController::class, 'show'])
            ->name('show');
        
        // Get all tags
        // GET /api/v1/knowledge-base/tags
        Route::get('/tags', [KnowledgeBaseController::class, 'tags'])
            ->name('tags');
        
        // Get articles by tag slug
        // GET /api/v1/knowledge-base/tag/{slug}
        Route::get('/tag/{slug}', [KnowledgeBaseController::class, 'byTag'])
            ->name('by-tag');
        
        // Get popular articles (most viewed)
        // GET /api/v1/knowledge-base/popular?limit=10
        Route::get('/popular', [KnowledgeBaseController::class, 'popular'])
            ->name('popular');
        
        // Get recent articles
        // GET /api/v1/knowledge-base/recent?limit=10
        Route::get('/recent', [KnowledgeBaseController::class, 'recent'])
            ->name('recent');
        
        // Search articles
        // POST /api/v1/knowledge-base/search
        // Body: { "query": "search term", "per_page": 12 }
        Route::post('/search', [KnowledgeBaseController::class, 'search'])
            ->name('search');
    });
    
    // Protected Routes - تحتاج Authentication (Sanctum)
    Route::middleware(['auth:sanctum'])->group(function () {
        
        Route::prefix('knowledge-base')->name('api.kb.')->group(function () {
            
            // Get articles for a specific course (requires enrollment)
            // GET /api/v1/knowledge-base/course/{course}
            Route::get('/course/{course}', [KnowledgeBaseController::class, 'forCourse'])
                ->name('course');
        });
    });
});

use App\Http\Controllers\Api\DiscussionThreadController;
use App\Http\Controllers\Api\DiscussionReplyController;
use App\Http\Controllers\Api\DiscussionNotificationController;

// Route::middleware(['auth:sanctum'])->prefix('learner')->group(function () {
    
//     // Discussion Threads
//     Route::get('courses/{course}/discussions', [DiscussionThreadController::class, 'index']);
//     Route::get('discussions/{thread}', [DiscussionThreadController::class, 'show']);
    
//     // Discussion Replies
//     Route::post('discussions/{thread}/replies', [DiscussionReplyController::class, 'store']);
//     Route::put('replies/{reply}', [DiscussionReplyController::class, 'update']);
//     Route::delete('replies/{reply}', [DiscussionReplyController::class, 'destroy']);
//     Route::post('replies/{reply}/like', [DiscussionReplyController::class, 'toggleLike']);
    
//     // Discussion Notifications
//     Route::get('discussion-notifications', [DiscussionNotificationController::class, 'index']);
//     Route::get('discussion-notifications/unread', [DiscussionNotificationController::class, 'unread']);
//     Route::put('discussion-notifications/{notification}/read', [DiscussionNotificationController::class, 'markAsRead']);
//     Route::put('discussion-notifications/read-all', [DiscussionNotificationController::class, 'markAllAsRead']);
// });

use App\Http\Controllers\Api\LinkTreeController\LinkTreeController;

Route::prefix('link-tree')->group(function () {
    // Public endpoints
    Route::get('/{slug?}', [LinkTreeController::class, 'show']);
    Route::post('/track/{linkId}', [LinkTreeController::class, 'trackClick']);
    
    // Protected endpoint for analytics
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/analytics', [LinkTreeController::class, 'analytics']);
    });
});

use App\Http\Controllers\Api\PlanController\PlanController;
use App\Http\Controllers\Api\SubscriptionController\SubscriptionController;

Route::middleware('auth:sanctum')->group(function () {
    // Plans
    Route::get('/plans', [PlanController::class, 'index']);
    Route::get('/plans/{plan}', [PlanController::class, 'show']);
    Route::get('/plans/type/{type}', [PlanController::class, 'byType']);
    
    // Subscriptions
    Route::post('/subscriptions', [SubscriptionController::class, 'store']);
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'show']);
});
use App\Http\Controllers\Api\BankAccountController\BankAccountController;

Route::prefix('bank-accounts')->group(function () {
    Route::get('/', [BankAccountController::class, 'index']);
    Route::get('/{bankAccount}', [BankAccountController::class, 'show']);
    Route::get('/search/by-bank', [BankAccountController::class, 'getByBank']);
});