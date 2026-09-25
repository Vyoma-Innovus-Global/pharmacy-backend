<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CDCGetAnswerscriptIntakeController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/get-answerscript-intake",
     *     tags={"CDC - Answer Scripts"},
     *     summary="Get Answer Script Intake Details",
     *     description="Fetches answer script intake details by calling public.fn_cdc_getanswerscriptintake(p_exam_year, p_semester_id, p_inst_code, p_dept_code, p_subject_code, p_entry_user_id)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"exam_year", "semester_id", "inst_code", "dept_code", "subject_code", "entry_user_id"},
     *             @OA\Property(property="exam_year", type="string", example="2026", description="Exam Year"),
     *             @OA\Property(property="semester_id", type="string", example="Part-II", description="Semester / Part Name (e.g. Part-I, Part-II)"),
     *             @OA\Property(property="inst_code", type="string", example="JCG", description="Institute Code"),
     *             @OA\Property(property="dept_code", type="string", example="PHARM", description="Department Code"),
     *             @OA\Property(property="subject_code", type="string", example="PHCE", description="Subject Code"),
     *             @OA\Property(property="entry_user_id", type="integer", example=12, description="Entry / Admin User ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Answer script intake details fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Answer script intake details fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="deptCode", type="string", example="PHARM"),
     *                 @OA\Property(property="examYear", type="string", example="2026"),
     *                 @OA\Property(property="instCode", type="string", example="JCG"),
     *                 @OA\Property(property="instName", type="string", example=null, nullable=true),
     *                 @OA\Property(property="semesterId", type="string", example="Part-II"),
     *                 @OA\Property(property="subjectCode", type="string", example="PHCE"),
     *                 @OA\Property(property="subjectName", type="string", example=null, nullable=true),
     *                 @OA\Property(property="totalScripts", type="integer", example=40)
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
     * POST /api/admin/get-answerscript-intake
     *
     * Calls: public.fn_cdc_getanswerscriptintake(
     *   p_exam_year,
     *   p_semester_id,
     *   p_inst_code,
     *   p_dept_code,
     *   p_subject_code,
     *   p_entry_user_id
     * )
     */
    public function getAnswerscriptIntake(Request $request)
    {
        Log::channel('daily')->info('🚀 === GET ANSWER SCRIPT INTAKE API - REQUEST START ===');
        Log::channel('daily')->info('📥 REQUEST INPUT:', [
            'full_request' => $request->all(),
            'method'       => $request->method(),
            'url'          => $request->fullUrl(),
            'ip'           => $request->ip(),
        ]);

        // Attempt to extract authenticated user_id from middleware header if available
        $authUserId = null;
        $authUserData = $request->header('auth_user_data');
        if ($authUserData) {
            $decodedAuth = is_string($authUserData) ? json_decode($authUserData, true) : (array) $authUserData;
            $authUserId = $decodedAuth['user_id'] ?? null;
        }

        // Normalize input keys
        $inputData = [
            'exam_year'     => $request->input('exam_year') ?? $request->input('p_exam_year') ?? $request->input('examYear') ?? $request->input('year'),
            'semester_id'   => $request->input('semester_id') ?? $request->input('p_semester_id') ?? $request->input('semesterId') ?? $request->input('semester') ?? $request->input('p_semester'),
            'inst_code'     => $request->input('inst_code') ?? $request->input('p_inst_code') ?? $request->input('instCode') ?? $request->input('institute_code') ?? $request->input('instituteCode'),
            'dept_code'     => $request->input('dept_code') ?? $request->input('p_dept_code') ?? $request->input('deptCode') ?? $request->input('department_code') ?? $request->input('departmentCode') ?? $request->input('department'),
            'subject_code'  => $request->input('subject_code') ?? $request->input('p_subject_code') ?? $request->input('subjectCode') ?? $request->input('subject'),
            'entry_user_id' => $request->input('entry_user_id') ?? $request->input('p_entry_user_id') ?? $request->input('entryUserId') ?? $request->input('admin_user_id') ?? $request->input('adminUserId') ?? $request->input('user_id') ?? $authUserId,
        ];

        $validator = Validator::make($inputData, [
            'exam_year'     => 'required|string|max:20',
            'semester_id'   => 'required',
            'inst_code'     => 'required|string|max:50',
            'dept_code'     => 'required|string|max:50',
            'subject_code'  => 'required|string|max:50',
            'entry_user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            Log::channel('daily')->error('❌ VALIDATION FAILED in getAnswerscriptIntake:', [
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

        $examYear = trim((string) $inputData['exam_year']);

        // Normalize semester_id format
        $rawSem = trim((string) $inputData['semester_id']);
        if ($rawSem === '1' || strcasecmp($rawSem, 'Part_I') === 0 || strcasecmp($rawSem, 'Part-1') === 0) {
            $semesterId = 'Part-I';
        } elseif ($rawSem === '2' || strcasecmp($rawSem, 'Part_II') === 0 || strcasecmp($rawSem, 'Part-2') === 0) {
            $semesterId = 'Part-II';
        } else {
            $semesterId = $rawSem;
        }

        $instCode    = strtoupper(trim((string) $inputData['inst_code']));
        $deptCode    = strtoupper(trim((string) $inputData['dept_code']));
        $subjectCode = strtoupper(trim((string) $inputData['subject_code']));
        $entryUserId = (int) $inputData['entry_user_id'];

        Log::channel('daily')->info('📤 Calling fn_cdc_getanswerscriptintake with parameters:', [
            'p_exam_year'     => $examYear,
            'p_semester_id'   => $semesterId,
            'p_inst_code'     => $instCode,
            'p_dept_code'     => $deptCode,
            'p_subject_code'  => $subjectCode,
            'p_entry_user_id' => $entryUserId,
        ]);

        try {
            $sql = 'SELECT public.fn_cdc_getanswerscriptintake(?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::bigint) AS data';

            $result = DB::select($sql, [
                $examYear,
                $semesterId,
                $instCode,
                $deptCode,
                $subjectCode,
                $entryUserId,
            ]);

            if (empty($result) || !isset($result[0]->data)) {
                Log::channel('daily')->warning('⚠️ No result returned from fn_cdc_getanswerscriptintake');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No answer script intake found.',
                    'data'    => null,
                ], 200);
            }

            $raw = $result[0]->data;
            $data = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('❌ JSON parsing error in fn_cdc_getanswerscriptintake:', [
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

            // Check if DB function returned an error code
            if (is_array($data) && isset($data['p_errorcode']) && (int) $data['p_errorcode'] !== 0) {
                $errorMsg = $data['p_errormsg'] ?? $data['message'] ?? 'Error fetching answer script intake.';
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => $errorMsg,
                    'data'    => $data,
                ], 400);
            }

            Log::channel('daily')->info('✅ Answer script intake details fetched successfully:', [
                'data' => $data,
            ]);

            $responseData = [
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Answer script intake details fetched successfully',
                'data'    => $data,
            ];

            Log::channel('daily')->info('📤 FINAL RESPONSE:', $responseData);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('🔥 EXCEPTION in getAnswerscriptIntake:', [
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
