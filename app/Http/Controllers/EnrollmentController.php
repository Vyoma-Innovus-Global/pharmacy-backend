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
use Illuminate\Support\Facades\Log;
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
                                    ->where('pharmacy_register_student_final.admission_type', '!=', 'CASUAL')
                                    //->whereRaw("CAST(NULLIF(SPLIT_PART(pharmacy_register_student_final.s_appl_reg_year, '-', 2), '') AS INTEGER) = ?", [$enroll_year])
                                    ->whereBetween('s_part1_status', [2, 8])

                                    ->orderBy('s_id', 'desc');

                                if (!empty($institute_code)) {
                                    $query->where('pharmacy_register_student_final.s_inst_code', $institute_code);
                                }
                                // if ($type == 'REGULAR') {
                                //     $query->whereRaw("CAST(NULLIF(SPLIT_PART(pharmacy_register_student_final.s_appl_reg_year, '-', 2), '') AS INTEGER) = ?", [$enroll_year]);
                                // } 
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
                                        ['semester', $part_sem],
                                        //['subject_type', 'Theoretical'],
                                        ['is_active', 1],
                                    ])
                                    ->select('q_code as code', 'subject_name')
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
                                    ->select('reg_no', 'sub1_grade', 'sub2_grade', 'sub3_grade', 'sub4_grade', 'sub5_grade', 'sub6_grade', 
                                    'sub7_grade', 'sub8_grade', 'sub9_grade', 'sub10_grade', 'session')
                                    ->whereRaw("CAST(NULLIF(SPLIT_PART(session, '-', 2), '') AS INTEGER) >= ?", [$minYr])
                                    ->whereRaw("CAST(NULLIF(SPLIT_PART(session, '-', 2), '') AS INTEGER) <= ?", [$enroll_year])
                                    ->get();

                                $filteredRegNos = $getResultData->filter(function ($item) {
                                    $grades = [$item->sub1_grade, $item->sub2_grade, $item->sub3_grade, $item->sub4_grade, $item->sub5_grade, 
                                    $item->sub6_grade, $item->sub7_grade, $item->sub8_grade, $item->sub9_grade, $item->sub10_grade];
                                    return collect($grades)->filter(fn($g) => $g == 'F')->count() <= 2;
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
                                        'sub6_code',
                                        'sub6_grade',
                                        'sub7_code',
                                        'sub7_grade',
                                        'sub8_code',
                                        'sub8_grade',
                                        'sub9_code',
                                        'sub9_grade',
                                        'sub10_code',
                                        'sub10_grade',
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
                                    for ($i = 1; $i <= 10; $i++) {
                                        $code = $s->{"sub{$i}_code"};
                                        $grade = $s->{"sub{$i}_grade"};

                                        if ($grade === 'F' && isset($subjectMaster[$code])) {
                                            $subject = $subjectMaster[$code];
                                            $appliedSubjects[] = [
                                                //'code' => $code,
                                                'subject_name' => $subject->subject_name,
                                                'code' => $subject->q_code,
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
                            $enrl_year = $request->enrl_year;
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
                                ->where('enrl_year', $enrl_year)
                                ->count();
                                
                            if ($exitenroll > 0) {
                                return response()->json([
                                    'error' => true,
                                    'message' => 'Student already enrolled.',
                                ]);
                            }
                            $students = Registerstudent::whereIn('s_appl_reg_no', $studentIds)
                                ->where('is_registration_payment', 1)
                                //->where('s_part_sem', $part_sem)
                                ->whereNotNull('s_appl_reg_no')
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

                                $currentYear = date("Y");
                                $filteredGrades = [];
                               
                                    $lastChance = countChance($enrl_year);
                                    $chance = $lastChance + 1;
                                

                                if ($type === 'REGULAR') {

                                    $subjects = DB::table('pharmacy_subjects_master')
                                        ->where([
                                            ['dept', 'PHARM'],
                                            ['semester', $part_sem],
                                            //['subject_type', 'Theoretical'],
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
                                ->select('reg_no', 'sub1_grade', 'sub2_grade', 'sub3_grade', 'sub4_grade', 'sub5_grade','sub6_grade', 'sub7_grade',
                                 'sub8_grade', 'sub9_grade', 'sub10_grade',  'session')
                                ->whereRaw("CAST(SPLIT_PART(session, '-', 2) AS INTEGER) >= ?", [$minYr])
                                ->get();
                            
                            $filteredRegNos = $getResultData->filter(function ($item) {
                                $grades = [
                                    $item->sub1_grade,
                                    $item->sub2_grade,
                                    $item->sub3_grade,
                                    $item->sub4_grade,
                                    $item->sub5_grade,
                                    $item->sub6_grade,
                                    $item->sub7_grade,
                                    $item->sub8_grade,
                                    $item->sub9_grade,
                                    $item->sub10_grade,
                                ];

                                $nonPCount = collect($grades)->filter(fn($grade) => $grade == 'F')->count();

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

        $data = DB::table('pharmacy_register_student_final as rsf')
                ->leftJoin('pharmacy_student_extraInfo as sei', 'sei.ei_appl_form_num', '=', 'rsf.s_appl_form_num')
                ->Join('pharmacy_enrollment as pe', 'pe.enrl_form_num', '=', 'rsf.s_appl_form_num')
                ->leftJoin('pharmacy_payment_transactions as ppt', function ($join) {
                    $join->whereRaw("rsf.s_appl_form_num = ANY (string_to_array(ppt.form_no, ','))");
                })
                ->where('rsf.s_appl_reg_no', $reg_no)
                ->where('rsf.s_part_sem', $part_sem)
                ->where('ppt.paying_for', $fees_type)
                ->where('ppt.trans_status', 'SUCCESS')
                ->select([
                    'rsf.s_appl_form_num',
                    'rsf.s_appl_reg_no',
                    'rsf.s_candidate_name',
                    'rsf.s_photo',
                    'sei.ei_guardian_name',
                    'rsf.s_sign',
                    'rsf.s_dob',
                    'rsf.s_phone',
                    'rsf.s_caste',
                    'rsf.s_pwd',
                    'rsf.s_inst_code',
                    'ppt.trans_amount',
                    'ppt.trans_mode',
                    'ppt.trans_time',
                    'ppt.trans_id',
                    'ppt.order_id',
                    'pe.enrl_part_sem',
                    'pe.enrl_year',
                    'pe.enrl_type',
                ])
                ->first();
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
							$enrl_inst_code = $request->enrl_inst_code;
                            $enrl_type = $request->enrl_type;
                            $enrl_year = $request->enrl_year;
                            $enrl_part_sem = $request->enrl_part_sem;


                            $students = DB::table('pharmacy_enrollment')
								->where('enrl_part_sem' , $enrl_part_sem)
								->where('enrl_year', $enrl_year)
								->when($enrl_inst_code != "", function ($query) use ($enrl_inst_code) {
                                    $query->where('enrl_inst_code', $enrl_inst_code);
                                })
								->where('enrl_type' , $enrl_type)
								->whereNull('enrl_roll_num')
                                ->whereIn('enrl_form_num', $form_no)
								->limit(250)
                                ->get();
								
								
                            $rolls = [];
							 
							//regualar and  readmission
							$lastRegistration = DB::table('pharmacy_roll_no')
							->where('part_sem' , $enrl_part_sem)
							->where('exam_year', $enrl_year)
							->where('enrl_type' , $enrl_type)
							->orderBy('number', 'desc')
							->first();
							 $lastFive = $lastRegistration ? (int) substr($lastRegistration->number, -5) : 0;
					
						//	 echo "a";
							 
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
                                    $partNumber = $romanToInt[$roman];
                                }
                                $roll = "P" . $institute . "PHARM" . $partNumber;
                                
								// $countReadmission = DB::table('pharmacy_re_admission')
                                //     ->where('s_appl_form_num', $student->enrl_form_num)->count();
                                // $num1 = 1 + $countReadmission;
                                // $num2 = 1;
                                
                                $num2 = substr($enrl_year, -2);
                                if ($enrl_type == 'REGULAR') {
                                    $num1 = 'AD';
                                    $semPartNumber = 0;
                                } 
                                elseif($enrl_type == 'READMISSION'){
                                    $num1 = 'RA';
                                    $semPartNumber = 0;
                                }
                                else {
                                    $num1 = 'CA';
                                    $semPartNumber = $partNumber;
                                }
                                $num3 = $semPartNumber;

                                $counter = $lastFive + $index + 1; // Ensures uniqueness per student

                                $fiveDigit = str_pad($counter, 5, '0', STR_PAD_LEFT);
								
								if($enrl_type =='CASUAL'){
									$result_data	=	DB::table('pharmacy_result')
									->where('reg_no', $student->enrl_reg_no)->first();
									 $fiveDigit =	substr($result_data->no, -5);
								}
								
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
								
								#dd($student->applied_paper);
								
								$enrol_subject_code	=	  array_map(function($item) {
											return $item['code'];
										}, json_decode($student->applied_paper, true));
										
								/*		 return response()->json([
                                'error' => true,
                                'message' => 'error',
								'data'    => $enrol_subject_code
                            ], 500);*/
										//dd($enrol_subject_code);
                                $subjectList = DB::table('pharmacy_subjects_master')
                                        ->where('semester', $enrl_part_sem)
                                        ->where('type', 'Main')
                                        #->where('subject_type', 'Sessional')
										->whereIn('general_code' , $enrol_subject_code )
                                        ->where('dept', 'PHARM')
                                        ->select('subject_id', 'general_code', 'q_code', 'subject_name', 'subject_type', )
                                        ->orderBy('subject_sn', 'ASC')
                                        ->get();
                                    $att = [];
                                    foreach ($subjectList as $subject) {
										
										  
                                        $exists = DB::table('exam_attendance_pone')
                                            ->where('ea_form_number', $userData->s_appl_form_num)
                                            ->where('ea_subject_code', $subject->general_code)
                                            ->where('ea_exam_year', $enrl_year)
                                            ->where('ea_part_sem', $enrl_part_sem)
                                            ->exists();

                                        if (!$exists) {
											$subject_type	=	$subject->subject_type;
											if($subject_type=='Sessional'){
												$ea_center_code=$institute;
												$ea_center_type='HOME';
												
											}else{
												$center_details	=	DB::table('pharmacy_exam_center')
												->where('exam_year', $enrl_year)
												->where('part_sem' ,  $enrl_part_sem)
												->where('inst_code' , $institute)
												->first();
												
												if(!$center_details){
													
													 
													 return response()->json([
															'error' => true,
															'message' => "Center not set for institute code". $institute
														], 400);
												}
												//$ea_center_type='AWAY';
												$ea_center_code=$center_details->center_code;
												$ea_center_type=$center_details->center_type;
												
												
											}
											 
											$attendence_insert[]	=	[
                                                'ea_student_type' => $enrl_type,
                                                'ea_inst_code' => $institute,
                                                'ea_center_code' => $ea_center_code, //change
                                                'ea_center_type' => $ea_center_type,     //change
                                                'ea_room_code' => 'NA',
                                                'ea_part_sem' => $enrl_part_sem,
                                                'ea_exam_year' => $enrl_year,
                                                'ea_subject_code' => $subject->general_code,
                                                'ea_form_number' => $userData->s_appl_form_num,
                                                'ea_roll_number' => $roll . $no_prefix . $fiveDigit,
                                                'ea_subject_id' => $subject->subject_id,
                                                'ea_is_present_external' => 1,
                                                'ea_is_present_internal' => 1,
                                                'ea_is_expel_external' => 0,
                                                'ea_is_expel_internal' => 0,
                                                'ea_is_ra_external' => 0,
                                                'ea_is_ra_internal' => 0,
                                                'ea_is_cop_external' => 0,
                                                'ea_is_cop_internal' => 0,
                                                'ea_is_blocked' => 0,
                                                'ea_is_absent_external' => 0,
                                                'ea_is_absent_internal' => 0
                                            ];
                                            
                                        } 


                                    }

                            }
							
							 
							DB::beginTransaction();
							DB::table('exam_attendance_pone')->insert($attendence_insert);
                            $insert = DB::table('pharmacy_roll_no')->insert($rolls);

                            if ($insert) {
								DB::commit();
                                return response()->json([
                                    'error' => false,
                                    'message' => 'Roll No generated successfully!'
                                ], 200); // success
                            } else {
								 DB::rollBack();
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
                        // ->when(!empty($dept_code), function ($query) use ($dept_code) {
                        //     $query->where('dept_code', $dept_code);
                        // })
                        ->where('center_code', $center_code)
                        ->where('inst_code', $instCode)
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

    /**
     * @OA\Post(
     *     path="/api/enrollment/ra-student-list",
     *     tags={"Enrollment"},
     *     summary="Get list of RA (Re-Admission) students by institute code",
     *     description="Calls PostgreSQL stored function fn_get_ra_student_list to retrieve all Re-Admission students for a given institute code.",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"inst_code"},
     *             @OA\Property(property="inst_code", type="string", example="RAIP", description="Institute Code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Re-admission students fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Re-admission students fetched successfully"),
     *             @OA\Property(property="count", type="integer", example=1),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="semester", type="string", example="Part-I"),
     *                     @OA\Property(property="studentRoll", type="string", example="PRAIPPHARM1"),
     *                     @OA\Property(property="studentNumber", type="string", example="AD25009939"),
     *                     @OA\Property(property="studentRegistrationNumber", type="string", example="PHARM242507934")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function getRaStudentList(Request $request)
    {
        $instCode = $request->input('inst_code', $request->input('p_instcode', $request->input('instCode', $request->input('institute_code', $request->input('i_code')))));

        $validator = Validator::make([
            'inst_code' => $instCode,
        ], [
            'inst_code' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed: ' . $validator->errors()->first(),
                'errors'  => $validator->errors(),
                'data'    => null,
            ], 422);
        }

        $instCode = strtoupper(trim($instCode));

        Log::channel('daily')->info('[Enrollment] fn_get_ra_student_list INPUT', [
            'inst_code' => $instCode,
            'ip'        => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_get_ra_student_list(?::varchar) AS data',
                [$instCode]
            );

            if (empty($result)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No re-admission students found.',
                    'count'   => 0,
                    'data'    => [],
                ], 200);
            }

            $students = [];

            foreach ($result as $row) {
                $raw = $row->data ?? null;

                if ($raw === null) {
                    continue;
                }

                $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    Log::channel('daily')->error('[Enrollment] fn_get_ra_student_list JSON decode error', [
                        'error' => json_last_error_msg(),
                        'raw'   => $raw,
                    ]);

                    return response()->json([
                        'version' => '1.0',
                        'status'  => 3,
                        'message' => 'Failed to parse database response.',
                        'data'    => null,
                    ], 500);
                }

                if (is_array($decoded) && array_is_list($decoded)) {
                    $students = array_merge($students, $decoded);
                } elseif (is_array($decoded)) {
                    $students[] = $decoded;
                }
            }

            Log::channel('daily')->info('[Enrollment] fn_get_ra_student_list OUTPUT', [
                'inst_code' => $instCode,
                'count'     => count($students),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Re-admission students fetched successfully',
                'count'   => count($students),
                'data'    => $students,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[Enrollment] fn_get_ra_student_list EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 3,
                'message' => 'An error occurred while fetching re-admission students: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/enrollment/enroll-student-details",
     *     tags={"Enrollment"},
     *     summary="Get enrolled student details by institute code, exam year, semester, and user ID",
     *     description="Calls PostgreSQL stored function fn_getenrollstudentdetails to retrieve enrolled student details.",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"inst_code", "exam_year", "semester", "user_id"},
     *             @OA\Property(property="inst_code", type="string", example="JCG", description="Institute Code (e.g. JCG)"),
     *             @OA\Property(property="exam_year", type="string", example="2026", description="Exam Year (e.g. 2026)"),
     *             @OA\Property(property="semester", type="string", example="Part-II", description="Semester / Part (e.g. Part-II)"),
     *             @OA\Property(property="user_id", type="integer", example=3114, description="User ID / Admin User ID (e.g. 3114)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Enrolled student details fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Enrolled student details fetched successfully"),
     *             @OA\Property(property="count", type="integer", example=2),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="rollNo", type="string", nullable=true, example=null),
     *                     @OA\Property(property="txnDate", type="string", nullable=true, example=null),
     *                     @OA\Property(property="enrlType", type="string", example="REGULAR"),
     *                     @OA\Property(property="instStatus", type="string", nullable=true, example=null),
     *                     @OA\Property(property="studentName", type="string", example="ARNAB PAUL"),
     *                     @OA\Property(property="fatherMotherName", type="string", example="ARUN PAUL"),
     *                     @OA\Property(property="registrationNumber", type="string", example="PHARM242504516")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function getEnrollStudentDetails(Request $request)
    {
        $authUserData = json_decode($request->header('auth_user_data'), true);
        $authUserId   = $authUserData['user_id'] ?? null;

        $instCode = $request->input('inst_code', $request->input('p_instcode', $request->input('instCode', $request->input('institute_code', $request->input('i_code')))));
        $examYear = $request->input('exam_year', $request->input('p_examyear', $request->input('examYear', $request->input('academic_year', $request->input('year')))));
        $semester = $request->input('semester', $request->input('p_semester', $request->input('part_sem', $request->input('partSem', $request->input('part')))));
        $userId   = $request->input('user_id', $request->input('p_userid', $request->input('userId', $request->input('admin_user_id', $authUserId))));

        $validator = Validator::make([
            'inst_code' => $instCode,
            'exam_year' => $examYear,
            'semester'  => $semester,
            'user_id'   => $userId,
        ], [
            'inst_code' => 'required|string|max:50',
            'exam_year' => 'required|string|max:20',
            'semester'  => 'required|string|max:50',
            'user_id'   => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed: ' . $validator->errors()->first(),
                'errors'  => $validator->errors(),
                'data'    => null,
            ], 422);
        }

        $instCode = strtoupper(trim($instCode));
        $examYear = trim((string) $examYear);
        $semester = trim((string) $semester);
        $userId   = (int) $userId;

        Log::channel('daily')->info('[Enrollment] fn_getenrollstudentdetails INPUT', [
            'inst_code' => $instCode,
            'exam_year' => $examYear,
            'semester'  => $semester,
            'user_id'   => $userId,
            'ip'        => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_getenrollstudentdetails(?::varchar, ?::varchar, ?::varchar, ?::bigint) AS data',
                [$instCode, $examYear, $semester, $userId]
            );

            if (empty($result)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No enrolled student details found.',
                    'count'   => 0,
                    'data'    => [],
                ], 200);
            }

            $students = [];

            foreach ($result as $row) {
                $raw = $row->data ?? null;

                if ($raw === null) {
                    $rowArr = (array) $row;
                    if (!empty($rowArr)) {
                        $students[] = $rowArr;
                    }
                    continue;
                }

                $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    Log::channel('daily')->error('[Enrollment] fn_getenrollstudentdetails JSON decode error', [
                        'error' => json_last_error_msg(),
                        'raw'   => $raw,
                    ]);

                    return response()->json([
                        'version' => '1.0',
                        'status'  => 3,
                        'message' => 'Failed to parse database response.',
                        'data'    => null,
                    ], 500);
                }

                if (is_array($decoded) && array_is_list($decoded)) {
                    $students = array_merge($students, $decoded);
                } elseif (is_array($decoded)) {
                    $students[] = $decoded;
                }
            }

            Log::channel('daily')->info('[Enrollment] fn_getenrollstudentdetails OUTPUT', [
                'inst_code' => $instCode,
                'exam_year' => $examYear,
                'semester'  => $semester,
                'user_id'   => $userId,
                'count'     => count($students),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Enrolled student details fetched successfully',
                'count'   => count($students),
                'data'    => $students,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[Enrollment] fn_getenrollstudentdetails EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 3,
                'message' => 'An error occurred while fetching enrolled student details: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/enrollment/update-student-enrollment-status",
     *     tags={"Enrollment"},
     *     summary="Update student enrollment status by admin",
     *     description="Calls PostgreSQL stored function fn_updatestudentenrollmentstatusbyadmin to update student enrollment status.",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_id", "exam_year", "semester", "admin_user_id", "admin_user_type", "status"},
     *             @OA\Property(property="student_id", type="integer", example=12345, description="Student ID (p_studentid)"),
     *             @OA\Property(property="exam_year", type="string", example="2026", description="Exam Year (p_examyear)"),
     *             @OA\Property(property="semester", type="string", example="1", description="Semester / Part (p_semester)"),
     *             @OA\Property(property="admin_user_id", type="integer", example=3114, description="Admin User ID (p_adminuserid)"),
     *             @OA\Property(property="admin_user_type", type="integer", example=1, description="Admin User Type (p_adminusertype)"),
     *             @OA\Property(property="status", type="integer", example=5, description="Status code (p_status): 5 = Accept / Approved, 7 = Reject / Rejected"),
     *             @OA\Property(property="remarks", type="string", example="Approved", description="Remarks (p_remarks)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student enrollment status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Student enrollment status updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="p_errorcode", type="integer", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function updateStudentEnrollmentStatusByAdmin(Request $request)
    {
        $authUserData = json_decode($request->header('auth_user_data'), true);
        $authUserId   = $authUserData['user_id'] ?? null;
        $authUserType = $authUserData['role_id'] ?? $authUserData['user_type'] ?? 1;

        $studentId     = $request->input('student_id', $request->input('studentId', $request->input('p_studentid', $request->input('s_id'))));
        $examYear      = $request->input('exam_year', $request->input('examYear', $request->input('p_examyear', $request->input('academic_year', $request->input('year')))));
        $semester      = $request->input('semester', $request->input('p_semester', $request->input('part_sem', $request->input('partSem', $request->input('part')))));
        $adminUserId   = $request->input('admin_user_id', $request->input('adminUserId', $request->input('p_adminuserid', $request->input('user_id', $request->input('userId', $authUserId)))));
        $adminUserType = $request->input('admin_user_type', $request->input('adminUserType', $request->input('p_adminusertype', $request->input('user_type', $request->input('userType', $authUserType)))));
        $status        = $request->input('status', $request->input('p_status', $request->input('enrollment_status', $request->input('enrollStatus'))));
        $remarks       = $request->input('remarks', $request->input('p_remarks', ''));

        $validator = Validator::make([
            'student_id'      => $studentId,
            'exam_year'       => $examYear,
            'semester'        => $semester,
            'admin_user_id'   => $adminUserId,
            'admin_user_type' => $adminUserType,
            'status'          => $status,
            'remarks'         => $remarks,
        ], [
            'student_id'      => 'required|integer',
            'exam_year'       => 'required|string|max:20',
            'semester'        => 'required|string|max:50',
            'admin_user_id'   => 'required|integer',
            'admin_user_type' => 'required|integer',
            'status'          => 'required|integer',
            'remarks'         => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed: ' . $validator->errors()->first(),
                'errors'  => $validator->errors(),
                'data'    => null,
            ], 422);
        }

        $studentId     = (int) $studentId;
        $examYear      = trim((string) $examYear);
        $semester      = trim((string) $semester);
        $adminUserId   = (int) $adminUserId;
        $adminUserType = (int) $adminUserType;
        $status        = (int) $status;
        $remarks       = (string) ($remarks ?? '');

        Log::channel('daily')->info('[Enrollment] fn_updatestudentenrollmentstatusbyadmin INPUT', [
            'student_id'      => $studentId,
            'exam_year'       => $examYear,
            'semester'        => $semester,
            'admin_user_id'   => $adminUserId,
            'admin_user_type' => $adminUserType,
            'status'          => $status,
            'remarks'         => $remarks,
            'ip'              => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_updatestudentenrollmentstatusbyadmin(?::bigint, ?::varchar, ?::varchar, ?::bigint, ?::bigint, ?::integer, ?::varchar) AS data',
                [
                    $studentId,
                    $examYear,
                    $semester,
                    $adminUserId,
                    $adminUserType,
                    $status,
                    $remarks,
                ]
            );

            $raw = $result[0]->data ?? null;

            if ($raw === null) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No response returned from database function.',
                    'data'    => null,
                ], 500);
            }

            $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('[Enrollment] fn_updatestudentenrollmentstatusbyadmin JSON decode error', [
                    'error' => json_last_error_msg(),
                    'raw'   => $raw,
                ]);

                return response()->json([
                    'version' => '1.0',
                    'status'  => 3,
                    'message' => 'Failed to parse database response.',
                    'data'    => null,
                ], 500);
            }

            $errorCode = $decoded['p_errorcode'] ?? $decoded['errorcode'] ?? 0;
            $isSuccess = ((int) $errorCode === 0);

            Log::channel('daily')->info('[Enrollment] fn_updatestudentenrollmentstatusbyadmin OUTPUT', [
                'student_id' => $studentId,
                'result'     => $decoded,
                'isSuccess'  => $isSuccess,
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => $isSuccess ? 0 : 1,
                'message' => $isSuccess ? 'Student enrollment status updated successfully' : 'Failed to update student enrollment status',
                'data'    => $decoded,
            ], $isSuccess ? 200 : 400);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[Enrollment] fn_updatestudentenrollmentstatusbyadmin EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 3,
                'message' => 'An error occurred while updating student enrollment status: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }
}
