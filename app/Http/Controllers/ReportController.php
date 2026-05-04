<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;


class ReportController extends Controller
{
    public function registeredStudentReportList(Request $request)
    {
        try {
            $inst_code = $request->inst_code;
            $sess_yr = $request->sess_yr;
            $inst_tbl = 'institute_master';

            $students = DB::table('pharmacy_register_student_final as s')
                ->join($inst_tbl . ' as i', 'i.i_code', '=', 's.s_inst_code')
                ->when($inst_code, function ($query, $inst_code) {
                    return $query->where('s.s_inst_code', $inst_code);
                })
                ->where('s.s_appl_sess_year', $sess_yr)
                ->where('s.s_registration_cancel', 0)
                ->where('s.is_active', 1)
                ->whereNotNull('s.s_appl_reg_no')
                ->get();


            
            $studentData = $students->map(function ($student) {
                return [
                    'Department' => "PHARM",
                    's_reg_no' => $student->s_appl_reg_no,
                    's_reg_year' => $student->s_appl_sess_year,
                    's_full_name' => $student->s_first_name." ".$student->s_middle_name." ".$student->s_last_name,
                    's_father_name' => $student->s_father_name,
                    's_mother_name' => $student->s_mother_name,
                    's_dob' => $student->s_dob,
                    's_aadhar_no' => $student->s_aadhar_original,
                    's_phone' => $student->s_phone,
                    's_email' => $student->s_email,
                    's_gender' => $student->s_gender,
                    's_religion' => $student->s_religion,
                    's_caste' => $student->s_caste,
                    'institute_code' => $student->s_inst_code,
                    'institute_name' => $student->i_name,
                    'adm_type' => $student->admission_type,
                    "s_alloted_category"=> $student->s_alloted_category
                ];
            });

            return response()->json([
                'error' => false,
                'message' => 'Data found',
                'data' => $studentData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching student list: ' . $e->getMessage()
            ], 500);
        }
    }
    

    public function resultReportList(Request $request)
    {
        try {
            $request->validate([
                'exam_year' => 'required',
            ]);

            $examYear = $request->exam_year;
            $hasDeptColumn = Schema::hasColumn('pharmacy_enrollment', 'enrl_dept_code');

            $query = DB::table('2024_pharmacy_result_pone as r')
                ->join('pharmacy_enrollment as e', 'e.enrl_reg_no', '=', 'r.reg_number')
                ->where('r.exam_year', $examYear);

            if ($hasDeptColumn) {
                // ✅ Join departments table
                $query->join('pharmacy_departments as d', 'd.dept_code', '=', 'e.enrl_dept_code')
                    ->select(
                        'd.dept_code',
                        'd.dept_name',
                        DB::raw("COUNT(CASE WHEN UPPER(r.result_final) = 'Q' THEN 1 END) as pass_count"),
                        DB::raw("COUNT(CASE WHEN UPPER(r.result_final) = 'F' THEN 1 END) as fail_count"),
                        DB::raw("COUNT(r.reg_number) as total_students")
                    )
                    ->whereNotNull('e.enrl_dept_code')
                    ->where('e.enrl_dept_code', '!=', '')
                    ->groupBy('d.dept_code', 'd.dept_name');
            } else {
                // ✅ If no department column — overall totals only
                $query->select(
                    DB::raw("COUNT(CASE WHEN UPPER(r.result_final) = 'Q' THEN 1 END) as pass_count"),
                    DB::raw("COUNT(CASE WHEN UPPER(r.result_final) = 'F' THEN 1 END) as fail_count"),
                    DB::raw("COUNT(r.reg_number) as total_students")
                );
            }

            $report = $query->get();

            return response()->json([
                'status' => 'success',
                'data' => $report,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching student list: ' . $e->getMessage()
            ], 500);
        }
    }

    public function resultSubjectWiseReportList(Request $request)
    {
        try {
            $request->validate([
                'exam_year' => 'required',
            ]);

            $examYear = $request->exam_year;
            $creditColumn = "credit_" . $examYear; // e.g., credit_2024

            // Fetch all subjects
            $subjects = DB::table('pharmacy_subjects_master')
                ->select('general_code', 'subject_name')
                ->where('is_active', '1')
                ->get()
                ->keyBy('general_code'); // map by subject_id for easy lookup

            $subjectCounts = [];

            // Fetch all students' credit columns
            $students = DB::table('2024_pharmacy_result_pone')
                ->select('reg_number', $creditColumn, 'p1_code', 'p2_code', 'p3_code', 'p4_code', 'p5_code', 'p6_code', 'p7_code', 'p8_code', 'p9_code', 'p10_code')
                ->where('exam_year', $examYear)
                ->get();

            $totalStudents = $students->count();

            foreach ($students as $student) {
                if (!$student->$creditColumn) continue;

                $passedPapers = explode(',', $student->$creditColumn); // e.g., ['p1','p2','p3']

                foreach ($passedPapers as $p) {
                    $subjectCode = $student->{$p . '_code'} ?? null;
                    if ($subjectCode) {
                        if (!isset($subjectCounts[$subjectCode])) {
                            $subjectCounts[$subjectCode] = 0;
                        }
                        $subjectCounts[$subjectCode]++;
                    }
                }
            }

            // Prepare result array
            $resultData = [];
            foreach ($subjects as $subjectCode => $subject) {
                $passCount = $subjectCounts[$subjectCode] ?? 0;
                $failCount = $totalStudents - $passCount;

                $resultData[] = [
                    'subject_code' => $subjectCode,
                    'subject_name' => $subject->subject_name,
                    'pass_count'   => $passCount,
                    'fail_count'   => $failCount,
                ];
            }

            return response()->json([
                'status'    => 'success',
                'exam_year' => $examYear,
                'data'      => $resultData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error generating subject-wise report: ' . $e->getMessage()
            ], 500);
        }
    }

    public function studentResultReport(Request $request)
    {
        try {
            $request->validate([
                'exam_year' => 'required',
            ]);

            $examYear = $request->exam_year;

            // Fetch student-wise results for the selected exam year
            $results = DB::table($examYear.'_pharmacy_result_pone as r')
                ->join('pharmacy_register_student_final as s', 's.s_appl_reg_no', '=', 'r.reg_number')
                ->select(
                    'r.roll_number',
                    'r.reg_number',
                    's.s_candidate_name as full_name',
					'r.grand_total as mark_obtained',
                    'r.percentage',
                    'r.result_final as remark'
                )
                ->where('r.exam_year', $examYear)
                ->get();

            return response()->json([
                'status' => 'success',
                'exam_year' => $examYear,
                'data' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching student results: ' . $e->getMessage()
            ], 500);
        }
    }





}
