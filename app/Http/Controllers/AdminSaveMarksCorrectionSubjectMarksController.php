<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminSaveMarksCorrectionSubjectMarksController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/save-marks-correction-subject-marks",
     *     tags={"Admin - Student Marks"},
     *     summary="Save subject marks for marks correction (Single or Bulk)",
     *     description="Saves subject marks for student(s) during marks correction by calling public.fn_save_markscorrection_subject_marks(p_student_id, p_reg_no, p_semester, p_exam_year, p_subject_code, p_marks, p_examstatus, p_marksstatus, p_document, p_teacher_id, p_examinert_type)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_id", "reg_no", "semester", "exam_year", "subject_code", "marks", "exam_status", "marks_status", "teacher_id", "examiner_type"},
     *             @OA\Property(property="student_id", type="integer", example=3635, description="Student ID"),
     *             @OA\Property(property="reg_no", type="string", example="PHARM242504509", description="Registration Number"),
     *             @OA\Property(property="semester", type="integer", example=1, description="Semester (integer)"),
     *             @OA\Property(property="exam_year", type="string", example="2025", description="Exam Year"),
     *             @OA\Property(property="subject_code", type="string", example="HUAP", description="Subject Code"),
     *             @OA\Property(property="marks", type="integer", example=70, description="Marks (integer)"),
     *             @OA\Property(property="exam_status", type="string", example="PR", description="Exam Status (e.g. PR / AB / RA)"),
     *             @OA\Property(property="marks_status", type="string", example="DRAFT", description="Marks Status (e.g. DRAFT / FINAL)"),
     *             @OA\Property(property="document", type="string", example="D:Admin", description="Document path or identifier", nullable=true),
     *             @OA\Property(property="teacher_id", type="integer", example=661, description="Teacher / Evaluator User ID"),
     *             @OA\Property(property="examiner_type", type="string", example="EXTERNAL", description="Examiner Type (e.g. EXTERNAL / INTERNAL)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subject marks saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Marks saved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="p_errorcode", type="integer", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="Validation failed: The student id field is required."),
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
     * POST /api/admin/save-marks-correction-subject-marks
     *
     * Calls: public.fn_save_markscorrection_subject_marks(
     *   p_student_id,
     *   p_reg_no,
     *   p_semester,
     *   p_exam_year,
     *   p_subject_code,
     *   p_marks,
     *   p_examstatus,
     *   p_marksstatus,
     *   p_document,
     *   p_teacher_id,
     *   p_examinert_type
     * )
     */
    public function saveMarksCorrectionSubjectMarks(Request $request)
    {
        Log::channel('daily')->info('🚀 === SAVE MARKS CORRECTION SUBJECT MARKS API - REQUEST START ===');
        Log::channel('daily')->info('📥 REQUEST INPUT:', [
            'full_request' => $request->all(),
            'method'       => $request->method(),
            'url'          => $request->fullUrl(),
            'ip'           => $request->ip(),
        ]);

        // Extract top-level shared fields
        $topLevelSemester     = $request->input('semester') ?? $request->input('p_semester') ?? $request->input('semester_id') ?? $request->input('p_semester_id') ?? $request->input('semesterId');
        $topLevelExamYear     = $request->input('exam_year') ?? $request->input('p_exam_year') ?? $request->input('examYear') ?? $request->input('examyear');
        $topLevelSubjectCode  = $request->input('subject_code') ?? $request->input('p_subject_code') ?? $request->input('subjectCode') ?? $request->input('subjectcode');
        $topLevelMarksStatus  = $request->input('marks_status') ?? $request->input('p_marksstatus') ?? $request->input('p_marks_status') ?? $request->input('marksstatus') ?? $request->input('marksStatus');
        $topLevelDocument     = $request->input('document') ?? $request->input('p_document') ?? $request->input('doc') ?? $request->input('p_doc') ?? '';
        $topLevelTeacherId    = $request->input('teacher_id') ?? $request->input('p_teacher_id') ?? $request->input('teacherId') ?? $request->input('admin_user_id') ?? $request->input('p_admin_user_id') ?? $request->input('user_id');
        $topLevelExaminerType = $request->input('examiner_type') ?? $request->input('p_examinert_type') ?? $request->input('p_examiner_type') ?? $request->input('examinerType') ?? $request->input('examinertype') ?? $request->input('marks_type') ?? $request->input('markstype');

        $items = [];

        // Check if marks or students array is passed (Bulk submission)
        $marksList = $request->input('marks') ?? $request->input('students') ?? $request->input('data');

        if (is_array($marksList)) {
            foreach ($marksList as $entry) {
                if (is_array($entry)) {
                    $items[] = [
                        'student_id'    => $entry['student_id'] ?? $entry['p_student_id'] ?? $entry['studentId'] ?? $entry['id'] ?? null,
                        'reg_no'        => $entry['reg_no'] ?? $entry['p_reg_no'] ?? $entry['regNo'] ?? $entry['registration_no'] ?? $entry['studentRegNo'] ?? $entry['student_reg_no'] ?? '',
                        'semester'      => $entry['semester'] ?? $entry['p_semester'] ?? $entry['semester_id'] ?? $entry['p_semester_id'] ?? $entry['semesterId'] ?? $topLevelSemester,
                        'exam_year'     => $entry['exam_year'] ?? $entry['p_exam_year'] ?? $entry['examYear'] ?? $entry['examyear'] ?? $topLevelExamYear,
                        'subject_code'  => $entry['subject_code'] ?? $entry['p_subject_code'] ?? $entry['subjectCode'] ?? $entry['subjectcode'] ?? $topLevelSubjectCode,
                        'marks'         => $entry['marks'] ?? $entry['p_marks'] ?? $entry['corrected_marks'] ?? $entry['correctedMarks'] ?? $entry['mark'] ?? null,
                        'exam_status'   => $entry['exam_status'] ?? $entry['p_examstatus'] ?? $entry['p_exam_status'] ?? $entry['examstatus'] ?? $entry['examStatus'] ?? 'PR',
                        'marks_status'  => $entry['marks_status'] ?? $entry['p_marksstatus'] ?? $entry['p_marks_status'] ?? $entry['marksstatus'] ?? $entry['marksStatus'] ?? $topLevelMarksStatus,
                        'document'      => $entry['document'] ?? $entry['p_document'] ?? $entry['doc'] ?? $entry['p_doc'] ?? $topLevelDocument,
                        'teacher_id'    => $entry['teacher_id'] ?? $entry['p_teacher_id'] ?? $entry['teacherId'] ?? $entry['admin_user_id'] ?? $topLevelTeacherId,
                        'examiner_type' => $entry['examiner_type'] ?? $entry['p_examinert_type'] ?? $entry['p_examiner_type'] ?? $entry['examinerType'] ?? $entry['examinertype'] ?? $topLevelExaminerType,
                    ];
                }
            }
        } else {
            // Single object submission
            $items[] = [
                'student_id'    => $request->input('student_id') ?? $request->input('p_student_id') ?? $request->input('studentId'),
                'reg_no'        => $request->input('reg_no') ?? $request->input('p_reg_no') ?? $request->input('regNo') ?? $request->input('registration_no') ?? $request->input('registrationNo') ?? $request->input('studentRegNo') ?? $request->input('student_reg_no'),
                'semester'      => $topLevelSemester,
                'exam_year'     => $topLevelExamYear,
                'subject_code'  => $topLevelSubjectCode,
                'marks'         => $request->input('marks') ?? $request->input('p_marks') ?? $request->input('corrected_marks') ?? $request->input('correctedMarks') ?? $request->input('mark'),
                'exam_status'   => $request->input('exam_status') ?? $request->input('p_examstatus') ?? $request->input('p_exam_status') ?? $request->input('examstatus') ?? $request->input('examStatus') ?? 'PR',
                'marks_status'  => $topLevelMarksStatus,
                'document'      => $topLevelDocument,
                'teacher_id'    => $topLevelTeacherId,
                'examiner_type' => $topLevelExaminerType,
            ];
        }

        if (empty($items)) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'No student marks provided.',
                'data'    => [],
            ], 400);
        }

        // Validate each item
        $isBulk = count($items) > 1 || is_array($marksList);
        $itemRules = [
            'student_id'    => 'required|integer',
            'reg_no'        => 'required|string|max:50',
            'semester'      => 'required|integer',
            'exam_year'     => 'required|string|max:20',
            'subject_code'  => 'required|string|max:50',
            'marks'         => 'nullable|integer',
            'exam_status'   => 'required|string|max:20',
            'marks_status'  => 'required|string|max:50',
            'document'      => 'nullable|string|max:255',
            'teacher_id'    => 'required|integer',
            'examiner_type' => 'required|string|max:50',
        ];

        foreach ($items as $index => $item) {
            $validator = Validator::make($item, $itemRules);
            if ($validator->fails()) {
                Log::channel('daily')->error("❌ VALIDATION FAILED on item {$index}:", [
                    'errors' => $validator->errors()->all(),
                    'item'   => $item,
                ]);

                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => ($isBulk ? "Item {$index}: " : '') . 'Validation failed: ' . $validator->errors()->first(),
                    'data'    => [],
                ], 400);
            }
        }

        $results = [];
        $lastResult = null;
        $sql = 'SELECT public.fn_save_markscorrection_subject_marks(?::bigint, ?::varchar, ?::integer, ?::varchar, ?::varchar, ?::integer, ?::varchar, ?::varchar, ?::varchar, ?::bigint, ?::varchar) AS data';

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                $studentId    = (int) $item['student_id'];
                $regNo        = trim((string) $item['reg_no']);
                $semester     = (int) $item['semester'];
                $examYear     = trim((string) $item['exam_year']);
                $subjectCode  = strtoupper(trim((string) $item['subject_code']));
                $marks        = ($item['marks'] !== null && $item['marks'] !== '') ? (int) $item['marks'] : null;
                $examStatus   = strtoupper(trim((string) $item['exam_status']));
                $marksStatus  = strtoupper(trim((string) $item['marks_status']));
                $document     = (string) ($item['document'] ?? '');
                $teacherId    = (int) $item['teacher_id'];
                $examinerType = strtoupper(trim((string) $item['examiner_type']));

                Log::channel('daily')->info('📤 Calling fn_save_markscorrection_subject_marks with parameters:', [
                    'p_student_id'      => $studentId,
                    'p_reg_no'          => $regNo,
                    'p_semester'        => $semester,
                    'p_exam_year'       => $examYear,
                    'p_subject_code'    => $subjectCode,
                    'p_marks'           => $marks,
                    'p_examstatus'      => $examStatus,
                    'p_marksstatus'     => $marksStatus,
                    'p_document'        => $document,
                    'p_teacher_id'      => $teacherId,
                    'p_examinert_type'  => $examinerType,
                ]);

                $spResult = DB::select($sql, [
                    $studentId,
                    $regNo,
                    $semester,
                    $examYear,
                    $subjectCode,
                    $marks,
                    $examStatus,
                    $marksStatus,
                    $document,
                    $teacherId,
                    $examinerType,
                ]);

                if (empty($spResult) || !isset($spResult[0]->data)) {
                    throw new \Exception("No response received from stored function for student ID {$studentId}");
                }

                $raw = $spResult[0]->data;
                $parsed = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Failed to parse database response for student ID {$studentId}");
                }

                $lastResult = $parsed;
                $results[] = [
                    'student_id' => $studentId,
                    'result'     => $parsed,
                ];
            }

            DB::commit();

            Log::channel('daily')->info('✅ fn_save_markscorrection_subject_marks executed successfully:', [
                'total_processed' => count($items),
            ]);

            $responseData = [
                'version' => '1.0',
                'status'  => 0,
                'message' => count($items) > 1
                    ? 'Marks saved successfully for ' . count($items) . ' student(s).'
                    : 'Marks saved successfully',
                'data'    => $lastResult ?? ['p_errorcode' => 0],
            ];

            Log::channel('daily')->info('📤 FINAL RESPONSE:', $responseData);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('daily')->error('🔥 EXCEPTION in saveMarksCorrectionSubjectMarks:', [
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
