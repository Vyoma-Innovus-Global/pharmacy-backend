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
     *
     * @OA\Post(
     *     path="/api/marks/student-marks-info-v1",
     *     tags={"Student Marks"},
     *     summary="Get student marks information",
     *     description="Retrieve student marks information using fn_admin_getstudentmarksinfo_v2 stored procedure. Use student_id=0 for all students or specific student_id for individual student.",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"admin_user_id", "student_id", "inst_code", "dept_code", "subject_code", "exam_year", "semester"},
     *             @OA\Property(property="admin_user_id", type="integer", example=5),
     *             @OA\Property(property="student_id", type="integer", example=11452, description="0 for all students, non-zero for specific student"),
     *             @OA\Property(property="inst_code", type="string", example="JCG"),
     *             @OA\Property(property="dept_code", type="string", example="PHARM"),
     *             @OA\Property(property="subject_code", type="string", example="PHCE"),
     *             @OA\Property(property="exam_year", type="integer", example=2025),
     *             @OA\Property(property="semester", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="Data fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="marksId", type="integer", example=100480),
     *                 @OA\Property(property="semester", type="integer", example=1),
     *                 @OA\Property(property="studentId", type="integer", example=11452),
     *                 @OA\Property(property="studentNo", type="string", example=""),
     *                 @OA\Property(property="department", type="string", example="PHARM"),
     *                 @OA\Property(property="examStatus", type="string", example="INC"),
     *                 @OA\Property(property="isEditable", type="integer", example=0),
     *                 @OA\Property(property="marksStatus", type="string", example="DRAFT"),
     *                 @OA\Property(property="studentName", type="string", example="MOSRAF HOSSAIN"),
     *                 @OA\Property(property="studentRoll", type="string", nullable=true),
     *                 @OA\Property(property="subjectCode", type="string", example="PHCE"),
     *                 @OA\Property(property="studentRegNo", type="string", example="PHARM232408597"),
     *                 @OA\Property(property="externalMarks", type="number", format="double", nullable=true),
     *                 @OA\Property(property="internalMarks", type="number", format="double", nullable=true),
     *                 @OA\Property(property="studentEnrlType", type="string", example="READMISSION"),
     *                 @OA\Property(property="subjectCategory", type="string", example="THEORY"),
     *                 @OA\Property(property="externalMaxMarks", type="number", format="double", example=80.00),
     *                 @OA\Property(property="internalMaxMarks", type="number", format="double", example=20.00),
     *                 @OA\Property(property="subjectCategoryId", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Token missing or invalid"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
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
