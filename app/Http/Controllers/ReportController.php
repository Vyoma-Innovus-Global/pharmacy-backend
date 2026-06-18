<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;


class ReportController extends Controller
{
    /**
     * Get registered student details list for an institute admin.
     *
     * Calls: public.fn_getregistredstudentdetailslistbyinstrituteadmin(
     *   p_department, p_instcode, p_academicseason
     * )
     */
    public function registeredStudentDetailsListByInstituteAdmin(Request $request)
    {
        $department = $request->input('department', $request->input('p_department'));
        $instCode = $request->input('inst_code', $request->input('p_instcode', $request->input('instcode')));
        $academicSession = $request->input('academic_session', $request->input('p_academicseason', $request->input('academicseason', $request->input('sess_yr'))));

        $validator = Validator::make([
            'department' => $department,
            'inst_code' => $instCode,
            'academic_session' => $academicSession,
        ], [
            'department' => 'required|string|max:50',
            'inst_code' => 'required|string|max:50',
            'academic_session' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $result = DB::select(
                'SELECT public.fn_getregistredstudentdetailslistbyinstrituteadmin(?::varchar, ?::varchar, ?::varchar) AS data',
                [trim($department), trim($instCode), trim($academicSession)]
            );

            if (empty($result)) {
                return response()->json([
                    'error' => true,
                    'message' => 'No registered student data found.',
                ], 404);
            }

            $raw = $result[0]->data ?? null;

            if ($raw === null) {
                return response()->json([
                    'error' => true,
                    'message' => 'No registered student data found.',
                ], 404);
            }

            $studentData = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE || empty($studentData)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Failed to parse registered student data from database.',
                ], 500);
            }

            $studentData = collect($studentData)->map(function ($student) {
                $student = (array) $student;
                $student['studentId'] = $student['studentId']
                    ?? $student['student_id']
                    ?? $student['s_id']
                    ?? null;
                $student['instregistrationstatus'] = $student['instregistrationstatus']
                    ?? $student['inst_registration_status']
                    ?? null;
                $student['instregistrationstatusBy'] = $student['instregistrationstatusBy']
                    ?? $student['inst_registration_status_by']
                    ?? null;
                $student['councilregistrationstatus'] = $student['councilregistrationstatus']
                    ?? $student['council_registration_status']
                    ?? null;
                $student['councilregistrationstatusBy'] = $student['councilregistrationstatusBy']
                    ?? $student['council_registration_status_by']
                    ?? null;

                return $student;
            })->values()->all();

            return response()->json([
                'error' => false,
                'message' => 'Data found',
                'data' => $studentData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get registered student details by student ID.
     *
     * Calls: public.fn_getregistredstudentdetailslistbystudentid(
     *   p_studentid, p_adminuserid
     * )
     */
    public function registeredStudentDetailsListByStudentId(Request $request)
    {
        $studentId = $request->input('student_id', $request->input('p_studentid', $request->input('studentId')));
        $adminUserId = $request->input('admin_user_id', $request->input('p_adminuserid', $request->input('adminUserId')));

        $validator = Validator::make([
            'student_id' => $studentId,
            'admin_user_id' => $adminUserId,
        ], [
            'student_id' => 'required|integer',
            'admin_user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $result = DB::select(
                'SELECT public.fn_getregistredstudentdetailslistbystudentid(?::bigint, ?::bigint) AS data',
                [(int) $studentId, (int) $adminUserId]
            );

            if (empty($result)) {
                return response()->json([
                    'error' => true,
                    'message' => 'No registered student data found.',
                ], 404);
            }

            $raw = $result[0]->data ?? null;

            if ($raw === null) {
                return response()->json([
                    'error' => true,
                    'message' => 'No registered student data found.',
                ], 404);
            }

            $studentData = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE || empty($studentData)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Failed to parse registered student data from database.',
                ], 500);
            }

            $studentData['studentId'] = $studentData['studentId']
                ?? $studentData['student_id']
                ?? $studentData['s_id']
                ?? (int) $studentId;
            $studentData['instregistrationstatus'] = $studentData['instregistrationstatus']
                ?? $studentData['inst_registration_status']
                ?? null;
            $studentData['instregistrationstatusBy'] = $studentData['instregistrationstatusBy']
                ?? $studentData['inst_registration_status_by']
                ?? null;
            $studentData['councilregistrationstatus'] = $studentData['councilregistrationstatus']
                ?? $studentData['council_registration_status']
                ?? null;
            $studentData['councilregistrationstatusBy'] = $studentData['councilregistrationstatusBy']
                ?? $studentData['council_registration_status_by']
                ?? null;

            return response()->json([
                'error' => false,
                'message' => 'Data found',
                'data' => $studentData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update registered student details by admin.
     *
     * Calls: public.fn_updatestudentdetailsbyadmin(...)
     */
    public function updateStudentDetailsByAdmin(Request $request)
    {
        $input = function (...$keys) use ($request) {
            foreach ($keys as $key) {
                if ($key !== null && $request->has($key)) {
                    return $request->input($key);
                }
            }

            return null;
        };

        $payload = [
            'student_id' => $input('student_id', 'studentId', 'p_studentid'),
            'admin_user_id' => $input('admin_user_id', 'adminUserId', 'p_adminuserid'),
            'admin_user_type' => $input('admin_user_type', 'adminUserType', 'p_adminusertype'),
            'first_name' => $input('first_name', 'firstName', 'p_firstname'),
            'middle_name' => $input('middle_name', 'middleName', 'p_middlename'),
            'last_name' => $input('last_name', 'lastName', 'p_lastname'),
            'father_name' => $input('father_name', 'fatherName', 'p_fathername'),
            'mother_name' => $input('mother_name', 'motherName', 'p_mothername'),
            'date_of_birth' => $input('date_of_birth', 'dateOfBirth', 'p_dob'),
            'gender' => $input('gender', null, 'p_gender'),
            'phone' => $input('phone', null, 'p_phone'),
            'email' => $input('email', null, 'p_email'),
            'aadhar_no' => $input('aadhar_no', 'aadharNo', 'p_aadharno'),
            'caste' => $input('caste', null, 'p_caste'),
            'address' => $input('address', null, 'p_address'),
            'police_station' => $input('police_station', 'policeStation', 'p_ps'),
            'post_office' => $input('post_office', 'postOffice', 'p_po'),
            'pin' => $input('pin', null, 'p_pin'),
            'is_married' => $input('is_married', 'isMarried', 'p_ismarried'),
            'is_kanyashree' => $input('is_kanyashree', 'isKanyashree', 'p_iskanyashree'),
            'photo' => $input('photo', null, 'p_photo'),
            'signature' => $input('signature', 'sign', 'p_sign'),
            'guardian_name' => $input('guardian_name', 'guardianName', 'p_guardianname'),
            'guardian_relation' => $input('guardian_relation', 'guardianRelation', 'p_guardianrelation'),
            'citizenship' => $input('citizenship', null, 'p_citizenship'),
            'state' => $input('state', null, 'p_state'),
            'district' => $input('district', null, 'p_district'),
            'sub_division' => $input('sub_division', 'subDivision', 'p_subdivision'),
            'municipality_block' => $input('municipality_block', 'municipalityBlock', 'p_municipalityblock'),
            'board_name' => $input('board_name', 'boardName', 'p_boardname'),
            'last_institute' => $input('last_institute', 'lastInstitute', 'p_lastinstitute'),
            'passing_year' => $input('passing_year', 'passingYear', 'p_passingyear'),
            'aggregate_marks' => $input('aggregate_marks', 'aggregateMarks', 'p_aggregatemarks'),
            'marks_obtained' => $input('marks_obtained', 'marksObtained', 'p_marksobtained'),
            'percentage' => $input('percentage', null, 'p_percentage'),
            'physics_full_marks' => $input('physics_full_marks', 'physicsFullMarks', 'p_physicsfullmarks'),
            'chemistry_full_marks' => $input('chemistry_full_marks', 'chemistryFullMarks', 'p_chemistryfullmarks'),
            'biology_math_full_marks' => $input('biology_math_full_marks', 'biologyMathFullMarks', 'p_biomathfullmarks'),
            'physics_marks' => $input('physics_marks', 'physicsMarks', 'p_physicsmarks'),
            'chemistry_marks' => $input('chemistry_marks', 'chemistryMarks', 'p_chemistrymarks'),
            'biology_math_marks' => $input('biology_math_marks', 'biologyMathMarks', 'p_biomathmarks'),
            'other_qualification' => $input('other_qualification', 'otherQualification', 'p_otherqualification'),
            'citizenship_document' => $input('citizenship_document', 'citizenshipDocument', 'p_citizenshipdoc'),
            'caste_document' => $input('caste_document', 'casteDocument', 'p_castdoc'),
            'physically_challenged_document' => $input('physically_challenged_document', 'physicallyChallengedDocument', 'p_pccertificatedoc'),
            'aadhar_document' => $input('aadhar_document', 'aadharDocument', 'p_aadhardoc'),
            'kanyashree_document' => $input('kanyashree_document', 'kanyashreeDocument', 'p_kanyashreedoc'),
            'kanyashree_number' => $input('kanyashree_number', 'kanyashreeNumber', 'kanyashreeId', 'p_kanyashreenumber'),
            'pwd_document' => $input('pwd_document', 'pwdDocument', 'p_pwddoc'),
            'is_pwd' => $input('is_pwd', 'isPwd', 's_pwd', 'p_ispwd'),
        ];

        $payload['pwd_document'] = $payload['pwd_document'] ?? $payload['physically_challenged_document'];
        $payload['is_pwd'] = $payload['is_pwd'] ?? ($payload['pwd_document'] ? 1 : 0);

        $validator = Validator::make($payload, [
            'student_id' => 'required|integer',
            'admin_user_id' => 'required|integer',
            'admin_user_type' => 'required|integer',
            'date_of_birth' => 'nullable|date',
            'is_married' => 'nullable|integer',
            'is_kanyashree' => 'nullable|integer',
            'is_pwd' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $result = DB::select(
                'SELECT public.fn_updatestudentdetailsbyadmin(
                    ?::bigint, ?::bigint, ?::bigint,
                    ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::date, ?::varchar,
                    ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar,
                    ?::varchar, ?::smallint, ?::smallint, ?::varchar, ?::varchar,
                    ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar,
                    ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar,
                    ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar,
                    ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::smallint
                ) AS data',
                [
                    (int) $payload['student_id'],
                    (int) $payload['admin_user_id'],
                    (int) $payload['admin_user_type'],
                    $payload['first_name'],
                    $payload['middle_name'],
                    $payload['last_name'],
                    $payload['father_name'],
                    $payload['mother_name'],
                    $payload['date_of_birth'],
                    $payload['gender'],
                    $payload['phone'],
                    $payload['email'],
                    $payload['aadhar_no'],
                    $payload['caste'],
                    $payload['address'],
                    $payload['police_station'],
                    $payload['post_office'],
                    $payload['pin'],
                    $payload['is_married'] === null ? null : (int) $payload['is_married'],
                    $payload['is_kanyashree'] === null ? null : (int) $payload['is_kanyashree'],
                    $payload['photo'],
                    $payload['signature'],
                    $payload['guardian_name'],
                    $payload['guardian_relation'],
                    $payload['citizenship'],
                    $payload['state'],
                    $payload['district'],
                    $payload['sub_division'],
                    $payload['municipality_block'],
                    $payload['board_name'],
                    $payload['last_institute'],
                    $payload['passing_year'],
                    $payload['aggregate_marks'],
                    $payload['marks_obtained'],
                    $payload['percentage'],
                    $payload['physics_full_marks'],
                    $payload['chemistry_full_marks'],
                    $payload['biology_math_full_marks'],
                    $payload['physics_marks'],
                    $payload['chemistry_marks'],
                    $payload['biology_math_marks'],
                    $payload['other_qualification'],
                    $payload['citizenship_document'],
                    $payload['caste_document'],
                    $payload['physically_challenged_document'],
                    $payload['aadhar_document'],
                    $payload['kanyashree_document'],
                    $payload['kanyashree_number'],
                    $payload['pwd_document'],
                    $payload['is_pwd'] === null ? null : (int) $payload['is_pwd'],
                ]
            );

            $raw = $result[0]->data ?? null;
            $responseData = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE || $raw === null) {
                return response()->json([
                    'error' => true,
                    'message' => 'Failed to parse student update response from database.',
                ], 500);
            }

            return response()->json([
                'error' => false,
                'message' => 'Student details updated',
                'data' => $responseData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update registered student registration status by admin.
     *
     * Calls: public.fn_updatestudentregistrationstatusbyadmin(
     *   p_studentid, p_adminuserid, p_adminusertype, p_status, p_remarks
     * )
     */
    public function updateStudentRegistrationStatusByAdmin(Request $request)
    {
        $studentId = $request->input('student_id', $request->input('studentId', $request->input('p_studentid')));
        $adminUserId = $request->input('admin_user_id', $request->input('adminUserId', $request->input('p_adminuserid')));
        $adminUserType = $request->input('admin_user_type', $request->input('adminUserType', $request->input('p_adminusertype')));
        $status = $request->input('status', $request->input('p_status'));
        $remarks = $request->input('remarks', $request->input('p_remarks'));

        $validator = Validator::make([
            'student_id' => $studentId,
            'admin_user_id' => $adminUserId,
            'admin_user_type' => $adminUserType,
            'status' => $status,
            'remarks' => $remarks,
        ], [
            'student_id' => 'required|integer',
            'admin_user_id' => 'required|integer',
            'admin_user_type' => 'required|integer',
            'status' => 'required|integer',
            'remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $result = DB::select(
                'SELECT public.fn_updatestudentregistrationstatusbyadmin(?::bigint, ?::bigint, ?::bigint, ?::integer, ?::varchar) AS data',
                [
                    (int) $studentId,
                    (int) $adminUserId,
                    (int) $adminUserType,
                    (int) $status,
                    $remarks,
                ]
            );

            $raw = $result[0]->data ?? null;
            $responseData = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE || $raw === null) {
                return response()->json([
                    'error' => true,
                    'message' => 'Failed to parse student registration status update response from database.',
                ], 500);
            }

            return response()->json([
                'error' => false,
                'message' => 'Student registration status updated',
                'data' => $responseData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

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
