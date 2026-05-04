<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;


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
                    'Dept ID' => "PHARM",
                    'Dept Name' => "Diploma In Pharmacy",
                    'Session' => $student->s_appl_sess_year,
                    's_reg_no' => $student->s_appl_reg_no,
                    //'s_reg_year' => $student->s_appl_sess_year,
                    's_full_name' => $student->s_first_name . " " . $student->s_middle_name . " " . $student->s_last_name,
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
                    "s_alloted_category" => $student->s_alloted_category
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


}
