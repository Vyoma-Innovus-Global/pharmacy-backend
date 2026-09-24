<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminMarksCorrectionEvaluatorSubjectAllocationSummaryController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/get-marks-correction-evaluator-subject-allocation-summary",
     *     tags={"Admin - Student Marks"},
     *     summary="Get marks correction evaluator subject allocation summary",
     *     description="Fetches evaluator subject allocation summary for marks correction by calling public.fn_admin_getmarkscorrection_evaluatorsubjectallocationsummary(p_admin_user_id, p_user_type_id, p_inst_code, p_evaluator_type_id, p_department_id, p_exam_year, p_semester)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"admin_user_id", "user_type_id", "inst_code", "evaluator_type_id", "department_id", "exam_year", "semester"},
     *             @OA\Property(property="admin_user_id", type="integer", example=661, description="Admin User ID / Evaluator User ID"),
     *             @OA\Property(property="user_type_id", type="integer", example=9, description="User Type ID"),
     *             @OA\Property(property="inst_code", type="string", example="JCG", description="Institute Code"),
     *             @OA\Property(property="evaluator_type_id", type="integer", example=2, description="Evaluator Type ID (e.g. 1=Internal, 2=External)"),
     *             @OA\Property(property="department_id", type="integer", example=1, description="Department ID"),
     *             @OA\Property(property="exam_year", type="string", example="2025", description="Exam Year"),
     *             @OA\Property(property="semester", type="integer", example=1, description="Semester ID / Number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subject allocation summary retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Data fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="adminUserId", type="integer", example=661),
     *                     @OA\Property(property="totalStudents", type="integer", example=7),
     *                     @OA\Property(property="pendingSubject", type="integer", example=0),
     *                     @OA\Property(property="assignedInstCode", type="string", example="JCG"),
     *                     @OA\Property(property="assignedInstName", type="string", example="JNAN CHANDRA GHOSH POLYTECHNIC"),
     *                     @OA\Property(property="assignedSubjectCode", type="string", example="HUAP"),
     *                     @OA\Property(property="assignedSubjectName", type="string", example="HUAP"),
     *                     @OA\Property(property="assignedDepartmentCode", type="string", example="PHARM"),
     *                     @OA\Property(property="assignedDepartmentName", type="string", example="PHARM")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="Validation failed: ..."),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=3),
     *             @OA\Property(property="message", type="string", example="Internal server error: ..."),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     )
     * )
     *
     * POST /api/admin/get-marks-correction-evaluator-subject-allocation-summary
     *
     * Calls: public.fn_admin_getmarkscorrection_evaluatorsubjectallocationsummary(
     *   p_admin_user_id,
     *   p_user_type_id,
     *   p_inst_code,
     *   p_evaluator_type_id,
     *   p_department_id,
     *   p_exam_year,
     *   p_semester
     * )
     */
    public function getMarksCorrectionEvaluatorSubjectAllocationSummary(Request $request)
    {
        Log::channel('daily')->info('🚀 === GET MARKS CORRECTION EVALUATOR SUBJECT ALLOCATION SUMMARY API - REQUEST START ===');
        Log::channel('daily')->info('📥 REQUEST INPUT:', [
            'full_request' => $request->all(),
            'method'       => $request->method(),
            'url'          => $request->fullUrl(),
            'ip'           => $request->ip(),
        ]);

        // Normalize inputs to support snake_case, camelCase, and p_ prefix conventions
        $inputData = [
            'admin_user_id'     => $request->input('admin_user_id') ?? $request->input('p_admin_user_id') ?? $request->input('p_admin_userid') ?? $request->input('adminUserId') ?? $request->input('user_id'),
            'user_type_id'      => $request->input('user_type_id') ?? $request->input('p_user_type_id') ?? $request->input('userTypeId') ?? $request->input('user_type'),
            'inst_code'         => $request->input('inst_code') ?? $request->input('p_inst_code') ?? $request->input('instCode') ?? $request->input('assigned_inst_code') ?? $request->input('assignedInstCode'),
            'evaluator_type_id' => $request->input('evaluator_type_id') ?? $request->input('p_evaluator_type_id') ?? $request->input('evaluatorTypeId') ?? $request->input('evaluator_type'),
            'department_id'     => $request->input('department_id') ?? $request->input('p_department_id') ?? $request->input('departmentId') ?? $request->input('dept_id') ?? $request->input('deptId'),
            'exam_year'         => $request->input('exam_year') ?? $request->input('examyear') ?? $request->input('p_exam_year') ?? $request->input('examYear'),
            'semester'          => $request->input('semester') ?? $request->input('p_semester') ?? $request->input('semester_id') ?? $request->input('p_semester_id') ?? $request->input('semesterId'),
        ];

        $validator = Validator::make($inputData, [
            'admin_user_id'     => 'required|integer',
            'user_type_id'      => 'required|integer',
            'inst_code'         => 'required|string|max:50',
            'evaluator_type_id' => 'required|integer',
            'department_id'     => 'required|integer',
            'exam_year'         => 'required|string|max:20',
            'semester'          => 'required|integer',
        ]);

        if ($validator->fails()) {
            Log::channel('daily')->error('❌ VALIDATION FAILED in getMarksCorrectionEvaluatorSubjectAllocationSummary:', [
                'errors' => $validator->errors()->all(),
                'input'  => $request->all(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed: ' . $validator->errors()->first(),
                'data'    => [],
            ], 400);
        }

        $adminUserId     = (int) $inputData['admin_user_id'];
        $userTypeId      = (int) $inputData['user_type_id'];
        $instCode        = strtoupper(trim((string) $inputData['inst_code']));
        $evaluatorTypeId = (int) $inputData['evaluator_type_id'];
        $departmentId    = (int) $inputData['department_id'];
        $examYear        = trim((string) $inputData['exam_year']);
        $semester        = (int) $inputData['semester'];

        Log::channel('daily')->info('📤 Calling fn_admin_getmarkscorrection_evaluatorsubjectallocationsummary with parameters:', [
            'p_admin_user_id'     => $adminUserId,
            'p_user_type_id'      => $userTypeId,
            'p_inst_code'         => $instCode,
            'p_evaluator_type_id' => $evaluatorTypeId,
            'p_department_id'     => $departmentId,
            'p_exam_year'         => $examYear,
            'p_semester'          => $semester,
        ]);

        try {
            $sql = 'SELECT public.fn_admin_getmarkscorrection_evaluatorsubjectallocationsummary(?::bigint, ?::bigint, ?::varchar, ?::integer, ?::integer, ?::varchar, ?::integer) AS data';

            $result = DB::select($sql, [
                $adminUserId,
                $userTypeId,
                $instCode,
                $evaluatorTypeId,
                $departmentId,
                $examYear,
                $semester,
            ]);

            if (empty($result) || !isset($result[0]->data)) {
                Log::channel('daily')->warning('⚠️ No result returned from fn_admin_getmarkscorrection_evaluatorsubjectallocationsummary');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No subject allocation summary found.',
                    'data'    => [],
                ], 200);
            }

            $raw = $result[0]->data;

            // DB function returns JSON string, array, or object
            $data = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('❌ JSON parsing error in fn_admin_getmarkscorrection_evaluatorsubjectallocationsummary:', [
                    'error' => json_last_error_msg(),
                    'raw'   => $raw,
                ]);

                return response()->json([
                    'version' => '1.0',
                    'status'  => 3,
                    'message' => 'Failed to parse database response.',
                    'data'    => [],
                ], 500);
            }

            Log::channel('daily')->info('✅ Subject allocation summary fetched successfully:', [
                'count' => is_array($data) ? count($data) : 1,
            ]);

            $responseData = [
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Data fetched successfully',
                'data'    => $data ?? [],
            ];

            Log::channel('daily')->info('📤 FINAL RESPONSE:', $responseData);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('🔥 EXCEPTION in getMarksCorrectionEvaluatorSubjectAllocationSummary:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 3,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }
}
