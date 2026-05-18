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
     *   "student_id":    0,           // 0 = all students, non-zero = specific student
     *   "inst_code":     "JCG",
     *   "dept_code":     "PHARM",
     *   "subject_code":  "PHCE",
     *   "exam_year":     2025,        // e.g. 2025 → session 2024-2025
     *   "semester":      1            // 1 = Part_I, 2 = Part_II
     * }
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
            'semester'      => 'required|integer|in:1,2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed.',
                'data'    => $validator->errors(),
            ], 422);
        }

        $adminUserId = (int) $request->input('admin_user_id');
        $studentId   = (int) $request->input('student_id');
        $instCode    = strtoupper(trim($request->input('inst_code')));
        $deptCode    = strtoupper(trim($request->input('dept_code')));
        $subjectCode = strtoupper(trim($request->input('subject_code')));
        $examYear    = (int) $request->input('exam_year');
        $semester    = (int) $request->input('semester');

        // semester int → part_sem string used in pharmacy_register_student_final
        $partSem  = $semester === 1 ? 'Part_I' : 'Part_II';

        // exam_year 2025 → session year "2024-2025"
        $sessYear = ($examYear - 1) . '-' . $examYear;

        Log::channel('daily')->info('[getStudentMarksInfoV1] INPUT', [
            'admin_user_id' => $adminUserId,
            'student_id'    => $studentId,
            'inst_code'     => $instCode,
            'dept_code'     => $deptCode,
            'subject_code'  => $subjectCode,
            'exam_year'     => $examYear,
            'semester'      => $semester,
            'part_sem'      => $partSem,
            'sess_year'     => $sessYear,
            'ip'            => $request->ip(),
        ]);

        try {
            // Fetch subject metadata from master to fill in when marks row is absent
            $subjectMeta = DB::selectOne("
                SELECT dsm_type, dsm_subject_category, dsm_subject_category_id,
                       dsm_subject_total_marks, dsm_subject_pass_marks,
                       dsm_subject_internal_total_marks, dsm_subject_external_total_marks,
                       dsm_subject_internal_total_attendance_marks,
                       dsm_subject_internal_total_project_marks
                FROM public.tbl_department_subjects_master
                WHERE dsm_dept = ?
                  AND dsm_subject_id = ?
                  AND dsm_is_active = '1'
                LIMIT 1
            ", [$deptCode, $subjectCode]);

            $subjectCategory      = $subjectMeta->dsm_subject_category      ?? null;
            $subjectCategoryId    = $subjectMeta->dsm_subject_category_id    ?? null;
            $subjectType          = $subjectMeta->dsm_type                   ?? null;  // Theoretical | Sessional
            $maxExternal          = $subjectMeta->dsm_subject_external_total_marks  ?? null;  // e.g. 80
            $maxInternal          = $subjectMeta->dsm_subject_internal_total_marks  ?? null;  // e.g. 20

            // For Theoretical subjects  → written_external_max = maxExternal, sessional_external_max = NULL
            // For Sessional subjects    → sessional_external_max = maxExternal, written_external_max = NULL
            $maxWrittenExternal    = ($subjectType === 'Theoretical') ? $maxExternal : null;
            $maxSessionalExternal  = ($subjectType === 'Sessional')   ? $maxExternal : null;

            // Base query — LEFT JOIN so all eligible students appear even without marks
            $sql = "
                SELECT
                    m.psm_mark_id                                   AS marks_id,
                    s.s_id                                          AS student_id,
                    s.s_candidate_name                              AS student_name,
                    s.s_appl_reg_no                                 AS student_reg_no,
                    m.psm_roll                                      AS student_roll,
                    m.psm_no                                        AS student_no,
                    m.psm_enrl_type                                 AS student_enrl_type,
                    m.psm_theory_marks_internal                     AS internal_theory_marks,
                    COALESCE(m.psm_theory_max_marks_internal,       NULLIF(?::text,'')::numeric) AS internal_max_theory_marks,
                    m.psm_theory_viva_marks_internal                AS internal_theory_viva_marks,
                    m.psm_theory_viva_max_marks_internal            AS internal_max_theory_viva_marks,
                    m.psm_theory_assignment_marks_internal          AS internal_theory_assignment_marks,
                    m.psm_theory_max_assignment_marks_internal      AS internal_max_theory_assignment_marks,
                    m.psm_theory_attendance_marks_internal          AS internal_theory_attendance_marks,
                    COALESCE(m.psm_theory_max_attendance_marks_internal, NULLIF(?::text,'')::numeric) AS internal_max_theory_attendance_marks,
                    m.psm_sessional_marks_internal                  AS internal_sessional_marks,
                    COALESCE(m.psm_sessional_max_marks_internal,    NULLIF(?::text,'')::numeric) AS internal_max_sessional_marks,
                    m.psm_sessional_viva_marks_internal             AS internal_sessional_viva_marks,
                    m.psm_sessional_max_viva_marks_internal         AS internal_max_sessional_viva_marks,
                    m.psm_sessional_attendance_marks_internal       AS internal_sessional_attendance_marks,
                    m.psm_sessional_max_attendance_marks_internal   AS internal_max_sessional_attendance_marks,
                    m.psm_practical_external_marks                  AS sessional_external_marks,
                    COALESCE(m.psm_max_practical_external_marks,    NULLIF(?::text,'')::numeric) AS sessional_external_max_marks,
                    m.psm_written_external_marks                    AS written_external_marks,
                    COALESCE(m.psm_written_external_max_marks,      NULLIF(?::text,'')::numeric) AS written_external_max_marks,
                    COALESCE(m.psm_exam_status_code, 'PR')          AS exam_status,
                    COALESCE(m.psm_status_code, 'INIT')             AS marks_status,
                    COALESCE(m.psm_subject_code, ?)                 AS subject_code,
                    COALESCE(m.psm_subject_category, ?)             AS subject_category,
                    COALESCE(m.psm_subject_category_id::text, ?)::smallint AS subject_category_id,
                    COALESCE(m.psm_semester, ?)                     AS semester,
                    COALESCE(m.psm_department, ?)                   AS department,
                    CASE WHEN m.psm_mark_id IS NULL THEN 1 ELSE 0 END AS pending_student,
                    1                                               AS is_editable
                FROM public.pharmacy_register_student_final s
                LEFT JOIN public.tbl_pharmacy_student_marks m
                    ON  m.psm_student_id   = s.s_id
                    AND m.psm_subject_code = ?
                    AND m.psm_department   = ?
                    AND m.psm_inst_code    = ?
                    AND m.psm_semester     = ?
                    AND m.psm_exam_year    = ?
                WHERE s.s_inst_code      = ?
                  AND s.s_trade_code     = ?
                  AND s.s_part_sem       = ?
                  AND s.s_appl_sess_year = ?
                  AND s.is_active        = 1
            ";

            $bindings = [
                // COALESCE max fallbacks from subject master
                $maxInternal,           // internal_max_theory_marks
                null,                   // internal_max_theory_attendance_marks (not in master)
                $maxInternal,           // internal_max_sessional_marks
                $maxSessionalExternal,  // sessional_external_max_marks
                $maxWrittenExternal,    // written_external_max_marks
                // other COALESCE fallbacks
                $subjectCode,
                $subjectCategory,
                $subjectCategoryId,
                $semester,
                $deptCode,
                // JOIN conditions
                $subjectCode, $deptCode, $instCode, $semester, $examYear,
                // WHERE conditions
                $instCode, $deptCode, $partSem, $sessYear,
            ];

            // Optional single-student filter
            if ($studentId !== 0) {
                $sql      .= ' AND s.s_id = ?';
                $bindings[] = $studentId;
            }

            $sql .= ' ORDER BY s.s_candidate_name';

            $results = DB::select($sql, $bindings);

            if (empty($results)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No students found for the given parameters.',
                    'data'    => [],
                ], 404);
            }

            // Cast to clean array
            $rows = array_map(function ($row) {
                return [
                    'marks_id'                                => $row->marks_id !== null ? (int) $row->marks_id : null,
                    'student_id'                              => (int) $row->student_id,
                    'student_name'                            => $row->student_name,
                    'student_reg_no'                          => $row->student_reg_no,
                    'student_roll'                            => $row->student_roll,
                    'student_no'                              => $row->student_no,
                    'student_enrl_type'                       => $row->student_enrl_type,
                    'internal_theory_marks'                   => $row->internal_theory_marks !== null ? (float) $row->internal_theory_marks : null,
                    'internal_max_theory_marks'               => $row->internal_max_theory_marks !== null ? (float) $row->internal_max_theory_marks : null,
                    'internal_theory_viva_marks'              => $row->internal_theory_viva_marks !== null ? (float) $row->internal_theory_viva_marks : null,
                    'internal_max_theory_viva_marks'          => $row->internal_max_theory_viva_marks !== null ? (float) $row->internal_max_theory_viva_marks : null,
                    'internal_theory_assignment_marks'        => $row->internal_theory_assignment_marks !== null ? (float) $row->internal_theory_assignment_marks : null,
                    'internal_max_theory_assignment_marks'    => $row->internal_max_theory_assignment_marks !== null ? (float) $row->internal_max_theory_assignment_marks : null,
                    'internal_theory_attendance_marks'        => $row->internal_theory_attendance_marks !== null ? (float) $row->internal_theory_attendance_marks : null,
                    'internal_max_theory_attendance_marks'    => $row->internal_max_theory_attendance_marks !== null ? (float) $row->internal_max_theory_attendance_marks : null,
                    'internal_sessional_marks'                => $row->internal_sessional_marks !== null ? (float) $row->internal_sessional_marks : null,
                    'internal_max_sessional_marks'            => $row->internal_max_sessional_marks !== null ? (float) $row->internal_max_sessional_marks : null,
                    'internal_sessional_viva_marks'           => $row->internal_sessional_viva_marks !== null ? (float) $row->internal_sessional_viva_marks : null,
                    'internal_max_sessional_viva_marks'       => $row->internal_max_sessional_viva_marks !== null ? (float) $row->internal_max_sessional_viva_marks : null,
                    'internal_sessional_attendance_marks'     => $row->internal_sessional_attendance_marks !== null ? (float) $row->internal_sessional_attendance_marks : null,
                    'internal_max_sessional_attendance_marks' => $row->internal_max_sessional_attendance_marks !== null ? (float) $row->internal_max_sessional_attendance_marks : null,
                    'sessional_external_marks'                => $row->sessional_external_marks !== null ? (float) $row->sessional_external_marks : null,
                    'sessional_external_max_marks'            => $row->sessional_external_max_marks !== null ? (float) $row->sessional_external_max_marks : null,
                    'written_external_marks'                  => $row->written_external_marks !== null ? (float) $row->written_external_marks : null,
                    'written_external_max_marks'              => $row->written_external_max_marks !== null ? (float) $row->written_external_max_marks : null,
                    'exam_status'                             => $row->exam_status,
                    'marks_status'                            => $row->marks_status,
                    'subject_code'                            => $row->subject_code,
                    'subject_category'                        => $row->subject_category,
                    'subject_category_id'                     => $row->subject_category_id !== null ? (int) $row->subject_category_id : null,
                    'semester'                                => (int) $row->semester,
                    'department'                              => $row->department,
                    'is_editable'                             => (int) $row->is_editable,
                    'pending_student'                         => (int) $row->pending_student,
                ];
            }, $results);

            Log::channel('daily')->info('[getStudentMarksInfoV1] OUTPUT (200)', [
                'inst_code'    => $instCode,
                'dept_code'    => $deptCode,
                'subject_code' => $subjectCode,
                'sess_year'    => $sessYear,
                'semester'     => $semester,
                'count'        => count($rows),
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
}
