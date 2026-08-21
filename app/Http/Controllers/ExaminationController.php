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
     *             @OA\Property(property="count", type="integer", example=5),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="subjectCode", type="string", example="HUAP"),
     *                     @OA\Property(property="subjectName", type="string", example="THEORY"),
     *                     @OA\Property(property="externalFullMarks", type="number", format="double", example=80.00),
     *                     @OA\Property(property="internalFullMarks", type="number", format="double", example=20.00),
     *                     @OA\Property(property="externalMarksObtained", type="number", format="double", example=25.00),
     *                     @OA\Property(property="internalMarksObtained", type="number", format="double", example=16.00)
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

            Log::channel('daily')->info('[Examinations] fn_getradetailslistbystudentregistartionnumber OUTPUT', [
                'registration_number' => $registrationNumber,
                'count'               => count($details),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Reported against details fetched successfully',
                'count'   => count($details),
                'data'    => $details,
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

}