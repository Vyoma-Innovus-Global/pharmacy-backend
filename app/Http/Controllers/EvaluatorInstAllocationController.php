<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EvaluatorInstAllocationController extends Controller
{
    /**
     * Get evaluator institute allocation summary for admin.
     *
     * Calls: public.fn_admin_getevaluatorinstallocationsummary_v1(
     *          p_admin_user_id, p_user_type_id, p_evaluator_type_id,
     *          p_exam_year, p_semester
     *        )
     *
     * POST /evaluator/inst-allocation-summary
     * Body: {
     *   "admin_user_id":      1,
     *   "user_type_id":       8,
     *   "evaluator_type_id":  1,
     *   "exam_year":          2025,
     *   "semester":           1
     * }
     */
    public function getSummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_user_id'     => 'required|integer',
            'user_type_id'      => 'required|integer',
            'evaluator_type_id' => 'required|integer',
            'exam_year'         => 'required|integer',
            'semester'          => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => true,
                'message' => $validator->errors(),
            ], 422);
        }

        $adminUserId      = (int) $request->input('admin_user_id');
        $userTypeId       = (int) $request->input('user_type_id');
        $evaluatorTypeId  = (int) $request->input('evaluator_type_id');
        $examYear         = (int) $request->input('exam_year');
        $semester         = (int) $request->input('semester');

        Log::channel('daily')->info('[getSummary] INPUT', [
            'admin_user_id'     => $adminUserId,
            'user_type_id'      => $userTypeId,
            'evaluator_type_id' => $evaluatorTypeId,
            'exam_year'         => $examYear,
            'semester'          => $semester,
            'ip'                => $request->ip(),
            'timestamp'         => now()->toDateTimeString(),
        ]);

        try {
            Log::channel('daily')->info('[getSummary] SP CALL', [
                'function' => 'fn_admin_getevaluatorinstallocationsummary_v1',
                'args'     => [
                    'p_admin_user_id'     => $adminUserId,
                    'p_user_type_id'      => $userTypeId,
                    'p_evaluator_type_id' => $evaluatorTypeId,
                    'p_exam_year'         => $examYear,
                    'p_semester'          => $semester,
                ],
            ]);

            $result = DB::select(
                'SELECT public.fn_admin_getevaluatorinstallocationsummary_v1(
                    ?::bigint, ?::bigint, ?::integer, ?::integer, ?::integer
                ) AS data',
                [$adminUserId, $userTypeId, $evaluatorTypeId, $examYear, $semester]
            );

            Log::channel('daily')->info('[getSummary] SP RAW RESPONSE', [
                'result' => $result,
            ]);

            if (empty($result)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No data found.',
                    'data'    => [
                        'internal_institutes' => [],
                        'external_institutes' => [],
                        'other_institutes'    => [],
                    ],
                ], 404);
            }

            $raw  = $result[0]->data ?? null;
            $rows = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            Log::channel('daily')->info('[getSummary] PARSED DATA', [
                'raw'    => $raw,
                'parsed' => $rows,
            ]);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'Failed to parse response from database.',
                    'data'    => null,
                ], 500);
            }

            // Group rows by evaluator type → institute → departments
            // assignedEvaluatorTypeId: 1=internal, 2=external, 3=other
            $grouped = [
                1 => [], // internal
                2 => [], // external
                3 => [], // other
            ];

            foreach ($rows as $row) {
                $typeId  = (int)($row['assignedEvaluatorTypeId'] ?? 0);
                $instId  = $row['assignedInstId']   ?? null;
                $deptId  = $row['assignedDepartmentId'] ?? null;

                if (!isset($grouped[$typeId])) {
                    $grouped[$typeId] = [];
                }

                // Build institute entry if not yet added
                if ($instId !== null && !isset($grouped[$typeId][$instId])) {
                    $grouped[$typeId][$instId] = [
                        'inst_id'            => $instId,
                        'inst_code'          => $row['assignedInstCode'] ?? null,
                        'inst_name'          => $row['assignedInstName'] ?? null,
                        'pending_department' => (int)($row['pending_department'] ?? 0),
                        'departments'        => [],
                    ];
                }

                // Append department if present
                if ($instId !== null && $deptId !== null) {
                    $grouped[$typeId][$instId]['departments'][] = [
                        'dept_id'               => $deptId,
                        'dept_code'             => $row['assignedDepartmentCode'] ?? null,
                        'total_subjects'        => (int)($row['totalSubjects']        ?? 0),
                        'total_pending_subjects'=> (int)($row['totalPendingSubjects'] ?? 0),
                    ];
                }
            }

            $response = [
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Data fetch successfully',
                'data'    => [
                    'internal_institutes' => array_values($grouped[1] ?? []),
                    'external_institutes' => array_values($grouped[2] ?? []),
                    'other_institutes'    => array_values($grouped[3] ?? []),
                ],
            ];

            Log::channel('daily')->info('[getSummary] OUTPUT (200)', $response);
            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[getSummary] EXCEPTION', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
