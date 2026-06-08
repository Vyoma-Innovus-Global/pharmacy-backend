<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminInstituteWiseSummaryController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/institute-wise-summary",
     *     tags={"Admin - Reports"},
     *     summary="Get institute wise summary",
     *     description="Retrieve institute wise subject submission summary using fn_admin_getinstitutewisesummary",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "semester_id", "exam_year", "inst_code", "dept_code", "subject_code", "subject_category_id", "mark_type"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="semester_id", type="integer", example=1),
     *             @OA\Property(property="exam_year", type="integer", example=2026),
     *             @OA\Property(property="inst_code", type="string", example="JCG"),
     *             @OA\Property(property="dept_code", type="string", example="PHARM"),
     *             @OA\Property(property="subject_code", type="string", example="SOPHP"),
     *             @OA\Property(property="subject_category_id", type="integer", example=2),
     *             @OA\Property(property="mark_type", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function getInstituteWiseSummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'             => 'required_without:admin_user_id|integer',
            'admin_user_id'       => 'required_without:user_id|integer',
            'semester_id'         => 'required|integer',
            'exam_year'           => 'required|integer',
            'inst_code'           => 'required|string',
            'dept_code'           => 'required|string',
            'subject_code'        => 'required|string',
            'subject_category_id' => 'required|integer',
            'mark_type'           => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed.',
                'data'    => $validator->errors(),
            ], 422);
        }

        $userId            = (int) $request->input('user_id', $request->input('admin_user_id'));
        $semesterId        = (int) $request->input('semester_id');
        $examYear          = (int) $request->input('exam_year');
        $instCode          = $request->input('inst_code');
        $deptCode          = $request->input('dept_code');
        $subjectCode       = $request->input('subject_code');
        $subjectCategoryId = (int) $request->input('subject_category_id');
        $markType          = (int) $request->input('mark_type');

        Log::channel('daily')->info('[getInstituteWiseSummary] INPUT', [
            'user_id'             => $userId,
            'semester_id'         => $semesterId,
            'exam_year'           => $examYear,
            'inst_code'           => $instCode,
            'dept_code'           => $deptCode,
            'subject_code'        => $subjectCode,
            'subject_category_id' => $subjectCategoryId,
            'mark_type'           => $markType,
            'ip'                  => $request->ip(),
        ]);

        try {
            $result = DB::selectOne(
                'SELECT public.fn_admin_getinstitutewisesummary(?::bigint, ?::integer, ?::integer, ?::varchar, ?::varchar, ?::varchar, ?::integer, ?::integer) AS data',
                [
                    $userId,
                    $semesterId,
                    $examYear,
                    $instCode,
                    $deptCode,
                    $subjectCode,
                    $subjectCategoryId,
                    $markType,
                ]
            );

            $rawData = $result->data ?? null;

            if (is_null($rawData)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No summary found.',
                    'data'    => [],
                ], 404);
            }

            $summary = $rawData;
            if (is_string($rawData)) {
                $summary = json_decode($rawData, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::channel('daily')->error('[getInstituteWiseSummary] JSON_DECODE_ERROR', [
                        'error' => json_last_error_msg(),
                        'raw'   => $rawData,
                    ]);

                    return response()->json([
                        'version' => '1.0',
                        'status'  => 1,
                        'message' => 'Failed to parse database response.',
                        'data'    => [],
                    ], 500);
                }
            }

            $summary = is_array($summary) ? $summary : (array) $summary;

            Log::channel('daily')->info('[getInstituteWiseSummary] OUTPUT (200)', [
                'count' => count($summary),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Institute wise summary retrieved successfully.',
                'data'    => $summary,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[getInstituteWiseSummary] EXCEPTION', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Failed to retrieve institute wise summary: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/examiner-wise-summary",
     *     tags={"Admin - Reports"},
     *     summary="Get examiner wise summary",
     *     description="Retrieve examiner wise subject submission summary using fn_admin_examinerwisesummary",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"semester_id", "exam_year", "inst_code", "dept_code", "subject_code", "subject_category_id", "mark_type", "status"},
     *             @OA\Property(property="semester_id", type="integer", example=1),
     *             @OA\Property(property="exam_year", type="integer", example=2025),
     *             @OA\Property(property="inst_code", type="string", example="0"),
     *             @OA\Property(property="dept_code", type="string", example="PHARM"),
     *             @OA\Property(property="subject_code", type="string", example="0"),
     *             @OA\Property(property="subject_category_id", type="integer", example=0),
     *             @OA\Property(property="mark_type", type="integer", example=1),
     *             @OA\Property(property="status", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function getExaminerWiseSummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'semester_id'         => 'required|integer',
            'exam_year'           => 'required|integer',
            'inst_code'           => 'required|string',
            'dept_code'           => 'required|string',
            'subject_code'        => 'required|string',
            'subject_category_id' => 'required|integer',
            'mark_type'           => 'required|integer',
            'status'              => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed.',
                'data'    => $validator->errors(),
            ], 422);
        }

        $semesterId        = (int) $request->input('semester_id');
        $examYear          = (int) $request->input('exam_year');
        $instCode          = $request->input('inst_code');
        $deptCode          = $request->input('dept_code');
        $subjectCode       = $request->input('subject_code');
        $subjectCategoryId = (int) $request->input('subject_category_id');
        $markType          = (int) $request->input('mark_type');
        $status            = (int) $request->input('status');

        Log::channel('daily')->info('[getExaminerWiseSummary] INPUT', [
            'semester_id'         => $semesterId,
            'exam_year'           => $examYear,
            'inst_code'           => $instCode,
            'dept_code'           => $deptCode,
            'subject_code'        => $subjectCode,
            'subject_category_id' => $subjectCategoryId,
            'mark_type'           => $markType,
            'status'              => $status,
            'ip'                  => $request->ip(),
        ]);

        try {
            $result = DB::selectOne(
                'SELECT public.fn_admin_examinerwisesummary(?::integer, ?::integer, ?::varchar, ?::varchar, ?::varchar, ?::integer, ?::integer, ?::integer) AS data',
                [
                    $semesterId,
                    $examYear,
                    $instCode,
                    $deptCode,
                    $subjectCode,
                    $subjectCategoryId,
                    $markType,
                    $status,
                ]
            );

            $rawData = $result->data ?? null;

            if (is_null($rawData)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No summary found.',
                    'data'    => [],
                ], 404);
            }

            $summary = $rawData;
            if (is_string($rawData)) {
                $summary = json_decode($rawData, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::channel('daily')->error('[getExaminerWiseSummary] JSON_DECODE_ERROR', [
                        'error' => json_last_error_msg(),
                        'raw'   => $rawData,
                    ]);

                    return response()->json([
                        'version' => '1.0',
                        'status'  => 1,
                        'message' => 'Failed to parse database response.',
                        'data'    => [],
                    ], 500);
                }
            }

            $summary = is_array($summary) ? $summary : (array) $summary;

            Log::channel('daily')->info('[getExaminerWiseSummary] OUTPUT (200)', [
                'count' => count($summary),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Examiner wise summary retrieved successfully.',
                'data'    => $summary,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[getExaminerWiseSummary] EXCEPTION', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Failed to retrieve examiner wise summary: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }
}
