<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\MarksUser;
use App\Models\AttendenceXi;
use App\Models\MarksEntryXi;
use App\Models\PaperMarksXi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\MarksEntryListResource;

use App\Models\Answersheetmasking;

class MarksEntryController extends Controller
{
    public function instituteList(Request $request)
    {
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


        return response()->json([
            'error' => false,
            'data' => $institute_data,
            'message' => 'No Data available'
        ], 200);

    }
    //Subject List for Marks Entry
    function marksEntrysubjectList(Request $request)
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

    public function list(Request $request)
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
        /* return response()->json([
                'error' => true,
                'message' => 'Masking is not available',
                'data'      =>  $masking_status
            ]); */


       /*  if($masking_status ==0){
            return response()->json([
                'error' => true,
                'message' => 'Masking is not available'
            ]);
        }   */


        if($evaluator_type === "COLLEGE"){
            $evaluator_type = 'EXAMINER';
        }
        else{
            $evaluator_type = $request->evaluator_type;

        }
        $type           = explode('_', $marks_type)[1];
        $ev_type = strtolower($evaluator_type);
        // check attendance final submit

        $table_name = $part_sem == 'Part_I' ? 'exam_attendance_pone' : 'exam_attendance_ptwo';
        $marks_tbl = $part_sem == 'Part_I' ? 'pharmacy_exam_marks_pone' : 'pharmacy_exam_marks_ptwo';

        $subject_marks = DB::table('pharmacy_subjects_master')
            ->where('general_code', $subject)
            #->where('syllabus', $session_year )
            ->where('semester', $part_sem)
            ->where('is_active', 1)
            ->first();

        $max_marks = $subject_marks->{"subject_{$type}_total_marks"};

        $list = DB::table($table_name)
                ->join('pharmacy_register_student_final', 's_appl_form_num', '=', 'ea_form_number')
                ->leftJoin($marks_tbl, function ($join) use ($subject, $part_sem, $exam_year) {
                    $join->on('em_roll_number', '=', 'ea_roll_number')
                        ->where('em_subject_code', '=', $subject)
                        ->where('em_part_sem', '=', $part_sem)
                        ->where('em_exam_year', '=', $exam_year);
                })
                ->where('ea_exam_year', $exam_year)
                ->where('ea_part_sem', $part_sem)
                ->where('ea_subject_code', $subject)
                ->where('ea_inst_code', $inst_code)
                ->whereRaw("(ea_is_final_submit::json->>'is_{$type}')::boolean = true")
                ->get();

        $marksCount = DB::table($marks_tbl)
                    ->where('em_subject_code', $subject)
                    ->where('em_part_sem', $part_sem)
                    ->where('em_exam_year', $exam_year)
                    ->whereNotNull("em_{$type}_marks_{$ev_type}")
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
            $final_marks   = !is_null($marks) ? $marks : ($is_absent ? 'AB' : ($is_ra ? 'RA' : null));

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

                'is_final'=> json_decode($item->{"is_final_$evaluator"}, true),
                "marks"=> $final_marks,
                "max_marks"=> $max_marks,
                "masking_active"    => ($masking_status >0 && $marks_type =='mark_external') ? true: false,
                "mask_code"         => $item->ea_masking_code,
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

        //all success Scenario
        return response()->json([
            'error' => false,
            'students' => $list,
            'max_marks' => $max_marks,
            'marks_entry' => $marksCount,
            'message' => 'No Data available'
        ], 200);
    }

    private function getMarks($data, $marks_type, $evaluator_type)
    {
        $exam_type  =   explode('_', $marks_type)[1];

        $evaluator_type_lower = strtolower($evaluator_type);

        $marks_key  =   "em_".$exam_type."_marks_".$evaluator_type_lower;
        return $data->{$marks_key};

        //dd($data->em_internal_marks_examiner);
       /*  if ($marks_type === 'mark_internal') {
            if ($evaluator_type === 'EXAMINER') {
                return $data->em_internal_marks_examiner;
            }
        }

        if ($marks_type === 'mark_external') {
            if ($evaluator_type === 'EXAMINER') {
                return $data->em_external_marks_examiner;
            }

            if ($evaluator_type === 'SCRUTINIZER') {
                return $data->em_external_marks_scrutinizer;
            }
        } */
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

    public function submit(Request $request)
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

        if($evaluator_type === "COLLEGE"){
            $evaluator_type = 'EXAMINER';
        }
        else{
            $evaluator_type = $other['evaluator_type'];
        }

        $type           = explode('_', $mark_type)[1];
        $evaluator      = strtolower($evaluator_type);

        try {
            DB::beginTransaction();
            $table_name = $part_sem == 'Part_I' ? 'pharmacy_exam_marks_pone' : 'pharmacy_exam_marks_ptwo';
            foreach ($request->student_data as $student) {
                $reg_no     = $student['reg_no'];
                $roll_no    = $student['roll_no'];
                $marks      = $student['marks'];
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
                    "em_{$type}_marks_{$evaluator}" => $marks,
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
                'message' => 'Marks ' .$action. ' Successfully',
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function hoeList(Request $request)
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
        /* return response()->json([
                'error' => true,
                'message' => 'Masking is not available',
                'data'      =>  $masking_status
            ]); */


       /*  if($masking_status ==0){
            return response()->json([
                'error' => true,
                'message' => 'Masking is not available'
            ]);
        }   */


        if($evaluator_type === "COLLEGE"){
            $evaluator_type = 'EXAMINER';
        }
        else{
            $evaluator_type = $request->evaluator_type;

        }
        $type           = explode('_', $marks_type)[1];
        $ev_type = strtolower($evaluator_type);
        // check attendance final submit

        $table_name = $part_sem == 'Part_I' ? 'exam_attendance_pone' : 'exam_attendance_ptwo';
        $marks_tbl = $part_sem == 'Part_I' ? 'pharmacy_exam_marks_pone' : 'pharmacy_exam_marks_ptwo';

        $subject_marks = DB::table('pharmacy_subjects_master')
            ->where('general_code', $subject)
            #->where('syllabus', $session_year )
            ->where('semester', $part_sem)
            ->where('is_active', 1)
            ->first();

        $max_marks = $subject_marks->{"subject_{$type}_total_marks"};

        $list = DB::table($table_name)
                ->join('pharmacy_register_student_final', 's_appl_form_num', '=', 'ea_form_number')
                ->leftJoin($marks_tbl, function ($join) use ($subject, $part_sem, $exam_year) {
                    $join->on('em_roll_number', '=', 'ea_roll_number')
                        ->where('em_subject_code', '=', $subject)
                        ->where('em_part_sem', '=', $part_sem)
                        ->where('em_exam_year', '=', $exam_year);
                })
                ->where('ea_exam_year', $exam_year)
                ->where('ea_part_sem', $part_sem)
                ->where('ea_subject_code', $subject)
                ->where('ea_inst_code', $inst_code)
                ->whereRaw("(ea_is_final_submit::json->>'is_{$type}')::boolean = true")
                ->get();

        $marksCount = DB::table($marks_tbl)
                    ->where('em_subject_code', $subject)
                    ->where('em_part_sem', $part_sem)
                    ->where('em_exam_year', $exam_year)
                    ->whereNotNull("em_{$type}_marks_{$ev_type}")
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
            $hoe_marks  =     $marks;
         #   $final_marks   = !is_null($marks) ? $marks : ($is_absent ? 'AB' : ($is_ra ? 'RA' : null));

            $marks_examiner = $this->getMarks($item, $marks_type,'EXAMINER');
            $marks_scrutinizer = $this->getMarks($item, $marks_type,'SCRUTINIZER');

            $examener_save_status   = $item->is_final_examiner;
            $is_final_scrutinizer   =   $item->is_final_scrutinizer;
           /*  if( $evaluator_type=='SCRUTINIZER'){
                $marks_examiner = $this->getMarks($item, $marks_type,'EXAMINER');
                //for checking exameaner marks entry count
                if(  !is_null($marks_examiner)   ){
                    $marks_enty_candidate++;
                }
                //first time SCRUTINIZER marks set by  examiner
                if(is_null($marks)){
                    $final_marks = $marks_examiner;
                }


            } */

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

                'is_final'=> json_decode($item->{"is_final_$evaluator"}, true),
                "marks"=> $marks,
                "hoe_marks"   => $hoe_marks ,
                "eve_type"  => $evaluator_type,
                "scrutinizer_marks" => $marks_scrutinizer,
                "examiner_marks"    =>  $marks_examiner,
                "max_marks"=> $max_marks,
                "masking_active"    => ($masking_status >0 && $marks_type =='mark_external') ? true: false,
                "mask_code"         => $item->ea_masking_code,
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

        //all success Scenario
        return response()->json([
            'error' => false,
            'students' => $list,
            'max_marks' => $max_marks,
            'marks_entry' => $marksCount,
            'message' => 'No Data available'
        ], 200);
    }


}
