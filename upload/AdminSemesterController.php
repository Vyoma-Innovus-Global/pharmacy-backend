<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminSemesterController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/semesters",
     *     tags={"Admin - Master Data"},
     *     summary="Get all semesters",
     *     description="Retrieves all semesters list filtered by semester type. Calls: fn_admin_getallsemesters(p_admin_user_id, p_semester_type)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"admin_user_id", "semester_type"},
     *             @OA\Property(property="admin_user_id", type="integer", example=668, description="Admin user ID"),
     *             @OA\Property(property="semester_type", type="integer", example=1, description="Semester type filter (1=Odd, 2=Even, etc.)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Semesters list retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Successfully retrieved semesters."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="semesterId", type="string", example="1"),
     *                     @OA\Property(property="semesterName", type="string", example="First Semester"),
     *                     @OA\Property(property="semesterType", type="integer", example=1)
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
     * POST /api/admin/semesters
     *
     * Get all semesters list filtered by semester type.
     *
     * Body: {
     *   "admin_user_id": 668,
     *   "semester_type": 1
     * }
     *
     * Calls: fn_admin_getallsemesters(p_admin_user_id, p_semester_type)
     */
    public function getAllSemesters(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_user_id' => 'required|integer',
            'semester_type' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed.',
                'data'    => $validator->errors(),
            ], 422);
        }

        $adminUserId  = (int) $request->input('admin_user_id');
        $semesterType = (int) $request->input('semester_type');

        Log::channel('daily')->info('[getAllSemesters] INPUT', [
            'admin_user_id' => $adminUserId,
            'semester_type' => $semesterType,
            'ip'            => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_admin_getallsemesters(?::bigint, ?::integer) AS data',
                [$adminUserId, $semesterType]
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
            $semesters = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('[getAllSemesters] JSON_DECODE_ERROR', [
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

            if (empty($semesters)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No semesters found.',
                    'data'    => [],
                ], 404);
            }

            Log::channel('daily')->info('[getAllSemesters] OUTPUT (200)', [
                'count' => count($semesters),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Data fetch successfully',
                'data'    => $semesters,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[getAllSemesters] EXCEPTION', [
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
