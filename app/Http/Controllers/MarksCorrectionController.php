<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MarksCorrectionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/get-student-details-marks-correction",
     *     tags={"Admin - Student Marks"},
     *     summary="Get student details for marks correction",
     *     description="Fetches student details for marks correction by calling public.fn_getstudentdetailsmarkscorrection(p_admin_user_id, p_semester_id, p_examyear, p_instcode, p_subjectcode, p_markstype)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"admin_user_id", "semester_id", "exam_year", "inst_code", "subject_code", "marks_type"},
     *             @OA\Property(property="admin_user_id", type="integer", example=12, description="Admin User ID"),
     *             @OA\Property(property="semester_id", type="string", example="part-II", description="Semester / Part identifier"),
     *             @OA\Property(property="exam_year", type="string", example="2025", description="Exam Year"),
     *             @OA\Property(property="inst_code", type="string", example="JCG", description="Institute Code"),
     *             @OA\Property(property="subject_code", type="string", example="PHCE", description="Subject Code"),
     *             @OA\Property(property="marks_type", type="string", example="Internal", description="Marks Type (e.g. Internal / External)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="Data fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="instCode", type="string", example="JCG"),
     *                     @OA\Property(property="studentId", type="integer", example=11452),
     *                     @OA\Property(property="subjectCode", type="string", example="PHCE"),
     *                     @OA\Property(property="candidateName", type="string", example="MOSRAF HOSSAIN"),
     *                     @OA\Property(property="registrationNo", type="string", example="PHARM232408597"),
     *                     @OA\Property(property="studentInstCode", type="string", example="JCG")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Validation failed: The admin user id field is required."),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Internal server error: ..."),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     )
     * )
     *
     * POST /api/admin/get-student-details-marks-correction
     * POST /api/marks-correction/get-student-details
     *
     * Calls: public.fn_getstudentdetailsmarkscorrection(
     *   p_admin_user_id,
     *   p_semester_id,
     *   p_examyear,
     *   p_instcode,
     *   p_subjectcode,
     *   p_markstype
     * )
     */
    public function getStudentDetailsMarksCorrection(Request $request)
    {
        Log::channel('daily')->info('🚀 === GET STUDENT DETAILS MARKS CORRECTION API - REQUEST START ===');
        Log::channel('daily')->info('📥 REQUEST INPUT:', [
            'full_request' => $request->all(),
            'method'       => $request->method(),
            'url'          => $request->fullUrl(),
            'ip'           => $request->ip(),
        ]);

        // Normalize inputs to support both snake_case, camelCase, and p_ prefix conventions
        $inputData = [
            'admin_user_id' => $request->input('admin_user_id') ?? $request->input('p_admin_user_id') ?? $request->input('adminUserId'),
            'semester_id'   => $request->input('semester_id') ?? $request->input('p_semester_id') ?? $request->input('semesterId') ?? $request->input('semester'),
            'exam_year'     => $request->input('exam_year') ?? $request->input('examyear') ?? $request->input('p_examyear') ?? $request->input('examYear'),
            'inst_code'     => $request->input('inst_code') ?? $request->input('instcode') ?? $request->input('p_instcode') ?? $request->input('instCode'),
            'subject_code'  => $request->input('subject_code') ?? $request->input('subjectcode') ?? $request->input('p_subjectcode') ?? $request->input('subjectCode'),
            'marks_type'    => $request->input('marks_type') ?? $request->input('markstype') ?? $request->input('p_markstype') ?? $request->input('marksType'),
        ];

        $validator = Validator::make($inputData, [
            'admin_user_id' => 'required|integer',
            'semester_id'   => 'required|string|max:50',
            'exam_year'     => 'required|string|max:20',
            'inst_code'     => 'required|string|max:50',
            'subject_code'  => 'required|string|max:50',
            'marks_type'    => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            Log::channel('daily')->error('❌ VALIDATION FAILED:', [
                'errors' => $validator->errors()->all(),
                'input'  => $request->all(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Validation failed: ' . $validator->errors()->first(),
                'data'    => [],
            ], 400);
        }

        $adminUserId  = (int) $inputData['admin_user_id'];
        $semesterId   = trim((string) $inputData['semester_id']);
        $examYear     = trim((string) $inputData['exam_year']);
        $instCode     = strtoupper(trim((string) $inputData['inst_code']));
        $subjectCode  = strtoupper(trim((string) $inputData['subject_code']));
        $marksType    = trim((string) $inputData['marks_type']);

        Log::channel('daily')->info('📤 Calling fn_getstudentdetailsmarkscorrection with parameters:', [
            'p_admin_user_id' => $adminUserId,
            'p_semester_id'   => $semesterId,
            'p_examyear'      => $examYear,
            'p_instcode'      => $instCode,
            'p_subjectcode'   => $subjectCode,
            'p_markstype'     => $marksType,
        ]);

        try {
            $sql = 'SELECT public.fn_getstudentdetailsmarkscorrection(?::bigint, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar) AS data';

            $result = DB::select($sql, [
                $adminUserId,
                $semesterId,
                $examYear,
                $instCode,
                $subjectCode,
                $marksType,
            ]);

            if (empty($result) || !isset($result[0]->data)) {
                Log::channel('daily')->warning('⚠️ No result returned from fn_getstudentdetailsmarkscorrection');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No student details found for marks correction.',
                    'data'    => [],
                ], 200);
            }

            $raw = $result[0]->data;

            // DB function can return JSON string, array, or object
            $data = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('❌ JSON parsing error in fn_getstudentdetailsmarkscorrection:', [
                    'error' => json_last_error_msg(),
                    'raw'   => $raw,
                ]);

                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'Failed to parse database response.',
                    'data'    => [],
                ], 500);
            }

            Log::channel('daily')->info('✅ Student details for marks correction fetched successfully:', [
                'count' => is_array($data) ? count($data) : 1,
            ]);

            $responseData = [
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Data fetched successfully',
                'data'    => $data ?? [],
            ];

            Log::channel('daily')->info('📤 FINAL RESPONSE:', $responseData);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('🔥 EXCEPTION in getStudentDetailsMarksCorrection:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }
}
