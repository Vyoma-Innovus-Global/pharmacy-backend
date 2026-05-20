<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminStudentMarksController extends Controller
{
    /**
     * Save Student Marks (Bulk)
     *
     * This endpoint handles bulk saving of student marks by calling
     * fn_admin_savestudentmarks stored procedure in a loop for each student.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * Expected Request Format:
     * {
     *   "marks": [
     *     {
     *       "p_marks_id": 30047,
     *       "p_internal_theory_marks": null,
     *       "p_internal_theory_attendance_marks": null,
     *       "p_internal_theory_assignment_marks": null,
     *       "p_internal_theory_viva_marks": null,
     *       "p_internal_sessional_marks": 15,
     *       "p_internal_sessional_viva_marks": 18,
     *       "p_internal_sessional_attendance_marks": 2,
     *       "p_sessional_external_marks": 35,
     *       "p_written_external_marks": null,
     *       "p_evaluator_type_id": 1,
     *       "p_admin_user_id": 2893,
     *       "p_submit_type_id": 1,
     *       "p_exam_status_code": "PR",
     *       "p_remarks": ""
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
     *     "total": 2,
     *     "success": 2,
     *     "failed": 0,
     *     "results": [
     *       {
     *         "marks_id": 30047,
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
     */
    public function saveStudentMarks(Request $request)
    {
        Log::info('=== SAVE STUDENT MARKS API - REQUEST START ===');
        Log::info('Request Data:', $request->all());

        try {
            // Validate main request structure
            $validator = Validator::make($request->all(), [
                'marks' => 'required|array|min:1',
                'marks.*.p_marks_id' => 'required|integer',
                'marks.*.p_internal_theory_marks' => 'nullable|numeric',
                'marks.*.p_internal_theory_attendance_marks' => 'nullable|numeric',
                'marks.*.p_internal_theory_assignment_marks' => 'nullable|numeric',
                'marks.*.p_internal_theory_viva_marks' => 'nullable|numeric',
                'marks.*.p_internal_sessional_marks' => 'nullable|numeric',
                'marks.*.p_internal_sessional_viva_marks' => 'nullable|numeric',
                'marks.*.p_internal_sessional_attendance_marks' => 'nullable|numeric',
                'marks.*.p_sessional_external_marks' => 'nullable|numeric',
                'marks.*.p_written_external_marks' => 'nullable|numeric',
                'marks.*.p_evaluator_type_id' => 'required|integer',
                'marks.*.p_admin_user_id' => 'required|integer',
                'marks.*.p_submit_type_id' => 'required|integer',
                'marks.*.p_exam_status_code' => 'required|string|max:10',
                'marks.*.p_remarks' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                Log::error('Validation Failed:', ['errors' => $validator->errors()->all()]);
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

            Log::info("Processing {$totalRecords} student marks records");

            // Process each marks record
            foreach ($marksData as $index => $marks) {
                $marksId = $marks['p_marks_id'];

                try {
                    Log::info("Processing marks record #{$index} for marks_id: {$marksId}");

                    // Function signature: fn_admin_savestudentmarks(
                    //   p_marks_id, p_theory_external_marks, p_theory_sessional_internal_marks,
                    //   p_practical_internal_marks, p_practical_sessional_internal_marks,
                    //   p_doc, p_exam_status_code, p_submit_type_id, p_examiner_type_id,
                    //   p_admin_user_id, p_remarks
                    // )

                    $functionParams = [
                        $marks['p_marks_id'],
                        $marks['p_written_external_marks'],
                        $marks['p_internal_theory_marks'],
                        $marks['p_sessional_external_marks'],
                        $marks['p_internal_sessional_marks'],
                        null, // p_doc
                        $marks['p_exam_status_code'],
                        $marks['p_submit_type_id'],
                        $marks['p_evaluator_type_id'],
                        $marks['p_admin_user_id'],
                        $marks['p_remarks'] ?? ''
                    ];

                    Log::info("Calling fn_admin_savestudentmarks with parameters:", [
                        'marks_id' => $functionParams[0],
                        'theory_external_marks' => $functionParams[1],
                        'theory_sessional_internal_marks' => $functionParams[2],
                        'practical_internal_marks' => $functionParams[3],
                        'practical_sessional_internal_marks' => $functionParams[4],
                        'doc' => $functionParams[5],
                        'exam_status_code' => $functionParams[6],
                        'submit_type_id' => $functionParams[7],
                        'examiner_type_id' => $functionParams[8],
                        'admin_user_id' => $functionParams[9],
                        'remarks' => $functionParams[10]
                    ]);

                    $sql = "SELECT public.fn_admin_savestudentmarks(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) as result";
                    $result = DB::selectOne($sql, $functionParams);

                    // The function returns JSON: {"p_errorcode": 0}
                    $resultJson = $result->result ?? '{"p_errorcode": 1}';
                    $resultData = json_decode($resultJson, true);
                    $resultCode = $resultData['p_errorcode'] ?? 1;

                    Log::info("Function fn_admin_savestudentmarks returned:", [
                        'marks_id' => $marksId,
                        'result_json' => $resultJson,
                        'result_code' => $resultCode,
                        'raw_result' => $result
                    ]);

                    // Determine status based on result code
                    $status = 'failed';
                    $message = '';

                    switch ($resultCode) {
                        case 0:
                            $status = 'success';
                            $message = 'Marks saved successfully';
                            $successCount++;
                            break;
                        case 1:
                            $message = 'Exception occurred';
                            $failedCount++;
                            break;
                        case 200:
                            $message = 'Invalid User';
                            $failedCount++;
                            break;
                        case 800:
                            $message = 'Invalid Marks Info';
                            $failedCount++;
                            break;
                        default:
                            $message = 'Unknown error';
                            $failedCount++;
                            break;
                    }

                    $results[] = [
                        'marks_id' => $marksId,
                        'status' => $status,
                        'result_code' => $resultCode,
                        'message' => $message
                    ];

                } catch (\Exception $e) {
                    Log::error("Error processing marks record #{$index}:", [
                        'marks_id' => $marksId,
                        'error' => $e->getMessage(),
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

            Log::info('Marks processing completed:', [
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

            return response()->json([
                'version' => '1.0',
                'status' => $overallStatus,
                'message' => $overallMessage,
                'data' => [
                    'total' => $totalRecords,
                    'success' => $successCount,
                    'failed' => $failedCount,
                    'results' => $results
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Exception in saveStudentMarks:', [
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
}
