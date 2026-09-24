<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminMarksCorrectionStudentMarksInfoController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/get-marks-correction-student-marks-info",
     *     tags={"Admin - Student Marks"},
     *     summary="Get student marks information for marks correction",
     *     description="Fetches student marks information for marks correction by calling public.fn_admin_getmarkscorrectionstudentmarksinfo(p_admin_user_id, p_student_id, p_inst_code, p_dept_code, p_subject_code, p_exam_year, p_semester, p_examiner_type)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"admin_user_id", "inst_code", "dept_code", "subject_code", "exam_year", "semester", "examiner_type"},
     *             @OA\Property(property="admin_user_id", type="integer", example=661, description="Admin / Evaluator User ID"),
     *             @OA\Property(property="student_id", type="integer", example=0, description="Student ID (pass 0 for all students)"),
     *             @OA\Property(property="inst_code", type="string", example="JCG", description="Institute Code"),
     *             @OA\Property(property="dept_code", type="string", example="PHARM", description="Department Code"),
     *             @OA\Property(property="subject_code", type="string", example="HUAP", description="Subject Code"),
     *             @OA\Property(property="exam_year", type="string", example="2025", description="Exam Year"),
     *             @OA\Property(property="semester", type="string", example="1", description="Semester / Part identifier"),
     *             @OA\Property(property="examiner_type", type="string", example="EXTERNAL", description="Examiner Type (e.g. EXTERNAL / INTERNAL)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student marks info retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Data fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="maxMarks", type="integer", example=80),
     *                     @OA\Property(property="semester", type="integer", example=1),
     *                     @OA\Property(property="studentId", type="integer", example=3635),
     *                     @OA\Property(property="studentNo", type="string", nullable=true, example=null),
     *                     @OA\Property(property="department", type="string", example="PHARM"),
     *                     @OA\Property(property="marksStatus", type="string", nullable=true, example=null),
     *                     @OA\Property(property="studentName", type="string", nullable=true, example=null),
     *                     @OA\Property(property="studentRoll", type="string", nullable=true, example=null),
     *                     @OA\Property(property="subjectCode", type="string", example="HUAP"),
     *                     @OA\Property(property="studentRegNo", type="string", nullable=true, example=null),
     *                     @OA\Property(property="correctedMarks", type="number", nullable=true, example=null),
     *                     @OA\Property(property="correctionMarksId", type="integer", example=1)
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
     *             @OA\Property(property="message", type="string", example="Validation failed: The admin user id field is required."),
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
     * POST /api/admin/get-marks-correction-student-marks-info
     *
     * Calls: public.fn_admin_getmarkscorrectionstudentmarksinfo(
     *   p_admin_user_id,
     *   p_student_id,
     *   p_inst_code,
     *   p_dept_code,
     *   p_subject_code,
     *   p_exam_year,
     *   p_semester,
     *   p_examiner_type
     * )
     */
    public function getMarksCorrectionStudentMarksInfo(Request $request)
    {
        Log::channel('daily')->info('🚀 === GET MARKS CORRECTION STUDENT MARKS INFO API - REQUEST START ===');
        Log::channel('daily')->info('📥 REQUEST INPUT:', [
            'full_request' => $request->all(),
            'method'       => $request->method(),
            'url'          => $request->fullUrl(),
            'ip'           => $request->ip(),
        ]);

        // Normalize inputs to support snake_case, camelCase, and p_ prefix conventions
        $inputData = [
            'admin_user_id' => $request->input('admin_user_id') ?? $request->input('p_admin_user_id') ?? $request->input('p_admin_userid') ?? $request->input('adminUserId') ?? $request->input('user_id'),
            'student_id'    => $request->input('student_id') ?? $request->input('p_student_id') ?? $request->input('studentId') ?? 0,
            'inst_code'     => $request->input('inst_code') ?? $request->input('p_inst_code') ?? $request->input('instCode') ?? $request->input('student_inst_code') ?? $request->input('student_instcode') ?? $request->input('studentInstCode'),
            'dept_code'     => $request->input('dept_code') ?? $request->input('p_dept_code') ?? $request->input('deptCode') ?? $request->input('department') ?? $request->input('department_code'),
            'subject_code'  => $request->input('subject_code') ?? $request->input('p_subject_code') ?? $request->input('subjectCode') ?? $request->input('subjectcode'),
            'exam_year'     => $request->input('exam_year') ?? $request->input('p_exam_year') ?? $request->input('examYear') ?? $request->input('examyear'),
            'semester'      => $request->input('semester') ?? $request->input('p_semester') ?? $request->input('semester_id') ?? $request->input('p_semester_id') ?? $request->input('semesterId'),
            'examiner_type' => $request->input('examiner_type') ?? $request->input('p_examiner_type') ?? $request->input('examinerType') ?? $request->input('examinertype') ?? $request->input('marks_type') ?? $request->input('markstype'),
        ];

        $validator = Validator::make($inputData, [
            'admin_user_id' => 'required|integer',
            'student_id'    => 'nullable|integer',
            'inst_code'     => 'required|string|max:50',
            'dept_code'     => 'required|string|max:50',
            'subject_code'  => 'required|string|max:50',
            'exam_year'     => 'required|string|max:20',
            'semester'      => 'required|integer',
            'examiner_type' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            Log::channel('daily')->error('❌ VALIDATION FAILED in getMarksCorrectionStudentMarksInfo:', [
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

        $adminUserId  = (int) $inputData['admin_user_id'];
        $studentId    = (int) ($inputData['student_id'] ?? 0);
        $instCode     = strtoupper(trim((string) $inputData['inst_code']));
        $deptCode     = strtoupper(trim((string) $inputData['dept_code']));
        $subjectCode  = strtoupper(trim((string) $inputData['subject_code']));
        $examYear     = trim((string) $inputData['exam_year']);
        $semester     = (int) $inputData['semester'];
        $examinerType = strtoupper(trim((string) $inputData['examiner_type']));

        Log::channel('daily')->info('📤 Calling fn_admin_getmarkscorrectionstudentmarksinfo with parameters:', [
            'p_admin_user_id' => $adminUserId,
            'p_student_id'    => $studentId,
            'p_inst_code'     => $instCode,
            'p_dept_code'     => $deptCode,
            'p_subject_code'  => $subjectCode,
            'p_exam_year'     => $examYear,
            'p_semester'      => $semester,
            'p_examiner_type' => $examinerType,
        ]);

        try {
            $sql = 'SELECT public.fn_admin_getmarkscorrectionstudentmarksinfo(?::bigint, ?::bigint, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::integer, ?::varchar) AS data';

            $result = DB::select($sql, [
                $adminUserId,
                $studentId,
                $instCode,
                $deptCode,
                $subjectCode,
                $examYear,
                $semester,
                $examinerType,
            ]);

            if (empty($result) || !isset($result[0]->data)) {
                Log::channel('daily')->warning('⚠️ No result returned from fn_admin_getmarkscorrectionstudentmarksinfo');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No student marks info found for marks correction.',
                    'data'    => [],
                ], 200);
            }

            $raw = $result[0]->data;

            // DB function can return JSON string, array, or object
            $data = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('❌ JSON parsing error in fn_admin_getmarkscorrectionstudentmarksinfo:', [
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

            Log::channel('daily')->info('✅ Student marks info for marks correction fetched successfully:', [
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
            Log::channel('daily')->error('🔥 EXCEPTION in getMarksCorrectionStudentMarksInfo:', [
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
