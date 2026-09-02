<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminSubjectController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/subjects",
     *     tags={"Admin - Master Data"},
     *     summary="Get all subjects for a department",
     *     description="Retrieves all subjects for a specific department, semester, and subject category. Calls: fn_admin_getdeptallsubjects_v1",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"admin_user_id", "dept_code", "semester_id", "subject_category_id"},
     *             @OA\Property(property="admin_user_id", type="integer", example=1, description="Admin user ID"),
     *             @OA\Property(property="dept_code", type="string", example="PHARM", description="Department code (e.g., PHARM, BIO)"),
     *             @OA\Property(property="semester_id", type="integer", example=1, description="Semester ID"),
     *             @OA\Property(property="subject_category_id", type="integer", example=1, description="Subject category ID (1=Theory, 2=Practical, etc.)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subjects retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Successfully retrieved subjects."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="subjectId", type="integer", example=101),
     *                     @OA\Property(property="subjectCode", type="string", example="PHARM101"),
     *                     @OA\Property(property="subjectName", type="string", example="Pharmaceutical Chemistry I"),
     *                     @OA\Property(property="deptCode", type="string", example="PHARM"),
     *                     @OA\Property(property="semesterId", type="string", example="1"),
     *                     @OA\Property(property="subjectCategoryId", type="integer", example=1)
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
     * POST /api/admin/subjects
     *
     * Get all subjects for a department, semester, and subject category.
     *
     * Body: {
     *   "admin_user_id": 1,
     *   "dept_code": "PHARM",
     *   "semester_id": 1,
     *   "subject_category_id": 1
     * }
     *
     * Calls: fn_admin_getdeptallsubjects_v1(p_admin_user_id, p_dept_code, p_semester_id, p_subject_category_id)
     */
    public function getDeptAllSubjects(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_user_id'       => 'required|integer',
            'dept_code'           => 'required|string',
            'semester_id'         => 'required|integer',
            'subject_category_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed.',
                'data'    => $validator->errors(),
            ], 422);
        }

        $adminUserId       = (int) $request->input('admin_user_id');
        $deptCode          = $request->input('dept_code');
        $semesterId        = (int) $request->input('semester_id');
        $subjectCategoryId = (int) $request->input('subject_category_id');

        Log::channel('daily')->info('[getDeptAllSubjects] INPUT', [
            'admin_user_id'       => $adminUserId,
            'dept_code'           => $deptCode,
            'semester_id'         => $semesterId,
            'subject_category_id' => $subjectCategoryId,
            'ip'                  => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_admin_getdeptallsubjects_v1(?, ?, ?, ?) AS data',
                [$adminUserId, $deptCode, $semesterId, $subjectCategoryId]
            );

            if (empty($result)) {
                Log::channel('daily')->warning('[getDeptAllSubjects] No result from DB function');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No data returned from database function.',
                    'data'    => [],
                ]);
            }

            $rawData = $result[0]->data ?? null;

            if (is_null($rawData)) {
                Log::channel('daily')->warning('[getDeptAllSubjects] Null data from DB function');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No subjects found.',
                    'data'    => [],
                ]);
            }

            // If the function returns a JSON string, decode it
            if (is_string($rawData)) {
                $decoded = json_decode($rawData, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $rawData = $decoded;
                }
            }

            Log::channel('daily')->info('[getDeptAllSubjects] SUCCESS', [
                'count' => is_array($rawData) ? count($rawData) : 0,
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Subjects retrieved successfully.',
                'data'    => $rawData,
            ]);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[getDeptAllSubjects] EXCEPTION', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Failed to retrieve subjects: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/subject-details",
     *     tags={"Admin - Master Data"},
     *     summary="Get subject details by semester, exam year and category",
     *     description="Calls fn_admin_getroutinesubjects_details.",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"semester_id", "exam_year", "subject_category_id"},
     *             @OA\Property(property="semester_id", type="integer", example=1, description="Semester ID (p_semester_id)"),
     *             @OA\Property(property="exam_year", type="string", example="2026", description="Exam Year (p_examyear)"),
     *             @OA\Property(property="subject_category_id", type="integer", example=1, description="Subject category ID (p_subject_category_id)")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Subject details retrieved successfully"),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function getSubjectDetails(Request $request)
    {
        $semesterId        = $request->input('semester_id', $request->input('p_semester_id', $request->input('semester', $request->input('p_semester'))));
        $examYear          = $request->input('exam_year', $request->input('p_examyear', $request->input('p_exam_year', $request->input('examyear', $request->input('year')))));
        $subjectCategoryId = $request->input('subject_category_id', $request->input('p_subject_category_id', $request->input('category_id', $request->input('subject_category'))));

        $validator = Validator::make([
            'semester_id'         => $semesterId,
            'exam_year'           => $examYear,
            'subject_category_id' => $subjectCategoryId,
        ], [
            'semester_id'         => 'required|integer|min:1',
            'exam_year'           => 'required|string|max:20',
            'subject_category_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed: ' . $validator->errors()->first(),
                'errors'  => $validator->errors(),
                'data'    => $validator->errors(),
            ], 422);
        }

        $semesterId        = (int) $semesterId;
        $examYear          = trim((string) $examYear);
        $subjectCategoryId = (int) $subjectCategoryId;

        Log::channel('daily')->info('[getSubjectDetails] INPUT', [
            'semester_id'         => $semesterId,
            'exam_year'           => $examYear,
            'subject_category_id' => $subjectCategoryId,
            'ip'                  => $request->ip(),
        ]);

        try {
            $result = DB::selectOne(
                'SELECT public.fn_admin_getroutinesubjects_details(?::integer, ?::varchar, ?::integer) AS data',
                [
                    $semesterId,
                    $examYear,
                    $subjectCategoryId,
                ]
            );

            if (! $result || $result->data === null) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No subjects found.',
                    'data'    => [],
                ]);
            }

            $data = is_string($result->data)
                ? json_decode($result->data, true, 512, JSON_THROW_ON_ERROR)
                : (array) $result->data;

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Subject details retrieved successfully.',
                'data'    => $data,
            ]);
        } catch (\JsonException $exception) {
            Log::channel('daily')->error('[getSubjectDetails] Invalid database JSON', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Failed to parse database response.',
                'data'    => [],
            ], 500);
        } catch (\Throwable $exception) {
            Log::channel('daily')->error('[getSubjectDetails] EXCEPTION', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Failed to retrieve subject details.',
                'data'    => [],
            ], 500);
        }
    }
}
