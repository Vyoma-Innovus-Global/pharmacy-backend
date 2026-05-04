<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Token;
use App\Models\Institute;
use App\Models\Enrollment;
use App\Models\Registerstudent;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class EnrollmentController extends Controller
{
    public function list(Request $request)
    {
                        DB::beginTransaction();

                        try {
                            $institute_code = trim($request->institute_code);
                            $type = $request->type;
                            $part_sem=$request->part_sem;
                            $enroll_year = (int) $request->enroll_year;

                            if ($type == 'REGULAR') {
                                $currentYear = (int) date("Y");
                                if ($enroll_year !== $currentYear) {
                                    return response()->json([
                                        'error' => true,
                                        'message' => 'REGULAR students can only be enrolled for the current academic year.'
                                    ]);
                                }
                                if($part_sem=="Part_I")
                                {
                                    $partField='s_part1_status';
                                } else {
                                     $partField='s_part2_status';
                                }
                                $query = Registerstudent::join('institute_master', 'pharmacy_register_student_final.s_inst_code', '=', 'institute_master.i_code')
                                    ->leftJoin('pharmacy_enrollment', 'pharmacy_register_student_final.s_appl_form_num', '=', 'pharmacy_enrollment.enrl_form_num')
                                    ->where('s_part_sem',$part_sem)
                                    ->where('pharmacy_register_student_final.is_registration_payment', 1)
                                    ->where('pharmacy_register_student_final.is_active', 1)
                                    ->whereNotNull('pharmacy_register_student_final.s_appl_reg_no')
                                    ->whereRaw("CAST(NULLIF(SPLIT_PART(pharmacy_register_student_final.s_appl_reg_year, '-', 2), '') AS INTEGER) = ?", [$enroll_year])
                                    ->whereBetween('s_part1_status', [2, 8])

                                    ->orderBy('s_id', 'desc');

                                if (!empty($institute_code)) {
                                    $query->where('pharmacy_register_student_final.s_inst_code', $institute_code);
                                }

                                // re-admission
                                $query2 = Registerstudent::join('institute_master', 'pharmacy_register_student_final.s_inst_code', '=', 'institute_master.i_code')
                                    ->leftJoin('pharmacy_enrollment', 'pharmacy_register_student_final.s_appl_form_num', '=', 'pharmacy_enrollment.enrl_form_num')
                                    ->where('pharmacy_register_student_final.re_admission_date', $enroll_year)
                                    ->where('pharmacy_register_student_final.re_admission_status', 1)
                                    ->orderBy('s_id', 'desc');

                                if (!empty($institute_code)) {
                                    $query2->where('pharmacy_register_student_final.s_inst_code', $institute_code);
                                }

                                $subjects = DB::table('pharmacy_subjects_master')
                                    ->where([
                                        ['dept', 'PHARM'],
                                        ['semester', 'Part_I'],
                                        ['subject_type', 'Theoretical'],
                                        ['is_active', 1],
                                    ])
                                    ->select('general_code as code', 'subject_name')
                                    ->get();

                                $totalPaper = $subjects->map(fn($item) => [
                                    'code' => $item->code,
                                    'subject_name' => $item->subject_name,
                                ])->values()->toArray();

                                $enrollmentdata = $query->get()
                                    ->map(function ($student) use ($type, $totalPaper) {
                                        return [
                                            's_appl_form_num' => $student->s_appl_form_num,
                                            's_id' => $student->s_id,
                                            'fullname' => $student->s_candidate_name,
                                            'father_name' => $student->s_father_name,
                                            'mother_name' => $student->s_mother_name,
                                            'institute_name' => $student->i_name,
                                            'institute_code' => $student->s_inst_code,
                                            's_reg_no' => $student->s_appl_reg_no,
                                            'mobile_no' => $student->s_phone,
                                            'reg_year' => $student->s_appl_reg_year,
                                            'student_dob' => $student->s_dob,
                                            'is_applied' => (bool) $student->is_enrollment,
                                            'is_paid' => (bool) $student->enrl_is_paid,
                                            'sem' => $student->s_part_sem,
                                            'type' => $type,
                                            'applied_paper' => json_encode($totalPaper),
                                        ];
                                    });

                                $enrollmentdata2 = $query2->get()->map(fn($student) => [
                                    's_appl_form_num' => $student->s_appl_form_num,
                                    's_id' => $student->s_id,
                                    'fullname' => $student->s_candidate_name,
                                    'father_name' => $student->s_father_name,
                                    'mother_name' => $student->s_mother_name,
                                    'institute_name' => $student->i_name,
                                    'institute_code' => $student->s_inst_code,
                                    's_reg_no' => $student->s_appl_reg_no,
                                    'mobile_no' => $student->s_phone,
                                    'reg_year' => $student->s_appl_reg_year,
                                    'student_dob' => $student->s_dob,
                                    'is_applied' => (bool) $student->is_enrollment,
                                    'is_paid' => (bool) $student->enrl_is_paid,
                                    'sem' => $student->s_part_sem,
                                    'type' => $type,
                                    'applied_paper' => json_encode($totalPaper),
                                ]);


                                $mergedEnrollmentData = $enrollmentdata->merge($enrollmentdata2)->values();
                            } else {
                                $date = date('Y');
                                $minYr = $date - 5;

                                $getResultData = DB::table('pharmacy_result')
                                    ->select('reg_no', 'sub1_grade', 'sub2_grade', 'sub3_grade', 'sub4_grade', 'sub5_grade', 'session')
                                    ->whereRaw("CAST(NULLIF(SPLIT_PART(session, '-', 2), '') AS INTEGER) >= ?", [$minYr])
                                    ->whereRaw("CAST(NULLIF(SPLIT_PART(session, '-', 2), '') AS INTEGER) = ?", [$enroll_year])
                                    ->get();

                                $filteredRegNos = $getResultData->filter(function ($item) {
                                    $grades = [$item->sub1_grade, $item->sub2_grade, $item->sub3_grade, $item->sub4_grade, $item->sub5_grade];
                                    return collect($grades)->filter(fn($g) => $g !== 'P')->count() <= 2;
                                })->pluck('reg_no');

                                $students = DB::table('pharmacy_register_student_final')
                                    ->join('pharmacy_result', 'pharmacy_register_student_final.s_appl_reg_no', '=', 'pharmacy_result.reg_no')
                                    ->join('institute_master', 'pharmacy_register_student_final.s_inst_code', '=', 'institute_master.i_code')
                                    ->leftJoin('pharmacy_enrollment', 'pharmacy_register_student_final.s_appl_form_num', '=', 'pharmacy_enrollment.enrl_form_num')
                                    ->whereIn('s_appl_reg_no', $filteredRegNos)

                                    ->when(!empty($institute_code), function ($q) use ($institute_code) {
                                        $q->where('pharmacy_register_student_final.s_inst_code', $institute_code);
                                    })
                                    ->select(
                                        's_id',
                                        's_inst_code',
                                        's_appl_form_num',
                                        's_appl_reg_no',
                                        's_appl_reg_year',
                                        's_candidate_name',
                                        's_father_name',
                                        's_mother_name',
                                        's_phone',
                                        's_email',
                                        's_dob',
                                        'i_name',
                                        's_part_sem',
                                        'sub1_code',
                                        'sub1_grade',
                                        'sub2_code',
                                        'sub2_grade',
                                        'sub3_code',
                                        'sub3_grade',
                                        'sub4_code',
                                        'sub4_grade',
                                        'sub5_code',
                                        'sub5_grade',
                                        'is_enrollment',
                                        'enrl_is_paid',
                                        'pharmacy_enrollment.created_at'
                                    )
                                    ->get();

                                $subjectMaster = DB::table('pharmacy_subjects_master')
                                    ->select('general_code', 'subject_name', 'q_code', 'subject_id')
                                    ->get()
                                    ->keyBy('general_code');

                                $mergedEnrollmentData = $students->map(function ($s) use ($subjectMaster, $type) {
                                    $appliedSubjects = [];
                                    for ($i = 1; $i <= 5; $i++) {
                                        $code = $s->{"sub{$i}_code"};
                                        $grade = $s->{"sub{$i}_grade"};

                                        if ($grade === 'F' && isset($subjectMaster[$code])) {
                                            $subject = $subjectMaster[$code];
                                            $appliedSubjects[] = [
                                                'code' => $code,
                                                'subject_name' => $subject->subject_name,
                                                'q_code' => $subject->q_code,
                                                'subject_id' => $subject->subject_id,
                                            ];
                                        }
                                    }


                                    return [
                                        's_appl_form_num' => $s->s_appl_form_num,
                                        's_id' => $s->s_id,
                                        'fullname' => $s->s_candidate_name,
                                        'father_name' => $s->s_father_name,
                                        'mother_name' => $s->s_mother_name,
                                        'institute_name' => $s->i_name,
                                        'institute_code' => $s->s_inst_code,
                                        's_reg_no' => $s->s_appl_reg_no,
                                        'mobile_no' => $s->s_phone,
                                        'reg_year' => $s->s_appl_reg_year,
                                        'student_dob' => $s->s_dob,
                                        'is_applied' => (bool) $s->is_enrollment,
                                        'is_paid' => (bool) $s->enrl_is_paid,
                                        'sem' => $s->s_part_sem,
                                        'type' => $type,
                                        'applied_paper' => json_encode($appliedSubjects),

                                    ];
                                });
                            }

                            DB::commit();
                            return response()->json([
                                'error' => false,
                                'message' => 'Student list found',
                                'student_list' => $mergedEnrollmentData,
                            ]);
                        } catch (Exception $e) {
                            DB::rollback();
                            return response()->json([
                                'error' => true,
                                'message' => $e->getMessage()
                            ]);
                        }
                   
    }

    public function enrollmentsubmit(Request $request)
    {
                        $validated = validator::make($request->all(), [
                            'student_ids' => 'required',
                        ]);
                        if ($validated->fails()) {
                            return response()->json([
                                'error' => true,
                                'message' => $validated->errors()
                            ]);
                        }
                        try {
                            $user_name = $request->user_name;
                            $part_sem = $request->part_sem;
                            //add new
                            $type = $request->type;
                            $studentIds = $request->student_ids;
                            if($part_sem=="Part_I")
                            {
                                $partField='s_part1_status';
                            } else {
                                $partField='s_part2_status';
                            }

                            if (!is_array($studentIds)) {
                                $studentIds = [$studentIds];
                            }

                            $exitenroll = Enrollment::where('enrl_reg_no', $studentIds)
                                ->where('is_enrollment', 1)
                                ->where('enrl_part_sem', $part_sem)
                                ->count();
                                
                            if ($exitenroll > 0) {
                                return response()->json([
                                    'error' => true,
                                    'message' => 'Student already enrolled.',
                                ]);
                            }
                            $students = Registerstudent::whereIn('s_appl_reg_no', $studentIds)
                                ->where('is_registration_payment', 1)
                                ->where('s_part_sem', $part_sem)
                                //->whereNotNull('s_appl_reg_no')
                                ->orderBy('s_id', 'asc')
                                ->get();
                        
                            if ($students->count() == 0) {
                                return response()->json([
                                    'error' => true,
                                    'message' => 'No student found for enrollment.',
                                ]);
                            }
                            $chance = 1;
                            foreach ($students as $student) {
                                $year = $student->s_appl_sess_year;

                                $enrl_year = substr($year, -4);

                                $currentYear = date("Y");
                                $filteredGrades = [];
                                if ($currentYear == $enrl_year) {
                                    $chance = 1;


                                } else {


                                    $lastChance = countChance($enrl_year);
                                    $chance = $lastChance + 1;
                                }

                                if ($type === 'REGULAR') {

                                    $subjects = DB::table('pharmacy_subjects_master')
                                        ->where([
                                            ['dept', 'PHARM'],
                                            ['semester', 'Part_I'],
                                            ['subject_type', 'Theoretical'],
                                            ['is_active', 1],
                                        ])
                                        ->select('general_code as code', 'subject_name')
                                        ->get();

                                    $marks = $subjects->map(function ($item) {
                                        return [
                                            'code' => $item->code,
                                            'subject_name' => $item->subject_name,
                                        ];
                                    })->values()->toArray();

                                } else {

                                    $result = (array) DB::table('pharmacy_result')
                                        ->where('reg_no', $student->s_appl_reg_no)
                                        ->first();


                                    $failedCodes = collect($result)
                                        ->filter(fn($v, $k) => str_ends_with($k, '_grade') && $v === 'F')
                                        ->map(fn($_, $gradeKey) => $result[str_replace('_grade', '_code', $gradeKey)])
                                        ->filter()
                                        ->unique()
                                        ->values();


                                    $subjects = DB::table('pharmacy_subjects_master')
                                        ->whereIn('general_code', $failedCodes)
                                        ->select('general_code as code', 'subject_name')
                                        ->get();

                                    $marks = $subjects->map(function ($item) {
                                        return [
                                            'code' => $item->code,
                                            'subject_name' => $item->subject_name,
                                        ];
                                    })->values()->toArray();
                                }


                                $enrollment = Enrollment::create([
                                    'enrl_form_num' => $student->s_appl_form_num,
                                    'enrl_inst_code' => $student->s_inst_code,
                                    'enrl_reg_no' => $student->s_appl_reg_no,
                                    'enrl_reg_year' => $student->s_appl_reg_year,
                                    'enrl_type' => $type,
                                    'enrl_part_sem' => $part_sem,
                                    'is_enrollment' => 1,
                                    'enrl_date' => now(),
                                    'enrl_by' => $user_name,
                                    'enrl_year' => $currentYear,
                                    'chance' => $chance,
                                    'applied_paper' => json_encode($marks)
                                ]);
                               
                                if ($enrollment) {
                                    Registerstudent::where('s_appl_reg_no', $student->s_appl_reg_no)->update([
                                        $partField => 3,

                                    ]);


                                    auditTrail($student->s_appl_form_num, "{$student->s_candidate_name} Eligible for Enrollment with Registration no {$student->s_appl_reg_no}");
                                } else {
                                    echo "Insert error.";
                                }
                            }

                            return response()->json([
                                'error' => false,
                                'message' => 'Eligible for Enrollment.',
                            ]);
                        } catch (Exception $e) {
                            return response()->json([
                                'error' => true,
                                'message' => $e->getMessage()
                            ]);
                        }
                   
    }

    public function enrollmentDownload(Request $request)
    {
        try {
            $institute_code = $request->institute_code;
            if (empty($institute_code)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Institute code is required.',
                ], 422);
            }

            // Fetch enrollment data
            $enrollmentdata = Enrollment::join('pharmacy_register_student_final as prs', 'pharmacy_enrollment.enrl_form_num', '=', 'prs.s_appl_form_num')
                ->join('institute_master', 'prs.s_inst_code', '=', 'institute_master.i_code')
                ->where('pharmacy_enrollment.is_enrollment', 1)
                ->where('pharmacy_enrollment.enrl_is_paid', 1)
                // ->where('prs.s_session_year', $session_year)
                ->where('prs.s_inst_code', $institute_code)
                ->select(
                    'prs.s_candidate_name',
                    'prs.s_appl_reg_no',
                    'prs.s_appl_reg_year',
                    'prs.s_appl_sess_year',
                    'institute_master.i_name',
                    'prs.s_id',
                    'prs.s_appl_form_num',
                    'pharmacy_enrollment.is_enrollment',
                    'pharmacy_enrollment.enrl_is_paid',
                    'prs.s_father_name',
                    'prs.s_sign'
                )
                ->get();


            if ($enrollmentdata->isEmpty()) {
                return response()->json([
                    'error' => true,
                    'message' => 'No enrollment data found for given filters.',
                ]);
            }


            $studentIds = $enrollmentdata->pluck('s_appl_form_num')->toArray();

            $paymentData = PaymentTransaction::where('paying_for', 'ENROLLMENT')
                ->where('trans_status', 'SUCCESS')
                ->get()
                ->filter(function ($transaction) use ($studentIds) {
                    $formNumbers = explode(',', $transaction->form_no);
                    $formNumbers = array_map('trim', $formNumbers);
                    return collect($formNumbers)->intersect($studentIds)->isNotEmpty();
                });

            $totalAmount = $paymentData->sum(fn($tx) => (float) $tx->trans_amount);



            $pdf = PDF::loadView('exports.enrollment-sheet', [
                'enrollmentdata' => $enrollmentdata,
                'paymentData' => $paymentData,
                'totalAmount' => $totalAmount,
            ]);

            return $pdf->setPaper('a4', 'portrait')
                ->setOption(['defaultFont' => 'sans-serif'])
                ->stream('enrollment-sheet.pdf');
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function re_admission_list(Request $request)
    {
        
                        try {
                            $inst_code = $request->inst_code;
                            $reAdmissionYr = $request->re_admission_year;
                            $searchData = $reAdmissionYr - 1;
                            $date = date('Y');
                            $minYr = $date - 5; // minYr function not included yet

                            $getResultData = DB::table('pharmacy_result')
                                ->select('reg_no', 'sub1_grade', 'sub2_grade', 'sub3_grade', 'sub4_grade', 'sub5_grade', 'session')
                                ->whereRaw("CAST(SPLIT_PART(session, '-', 2) AS INTEGER) >= ?", [$minYr])
                                ->get();
                            
                            $filteredRegNos = $getResultData->filter(function ($item) {
                                $grades = [
                                    $item->sub1_grade,
                                    $item->sub2_grade,
                                    $item->sub3_grade,
                                    $item->sub4_grade,
                                    $item->sub5_grade,
                                ];

                                $nonPCount = collect($grades)->filter(fn($grade) => $grade !== 'P')->count();

                                return $nonPCount > 2;
                            })->pluck('reg_no');

                            $students = DB::table('pharmacy_register_student_final')
                                ->join('pharmacy_result', 'pharmacy_register_student_final.s_appl_reg_no', '=', 'pharmacy_result.reg_no')
                                ->whereIn('s_appl_reg_no', $filteredRegNos)
                                ->select('s_inst_code', 's_appl_form_num', 's_appl_reg_no', 's_appl_reg_year', 's_candidate_name', 's_father_name', 's_mother_name', 's_phone', 's_email', 'sub1_code', 'sub1_grade', 'sub2_code', 'sub2_grade', 'sub3_code', 'sub3_grade', 'sub4_code', 'sub4_grade', 'sub5_code', 'sub5_grade', 're_admission_status', 're_admission_date')
                                ->where('s_inst_code', $inst_code)
                                ->whereRaw('right(s_appl_reg_year, 4) = ?', [$searchData])
                                ->get();

                            return response()->json([
                                'error' => false,
                                'student_list' => $students,
                            ], 200);

                        } catch (Exception $e) {
                            return response()->json([
                                'error' => true,
                                'message' => $e->getMessage()
                            ], 500);
                        }
                    
    }

    public function re_admission_submit(Request $request)
    {
        
                        try {
                            $date = date('Y');
                            $form_reg_num = $request->form_reg_num;
                            $students = DB::table('pharmacy_register_student_final')
                                ->join('pharmacy_result', 'pharmacy_register_student_final.s_appl_reg_no', '=', 'pharmacy_result.reg_no')
                                ->whereIn('s_appl_reg_no', $form_reg_num)
                                ->select('s_inst_code', 's_appl_form_num', 's_appl_reg_no', 's_appl_reg_year', 's_candidate_name', 's_father_name', 's_mother_name', 's_phone', 's_email', 'sub1_code', 'sub1_grade', 'sub2_code', 'sub2_grade', 'sub3_code', 'sub3_grade', 'sub4_code', 'sub4_grade', 'sub5_code', 'sub5_grade')
                                ->get();

                            $insertData = $students->map(function ($item) {
                                $arrayItem = (array) $item;
                                $arrayItem['created_at'] = now();
                                $arrayItem['updated_at'] = now();
                                return $arrayItem;
                            })->toArray();

                            $insert = DB::table('pharmacy_re_admission')->insert($insertData);
                            if ($insert) {
                                Registerstudent::whereIn('s_appl_reg_no', $form_reg_num)->update(['re_admission_status' => 1, 're_admission_date' => $date]);

                                foreach ($students as $student) {
                                    auditTrail(
                                        $student->s_appl_form_num,
                                        "{$student->s_candidate_name} submitted for Re-Admission ({$student->s_appl_reg_no})"
                                    );
                                }

                                return response()->json([
                                    'error' => false,
                                    'message' => 'Student submitted for Re-Admission',
                                ], 200);
                            }
                        } catch (Exception $e) {
                            return response()->json([
                                'error' => true,
                                'message' => $e->getMessage()
                            ], 500);
                        }
                    
    }

    public function enrollmentReceipt(Request $request)
    {

        $reg_no = $request->reg_no;
        $part_sem = $request->part_sem;
        $fees_type = $request->fees_type;

        $data = Registerstudent::where('pharmacy_register_student_final.s_appl_reg_no', $reg_no)
            ->where('pharmacy_register_student_final.s_part_sem', $part_sem)
            ->leftJoin(
                "pharmacy_student_extraInfo",
                "pharmacy_student_extraInfo.ei_appl_form_num",
                '=',
                'pharmacy_register_student_final.s_appl_form_num'
            )
            ->leftJoin('pharmacy_payment_transactions', function ($join) {
                $join->whereRaw("pharmacy_register_student_final.s_appl_form_num = ANY (string_to_array(pharmacy_payment_transactions.form_no, ','))");
            })
            ->select(
                'pharmacy_register_student_final.s_appl_form_num',
                'pharmacy_register_student_final.s_candidate_name',
                'pharmacy_register_student_final.s_photo',
                'pharmacy_student_extraInfo.ei_guardian_name',
                'pharmacy_register_student_final.s_sign',
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
            //dd($data);
        if (!$data) {
            return response()->json(['error' => 'No data found.'], 404);
        }

        $pdf = Pdf::loadView('enrollment_fees', ['data' => $data]);
        return $pdf->stream('enrollment_fees.pdf');

    }

    public function rollno_generate_list(Request $request)
    {
                        try {
                            $enrl_inst_code = $request->enrl_inst_code;
                            $enrl_type = $request->enrl_type;
                            $enrl_year = $request->enrl_year;
                            $enrl_part_sem = $request->enrl_part_sem;

                            $studentList = DB::table('pharmacy_enrollment')
                                ->leftJoin('pharmacy_roll_no', 'pharmacy_enrollment.enrl_form_num', '=', 'pharmacy_roll_no.form_no')
                                //->where('pharmacy_enrollment.enrl_inst_code', $enrl_inst_code)
                                ->when(!empty($enrl_inst_code), function ($q) use ($enrl_inst_code) {
                                    $q->where('pharmacy_enrollment.enrl_inst_code', $enrl_inst_code);
                                })
                                ->where('pharmacy_enrollment.enrl_type', $enrl_type)
                                ->where('pharmacy_enrollment.enrl_year', $enrl_year)
                                ->where('pharmacy_enrollment.enrl_part_sem', $enrl_part_sem)
                                ->where('pharmacy_enrollment.enrl_is_paid', 1)
                                ->select(
                                    'pharmacy_enrollment.enrl_form_num',
                                    'pharmacy_enrollment.enrl_reg_no',
                                    'pharmacy_enrollment.enrl_reg_year',
                                    'pharmacy_enrollment.enrl_inst_code',
                                    'pharmacy_enrollment.enrl_type',
                                    'pharmacy_enrollment.enrl_year',
                                    'pharmacy_enrollment.chance',
                                    'pharmacy_enrollment.enrl_part_sem',
                                    'pharmacy_roll_no.roll',
                                    'pharmacy_roll_no.no_prefix',
                                    'pharmacy_roll_no.number'
                                )
                                ->get();

                            if ($studentList->isEmpty()) {
                                return response()->json([
                                    'error' => true,
                                    'message' => 'No students found with paid enrollment.'
                                ], 404);
                            }

                            return response()->json([
                                'error' => false,
                                'student_list' => $studentList
                            ], 200);


                        } catch (Exception $e) {
                            return response()->json([
                                'error' => true,
                                'message' => $e->getMessage()
                            ], 500);
                        }
                    
    }

    public function rollno_generate_submit(Request $request)
    {
        
                        try {
                            $form_no = $request->enrl_form_num;
                            $students = DB::table('pharmacy_enrollment')
                                ->whereIn('enrl_form_num', $form_no)
                                ->get();
                            $rolls = [];
                            $lastRegistration = DB::table('pharmacy_roll_no')
                                ->orderBy('number', 'desc')
                                ->first();
                            $lastFive = $lastRegistration ? (int) substr($lastRegistration->number, -5) : 0;
                            foreach ($students as $index => $student) {

                                $userData = DB::table('pharmacy_register_student_final')
                                    ->where('s_appl_form_num', $student->enrl_form_num)
                                    ->select('s_appl_form_num', 's_candidate_name')
                                    ->first();

                                $institute = $student->enrl_inst_code;
                                $enrl_year = $student->enrl_year;
                                $chance = $student->chance;
                                $enrl_part_sem = $student->enrl_part_sem;
                                $enrl_type = $student->enrl_type;
                                $enrl_reg_year = $student->enrl_reg_year;
                                //$exam_year = $student->enrl_year;

                                $partNumber = 1;
                                if (preg_match('/Part_(\w+)/', $enrl_part_sem, $matches)) {
                                    $roman = strtoupper($matches[1]);
                                    $romanToInt = [
                                        'I' => 1,
                                        'II' => 2
                                    ];
                                    $partNumber = $romanToInt[$roman] ?? 1;
                                }
                                $roll = "P" . $institute . "PHARM" . $partNumber;
                                $countReadmission = DB::table('pharmacy_re_admission')
                                    ->where('s_appl_form_num', $student->enrl_form_num)->count();
                                $num1 = 1 + $countReadmission;
                                $num2 = 1;

                                if ($enrl_type == 'REGULAR') {
                                    $num2 = 0;
                                    $semPartNumber = 0;
                                } else {
                                    $num2 = 1;
                                    $semPartNumber = $partNumber;
                                }
                                $num3 = $semPartNumber;

                                $counter = $lastFive + $index + 1; // Ensures uniqueness per student

                                $fiveDigit = str_pad($counter, 5, '0', STR_PAD_LEFT);


                                $no_prefix = $num1 . $num2 . $num3;
                                $rolls[] = [
                                    'form_no' => $student->enrl_form_num,
                                    'part_sem' => $enrl_part_sem,
                                    'enrl_reg_year' => $enrl_reg_year,
                                    'enrl_type' => $enrl_type,
                                    'roll' => $roll,
                                    'number' => $fiveDigit,
                                    'date' => now(),
                                    'no_prefix' => $no_prefix,
                                    'inst_code' => $institute,
                                    'exam_year' => $enrl_year
                                ];
                                auditTrail(
                                    $userData->s_appl_form_num,
                                    "Roll No generated successfully for {$userData->s_candidate_name} (" . $roll . $no_prefix . $fiveDigit . ")"
                                );

                            }

                            $insert = DB::table('pharmacy_roll_no')->insert($rolls);

                            if ($insert) {

                                return response()->json([
                                    'error' => false,
                                    'message' => 'Roll No generated successfully!'
                                ], 200); // success
                            } else {
                                return response()->json([
                                    'error' => true,
                                    'message' => 'Failed to generate roll numbers.'
                                ], 500); // server error
                            }


                        } catch (Exception $e) {
                            return response()->json([
                                'error' => true,
                                'message' => $e->getMessage()
                            ], 500);
                        }

                    
    }

    public function exam_center_list(Request $request)
    {
        try {
            $part_sem = $request->part_sem;
            $exam_year = $request->exam_year;
            $center_type = $request->center_type;
            $institute = 'institute_master';
            $columns = Schema::getColumnListing('pharmacy_exam_center');
            $filteredColumns = array_filter($columns, fn($col) => !in_array($col, ['created_by', 'created_at']));
            $getCenterList = DB::table('pharmacy_exam_center')
                ->where('part_sem', $part_sem)
                ->where('exam_year', $exam_year)
                ->where('center_type', $center_type)
                //->join($institute . ' as ins', 'pharmacy_exam_center.center_code', '=', 'ins.i_code')
                ->select($filteredColumns)
                ->get();
            return response()->json([
                'error' => false,
                'centerList' => $getCenterList
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function exam_center_submit(Request $request)
    {
        try {
            $exam_year = $request->exam_year;
            $center_type = $request->center_type;
            $part_sem = $request->part_sem;
            $away_center = $request->away_center;
            $user_name = $request->user_name;
            $institute = $request->institute;

            $allInst = Institute::where('is_active', 1)->pluck('i_code');
            if ($center_type == 'HOME') {
                $institutes = $allInst;
            } else {
                $institutes = $institute;
            }
            //dd($institutes);
            //$institutes = ($center_type == 'HOME') ? $istitute2 : $istitute1;
            foreach ($institutes as $instCode) {

                $center_code = ($center_type == 'HOME') ? $instCode : $away_center;

                //dd($center_code);
                if ($center_type == 'HOME') {
                    $exists = DB::table('pharmacy_exam_center')
                        ->where('part_sem', $part_sem)
                        ->where('exam_year', $exam_year)
                        ->where('center_code', $center_code)
                        ->where('inst_code', $instCode)
                        ->exists();
                } else {
                    $exists = DB::table('pharmacy_exam_center')
                        ->where('part_sem', $part_sem)
                        ->where('exam_year', $exam_year)
                        ->where('center_code', $center_code)
                        ->orWhere('center_code', $instCode)
                        ->exists();
                }

                //dd($instCode);
                if (!$exists) {
                    DB::table('pharmacy_exam_center')->insert([
                        'part_sem' => $part_sem,
                        'exam_year' => $exam_year,
                        'center_type' => $center_type,
                        'inst_code' => $instCode,
                        'center_code' => $center_code,
                        'created_by' => $user_name,
                        'created_at' => now(),
                    ]);

                    auditTrail($instCode, "Exam center submitted ({$center_code})");
                } else {
                    return response()->json([
                        'error' => true,
                        'message' => 'Exam center already Submitted!'
                    ], 200);
                    auditTrail($instCode, "Skipped: Exam center already exists ({$center_code})");
                }

            }
            return response()->json([
                'error' => false,
                'message' => 'Exam center submited successfully!'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
