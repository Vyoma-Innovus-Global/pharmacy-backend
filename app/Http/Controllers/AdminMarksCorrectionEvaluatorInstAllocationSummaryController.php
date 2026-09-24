<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminMarksCorrectionEvaluatorInstAllocationSummaryController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/get-marks-correction-evaluator-inst-allocation-summary",
     *     tags={"Admin - Student Marks"},
     *     summary="Get marks correction evaluator institute allocation summary",
     *     description="Fetches evaluator institute allocation summary for marks correction by calling public.fn_admin_getmarkscorrectionevaluatorinstallocationsummary(p_admin_user_id, p_user_type_id, p_evaluator_type_id, p_exam_year, p_semester)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"admin_user_id", "user_type_id", "evaluator_type_id", "exam_year", "semester"},
     *             @OA\Property(property="admin_user_id", type="integer", example=661, description="Admin User ID / Evaluator User ID"),
     *             @OA\Property(property="user_type_id", type="integer", example=9, description="User Type ID"),
     *             @OA\Property(property="evaluator_type_id", type="integer", example=2, description="Evaluator Type ID (e.g. 1=Internal, 2=External)"),
     *             @OA\Property(property="exam_year", type="string", example="2025", description="Exam Year"),
     *             @OA\Property(property="semester", type="integer", example=1, description="Semester ID / Number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Allocation summary fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Data fetch successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="internal_institutes",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="inst_id", type="integer", example=1),
     *                         @OA\Property(property="inst_code", type="string", example="JCG"),
     *                         @OA\Property(property="inst_name", type="string", example="JNAN CHANDRA GHOSH POLYTECHNIC"),
     *                         @OA\Property(property="pending_department", type="integer", example=0),
     *                         @OA\Property(
     *                             property="departments",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="dept_id", type="integer", example=1),
     *                                 @OA\Property(property="dept_code", type="string", example="PHARM"),
     *                                 @OA\Property(property="total_subjects", type="integer", example=1),
     *                                 @OA\Property(property="total_pending_subjects", type="integer", example=0)
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="external_institutes", type="array", @OA\Items()),
     *                 @OA\Property(property="other_institutes", type="array", @OA\Items())
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
     * POST /api/admin/get-marks-correction-evaluator-inst-allocation-summary
     *
     * Calls: public.fn_admin_getmarkscorrectionevaluatorinstallocationsummary(
     *   p_admin_user_id,
     *   p_user_type_id,
     *   p_evaluator_type_id,
     *   p_exam_year,
     *   p_semester
     * )
     */
    public function getMarksCorrectionEvaluatorInstAllocationSummary(Request $request)
    {
        Log::channel('daily')->info('🚀 === GET MARKS CORRECTION EVALUATOR INST ALLOCATION SUMMARY API - REQUEST START ===');
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
            'evaluator_type_id' => $request->input('evaluator_type_id') ?? $request->input('p_evaluator_type_id') ?? $request->input('evaluatorTypeId') ?? $request->input('evaluator_type'),
            'exam_year'         => $request->input('exam_year') ?? $request->input('examyear') ?? $request->input('p_exam_year') ?? $request->input('examYear'),
            'semester'          => $request->input('semester') ?? $request->input('p_semester') ?? $request->input('semester_id') ?? $request->input('p_semester_id') ?? $request->input('semesterId'),
        ];

        $validator = Validator::make($inputData, [
            'admin_user_id'     => 'required|integer',
            'user_type_id'      => 'required|integer',
            'evaluator_type_id' => 'required|integer',
            'exam_year'         => 'required|string|max:20',
            'semester'          => 'required|integer',
        ]);

        if ($validator->fails()) {
            Log::channel('daily')->error('❌ VALIDATION FAILED in getMarksCorrectionEvaluatorInstAllocationSummary:', [
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
        $evaluatorTypeId = (int) $inputData['evaluator_type_id'];
        $examYear        = trim((string) $inputData['exam_year']);
        $semester        = (int) $inputData['semester'];

        Log::channel('daily')->info('📤 Calling fn_admin_getmarkscorrectionevaluatorinstallocationsummary with parameters:', [
            'p_admin_user_id'     => $adminUserId,
            'p_user_type_id'      => $userTypeId,
            'p_evaluator_type_id' => $evaluatorTypeId,
            'p_exam_year'         => $examYear,
            'p_semester'          => $semester,
        ]);

        try {
            $sql = 'SELECT public.fn_admin_getmarkscorrectionevaluatorinstallocationsummary(?::bigint, ?::bigint, ?::integer, ?::varchar, ?::integer) AS data';

            $result = DB::select($sql, [
                $adminUserId,
                $userTypeId,
                $evaluatorTypeId,
                $examYear,
                $semester,
            ]);

            if (empty($result) || !isset($result[0]->data)) {
                Log::channel('daily')->warning('⚠️ No result returned from fn_admin_getmarkscorrectionevaluatorinstallocationsummary');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'Data fetch successfully',
                    'data'    => [
                        'internal_institutes' => [],
                        'external_institutes' => [],
                        'other_institutes'    => [],
                    ],
                ], 200);
            }

            $raw = $result[0]->data;

            // DB function returns JSON string, array, or object
            $rows = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('❌ JSON parsing error in fn_admin_getmarkscorrectionevaluatorinstallocationsummary:', [
                    'error' => json_last_error_msg(),
                    'raw'   => $raw,
                ]);

                return response()->json([
                    'version' => '1.0',
                    'status'  => 3,
                    'message' => 'Failed to parse database response.',
                    'data'    => null,
                ], 500);
            }

            // Ensure $rows is an indexed array of rows
            if (!is_array($rows)) {
                $rows = [];
            } elseif (isset($rows['assignedInstId']) || isset($rows['inst_id']) || isset($rows['deptid']) || isset($rows['dept_id'])) {
                $rows = [$rows];
            }

            // Group rows by evaluator type -> institute -> departments
            // 1 = Internal, 2 = External, 3 = Other
            $grouped = [
                1 => [], // Internal
                2 => [], // External
                3 => [], // Other
            ];

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $typeId = (int) ($row['assignedEvaluatorTypeId'] ?? $row['assigned_evaluator_type_id'] ?? $row['evaluator_type_id'] ?? 0);
                if ($typeId === 0 && !empty($row['assignedEvaluatorType'])) {
                    $typeStr = strtolower(trim((string) $row['assignedEvaluatorType']));
                    if ($typeStr === 'internal') {
                        $typeId = 1;
                    } elseif ($typeStr === 'external') {
                        $typeId = 2;
                    } else {
                        $typeId = 3;
                    }
                }

                // If still not recognized, fall back to request evaluator type or default 1
                if (!isset($grouped[$typeId])) {
                    $typeId = ($evaluatorTypeId > 0 && isset($grouped[$evaluatorTypeId])) ? $evaluatorTypeId : 1;
                }

                $instId   = $row['assignedInstId'] ?? $row['inst_id'] ?? $row['instId'] ?? null;
                $instCode = $row['assignedInstCode'] ?? $row['inst_code'] ?? $row['instCode'] ?? null;
                $instName = $row['assignedInstName'] ?? $row['inst_name'] ?? $row['instName'] ?? null;
                $deptId   = $row['deptid'] ?? $row['dept_id'] ?? $row['deptId'] ?? $row['assignedDepartmentId'] ?? null;
                $deptCode = $row['deptcode'] ?? $row['dept_code'] ?? $row['deptCode'] ?? $row['assignedDepartmentCode'] ?? null;

                if ($instId !== null) {
                    if (!isset($grouped[$typeId][$instId])) {
                        $grouped[$typeId][$instId] = [
                            'inst_id'            => $instId,
                            'inst_code'          => $instCode,
                            'inst_name'          => $instName,
                            'pending_department' => (int) ($row['pending_department'] ?? $row['pendingDepartment'] ?? 0),
                            'departments'        => [],
                        ];
                    }

                    if ($deptId !== null) {
                        $grouped[$typeId][$instId]['departments'][] = [
                            'dept_id'                => $deptId,
                            'dept_code'              => $deptCode,
                            'total_subjects'         => (int) ($row['totalSubjects'] ?? $row['total_subjects'] ?? 0),
                            'total_pending_subjects' => (int) ($row['totalPendingSubjects'] ?? $row['total_pending_subjects'] ?? 0),
                        ];
                    }
                }
            }

            $responseData = [
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Data fetch successfully',
                'data'    => [
                    'internal_institutes' => array_values($grouped[1] ?? []),
                    'external_institutes' => array_values($grouped[2] ?? []),
                    'other_institutes'    => array_values($grouped[3] ?? []),
                ],
            ];

            Log::channel('daily')->info('✅ Allocation summary structured successfully:', [
                'internal_count' => count($responseData['data']['internal_institutes']),
                'external_count' => count($responseData['data']['external_institutes']),
                'other_count'    => count($responseData['data']['other_institutes']),
            ]);

            Log::channel('daily')->info('📤 FINAL RESPONSE:', $responseData);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('🔥 EXCEPTION in getMarksCorrectionEvaluatorInstAllocationSummary:', [
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
