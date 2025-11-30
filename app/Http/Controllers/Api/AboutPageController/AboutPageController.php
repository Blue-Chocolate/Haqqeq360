<?php

namespace App\Http\Controllers\Api\AboutPageController;

use App\Http\Controllers\Controller;
use App\Repositories\AboutPageRepository\AboutPageRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AboutPageController extends Controller
{
    protected $aboutPageRepository;

    /**
     * Create a new controller instance.
     *
     * @param AboutPageRepository $aboutPageRepository
     */
    public function __construct(AboutPageRepository $aboutPageRepository)
    {
        $this->aboutPageRepository = $aboutPageRepository;
    }

    /**
     * Display a listing of the about pages.
     * 
     * @OA\Get(
     *     path="/api/about-pages",
     *     tags={"About Pages"},
     *     summary="Get list of about pages",
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=400, description="Bad request")
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validate per_page parameter
            $perPage = $request->input('per_page', 15);
            
            // Ensure per_page is between 1 and 100
            $perPage = max(1, min(100, (int) $perPage));
            
            // Get paginated about pages (published and active only)
            $aboutPages = $this->aboutPageRepository->getPublishedPaginated($perPage);

            // Check if no data found
            if ($aboutPages->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'لا توجد صفحات متاحة حالياً',
                    'data' => [
                        'about_pages' => [],
                        'pagination' => [
                            'total' => 0,
                            'per_page' => $perPage,
                            'current_page' => 1,
                            'last_page' => 1,
                            'from' => null,
                            'to' => null,
                        ]
                    ]
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم جلب البيانات بنجاح',
                'data' => [
                    'about_pages' => $aboutPages->items(),
                    'pagination' => [
                        'total' => $aboutPages->total(),
                        'per_page' => $aboutPages->perPage(),
                        'current_page' => $aboutPages->currentPage(),
                        'last_page' => $aboutPages->lastPage(),
                        'from' => $aboutPages->firstItem(),
                        'to' => $aboutPages->lastItem(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            // Handle error - ERR-ABOUT-HERO-01, ERR-ABOUT-CONT-01, ERR-ABOUT-VIS-01
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
            ], 500);
        }
    }

    /**
     * Display the specified about page.
     * 
     * @OA\Get(
     *     path="/api/about-pages/{id}",
     *     tags={"About Pages"},
     *     summary="Get specific about page",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="About page ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="About page not found")
     * )
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Find published about page by ID
            $aboutPage = $this->aboutPageRepository->findPublishedById($id);

            // Check if about page not found
            if (!$aboutPage) {
                return response()->json([
                    'success' => false,
                    'message' => 'الصفحة غير موجودة أو غير منشورة',
                    'data' => null
                ], 404);
            }

            // Prepare response data with fallbacks for errors
            $responseData = [
                'id' => $aboutPage->id,
                
                // Hero Section - FR-ABOUT-HERO-01, FR-ABOUT-HERO-02
                'hero' => [
                    'title' => $aboutPage->hero_title ?: 'من نحن في أكاديمية حقق 360', // ERR-ABOUT-HERO-01 fallback
                    'description' => $aboutPage->hero_description,
                    'background_image' => $aboutPage->hero_background_image_url, // ERR-ABOUT-HERO-02: null if failed
                    'overlay_opacity' => $aboutPage->hero_overlay_opacity,
                    'word_count' => $aboutPage->hero_description_word_count,
                ],
                
                // About Content - FR-ABOUT-CONT-01, FR-ABOUT-CONT-02
                'about' => [
                    'content' => $aboutPage->about_content ?: 'محتوى قيد التحديث', // ERR-ABOUT-CONT-01 placeholder
                    'show_icons' => $aboutPage->show_about_icons,
                    'word_count' => $aboutPage->about_content_word_count,
                ],
                
                // Vision Section - FR-ABOUT-VIS-01, FR-ABOUT-VIS-02
                'vision' => $aboutPage->show_vision_section ? [
                    'title' => $aboutPage->vision_title,
                    'content' => $aboutPage->vision_content ?: 'رؤيتنا قيد التحديث', // ERR-ABOUT-VIS-01 fallback
                    'icon' => $aboutPage->vision_icon_url,
                    'word_count' => $aboutPage->vision_content_word_count,
                ] : null, // FR-ABOUT-VIS-03: hide section if not active
                
                'status' => $aboutPage->status,
                'created_at' => $aboutPage->created_at,
                'updated_at' => $aboutPage->updated_at,
            ];

            return response()->json([
                'success' => true,
                'message' => 'تم جلب البيانات بنجاح',
                'data' => $responseData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
            ], 500);
        }
    }
}