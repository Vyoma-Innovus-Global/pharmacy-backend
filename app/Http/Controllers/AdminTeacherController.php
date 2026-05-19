<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminTeacherController extends Controller
{
    /**
     * Save Teacher Information and Assign Subjects
     *
     * This endpoint handles:
     * 1. Saving teacher basic info via fn_admin_saveteacherinfo
     * 2. Getting the returned teacher_id
     * 3. Looping through subjects array and calling fn_admin_saveteacherassignsubject_v1 for each
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * Expected Request Format:
     * {
     *   "admin_user_id": 1001,
     *   "teacherInfo": {
     *     "in_teacher_id": 0,
     *     "full_name": "Souvik Nag",
     *     "contact_no": "9876543219",
     *     "email": "souvik@example.com",
     *     "highest_qualification": "M.Tech",
     *     "aadhar_no": "123456789012",
     *     "inst_id": 1,
     *     "inst_name": "ABC Pharmacy College",
     *     "designation_id": "1",
     *     "image": "base64_image_string",
     *     "remarks": "New teacher entry"
     *   },
     *   "subjectList": [
     *     {
     *       "dept_id": 1,
     *       "semester_id": 1,
     *       "subject_category_id": 1,
     *       "subject_id": 101
     *     },
     *     {
     *       "dept_id": 1,
     *       "semester_id": 1,
     *       "subject_category_id": 2,
     *       "subject_id": 102
     *     }
     *   ]
     * }
     */
    public function saveTeacherWithSubjects(Request $request)
    {
        // Log the complete incoming request
        Log::info('=== SAVE TEACHER API - REQUEST START ===');
        Log::info('Full Request Data:', [
            'all_data' => $request->all(),
            'headers' => $request->headers->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl()
        ]);

        try {
            // Validate main request structure
            $validator = Validator::make($request->all(), [
                'admin_user_id' => 'required|integer',
                'teacherInfo' => 'required|array',
                'teacherInfo.in_teacher_id' => 'required|integer',
                'teacherInfo.full_name' => 'required|string|max:255',
                'teacherInfo.contact_no' => 'required|string|max:20',
                'teacherInfo.email' => 'required|email|max:255',
                'teacherInfo.highest_qualification' => 'required|string|max:255',
                'teacherInfo.aadhar_no' => 'nullable|string|max:12',
                'teacherInfo.inst_id' => 'required|integer',
                'teacherInfo.inst_name' => 'required|string|max:255',
                'teacherInfo.designation_id' => 'required|string|max:50',
                'teacherInfo.image' => 'nullable|string',
                'teacherInfo.remarks' => 'nullable|string',
                'subjectList' => 'required|array|min:1',
                'subjectList.*.dept_id' => 'required|integer',
                'subjectList.*.semester_id' => 'required|integer',
                'subjectList.*.subject_category_id' => 'required|integer',
                'subjectList.*.subject_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                Log::error('SAVE TEACHER API - Validation Failed:', [
                    'errors' => $validator->errors()->all(),
                    'failed_fields' => $validator->errors()->keys()
                ]);
                return response()->json([
                    'version' => '1.0',
                    'status' => 0,
                    'message' => 'Validation failed: ' . $validator->errors()->first(),
                    'data' => []
                ], 400);
            }

            $adminUserId = $request->input('admin_user_id');
            $teacherInfo = $request->input('teacherInfo');
            $subjectList = $request->input('subjectList');

            Log::info('AdminTeacherController::saveTeacherWithSubjects - Starting', [
                'admin_user_id' => $adminUserId,
                'teacher_name' => $teacherInfo['full_name'],
                'subjects_count' => count($subjectList)
            ]);

            // Step 1: Save Teacher Info
            Log::info('CALLING fn_admin_saveteacherinfo with parameters:', [
                'p_in_teacher_id' => $teacherInfo['in_teacher_id'],
                'p_full_name' => $teacherInfo['full_name'],
                'p_contact_no' => $teacherInfo['contact_no'],
                'p_email' => $teacherInfo['email'],
                'p_highest_qualification' => $teacherInfo['highest_qualification'],
                'p_aadhar_no' => $teacherInfo['aadhar_no'] ?? null,
                'p_inst_id' => $teacherInfo['inst_id'],
                'p_inst_name' => $teacherInfo['inst_name'],
                'p_designation_id' => $teacherInfo['designation_id'],
                'p_image' => strlen($teacherInfo['image'] ?? '') > 100 ? substr($teacherInfo['image'], 0, 100) . '... [truncated]' : ($teacherInfo['image'] ?? null),
                'p_remarks' => $teacherInfo['remarks'] ?? '',
                'p_admin_user_id' => $adminUserId
            ]);

            $saveTeacherQuery = "SELECT public.fn_admin_saveteacherinfo(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) as result";

            $teacherResult = DB::selectOne($saveTeacherQuery, [
                $teacherInfo['in_teacher_id'],
                $teacherInfo['full_name'],
                $teacherInfo['contact_no'],
                $teacherInfo['email'],
                $teacherInfo['highest_qualification'],
                $teacherInfo['aadhar_no'] ?? null,
                $teacherInfo['inst_id'],
                $teacherInfo['inst_name'],
                $teacherInfo['designation_id'],
                $teacherInfo['image'] ?? null,
                $teacherInfo['remarks'] ?? '',
                $adminUserId
            ]);

            Log::info('OUTPUT from fn_admin_saveteacherinfo:', [
                'raw_result' => $teacherResult,
                'result_json' => $teacherResult->result ?? null
            ]);

            if (!$teacherResult || !$teacherResult->result) {
                return response()->json([
                    'version' => '1.0',
                    'status' => 0,
                    'message' => 'Failed to save teacher information',
                    'data' => []
                ], 500);
            }

            // Parse the result JSON: {"p_errorcode": 0, "p_teacher_id": 5}
            $resultData = json_decode($teacherResult->result, true);

            Log::info('PARSED fn_admin_saveteacherinfo result:', [
                'p_errorcode' => $resultData['p_errorcode'] ?? 'not_found',
                'p_teacher_id' => $resultData['p_teacher_id'] ?? 'not_found',
                'full_result' => $resultData
            ]);

            if (!$resultData || !isset($resultData['p_errorcode']) || $resultData['p_errorcode'] != 0) {
                return response()->json([
                    'version' => '1.0',
                    'status' => 0,
                    'message' => 'Teacher save returned error code: ' . ($resultData['p_errorcode'] ?? 'unknown'),
                    'data' => $resultData ?? []
                ], 500);
            }

            $teacherId = $resultData['p_teacher_id'];

            Log::info('AdminTeacherController::saveTeacherWithSubjects - Teacher saved successfully', [
                'teacher_id' => $teacherId
            ]);

            // Step 2: Loop through subjects and assign each one
            $assignSubjectQuery = "SELECT public.fn_admin_saveteacherassignsubject_v1(?, ?, ?, ?, ?, ?, ?) as result";

            $successCount = 0;
            $failedSubjects = [];

            foreach ($subjectList as $index => $subject) {
                try {
                    Log::info("CALLING fn_admin_saveteacherassignsubject_v1 (Subject #{$index}):", [
                        'p_teacher_id' => $teacherId,
                        'p_dept_id' => $subject['dept_id'],
                        'p_semester_id' => $subject['semester_id'],
                        'p_subject_category_id' => $subject['subject_category_id'],
                        'p_subject_id' => $subject['subject_id'],
                        'p_admin_user_id' => $adminUserId,
                        'p_inst_id' => $teacherInfo['inst_id']
                    ]);

                    $subjectResult = DB::selectOne($assignSubjectQuery, [
                        $teacherId,
                        $subject['dept_id'],
                        $subject['semester_id'],
                        $subject['subject_category_id'],
                        $subject['subject_id'],
                        $adminUserId,
                        $teacherInfo['inst_id']
                    ]);

                    Log::info("OUTPUT from fn_admin_saveteacherassignsubject_v1 (Subject #{$index}):", [
                        'subject_id' => $subject['subject_id'],
                        'raw_result' => $subjectResult,
                        'result_json' => $subjectResult->result ?? null
                    ]);

                    if ($subjectResult && $subjectResult->result) {
                        $subjectResultData = json_decode($subjectResult->result, true);

                        if ($subjectResultData && isset($subjectResultData['p_errorcode']) && $subjectResultData['p_errorcode'] == 0) {
                            $successCount++;
                        } else {
                            $failedSubjects[] = [
                                'subject_id' => $subject['subject_id'],
                                'error_code' => $subjectResultData['p_errorcode'] ?? 'unknown'
                            ];
                        }
                    } else {
                        $failedSubjects[] = [
                            'subject_id' => $subject['subject_id'],
                            'error' => 'No result returned'
                        ];
                    }
                } catch (\Exception $subjectEx) {
                    Log::error('AdminTeacherController::saveTeacherWithSubjects - Subject assignment error', [
                        'subject_id' => $subject['subject_id'],
                        'error' => $subjectEx->getMessage()
                    ]);

                    $failedSubjects[] = [
                        'subject_id' => $subject['subject_id'],
                        'error' => $subjectEx->getMessage()
                    ];
                }
            }

            Log::info('AdminTeacherController::saveTeacherWithSubjects - Completed', [
                'teacher_id' => $teacherId,
                'total_subjects' => count($subjectList),
                'success_count' => $successCount,
                'failed_count' => count($failedSubjects)
            ]);

            // Determine overall success
            if ($successCount === count($subjectList)) {
                // All subjects assigned successfully
                $response = [
                    'version' => '1.0',
                    'status' => 1,
                    'message' => 'Teacher and all subjects saved successfully',
                    'data' => [
                        'teacher_id' => $teacherId,
                        'subjects_assigned' => $successCount,
                        'total_subjects' => count($subjectList)
                    ]
                ];
                Log::info('=== SAVE TEACHER API - RESPONSE (SUCCESS) ===', ['response' => $response]);
                return response()->json($response, 200);
            } elseif ($successCount > 0) {
                // Partial success
                $response = [
                    'version' => '1.0',
                    'status' => 1,
                    'message' => 'Teacher saved, but some subjects failed to assign',
                    'data' => [
                        'teacher_id' => $teacherId,
                        'subjects_assigned' => $successCount,
                        'total_subjects' => count($subjectList),
                        'failed_subjects' => $failedSubjects
                    ]
                ];
                Log::warning('=== SAVE TEACHER API - RESPONSE (PARTIAL SUCCESS) ===', ['response' => $response]);
                return response()->json($response, 200);
            } else {
                // All subjects failed
                $response = [
                    'version' => '1.0',
                    'status' => 0,
                    'message' => 'Teacher saved but all subjects failed to assign',
                    'data' => [
                        'teacher_id' => $teacherId,
                        'failed_subjects' => $failedSubjects
                    ]
                ];
                Log::error('=== SAVE TEACHER API - RESPONSE (FAILURE) ===', ['response' => $response]);
                return response()->json($response, 500);
            }

        } catch (\Exception $e) {
            Log::error('AdminTeacherController::saveTeacherWithSubjects - Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'version' => '1.0',
                'status' => 0,
                'message' => 'Server error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get Assigned Teacher Information
     *
     * Calls fn_admin_getassignedteacherinfo to retrieve teacher details with their assignments
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * Expected Request Format:
     * {
     *   "admin_user_id": 1001,
     *   "inst_code": "INST001",
     *   "department_code": "0",
     *   "subject_code": "0",
     *   "semester_id": 0
     * }
     */
    public function getAssignedTeacherInfo(Request $request)
    {
        Log::info('=== GET ASSIGNED TEACHER INFO API - REQUEST START ===');
        Log::info('Request Data:', [
            'all_data' => $request->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl()
        ]);

        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'admin_user_id' => 'required|integer',
                'inst_code' => 'required|string',
                'department_code' => 'nullable|string',
                'subject_code' => 'nullable|string',
                'semester_id' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                Log::error('GET ASSIGNED TEACHER INFO API - Validation Failed:', [
                    'errors' => $validator->errors()->all()
                ]);
                return response()->json([
                    'version' => '1.0',
                    'status' => 0,
                    'message' => 'Validation failed: ' . $validator->errors()->first(),
                    'data' => []
                ], 400);
            }

            $adminUserId = $request->input('admin_user_id');
            $instCode = $request->input('inst_code');
            $departmentCode = $request->input('department_code', '0');
            $subjectCode = $request->input('subject_code', '0');
            $semesterId = $request->input('semester_id', 0);

            Log::info('CALLING fn_admin_getassignedteacherinfo with parameters:', [
                'p_admin_user_id' => $adminUserId,
                'p_inst_code' => $instCode,
                'p_department_code' => $departmentCode,
                'p_subject_code' => $subjectCode,
                'p_semester_id' => $semesterId
            ]);

            // Call the database function
            $query = "SELECT public.fn_admin_getassignedteacherinfo(?, ?, ?, ?, ?) as result";

            $result = DB::selectOne($query, [
                $adminUserId,
                $instCode,
                $departmentCode,
                $subjectCode,
                $semesterId
            ]);

            Log::info('OUTPUT from fn_admin_getassignedteacherinfo:', [
                'raw_result' => $result,
                'result_json' => $result->result ?? null
            ]);

            if (!$result || !$result->result) {
                return response()->json([
                    'version' => '1.0',
                    'status' => 0,
                    'message' => 'No teacher data found',
                    'data' => []
                ], 404);
            }

            // Parse the JSON result (one row per teacher-subject from DB function)
            $teacherData = json_decode($result->result, true);
            $groupedTeachers = is_array($teacherData)
                ? $this->groupTeachersByTeacherId($teacherData)
                : [];

            Log::info('PARSED teacher data:', [
                'raw_row_count' => is_array($teacherData) ? count($teacherData) : 0,
                'grouped_teacher_count' => count($groupedTeachers),
                'data' => $groupedTeachers
            ]);

            $response = [
                'version' => '1.0',
                'status' => 1,
                'message' => 'Teacher information retrieved successfully',
                'data' => $groupedTeachers
            ];

            Log::info('=== GET ASSIGNED TEACHER INFO API - RESPONSE (SUCCESS) ===', [
                'response' => $response
            ]);

            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::error('AdminTeacherController::getAssignedTeacherInfo - Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'version' => '1.0',
                'status' => 0,
                'message' => 'Server error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Group flat teacher-subject rows into one record per teacher with assignments[].
     */
    private function groupTeachersByTeacherId(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $teacherId = $row['teacherId'] ?? $row['teacher_id'] ?? null;
            if ($teacherId === null) {
                continue;
            }

            if (!isset($grouped[$teacherId])) {
                $grouped[$teacherId] = [
                    'teacherId' => $teacherId,
                    'semesterId' => $row['semesterId'] ?? $row['semester_id'] ?? null,
                    'teacherFullName' => $row['teacherFullName'] ?? $row['teacher_full_name'] ?? null,
                    'teacherInstCode' => $row['teacherInstCode'] ?? $row['teacher_inst_code'] ?? null,
                    'teacherInstName' => $row['teacherInstName'] ?? $row['teacher_inst_name'] ?? null,
                    'teacherContactNo' => $row['teacherContactNo'] ?? $row['teacher_contact_no'] ?? null,
                    'teacherDesignation' => $row['teacherDesignation'] ?? $row['teacher_designation'] ?? null,
                    'teacherHighestQualification' => $row['teacherHighestQualification'] ?? $row['teacher_highest_qualification'] ?? null,
                    'teacherDeptCode' => $row['teacherDeptCode'] ?? $row['teacher_dept_code'] ?? null,
                    'teacherDeptName' => $row['teacherDeptName'] ?? $row['teacher_dept_name'] ?? null,
                    'assignments' => [],
                ];
            }

            $subjectId = $row['assignedSubjectId'] ?? $row['assigned_subject_id'] ?? null;
            if ($subjectId === null) {
                continue;
            }

            $existingIds = array_column($grouped[$teacherId]['assignments'], 'assignedSubjectId');
            if (in_array($subjectId, $existingIds, true)) {
                continue;
            }

            $grouped[$teacherId]['assignments'][] = [
                'assignedSubjectId' => $subjectId,
                'assignedSubjectCode' => $row['assignedSubjectCode'] ?? $row['assigned_subject_code'] ?? null,
                'assignedSubjectName' => $row['assignedSubjectName'] ?? $row['assigned_subject_name'] ?? null,
                'subjectCategoryId' => $row['subjectCategoryId'] ?? $row['subject_category_id'] ?? null,
                'subjectCategory' => $row['subjectCategory'] ?? $row['subject_category'] ?? null,
                'teacherDeptCode' => $row['teacherDeptCode'] ?? $row['teacher_dept_code'] ?? null,
                'teacherDeptName' => $row['teacherDeptName'] ?? $row['teacher_dept_name'] ?? null,
                'semesterId' => $row['semesterId'] ?? $row['semester_id'] ?? null,
            ];
        }

        $result = [];
        foreach ($grouped as $teacher) {
            $names = array_values(array_filter(array_map(
                fn ($a) => $a['assignedSubjectName'] ?? null,
                $teacher['assignments']
            )));
            $teacher['assignedSubjects'] = implode(', ', $names);
            $result[] = $teacher;
        }

        usort($result, fn ($a, $b) => ($b['teacherId'] ?? 0) <=> ($a['teacherId'] ?? 0));

        return $result;
    }

    /**
     * Get Evaluator Subject Allocation Summary
     *
     * Calls fn_admin_getevaluatorsubjectallocationsummary stored procedure
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * Expected Request Format:
     * {
     *   "admin_user_id": 5,
     *   "user_type_id": 9,
     *   "inst_id": 1,
     *   "evaluator_type_id": 1,
     *   "department_id": 1,
     *   "exam_year": 2025,
     *   "semester": 1
     * }
     *
     * Response Format:
     * {
     *   "version": "1.0",
     *   "status": 1,
     *   "message": "Evaluator subject allocation summary retrieved successfully",
     *   "data": [
     *     {
     *       "adminUserId": 5,
     *       "totalStudents": 0,
     *       "pendingSubject": 0,
     *       "assignedInstCode": "JCG",
     *       "assignedInstName": "JNAN CHANDRA GHOSH POLYTECHNIC",
     *       "assignedSubjectId": 1,
     *       "assignedSubjectCode": "PHCE",
     *       "assignedSubjectName": "PHARMACEUTICS",
     *       "assignedDepartmentCode": "PHARM",
     *       "assignedDepartmentName": "Pharmacy"
     *     }
     *   ]
     * }
     */
    public function getEvaluatorSubjectAllocationSummary(Request $request)
    {
        Log::info('=== GET EVALUATOR SUBJECT ALLOCATION SUMMARY API - REQUEST START ===');
        Log::info('Request Data:', $request->all());

        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'admin_user_id' => 'required|integer',
                'user_type_id' => 'required|integer',
                'inst_id' => 'required|integer',
                'evaluator_type_id' => 'required|integer',
                'department_id' => 'required|integer',
                'exam_year' => 'required|integer',
                'semester' => 'required|integer',
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

            $adminUserId = $request->input('admin_user_id');
            $userTypeId = $request->input('user_type_id');
            $instId = $request->input('inst_id');
            $evaluatorTypeId = $request->input('evaluator_type_id');
            $departmentId = $request->input('department_id');
            $examYear = $request->input('exam_year');
            $semester = $request->input('semester');

            Log::info('Calling fn_admin_getevaluatorsubjectallocationsummary with parameters:', [
                'admin_user_id' => $adminUserId,
                'user_type_id' => $userTypeId,
                'inst_id' => $instId,
                'evaluator_type_id' => $evaluatorTypeId,
                'department_id' => $departmentId,
                'exam_year' => $examYear,
                'semester' => $semester
            ]);

            // Call the stored procedure
            $sql = "SELECT public.fn_admin_getevaluatorsubjectallocationsummary(?, ?, ?, ?, ?, ?, ?) as result";

            $result = DB::selectOne($sql, [
                $adminUserId,
                $userTypeId,
                $instId,
                $evaluatorTypeId,
                $departmentId,
                $examYear,
                $semester
            ]);

            Log::info('Database function raw result:', ['result' => $result]);

            if (!$result || !isset($result->result)) {
                Log::warning('No result returned from database function');
                return response()->json([
                    'version' => '1.0',
                    'status' => 0,
                    'message' => 'No data returned from database function',
                    'data' => []
                ], 404);
            }

            // Parse JSON result
            $data = json_decode($result->result, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON parsing error:', ['error' => json_last_error_msg()]);
                return response()->json([
                    'version' => '1.0',
                    'status' => 0,
                    'message' => 'Failed to parse database response',
                    'data' => []
                ], 500);
            }

            Log::info('Parsed data:', ['data' => $data, 'count' => count($data ?? [])]);

            return response()->json([
                'version' => '1.0',
                'status' => 1,
                'message' => 'Evaluator subject allocation summary retrieved successfully',
                'data' => $data ?? []
            ], 200);

        } catch (\Exception $e) {
            Log::error('Exception in getEvaluatorSubjectAllocationSummary:', [
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

