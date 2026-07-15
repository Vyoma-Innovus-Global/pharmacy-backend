<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminStudentMarksController extends Controller
{
    /**
     * Save Student Marks (Bulk) - Updated for v3
     *
     * This endpoint handles bulk saving of student marks by calling
     * fn_admin_savestudentmarks_v3 stored procedure in a loop for each student.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * Expected Request Format:
     * {
     *   "marks": [
     *     {
     *       "p_marks_id": 1,
     *       "p_external_marks": 75.0,
     *       "p_internal_marks": 28.0,
     *       "p_doc": "marksheet.pdf",
     *       "p_exam_status_code": "PASS",
     *       "p_submit_type_id": 5,
     *       "p_evaluator_type_id": 1,
     *       "p_admin_user_id": 5,
     *       "p_remarks": "Marks updated successfully"
     *     }
     *   ]
     * }
     *
     * Response Format:
     * {
     *   "version": "1.0",
     *   "status": 1,
     *   "message": "Student marks saved successfully",
     *   "data": {
     *     "total": 1,
     *     "success": 1,
     *     "failed": 0,
     *     "results": [
     *       {
     *         "marks_id": 1,
     *         "status": "success",
     *         "result_code": 0
     *       }
     *     ]
     *   }
     * }
     *
     * Function Return Codes:
     * 0   - Success
     * 1   - Exception
     * 200 - Invalid User
     * 800 - Invalid Marks Info
     *
     * @OA\Post(
     *     path="/api/admin/save-student-marks",
     *     tags={"Admin - Student Marks"},
     *     summary="Save student marks (bulk)",
     *     description="Save marks for one or multiple students using fn_admin_savestudentmarks_v3 stored procedure",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"marks"},
     *             @OA\Property(
     *                 property="marks",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"p_marks_id", "p_exam_status_code", "p_submit_type_id", "p_evaluator_type_id", "p_admin_user_id"},
     *                     @OA\Property(property="p_marks_id", type="integer", example=1),
     *                     @OA\Property(property="p_external_marks", type="number", format="double", example=75, nullable=true),
     *                     @OA\Property(property="p_internal_marks", type="number", format="double", example=28, nullable=true),
     *                     @OA\Property(property="p_doc", type="string", example="marksheet.pdf", nullable=true),
     *                     @OA\Property(property="p_exam_status_code", type="string", example="PASS"),
     *                     @OA\Property(property="p_submit_type_id", type="integer", example=5),
     *                     @OA\Property(property="p_evaluator_type_id", type="integer", example=1),
     *                     @OA\Property(property="p_admin_user_id", type="integer", example=5),
     *                     @OA\Property(property="p_remarks", type="string", example="Marks updated successfully", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Marks saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="All 1 marks saved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=1),
     *                 @OA\Property(property="success", type="integer", example=1),
     *                 @OA\Property(property="failed", type="integer", example=0),
     *                 @OA\Property(
     *                     property="results",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="marks_id", type="integer", example=1),
     *                         @OA\Property(property="status", type="string", example="success"),
     *                         @OA\Property(property="result_code", type="integer", example=0),
     *                         @OA\Property(property="message", type="string", example="Marks saved successfully")
     *                     )
     *                 )
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
    public function saveStudentMarks(Request $request)
    {
        Log::channel('daily')->info('🚀 === SAVE STUDENT MARKS API - REQUEST START ===');
        Log::channel('daily')->info('📥 REQUEST INPUT:', [
            'full_request' => $request->all(),
            'headers' => $request->headers->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl()
        ]);

        try {
            // Validate main request structure
            $validator = Validator::make($request->all(), [
                'marks' => 'required|array|min:1',
                'marks.*.p_marks_id' => 'required|integer',
                'marks.*.p_external_marks' => 'nullable|numeric',
                'marks.*.p_internal_marks' => 'nullable|numeric',
                'marks.*.p_doc' => 'nullable|string',
                'marks.*.p_exam_status_code' => 'required|string|max:10',
                'marks.*.p_submit_type_id' => 'required|integer',
                'marks.*.p_evaluator_type_id' => 'required|integer',
                'marks.*.p_admin_user_id' => 'required|integer',
                'marks.*.p_remarks' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                Log::channel('daily')->error('❌ VALIDATION FAILED:', [
                    'errors' => $validator->errors()->all(),
                    'input' => $request->all()
                ]);
                return response()->json([
                    'version' => '1.0',
                    'status' => 0,
                    'message' => 'Validation failed: ' . $validator->errors()->first(),
                    'data' => []
                ], 400);
            }

            $marksData = $request->input('marks');
            $totalRecords = count($marksData);
            $successCount = 0;
            $failedCount = 0;
            $results = [];

            Log::channel('daily')->info("🔵 Processing {$totalRecords} student marks records");

            // Process each marks record
            foreach ($marksData as $index => $marks) {
                $marksId = $marks['p_marks_id'];

                try {
                    Log::channel('daily')->info("🟡 Processing marks record #{$index} for marks_id: {$marksId}", [
                        'input_data' => $marks
                    ]);

                    // Function signature: fn_admin_savestudentmarks_v3(
                    //   p_marks_id, p_external_marks, p_internal_marks, p_doc,
                    //   p_exam_status_code, p_submit_type_id, p_evaluator_type_id,
                    //   p_admin_user_id, p_remarks
                    // )

                    $functionParams = [
                        $marks['p_marks_id'],
                        $marks['p_external_marks'] ?? null,
                        $marks['p_internal_marks'] ?? null,
                        $marks['p_doc'] ?? null,
                        $marks['p_exam_status_code'],
                        $marks['p_submit_type_id'],
                        $marks['p_evaluator_type_id'],
                        $marks['p_admin_user_id'],
                        $marks['p_remarks'] ?? ''
                    ];

                    Log::channel('daily')->info("📤 Calling fn_admin_savestudentmarks_v3 with parameters:", [
                        'params_array' => $functionParams,
                        'params_named' => [
                            'p_marks_id' => $functionParams[0],
                            'p_external_marks' => $functionParams[1],
                            'p_internal_marks' => $functionParams[2],
                            'p_doc' => $functionParams[3],
                            'p_exam_status_code' => $functionParams[4],
                            'p_submit_type_id' => $functionParams[5],
                            'p_evaluator_type_id' => $functionParams[6],
                            'p_admin_user_id' => $functionParams[7],
                            'p_remarks' => $functionParams[8]
                        ]
                    ]);

                    $sql = "SELECT public.fn_admin_savestudentmarks_v3(?, ?, ?, ?, ?, ?, ?, ?, ?) as result";

                    Log::channel('daily')->info("🔍 Executing SQL:", [
                        'sql' => $sql,
                        'params' => $functionParams
                    ]);

                    $result = DB::selectOne($sql, $functionParams);

                    // The function returns JSON: {"p_errorcode": 0}
                    $resultJson = $result->result ?? '{"p_errorcode": 1}';
                    $resultData = json_decode($resultJson, true);
                    $resultCode = $resultData['p_errorcode'] ?? 1;

                    Log::channel('daily')->info("📥 Function fn_admin_savestudentmarks_v3 returned:", [
                        'marks_id' => $marksId,
                        'raw_result' => $result,
                        'result_json' => $resultJson,
                        'result_data' => $resultData,
                        'result_code' => $resultCode
                    ]);

                    // Determine status based on result code
                    $status = 'failed';
                    $message = '';

                    switch ($resultCode) {
                        case 0:
                            $status = 'success';
                            $message = 'Marks saved successfully';
                            $successCount++;
                            Log::channel('daily')->info("✅ SUCCESS: Marks saved for marks_id: {$marksId}");
                            break;
                        case 1:
                            $message = 'Exception occurred';
                            $failedCount++;
                            Log::channel('daily')->error("❌ FAILED: Exception occurred for marks_id: {$marksId}");
                            break;
                        case 200:
                            $message = 'Invalid User';
                            $failedCount++;
                            Log::channel('daily')->error("❌ FAILED: Invalid User for marks_id: {$marksId}");
                            break;
                        case 800:
                            $message = 'Invalid Marks Info';
                            $failedCount++;
                            Log::channel('daily')->error("❌ FAILED: Invalid Marks Info for marks_id: {$marksId}");
                            break;
                        default:
                            $message = 'Unknown error';
                            $failedCount++;
                            Log::channel('daily')->error("❌ FAILED: Unknown error (code: {$resultCode}) for marks_id: {$marksId}");
                            break;
                    }

                    $results[] = [
                        'marks_id' => $marksId,
                        'status' => $status,
                        'result_code' => $resultCode,
                        'message' => $message
                    ];

                } catch (\Exception $e) {
                    Log::channel('daily')->error("🔴 EXCEPTION processing marks record #{$index}:", [
                        'marks_id' => $marksId,
                        'error_message' => $e->getMessage(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    $failedCount++;
                    $results[] = [
                        'marks_id' => $marksId,
                        'status' => 'failed',
                        'result_code' => 1,
                        'message' => 'Exception: ' . $e->getMessage()
                    ];
                }
            }

            Log::channel('daily')->info('🟢 Marks processing completed:', [
                'total' => $totalRecords,
                'success' => $successCount,
                'failed' => $failedCount
            ]);

            // Determine overall status
            $overallStatus = $failedCount === 0 ? 1 : ($successCount > 0 ? 2 : 0);
            $overallMessage = $failedCount === 0
                ? "All {$successCount} marks saved successfully"
                : ($successCount > 0
                    ? "Partial success: {$successCount} succeeded, {$failedCount} failed"
                    : "All {$failedCount} marks failed to save");

            $responseData = [
                'version' => '1.0',
                'status' => $overallStatus,
                'message' => $overallMessage,
                'data' => [
                    'total' => $totalRecords,
                    'success' => $successCount,
                    'failed' => $failedCount,
                    'results' => $results
                ]
            ];

            Log::channel('daily')->info('📤 FINAL RESPONSE:', $responseData);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('🔥 EXCEPTION in saveStudentMarks:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'version' => '1.0',
                'status' => 0,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/save-review-subject-marks",
     *     tags={"Admin - Student Marks"},
     *     summary="Save review subject marks in bulk",
     *     description="Loops through the marks array and calls fn_save_review_subject_marks for each item.",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"marks"},
     *             @OA\Property(
     *                 property="marks",
     *                 type="array",
     *                 @OA\Items(
     *                     required={"student_id", "reg_no", "semester", "exam_year", "subject_code", "marks", "exam_status", "marks_status", "teacher_id"},
     *                     @OA\Property(property="student_id", type="integer", format="int64", example=9432),
     *                     @OA\Property(property="reg_no", type="string", example="PHARM242500625"),
     *                     @OA\Property(property="semester", type="string", example="Part-I"),
     *                     @OA\Property(property="exam_year", type="integer", example=2025),
     *                     @OA\Property(property="subject_code", type="string", example="SOPH"),
     *                     @OA\Property(property="marks", type="integer", example=65),
     *                     @OA\Property(property="exam_status", type="string", example="PRESENT"),
     *                     @OA\Property(property="marks_status", type="string", example="SUBMITTED"),
     *                     @OA\Property(property="document", type="string", example="review_document.pdf", nullable=true),
     *                     @OA\Property(property="teacher_id", type="integer", format="int64", example=887)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Review marks processed"),
     *     @OA\Response(response=400, description="Validation failed"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function saveReviewSubjectMarks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'marks' => 'required|array|min:1',
            'marks.*.student_id' => 'required|integer|min:1',
            'marks.*.reg_no' => 'required|string|max:100',
            'marks.*.semester' => 'required|string|max:50',
            'marks.*.exam_year' => 'required|integer',
            'marks.*.subject_code' => 'required|string|max:50',
            'marks.*.marks' => 'required|integer|min:0',
            'marks.*.exam_status' => 'required|string|max:50',
            'marks.*.marks_status' => 'required|string|max:50',
            'marks.*.document' => 'nullable|string',
            'marks.*.teacher_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status' => 0,
                'message' => 'Validation failed: ' . $validator->errors()->first(),
                'data' => [],
            ], 400);
        }

        $marksItems = $validator->validated()['marks'];
        $results = [];
        $success = 0;
        $failed = 0;

        foreach ($marksItems as $index => $marksItem) {
            try {
                $result = DB::selectOne(
                    'SELECT public.fn_save_review_subject_marks(?::bigint, ?::varchar, ?::varchar, ?::integer, ?::varchar, ?::integer, ?::varchar, ?::varchar, ?::varchar, ?::bigint) AS result',
                    [
                        (int) $marksItem['student_id'],
                        trim($marksItem['reg_no']),
                        trim($marksItem['semester']),
                        (int) $marksItem['exam_year'],
                        strtoupper(trim($marksItem['subject_code'])),
                        (int) $marksItem['marks'],
                        strtoupper(trim($marksItem['exam_status'])),
                        strtoupper(trim($marksItem['marks_status'])),
                        $marksItem['document'] ?? null,
                        (int) $marksItem['teacher_id'],
                    ]
                );

                if (! $result || $result->result === null) {
                    throw new \RuntimeException('No response returned from database function');
                }

                $data = is_string($result->result)
                    ? json_decode($result->result, true, 512, JSON_THROW_ON_ERROR)
                    : (array) $result->result;
                $errorCode = (int) ($data['p_errorcode'] ?? 1);
                $isSuccessful = $errorCode === 0;

                if ($isSuccessful) {
                    $success++;
                } else {
                    $failed++;
                }

                $results[] = [
                    'index' => $index,
                    'student_id' => (int) $marksItem['student_id'],
                    'reg_no' => $marksItem['reg_no'],
                    'subject_code' => strtoupper(trim($marksItem['subject_code'])),
                    'status' => $isSuccessful ? 'success' : 'failed',
                    'result' => $data,
                ];
            } catch (\Throwable $exception) {
                $failed++;
                Log::channel('daily')->error('[Admin Student Marks] Review marks item failed', [
                    'index' => $index,
                    'student_id' => $marksItem['student_id'],
                    'message' => $exception->getMessage(),
                ]);

                $results[] = [
                    'index' => $index,
                    'student_id' => (int) $marksItem['student_id'],
                    'reg_no' => $marksItem['reg_no'],
                    'subject_code' => strtoupper(trim($marksItem['subject_code'])),
                    'status' => 'failed',
                    'message' => 'Failed to save review marks',
                ];
            }
        }

        $overallStatus = $failed === 0 ? 1 : ($success > 0 ? 2 : 0);

        return response()->json([
            'version' => '1.0',
            'status' => $overallStatus,
            'message' => $failed === 0
                ? "All {$success} review marks saved successfully"
                : ($success > 0
                    ? "Partial success: {$success} succeeded, {$failed} failed"
                    : "All {$failed} review marks failed to save"),
            'data' => [
                'total' => count($marksItems),
                'success' => $success,
                'failed' => $failed,
                'results' => $results,
            ],
        ]);
    }
}
