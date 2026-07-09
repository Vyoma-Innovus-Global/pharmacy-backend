<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use STS\ZipStream\Facades\Zip;
use Exception;
use App\Models\User;
use App\Models\Token;
use App\Models\Institute;
use App\Models\Subject;
use App\Models\Elective;
use Illuminate\Support\Str;
use App\Models\Registerstudent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use App\Mail\StudentMail;
use App\Models\Answersheetmasking;

class ReviewController extends Controller
{

    public function getStudentReviewSubject(Request $request)
    {
        try {
            $request->validate([
                'reg_no' => 'required|string',
                'part_sem' => 'required|string',
                'exam_year' => 'required|integer',
            ]);

            $regNo = $request->input('reg_no');
            $partSem = $request->input('part_sem');
            $examYear = $request->input('exam_year');

            // ? Check existing review application
            $existingRecord = DB::table('pharmacy_appl_review_apply')
                ->where('reg_no', $regNo)
                ->where('part_sem', $partSem)
                ->where('exam_year', $examYear)
                ->first();

            // ? Decode paper_code (stored as array like ["655","8766","7666"])
            $appliedPapers = [];
            if ($existingRecord && !empty($existingRecord->paper_code)) {
                $decoded = json_decode($existingRecord->paper_code, true);
                if (is_array($decoded)) {
                    $appliedPapers = array_map('strval', $decoded); // ensure string comparison consistency
                }
            }
            // ? Check active review schedule
            $reviewSchedule = DB::table('pharmacy_schedule_master')
                ->where('schedule_type', 'REVIEW')
                ->where('exam_year', $examYear)
                ->where('part', $partSem)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->first();

            if (!$reviewSchedule) {
                return response()->json([
                    'error' => false,
                    'message' => 'You are not eligible for review at this time.'
                ], 200);
            }

            $res_tbl = null;

            if($reviewSchedule){
                if($reviewSchedule->part == 'Part_I'){
                    $res_tbl = '2024_pharmacy_result_pone';
                }else{
                    $res_tbl = '2024_pharmacy_result_ptwo';
                }
            }

            // ? Fetch student's result
            $studentResult = DB::table($res_tbl)
                ->where('reg_number', $regNo)
                ->where('exam_year', $examYear)
                ->first();

            if (!$studentResult) {
                return response()->json([
                    'error' => false,
                    'message' => 'Result not found for this registration number and semester so you are not allowed'
                ], 200);
            }


            // ? Collect subjects with written marks
            $subjectsWithMarks = [];
            for ($i = 1; $i <= 10; $i++) {
                $codeField = "p{$i}_code";
                $writtenField = "p{$i}_wr_".$examYear;
                if (!empty($studentResult->$codeField)) {
                    $subjectsWithMarks[$studentResult->$codeField] = [
                        'written_mark' => $studentResult->$writtenField ?? null
                    ];
                }
            }

            if (empty($subjectsWithMarks)) {
                return response()->json([
                    'error' => true,
                    'message' => 'No subjects found for this student.'
                ], 404);
            }

            // ? Fetch subject details and merge applied status
            $subjects = DB::table('pharmacy_subjects_master')
                ->whereIn('general_code', array_keys($subjectsWithMarks))
                ->select('general_code', 'subject_name', 'subject_id', 'subject_type', 'paper_type')
                ->get()
                ->map(function ($sub) use ($subjectsWithMarks, $appliedPapers) {
                    return [
                        'general_code' => $sub->general_code,
                        'subject_name' => $sub->subject_name,
                        'subject_id' => $sub->subject_id,
                        'subject_type' => $sub->subject_type,
                        'paper_type' => $sub->paper_type,
                        'written_mark' => $subjectsWithMarks[$sub->general_code]['written_mark'] ?? null,
                        'is_applied' => in_array((string)$sub->general_code, $appliedPapers),
                    ];
                });

            return response()->json([
                'error' => false,
                'reg_no' => $regNo,
                'part_sem' => $partSem,
                'subjects' => $subjects,
                'is_applied' => $existingRecord ? true : false,
                'is_payment_done' => $existingRecord && $existingRecord->payment_transactions_id ? true : false,
                'academic_session' => $reviewSchedule->academic_session,
                'exam_year' => $examYear,
                'roll' => $studentResult->roll_number,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get teacher list for a review subject.
     *
     * Calls: public.fn_get_teacherlistofreviewsubject(
     *   p_studentid, p_examyear, p_partid, p_subjectcode, p_instcode
     * )
     */
    public function getTeacherListOfReviewSubject(Request $request)
    {
        $studentId = $request->input('student_id', $request->input('p_studentid'));
        $examYear = $request->input('exam_year', $request->input('p_examyear'));
        $partId = $request->input('part_id', $request->input('p_partid', $request->input('part_sem')));
        $subjectCode = $request->input('subject_code', $request->input('p_subjectcode'));
        $instCode = $request->input('inst_code', $request->input('p_instcode', $request->input('instcode')));

        $validator = Validator::make([
            'student_id' => $studentId,
            'exam_year' => $examYear,
            'part_id' => $partId,
            'subject_code' => $subjectCode,
            'inst_code' => $instCode,
        ], [
            'student_id' => 'required|integer',
            'exam_year' => 'required|string|max:20',
            'part_id' => 'required|string|max:20',
            'subject_code' => 'required|string|max:50',
            'inst_code' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $result = DB::select(
                'SELECT public.fn_get_teacherlistofreviewsubject(?::bigint, ?::varchar, ?::varchar, ?::varchar, ?::varchar) AS data',
                [
                    (int) $studentId,
                    trim((string) $examYear),
                    trim((string) $partId),
                    trim((string) $subjectCode),
                    trim((string) $instCode),
                ]
            );

            if (empty($result)) {
                return response()->json([
                    'error' => true,
                    'message' => 'No teacher data found.',
                ], 404);
            }

            $raw = $result[0]->data ?? null;

            if ($raw === null) {
                return response()->json([
                    'error' => true,
                    'message' => 'No teacher data found.',
                ], 404);
            }

            $teacherData = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'error' => true,
                    'message' => 'Failed to parse teacher list from database.',
                ], 500);
            }

            return response()->json([
                'error' => false,
                'message' => 'Data found',
                'data' => $teacherData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function applyForReview(Request $request)
    {
        try {
            // ? Step 1: Basic validation
            $request->validate([
                'form_num' => 'required|string',
                'reg_no' => 'required|string',
                'part_sem' => 'required|string',
                'academic_session' => 'required|string',
                'exam_year' => 'required|integer',
                'roll' => 'required|string',
            ]);

            // ? Step 2: Check for existing record
            $existingRecord = DB::table('pharmacy_appl_review_apply')
                ->where('form_num', $request->form_num)
                ->where('reg_no', $request->reg_no)
                ->where('part_sem', $request->part_sem)
                ->where('academic_session', $request->academic_session)
                ->where('exam_year', $request->exam_year)
                ->where('roll_number', $request->roll)
                ->first();

            if (!$existingRecord) {
                $request->validate([
                    'paper_code' => 'required|string'
                ]);
            }

            // ? Step 3: Verify student
            $student = Registerstudent::where('s_appl_form_num', $request->form_num)->first();
            if (!$student) {
                return response()->json([
                    'error' => true,
                    'message' => 'Invalid form number.'
                ], 404);
            }
            $res_tbl = null;

            if($request->part_sem == 'Part_I'){
                $res_tbl = '2024_pharmacy_result_pone';
            }else{
                $res_tbl = '2024_pharmacy_result_ptwo';
            }
            // ? Step 4: Verify result
            $studentResult = DB::table($res_tbl)
                ->where('reg_number', $request->reg_no)
                ->where('exam_year', $request->exam_year)
                ->first();

            if (!$studentResult) {
                return response()->json([
                    'error' => true,
                    'message' => 'Invalid registration number.'
                ], 404);
            }

            // ? Step 5: Convert paper_code string to JSON array
            // Example input: "5422,5423,5424" ? ["5422", "5423", "5424"]
            $paperCodeArray = collect(explode(',', $request->paper_code))
                ->map(fn($code) => trim($code))
                ->filter()
                ->values()
                ->all();

            // ? Step 6: Limit max 3 selections
            if (count($paperCodeArray) > 3) {
                return response()->json([
                    'error' => true,
                    'message' => 'You can apply for review for a maximum of 3 papers only.'
                ], 400);
            }

            // ? Step 7: Prepare data for insert/update
            $insertOrUpdateData = [
                'form_num' => $request->form_num,
                'academic_session' => $request->academic_session,
                'part_sem' => $request->part_sem,
                'reg_no' => $request->reg_no,
                'roll_number' => $request->roll,
                'exam_year' => $request->exam_year,
                'paper_code' => json_encode($paperCodeArray, JSON_UNESCAPED_UNICODE),
                'payment_transactions_id' => null,
                'review_status' => count($paperCodeArray) > 0?1:0,
                'updated_on' => now(),
                'update_by' => $student->s_id,
            ];

            // ? Step 8: Insert or update record
            if ($existingRecord) {
                DB::table('pharmacy_appl_review_apply')
                    ->where('id', $existingRecord->id)
                    ->update($insertOrUpdateData);

                auditTrail($request->form_num, "updated review application: {$request->paper_code}");
                $message = 'Review application updated successfully.';
            } else {
                DB::table('pharmacy_appl_review_apply')->insert($insertOrUpdateData);

                auditTrail($request->form_num, "applied for review: {$request->paper_code}");
                $message = 'Review application submitted successfully.';
            }

            // ? Step 9: Return success
            return response()->json([
                'error' => false,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            // ? Step 10: Handle exceptions
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function reviewReceipt(Request $request)
    {
        $form_num = $request->form_num;
        $part_sem = $request->part_sem;
        $exam_year = $request->exam_year;

        // ? Fetch review application data
        $data = DB::table('pharmacy_appl_review_apply')
            ->where('pharmacy_appl_review_apply.form_num', $form_num)
            ->where('pharmacy_appl_review_apply.part_sem', $part_sem)
            ->where('pharmacy_appl_review_apply.exam_year', $exam_year)
            ->where('pharmacy_appl_review_apply.review_status', 2)
            ->leftJoin(
                "pharmacy_student_extraInfo",
                "pharmacy_student_extraInfo.ei_appl_form_num",
                '=',
                'pharmacy_appl_review_apply.form_num'
            )
            ->leftJoin(
                "pharmacy_register_student_final",
                "pharmacy_register_student_final.s_appl_form_num",
                '=',
                'pharmacy_appl_review_apply.form_num'
            )
            ->leftJoin('pharmacy_payment_transactions', function ($join) {
                $join->whereRaw("pharmacy_appl_review_apply.form_num = ANY (string_to_array(pharmacy_payment_transactions.form_no, ','))");
            })
            ->select(
                'pharmacy_appl_review_apply.academic_session',
                'pharmacy_appl_review_apply.payment_transactions_id',
                'pharmacy_appl_review_apply.exam_year',
                'pharmacy_appl_review_apply.reg_no',
                'pharmacy_appl_review_apply.roll_number',
                'pharmacy_appl_review_apply.paper_code',
                'pharmacy_register_student_final.s_appl_form_num',
                'pharmacy_register_student_final.s_candidate_name',
                'pharmacy_student_extraInfo.ei_guardian_name',
                'pharmacy_register_student_final.s_dob',
                'pharmacy_register_student_final.s_phone',
                'pharmacy_register_student_final.s_caste',
                'pharmacy_register_student_final.s_pwd',
                'pharmacy_register_student_final.s_inst_code',
                'pharmacy_payment_transactions.trans_amount',
                'pharmacy_payment_transactions.trans_mode',
                'pharmacy_payment_transactions.trans_time',
                'pharmacy_payment_transactions.trans_id',
                'pharmacy_payment_transactions.order_id'
            )
            ->first();

        if (!$data) {
            return response()->json(['error' => 'No data found.'], 404);
        }

        // ? Decode paper_code JSON (now stored as array like ["655","8766","7666"])
        $paperCodes = json_decode($data->paper_code, true);

        // ? Ensure it's a valid array
        $appliedCodes = [];
        if (is_array($paperCodes)) {
            $appliedCodes = array_map('intval', $paperCodes); // Convert all to integers
        }

        // ? Fetch subject details for these paper codes
        $subjects = [];
        if (!empty($appliedCodes)) {
            $subjects = DB::table('pharmacy_subjects_master')
                ->whereIn('general_code', $appliedCodes)
                ->select('general_code', 'subject_name', 'subject_id', 'subject_type', 'paper_type')
                ->get();
        }

        // ? Attach subjects to data object
        $data->subjects = $subjects;

        // ? Generate and stream PDF
        $pdf = Pdf::loadView('review_fees', ['data' => $data]);
        return $pdf->stream('review_fees.pdf');
    }

    public function getReviewList(Request $request)
    {
        try {
            $request->validate([
                'exam_year' => 'required',
                'part_sem' => 'required',
            ]);

            $part_sem  = $request->part_sem;
            $exam_year = $request->exam_year;

            // Fetch review list
            $student_review_list = DB::table('pharmacy_appl_review_apply')
                ->where('part_sem', $part_sem)
                ->where('exam_year', $exam_year)
                ->where('review_status', 2)
                ->whereNotNull('payment_transactions_id')
                ->select('reg_no', 'roll_number', 'exam_year', 'part_sem', 'paper_code')
                ->get();

            // Collect all paper codes from all students
            $allPaperCodes = [];

            foreach ($student_review_list as $student) {
                $codes = json_decode($student->paper_code, true);
                if (is_array($codes)) {
                    $allPaperCodes = array_merge($allPaperCodes, $codes);
                }
            }

            // Remove duplicates and ensure they are integers
            $allPaperCodes = array_unique(array_map('intval', $allPaperCodes));

            // Fetch subjects related to all paper codes
            $subjects = [];
            if (!empty($allPaperCodes)) {
                $subjects = DB::table('pharmacy_subjects_master')
                    ->whereIn('general_code', $allPaperCodes)
                    ->select('general_code', 'subject_name')
                    ->get()
                    ->keyBy('general_code'); // So we can map easily later
            }

            // Attach subjects to each student based on their paper_code
            foreach ($student_review_list as $student) {
                $codes = json_decode($student->paper_code, true);
                $studentSubjects = [];

                if (is_array($codes)) {
                    foreach ($codes as $code) {
                        if (isset($subjects[$code])) {
                            $studentSubjects[] = $subjects[$code];
                        }
                    }
                }

                $student->subjects = $studentSubjects;
            }

            return response()->json([
                'error' => false,
                'message' => 'Review list found',
                'data' => $student_review_list,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching review list: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Unable to fetch review list',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    public function marksEntryVerifyInstituteList(Request $request)
    {

        try {
            $request->validate([
            'exam_year' => 'required',
            'part_sem' => 'required',
            'subject' => 'required',
            'marks_type' => 'required',
            'u_role_id' => 'required'
        ]);

        $exam_year = $request->exam_year;
        $part_sem = $request->part_sem;
        $subject = $request->subject;
        $marks_type = $request->marks_type;
        $u_role_id = $request->u_role_id;
        $table_name = $part_sem == 'Part_I' ? 'exam_attendance_pone' : 'exam_attendance_ptwo';

        $data = DB::table($table_name)
            ->select('ea_inst_code', 'i_name')
            ->where('ea_exam_year', $exam_year)
            ->where('ea_part_sem', $part_sem)
            ->where('ea_subject_code', $subject)
            # ->where('ea_exam_year' ,$marks_type )
            ->join('institute_master', 'i_code', '=', 'ea_inst_code')
            ->groupBy('ea_inst_code', 'i_name')
            ->get();

        $institute_data = $data->map(function ($item) {
            return [
                'institute_code' => $item->ea_inst_code,
                'institute_name' => $item->i_name
            ];
        });
        if($institute_data){
            return response()->json([
                'error' => false,
                'data' => $institute_data,
                'message' => 'Data available'
            ], 200);
        }
        return response()->json([
            'error' => false,
            'message' => 'No Data available'
        ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    function marksEntryVerifySubjectList(Request $request)
    {
        try {
            $part_sem = $request->input('part_sem');
            $sub_type = $request->input('subject_type');

            $subjectList = DB::table('pharmacy_subjects_master')
                ->where('semester', $part_sem)
                ->where('subject_type', $sub_type)
                ->where('type', 'Main')
                ->where('dept', 'PHARM')
                ->select('subject_id', 'general_code', 'q_code', 'subject_name', 'subject_type',)
                ->orderBy('subject_sn', 'ASC')
                ->get();

            return response()->json([
                'error' => false,
                'subjectList' => $subjectList
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getAttendance($data, $marks_type, $type)
    {
        if ($marks_type === 'mark_internal') {
            return (bool) $data->{"ea_is_{$type}_internal"};
        }

        if ($marks_type === 'mark_external') {
            return (bool) $data->{"ea_is_{$type}_external"};
        }
    }

    private function getMarks($data, $marks_type, $evaluator_type)
    {
        $exam_type  =   explode('_', $marks_type)[1];

        $evaluator_type_lower = strtolower($evaluator_type);

        $marks_key  =   "em_".$exam_type."_marks_".$evaluator_type_lower;
        return $data->{$marks_key};
    }

    private function getRevMarks($data, $marks_type, $evaluator_type)
    {
        $exam_type  =   explode('_', $marks_type)[1];

        $evaluator_type_lower = strtolower($evaluator_type);

        $marks_key  =   "em_".$exam_type."_marks_rev_".$evaluator_type_lower;
        return $data->{$marks_key};
    }

    public function marksEntryList(Request $request)
    {
        $request->validate([
            'exam_year' => 'required',
            'part_sem'  => 'required',
            'subject'   => 'required',
            'marks_type'=> 'required',
            'inst_code' => 'required',
            'u_role_id' => 'required'
        ]);

        $exam_year      = $request->exam_year;
        $part_sem       = $request->part_sem;
        $subject        = $request->subject;
        $marks_type     = $request->marks_type;
        $inst_code      = $request->inst_code;
        $u_role_id      = $request->u_role_id;
        $session_year   = $request->session_year;
        $marks_type     = $request->marks_type;
        $evaluator_type = $request->evaluator_type;

        $masking_status = Answersheetmasking::query()
                    ->where([
                        ['pams_exam_year', '=', $exam_year],
                        ['pams_part_sem', '=', $part_sem],
                        ['pams_is_active', '=', 1],
                        ['pams_masking_active','=', 1],
                        ['pams_subject_code', '=', $subject]
                    ])->count() ;

        $evaluator_type = $request->evaluator_type;

        $type           = explode('_', $marks_type)[1];
        $ev_type = strtolower($evaluator_type);
        // check attendance final submit

        $table_name = $part_sem == 'Part_I' ? 'exam_attendance_pone' : 'exam_attendance_ptwo';
        $marks_tbl = $part_sem == 'Part_I' ? 'pharmacy_exam_marks_pone' : 'pharmacy_exam_marks_ptwo';
        $marks_rev_tbl = $part_sem == 'Part_I' ? 'pharmacy_exam_marks_pone_review' : 'pharmacy_exam_marks_ptwo_review';

        $subject_marks = DB::table('pharmacy_subjects_master')
            ->where('general_code', $subject)
            #->where('syllabus', $session_year )
            ->where('semester', $part_sem)
            ->where('is_active', 1)
            ->first();
        $max_marks = $subject_marks->{"subject_{$type}_total_marks"};

        $list = DB::table("$table_name as ea")
            ->join('pharmacy_register_student_final as rsf', 'rsf.s_appl_form_num', '=', 'ea.ea_form_number')
            ->leftJoin("$marks_tbl as m", function ($join) use ($subject, $part_sem, $exam_year) {
                $join->on('m.em_roll_number', '=', 'ea.ea_roll_number')
                    ->where('m.em_subject_code', '=', $subject)
                    ->where('m.em_part_sem', '=', $part_sem)
                    ->where('m.em_exam_year', '=', $exam_year);
            })
            ->leftJoin("$marks_rev_tbl as mr", function ($join) use ($subject, $part_sem, $exam_year) {
                $join->on('mr.em_roll_number', '=', 'ea.ea_roll_number')
                    ->where('mr.em_subject_code', '=', $subject)
                    ->where('mr.em_part_sem', '=', $part_sem)
                    ->where('mr.em_exam_year', '=', $exam_year);
            })
            ->where('ea.ea_exam_year', $exam_year)
            ->where('ea.ea_part_sem', $part_sem)
            ->where('ea.ea_subject_code', $subject)
            ->where('ea.ea_inst_code', $inst_code)
            ->whereRaw("(ea.ea_is_final_submit::json->>'is_{$type}')::boolean = true")
            ->whereExists(function ($query) use ($exam_year, $part_sem, $subject) {
                $query->select(DB::raw(1))
                    ->from('pharmacy_appl_review_apply')
                    ->whereColumn('pharmacy_appl_review_apply.roll_number', 'ea.ea_roll_number')
                    ->where('pharmacy_appl_review_apply.exam_year', $exam_year)
                    ->where('pharmacy_appl_review_apply.part_sem', $part_sem)
                    ->whereRaw("pharmacy_appl_review_apply.paper_code ILIKE '%' || ? || '%'", [$subject]);
            })
            ->select(
                'ea.*',
                'rsf.s_candidate_name',
                'rsf.s_appl_reg_no',
                'm.*',
                'mr.*'
            )
            ->get();

        $marksCount = DB::table($marks_rev_tbl)
                    ->where('em_subject_code', $subject)
                    ->where('em_part_sem', $part_sem)
                    ->where('em_exam_year', $exam_year)
                    ->whereNotNull("em_{$type}_marks_rev_{$ev_type}")
                    ->count();

        if (!count($list)) {
            return response()->json([
                'error' => true,
                'message' => 'Attendance not finally submitted',
            ], 200);
        }
        $total_candidate    = 0 ;
        $masked_candiadte   = 0;

        $total_present_candidated   = 0;
        $marks_enty_candidate       =   0;

        $list = $list->map(function ($item) use ($max_marks, $marks_type, $evaluator_type, $masking_status,
         &$total_candidate, &$masked_candiadte,
         &$total_present_candidated, &$marks_enty_candidate,

         ) {
            // dd($item);
            $is_present = $this->getAttendance($item, $marks_type, 'present');
            $is_absent = $this->getAttendance($item, $marks_type, 'absent');
            $is_ra = $this->getAttendance($item, $marks_type, 'ra');
            $marks = $this->getMarks($item, $marks_type, $evaluator_type);
            $revMarks = $this->getRevMarks($item, $marks_type, $evaluator_type);
            $final_marks   = !is_null($marks) ? $marks : ($is_absent ? 'AB' : ($is_ra ? 'RA' : null));
            $final_rev_marks   = !is_null($revMarks) ? $revMarks : ($is_absent ? 'AB' : ($is_ra ? 'RA' : null));

            $examener_save_status   = $item->is_final_examiner;
            $is_final_scrutinizer   =   $item->is_final_scrutinizer;

            if( $evaluator_type=='SCRUTINIZER'){
                $marks_examiner = $this->getMarks($item, $marks_type,'EXAMINER');
                //for checking exameaner marks entry count
                if(  !is_null($marks_examiner) && $is_present  ){
                    $marks_enty_candidate++;
                }
                //first time SCRUTINIZER marks set by  examiner
                if(is_null($marks)){
                    $final_marks = $marks_examiner;
                    $final_rev_marks = $marks_examiner;
                }


            }

            $evaluator = strtolower($evaluator_type);
            $total_candidate++;

            if( $item->ea_masking_code ==''){
                $masked_candiadte++;
            }

            if( $is_present){
                $total_present_candidated++;
            }



            return [
                'reg_no'=> $item->s_appl_reg_no,
                'stu_name'=> $item->s_candidate_name,
                'roll_no'=> $item->ea_roll_number,

                'is_present'=> $is_present,
                'is_absent'=> $is_absent,
                'is_ra'=> $is_ra,

                'is_final'=> json_decode($item->{"is_final_rev_$evaluator"}, true),
                "marks"=> $final_marks,
                "rev_marks"=> $final_rev_marks,
                "max_marks"=> $max_marks,

            ];
        });

        //masking data validation
        if($masking_status){
            if($masked_candiadte>0){
                if($marks_type =='mark_external'){
                    return response()->json([
                                        'error' => true,
                                        'message' => 'Answersheet serial not generated'
                                    ], 200);
                }
            }
        }

        //validation for SCRUTINIZER if all candidate marks marks not entyerd by examener
        if( ($evaluator_type=='SCRUTINIZER') && ($total_present_candidated != $marks_enty_candidate)){
            return response()->json([
            'error' => true,
            'message' => 'Required to submit Examiner marks',
            'data'  =>  $total_present_candidated.' != '.$marks_enty_candidate
        ], 200);
        }
        if($list){
            return response()->json([
                'error' => false,
                'students' => $list,
                'max_marks' => $max_marks,
                'marks_entry' => $marksCount,
                'message' => 'Data available'
            ], 200);
        }
        return response()->json([
            'error' => false,
            'message' => 'No Data available'
        ], 200);

    }

    public function marksEntrySubmit(Request $request)
    {
        $save_type      = $request->save_type;
        $other          = $request->other_data;
        $user_id        = $other['user_id'];
        $session_year   = $other['session_year'];
        $exam_year      = $other['exam_year'];
        $part_sem       = $other['part_sem'];
        $subject_code   = $other['subject_code'];
        $mark_type      = $other['mark_type'];
        $inst_code      = $other['inst_code'];
        $evaluator_type = $other['evaluator_type'];


        $evaluator_type = $other['evaluator_type'];

        $type           = explode('_', $mark_type)[1];
        $evaluator      = strtolower($evaluator_type);

        try {
            DB::beginTransaction();
            $table_name = $part_sem == 'Part_I' ? 'pharmacy_exam_marks_pone_review' : 'pharmacy_exam_marks_ptwo_review';
            foreach ($request->student_data as $student) {
                $reg_no     = $student['reg_no'];
                $roll_no    = $student['roll_no'];
                $marks      = $student['rev_marks'];
                $is_final   = $student['is_final'];

                $where = [
                    'em_academic_session'   => $session_year,
                    'em_exam_year'          => $exam_year,
                    'em_inst_code'          => $inst_code,
                    'em_reg_no'             => $reg_no,
                    'em_roll_number'        => $roll_no,
                    'em_part_sem'           => $part_sem,
                    'em_subject_code'       => $subject_code,
                ];

                $update = [
                    "em_{$type}_marks_rev_{$evaluator}" => $marks,
                ];

                if ($save_type === 'FINAL_SUBMIT') {
                    $update["is_final_rev_{$evaluator}"] = json_encode($is_final);
                    $action = 'Finally submitted';
                } else {
                    $action = 'Submitted';
                }
                DB::table($table_name)->updateOrInsert($where, $update);
                auditTrail($user_id, "Student of {$part_sem} for {$subject_code}, {$mark_type} of registration no {$reg_no} {$action}");
            }

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => 'Review Marks ' .$action. ' Successfully',
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function MarksVerifyhoeList(Request $request)
    {
        $request->validate([
            'exam_year' => 'required',
            'part_sem'  => 'required',
            'subject'   => 'required',
            'marks_type'=> 'required',
            'inst_code' => 'required',
            'u_role_id' => 'required'
        ]);

        $exam_year      = $request->exam_year;
        $part_sem       = $request->part_sem;
        $subject        = $request->subject;
        $marks_type     = $request->marks_type;
        $inst_code      = $request->inst_code;
        $u_role_id      = $request->u_role_id;
        $session_year   = $request->session_year;
        $marks_type     = $request->marks_type;
        $evaluator_type = $request->evaluator_type;

        $masking_status = Answersheetmasking::query()
                    ->where([
                        ['pams_exam_year', '=', $exam_year],
                        ['pams_part_sem', '=', $part_sem],
                        ['pams_is_active', '=', 1],
                        ['pams_masking_active','=', 1],
                        ['pams_subject_code', '=', $subject]
                    ])->count() ;


        $evaluator_type = $request->evaluator_type;

        $type           = explode('_', $marks_type)[1];
        $ev_type = strtolower($evaluator_type);
        // check attendance final submit

        $table_name = $part_sem == 'Part_I' ? 'exam_attendance_pone' : 'exam_attendance_ptwo';
        $marks_tbl = $part_sem == 'Part_I' ? 'pharmacy_exam_marks_pone' : 'pharmacy_exam_marks_ptwo';
        $marks_rev_tbl = $part_sem == 'Part_I' ? 'pharmacy_exam_marks_pone_review' : 'pharmacy_exam_marks_ptwo_review';

        $subject_marks = DB::table('pharmacy_subjects_master')
            ->where('general_code', $subject)
            ->where('semester', $part_sem)
            ->where('is_active', 1)
            ->first();

        $max_marks = $subject_marks->{"subject_{$type}_total_marks"};

        $list = DB::table("$table_name as ea")
            ->join('pharmacy_register_student_final as rsf', 'rsf.s_appl_form_num', '=', 'ea.ea_form_number')
            ->leftJoin("$marks_tbl as m", function ($join) use ($subject, $part_sem, $exam_year) {
                $join->on('m.em_roll_number', '=', 'ea.ea_roll_number')
                    ->where('m.em_subject_code', '=', $subject)
                    ->where('m.em_part_sem', '=', $part_sem)
                    ->where('m.em_exam_year', '=', $exam_year);
            })
            ->leftJoin("$marks_rev_tbl as mr", function ($join) use ($subject, $part_sem, $exam_year) {
                $join->on('mr.em_roll_number', '=', 'ea.ea_roll_number')
                    ->where('mr.em_subject_code', '=', $subject)
                    ->where('mr.em_part_sem', '=', $part_sem)
                    ->where('mr.em_exam_year', '=', $exam_year);
            })
            ->where('ea.ea_exam_year', $exam_year)
            ->where('ea.ea_part_sem', $part_sem)
            ->where('ea.ea_subject_code', $subject)
            ->where('ea.ea_inst_code', $inst_code)
            ->whereRaw("(ea.ea_is_final_submit::json->>'is_{$type}')::boolean = true")
            ->whereExists(function ($query) use ($exam_year, $part_sem, $subject) {
                $query->select(DB::raw(1))
                    ->from('pharmacy_appl_review_apply')
                    ->whereColumn('pharmacy_appl_review_apply.roll_number', 'ea.ea_roll_number')
                    ->where('pharmacy_appl_review_apply.exam_year', $exam_year)
                    ->where('pharmacy_appl_review_apply.part_sem', $part_sem)
                    ->whereRaw("pharmacy_appl_review_apply.paper_code ILIKE '%' || ? || '%'", [$subject]);
            })
            ->select(
                'ea.*',
                'rsf.s_candidate_name',
                'rsf.s_appl_reg_no',
                'm.*',
                'mr.*'
            )
            ->get();

        $marksCount = DB::table($marks_rev_tbl)
                    ->where('em_subject_code', $subject)
                    ->where('em_part_sem', $part_sem)
                    ->where('em_exam_year', $exam_year)
                    ->whereNotNull("em_{$type}_marks_rev_{$ev_type}")
                    ->count();

        if (!count($list)) {
            return response()->json([
                'error' => true,
                'message' => 'Attendance not finally submitted',
            ], 200);
        }
        $total_candidate    = 0 ;
        $masked_candiadte   = 0;

        $total_present_candidated   = 0;
        $marks_enty_candidate       =   0;

        $list = $list->map(function ($item) use ($max_marks, $marks_type, $evaluator_type, $masking_status,
         &$total_candidate, &$masked_candiadte,
         &$total_present_candidated, &$marks_enty_candidate,

         ) {
            // dd($item);
            $is_present = $this->getAttendance($item, $marks_type, 'present');
            $is_absent = $this->getAttendance($item, $marks_type, 'absent');
            $is_ra = $this->getAttendance($item, $marks_type, 'ra');
            $marks = $this->getMarks($item, $marks_type, $evaluator_type);
            $revMarks = $this->getRevMarks($item, $marks_type, $evaluator_type);
            $hoe_marks  = $this->getRevMarks($item, $marks_type, $evaluator_type);
         #   $final_marks   = !is_null($marks) ? $marks : ($is_absent ? 'AB' : ($is_ra ? 'RA' : null));

            $marks_examiner = $this->getRevMarks($item, $marks_type,'EXAMINER');
            $marks_scrutinizer = $this->getRevMarks($item, $marks_type,'SCRUTINIZER');

            $examener_save_status   = $item->is_final_examiner;
            $is_final_scrutinizer   =   $item->is_final_scrutinizer;


            $evaluator = strtolower($evaluator_type);
            $total_candidate++;

            if( $item->ea_masking_code ==''){
                $masked_candiadte++;
            }

            if( $is_present){
                $total_present_candidated++;
            }
            $mismatch   =   false;
            if(is_null($marks) ){
                if( $marks_scrutinizer == $marks_examiner ){

                    $marks  =   $marks_scrutinizer;
                }   else{
                   $mismatch = true;
                }
            }else{
               # $marks  =   1;
            }


            return [
                'reg_no'=> $item->s_appl_reg_no,
                'stu_name'=> $item->s_candidate_name,
                'roll_no'=> $item->ea_roll_number,

                'is_present'=> $is_present,
                'is_absent'=> $is_absent,
                'is_ra'=> $is_ra,

                'is_final'=> json_decode($item->{"is_final_rev_$evaluator"}, true),
                "marks"=> $marks,
                "rev_hoe_marks"   => $hoe_marks ,
                "eve_type"  => $evaluator_type,
                "scrutinizer_marks" => $marks_scrutinizer,
                "examiner_marks"    =>  $marks_examiner,
                "max_marks"=> $max_marks,
                "mismatch"          =>  $mismatch,
                "examener_save_status" =>$examener_save_status,
                "is_final_scrutinizer"  => $is_final_scrutinizer
            ];
        });

        //masking data validation
        if($masking_status){
            if($masked_candiadte>0){
                if($marks_type =='mark_external'){
                    return response()->json([
                                        'error' => true,
                                        'message' => 'Answersheet serial not generated'
                                    ], 200);
                }
            }
        }

        //validation for SCRUTINIZER if all candidate marks marks not entyerd by examener
        if( ($evaluator_type=='SCRUTINIZER') && ($total_present_candidated != $marks_enty_candidate)){
            return response()->json([
            'error' => true,
            'message' => 'Required to submit Examiner marks',
            'data'  =>  $total_present_candidated.' != '.$marks_enty_candidate
        ], 200);
        }

        if($list){
            return response()->json([
                'error' => false,
                'students' => $list,
                'max_marks' => $max_marks,
                'marks_entry' => $marksCount,
                'message' => 'Data available'
            ], 200);
        }


        return response()->json([
            'error' => false,
            'message' => 'No Data available'
        ], 200);
    }

    public function savePharmacyReviewSubject(Request $request)
    {
        $items = $request->json()->all();

        if (isset($items['subjects']) && is_array($items['subjects'])) {
            $items = $items['subjects'];
        } elseif (isset($items['data']) && is_array($items['data'])) {
            $items = $items['data'];
        }

        if (!is_array($items)) {
            return response()->json([
                'error' => true,
                'message' => 'Request body must be a JSON array of review subject objects.',
            ], 422);
        }

        if (empty($items)) {
            return response()->json([
                'error' => true,
                'message' => 'At least one review subject is required.',
            ], 422);
        }

        if (array_keys($items) !== range(0, count($items) - 1)) {
            return response()->json([
                'error' => true,
                'message' => 'Request body must be a JSON array of review subject objects.',
            ], 422);
        }

        $normalized = [];

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                return response()->json([
                    'error' => true,
                    'message' => "Item {$index} must be an object.",
                ], 422);
            }

            $row = [
                'student_id' => $item['student_id'] ?? $item['studentId'] ?? $item['p_student_id'] ?? null,
                'reg_no' => $item['reg_no'] ?? $item['regNo'] ?? $item['registration_number'] ?? $item['registrationNumber'] ?? $item['p_reg_no'] ?? null,
                'semester' => $item['semester'] ?? $item['part_id'] ?? $item['partId'] ?? $item['p_semester'] ?? null,
                'exam_year' => $item['exam_year'] ?? $item['examYear'] ?? $item['p_exam_year'] ?? null,
                'inst_code' => $item['inst_code'] ?? $item['instCode'] ?? $item['institute_code'] ?? $item['instituteCode'] ?? $item['p_inst_code'] ?? null,
                'subject_code' => $item['subject_code'] ?? $item['subjectCode'] ?? $item['SubjectCode'] ?? $item['p_subject_code'] ?? null,
                'order_id' => $item['order_id'] ?? $item['orderId'] ?? $item['p_order_id'] ?? null,
                'subject_name' => $item['subject_name'] ?? $item['subjectName'] ?? $item['SubjectName'] ?? $item['p_subject_name'] ?? null,
                'create_by' => $item['create_by'] ?? $item['createBy'] ?? $item['admin_user_id'] ?? $item['adminUserId'] ?? $item['p_create_by'] ?? null,
            ];

            $validator = Validator::make($row, [
                'student_id' => 'required|integer',
                'reg_no' => 'required|string|max:50',
                'semester' => 'required|integer',
                'exam_year' => 'required|integer',
                'inst_code' => 'required|string|max:50',
                'subject_code' => 'required|string|max:50',
                'order_id' => 'required|string|max:100',
                'subject_name' => 'required|string|max:255',
                'create_by' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => "Validation failed for item {$index}.",
                    'data' => $validator->errors(),
                ], 422);
            }

            $normalized[] = $row;
        }

        try {
            DB::beginTransaction();

            $responses = [];

            foreach ($normalized as $index => $row) {
                $result = DB::select(
                    'SELECT public.fn_save_pharmacy_review_subject(?::bigint, ?::varchar, ?::integer, ?::integer, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::bigint) AS data',
                    [
                        (int) $row['student_id'],
                        trim($row['reg_no']),
                        (int) $row['semester'],
                        (int) $row['exam_year'],
                        trim($row['inst_code']),
                        trim($row['subject_code']),
                        trim($row['order_id']),
                        trim($row['subject_name']),
                        (int) $row['create_by'],
                    ]
                );

                $responses[] = [
                    'index' => $index,
                    'subject_code' => $row['subject_code'],
                    'response' => $this->decodeReviewSubjectFunctionJson($result[0]->data ?? null),
                ];
            }

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => 'Review subjects saved successfully.',
                'data' => $responses,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('daily')->error('[Review] fn_save_pharmacy_review_subject EXCEPTION', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Failed to save review subjects.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    private function decodeReviewSubjectFunctionJson($raw)
    {
        if ($raw === null) {
            return null;
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
        }

        return (array) $raw;
    }

}
