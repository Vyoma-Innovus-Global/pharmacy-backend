<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminSubjectCategoryController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/subject-categories",
     *     tags={"Admin - Master Data"},
     *     summary="Get all subject categories",
     *     description="Retrieves all subject categories for a given semester. Calls: fn_admin_getallsubjectcategory(p_admin_user_id, p_semester)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"admin_user_id", "semester"},
     *             @OA\Property(property="admin_user_id", type="integer", example=668, description="Admin user ID"),
     *             @OA\Property(property="semester", type="integer", example=1, description="Semester number (1-6)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subject categories retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Successfully retrieved subject categories."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="subjectCategoryId", type="integer", example=1),
     *                     @OA\Property(property="subjectCategoryName", type="string", example="Theory"),
     *                     @OA\Property(property="semester", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="Validation failed.")
     *         )
     *     )
     * )
     *
     * POST /api/admin/subject-categories
     *
     * Get all subject categories for a given semester.
     *
     * Body: {
     *   "admin_user_id": 668,
     *   "semester": 1
     * }
     *
     * Calls: fn_admin_getallsubjectcategory(p_admin_user_id, p_semester)
     */
    public function getAllSubjectCategories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_user_id' => 'required|integer',
            'semester'      => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed.',
                'data'    => $validator->errors(),
            ], 422);
        }

        $adminUserId = (int) $request->input('admin_user_id');
        $semester    = (int) $request->input('semester');

        Log::channel('daily')->info('[getAllSubjectCategories] INPUT', [
            'admin_user_id' => $adminUserId,
            'semester'      => $semester,
            'ip'            => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_admin_getallsubjectcategory(?::bigint, ?::integer) AS data',
                [$adminUserId, $semester]
            );

            $raw = $result[0]->data ?? null;

            if (!$raw) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No data returned from database function.',
                    'data'    => [],
                ], 404);
            }

            // DB function returns JSON string
            $categories = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('[getAllSubjectCategories] JSON_DECODE_ERROR', [
                    'error' => json_last_error_msg(),
                    'raw'   => $raw,
                ]);
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'Failed to parse database response.',
                    'data'    => null,
                ], 500);
            }

            if (empty($categories)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No subject categories found.',
                    'data'    => [],
                ], 404);
            }

            Log::channel('daily')->info('[getAllSubjectCategories] OUTPUT (200)', [
                'count' => count($categories),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Data fetch successfully',
                'data'    => $categories,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[getAllSubjectCategories] EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }
}
