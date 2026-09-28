<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminTeacherInfoByInstAndSubjectController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/get-teacher-info-by-inst-and-subject",
     *     tags={"Admin - Teacher"},
     *     summary="Get Teacher Info by Institute and Subject",
     *     description="Fetches teacher information for a specific institute, subject, semester, and exam year by calling public.fn_get_teacherinfobyinstandsubject(p_instcode, p_subjectcode, p_semester, p_examyear)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"inst_code", "subject_code", "semester", "exam_year"},
     *             @OA\Property(property="inst_code", type="string", example="JCG", description="Institute Code (or p_instcode)"),
     *             @OA\Property(property="subject_code", type="string", example="PHCE", description="Subject Code (or p_subjectcode)"),
     *             @OA\Property(property="semester", type="string", example="Part-I", description="Semester / Part Name (or p_semester, e.g. Part-I, Part-II)"),
     *             @OA\Property(property="exam_year", type="string", example="2025", description="Exam Year (or p_examyear)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Teacher info fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Teacher info fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="teacherId", type="integer", example=851),
     *                     @OA\Property(property="teacherName", type="string", example="SEFALI HOTA"),
     *                     @OA\Property(property="teacherInstCode", type="string", example="JCG"),
     *                     @OA\Property(property="teacherInstName", type="string", example="JNAN CHANDRA GHOSH POLYTECHNIC"),
     *                     @OA\Property(property="teacherPhoneNumber", type="string", example="9831575542")
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
     * POST /api/admin/get-teacher-info-by-inst-and-subject
     *
     * Calls: public.fn_get_teacherinfobyinstandsubject(
     *   p_instcode,
     *   p_subjectcode,
     *   p_semester,
     *   p_examyear
     * )
     */
    public function getTeacherInfoByInstAndSubject(Request $request)
    {
        Log::channel('daily')->info('🚀 === GET TEACHER INFO BY INST AND SUBJECT API - REQUEST START ===');
        Log::channel('daily')->info('📥 REQUEST INPUT:', [
            'full_request' => $request->all(),
            'method'       => $request->method(),
            'url'          => $request->fullUrl(),
            'ip'           => $request->ip(),
        ]);

        // Normalize inputs supporting snake_case, camelCase, and SP parameter prefixes
        $rawInstCode    = $request->input('inst_code') ?? $request->input('p_instcode') ?? $request->input('p_inst_code') ?? $request->input('instCode') ?? $request->input('institute_code') ?? $request->input('instituteCode');
        $rawSubjectCode = $request->input('subject_code') ?? $request->input('p_subjectcode') ?? $request->input('p_subject_code') ?? $request->input('subjectCode') ?? $request->input('subject');
        $rawSemester    = $request->input('semester') ?? $request->input('p_semester') ?? $request->input('semester_id') ?? $request->input('p_semester_id') ?? $request->input('semesterId');
        $rawExamYear    = $request->input('exam_year') ?? $request->input('p_examyear') ?? $request->input('p_exam_year') ?? $request->input('examYear') ?? $request->input('year');

        $inputData = [
            'inst_code'    => $rawInstCode !== null ? trim((string) $rawInstCode) : null,
            'subject_code' => $rawSubjectCode !== null ? trim((string) $rawSubjectCode) : null,
            'semester'     => $rawSemester !== null ? trim((string) $rawSemester) : null,
            'exam_year'    => $rawExamYear !== null ? trim((string) $rawExamYear) : null,
        ];

        $validator = Validator::make($inputData, [
            'inst_code'    => 'required|string|max:50',
            'subject_code' => 'required|string|max:50',
            'semester'     => 'required|string|max:50',
            'exam_year'    => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            Log::channel('daily')->error('❌ VALIDATION FAILED in getTeacherInfoByInstAndSubject:', [
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

        $instCode    = strtoupper($inputData['inst_code']);
        $subjectCode = strtoupper($inputData['subject_code']);

        // Normalize semester format (e.g. 1 -> Part-I, 2 -> Part-II)
        $rawSem = $inputData['semester'];
        if ($rawSem === '1' || strcasecmp($rawSem, 'Part_I') === 0 || strcasecmp($rawSem, 'Part-1') === 0) {
            $semester = 'Part-I';
        } elseif ($rawSem === '2' || strcasecmp($rawSem, 'Part_II') === 0 || strcasecmp($rawSem, 'Part-2') === 0) {
            $semester = 'Part-II';
        } else {
            $semester = $rawSem;
        }

        $examYear = $inputData['exam_year'];

        Log::channel('daily')->info('📤 Calling fn_get_teacherinfobyinstandsubject with parameters:', [
            'p_instcode'    => $instCode,
            'p_subjectcode' => $subjectCode,
            'p_semester'    => $semester,
            'p_examyear'    => $examYear,
        ]);

        try {
            $sql = 'SELECT public.fn_get_teacherinfobyinstandsubject(?::varchar, ?::varchar, ?::varchar, ?::varchar) AS data';

            $result = DB::select($sql, [
                $instCode,
                $subjectCode,
                $semester,
                $examYear,
            ]);

            if (empty($result) || !isset($result[0]->data)) {
                Log::channel('daily')->warning('⚠️ No result returned from fn_get_teacherinfobyinstandsubject');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No teacher info found.',
                    'data'    => [],
                ], 200);
            }

            $raw = $result[0]->data;
            $data = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('❌ JSON parsing error in fn_get_teacherinfobyinstandsubject:', [
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

            // Check if DB function returned an error code object instead of array
            if (is_array($data) && isset($data['p_errorcode']) && (int) $data['p_errorcode'] !== 0) {
                $errorMsg = $data['p_errormsg'] ?? $data['message'] ?? 'Error fetching teacher info.';
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => $errorMsg,
                    'data'    => $data,
                ], 400);
            }

            Log::channel('daily')->info('✅ Teacher info fetched successfully:', [
                'count' => is_array($data) ? count($data) : 0,
            ]);

            $responseData = [
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Teacher info fetched successfully',
                'data'    => $data ?? [],
            ];

            Log::channel('daily')->info('📤 FINAL RESPONSE:', $responseData);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('🔥 EXCEPTION in getTeacherInfoByInstAndSubject:', [
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
