<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminSaveTeacherAssignMarksCorrectionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/save-teacher-assign-marks-correction",
     *     tags={"Admin - Student Marks"},
     *     summary="Save teacher assignment for marks correction (Single or Multiple Students)",
     *     description="Assigns teacher to student(s) for marks correction by looping public.fn_admin_saveteacherassignmarkscorrection(p_teacher_id, p_student_id, p_student_instcode, p_semester_id, p_subject_code, p_exam_year, p_examiner_type, p_adminuser_id)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"teacher_id", "student_id", "student_inst_code", "semester_id", "subject_code", "exam_year", "examiner_type", "admin_user_id"},
     *             @OA\Property(property="teacher_id", type="integer", example=661, description="Teacher ID"),
     *             @OA\Property(
     *                 property="student_id",
     *                 description="Student ID (single integer or array of student IDs)",
     *                 oneOf={
     *                     @OA\Schema(type="array", @OA\Items(type="integer", example=3635)),
     *                     @OA\Schema(type="integer", example=3635)
     *                 }
     *             ),
     *             @OA\Property(property="student_inst_code", type="string", example="JCG", description="Student Institute Code"),
     *             @OA\Property(property="semester_id", type="integer", example=1, description="Semester ID / Number"),
     *             @OA\Property(property="subject_code", type="string", example="HUAP", description="Subject Code"),
     *             @OA\Property(property="exam_year", type="string", example="2025", description="Exam Year"),
     *             @OA\Property(property="examiner_type", type="string", example="EXTERNAL", description="Examiner Type (EXTERNAL / INTERNAL)"),
     *             @OA\Property(property="admin_user_id", type="integer", example=1, description="Admin User ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Teacher assignment saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Teacher assigned successfully for 2 student(s)."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="p_errorcode", type="integer", example=0))
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
     * POST /api/admin/save-teacher-assign-marks-correction
     *
     * Loops & Calls: public.fn_admin_saveteacherassignmarkscorrection(
     *   p_teacher_id,
     *   p_student_id,
     *   p_student_instcode,
     *   p_semester_id,
     *   p_subject_code,
     *   p_exam_year,
     *   p_examiner_type,
     *   p_adminuser_id
     * )
     */
    public function saveTeacherAssignMarksCorrection(Request $request)
    {
        Log::channel('daily')->info('🚀 === SAVE TEACHER ASSIGN MARKS CORRECTION API - REQUEST START ===');
        Log::channel('daily')->info('📥 REQUEST INPUT:', [
            'full_request' => $request->all(),
            'method'       => $request->method(),
            'url'          => $request->fullUrl(),
            'ip'           => $request->ip(),
        ]);

        $rawBody = $request->all();

        // Top level shared parameters
        $topLevelTeacherId       = $request->input('teacher_id') ?? $request->input('p_teacher_id') ?? $request->input('teacherId');
        $topLevelStudentInstCode = $request->input('student_inst_code') ?? $request->input('student_instcode') ?? $request->input('p_student_instcode') ?? $request->input('studentInstCode') ?? $request->input('inst_code') ?? $request->input('instcode') ?? $request->input('p_instcode');
        $topLevelSemesterId      = $request->input('semester_id') ?? $request->input('p_semester_id') ?? $request->input('semesterId') ?? $request->input('semester') ?? $request->input('p_semester');
        $topLevelSubjectCode     = $request->input('subject_code') ?? $request->input('p_subject_code') ?? $request->input('subjectCode') ?? $request->input('subjectcode');
        $topLevelExamYear        = $request->input('exam_year') ?? $request->input('p_exam_year') ?? $request->input('examYear') ?? $request->input('examyear');
        $topLevelExaminerType    = $request->input('examiner_type') ?? $request->input('p_examiner_type') ?? $request->input('examinerType') ?? $request->input('examinertype') ?? $request->input('marks_type') ?? $request->input('markstype');
        $topLevelAdminUserId     = $request->input('admin_user_id') ?? $request->input('p_adminuser_id') ?? $request->input('p_admin_userid') ?? $request->input('p_admin_user_id') ?? $request->input('adminUserId') ?? $request->input('entry_user_id') ?? $request->input('user_id');

        $studentInput = $request->input('student_id') ?? $request->input('student_ids') ?? $request->input('p_student_id') ?? $request->input('studentId');

        $items = [];

        // 1. Check if student_id is passed as an array (e.g. "student_id": [3635, 3636])
        if (is_array($studentInput)) {
            foreach ($studentInput as $sId) {
                $actualStudentId = is_array($sId) ? ($sId['student_id'] ?? $sId['id'] ?? null) : $sId;
                if ($actualStudentId !== null) {
                    $items[] = [
                        'teacher_id'        => is_array($sId) ? ($sId['teacher_id'] ?? $topLevelTeacherId) : $topLevelTeacherId,
                        'student_id'        => $actualStudentId,
                        'student_inst_code' => is_array($sId) ? ($sId['student_inst_code'] ?? $sId['student_instcode'] ?? $sId['inst_code'] ?? $topLevelStudentInstCode) : $topLevelStudentInstCode,
                        'semester_id'       => is_array($sId) ? ($sId['semester_id'] ?? $topLevelSemesterId) : $topLevelSemesterId,
                        'subject_code'      => is_array($sId) ? ($sId['subject_code'] ?? $topLevelSubjectCode) : $topLevelSubjectCode,
                        'exam_year'         => is_array($sId) ? ($sId['exam_year'] ?? $topLevelExamYear) : $topLevelExamYear,
                        'examiner_type'     => is_array($sId) ? ($sId['examiner_type'] ?? $topLevelExaminerType) : $topLevelExaminerType,
                        'admin_user_id'     => is_array($sId) ? ($sId['admin_user_id'] ?? $topLevelAdminUserId) : $topLevelAdminUserId,
                    ];
                }
            }
        }
        // 2. Check if assignments array is passed (e.g. "assignments": [ {...}, {...} ])
        elseif ($request->has('assignments') && is_array($request->input('assignments'))) {
            foreach ($request->input('assignments') as $assignment) {
                $assignedStudentId = $assignment['student_id'] ?? $assignment['p_student_id'] ?? $assignment['studentId'] ?? $assignment['id'] ?? null;
                $assignedInstCode  = $assignment['student_inst_code'] ?? $assignment['student_instcode'] ?? $assignment['p_student_instcode'] ?? $assignment['studentInstCode'] ?? $assignment['inst_code'] ?? $assignment['instcode'] ?? $topLevelStudentInstCode;

                if (is_array($assignedStudentId)) {
                    foreach ($assignedStudentId as $innerSId) {
                        $items[] = [
                            'teacher_id'        => $assignment['teacher_id'] ?? $assignment['p_teacher_id'] ?? $assignment['teacherId'] ?? $topLevelTeacherId,
                            'student_id'        => is_array($innerSId) ? ($innerSId['student_id'] ?? $innerSId['id'] ?? null) : $innerSId,
                            'student_inst_code' => is_array($innerSId) ? ($innerSId['student_inst_code'] ?? $innerSId['student_instcode'] ?? $innerSId['inst_code'] ?? $assignedInstCode) : $assignedInstCode,
                            'semester_id'       => $assignment['semester_id'] ?? $assignment['p_semester_id'] ?? $assignment['semesterId'] ?? $assignment['semester'] ?? $topLevelSemesterId,
                            'subject_code'      => $assignment['subject_code'] ?? $assignment['p_subject_code'] ?? $assignment['subjectCode'] ?? $assignment['subjectcode'] ?? $topLevelSubjectCode,
                            'exam_year'         => $assignment['exam_year'] ?? $assignment['p_exam_year'] ?? $assignment['examYear'] ?? $assignment['examyear'] ?? $topLevelExamYear,
                            'examiner_type'     => $assignment['examiner_type'] ?? $assignment['p_examiner_type'] ?? $assignment['examinerType'] ?? $assignment['examinertype'] ?? $assignment['marks_type'] ?? $topLevelExaminerType,
                            'admin_user_id'     => $assignment['admin_user_id'] ?? $assignment['p_adminuser_id'] ?? $assignment['p_admin_userid'] ?? $assignment['p_admin_user_id'] ?? $assignment['adminUserId'] ?? $assignment['entry_user_id'] ?? $topLevelAdminUserId,
                        ];
                    }
                } else {
                    $items[] = [
                        'teacher_id'        => $assignment['teacher_id'] ?? $assignment['p_teacher_id'] ?? $assignment['teacherId'] ?? $topLevelTeacherId,
                        'student_id'        => $assignedStudentId,
                        'student_inst_code' => $assignedInstCode,
                        'semester_id'       => $assignment['semester_id'] ?? $assignment['p_semester_id'] ?? $assignment['semesterId'] ?? $assignment['semester'] ?? $topLevelSemesterId,
                        'subject_code'      => $assignment['subject_code'] ?? $assignment['p_subject_code'] ?? $assignment['subjectCode'] ?? $assignment['subjectcode'] ?? $topLevelSubjectCode,
                        'exam_year'         => $assignment['exam_year'] ?? $assignment['p_exam_year'] ?? $assignment['examYear'] ?? $assignment['examyear'] ?? $topLevelExamYear,
                        'examiner_type'     => $assignment['examiner_type'] ?? $assignment['p_examiner_type'] ?? $assignment['examinerType'] ?? $assignment['examinertype'] ?? $assignment['marks_type'] ?? $topLevelExaminerType,
                        'admin_user_id'     => $assignment['admin_user_id'] ?? $assignment['p_adminuser_id'] ?? $assignment['p_admin_userid'] ?? $assignment['p_admin_user_id'] ?? $assignment['adminUserId'] ?? $assignment['entry_user_id'] ?? $topLevelAdminUserId,
                    ];
                }
            }
        }
        // 3. Check if root body is a numeric array of objects [ {...}, {...} ]
        elseif (is_array($rawBody) && isset($rawBody[0]) && is_array($rawBody[0])) {
            foreach ($rawBody as $assignment) {
                $items[] = [
                    'teacher_id'        => $assignment['teacher_id'] ?? $assignment['p_teacher_id'] ?? $assignment['teacherId'] ?? $topLevelTeacherId,
                    'student_id'        => $assignment['student_id'] ?? $assignment['p_student_id'] ?? $assignment['studentId'] ?? $assignment['id'] ?? null,
                    'student_inst_code' => $assignment['student_inst_code'] ?? $assignment['student_instcode'] ?? $assignment['p_student_instcode'] ?? $assignment['inst_code'] ?? $topLevelStudentInstCode,
                    'semester_id'       => $assignment['semester_id'] ?? $assignment['p_semester_id'] ?? $assignment['semesterId'] ?? $assignment['semester'] ?? $topLevelSemesterId,
                    'subject_code'      => $assignment['subject_code'] ?? $assignment['p_subject_code'] ?? $assignment['subjectCode'] ?? $topLevelSubjectCode,
                    'exam_year'         => $assignment['exam_year'] ?? $assignment['p_exam_year'] ?? $assignment['examYear'] ?? $topLevelExamYear,
                    'examiner_type'     => $assignment['examiner_type'] ?? $assignment['p_examiner_type'] ?? $assignment['examinerType'] ?? $assignment['marks_type'] ?? $topLevelExaminerType,
                    'admin_user_id'     => $assignment['admin_user_id'] ?? $assignment['p_adminuser_id'] ?? $assignment['p_admin_userid'] ?? $assignment['adminUserId'] ?? $assignment['entry_user_id'] ?? $topLevelAdminUserId,
                ];
            }
        }
        // 4. Single student_id (integer or comma-separated string)
        elseif ($studentInput !== null) {
            if (is_string($studentInput) && str_contains($studentInput, ',')) {
                $ids = explode(',', $studentInput);
                foreach ($ids as $sId) {
                    $trimmedId = trim($sId);
                    if ($trimmedId !== '') {
                        $items[] = [
                            'teacher_id'        => $topLevelTeacherId,
                            'student_id'        => (int) $trimmedId,
                            'student_inst_code' => $topLevelStudentInstCode,
                            'semester_id'       => $topLevelSemesterId,
                            'subject_code'      => $topLevelSubjectCode,
                            'exam_year'         => $topLevelExamYear,
                            'examiner_type'     => $topLevelExaminerType,
                            'admin_user_id'     => $topLevelAdminUserId,
                        ];
                    }
                }
            } else {
                $items[] = [
                    'teacher_id'        => $topLevelTeacherId,
                    'student_id'        => $studentInput,
                    'student_inst_code' => $topLevelStudentInstCode,
                    'semester_id'       => $topLevelSemesterId,
                    'subject_code'      => $topLevelSubjectCode,
                    'exam_year'         => $topLevelExamYear,
                    'examiner_type'     => $topLevelExaminerType,
                    'admin_user_id'     => $topLevelAdminUserId,
                ];
            }
        }

        if (empty($items)) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed: student_id is required.',
                'data'    => [],
            ], 400);
        }

        $validator = Validator::make(['items' => $items], [
            'items'                     => 'required|array|min:1',
            'items.*.teacher_id'        => 'required|integer|min:1',
            'items.*.student_id'        => 'required|integer|min:1',
            'items.*.student_inst_code' => 'required|string|max:50',
            'items.*.semester_id'       => 'required|integer',
            'items.*.subject_code'      => 'required|string|max:50',
            'items.*.exam_year'         => 'required|string|max:20',
            'items.*.examiner_type'     => 'required|string|max:50',
            'items.*.admin_user_id'     => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            Log::channel('daily')->error('❌ VALIDATION FAILED in saveTeacherAssignMarksCorrection:', [
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

        $successCount     = 0;
        $failedStudentIds = [];

        $sql = 'SELECT public.fn_admin_saveteacherassignmarkscorrection(?::bigint, ?::integer, ?::varchar, ?::integer, ?::varchar, ?::varchar, ?::varchar, ?::bigint) AS data';

        // Loop and execute stored procedure for each student
        foreach ($items as $index => $item) {
            $teacherId       = (int) $item['teacher_id'];
            $studentId       = (int) $item['student_id'];
            $studentInstCode = strtoupper(trim((string) $item['student_inst_code']));
            $semesterId      = (int) $item['semester_id'];
            $subjectCode     = strtoupper(trim((string) $item['subject_code']));
            $examYear        = trim((string) $item['exam_year']);
            $examinerType    = strtoupper(trim((string) $item['examiner_type']));
            $adminUserId     = (int) $item['admin_user_id'];

            Log::channel('daily')->info("📤 [Loop Execution] Item index [$index] -> Calling fn_admin_saveteacherassignmarkscorrection", [
                'p_teacher_id'       => $teacherId,
                'p_student_id'       => $studentId,
                'p_student_instcode' => $studentInstCode,
                'p_semester_id'      => $semesterId,
                'p_subject_code'     => $subjectCode,
                'p_exam_year'        => $examYear,
                'p_examiner_type'    => $examinerType,
                'p_adminuser_id'     => $adminUserId,
            ]);

            try {
                $dbResult = DB::select($sql, [
                    $teacherId,
                    $studentId,
                    $studentInstCode,
                    $semesterId,
                    $subjectCode,
                    $examYear,
                    $examinerType,
                    $adminUserId,
                ]);

                if (empty($dbResult) || !isset($dbResult[0]->data)) {
                    $failedStudentIds[] = $studentId;
                    continue;
                }

                $raw = $dbResult[0]->data;
                $data = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $failedStudentIds[] = $studentId;
                    continue;
                }

                $errorCode = isset($data['p_errorcode']) ? (int) $data['p_errorcode'] : 0;

                if ($errorCode === 0) {
                    $successCount++;
                } else {
                    $failedStudentIds[] = $studentId;
                }

            } catch (\Exception $e) {
                $failedStudentIds[] = $studentId;
                Log::channel('daily')->error("🔥 EXCEPTION in student assignment loop [$index]:", [
                    'student_id' => $studentId,
                    'message'    => $e->getMessage(),
                ]);
            }
        }

        Log::channel('daily')->info('📊 Assignment loop execution finished:', [
            'total_students'     => count($items),
            'success_count'      => $successCount,
            'failed_student_ids' => $failedStudentIds,
        ]);

        if (!empty($failedStudentIds)) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Failed to assign teacher for student ID(s): ' . implode(', ', $failedStudentIds),
                'data'    => [
                    'p_errorcode'        => 1,
                    'failed_student_ids' => $failedStudentIds,
                ],
            ], 400);
        }

        return response()->json([
            'version' => '1.0',
            'status'  => 0,
            'message' => count($items) === 1
                ? 'Teacher assigned successfully for marks correction.'
                : 'Teacher assigned successfully for ' . count($items) . ' student(s).',
            'data'    => [
                'p_errorcode' => 0,
            ],
        ], 200);
    }
}
