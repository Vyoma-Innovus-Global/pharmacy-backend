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
     * Get student marks information using fn_admin_getstudentmarksinfo_v2
     *
     * Body: {
     *   "admin_user_id": 5,
     *   "student_id":    11452,       // 0 = all students, non-zero = specific student
     *   "inst_code":     "JCG",
     *   "dept_code":     "PHARM",
     *   "subject_code":  "PHCE",
     *   "exam_year":     2025,
     *   "semester":      1
     * }
     *
     * Response Fields:
     * - marksId: Unique marks record ID
     * - semester: Semester number
     * - studentId: Student ID
     * - studentNo: Student number
     * - department: Department code
     * - examStatus: Exam status code (INC, PASS, etc.)
     * - isEditable: Whether marks can be edited (0/1)
     * - marksStatus: Status of marks (DRAFT, SUBMITTED, etc.)
     * - studentName: Student's full name
     * - studentRoll: Roll number
     * - subjectCode: Subject code
     * - studentRegNo: Registration number
     * - externalMarks: External marks obtained
     * - internalMarks: Internal marks obtained
     * - studentEnrlType: Enrollment type (READMISSION, etc.)
     * - subjectCategory: Category (THEORY/PRACTICAL)
     * - externalMaxMarks: Maximum external marks
     * - internalMaxMarks: Maximum internal marks
     * - subjectCategoryId: Category ID
     */
    public function getStudentMarksInfoV1(Request $request)
    {
        Log::channel('daily')->info('🚀 === GET STUDENT MARKS INFO V1 API - REQUEST START ===');
        Log::channel('daily')->info('📥 REQUEST INPUT:', [
            'full_request' => $request->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl()
        ]);

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
            Log::channel('daily')->error('❌ VALIDATION FAILED:', [
                'errors' => $validator->errors()->all(),
                'input' => $request->all()
            ]);
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

        Log::channel('daily')->info('📤 Calling fn_admin_getstudentmarksinfo_v2 with parameters:', [
            'p_admin_user_id' => $adminUserId,
            'p_student_id'    => $studentId,
            'p_inst_code'     => $instCode,
            'p_dept_code'     => $deptCode,
            'p_subject_code'  => $subjectCode,
            'p_exam_year'     => $examYear,
            'p_semester'      => $semester,
        ]);

        try {
            // Call the stored procedure v2
            $sql = "SELECT public.fn_admin_getstudentmarksinfo_v2(?, ?, ?, ?, ?, ?, ?) as result";

            $result = DB::selectOne($sql, [
                $adminUserId,
                $studentId,
                $instCode,
                $deptCode,
                $subjectCode,
                $examYear,
                $semester
            ]);

            Log::channel('daily')->info('📥 Function fn_admin_getstudentmarksinfo_v2 returned:', [
                'raw_result' => $result
            ]);

            if (!$result || !isset($result->result)) {
                Log::channel('daily')->warning('⚠️ No result returned from database function');
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
                Log::channel('daily')->error('❌ JSON parsing error:', [
                    'error' => json_last_error_msg(),
                    'raw_result' => $result->result
                ]);
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'Failed to parse database response',
                    'data'    => []
                ], 500);
            }

            Log::channel('daily')->info('✅ Data parsed successfully:', [
                'count' => is_array($data) ? count($data) : 1,
                'data' => $data
            ]);

            $responseData = [
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Data fetched successfully',
                'data'    => $data ?? []
            ];

            Log::channel('daily')->info('📤 FINAL RESPONSE:', $responseData);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('🔥 EXCEPTION in getStudentMarksInfoV1:', [
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
