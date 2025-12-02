<?php

namespace App\Http\Controllers\Api\EvaluationController;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\EvaluationResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EvaluationController extends Controller
{
    /**
     * Get products list for evaluation creation
     */
    public function getProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_type' => 'required|in:course,bootcamp,workshop',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $type = $request->product_type;
        
        // Assuming you have Course, Bootcamp, Workshop models
        $products = match($type) {
            'course' => \App\Models\Course::select('id', 'title')->get(),
            'bootcamp' => \App\Models\Bootcamp::select('id', 'title')->get(),
            'workshop' => \App\Models\Workshop::select('id', 'title')->get(),
        };

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Load standard questions template
     */
    public function getStandardQuestions()
    {
        $evaluation = new Evaluation();
        $questions = $evaluation->loadStandardQuestions();

        return response()->json([
            'success' => true,
            'data' => $questions
        ]);
    }

    /**
     * Create new evaluation
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_type' => 'required|in:course,bootcamp,workshop',
            'product_id' => 'required|integer',
            'product_name' => 'required|string',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => 'required|in:rating,scale,yes_no,text,grade',
            'questions.*.options' => 'nullable|array',
            'questions.*.order' => 'required|integer',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $evaluation = Evaluation::create([
                'product_type' => $request->product_type,
                'product_id' => $request->product_id,
                'product_name' => $request->product_name,
                'is_active' => $request->is_active ?? true,
            ]);

            foreach ($request->questions as $question) {
                $evaluation->questions()->create($question);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء التقييم بنجاح',
                'data' => $evaluation->load('questions')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء التقييم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get evaluation by product
     */
    public function getByProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_type' => 'required|in:course,bootcamp,workshop',
            'product_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $evaluation = Evaluation::forProduct($request->product_type, $request->product_id)
            ->active()
            ->with('questions')
            ->first();

        if (!$evaluation) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد محتوى متعلق بالمنتج المختار'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $evaluation
        ]);
    }

    /**
     * Update evaluation
     */
    public function update(Request $request, Evaluation $evaluation)
    {
        $validator = Validator::make($request->all(), [
            'product_name' => 'sometimes|string',
            'questions' => 'sometimes|array|min:1',
            'questions.*.id' => 'sometimes|exists:evaluation_questions,id',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => 'required|in:rating,scale,yes_no,text,grade',
            'questions.*.options' => 'nullable|array',
            'questions.*.order' => 'required|integer',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $evaluation->update($request->only(['product_name', 'is_active']));

            if ($request->has('questions')) {
                // Delete existing questions
                $evaluation->questions()->delete();
                
                // Create new questions
                foreach ($request->questions as $question) {
                    $evaluation->questions()->create($question);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث التقييم بنجاح',
                'data' => $evaluation->load('questions')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث التقييم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete evaluation
     */
    public function destroy(Evaluation $evaluation)
    {
        try {
            $evaluation->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف التقييم بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف التقييم',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}