<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StudentMarksController extends Controller
{
    /**
     * POST /api/marks/student-marks-info-v1
     *
     * Get student marks information for a given subject/dept/institute/semester.
     *
     * Body: {
     *   "admin_user_id": 668,
     *   "student_id":    0,       // 0 = all students
     *   "inst_code":     "KAN",
     *   "dept_code":     "ME",
     *   "subject_code":  "MTHS1",
     *   "exam_year":     2026,
     *   "semester":      1
     * }
     *
     * TODO: Replace mock data with DB call below once fn_admin_getstudentmarksinfo_v1 is ready:
     * -----------------------------------------------------------------------------------------
     * $result = DB::select(
     *     'SELECT public.fn_admin_getstudentmarksinfo_v1(
     *         ?::bigint, ?::bigint, ?::varchar, ?::varchar, ?::varchar, ?::integer, ?::integer
     *     ) AS data',
     *     [$adminUserId, $studentId, $instCode, $deptCode, $subjectCode, $examYear, $semester]
     * );
     * $raw  = $result[0]->data ?? null;
     * $rows = is_string($raw) ? json_decode($raw, true) : (array) $raw;
     * -----------------------------------------------------------------------------------------
     */
    public function getStudentMarksInfoV1(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_user_id' => 'required|integer',
            'student_id'    => 'required|integer',
            'inst_code'     => 'required|string|max:20',
            'dept_code'     => 'required|string|max:20',
            'subject_code'  => 'required|string|max:50',
            'exam_year'     => 'required|integer',
            'semester'      => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed.',
                'data'    => $validator->errors(),
            ], 422);
        }

        $adminUserId  = (int) $request->input('admin_user_id');
        $studentId    = (int) $request->input('student_id');
        $instCode     = strtoupper(trim($request->input('inst_code')));
        $deptCode     = strtoupper(trim($request->input('dept_code')));
        $subjectCode  = strtoupper(trim($request->input('subject_code')));
        $examYear     = (int) $request->input('exam_year');
        $semester     = (int) $request->input('semester');

        Log::channel('daily')->info('[getStudentMarksInfoV1] INPUT', [
            'admin_user_id' => $adminUserId,
            'student_id'    => $studentId,
            'inst_code'     => $instCode,
            'dept_code'     => $deptCode,
            'subject_code'  => $subjectCode,
            'exam_year'     => $examYear,
            'semester'      => $semester,
            'ip'            => $request->ip(),
        ]);

        try {
            // ---------------------------------------------------------------
            // TODO: Uncomment DB call below once fn_admin_getstudentmarksinfo_v1 is created
            // ---------------------------------------------------------------
            // $result = DB::select(
            //     'SELECT public.fn_admin_getstudentmarksinfo_v1(
            //         ?::bigint, ?::bigint, ?::varchar, ?::varchar, ?::varchar, ?::integer, ?::integer
            //     ) AS data',
            //     [$adminUserId, $studentId, $instCode, $deptCode, $subjectCode, $examYear, $semester]
            // );
            // $raw  = $result[0]->data ?? null;
            // $rows = is_string($raw) ? json_decode($raw, true) : (array) $raw;
            // if (json_last_error() !== JSON_ERROR_NONE || empty($rows)) {
            //     return response()->json(['version' => '1.0', 'status' => 1, 'message' => 'No data found.', 'data' => []], 404);
            // }
            // ---------------------------------------------------------------

            // --- MOCK DATA (remove after DB function is ready) ---
            $rows = $this->getMockData($instCode, $deptCode, $subjectCode, $examYear, $semester, $studentId);
            // --- END MOCK DATA ---

            Log::channel('daily')->info('[getStudentMarksInfoV1] OUTPUT (200)', [
                'count' => count($rows),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Data fetch successfully',
                'data'    => $rows,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[getStudentMarksInfoV1] EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Mock data builder — mirrors the real DB response shape.
     * Replace with DB call once fn_admin_getstudentmarksinfo_v1 is available.
     */
    private function getMockData(string $instCode, string $deptCode, string $subjectCode, int $examYear, int $semester, int $studentId): array
    {
        $allStudents = [
            [
                'marks_id'                                => 215138,
                'student_id'                              => 6023,
                'student_name'                            => 'ANIKET  MAHATO',
                'student_reg_no'                          => 'D242517928',
                'student_roll'                            => 'D' . $instCode . $deptCode . 'S' . $semester,
                'student_no'                              => '10011967',
                'student_enrl_type'                       => null,
                'internal_theory_marks'                   => null,
                'internal_max_theory_marks'               => null,
                'internal_theory_viva_marks'              => null,
                'internal_max_theory_viva_marks'          => null,
                'internal_theory_assignment_marks'        => null,
                'internal_max_theory_assignment_marks'    => null,
                'internal_theory_attendance_marks'        => null,
                'internal_max_theory_attendance_marks'    => null,
                'internal_sessional_marks'                => null,
                'internal_max_sessional_marks'            => null,
                'internal_sessional_viva_marks'           => null,
                'internal_max_sessional_viva_marks'       => null,
                'internal_sessional_attendance_marks'     => null,
                'internal_max_sessional_attendance_marks' => null,
                'sessional_external_marks'                => null,
                'sessional_external_max_marks'            => null,
                'written_external_marks'                  => null,
                'written_external_max_marks'              => null,
                'exam_status'                             => 'PR',
                'marks_status'                            => 'INIT',
                'subject_code'                            => $subjectCode,
                'subject_category'                        => 'THEORETICAL',
                'subject_category_id'                     => 1,
                'semester'                                => $semester,
                'department'                              => $deptCode,
                'is_editable'                             => 1,
                'pending_student'                         => 1,
            ],
            [
                'marks_id'                                => 215139,
                'student_id'                              => 6024,
                'student_name'                            => 'PRIYA SHARMA',
                'student_reg_no'                          => 'D242517929',
                'student_roll'                            => 'D' . $instCode . $deptCode . 'S' . $semester,
                'student_no'                              => '10011968',
                'student_enrl_type'                       => null,
                'internal_theory_marks'                   => 18,
                'internal_max_theory_marks'               => 25,
                'internal_theory_viva_marks'              => null,
                'internal_max_theory_viva_marks'          => null,
                'internal_theory_assignment_marks'        => 8,
                'internal_max_theory_assignment_marks'    => 10,
                'internal_theory_attendance_marks'        => null,
                'internal_max_theory_attendance_marks'    => null,
                'internal_sessional_marks'                => null,
                'internal_max_sessional_marks'            => null,
                'internal_sessional_viva_marks'           => null,
                'internal_max_sessional_viva_marks'       => null,
                'internal_sessional_attendance_marks'     => null,
                'internal_max_sessional_attendance_marks' => null,
                'sessional_external_marks'                => null,
                'sessional_external_max_marks'            => null,
                'written_external_marks'                  => 45,
                'written_external_max_marks'              => 75,
                'exam_status'                             => 'PR',
                'marks_status'                            => 'DRAFT',
                'subject_code'                            => $subjectCode,
                'subject_category'                        => 'THEORETICAL',
                'subject_category_id'                     => 1,
                'semester'                                => $semester,
                'department'                              => $deptCode,
                'is_editable'                             => 1,
                'pending_student'                         => 0,
            ],
        ];

        // Filter by student_id if non-zero
        if ($studentId !== 0) {
            $allStudents = array_values(array_filter($allStudents, fn($s) => $s['student_id'] === $studentId));
        }

        return $allStudents;
    }
}
