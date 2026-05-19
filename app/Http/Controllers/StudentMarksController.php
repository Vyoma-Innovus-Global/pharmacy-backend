<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StudentMarksController extends Controller
{
    /**
     * POST /api/marks/student-marks-info-v1
     *
     * Get student marks information using fn_admin_getstudentmarksinfo_v1
     *
     * Body: {
     *   "admin_user_id": 5,
     *   "student_id":    10677,       // 0 = all students, non-zero = specific student
     *   "inst_code":     "JCG",
     *   "dept_code":     "PHARM",
     *   "subject_code":  "PHCE",
     *   "exam_year":     2025,
     *   "semester":      1
     * }
     */
    public function getStudentMarksInfoV1(Request $request)
    {
        Log::info('=== GET STUDENT MARKS INFO V1 API - REQUEST START ===');
        Log::info('Request Data:', $request->all());

        $validator = Validator::make($request->all(), [
            'admin_user_id' => 'required|integer',
            'student_id'    => 'required|integer',
            'inst_code'     => 'required|string|max:20',
            'dept_code'     => 'required|string|max:20',
            'subject_code'  => 'required|string|max:50',
            'exam_year'     => 'required|integer',
            'semester'      => 'required|integer',
        ]);

        if ($validator->fails()) {
            Log::error('Validation Failed:', ['errors' => $validator->errors()->all()]);
            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Validation failed: ' . $validator->errors()->first(),
                'data'    => []
            ], 400);
        }

        $adminUserId = $request->input('admin_user_id');
        $studentId   = $request->input('student_id');
        $instCode    = strtoupper(trim($request->input('inst_code')));
        $deptCode    = strtoupper(trim($request->input('dept_code')));
        $subjectCode = strtoupper(trim($request->input('subject_code')));
        $examYear    = $request->input('exam_year');
        $semester    = $request->input('semester');

        Log::info('Calling fn_admin_getstudentmarksinfo_v1 with parameters:', [
            'admin_user_id' => $adminUserId,
            'student_id'    => $studentId,
            'inst_code'     => $instCode,
            'dept_code'     => $deptCode,
            'subject_code'  => $subjectCode,
            'exam_year'     => $examYear,
            'semester'      => $semester,
        ]);

        try {
            // Call the stored procedure
            $sql = "SELECT public.fn_admin_getstudentmarksinfo_v1(?, ?, ?, ?, ?, ?, ?) as result";

            $result = DB::selectOne($sql, [
                $adminUserId,
                $studentId,
                $instCode,
                $deptCode,
                $subjectCode,
                $examYear,
                $semester
            ]);

            Log::info('Function fn_admin_getstudentmarksinfo_v1 returned:', [
                'raw_result' => $result
            ]);

            if (!$result || !isset($result->result)) {
                Log::warning('No result returned from database function');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No data found',
                    'data'    => []
                ], 200);
            }

            // Parse JSON result
            $data = json_decode($result->result, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON parsing error:', ['error' => json_last_error_msg()]);
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'Failed to parse database response',
                    'data'    => []
                ], 500);
            }

            Log::info('Parsed data:', ['count' => count($data ?? [])]);

            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Data fetched successfully',
                'data'    => $data ?? []
            ], 200);

        } catch (\Exception $e) {
            Log::error('Exception in getStudentMarksInfoV1:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data'    => []
            ], 500);
        }
    }
}
