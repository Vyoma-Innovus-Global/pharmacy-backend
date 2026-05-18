<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminSubjectController extends Controller
{
    /**
     * POST /api/admin/subjects
     *
     * Get all subjects for a department, semester, and subject category.
     *
     * Body: {
     *   "admin_user_id": 1,
     *   "dept_code": "PHARM",
     *   "semester_id": "1",
     *   "subject_category_id": 1
     * }
     *
     * Note: semester_id should be numeric string (1, 2, 3) from semester dropdown's semesterId field
     *
     * Calls: fn_admin_getdeptallsubjects_v1(p_admin_user_id, p_dept_code, p_semester_id, p_subject_category_id)
     */
    public function getDeptAllSubjects(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_user_id'       => 'required|integer',
            'dept_code'           => 'required|string',
            'semester_id'         => 'required|string',
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
        $semesterId        = $request->input('semester_id');
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
}
