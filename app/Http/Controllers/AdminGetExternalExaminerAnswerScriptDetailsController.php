<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminGetExternalExaminerAnswerScriptDetailsController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/get-external-examiner-answer-script-details",
     *     tags={"Admin - Teacher"},
     *     summary="Get External Examiner Answer Script Details",
     *     description="Fetches external examiner answer script details by calling public.fn_admin_getexternalexamineranswerscriptdetails(p_admin_user_id, p_teacherid, p_examyear, p_semester)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"admin_user_id", "teacher_id", "exam_year", "semester"},
     *             @OA\Property(property="admin_user_id", type="integer", example=12, description="Admin User ID (or p_admin_user_id)"),
     *             @OA\Property(property="teacher_id", type="integer", example=5503, description="Teacher ID (or p_teacherid)"),
     *             @OA\Property(property="exam_year", type="string", example="2026", description="Exam Year (or p_examyear)"),
     *             @OA\Property(property="semester", type="string", example="Part-II", description="Semester / Part Name (or p_semester, e.g. Part-I, Part-II)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="External examiner answer script details fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="External examiner answer script details fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="instCode", type="string", example="JCG"),
     *                     @OA\Property(property="instName", type="string", example="JNAN CHANDRA GHOSH POLYTECHNIC"),
     *                     @OA\Property(property="teacherName", type="string", example="Svvankar"),
     *                     @OA\Property(property="teacherEmail", type="string", example="nachiketaverma8@gmail.com"),
     *                     @OA\Property(property="totalAnsScript", type="integer", example=45),
     *                     @OA\Property(property="teacherPhoneNumber", type="string", example="9903345542")
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
     * POST /api/admin/get-external-examiner-answer-script-details
     *
     * Calls: public.fn_admin_getexternalexamineranswerscriptdetails(
     *   p_admin_user_id,
     *   p_teacherid,
     *   p_examyear,
     *   p_semester
     * )
     */
    public function getExternalExaminerAnswerScriptDetails(Request $request)
    {
        Log::channel('daily')->info('🚀 === GET EXTERNAL EXAMINER ANSWER SCRIPT DETAILS API - REQUEST START ===');
        Log::channel('daily')->info('📥 REQUEST INPUT:', [
            'full_request' => $request->all(),
            'method'       => $request->method(),
            'url'          => $request->fullUrl(),
            'ip'           => $request->ip(),
        ]);

        // Extract authenticated user_id from middleware header if available as fallback
        $authUserId = null;
        $authUserData = $request->header('auth_user_data');
        if ($authUserData) {
            $decodedAuth = is_string($authUserData) ? json_decode($authUserData, true) : (array) $authUserData;
            $authUserId = $decodedAuth['user_id'] ?? $decodedAuth['id'] ?? null;
        }

        // Normalize inputs supporting snake_case, camelCase, and SP parameter prefixes
        $rawAdminUserId = $request->input('admin_user_id') ?? $request->input('p_admin_user_id') ?? $request->input('adminUserId') ?? $request->input('user_id') ?? $request->input('p_user_id') ?? $request->input('userId') ?? $authUserId;
        $rawTeacherId   = $request->input('teacher_id') ?? $request->input('p_teacherid') ?? $request->input('p_teacher_id') ?? $request->input('teacherId') ?? $request->input('teacherid');
        $rawExamYear    = $request->input('exam_year') ?? $request->input('p_examyear') ?? $request->input('p_exam_year') ?? $request->input('examYear') ?? $request->input('year');
        $rawSemester    = $request->input('semester') ?? $request->input('p_semester') ?? $request->input('semester_id') ?? $request->input('p_semester_id') ?? $request->input('semesterId') ?? $request->input('part') ?? $request->input('part_name');

        $inputData = [
            'admin_user_id' => $rawAdminUserId !== null ? $rawAdminUserId : null,
            'teacher_id'    => $rawTeacherId !== null ? $rawTeacherId : null,
            'exam_year'     => $rawExamYear !== null ? trim((string) $rawExamYear) : null,
            'semester'      => $rawSemester !== null ? trim((string) $rawSemester) : null,
        ];

        $validator = Validator::make($inputData, [
            'admin_user_id' => 'required|integer',
            'teacher_id'    => 'required|integer',
            'exam_year'     => 'required|string|max:20',
            'semester'      => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            Log::channel('daily')->error('❌ VALIDATION FAILED in getExternalExaminerAnswerScriptDetails:', [
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

        $adminUserId = (int) $inputData['admin_user_id'];
        $teacherId   = (int) $inputData['teacher_id'];
        $examYear    = $inputData['exam_year'];

        // Normalize semester format (e.g. 1 -> Part-I, 2 -> Part-II)
        $rawSem = $inputData['semester'];
        if ($rawSem === '1' || strcasecmp($rawSem, 'Part_I') === 0 || strcasecmp($rawSem, 'Part-1') === 0) {
            $semester = 'Part-I';
        } elseif ($rawSem === '2' || strcasecmp($rawSem, 'Part_II') === 0 || strcasecmp($rawSem, 'Part-2') === 0) {
            $semester = 'Part-II';
        } else {
            $semester = $rawSem;
        }

        Log::channel('daily')->info('📤 Calling fn_admin_getexternalexamineranswerscriptdetails with parameters:', [
            'p_admin_user_id' => $adminUserId,
            'p_teacherid'     => $teacherId,
            'p_examyear'      => $examYear,
            'p_semester'      => $semester,
        ]);

        try {
            $sql = 'SELECT public.fn_admin_getexternalexamineranswerscriptdetails(?::bigint, ?::bigint, ?::varchar, ?::varchar) AS data';

            $result = DB::select($sql, [
                $adminUserId,
                $teacherId,
                $examYear,
                $semester,
            ]);

            if (empty($result) || !isset($result[0]->data)) {
                Log::channel('daily')->warning('⚠️ No result returned from fn_admin_getexternalexamineranswerscriptdetails');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No external examiner answer script details found.',
                    'data'    => [],
                ], 200);
            }

            $raw = $result[0]->data;
            $data = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('❌ JSON parsing error in fn_admin_getexternalexamineranswerscriptdetails:', [
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
                $errorMsg = $data['p_errormsg'] ?? $data['message'] ?? 'Error fetching external examiner answer script details.';
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => $errorMsg,
                    'data'    => $data,
                ], 400);
            }

            Log::channel('daily')->info('✅ External examiner answer script details fetched successfully:', [
                'count' => is_array($data) ? count($data) : 0,
            ]);

            $responseData = [
                'version' => '1.0',
                'status'  => 0,
                'message' => 'External examiner answer script details fetched successfully',
                'data'    => $data ?? [],
            ];

            Log::channel('daily')->info('📤 FINAL RESPONSE:', $responseData);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('🔥 EXCEPTION in getExternalExaminerAnswerScriptDetails:', [
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
