<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminTeacherInfoMarksCorrectionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/get-teacher-info-marks-correction",
     *     tags={"Admin - Student Marks"},
     *     summary="Get teacher info for marks correction",
     *     description="Fetches teacher info for marks correction by calling public.fn_admin_getteacherinfomarkscorrection(p_admin_userid, p_student_instcode, p_subject_code, p_exam_year, p_semester, p_examinertype)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"admin_user_id", "student_inst_code", "subject_code", "exam_year", "semester", "examiner_type"},
     *             @OA\Property(property="admin_user_id", type="integer", example=12, description="Admin User ID"),
     *             @OA\Property(property="student_inst_code", type="string", example="JCG", description="Student Institute Code"),
     *             @OA\Property(property="subject_code", type="string", example="HUAP", description="Subject Code"),
     *             @OA\Property(property="exam_year", type="string", example="2025", description="Exam Year"),
     *             @OA\Property(property="semester", type="integer", example=1, description="Semester ID / Number"),
     *             @OA\Property(property="examiner_type", type="string", example="EXTERNAL", description="Examiner Type (e.g. EXTERNAL / INTERNAL)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Teacher info retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Data fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="teacherId", type="integer", example=661),
     *                     @OA\Property(property="teacherInstCode", type="string", example="TMP"),
     *                     @OA\Property(property="teacherPhoneNumber", type="string", example="8967975026")
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
     * POST /api/admin/get-teacher-info-marks-correction
     *
     * Calls: public.fn_admin_getteacherinfomarkscorrection(
     *   p_admin_userid,
     *   p_student_instcode,
     *   p_subject_code,
     *   p_exam_year,
     *   p_semester,
     *   p_examinertype
     * )
     */
    public function getTeacherInfoMarksCorrection(Request $request)
    {
        Log::channel('daily')->info('🚀 === GET TEACHER INFO MARKS CORRECTION API - REQUEST START ===');
        Log::channel('daily')->info('📥 REQUEST INPUT:', [
            'full_request' => $request->all(),
            'method'       => $request->method(),
            'url'          => $request->fullUrl(),
            'ip'           => $request->ip(),
        ]);

        // Normalize inputs to support snake_case, camelCase, and p_ prefix conventions
        $inputData = [
            'admin_user_id'     => $request->input('admin_user_id') ?? $request->input('p_admin_userid') ?? $request->input('p_admin_user_id') ?? $request->input('adminUserId') ?? $request->input('user_id'),
            'student_inst_code' => $request->input('student_inst_code') ?? $request->input('student_instcode') ?? $request->input('p_student_instcode') ?? $request->input('studentInstCode') ?? $request->input('inst_code') ?? $request->input('instcode'),
            'subject_code'      => $request->input('subject_code') ?? $request->input('subjectcode') ?? $request->input('p_subject_code') ?? $request->input('p_subjectcode') ?? $request->input('subjectCode'),
            'exam_year'         => $request->input('exam_year') ?? $request->input('examyear') ?? $request->input('p_exam_year') ?? $request->input('p_examyear') ?? $request->input('examYear'),
            'semester'          => $request->input('semester') ?? $request->input('p_semester') ?? $request->input('semester_id') ?? $request->input('p_semester_id') ?? $request->input('semesterId'),
            'examiner_type'     => $request->input('examiner_type') ?? $request->input('examinertype') ?? $request->input('p_examinertype') ?? $request->input('examinerType') ?? $request->input('marks_type') ?? $request->input('markstype') ?? $request->input('p_markstype') ?? $request->input('marksType'),
        ];

        $validator = Validator::make($inputData, [
            'admin_user_id'     => 'required|integer',
            'student_inst_code' => 'required|string|max:50',
            'subject_code'      => 'required|string|max:50',
            'exam_year'         => 'required|string|max:20',
            'semester'          => 'required|integer',
            'examiner_type'     => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            Log::channel('daily')->error('❌ VALIDATION FAILED:', [
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
        $studentInstCode = strtoupper(trim((string) $inputData['student_inst_code']));
        $subjectCode     = strtoupper(trim((string) $inputData['subject_code']));
        $examYear        = trim((string) $inputData['exam_year']);
        $semester        = (int) $inputData['semester'];
        $examinerType    = strtoupper(trim((string) $inputData['examiner_type']));

        Log::channel('daily')->info('📤 Calling fn_admin_getteacherinfomarkscorrection with parameters:', [
            'p_admin_userid'     => $adminUserId,
            'p_student_instcode' => $studentInstCode,
            'p_subject_code'     => $subjectCode,
            'p_exam_year'        => $examYear,
            'p_semester'         => $semester,
            'p_examinertype'     => $examinerType,
        ]);

        try {
            $sql = 'SELECT public.fn_admin_getteacherinfomarkscorrection(?::bigint, ?::varchar, ?::varchar, ?::varchar, ?::integer, ?::varchar) AS data';

            $result = DB::select($sql, [
                $adminUserId,
                $studentInstCode,
                $subjectCode,
                $examYear,
                $semester,
                $examinerType,
            ]);

            if (empty($result) || !isset($result[0]->data)) {
                Log::channel('daily')->warning('⚠️ No result returned from fn_admin_getteacherinfomarkscorrection');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No teacher details found for marks correction.',
                    'data'    => [],
                ], 200);
            }

            $raw = $result[0]->data;

            // DB function can return JSON string, array, or object
            $data = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('❌ JSON parsing error in fn_admin_getteacherinfomarkscorrection:', [
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

            Log::channel('daily')->info('✅ Teacher details for marks correction fetched successfully:', [
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
            Log::channel('daily')->error('🔥 EXCEPTION in getTeacherInfoMarksCorrection:', [
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
