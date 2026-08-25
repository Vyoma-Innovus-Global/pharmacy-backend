<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\Models\Attendancepone;
use App\Models\Rollnomodel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Exports\decodingList;
use App\Exports\printingInstruction;
use App\Exports\packingSlipDownload;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CenterWiseStudentsExport;
use Illuminate\Support\Facades\Validator;

class ExaminationController extends Controller
{

    public function getExamScheduleForAdmit($part_sem, $exam_year, $subject_codes)
    {

        $sch_tbl = 'pharmacy_exam_schedule';
        $subj_tbl = 'pharmacy_subjects_master';

        try {
            $results = DB::table($sch_tbl . ' as sch')
                ->select([
                    'sch.*',
                    'sub.subject_name',
                    'sub.general_code',
                    'sub.q_code'
                ])
                ->join($subj_tbl . ' as sub', 'sub.subject_id', '=', 'sch.exam_subject_id')
                ->where([
                    'sch.exam_year' => $exam_year,
                    'sch.exam_part_sem' => $part_sem,
                    'sub.subject_type' => 'Theoretical'
                ])
                ->whereIn('sch.exam_subject_general_code', $subject_codes)
                ->orderBy('sch.exam_date', 'ASC')
                ->get();

            $schedule = [];
            foreach ($results as $result) {
                $schedule[] = [
                    'exam_date' => Carbon::parse($result->exam_date)->format('d-m-Y'),
                    'exam_subj' => $result->subject_name,
                    'exam_time' => $result->exam_time,
                    'exam_subj_code' => $result->q_code,

                    'exam_month' => Carbon::parse($result->exam_date)->format('F')
                ];
            }
            return $schedule;
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function downloadAdmitCard(Request $request)
    {
        $part_sem = $request->part_sem;
        $exam_year = $request->exam_yr;
        $form_no = $request->form_num;
        $center_name = $request->center_name;
        $role_id = $request->role_id;
        #dd($role_id);
        # if($role_id){
        $center = DB::table('exam_attendance_pone as a')
            ->select(DB::raw("
                            a.ea_inst_code,
                            a.ea_center_code,
                            a.ea_center_type,
                            a.ea_room_code,
                            a.ea_part_sem,
                            a.ea_exam_year,
                            a.ea_subject_code,
                            i.i_name,
                            i.i_code,
							acm.acm_address,
                            COUNT(a.ea_room_code) AS student_count
                        "))
            ->join('institute_master as i', 'i.i_code', '=', 'a.ea_center_code')
            ->join('pharmacy_away_center_master as acm', 'acm.acm_code', '=', 'a.ea_center_code')
            ->where('a.ea_exam_year', $exam_year)
            ->where('a.ea_part_sem', $part_sem)
            ->where('a.ea_center_type', 'AWAY')
            ->where('a.ea_form_number', $form_no)
            ->groupBy(DB::raw("
                            a.ea_inst_code,
                            a.ea_center_code,
                            a.ea_center_type,
                            a.ea_room_code,
                            a.ea_part_sem,
                            a.ea_exam_year,
                            a.ea_subject_code,
                            i.i_name,
                            i.i_code,
							acm.acm_address
                        "))
            ->first();
        #dd($center);
        $center_name = $center->i_name . " (" . $center->i_code . ")";
        #}

        if ($part_sem == 'Part_I') {
            $at_tbl = 'exam_attendance_pone';
        } else {
            $at_tbl = 'exam_attendance_ptwo';
        }
        $st_tbl = 'pharmacy_register_student_final';
        $roll_tbl = 'pharmacy_roll_no';
        $inst_tbl = 'institute_master';
        $profile_photo = '';
        $profile_sign = '';
        /*  try{*/
        $selectField = [
            's_appl_form_num',
            's_appl_reg_no',
            's_first_name',
            's_middle_name',
            's_last_name',
            's_photo',
            's_sign',
            's_inst_code',
            'enrl_type',
            'roll',
            'no_prefix',
            'number',
            'ea_inst_code',
            'ea_center_code',
            'ea_center_type',
            'ea_part_sem',
            'ea_exam_year',
            'i_code',
            'i_name',
            'i_dist_code',
            's_father_name',
            'ea_subject_code'
        ];


        $result_all = DB::table($st_tbl)->select($selectField)
            ->join($roll_tbl, 'form_no', '=', 's_appl_form_num')
            ->join($at_tbl, 'ea_form_number', '=', 'form_no')
            ->join($inst_tbl, 'i_code', '=', 'ea_inst_code')
            ->where([
                's_appl_form_num' => $form_no,
                'ea_form_number' => $form_no,
                'form_no' => $form_no,
                'ea_part_sem' => $part_sem,
                'part_sem' => $part_sem,
                'ea_exam_year' => $exam_year,
                'exam_year' => $exam_year
            ])->get();
        $exam_subject_code = $result_all->pluck('ea_subject_code');
        #print_r($exam_subject_code);
        #exit();			
        $result = $result_all[0];
        $getExamScheduleForAdmit = $this->getExamScheduleForAdmit($part_sem, $exam_year, $exam_subject_code);
        $exam_month = $getExamScheduleForAdmit[0]['exam_date'];
        $formattedPartSem = ucwords(str_replace('_', ' ', strtolower($result->ea_part_sem)));
        $admitInfo = [
            'st_form_number' => $result->s_appl_form_num,
            'st_reg_number' => $result->s_appl_reg_no,
            'st_full_name' => $result->s_first_name . ' ' . $result->s_middle_name . ' ' . $result->s_last_name,
            'st_gur_name' => $result->s_father_name,
            'st_roll' => $result->roll,
            'st_admit_num' => $result->no_prefix . '' . $result->number,
            'st_part_sem' => $formattedPartSem,
            'st_apr_type' => $result->enrl_type,
            'st_institute' => $result->s_inst_code,
            'st_institute_name' => $result->i_name,
            'st_course' => 'PHARMACY (PHARM)',
            'st_exm_yr' => $result->ea_exam_year,
            'st_exm_center' => $center_name,
            'st_exm_cnt_address' => $center->acm_address,
            'st_profile_img' => $result->s_photo,
            'st_profile_sign' => $result->s_sign,
            'exam_month' => Carbon::parse($exam_month)->format('F'),
            'st_exam_schedule' => $getExamScheduleForAdmit,
        ];

        #dd($admitInfo);
        $fileName = 'admit-' . $form_no . '.pdf';
        $pdf = PDF::loadView('admit', ['data' => $admitInfo])
            ->setPaper('a4', 'landscape');

        return $pdf->stream($fileName);

        /*}
        catch(\Exception $e){
            return response()->json([
                'error'   => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }*/
    }
    public function downloadAdmitCardInbulk(Request $request)
    {
        $part_sem = $request->part_sem;
        $exam_year = $request->exam_yr;
        $form_numbers = $request->form_num; // array of form numbers
        $center_name = $request->center_name;
        //dd($form_numbers);

        $at_tbl = ($part_sem == 'Part_I') ? 'exam_attendance_pone' : 'exam_attendance_ptwo';
        $st_tbl = 'pharmacy_register_student_final';
        $roll_tbl = 'pharmacy_roll_no';
        $inst_tbl = 'institute_master';

        $admitCards = [];

        try {
            foreach ($form_numbers as $form_no) {
                $result = DB::table($st_tbl)
                    ->select([
                        's_appl_form_num',
                        's_appl_reg_no',
                        's_first_name',
                        's_middle_name',
                        's_last_name',
                        's_photo',
                        's_sign',
                        's_inst_code',
                        'enrl_type',
                        'roll',
                        'no_prefix',
                        'number',
                        'ea_inst_code',
                        'ea_center_code',
                        'ea_center_type',
                        'ea_room_code',
                        'ea_part_sem',
                        'ea_exam_year',
                        'i_code',
                        'i_name',
                        'i_dist_code',
                        's_father_name'
                    ])
                    ->join($roll_tbl, 'form_no', '=', 's_appl_form_num')
                    ->join($at_tbl, 'ea_form_number', '=', 'form_no')
                    ->join($inst_tbl, 'i_code', '=', 'ea_inst_code')
                    ->where([
                        's_appl_form_num' => $form_no,
                        'ea_form_number' => $form_no,
                        'form_no' => $form_no,
                        'ea_part_sem' => $part_sem,
                        'part_sem' => $part_sem,
                        'ea_exam_year' => $exam_year,
                        'exam_year' => $exam_year
                    ])
                    ->first();

                $getExamScheduleForAdmit = $this->getExamScheduleForAdmit($part_sem, $exam_year, $result->s_appl_form_num);
                $exam_month = $getExamScheduleForAdmit[0]['exam_date'];

                if (!$result) {
                    continue;
                }

                $admitInfo = [
                    'st_form_number' => $result->s_appl_form_num,
                    'st_reg_number' => $result->s_appl_reg_no,
                    'st_full_name' => $result->s_first_name . ' ' . $result->s_middle_name . ' ' . $result->s_last_name,
                    'st_gur_name' => $result->s_father_name,
                    'st_roll' => $result->roll,
                    'st_admit_num' => $result->no_prefix . '' . $result->number,
                    'st_part_sem' => $result->ea_part_sem,
                    'st_apr_type' => $result->enrl_type,
                    'st_institute' => $result->s_inst_code,
                    'st_institute_name' => $result->i_name,
                    'st_course' => 'PHARMACY (PHARM)',
                    'st_exm_yr' => $result->ea_exam_year,
                    'st_exm_center' => isset($center_name) ? $center_name : '',
                    'st_profile_img' => $result->s_photo,
                    'st_profile_sign' => $result->s_sign,
                    'exam_month' => Carbon::parse($exam_month)->format('F'),

                    'st_exam_schedule' => $getExamScheduleForAdmit,
                ];

                $admitCards[] = $admitInfo;
            }

            if (empty($admitCards)) {
                return back()->with('error', 'No admit cards found.');
            }

            //dd($admitCards);
            $pdf = PDF::loadView('admit-multi', ['student' => $admitCards])
                ->setPaper('a4', 'landscape');

            $fileName = 'admit-cards-' . time() . '.pdf';
            return $pdf->stream($fileName);

        } catch (\Exception $e) {
            Log::error('Error fetching multiple admit cards: ' . $e->getMessage());
            return back()->with('error', 'Unable to generate admit cards.');
        }
    }
    public function downloadAdmitCardList(Request $request)
    {
        #return back()->with('error', 'Unable to generate admit cards.');
        try {
            $part_sem = $request->part_sem;
            $exam_yr = $request->exam_yr;
            $enrl_type = $request->enrl_type;
            $inst_code = $request->inst_code;
            $center_name = "";

            $center = DB::table('exam_attendance_pone as a')
                ->select(DB::raw("
                            a.ea_inst_code,
                            a.ea_center_code,
                            a.ea_center_type,
                            a.ea_room_code,
                            a.ea_part_sem,
                            a.ea_exam_year,
                            a.ea_subject_code,
                            i.i_name,
                            i.i_code,
                            COUNT(a.ea_room_code) AS student_count
                        "))
                ->join('institute_master as i', 'i.i_code', '=', 'a.ea_inst_code')
                //->where('a.ea_student_type', $enrl_type)
                ->where('a.ea_exam_year', $exam_yr)
                ->where('a.ea_part_sem', $part_sem)
                ->where('a.ea_center_type', 'AWAY')
                ->where('a.ea_inst_code', $inst_code)
                ->groupBy(DB::raw("
                            a.ea_inst_code,
                            a.ea_center_code,
                            a.ea_center_type,
                            a.ea_room_code,
                            a.ea_part_sem,
                            a.ea_exam_year,
                            a.ea_subject_code,
                            i.i_name,
                            i.i_code
                        "))
                ->first();


            #dd($center );
            $center_name = $center->i_name . " (" . $center->i_code . ")";


            $student_list = Rollnomodel::query()
                ->select(
                    'pharmacy_roll_no.*',
                    'pharmacy_register_student_final.s_appl_reg_no',
                    'pharmacy_register_student_final.s_first_name',
                    'pharmacy_register_student_final.s_middle_name',
                    'pharmacy_register_student_final.s_last_name',
                    'pharmacy_register_student_final.s_candidate_name'
                )
                ->join('pharmacy_register_student_final', 'pharmacy_register_student_final.s_appl_form_num', '=', 'pharmacy_roll_no.form_no')
                ->where('pharmacy_roll_no.part_sem', $part_sem)
                ->where('pharmacy_roll_no.exam_year', $exam_yr)
                ->when(
                    in_array($enrl_type, ['REGULAR', 'READMISSION']),
                    fn($q) => $q->whereIn('pharmacy_roll_no.enrl_type', ['REGULAR', 'READMISSION']),
                    fn($q) => $q->where('pharmacy_roll_no.enrl_type', $enrl_type)
                )
				->where('pharmacy_register_student_final.is_active',1)
                ->where('pharmacy_roll_no.inst_code', $inst_code)
                ->get();


            return response()->json([
                'error' => false,
                'message' => 'Student list found',
                'student_list' => $student_list,
                'center_name' => isset($center_name) ? $center_name : '',
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'error' => true,
                'message' => 'Unable to get admit card list.',
                # 'student_list' => $student_list,
                #'center_name' => isset($center_name) ? $center_name : '',
            ]);
            Log::error('Error fetching multiple admit cards: ' . $e->getMessage());
            // return back()->with('error', 'Unable to generate admit cards.');
        }
    }
    public function getHsInstituteList(Request $request)
    {
        $exam_year = $request->exam_year;
        $exam_type = $request->exam_type;
        $inst_code = $request->inst_code;
        $part_sem = $request->part_sem;
        if ($part_sem == 'Part_I') {
            $at_tbl = 'exam_attendance_pone';
        } else {
            $at_tbl = 'exam_attendance_ptwo';
        }

        try {
            $center = DB::table($at_tbl . ' as a')
                ->select(DB::raw("
                        a.ea_center_code,
                        a.ea_center_type,
                        i.i_name,
                        i.i_code
                    "))
                ->join('institute_master as i', 'i.i_code', '=', 'a.ea_inst_code')
                //->where('a.ea_student_type', $exam_type)
                ->where('a.ea_exam_year', $exam_year)
                ->where('a.ea_part_sem', $part_sem)
                ->where('a.ea_center_code', $inst_code)
                ->where('ea_center_type', 'AWAY')
                ->when(
                    in_array($exam_type, ['REGULAR', 'READMISSION']),
                    fn($q) => $q->whereIn('a.ea_student_type', ['REGULAR', 'READMISSION']),
                    fn($q) => $q->where('a.ea_student_type', $exam_type)
                )
                ->groupBy(DB::raw("
                        a.ea_center_code,
                        a.ea_center_type,
                        i.i_name,
                        i.i_code
                    "));
            $istitute = $center->get();

            return response()->json([
                'institute' => $istitute,

            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function getAdminHsAwayList(Request $request)
    {
        $exam_year = $request->exam_year;
        $center_type = $request->center_type;
        $part_sem = $request->part_sem;

        try {
            $awayCenter = DB::table('pharmacy_exam_center')
                ->select('center_code', 'i.acm_name')
                ->join('pharmacy_away_center_master as i', 'i.acm_code', '=', 'pharmacy_exam_center.center_code')
                ->where('center_type', $center_type)
                ->where('part_sem', $part_sem)
                ->where('exam_year', $exam_year)
                ->groupBy('center_code', 'i.acm_name')
                ->get();

            return response()->json([
                'awayCenter' => $awayCenter,
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function hallStickerList(Request $request)
    {
        try {
            $exam_year = $request->exam_year;
            $exam_type = $request->exam_type;
            $inst_code = $request->inst_code;
            $center_code = $request->center_code;
            $part_sem = $request->part_sem;
            $subject_code = $request->subject_code;

            if ($part_sem == 'Part_I') {
                $at_tbl = 'exam_attendance_pone';
            } else {
                $at_tbl = 'exam_attendance_ptwo';
            }
            $students = Rollnomodel::query()
                ->selectRaw('COUNT(form_no) AS total_count')
                ->join($at_tbl, 'form_no', '=', 'ea_form_number')
                ->where('part_sem', $part_sem)
                ->where('exam_year', $exam_year)
                ->when(
                    in_array($exam_type, ['REGULAR', 'READMISSION']),
                    fn($q) => $q->whereIn('enrl_type', ['REGULAR', 'READMISSION']),
                    fn($q) => $q->where('enrl_type', $exam_type)
                )
                ->where('inst_code', $inst_code)
                ->where('ea_center_code', $center_code)
                ->where('ea_subject_code', $subject_code);


            $results = $students->first();

            return [
                'hall_sticker' => [
                    'enrl_type' => $exam_type,
                    'cntr_code' => $inst_code,
                    'subj_code' => $subject_code,
                    'part_sem' => $part_sem,
                    'exam_year' => $exam_year,
                    'total_records' => $results->total_count ?? 0,
					'page_per_count'	=> $results->total_count

                ]
            ];


        } catch (\Exception $e) {
            Log::error('Error fetching multiple admit cards: ' . $e->getMessage());
            return back()->with('error', 'Unable to generate admit cards.');
        }
    }

    public function hallStickerDownload(Request $request)
    {
        try {
            $cntr_code = $request->cntr_code;
            $subj_code = $request->subj_code;
            $part_sem = $request->part_sem;
            $exam_year = $request->exam_year;
            $enrl_type = $request->enrl_type;
            $tab = $request->tab_no;
			$page_per_count	=	$request->page_per_count;
            if ($part_sem == 'Part_I') {
                $at_tbl = 'exam_attendance_pone';
            } else {
                $at_tbl = 'exam_attendance_ptwo';
            }
            $per_page = $page_per_count;
            $offset = ($tab - 1) * $per_page;
            $students = Rollnomodel::query()
                ->select(
                    'pharmacy_roll_no.form_no',
                    'pharmacy_roll_no.part_sem',
                    'pharmacy_roll_no.enrl_type',
                    'pharmacy_roll_no.exam_year',
                    'pharmacy_roll_no.inst_code',
                    'pharmacy_roll_no.roll',
                    'pharmacy_roll_no.no_prefix',
                    'pharmacy_roll_no.number',
                    'pharmacy_roll_no.inst_code',
                    'a.ea_subject_code',
                    'a.ea_room_code',
                    'pharmacy_register_student_final.s_candidate_name',
                    'institute_master.i_name',
                    'institute_master.i_code'
                )
                ->join($at_tbl . ' as a', 'pharmacy_roll_no.form_no', '=', 'a.ea_form_number')
                ->join(
                    'pharmacy_register_student_final',
                    'pharmacy_roll_no.form_no',
                    '=',
                    'pharmacy_register_student_final.s_appl_form_num'
                )
                ->join(
                    'institute_master',
                    'a.ea_center_code',
                    '=',
                    'institute_master.i_code'
                )
                ->where('pharmacy_roll_no.part_sem', $part_sem)
                ->where('pharmacy_roll_no.exam_year', $exam_year)
                //->where('pharmacy_roll_no.enrl_type', $enrl_type)
                ->when(
                    in_array($enrl_type, ['REGULAR', 'READMISSION']),
                    fn($q) => $q->whereIn('pharmacy_roll_no.enrl_type', ['REGULAR', 'READMISSION']),
                    fn($q) => $q->where('pharmacy_roll_no.enrl_type', $enrl_type)
                )
                ->where('pharmacy_roll_no.inst_code', $cntr_code)
                ->where('a.ea_subject_code', $subj_code)
				->where('pharmacy_register_student_final.is_active',1)
                ->orderBy('pharmacy_roll_no.number')
                ->offset($offset)
                ->limit($per_page);
            $rows = $students->get();
            $formattedPartSem = ucwords(str_replace('_', ' ', strtolower($part_sem)));

            $finalData = [];
            foreach ($rows as $row) {
                $roll = $row->roll . "-" . $row->no_prefix;
                $number = $row->number;
                $finalData[] = [
                    'part_sem' => $formattedPartSem,
                    'exam_year' => $exam_year,
                    'roll' => $roll,
                    'no' => $number,
                    'subject_name' => $row->subject_name,
                    's_candidate_name' => $row->s_candidate_name,
                    'i_name' => $row->i_name . " (" . $row->i_code . ")",
                    'room_code' => $row->ea_room_code,
                    's_inst_code' => $row->inst_code
                ];
            }

            $fileName = 'hallSticker-' . time() . '.pdf';
            $pdf = PDF::loadView('hallSticker', ['data' => $finalData])->setPaper('a4', 'landscape');
            return $pdf->stream($fileName);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function descriptiveRoll(Request $request)
    {
        try {
            $exam_year = $request->exam_year;
            $exam_type = $request->exam_type;
            $inst_code = $request->inst_code;
            $part_sem = $request->part_sem;
            $subject_code = $request->subject_code;
            $center_code = $request->center_code;

            if ($part_sem == 'Part_I') {
                $at_tbl = 'exam_attendance_pone';
            } else {
                $at_tbl = 'exam_attendance_ptwo';
            }

            $students = DB::table($at_tbl)
                ->select(
                    "$at_tbl.ea_form_number",
                    "$at_tbl.ea_room_code",
                    "institute_master.i_name as center_name"
                )
                ->join("institute_master", "$at_tbl.ea_center_code", "=", "institute_master.i_code")
                ->where("$at_tbl.ea_inst_code", $inst_code)
                ->when(
                    in_array($exam_type, ['REGULAR', 'READMISSION']),
                    fn($q) => $q->whereIn("$at_tbl.ea_student_type", ['REGULAR', 'READMISSION']),
                    fn($q) => $q->where("$at_tbl.ea_student_type", $exam_type)
                )
                ->where("$at_tbl.ea_subject_code", $subject_code)
                ->where("$at_tbl.ea_part_sem", $part_sem)
                ->where("$at_tbl.ea_exam_year", $exam_year)
                ->where("$at_tbl.ea_center_code", $center_code)
                ->orderBy("$at_tbl.ea_roll_number")
                ->get();



            $grouped = $students->groupBy('ea_room_code');

			$count_per_page	=	100;	
            $final = $grouped->map(function ($roomStudents, $roomCode) use ($count_per_page){
                $center_name = $roomStudents[0]->center_name;
                $student_count = $roomStudents->count();

                $formidChunks = $roomStudents
                    ->pluck('ea_form_number')
                    ->chunk($count_per_page)
                    ->map(fn($chunk) => $chunk->values())
                    ->values();

                return [
                    'center_name' => $center_name,
                    'room_code' => $roomCode,
                    'student_count' => $student_count,
                    'formid' => $formidChunks
                ];
            })->values();

            return response()->json([
                'error' => false,
                'message' => 'Student list found',
                'data' => $final
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching multiple admit cards: ' . $e->getMessage());
            return back()->with('error', 'Unable to generate admit cards.');
        }
    }
    public function descriptiveRollDownload(Request $request)
    {
        try {
            $students = $request->query('form_ids');
            $subject_code = $request->query('subject_code');
            $finalData = [];
            foreach ($students as $student) {
                $data = DB::table('pharmacy_register_student_final')
                    ->select('pharmacy_register_student_final.s_photo', 'pharmacy_register_student_final.s_candidate_name', 'pharmacy_register_student_final.s_appl_reg_no', 'pharmacy_register_student_final.s_appl_reg_year', 'pharmacy_roll_no.roll', 'pharmacy_roll_no.no_prefix', 'pharmacy_roll_no.number', 'exam_attendance_pone.ea_center_code', 'exam_attendance_pone.ea_inst_code','exam_attendance_pone.ea_room_code', 'exam_attendance_pone.ea_subject_code', 'institute_master.i_name')
                    ->join('pharmacy_roll_no', 'pharmacy_register_student_final.s_appl_form_num', 'pharmacy_roll_no.form_no')
                    //->join('exam_attendance_pone', 'pharmacy_register_student_final.s_appl_form_num', 'exam_attendance_pone.ea_form_number')
                    ->join('exam_attendance_pone', function($join) {
                        $join->on('pharmacy_register_student_final.s_appl_form_num', '=', 'exam_attendance_pone.ea_form_number')
                            ->where('exam_attendance_pone.ea_center_type', '=', 'AWAY');
                    })
                    ->join('institute_master', 'exam_attendance_pone.ea_center_code', 'institute_master.i_code')
                    ->where('s_appl_form_num', $student)
                    ->first();
                $finalData[] = [
                    'center' => $data->i_name,
                    'institute' => $data->ea_inst_code,
                    'room_code' => $data->ea_room_code,
                    'ea_subject_code' => convertSubname($subject_code),
                    'photo' => $data->s_photo,
                    'name' => $data->s_candidate_name,
                    's_appl_reg_no' => $data->s_appl_reg_no,
                    's_appl_reg_year' => $data->s_appl_reg_year,
                    'roll_no' => $data->roll . $data->no_prefix . $data->number
                ];
            }
            $fileName = 'descriptive-' . time() . '.pdf';
            $pdf = PDF::loadView('descriptive', ['data' => $finalData])
                ->setPaper('a4', 'landscape');
            return $pdf->stream($fileName);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function topSheetCount(Request $request)
    {
        $exam_type = $request->exam_type;
        $subject = $request->subject_code;
        $part_sem = $request->part_sem;
        $exam_yr = $request->exam_yr;
        $center = $request->exam_center;
        $center_code = $request->center_code;

        if ($part_sem == 'Part_I') {
            $at_tbl = 'exam_attendance_pone';
        } else {
            $at_tbl = 'exam_attendance_ptwo';
        }
        try {
            $presentQuery = DB::table($at_tbl)
                ->selectRaw('COUNT(ea_roll_number) AS total_count')
                #->where('ea_is_present_external', 1)
                ->where('ea_part_sem', $part_sem)
                ->where('ea_exam_year', $exam_yr)
                ->where('ea_subject_code', $subject)
                //->where('ea_student_type', $exam_type)
                ->when(
                    in_array($exam_type, ['REGULAR', 'READMISSION']),
                    fn($q) => $q->whereIn('ea_student_type', ['REGULAR', 'READMISSION']),
                    fn($q) => $q->where('ea_student_type', $exam_type)
                )
                ->where('ea_inst_code', $center)
                ->where('ea_center_code', $center_code);
            $results = $presentQuery->first();
            //dd($presentQuery);
            //Second query: expelled or RA count
            $exQuery = DB::table($at_tbl)
                ->selectRaw('COUNT(ea_roll_number) AS total_count')
                ->where('ea_is_present_external', 1)
                ->where('ea_part_sem', $part_sem)
                ->where('ea_exam_year', $exam_yr)
                ->where('ea_subject_code', $subject)
                ->when(
                    in_array($exam_type, ['REGULAR', 'READMISSION']),
                    fn($q) => $q->whereIn('ea_student_type', ['REGULAR', 'READMISSION']),
                    fn($q) => $q->where('ea_student_type', $exam_type)
                )
                ->where('ea_inst_code', $center)
                ->whereRaw('(ea_is_expel_internal = 1 OR ea_is_expel_external = 1 OR ea_is_ra_external = 1 OR ea_is_ra_internal = 1)');

            $ex_results = $exQuery->first();
            //Return structured array
            return [
                'topsheet' => [
                    'cntr_code' => $center,
                    'subj_code' => $subject,
                    'part_sem' => $part_sem,
                    'exam_year' => $exam_yr,
                    'total_records' => $results->total_count ?? 0,
                    'total_ex_records' => $ex_results->total_count ?? 0,
					'page_per_count'	=>	$results->total_count
                ]
            ];

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    //Topsheet Download
    public function topSheetDownload(Request $request)
    {

        $exam_type = $request->exam_type;
        $subject = $request->subject_code;
        $part_sem = $request->part_sem;
        $exam_yr = $request->exam_yr;
        $center = $request->exam_center;
        $tab = $request->tab_no;
		$page_per_count	=	$request->page_per_count;

        if ($part_sem == 'Part_I') {
            $at_tbl = 'exam_attendance_pone';
        } else {
            $at_tbl = 'exam_attendance_ptwo';
        }
        $subj_tbl = 'pharmacy_subjects_master';
        $sch_tbl = 'pharmacy_exam_schedule';
        $cntr_tbl = 'pharmacy_exam_center';
        $institute = 'institute_master';
        try {
            $per_page = $page_per_count;
            $offset = ($tab - 1) * $per_page;
            $query = DB::table($at_tbl . ' as ea')
                ->distinct()
                ->selectRaw("
					ea.ea_center_code,
					cntr.center_code,
					ea.ea_part_sem,
					ea.ea_subject_code,
					subj.subject_name,
					subj.q_code,
					ea.ea_subject_code,

					ea.ea_roll_number,
					ea.ea_is_expel_internal,
					ea_is_expel_external,
					ea.ea_is_ra_internal,
					ea.ea_is_ra_external,
					ea.ea_is_present_external,
					ea.ea_is_present_internal,
					ea.ea_inst_code,
					ins.i_name,
					es.exam_year, es.exam_date

				")
                
                ->join($cntr_tbl . ' as cntr', 'cntr.center_code', '=', 'ea.ea_center_code')
                ->join($subj_tbl . ' as subj', 'subj.subject_id', '=', 'ea.ea_subject_id')
                //block for no data
                ->join($sch_tbl . ' as es', DB::raw('es.exam_subject_general_code'), '=', DB::raw('ea.ea_subject_code::text'))
                ->join($institute . ' as ins', 'ea.ea_center_code', '=', 'ins.i_code')
                ->where([
                        'ea.ea_exam_year' => $exam_yr,
                        'ea.ea_subject_code' => $subject,
                        'ea.ea_part_sem' => (string) $part_sem,
                        'ea.ea_inst_code' => (string) $center,
                        'ea.ea_is_blocked' => 0,
                        //'ea.ea_is_present_external' => 1,
                        //'ea.ea_is_present_internal' => 1,
                    ])
                    ->when(
                        in_array($exam_type, ['REGULAR', 'READMISSION']),
                        fn($q) => $q->whereIn('ea.ea_student_type', ['REGULAR', 'READMISSION']),
                        fn($q) => $q->where('ea.ea_student_type', $exam_type)
                    )
                ->orderBy('ea.ea_roll_number')
                ->offset($offset)
                ->limit($per_page);
            $rows = $query->get();

            //Format results
            $results = [];
			$roll=	'';
            foreach ($rows as $row) {
                $course = 'PHARMACY';
				
				//break roll and number
				$breakPoint = "PHARM";
				$roll_number	=	$row->ea_roll_number;
				$position = strpos($roll_number, $breakPoint);
				if ($position !== false) {
					$lengthBreak = strlen($breakPoint);
					if($roll==''){
						$roll = substr($roll_number, 0, $position + $lengthBreak);
					}					
					$number = substr($roll_number, $position + $lengthBreak); 
				}
				
                $results[] = [
                    'exam_name' => $course . ' ' . $row->ea_part_sem . ' Examination, ' . $row->exam_year,
                    'centre_name' => $row->i_name . ' [' . $row->ea_center_code . ']',
                    'subject' => $row->subject_name . ' [ ' . $row->q_code . ' ]',
                    'yr_sem' => $row->ea_part_sem,
                    'exam_date' => date('d-F-Y', strtotime($row->exam_date)),
                    'sign_date' => date('jS F, Y', strtotime($row->exam_date)),
                    'roll_num' => $roll_number,
                    'is_expel' => $row->ea_is_expel_external,
                    'is_ra' => $row->ea_is_ra_external,
                    'is_present_external' => $row->ea_is_present_external,
                    'is_present_internal' => $row->ea_is_present_internal,
                    'exam_type' => $exam_type,
                    'inst_code' => $row->ea_inst_code,
                    'univ_logo' => '',
					#'roll'		=>	$roll,
					'number'	=>	$number
					
                ];
            }

           # return ['info' => $results];
            $fileName = 'top_sheet-' . time() . '.pdf';
            $pdf = PDF::loadView('top_sheet', ['data' => $results,'roll' => $roll])->setPaper('a4', 'potrait');
            return $pdf->stream($fileName);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function packingSlipDownload(Request $request)
    {
        try {
            $show_type = $request->input('show_type');
            $part_sem = $request->input('part_sem');
            if ($part_sem == 'Part_I') {
                $at_tbl = 'exam_attendance_pone';
            } else {
                $at_tbl = 'exam_attendance_ptwo';
            }
            $subquery = DB::table('pharmacy_exam_schedule')
                ->select(
                    DB::raw('MIN(exam_date) as exam_date'),
                    DB::raw('MIN(exam_time) as exam_time'),
                    'exam_subject_general_code'
                )
                ->groupBy('exam_subject_general_code');

            $data = DB::table(DB::raw("$at_tbl as eap"))
                ->joinSub($subquery, 'pes', function ($join) {
                    $join->on(DB::raw('CAST(eap.ea_subject_code AS TEXT)'), '=', DB::raw('CAST(pes.exam_subject_general_code AS TEXT)'));
                })
                ->join('pharmacy_subjects_master as psm', DB::raw('CAST(eap.ea_subject_code AS TEXT)'), '=', DB::raw('CAST(psm.general_code AS TEXT)'))
                ->when($request->filled('inst_code'), function ($query) use ($request) {
                    $query->where('eap.ea_inst_code', (string) $request->input('inst_code'));
                })
                ->where('eap.ea_exam_year', (string) $request->input('exam_year'))
                ->where('eap.ea_part_sem', (string) $request->input('part_sem'))
                ->select(
                    'eap.ea_inst_code',
                    'psm.subject_name',
                    DB::raw("CASE
                        WHEN eap.ea_part_sem = 'Part_I' THEN 1
                        WHEN eap.ea_part_sem = 'Part_II' THEN 2
                    END as sem_code"),
                    DB::raw('COUNT(*) as packet_count'),
                    DB::raw('CEIL(COUNT(*) / 20.0) as packet_required'),
                    'psm.q_code',
                    DB::raw("CONCAT('SO',
                        psm.id,
                        CASE
                            WHEN eap.ea_part_sem = 'Part_I' THEN '1'
                            WHEN eap.ea_part_sem = 'Part_II' THEN '2'
                            ELSE '0'
                        END,
                        RIGHT(CAST(eap.ea_exam_year AS TEXT), 2)
                    ) as subject_d_code"), // SO+ id+sem+examyr
                    DB::raw("TO_CHAR(pes.exam_date, 'DD-Mon-YYYY') as exam_date"),
                    'pes.exam_time as exam_hlf'
                )
                ->groupBy(
                    'eap.ea_inst_code',
                    'eap.ea_exam_year',
                    'psm.id',
                    'psm.subject_name',
                    'eap.ea_part_sem',
                    'psm.q_code',
                    'pes.exam_date',
                    'pes.exam_time'
                )
                ->get();

            if ($show_type == 'export') {
                return Excel::download(new packingSlipDownload($data->toArray()), 'packing-slip-download.xlsx');
            } else {
                return response()->json([
                    'error' => false,
                    'data' => $data,
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function printingInstruction(Request $request)
    {
        try {
            $show_type = $request->input('show_type');
            $part_sem = $request->input('part_sem');
            if ($part_sem == 'Part_I') {
                $at_tbl = 'exam_attendance_pone';
            } else {
                $at_tbl = 'exam_attendance_ptwo';
            }

            $data = DB::table(DB::raw("$at_tbl as eap"))
                ->join(
                    DB::raw('pharmacy_subjects_master as psm'),
                    DB::raw("CAST(eap.ea_subject_code AS TEXT)"),
                    '=',
                    DB::raw('CAST(psm.general_code AS TEXT)')
                )
                ->when($request->filled('inst_code'), function ($query) use ($request) {
                    $query->where('eap.ea_inst_code', (string) $request->input('inst_code'));
                })
                ->where('eap.ea_exam_year', (string) $request->input('exam_year'))
                ->where('eap.ea_part_sem', (string) $request->input('part_sem'))
                ->select(
                    'psm.q_code',
                    'psm.subject_name',
                    DB::raw("CASE
                    WHEN eap.ea_part_sem = 'Part_I' THEN 1
                    WHEN eap.ea_part_sem = 'Part_II' THEN 2
                END as sem_code"),
                    DB::raw('COUNT(*) as qty_to_be_printed'),
                    DB::raw('CEIL(COUNT(*) / 2.0) as no_of_packets'),
                )
                ->groupBy('psm.id', 'psm.q_code', 'psm.subject_name', 'eap.ea_part_sem', 'eap.ea_exam_year')
                ->get();




            if ($show_type == 'export') {
                return Excel::download(new printingInstruction($data->toArray()), 'printing-instruction.xlsx');
            } else {
                return response()->json([
                    'error' => false,
                    'data' => $data,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }

    }
    public function decodingList(Request $request)
    {
        try {
            $show_type = $request->input('show_type');
            $part_sem = $request->input('part_sem');
            if ($part_sem == 'Part_I') {
                $at_tbl = 'exam_attendance_pone';
            } else {
                $at_tbl = 'exam_attendance_ptwo';
            }
            $subquery = DB::table('pharmacy_exam_schedule')
                ->select(
                    DB::raw('MIN(exam_date) as exam_date'),
                    DB::raw('MIN(exam_time) as exam_time'),
                    'exam_subject_general_code'
                )
                ->groupBy('exam_subject_general_code');


            $data = DB::table(DB::raw("$at_tbl as eap"))
                ->joinSub($subquery, 'pes', function ($join) {
                    $join->on(DB::raw('CAST(eap.ea_subject_code AS TEXT)'), '=', DB::raw('CAST(pes.exam_subject_general_code AS TEXT)'));
                })
                ->join(
                    DB::raw('pharmacy_subjects_master as psm'),
                    DB::raw("CAST(eap.ea_subject_code AS TEXT)"),
                    '=',
                    DB::raw('CAST(psm.general_code AS TEXT)')
                )
                ->when($request->filled('inst_code'), function ($query) use ($request) {
                    $query->where('eap.ea_inst_code', (string) $request->input('inst_code'));
                })
                ->where('eap.ea_exam_year', (string) $request->input('exam_year'))
                ->where('eap.ea_part_sem', (string) $request->input('part_sem'))
                ->select(

                    DB::raw("CONCAT('SO',
                            psm.id,
                            CASE
                                WHEN eap.ea_part_sem = 'Part_I' THEN '1'
                                WHEN eap.ea_part_sem = 'Part_II' THEN '2'
                                ELSE '0'
                            END,
                            RIGHT(CAST(eap.ea_exam_year AS TEXT), 2)
                        ) as packet_code"), // SO+ id+sem+examyr

                    'psm.q_code as q_code',
                    'psm.subject_name',
                    DB::raw("CASE
                    WHEN eap.ea_part_sem = 'Part_I' THEN 1
                    WHEN eap.ea_part_sem = 'Part_II' THEN 2
                END as sem_code"),
                    DB::raw("TO_CHAR(pes.exam_date, 'DD-Mon-YYYY') as exam_date"),
                    'pes.exam_time'


                )
                ->groupBy('psm.id', 'psm.subject_name', 'eap.ea_part_sem', 'eap.ea_exam_year', 'pes.exam_date', 'pes.exam_time')
                ->get();
            if ($show_type == 'export') {

                return Excel::download(new decodingList($data), 'decoding-list.xlsx');

            } else {
                return response()->json([
                    'error' => false,
                    'data' => $data,
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    //Syllabus Subject Master
    public function getCenterWiseStudents($part_sem, $exam_year, $center_code)
    {

        try {

            $fileName = "center_wise_students_{$center_code}_{$exam_year}.xlsx";

            return Excel::download(new CenterWiseStudentsExport(
                $part_sem,
                $exam_year,
                $center_code
            ), $fileName);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/examinations/check-student-eligible-in-exam",
     *     tags={"Examinations"},
     *     summary="Check if a student is eligible in exam",
     *     description="Calls PostgreSQL stored function fn_check_studenteligible_inexam_studentid to verify student eligibility, registration, roll number, and payment status for an exam.",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_id", "exam_year", "semester"},
     *             @OA\Property(property="student_id", type="integer", format="int64", example=1077, description="Student ID"),
     *             @OA\Property(property="exam_year", type="string", example="2026", description="Examination Year"),
     *             @OA\Property(property="semester", type="string", example="Part-II", description="Semester / Part Name"),
     *             @OA\Property(property="admin_user_id", type="integer", format="int64", example=1077, description="Admin User ID (defaults to auth user / student ID)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student eligibility details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="boolean", example=false),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Student eligibility details fetched successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="Ispaid", type="boolean", nullable=true, example=null),
     *                 @OA\Property(property="Semester", type="string", example="Part-II"),
     *                 @OA\Property(property="StudentId", type="integer", example=1077),
     *                 @OA\Property(property="RollNumber", type="string", nullable=true, example=null),
     *                 @OA\Property(property="StudentName", type="string", example="SARMIN MIDDEY"),
     *                 @OA\Property(property="PayableAmount", type="number", nullable=true, example=null),
     *                 @OA\Property(property="EnrollmentType", type="string", example="REGULAR"),
     *                 @OA\Property(property="RegistrationNumber", type="string", example="PHARM242501978")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Student record not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function checkStudentEligibleInExam(Request $request)
    {
        $studentId = $request->input('student_id', $request->input('p_studentid', $request->input('studentId', $request->input('id'))));
        $examYear = $request->input('exam_year', $request->input('p_examyear', $request->input('examYear', $request->input('year'))));
        $semester = $request->input('semester', $request->input('p_semester', $request->input('part_sem', $request->input('semester_name'))));
        
        $adminUserId = $request->input('admin_user_id', $request->input('p_adminuserid', $request->input('adminUserId', $request->input('user_id'))));
        if (empty($adminUserId)) {
            try {
                $adminUserId = authUserId();
            } catch (\Exception $e) {
                $adminUserId = null;
            }
        }
        if (empty($adminUserId)) {
            $adminUserId = $studentId;
        }

        $validator = Validator::make([
            'student_id'    => $studentId,
            'exam_year'     => $examYear,
            'semester'      => $semester,
            'admin_user_id' => $adminUserId,
        ], [
            'student_id'    => 'required|numeric',
            'exam_year'     => 'required',
            'semester'      => 'required|string',
            'admin_user_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => true,
                'status'  => 1,
                'message' => 'Validation failed: ' . $validator->errors()->first(),
                'errors'  => $validator->errors(),
                'data'    => null,
            ], 422);
        }

        Log::channel('daily')->info('[Examinations] fn_check_studenteligible_inexam_studentid INPUT', [
            'student_id'    => $studentId,
            'exam_year'     => $examYear,
            'semester'      => $semester,
            'admin_user_id' => $adminUserId,
            'ip'            => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_check_studenteligible_inexam_studentid(?::bigint, ?::varchar, ?::varchar, ?::bigint) AS data',
                [(int) $studentId, (string) $examYear, (string) $semester, (int) $adminUserId]
            );

            if (empty($result)) {
                return response()->json([
                    'error'   => true,
                    'status'  => 3,
                    'message' => 'No data returned from database function.',
                    'data'    => null,
                ], 404);
            }

            $raw = $result[0]->data ?? null;

            if ($raw === null) {
                return response()->json([
                    'error'   => true,
                    'status'  => 3,
                    'message' => 'No student eligibility record found.',
                    'data'    => null,
                ], 404);
            }

            $data = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE && is_string($raw)) {
                Log::channel('daily')->error('[Examinations] fn_check_studenteligible_inexam_studentid JSON decode error', [
                    'error' => json_last_error_msg(),
                    'raw'   => $raw,
                ]);
                return response()->json([
                    'error'   => true,
                    'status'  => 3,
                    'message' => 'Failed to parse database response.',
                    'data'    => null,
                ], 500);
            }

            Log::channel('daily')->info('[Examinations] fn_check_studenteligible_inexam_studentid OUTPUT', [
                'student_id' => $studentId,
                'data'       => $data,
            ]);

            return response()->json([
                'error'   => false,
                'status'  => 0,
                'message' => 'Student eligibility details fetched successfully.',
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[Examinations] fn_check_studenteligible_inexam_studentid EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'error'   => true,
                'status'  => 3,
                'message' => 'An error occurred while checking student eligibility: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/examinations/ra-details-by-registration-number",
     *     tags={"Examinations"},
     *     summary="Get reported against subject marks details by student registration number",
     *     description="Calls PostgreSQL stored function fn_getradetailslistbystudentregistartionnumber to retrieve subject-wise internal/external marks and obtained marks for a reported against student.",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"registration_number", "exam_year", "part_id"},
     *             @OA\Property(property="registration_number", type="string", example="PHARM242507934", description="Student Registration Number"),
     *             @OA\Property(property="exam_year", type="string", example="2026", description="Exam Year"),
     *             @OA\Property(property="part_id", type="string", example="Part-I", description="Part / Semester Name (e.g. Part-I, Part-II)"),
     *             @OA\Property(property="admin_user_id", type="integer", format="int64", example=1, description="Admin User ID (defaults to auth user / 1)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reported against details fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Reported against details fetched successfully"),
     *             @OA\Property(property="count", type="integer", example=10),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="remarks", type="string", example="test"),
     *                     @OA\Property(property="attendence", type="string", example="PR"),
     *                     @OA\Property(property="backSubject", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="raDecission", type="integer", example=2),
     *                     @OA\Property(
     *                         property="marks",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="marksId", type="integer", example=1591),
     *                             @OA\Property(property="raStatus", type="integer", example=1),
     *                             @OA\Property(property="subjectCode", type="string", example="HUAP"),
     *                             @OA\Property(property="subjectName", type="string", example="HUMAN ANATOMY & PHYSIOLOGY"),
     *                             @OA\Property(property="externalFullMarks", type="number", format="double", example=80.00),
     *                             @OA\Property(property="internalFullMarks", type="number", format="double", example=20.00),
     *                             @OA\Property(property="externalMarksObtained", type="number", format="double", example=0.00),
     *                             @OA\Property(property="internalMarksObtained", type="number", format="double", example=0.00)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function getRaDetailsListByStudentRegistrationNumber(Request $request)
    {
        $registrationNumber = $request->input('registration_number', $request->input('p_registrationnumber', $request->input('reg_no', $request->input('studentRegistrationNumber', $request->input('registrationNumber', $request->input('student_reg_no'))))));
        $examYear = $request->input('exam_year', $request->input('p_examyear', $request->input('examYear', $request->input('year'))));
        $partId = $request->input('part_id', $request->input('p_partid', $request->input('partId', $request->input('part_sem', $request->input('semester', $request->input('part'))))));
        
        $adminUserId = $request->input('admin_user_id', $request->input('p_adminuserid', $request->input('adminUserId', $request->input('user_id'))));
        if (empty($adminUserId)) {
            try {
                $adminUserId = authUserId();
            } catch (\Exception $e) {
                $adminUserId = null;
            }
        }
        if (empty($adminUserId)) {
            $adminUserId = 1;
        }

        $validator = Validator::make([
            'registration_number' => $registrationNumber,
            'exam_year'           => $examYear,
            'part_id'             => $partId,
            'admin_user_id'       => $adminUserId,
        ], [
            'registration_number' => 'required|string|max:100',
            'exam_year'           => 'required',
            'part_id'             => 'required|string|max:50',
            'admin_user_id'       => 'required|numeric',
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

        $registrationNumber = trim($registrationNumber);
        $examYear           = (string) trim($examYear);
        $partId             = (string) trim($partId);
        $adminUserId        = (int) $adminUserId;

        Log::channel('daily')->info('[Examinations] fn_getradetailslistbystudentregistartionnumber INPUT', [
            'registration_number' => $registrationNumber,
            'exam_year'           => $examYear,
            'part_id'             => $partId,
            'admin_user_id'       => $adminUserId,
            'ip'                  => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_getradetailslistbystudentregistartionnumber(?::varchar, ?::varchar, ?::varchar, ?::bigint) AS data',
                [$registrationNumber, $examYear, $partId, $adminUserId]
            );

            if (empty($result)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No reported against details found.',
                    'count'   => 0,
                    'data'    => [],
                ], 200);
            }

            $details = [];

            foreach ($result as $row) {
                $raw = $row->data ?? null;

                if ($raw === null) {
                    continue;
                }

                $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    Log::channel('daily')->error('[Examinations] fn_getradetailslistbystudentregistartionnumber JSON decode error', [
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
                    $details = array_merge($details, $decoded);
                } elseif (is_array($decoded)) {
                    $details[] = $decoded;
                }
            }

            $groupedData = [];
            if (!empty($details)) {
                $first = $details[0];
                $remarks     = $first['remarks'] ?? null;
                $attendence  = $first['attendence'] ?? ($first['attendance'] ?? null);
                $backSubject = $first['backSubject'] ?? ($first['back_subject'] ?? []);
                $raDecission = $first['raDecission'] ?? ($first['raDecision'] ?? ($first['ra_decision'] ?? null));

                $marksList = array_map(function ($item) {
                    unset(
                        $item['remarks'],
                        $item['attendence'],
                        $item['attendance'],
                        $item['backSubject'],
                        $item['back_subject'],
                        $item['raDecission'],
                        $item['raDecision'],
                        $item['ra_decision']
                    );
                    return $item;
                }, $details);

                $groupedData = [
                    [
                        'remarks'     => $remarks,
                        'attendence'  => $attendence,
                        'backSubject' => $backSubject,
                        'raDecission' => $raDecission,
                        'marks'       => $marksList,
                    ]
                ];
            }

            Log::channel('daily')->info('[Examinations] fn_getradetailslistbystudentregistartionnumber OUTPUT', [
                'registration_number' => $registrationNumber,
                'count'               => count($details),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Reported against details fetched successfully',
                'count'   => count($details),
                'data'    => $groupedData,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[Examinations] fn_getradetailslistbystudentregistartionnumber EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 3,
                'message' => 'An error occurred while fetching reported against details: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/examinations/update-ra-student-marks",
     *     tags={"Examinations"},
     *     summary="Update reported against student marks (single or batch array)",
     *     description="Calls PostgreSQL stored function fn_update_ra_studentmarks to update internal marks, external marks, remarks, and status for one or multiple reported against student records in a loop.",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     required={"marks_id"},
     *                     @OA\Property(property="marks_id", type="integer", format="int64", example=12345, description="Marks ID (p_marksid)"),
     *                     @OA\Property(property="admin_user_id", type="integer", format="int64", example=3114, description="Admin User ID"),
     *                     @OA\Property(property="internal_marks", type="integer", example=25, description="Internal Marks (p_internalmarks)", nullable=true),
     *                     @OA\Property(property="external_marks", type="integer", example=65, description="External Marks (p_externalmarks)", nullable=true),
     *                     @OA\Property(property="remarks", type="string", example="Updated by admin", description="Remarks (p_remarks)", nullable=true),
     *                     @OA\Property(property="status", type="integer", example=0, description="Status (p_status)", nullable=true)
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="admin_user_id", type="integer", example=3114),
     *                     @OA\Property(property="remarks", type="string", example="Updated by admin"),
     *                     @OA\Property(property="status", type="integer", example=0),
     *                     @OA\Property(
     *                         property="marks",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             required={"marks_id"},
     *                             @OA\Property(property="marks_id", type="integer", format="int64", example=12345),
     *                             @OA\Property(property="internal_marks", type="integer", example=25, nullable=true),
     *                             @OA\Property(property="external_marks", type="integer", example=65, nullable=true),
     *                             @OA\Property(property="remarks", type="string", example="Updated by admin", nullable=true),
     *                             @OA\Property(property="status", type="integer", example=0, nullable=true)
     *                         )
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="RA student marks updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="error", type="boolean", example=false),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="All 2 RA student marks updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad Request / Function error"),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    /**
     * @OA\Post(
     *     path="/api/examinations/update-ra-student-marks",
     *     tags={"Examinations"},
     *     summary="Update reported against student marks and decision",
     *     description="Calls PostgreSQL stored functions fn_update_ra_studentmarks (for marks) and fn_save_ra_decission_student (for decision/back type) in a single API call.",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="student_id", type="integer", format="int64", example=3293, description="Student ID (p_student_id)"),
     *             @OA\Property(property="student_reg_no", type="string", example="PHARM242507934", description="Student Reg No (p_student_reg_no)"),
     *             @OA\Property(property="attendance", type="string", example="PR", description="Attendance (p_attendence, defaults to PR)"),
     *             @OA\Property(property="decision", type="integer", example=3, description="Decision / Back Type (p_decission)"),
     *             @OA\Property(property="subject_code", type="array", @OA\Items(type="string"), example={"HUAP", "PHCY"}, description="Subject codes JSONB (p_subjectcode)"),
     *             @OA\Property(property="admin_user_id", type="integer", format="int64", example=3114, description="Admin / Entry User ID"),
     *             @OA\Property(property="remarks", type="string", example="Updated by admin", description="Remarks (p_remarks)"),
     *             @OA\Property(
     *                 property="marks",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"marks_id"},
     *                     @OA\Property(property="marks_id", type="integer", format="int64", example=12345),
     *                     @OA\Property(property="internal_marks", type="integer", example=25, nullable=true),
     *                     @OA\Property(property="external_marks", type="integer", example=65, nullable=true),
     *                     @OA\Property(property="remarks", type="string", example="Updated by admin", nullable=true),
     *                     @OA\Property(property="status", type="integer", example=1, nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="RA student marks and decision updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="error", type="boolean", example=false),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="RA student marks and decision updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad Request / Function error"),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function updateRaStudentMarks(Request $request)
    {
        $globalAdminUserId = $request->input('admin_user_id', $request->input('p_adminuserid', $request->input('p_entry_user_id', $request->input('entry_user_id', $request->input('adminUserId', $request->input('user_id'))))));
        if (empty($globalAdminUserId)) {
            try {
                $globalAdminUserId = authUserId();
            } catch (\Exception $e) {
                $globalAdminUserId = null;
            }
        }
        if (empty($globalAdminUserId)) {
            $globalAdminUserId = 1;
        }

        $globalRemarks = $request->input('remarks', $request->input('p_remarks', $request->input('remark', $request->input('comments'))));
        $globalStatus  = $request->input('status', $request->input('p_status', $request->input('ra_status', $request->input('raStatus', 0))));

        // Decision SP parameters
        $studentId    = $request->input('student_id', $request->input('p_student_id', $request->input('studentId', $request->input('id'))));
        $studentRegNo = $request->input('student_reg_no', $request->input('p_student_reg_no', $request->input('registration_number', $request->input('registrationNumber', $request->input('reg_no', $request->input('studentRegistrationNumber'))))));
        $attendance   = $request->input('attendance', $request->input('attendence', $request->input('p_attendance', $request->input('p_attendence', 'PR'))));
        $decision     = $request->input('decision', $request->input('decission', $request->input('p_decision', $request->input('p_decission', $request->input('back_type', $request->input('backType'))))));
        $subjectCodesRaw = $request->input('subject_code', $request->input('subjectcode', $request->input('p_subjectcode', $request->input('subject_codes', $request->input('selected_subjects', $request->input('selectedSubjects', []))))));

        $rawItems = [];
        $isBatchRequest = false;

        $jsonPayload = $request->json()->all();
        if ($request->isJson() && is_array($jsonPayload) && !empty($jsonPayload) && array_is_list($jsonPayload)) {
            $rawItems = $jsonPayload;
            $isBatchRequest = true;
        } else {
            $arrayCandidate = $request->input('marks', $request->input('marks_list', $request->input('student_marks', $request->input('studentMarks', $request->input('data', $request->input('items'))))));

            if (is_array($arrayCandidate) && !empty($arrayCandidate) && array_is_list($arrayCandidate)) {
                $rawItems = $arrayCandidate;
                $isBatchRequest = true;
            } elseif (is_array($request->input('marks_id')) || is_array($request->input('marksId')) || is_array($request->input('p_marksid'))) {
                $marksIdArr  = (array) $request->input('marks_id', $request->input('marksId', $request->input('p_marksid')));
                $internalArr = (array) $request->input('internal_marks', $request->input('internalMarks', $request->input('p_internalmarks', [])));
                $externalArr = (array) $request->input('external_marks', $request->input('externalMarks', $request->input('p_externalmarks', [])));
                $remarksArr  = (array) $request->input('remarks', $request->input('remark', $request->input('p_remarks', [])));
                $statusArr   = (array) $request->input('status', $request->input('p_status', $request->input('ra_status', $request->input('raStatus', []))));

                foreach ($marksIdArr as $idx => $mId) {
                    $rawItems[] = [
                        'marks_id'       => $mId,
                        'internal_marks' => $internalArr[$idx] ?? null,
                        'external_marks' => $externalArr[$idx] ?? null,
                        'remarks'        => is_array($remarksArr) && isset($remarksArr[$idx]) ? $remarksArr[$idx] : $globalRemarks,
                        'status'         => is_array($statusArr) && isset($statusArr[$idx]) ? $statusArr[$idx] : $globalStatus,
                    ];
                }
                $isBatchRequest = count($rawItems) > 1;
            } elseif ($request->has('marks_id') || $request->has('p_marksid') || $request->has('marksId')) {
                $rawItems = [$request->all()];
                $isBatchRequest = false;
            }
        }

        $normalizedItems = [];
        if (!empty($rawItems)) {
            foreach ($rawItems as $index => $item) {
                if (!is_array($item)) {
                    return response()->json([
                        'version' => '1.0',
                        'error'   => true,
                        'status'  => 1,
                        'message' => "Invalid payload format at index {$index}.",
                        'data'    => null,
                    ], 422);
                }

                $marksId       = $item['marks_id'] ?? $item['p_marksid'] ?? $item['marksId'] ?? $item['id'] ?? null;
                $adminUserId   = $item['admin_user_id'] ?? $item['p_adminuserid'] ?? $item['adminUserId'] ?? $item['user_id'] ?? $globalAdminUserId;
                $internalMarks = $item['internal_marks'] ?? $item['p_internalmarks'] ?? $item['internalMarks'] ?? $item['internal_mark'] ?? $item['internal'] ?? null;
                $externalMarks = $item['external_marks'] ?? $item['p_externalmarks'] ?? $item['externalMarks'] ?? $item['external_mark'] ?? $item['external'] ?? null;
                $remarks       = $item['remarks'] ?? $item['p_remarks'] ?? $item['remark'] ?? $item['comments'] ?? $globalRemarks;
                $status        = $item['status'] ?? $item['p_status'] ?? $item['ra_status'] ?? $item['raStatus'] ?? ($isBatchRequest ? 0 : $globalStatus);

                $validator = Validator::make([
                    'marks_id'       => $marksId,
                    'admin_user_id'  => $adminUserId,
                    'internal_marks' => $internalMarks,
                    'external_marks' => $externalMarks,
                    'remarks'        => $remarks,
                    'status'         => $status,
                ], [
                    'marks_id'       => 'required|numeric',
                    'admin_user_id'  => 'required|numeric',
                    'internal_marks' => 'nullable|numeric|min:0',
                    'external_marks' => 'nullable|numeric|min:0',
                    'remarks'        => 'nullable|string|max:500',
                    'status'         => 'nullable|integer',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'version' => '1.0',
                        'error'   => true,
                        'status'  => 1,
                        'message' => "Validation failed for item #{$index}: " . $validator->errors()->first(),
                        'errors'  => $validator->errors(),
                        'data'    => null,
                    ], 422);
                }

                $normalizedItems[] = [
                    'index'          => $index,
                    'marks_id'       => (int) $marksId,
                    'admin_user_id'  => (int) $adminUserId,
                    'internal_marks' => ($internalMarks !== null && $internalMarks !== '') ? (int) $internalMarks : null,
                    'external_marks' => ($externalMarks !== null && $externalMarks !== '') ? (int) $externalMarks : null,
                    'remarks'        => $remarks !== null ? (string) trim($remarks) : null,
                    'status'         => ($status !== null && $status !== '') ? (int) $status : 0,
                ];
            }
        }

        // Map subject codes for decision SP
        $mappedSubjectCodes = [];
        if (is_array($subjectCodesRaw)) {
            foreach ($subjectCodesRaw as $s) {
                if (is_string($s) && !is_numeric($s)) {
                    $mappedSubjectCodes[] = strtoupper(trim($s));
                } else {
                    $foundCode = null;
                    foreach ($rawItems as $rItem) {
                        $rMarksId = $rItem['marks_id'] ?? $rItem['p_marksid'] ?? $rItem['marksId'] ?? $rItem['id'] ?? null;
                        if ((string)$rMarksId === (string)$s) {
                            $foundCode = $rItem['subject_code'] ?? $rItem['subjectCode'] ?? $rItem['subject'] ?? null;
                            break;
                        }
                    }
                    if ($foundCode) {
                        $mappedSubjectCodes[] = strtoupper(trim($foundCode));
                    } else {
                        $mappedSubjectCodes[] = (string) $s;
                    }
                }
            }
        } elseif (is_string($subjectCodesRaw) && trim($subjectCodesRaw) !== '') {
            $decodedJson = json_decode($subjectCodesRaw, true);
            if (is_array($decodedJson)) {
                $mappedSubjectCodes = $decodedJson;
            } else {
                $mappedSubjectCodes = array_filter(array_map('trim', explode(',', $subjectCodesRaw)));
            }
        }

        $subjectCodeJson = json_encode(array_values($mappedSubjectCodes));

        DB::beginTransaction();

        try {
            $success = 0;
            $failed = 0;
            $results = [];

            // 1. Execute SP 1: fn_update_ra_studentmarks for each mark item
            foreach ($normalizedItems as $item) {
                $marksId       = $item['marks_id'];
                $adminUserId   = $item['admin_user_id'];
                $internalMarks = $item['internal_marks'];
                $externalMarks = $item['external_marks'];
                $remarks       = $item['remarks'];
                $status        = $item['status'];

                $result = DB::select(
                    'SELECT public.fn_update_ra_studentmarks(?::bigint, ?::bigint, ?::integer, ?::integer, ?::varchar, ?::smallint) AS data',
                    [$marksId, $adminUserId, $internalMarks, $externalMarks, $remarks, $status]
                );

                if (empty($result)) {
                    $failed++;
                    $results[] = [
                        'index'    => $item['index'],
                        'marks_id' => $marksId,
                        'status'   => 'failed',
                        'error'    => 'No response returned from fn_update_ra_studentmarks',
                    ];
                    continue;
                }

                $raw = $result[0]->data ?? null;
                if ($raw === null && !empty($result[0])) {
                    $raw = (array) $result[0];
                }

                $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    $failed++;
                    $results[] = [
                        'index'    => $item['index'],
                        'marks_id' => $marksId,
                        'status'   => 'failed',
                        'error'    => 'Failed to parse fn_update_ra_studentmarks response',
                    ];
                    continue;
                }

                $errorCode = isset($decoded['p_errorcode']) ? (int) $decoded['p_errorcode'] : (isset($decoded['errorcode']) ? (int) $decoded['errorcode'] : 0);

                if ($errorCode === 0) {
                    $success++;
                    $results[] = [
                        'index'    => $item['index'],
                        'marks_id' => $marksId,
                        'status'   => 'success',
                        'result'   => $decoded,
                    ];
                } else {
                    $failed++;
                    $results[] = [
                        'index'      => $item['index'],
                        'marks_id'   => $marksId,
                        'status'     => 'failed',
                        'error_code' => $errorCode,
                        'message'    => $decoded['p_message'] ?? $decoded['message'] ?? 'fn_update_ra_studentmarks error',
                        'result'     => $decoded,
                    ];
                }
            }

            // 2. Execute SP 2: fn_save_ra_decission_student if decision / student details are provided
            $decisionResult = null;
            $hasDecisionCall = ($studentId !== null || $studentRegNo !== null || $decision !== null);

            if ($hasDecisionCall) {
                $sp2StudentId    = $studentId ? (int) $studentId : 0;
                $sp2StudentRegNo = (string) ($studentRegNo ?? '');
                $sp2Attendance   = (string) ($attendance ?? 'PR');
                $sp2Decision     = $decision !== null ? (int) $decision : 0;

                Log::channel('daily')->info('[Examinations] fn_save_ra_decission_student INPUT', [
                    'student_id'     => $sp2StudentId,
                    'student_reg_no' => $sp2StudentRegNo,
                    'attendance'     => $sp2Attendance,
                    'decision'       => $sp2Decision,
                    'subject_code'   => $subjectCodeJson,
                    'remarks'        => $globalRemarks,
                    'entry_user_id'  => $globalAdminUserId,
                ]);

                $sp2Res = DB::select(
                    'SELECT public.fn_save_ra_decission_student(?::bigint, ?::varchar, ?::varchar, ?::integer, ?::jsonb, ?::varchar, ?::bigint) AS data',
                    [$sp2StudentId, $sp2StudentRegNo, $sp2Attendance, $sp2Decision, $subjectCodeJson, $globalRemarks, $globalAdminUserId]
                );

                if (!empty($sp2Res)) {
                    $raw2 = $sp2Res[0]->data ?? null;
                    if ($raw2 === null && !empty($sp2Res[0])) {
                        $raw2 = (array) $sp2Res[0];
                    }

                    $decisionResult = is_string($raw2) ? json_decode($raw2, true) : (array) $raw2;
                    $decisionErrorCode = isset($decisionResult['p_errorcode'])
                        ? (int) $decisionResult['p_errorcode']
                        : (isset($decisionResult['errorcode']) ? (int) $decisionResult['errorcode'] : 0);

                    if ($decisionErrorCode !== 0) {
                        DB::rollBack();
                        return response()->json([
                            'version' => '1.0',
                            'error'   => true,
                            'status'  => $decisionErrorCode,
                            'message' => $decisionResult['p_message'] ?? $decisionResult['message'] ?? 'Failed to save RA decision (error code: ' . $decisionErrorCode . ')',
                            'data'    => [
                                'marks_results'   => $results,
                                'decision_result' => $decisionResult,
                            ],
                        ], 400);
                    }
                }
            }

            // Check marks outcome
            if ($failed > 0 && $success === 0 && !empty($normalizedItems)) {
                DB::rollBack();
                return response()->json([
                    'version' => '1.0',
                    'error'   => true,
                    'status'  => 1,
                    'message' => "All {$failed} RA student marks failed to update",
                    'data'    => [
                        'total'           => count($normalizedItems),
                        'success'         => $success,
                        'failed'          => $failed,
                        'results'         => $results,
                        'decision_result' => $decisionResult,
                    ],
                ], 400);
            }

            DB::commit();

            Log::channel('daily')->info('[Examinations] updateRaStudentMarks COMPLETE', [
                'marks_success'   => $success,
                'marks_failed'    => $failed,
                'decision_result' => $decisionResult,
            ]);

            // Single item non-batch response
            if (!$isBatchRequest && count($normalizedItems) === 1) {
                $firstResult = $results[0] ?? [];
                $resData = $firstResult['result'] ?? ['p_errorcode' => 0];
                if ($decisionResult !== null) {
                    $resData['decision_result'] = $decisionResult;
                }

                return response()->json([
                    'version' => '1.0',
                    'error'   => false,
                    'status'  => 0,
                    'message' => 'RA student marks and decision updated successfully',
                    'data'    => $resData,
                ], 200);
            }

            $overallError  = $failed > 0;
            $overallStatus = $failed === 0 ? 0 : ($success > 0 ? 2 : 1);
            $message = $failed === 0
                ? "RA student marks and decision updated successfully"
                : "Partial success: {$success} succeeded, {$failed} failed";

            return response()->json([
                'version' => '1.0',
                'error'   => $overallError,
                'status'  => $overallStatus,
                'message' => $message,
                'data'    => [
                    'total'           => count($normalizedItems),
                    'success'         => $success,
                    'failed'          => $failed,
                    'results'         => $results,
                    'decision_result' => $decisionResult,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('daily')->error('[Examinations] updateRaStudentMarks EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'version' => '1.0',
                'error'   => true,
                'status'  => 3,
                'message' => 'An error occurred while updating RA student marks and decision: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/examinations/all-examination-institutes",
     *     tags={"Examinations", "Admin Institutes"},
     *     summary="Get all examination institutes by institute/center code",
     *     description="Calls PostgreSQL stored function fn_admin_getallexaminationinstitutes to retrieve all examination institutes for a given institute/center code and admin user ID.",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"inst_code"},
     *             @OA\Property(property="inst_code", type="string", example="JCG", description="Institute / Center Code (p_inst_code)"),
     *             @OA\Property(property="admin_user_id", type="integer", format="int64", example=5447, description="Admin User ID (p_admin_user_id, defaults to auth user)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Examination institutes fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Examination institutes fetched successfully"),
     *             @OA\Property(property="count", type="integer", example=2),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="instituteId", type="integer", example=187),
     *                     @OA\Property(property="instituteCode", type="string", example="AMNA"),
     *                     @OA\Property(property="instituteName", type="string", example="AAMNA COLLEGE OF PHARMACEUTICAL SCIENCE & RESEARCH"),
     *                     @OA\Property(property="instituteType", type="string", example="Private")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function getAllExaminationInstitutes(Request $request)
    {
        $instCode = $request->input('inst_code', $request->input('p_inst_code', $request->input('institute_code', $request->input('instituteCode', $request->input('i_code', $request->input('center_code'))))));
        $adminUserId = $request->input('admin_user_id', $request->input('p_admin_user_id', $request->input('adminUserId', $request->input('user_id'))));

        if (empty($adminUserId)) {
            try {
                $adminUserId = authUserId();
            } catch (\Exception $e) {
                $adminUserId = null;
            }
        }
        if (empty($adminUserId)) {
            $adminUserId = 1;
        }

        $validator = Validator::make([
            'inst_code'     => $instCode,
            'admin_user_id' => $adminUserId,
        ], [
            'inst_code'     => 'required|string|max:100',
            'admin_user_id' => 'required|numeric',
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

        $instCode    = trim($instCode);
        $adminUserId = (int) $adminUserId;

        Log::channel('daily')->info('[Examinations] fn_admin_getallexaminationinstitutes INPUT', [
            'inst_code'     => $instCode,
            'admin_user_id' => $adminUserId,
            'ip'            => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_admin_getallexaminationinstitutes(?::varchar, ?::bigint) AS data',
                [$instCode, $adminUserId]
            );

            if (empty($result)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No examination institutes found.',
                    'count'   => 0,
                    'data'    => [],
                ], 200);
            }

            $institutes = [];

            foreach ($result as $row) {
                $raw = $row->data ?? null;

                if ($raw === null) {
                    continue;
                }

                $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    Log::channel('daily')->error('[Examinations] fn_admin_getallexaminationinstitutes JSON decode error', [
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
                    $institutes = array_merge($institutes, $decoded);
                } elseif (is_array($decoded)) {
                    $institutes[] = $decoded;
                }
            }

            Log::channel('daily')->info('[Examinations] fn_admin_getallexaminationinstitutes OUTPUT', [
                'inst_code' => $instCode,
                'count'     => count($institutes),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Examination institutes fetched successfully',
                'count'   => count($institutes),
                'data'    => $institutes,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[Examinations] fn_admin_getallexaminationinstitutes EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 3,
                'message' => 'An error occurred while fetching examination institutes: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/examinations/save-examination-center",
     *     tags={"Examinations"},
     *     summary="Save examination center allocation (single or multiple destination institutes)",
     *     description="Calls PostgreSQL stored function fn_save_examinationcenter in a loop to assign or map one or multiple destination examination center(s) to a source institute for an exam year and semester.",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"source_inst_code", "destination_inst_code", "exam_year", "semester"},
     *             @OA\Property(property="source_inst_code", type="string", example="JCG", description="Source Institute Code (p_source_inst_code)"),
     *             @OA\Property(
     *                 property="destination_inst_code",
     *                 oneOf={
     *                     @OA\Schema(type="string", example="SRV", description="Single destination code"),
     *                     @OA\Schema(type="array", @OA\Items(type="string"), example={"SRV", "AMNA"}, description="Array of destination codes"),
     *                     @OA\Schema(type="array", @OA\Items(type="object", @OA\Property(property="instituteCode", type="string", example="SRV")), description="Array of institute objects")
     *                 },
     *                 description="Destination Center Institute Code(s) (p_destination_inst_code)"
     *             ),
     *             @OA\Property(property="exam_year", type="string", example="2026", description="Exam Year (p_examyear)"),
     *             @OA\Property(property="semester", type="string", example="Part-II", description="Semester / Part Name (p_semester)"),
     *             @OA\Property(property="user_id", type="integer", format="int64", example=12, description="User ID (p_userid, defaults to auth user)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Examination center(s) saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Examination center(s) saved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad Request / Stored function error"),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function saveExaminationCenter(Request $request)
    {
        $sourceInstCode = $request->input('source_inst_code', $request->input('p_source_inst_code', $request->input('source_institute_code', $request->input('source_code', $request->input('src_inst_code')))));
        $destinationRaw = $request->input('destination_inst_code', $request->input('p_destination_inst_code', $request->input('destination_institute_code', $request->input('destination_institutes', $request->input('dest_inst_code', $request->input('center_code', $request->input('destination_code', $request->input('destination_codes'))))))));
        $examYear       = $request->input('exam_year', $request->input('p_examyear', $request->input('examyear', $request->input('year'))));
        $semester       = $request->input('semester', $request->input('p_semester', $request->input('part_sem', $request->input('part_id', $request->input('part')))));
        $userId         = $request->input('user_id', $request->input('p_userid', $request->input('admin_user_id', $request->input('userid', $request->input('adminUserId')))));

        if (empty($userId)) {
            try {
                $userId = authUserId();
            } catch (\Exception $e) {
                $userId = null;
            }
        }
        if (empty($userId)) {
            $userId = 1;
        }

        // Normalize destination institute codes into a flat list of strings
        $destinationList = [];
        if (is_array($destinationRaw)) {
            foreach ($destinationRaw as $item) {
                if (is_array($item)) {
                    $code = $item['destination_inst_code'] ?? ($item['instituteCode'] ?? ($item['institute_code'] ?? ($item['code'] ?? ($item['value'] ?? ($item['i_code'] ?? null)))));
                    if (!empty($code)) {
                        $destinationList[] = trim((string) $code);
                    }
                } elseif (is_string($item) || is_numeric($item)) {
                    $code = trim((string) $item);
                    if ($code !== '') {
                        $destinationList[] = $code;
                    }
                }
            }
        } elseif (is_string($destinationRaw) || is_numeric($destinationRaw)) {
            $str = trim((string) $destinationRaw);
            if ($str !== '') {
                if (str_contains($str, ',')) {
                    $parts = explode(',', $str);
                    foreach ($parts as $p) {
                        $p = trim($p);
                        if ($p !== '') {
                            $destinationList[] = $p;
                        }
                    }
                } else {
                    $destinationList[] = $str;
                }
            }
        }

        $destinationList = array_values(array_unique($destinationList));

        $validator = Validator::make([
            'source_inst_code'      => $sourceInstCode,
            'destination_inst_code' => $destinationList,
            'exam_year'             => $examYear,
            'semester'              => $semester,
            'user_id'               => $userId,
        ], [
            'source_inst_code'      => 'required|string|max:100',
            'destination_inst_code' => 'required|array|min:1',
            'destination_inst_code.*' => 'required|string|max:100',
            'exam_year'             => 'required|string|max:20',
            'semester'              => 'required|string|max:50',
            'user_id'               => 'required|numeric',
        ], [
            'destination_inst_code.required' => 'At least one destination institute code is required.',
            'destination_inst_code.min'      => 'At least one destination institute code is required.',
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

        $sourceInstCode = trim($sourceInstCode);
        $examYear       = (string) trim($examYear);
        $semester       = (string) trim($semester);
        $userId         = (int) $userId;

        Log::channel('daily')->info('[Examinations] fn_save_examinationcenter INPUT', [
            'source_inst_code'       => $sourceInstCode,
            'destination_inst_codes' => $destinationList,
            'exam_year'              => $examYear,
            'semester'               => $semester,
            'user_id'                => $userId,
            'ip'                     => $request->ip(),
        ]);

        try {
            DB::beginTransaction();

            $results      = [];
            $successCount = 0;
            $failedCount  = 0;

            foreach ($destinationList as $destinationInstCode) {
                $result = DB::select(
                    'SELECT public.fn_save_examinationcenter(?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::bigint) AS data',
                    [$sourceInstCode, $destinationInstCode, $examYear, $semester, $userId]
                );

                if (empty($result)) {
                    $failedCount++;
                    $results[] = [
                        'destination_inst_code' => $destinationInstCode,
                        'status'                => 1,
                        'message'               => 'No response from database function.',
                        'data'                  => null,
                    ];
                    continue;
                }

                $raw = $result[0]->data ?? null;

                if ($raw === null) {
                    $failedCount++;
                    $results[] = [
                        'destination_inst_code' => $destinationInstCode,
                        'status'                => 1,
                        'message'               => 'Database function returned empty result.',
                        'data'                  => null,
                    ];
                    continue;
                }

                $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    $failedCount++;
                    $results[] = [
                        'destination_inst_code' => $destinationInstCode,
                        'status'                => 3,
                        'message'               => 'Failed to parse database response.',
                        'data'                  => null,
                    ];
                    continue;
                }

                $errorCode = $decoded['p_errorcode'] ?? ($decoded['errorCode'] ?? ($decoded['error_code'] ?? null));

                if ($errorCode !== null && (int) $errorCode !== 0) {
                    $failedCount++;
                    $results[] = [
                        'destination_inst_code' => $destinationInstCode,
                        'status'                => (int) $errorCode,
                        'message'               => $decoded['p_errormessage'] ?? ($decoded['message'] ?? 'Failed to save examination center.'),
                        'data'                  => $decoded,
                    ];
                } else {
                    $successCount++;
                    $results[] = [
                        'destination_inst_code' => $destinationInstCode,
                        'status'                => 0,
                        'message'               => 'Saved successfully',
                        'data'                  => $decoded,
                    ];
                }
            }

            Log::channel('daily')->info('[Examinations] fn_save_examinationcenter OUTPUT', [
                'total'   => count($destinationList),
                'success' => $successCount,
                'failed'  => $failedCount,
            ]);

            if ($failedCount > 0 && $successCount === 0) {
                DB::rollBack();
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'Failed to save examination center(s).',
                    'data'    => [
                        'total'   => count($destinationList),
                        'success' => $successCount,
                        'failed'  => $failedCount,
                        'results' => $results,
                    ],
                ], 400);
            }

            DB::commit();

            // For single-item string request, provide clean single object payload compatibility
            if (count($destinationList) === 1 && !is_array($destinationRaw)) {
                $firstResult = $results[0];
                return response()->json([
                    'version' => '1.0',
                    'status'  => $firstResult['status'],
                    'message' => 'Examination center saved successfully',
                    'data'    => $firstResult['data'] ?? ['p_errorcode' => 0],
                ], 200);
            }

            return response()->json([
                'version' => '1.0',
                'status'  => $failedCount === 0 ? 0 : 2,
                'message' => $failedCount === 0
                    ? 'Examination center(s) saved successfully'
                    : "Partial success: {$successCount} saved, {$failedCount} failed",
                'data'    => [
                    'total'   => count($destinationList),
                    'success' => $successCount,
                    'failed'  => $failedCount,
                    'results' => $results,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('daily')->error('[Examinations] fn_save_examinationcenter EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 3,
                'message' => 'An error occurred while saving examination center: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/examinations/save-routine",
     *     tags={"Examinations", "Schedule"},
     *     summary="Save examination routine / schedule (single or batch)",
     *     description="Calls PostgreSQL stored function fn_admin_saveroutine to save or update subject examination dates for an exam year and semester.",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     required={"exam_year", "semester", "exam_date", "subject_code"},
     *                     @OA\Property(property="routine_id", type="integer", format="int64", example=0, description="Routine ID (0 for new, >0 for edit)"),
     *                     @OA\Property(property="exam_year", type="string", example="2026", description="Exam Year (p_examyear)"),
     *                     @OA\Property(property="semester", type="string", example="Part_II", description="Semester / Part Name (p_semester)"),
     *                     @OA\Property(property="exam_date", type="string", format="date-time", example="2026-09-20 10:00:00", description="Exam Date & Time (p_examdate)"),
     *                     @OA\Property(property="subject_code", type="string", example="CPHM", description="Subject Code (p_subjectcode)"),
     *                     @OA\Property(property="entry_user_id", type="integer", format="int64", example=3114, description="Entry Admin User ID (p_entry_user_id)")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="exam_year", type="string", example="2026"),
     *                     @OA\Property(property="semester", type="string", example="Part_II"),
     *                     @OA\Property(property="entry_user_id", type="integer", example=3114),
     *                     @OA\Property(
     *                         property="routines",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             required={"exam_date", "subject_code"},
     *                             @OA\Property(property="routine_id", type="integer", example=0),
     *                             @OA\Property(property="exam_date", type="string", example="2026-09-20 10:00:00"),
     *                             @OA\Property(property="subject_code", type="string", example="CPHM")
     *                         )
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Routine saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Examination routine saved successfully"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="p_errorcode", type="integer", example=0))
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad Request / Stored function error"),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function saveRoutine(Request $request)
    {
        $rawContent = $request->all();
        $isRawList = is_array($rawContent) && array_is_list($rawContent);

        $defaultExamYear    = $request->input('exam_year', $request->input('p_examyear', $request->input('examYear', $request->input('year'))));
        $defaultSemester    = $request->input('semester', $request->input('p_semester', $request->input('part_sem', $request->input('part_id', $request->input('part')))));
        $defaultEntryUserId = $request->input('entry_user_id', $request->input('p_entry_user_id', $request->input('admin_user_id', $request->input('user_id', $request->input('userId')))));

        if (empty($defaultEntryUserId)) {
            try {
                $defaultEntryUserId = authUserId();
            } catch (\Exception $e) {
                $defaultEntryUserId = null;
            }
        }
        if (empty($defaultEntryUserId)) {
            $defaultEntryUserId = 1;
        }

        $routinesArray = $request->input('routines', $request->input('routine_list', $request->input('items', [])));

        $items = [];

        if ($isRawList) {
            $items = $rawContent;
        } elseif (!empty($routinesArray) && is_array($routinesArray)) {
            $items = $routinesArray;
        } else {
            // Single item
            $items = [
                [
                    'routine_id'    => $request->input('routine_id', $request->input('p_routineid', $request->input('routineId', $request->input('id', 0)))),
                    'exam_year'     => $defaultExamYear,
                    'semester'      => $defaultSemester,
                    'exam_date'     => $request->input('exam_date', $request->input('p_examdate', $request->input('examDate', $request->input('date')))),
                    'subject_code'  => $request->input('subject_code', $request->input('p_subjectcode', $request->input('subjectCode', $request->input('subject')))),
                    'entry_user_id' => $defaultEntryUserId,
                ]
            ];
        }

        $normalizedItems = [];
        foreach ($items as $index => $item) {
            $routineId   = isset($item['routine_id']) ? (int) $item['routine_id'] : (isset($item['p_routineid']) ? (int) $item['p_routineid'] : (isset($item['routineId']) ? (int) $item['routineId'] : (isset($item['id']) ? (int) $item['id'] : 0)));
            $examYear    = $item['exam_year'] ?? ($item['p_examyear'] ?? ($item['examYear'] ?? $defaultExamYear));
            $semester    = $item['semester'] ?? ($item['p_semester'] ?? ($item['part_sem'] ?? ($item['part_id'] ?? $defaultSemester)));
            $examDate    = $item['exam_date'] ?? ($item['p_examdate'] ?? ($item['examDate'] ?? ($item['date'] ?? null)));
            $subjectCode = $item['subject_code'] ?? ($item['p_subjectcode'] ?? ($item['subjectCode'] ?? ($item['subject'] ?? null)));
            $entryUserId = $item['entry_user_id'] ?? ($item['p_entry_user_id'] ?? ($item['admin_user_id'] ?? ($item['user_id'] ?? $defaultEntryUserId)));

            if (empty($entryUserId)) {
                $entryUserId = $defaultEntryUserId;
            }

            $normalizedItems[] = [
                'index'         => $index,
                'routine_id'    => (int) $routineId,
                'exam_year'     => $examYear !== null ? trim((string) $examYear) : null,
                'semester'      => $semester !== null ? trim((string) $semester) : null,
                'exam_date'     => $examDate !== null ? trim((string) $examDate) : null,
                'subject_code'  => $subjectCode !== null ? trim((string) $subjectCode) : null,
                'entry_user_id' => (int) $entryUserId,
            ];
        }

        if (empty($normalizedItems)) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'No routine data provided.',
                'data'    => null,
            ], 422);
        }

        // Validate each item
        $validator = Validator::make(['items' => $normalizedItems], [
            'items'                 => 'required|array|min:1',
            'items.*.exam_year'     => 'required|string|max:20',
            'items.*.semester'      => 'required|string|max:50',
            'items.*.exam_date'     => 'required',
            'items.*.subject_code'  => 'required|string|max:50',
            'items.*.entry_user_id' => 'required|numeric',
        ], [
            'items.*.exam_year.required'    => 'Exam year is required for each routine item.',
            'items.*.semester.required'     => 'Semester is required for each routine item.',
            'items.*.exam_date.required'    => 'Exam date is required for each routine item.',
            'items.*.subject_code.required' => 'Subject code is required for each routine item.',
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

        Log::channel('daily')->info('[Examinations] fn_admin_saveroutine INPUT', [
            'count' => count($normalizedItems),
            'items' => $normalizedItems,
            'ip'    => $request->ip(),
        ]);

        try {
            DB::beginTransaction();

            $results      = [];
            $successCount = 0;
            $failedCount  = 0;

            foreach ($normalizedItems as $item) {
                $formattedDate = $item['exam_date'];
                try {
                    $timestamp = strtotime($formattedDate);
                    if ($timestamp !== false) {
                        $formattedDate = date('Y-m-d H:i:s', $timestamp);
                    }
                } catch (\Exception $de) {
                    // keep original date string
                }

                $result = DB::select(
                    'SELECT public.fn_admin_saveroutine(?::bigint, ?::varchar, ?::varchar, ?::timestamp, ?::varchar, ?::bigint) AS data',
                    [
                        $item['routine_id'],
                        $item['exam_year'],
                        $item['semester'],
                        $formattedDate,
                        $item['subject_code'],
                        $item['entry_user_id']
                    ]
                );

                if (empty($result)) {
                    $failedCount++;
                    $results[] = [
                        'subject_code' => $item['subject_code'],
                        'status'       => 1,
                        'message'      => 'No response from database function.',
                        'data'         => null,
                    ];
                    continue;
                }

                $raw = $result[0]->data ?? null;

                if ($raw === null) {
                    $failedCount++;
                    $results[] = [
                        'subject_code' => $item['subject_code'],
                        'status'       => 1,
                        'message'      => 'Database function returned empty result.',
                        'data'         => null,
                    ];
                    continue;
                }

                $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    $failedCount++;
                    $results[] = [
                        'subject_code' => $item['subject_code'],
                        'status'       => 3,
                        'message'      => 'Failed to parse database response.',
                        'data'         => null,
                    ];
                    continue;
                }

                $errorCode = $decoded['p_errorcode'] ?? ($decoded['errorCode'] ?? ($decoded['error_code'] ?? null));

                if ($errorCode !== null && (int) $errorCode !== 0) {
                    $failedCount++;
                    $results[] = [
                        'subject_code' => $item['subject_code'],
                        'status'       => (int) $errorCode,
                        'message'      => $decoded['p_errormessage'] ?? ($decoded['message'] ?? 'Failed to save routine.'),
                        'data'         => $decoded,
                    ];
                } else {
                    $successCount++;
                    $results[] = [
                        'subject_code' => $item['subject_code'],
                        'status'       => 0,
                        'message'      => 'Routine saved successfully',
                        'data'         => $decoded,
                    ];
                }
            }

            Log::channel('daily')->info('[Examinations] fn_admin_saveroutine OUTPUT', [
                'total'   => count($normalizedItems),
                'success' => $successCount,
                'failed'  => $failedCount,
            ]);

            if ($failedCount > 0 && $successCount === 0) {
                DB::rollBack();
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'Failed to save examination routine.',
                    'data'    => [
                        'total'   => count($normalizedItems),
                        'success' => $successCount,
                        'failed'  => $failedCount,
                        'results' => $results,
                    ],
                ], 400);
            }

            DB::commit();

            // Single-item clean response format
            if (count($normalizedItems) === 1 && !$isRawList && empty($routinesArray)) {
                $firstResult = $results[0];
                return response()->json([
                    'version' => '1.0',
                    'status'  => $firstResult['status'],
                    'message' => 'Examination routine saved successfully',
                    'data'    => $firstResult['data'] ?? ['p_errorcode' => 0],
                ], 200);
            }

            return response()->json([
                'version' => '1.0',
                'status'  => $failedCount === 0 ? 0 : 2,
                'message' => $failedCount === 0
                    ? 'Examination routine(s) saved successfully'
                    : "Partial success: {$successCount} saved, {$failedCount} failed",
                'data'    => [
                    'total'   => count($normalizedItems),
                    'success' => $successCount,
                    'failed'  => $failedCount,
                    'results' => $results,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('daily')->error('[Examinations] fn_admin_saveroutine EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 3,
                'message' => 'An error occurred while saving examination routine: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/examinations/routine-list",
     *     tags={"Examinations", "Schedule"},
     *     summary="Get examination routine list by exam year and semester",
     *     description="Calls PostgreSQL stored function fn_get_routinelist to retrieve the examination schedule/routine for an exam year and semester.",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"exam_year", "semester"},
     *             @OA\Property(property="exam_year", type="string", example="2026", description="Exam Year (p_examyear)"),
     *             @OA\Property(property="semester", type="string", example="Part_II", description="Semester / Part Name (p_semester)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Routine list fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Examination routine fetched successfully"),
     *             @OA\Property(property="count", type="integer", example=3),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="routineId", type="integer", example=1),
     *                     @OA\Property(property="examDate", type="string", example="2026-09-20T10:00:00"),
     *                     @OA\Property(property="subjectCode", type="string", example="CPHM"),
     *                     @OA\Property(property="subjectName", type="string", example="COMMUNITY PHARMACY & MANAGEMENT")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function getRoutineList(Request $request)
    {
        $examYear = $request->input('exam_year', $request->input('p_examyear', $request->input('examYear', $request->input('year'))));
        $semester = $request->input('semester', $request->input('p_semester', $request->input('part_sem', $request->input('part_id', $request->input('part')))));

        $validator = Validator::make([
            'exam_year' => $examYear,
            'semester'  => $semester,
        ], [
            'exam_year' => 'required|string|max:20',
            'semester'  => 'required|string|max:50',
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

        $examYear = (string) trim($examYear);
        $semester = (string) trim($semester);

        Log::channel('daily')->info('[Examinations] fn_get_routinelist INPUT', [
            'exam_year' => $examYear,
            'semester'  => $semester,
            'ip'        => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_get_routinelist(?::varchar, ?::varchar) AS data',
                [$examYear, $semester]
            );

            if (empty($result)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No examination routine found.',
                    'count'   => 0,
                    'data'    => [],
                ], 200);
            }

            $routines = [];

            foreach ($result as $row) {
                $raw = $row->data ?? null;

                if ($raw === null) {
                    continue;
                }

                $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    Log::channel('daily')->error('[Examinations] fn_get_routinelist JSON decode error', [
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
                    $routines = array_merge($routines, $decoded);
                } elseif (is_array($decoded)) {
                    $routines[] = $decoded;
                }
            }

            Log::channel('daily')->info('[Examinations] fn_get_routinelist OUTPUT', [
                'exam_year' => $examYear,
                'semester'  => $semester,
                'count'     => count($routines),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Examination routine fetched successfully',
                'count'   => count($routines),
                'data'    => $routines,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[Examinations] fn_get_routinelist EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 3,
                'message' => 'An error occurred while fetching examination routine: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

}