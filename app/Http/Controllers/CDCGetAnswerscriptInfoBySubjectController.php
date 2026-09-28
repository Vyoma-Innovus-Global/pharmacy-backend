<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CDCGetAnswerscriptInfoBySubjectController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/get-answerscript-info-by-subject",
     *     tags={"CDC - Answer Scripts"},
     *     summary="Get Answer Script Info by Subject",
     *     description="Fetches answer script allocation and intake details for a specific subject by calling public.fn_get_answerscriptinfobysubject(p_examyear, p_semester, p_subjectcode)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"exam_year", "semester", "subject_code"},
     *             @OA\Property(property="exam_year", type="string", example="2026", description="Exam Year (or p_examyear)"),
     *             @OA\Property(property="semester", type="string", example="Part-II", description="Semester / Part Name (e.g. Part-I, Part-II)"),
     *             @OA\Property(property="subject_code", type="string", example="PHCO", description="Subject Code (or p_subjectcode)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Answer script info fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Answer script info fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="instId", type="integer", example=1),
     *                     @OA\Property(property="deptCode", type="string", example="PHARM"),
     *                     @OA\Property(property="instCode", type="string", example="JCG"),
     *                     @OA\Property(property="instName", type="string", example="JNAN CHANDRA GHOSH POLYTECHNIC"),
     *                     @OA\Property(property="subjectId", type="integer", example=1),
     *                     @OA\Property(property="subjectCode", type="string", example="PHCO"),
     *                     @OA\Property(property="subjectName", type="string", example="PHARMACOLOGY"),
     *                     @OA\Property(property="assignTeacherId", type="integer", example=null, nullable=true),
     *                     @OA\Property(property="assignTeacherName", type="string", example=null, nullable=true),
     *                     @OA\Property(property="totalAnswerScript", type="integer", example=45),
     *                     @OA\Property(property="assignTeacherPhoneNumber", type="string", example=null, nullable=true)
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
     * POST /api/admin/get-answerscript-info-by-subject
     *
     * Calls: public.fn_get_answerscriptinfobysubject(
     *   p_examyear,
     *   p_semester,
     *   p_subjectcode
     * )
     */
    public function getAnswerscriptInfoBySubject(Request $request)
    {
        Log::channel('daily')->info('🚀 === GET ANSWER SCRIPT INFO BY SUBJECT API - REQUEST START ===');
        Log::channel('daily')->info('📥 REQUEST INPUT:', [
            'full_request' => $request->all(),
            'method'       => $request->method(),
            'url'          => $request->fullUrl(),
            'ip'           => $request->ip(),
        ]);

        // Normalize input keys (supporting snake_case, camelCase, and sp parameter names)
        $rawExamYear    = $request->input('exam_year') ?? $request->input('p_examyear') ?? $request->input('p_exam_year') ?? $request->input('examYear') ?? $request->input('year');
        $rawSemester    = $request->input('semester') ?? $request->input('p_semester') ?? $request->input('semester_id') ?? $request->input('p_semester_id') ?? $request->input('semesterId');
        $rawSubjectCode = $request->input('subject_code') ?? $request->input('p_subjectcode') ?? $request->input('p_subject_code') ?? $request->input('subjectCode') ?? $request->input('subject');

        $inputData = [
            'exam_year'    => $rawExamYear !== null ? trim((string) $rawExamYear) : null,
            'semester'     => $rawSemester !== null ? trim((string) $rawSemester) : null,
            'subject_code' => $rawSubjectCode !== null ? trim((string) $rawSubjectCode) : null,
        ];

        $validator = Validator::make($inputData, [
            'exam_year'    => 'required|string|max:20',
            'semester'     => 'required|string|max:50',
            'subject_code' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            Log::channel('daily')->error('❌ VALIDATION FAILED in getAnswerscriptInfoBySubject:', [
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

        $examYear = $inputData['exam_year'];

        // Normalize semester format (e.g., 1 -> Part-I, 2 -> Part-II)
        $rawSem = $inputData['semester'];
        if ($rawSem === '1' || strcasecmp($rawSem, 'Part_I') === 0 || strcasecmp($rawSem, 'Part-1') === 0) {
            $semester = 'Part-I';
        } elseif ($rawSem === '2' || strcasecmp($rawSem, 'Part_II') === 0 || strcasecmp($rawSem, 'Part-2') === 0) {
            $semester = 'Part-II';
        } else {
            $semester = $rawSem;
        }

        $subjectCode = strtoupper($inputData['subject_code']);

        Log::channel('daily')->info('📤 Calling fn_get_answerscriptinfobysubject with parameters:', [
            'p_examyear'    => $examYear,
            'p_semester'    => $semester,
            'p_subjectcode' => $subjectCode,
        ]);

        try {
            $sql = 'SELECT public.fn_get_answerscriptinfobysubject(?::varchar, ?::varchar, ?::varchar) AS data';

            $result = DB::select($sql, [
                $examYear,
                $semester,
                $subjectCode,
            ]);

            if (empty($result) || !isset($result[0]->data)) {
                Log::channel('daily')->warning('⚠️ No result returned from fn_get_answerscriptinfobysubject');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No answer script info found.',
                    'data'    => [],
                ], 200);
            }

            $raw = $result[0]->data;
            $data = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('❌ JSON parsing error in fn_get_answerscriptinfobysubject:', [
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
                $errorMsg = $data['p_errormsg'] ?? $data['message'] ?? 'Error fetching answer script info.';
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => $errorMsg,
                    'data'    => $data,
                ], 400);
            }

            Log::channel('daily')->info('✅ Answer script info fetched successfully:', [
                'count' => is_array($data) ? count($data) : 0,
            ]);

            $responseData = [
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Answer script info fetched successfully',
                'data'    => $data ?? [],
            ];

            Log::channel('daily')->info('📤 FINAL RESPONSE:', $responseData);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('🔥 EXCEPTION in getAnswerscriptInfoBySubject:', [
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
