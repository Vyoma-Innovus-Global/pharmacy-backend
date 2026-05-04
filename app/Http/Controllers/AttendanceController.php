<?php
namespace App\Http\Controllers;
use App\Models\MarksEntryPone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Attendancepone;
use App\Models\Rollnomodel;
use App\Models\Examcenter;
use App\Models\Registerstudent;
use App\Http\Resources\StudentAttendenceResource;

class AttendanceController extends Controller
{
    
    public function getcenterType($exam_year, $part_sem,  $exam_center)
    {
    try {
        $ar_tbl = "pharmacy_exam_center";
        
        // Start building query
        $query = DB::table($ar_tbl)
            ->select('center_type','part_sem','exam_year')
            
            ->where('exam_year', $exam_year)
            ->where('part_sem', $part_sem)
            ->where('inst_code', $exam_center)
            ->groupBy('center_type','exam_year','part_sem');
        // Execute and count
        $students = $query->first();
        if (!$students) {

            $query2 = DB::table($ar_tbl)
            ->select('center_type','part_sem','exam_year')          
            ->where('exam_year', $exam_year)
            ->where('part_sem', $part_sem)
            ->where('center_code', $exam_center)
            ->groupBy('center_type','exam_year','part_sem')->first();
            return $query2;

        } else{
          return $students;
        }
        

    } catch (\Exception $e) {
        Log::error('Error in getcenterType: ' . $e->getMessage());

        return 0;
    }
}
 public function getStudentsCountByPaper(  $exam_year, $part_sem,  $exam_center)
    {
        try {
            $ar_tbl = "pharmacy_register_student_final";
            $rn_tbl = "pharmacy_roll_no";
            
       
            $query = DB::table($rn_tbl)
                ->select('roll', 'number')
                ->join($ar_tbl, "$rn_tbl.form_no", '=', "$ar_tbl.s_appl_form_num")
                //->where('enrl_type', $enrl_type)
                ->where('exam_year', $exam_year)
                ->where('part_sem', $part_sem)
                ->where('inst_code', $exam_center);

            
            $students = $query->get();
            return $students->count();

        } catch (\Exception $e) {
            Log::error('Error in getStudentsCountByPaper: ' . $e->getMessage());

            return 0;
        }
    }
    
  
    public function roomAllotmentList(Request $request)
    {
        try{
            $ea_tbl = '';
            #$enrl_type   =   $request->enrl_type;
            $part_sem    =   $request->part_sem; 
            $subject     =   $request->subject; 
            $exam_year   =   $request->exam_year; 
            $exam_center =   $request->exam_center;
            $exam_pi 	=   $request->exam_pi;
            $centerType  =   $request->center_type;

            if($part_sem == 'Part_I'){
                    $ea_tbl = 'exam_attendance_pone';
            }else{
                    $ea_tbl = 'exam_attendance_ptwo';
            }
            $results = Attendancepone::select(
                        'ea_inst_code',
                        'ea_center_code',
                        'ea_center_type',
                        'ea_room_code',
                        'ea_part_sem',
                        'ea_exam_year',
                        'ea_subject_code',
                        DB::raw('COUNT(ea_room_code) as student_count')
                    )
                    ->where([
                       // ['ea_student_type', '=', $enrl_type],
                        ['ea_exam_year', '=', $exam_year],
                        ['ea_part_sem', '=', $part_sem],
                        ['ea_center_code', '=', $exam_center],
                        ['ea_inst_code', '=', $exam_pi],
                        ['ea_subject_code', '=', $subject]
                    ])
                    ->groupBy('ea_inst_code', 'ea_room_code', 'ea_center_code', 'ea_center_type', 'ea_part_sem', 'ea_exam_year', 'ea_subject_code')
                    ->get();


                $students = $results->map(function ($item) {
                    return [
                        'cntr_code' => $item->ea_center_code,
                        'subj_code' => $item->ea_subject_code,
                        'part_sem' => $item->ea_part_sem,
                        'room_code' => $item->ea_room_code,
                        'student_count' => $item->student_count,
                        
                    ];
                });
                
                // Call internal method
                $students_count = $this->getStudentsCountByPaper(   $exam_year, $part_sem,  $exam_center);
                // Call internal method
                //$centerType = $this->getcenterType($exam_year, $part_sem,  $exam_center);
                if ($centerType == 'AWAY') {
                    $allstudent = DB::table('pharmacy_roll_no')
						->join('pharmacy_enrollment', 'enrl_form_num', '=','form_no')
                        ->where('inst_code', $exam_pi) 
                        ->where('part_sem', $part_sem)
						->whereRaw("
							EXISTS (
								SELECT 1
								FROM jsonb_array_elements(applied_paper::jsonb) AS elem
								WHERE elem->>'code' = '$subject'
							)
						")
                        ->where('exam_year', $exam_year);
                
                    $students_count = $allstudent->count();
               
            }

            return [
                'students' => $students,
                'totalCount' => $students_count,
                'centerType' => $centerType,
                'exam_center' => $exam_center
            ];


        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
        
    }
    

    public function roomAllotmentSubmit(Request $request)
    {
        try{
            $noOfStudent=$request->no_of_student;
            $room_no=$request->room_no;
            $part_sem=$request->part_sem;
            $enrl_type=$request->enrl_type;
            $exam_year=$request->exam_year;
            $inst_code=$request->inst_code;
            $paper_code=$request->paper_code;
            $checkCenter=$request->center_type;
            $center_code = '';
            if($checkCenter=='HOME') { 
                $center_code = $request->inst_code;
            }else{
                $center_code = $request->center_code;
            }
            
            if($checkCenter=='HOME') {     
            $allotted = Attendancepone::where('ea_inst_code',$inst_code)
                       ->where('ea_center_code',$center_code)
                       ->where('ea_exam_year',$exam_year)
                       ->where('ea_subject_code',$paper_code)
                       ->where('ea_part_sem', $part_sem);
            } else {
                 $allotted = Attendancepone::where('ea_center_code',$center_code)
                       ->where('ea_inst_code',$inst_code)
                       ->where('ea_exam_year',$exam_year)
                       ->where('ea_subject_code',$paper_code)
                       ->where('ea_part_sem', $part_sem);
            }
                       
            $allottedCount=$allotted->count();
            
            $selectFormNo=$allotted->pluck('ea_form_number')->toArray();
           
           
                       
            if($checkCenter=='HOME'){
                $baseQuery = Rollnomodel::where('pharmacy_roll_no.part_sem', $part_sem)
                ->where('pharmacy_roll_no.enrl_type', $enrl_type)
                ->where('pharmacy_roll_no.inst_code', $inst_code)
                ->where('pharmacy_roll_no.exam_year', $exam_year)
                ->whereNotIn('pharmacy_roll_no.form_no', $selectFormNo)
                ->join('pharmacy_exam_center', function ($join) {
                    $join->on('pharmacy_roll_no.inst_code', '=', 'pharmacy_exam_center.inst_code')
                        ->on('pharmacy_roll_no.part_sem', '=', 'pharmacy_exam_center.part_sem');
                })
                ->select(
                    'pharmacy_roll_no.*',
                    'pharmacy_exam_center.center_code',
                    'pharmacy_exam_center.center_type'
                );
                $allData = $baseQuery->get();
                $insetData = $baseQuery->limit($noOfStudent)->get();
                $totalNoOf=$baseQuery->count();
            } 
            else {
                $baseQuery = Rollnomodel::query()
                ->where('pharmacy_roll_no.inst_code', $inst_code)
                ->where('pharmacy_roll_no.part_sem', $part_sem)
                ->where('pharmacy_roll_no.exam_year', $exam_year)
                ->whereNotIn('pharmacy_roll_no.form_no', $selectFormNo)
                ->select(
                    'pharmacy_roll_no.*'
                );

                $allData = $baseQuery->get();
                $insetData = $baseQuery->limit($noOfStudent)->get();
                $totalNoOf=$baseQuery->count();
            }
           
           
            $ea_subject_id = DB::table('pharmacy_subjects_master')
            ->where('general_code', $paper_code)
            ->value('subject_id');

            $notAllottedCount=$totalNoOf;

            if ($notAllottedCount >= $noOfStudent) {
              $insertedCount = 0;
              $failedEntries = [];
                foreach($insetData as $getData){
                    
                    $data = [
                        'ea_inst_code'   => $getData->inst_code,
                        'ea_center_code' => $center_code, 
                        'ea_center_type' => $checkCenter,
                        'ea_room_code'   => $room_no,
                        'ea_part_sem'    => $part_sem,
                        'ea_exam_year'   => $exam_year,
                        'ea_subject_code'=> $paper_code,
                        'ea_form_number' => $getData->form_no,
                        'ea_roll_number' => $getData->roll.$getData->no_prefix.$getData->number,
                        'ea_subject_id'  => $ea_subject_id,
                        'ea_student_type'=> $getData->enrl_type,
                        'ea_is_present_external'=> 1,
                        'ea_is_present_internal'=> 1,

                        'ea_is_expel_external'=> 0,
                        'ea_is_expel_internal'=> 0,

                        'ea_is_ra_external'=> 0,
                        'ea_is_ra_internal'=> 0,

                        'ea_is_cop_external'=> 0,
                        'ea_is_cop_internal'=> 0
                    ];
                    $insert=Attendancepone::insert($data);
                    
                   if ($insert) {
                        $insertedCount++;
                    } else {
                        $failedEntries[] = $data['ea_form_number'] ?? 'unknown';
                    }
                }
                
            } else {
                return response()->json([
                    'error' => true,
                    'message' => "Cannot select more than total not-allotted students."
                ], 500);
            }

            return response()->json([
                'error' => false,
                'message'=> "Success"
               
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }

    }

    public function collegeWiseInstitute(Request $request)
    {
        try{
            $exam_year = $request->exam_year;
            $part_sem = $request->part_sem;
            $inst_code = $request->inst_code;

            $data = Examcenter::where('exam_year', $exam_year)
                    ->where('part_sem', $part_sem)
                    ->where('center_code', $inst_code)
                    ->first();

            if (!$data) {
                return response()->json([
                    'error' => true,
                    'message' => 'No exam center found.'
                ], 404);
            }

                
            if ($data->center_type == 'AWAY') {
                $institute = Examcenter::where('exam_year', $exam_year)
                    ->where('part_sem', $part_sem)
                    ->where('center_type', 'AWAY')
					->where('center_code', $inst_code)
                    ->pluck('inst_code') 
                    ->toArray();
            } else {
                $institute = [$inst_code];
            }

            return response()->json([
                'error' => false,
                'center_type' => $data->center_type,
                'inst_code' => $institute
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function instituteWiseCenter(Request $request)
    {
        try{
            $exam_year = $request->exam_year;
            $part_sem = $request->part_sem;
            $inst_code = $request->inst_code;

            $data = Examcenter::where('exam_year', $exam_year)
                    ->where('part_sem', $part_sem)
                    ->where('inst_code', $inst_code)
                    ->first();

            if (!$data) {
                return response()->json([
                    'error' => false,
                    'message' => 'No exam center found.'
                ], 200);
            }

                
            if ($data->center_type == 'AWAY') {
                $institute = Examcenter::where('exam_year', $exam_year)
                            ->where('part_sem', $part_sem)
                            ->where('center_type', 'AWAY')
							->where('inst_code',$inst_code )
                            ->groupBy('center_code')
                            ->select('center_code')
                            ->get()
                            ->pluck('center_code')
                            ->toArray();

            } else {
                $institute = [$inst_code];
            }

            return response()->json([
                'error' => false,
                'center_type' => $data->center_type,
                'center_code' => $institute
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function listAttendance(Request $request)
    {
        $type = $request->student_type;
        $part_sem = $request->part_sem;
        $exam_year = $request->exam_year;
        $subject_code = $request->subject_code;
        $mark_type = $request->mark_type;
        $center_code= $request->center_code;
        $center_inst_code= $request->center_inst_code;
        $user_role_id= $request->user_role_id;

        $mark_type = strtolower(explode('_', $mark_type)[1]);
        if ($part_sem == 'Part_I') {
            if($user_role_id == '3'){
                $attendances = Attendancepone::where([
                    //'ea_student_type' => $type,
                    'ea_exam_year'    => $exam_year,
                    'ea_part_sem'     => $part_sem,
                    'ea_subject_code' => $subject_code,
                ])
                ->when(
                    $mark_type != 'internal',
                    function ($q) use ($center_code, $center_inst_code) {
                        $q->where('ea_center_code', $center_code)
                        ->where('ea_inst_code', $center_inst_code);
                    }
                )
                ->when(
                    $mark_type == 'internal',
                    fn($q) => $q->where('ea_inst_code', $center_code)
                )
                ->when(
                    in_array($type, ['REGULAR', 'READMISSION']),
                    fn($q) => $q->whereIn('ea_student_type', ['REGULAR', 'READMISSION']),
                    fn($q) => $q->where('ea_student_type', $type)
                );
            }
            
            if($user_role_id == '4'){
                $attendances = Attendancepone::where([
                'ea_exam_year' => $exam_year,
                'ea_part_sem' => $part_sem,
                'ea_subject_code' => $subject_code,
                'ea_inst_code'=> $center_inst_code,
                ])->when(
                    in_array($type, ['REGULAR', 'READMISSION']),
                    fn($q) => $q->whereIn('ea_student_type', ['REGULAR', 'READMISSION']),
                    fn($q) => $q->where('ea_student_type', $type)
                );
            }
            
                //dd($attendances->toSql(), $attendances->getBindings());
        }
        else{
            $attendances = Attendanceptwo::where([
                'ea_student_type' => $type,
                'ea_exam_year' => $exam_year,
                'ea_part_sem' => $part_sem,
                'ea_subject_code' => $subject_code,
                'ea_center_code'=> $center_code,
                'ea_inst_code'=> $center_inst_code,
            ]);
        }
        if (!$attendances->count()) {
            return response()->json([
                'error' => true,
                'message' => 'No Student Found'
            ]);
        }

        $present_count = $absent_count = $ra_count = 0;
        

        $present_count = $attendances->clone()->where("ea_is_present_{$mark_type}", 1)->count();
        $absent_count = $attendances->clone()->where("ea_is_absent_{$mark_type}", 1)->count();
       
        $ra_count = $attendances->clone()->where("ea_is_ra_{$mark_type}", 1)->count();

        $attendances = $attendances->clone()
            ->with('student')
            ->orderBy('ea_roll_number', 'ASC')
            ->get();
        
        return response()->json([
            'error' => false,
            'message' => 'Data found',
            'student' => $this->StudentAttendenceData($attendances, $subject_code),
            'enrolled_count' => sizeof($attendances),
            'present_count' => $present_count,
            'absent_count' => $absent_count,
            'ra_count' => $ra_count
        ]);
    }
    private function StudentAttendenceData($attendances, $subject_code)
    {
     
        $mark_type = request()->mark_type; 
        $type = match ($mark_type) {
            'mark_internal' => 'internal',
            'mark_external' => 'external',
            default => null,
        };

        return $attendances->map(function ($item) use ($type, $subject_code) {
            $student = $item->student; 
            return [
                'id' => $student->s_id,
                'student_form_no' => $student->s_appl_form_num,
                'student_reg_no' => $student->s_appl_reg_no,
                'roll_no' => $item->ea_roll_number,
                'center_code' => $item->ea_center_code,
                'inst_code' => $item->ea_inst_code,
                'paper_code' =>  $subject_code,
                'student_name' => $student->s_candidate_name,
                'is_present'=> $item->{'ea_is_present_'.$type} ? true: false,
                'is_absent'=> $item->{'ea_is_absent_'.$type} ? true: false,
                'is_ra' => $item->{'ea_is_ra_'.$type} ? true: false,
                'is_final' => json_decode($item->ea_is_final_submit),
            ];
        });
    }

    //Attendance Update
    public function updateAttendance(Request $request)
    {
        try {
            $request->validate([
                'student_type' => ['required'],
                'form_number' => ['required'],
                'exam_year' => ['required'],
                'subject_code' => ['required'],
                'is_present' => ['required'],
                'is_absent' => ['required'],
                'is_ra' => ['required'],
                'mark_type' => ['required'],
                'part_sem' => ['required'],
                'center_code' => ['required'],
                
            ]);
            
            $type = $request->student_type;
            $part_sem = $request->part_sem;
            $form_number = $request->form_number;
            $exam_year = $request->exam_year;
            $subject_code = $request->subject_code;
            $center_code = $request->center_code;
            $inst_code = $request->inst_code;
            $mark_type = $request->mark_type;


            $is_present = $request->is_present;
            $is_absent = $request->is_absent;
            $is_ra = $request->is_ra;

             if (!$is_present && !$is_absent && !$is_ra) {
                $is_present = true;
            }
            
            $mark_type = strtolower(explode('_', $request->mark_type)[1]);
 
            if($part_sem == 'Part_I'){
                    $attendence = Attendancepone::where([
                    //'ea_student_type' => $type,
                    'ea_exam_year' => $exam_year,
                    'ea_form_number' => $form_number,
                    'ea_part_sem' => $part_sem,
                    'ea_subject_code' => $subject_code
                ]);
                }else{
                $attendence = Attendanceptwo::where([
                    'ea_student_type' => $type,
                    'exam_year' => $exam_year,
                    'ea_form_number' => $form_number,
                    'ea_part_sem' => $part_sem,
                    'ea_subject_code' => $subject_code
                    ]);
                }
            DB::beginTransaction();
            $attendence->update([
                "ea_is_present_{$mark_type}" => $is_present,
                "ea_is_ra_{$mark_type}" => $is_ra,
                "ea_is_absent_{$mark_type}" => $is_absent
            ]);

            if ($is_absent) {
                $att_for = 'ABSENT';
            } elseif ($is_ra) {
                $att_for = 'RA';
            }else{
               $att_for = 'PRESENT';
            }

            auditTrail($form_number, "{$part_sem} {$exam_year} Student Attendence Updated for Form .No {$request->form_number} for {$request->mark_type} {$request->subject_code} to {$att_for}");

            DB::commit();

            return response()->json([
                'error'         =>  false,
                'message'       =>  "Attendence Submitted as {$att_for}",
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error'     =>  true,
                'message'   =>  $e->getMessage()
            ], 400);
        }
    }

    public function finalSubmitAttendance(Request $request)
    {
        try {
            DB::beginTransaction();
             
            $attendances = $request->all();
            $update_count   =   0;
            foreach ($attendances as $attendance) {
               //dd($attendance["student_form_no"]);
                $part_sem = $attendance['part_sem'];
               
                if($part_sem == 'Part_I'){
                    $att = Attendancepone::where([
                    //'ea_student_type' =>$attendance['student_type'],
                    'ea_exam_year' => $attendance['exam_year'],
                    'ea_part_sem' => $attendance['part_sem'],
                    'ea_subject_code' => $attendance['subject_code'],
                    'ea_form_number' => $attendance['student_form_no'],
                    'ea_inst_code' => $attendance['center_code']
                    ]);
                }else{
                    $att = Attendanceptwo::where([
                    //'ea_student_type' =>$attendance['student_type'],
                    'ea_exam_year' => $attendance['exam_year'],
                    'ea_part_sem' => $attendance['part_sem'],
                    'ea_subject_code' => $attendance['subject_code'],
                    'ea_form_number' => $attendance['student_form_no'],
                    'ea_inst_code' => $attendance['center_code']
                    ]);
                }
                          

               $update_stat =    $att->update([
                    'ea_is_final_submit' => json_encode($attendance['is_final']),
                    //'ea_final_submit_by' => 
                ]);
                 $update_count+= $update_stat;

                auditTrail($attendance['student_form_no'], 'Student Attendance Updated Finally');
            }

            

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => 'Attendance Updated Finally',
                'count' => $att->count(),
                'update'=>$part_sem 
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function attendanceUnlock(Request $request)
    {
        try {
            DB::beginTransaction();
            $is_lock = $request->is_lock;
            $form_number = $request->form_number;
            $roll_number = $request->roll_number;
            $exam_year = $request->exam_year;
            $part_sem = $request->part_sem;
            $student_type = $request->student_type;
            $subject_code= $request->subject_code;
            $inst_code= $request->center_code;
            $mark_type = strtolower(explode('_', $request->mark_type)[1]);
            //
            if($part_sem == 'Part_I'){
                $attendence = Attendancepone::where([
                'ea_form_number' => $form_number,
                'ea_roll_number' => $roll_number,
                'ea_student_type' => $student_type,
                'ea_exam_year' => $exam_year,
                'ea_part_sem' => $part_sem,
                'ea_subject_code' => $subject_code,
                ]);
            }else{
                $attendence = Attendanceptwo::where([
                'ea_form_number' => $form_number,
                'ea_roll_number' => $roll_number,
                'ea_student_type' => $student_type,
                'ea_exam_year' => $exam_year,
                'ea_part_sem' => $part_sem,
                'ea_subject_code' => $subject_code,
                ]);
            }
            
            $attendence = $attendence->first();
            if ($attendence) {
                $attendence->update([
                    'ea_is_final_submit' => json_encode($request->is_lock),
                    'ea_modified_on' => now(),
                    'ea_modified_by' => ''
                ]);

               $this->clearMarksOnUnlock($exam_year, $part_sem, $mark_type, $roll_number, $inst_code, $subject_code);

                auditTrail($form_number, "Student Form Number: {$form_number} Attendence {$request->lock_type}");

                DB::commit();

                return response()->json([
                    'error' => false,
                    'message' => "Attendance {$request->lock_type} Successfully",
                ]);
            } else {
                return response()->json([
                    'error'     =>  true,
                    'message'   =>   "Update Attendence First"
                ], 404);
            }
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error'     =>  true,
                'message'   =>  $e->getMessage()
            ], 400);
        }
    }

    private function clearMarksOnUnlock($exam_year, $part_sem, $mark_type, $roll_number, $inst_code, $subject_code)
    {
        if (in_array($mark_type, ['internal', 'external'])) {
            if($part_sem == 'Part_I'){
                $marks = MarksEntryPone::where([
                    'em_exam_year' => $exam_year,
                    'em_inst_code' => $inst_code,
                    'em_roll_number' => $roll_number,
                    'em_part_sem' => $part_sem,
                    'em_subject_code' => $subject_code,
                 ])->first();
             }else{
                $marks = MarksEntryPtwo::where([
                    'em_exam_year' => $exam_year,
                    'em_inst_code' => $inst_code,
                    'em_roll_number' => $roll_number,
                    'em_part_sem' => $part_sem,
                    'em_subject_code' => $subject_code,
                 ])->first();
             }
            
            if ($marks) {
                $unlock_col_examiner = $marks["is_final_examiner"];
                $unlock_col_scrutiniser = $marks["is_final_scrutinizer"];
                $unlock_col_head_ex = $marks["is_final_head_examiner"];
                
                $is_unlock_ex = null;
                $is_unlock_sc = null;
                $is_unlock_he = null;
                if (!is_null($unlock_col_examiner)) {
                        $is_unlock_ex = json_decode($unlock_col_examiner, true);
                        $is_unlock_ex["is_{$mark_type}"] = false;
                }
                if (!is_null($unlock_col_scrutiniser)) {
                        $is_unlock_sc = json_decode($unlock_col_scrutiniser, true);
                        $is_unlock_sc["is_{$mark_type}"] = false;
                }
                if (!is_null($unlock_col_head_ex)) {
                        $is_unlock_he = json_decode($unlock_col_head_ex, true);
                        $is_unlock_he["is_{$mark_type}"] = false;
                }
                if($mark_type === 'external' ){
                    $marks->update([
                        "em_{$mark_type}_marks_examiner"=> null,
                        "em_{$mark_type}_marks_scrutinizer"=> null,
                        "em_{$mark_type}_marks_head_examiner"=> null,
                        "is_final_examiner"=> json_encode($is_unlock_ex, true),
                        "is_final_scrutinizer"=> json_encode($is_unlock_sc, true),
                        "is_final_head_examiner"=> json_encode($is_unlock_he, true)
                    ]);
                }
                else{
                    $marks->update([
                        "em_{$mark_type}_marks_examiner"=> null,
                        "is_final_examiner"=> json_encode($is_unlock_ex, true),
                        "is_final_scrutinizer"=> json_encode($is_unlock_sc, true),
                        "is_final_head_examiner"=> json_encode($is_unlock_he, true)
                    ]);
                }
                

            }
        }
    }


    public function attendanceunlockAll(Request $request)
    {
        try {
            DB::beginTransaction();
            $is_lock = $request->is_lock;
            $form_numbers = $request->student_form_no;
            $exam_year = $request->exam_year;
            $part_sem = $request->part_sem;
            $student_type = $request->student_type;
            $subject_code= $request->subject_code;
            $mark_type = strtolower(explode('_', $request->mark_type)[1]);

            foreach ($form_numbers as $form_number) {
                if($part_sem == 'Part_I'){
                    $attendence = Attendancepone::where([
                    'ea_form_number' => $form_number,
                    //'ea_student_type' => $student_type,
                    'ea_exam_year' => $exam_year,
                    'ea_part_sem' => $part_sem,
                    'ea_subject_code' => $subject_code,
                
                
                    ]);
                }else{
                    $attendence = Attendanceptwo::where([
                    'ea_form_number' => $form_number,
                    'ea_student_type' => $student_type,
                    'ea_exam_year' => $exam_year,
                    'ea_part_sem' => $part_sem,
                    'ea_subject_code' => $subject_code,
                    ]);
                }
                
                $attendence = $attendence->first();
                //dd($request->is_lock);
                if ($attendence) {
                    
                    $attendence->update([
                        'ea_is_final_submit' => json_encode($request->is_lock),
                        
                    ]);

                    
                    auditTrail($form_number, "Student Attendence {$request->lock_type}");
                    
                }
            }

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => "Attendance Unlocked Successfully",
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error'     =>  true,
                'message'   =>  $e->getMessage()
            ], 400);
        }
    }
    public function listCentercode(Request $request)
    {
        try {
            $u_role_id = $request->u_role_id;
            $part_sem = $request->part_sem;
            $exam_year = $request->exam_year;
            $subj_code = $request->subject_code;
            $mark_type = $request->mark_type;
            $mark_type = strtolower(explode('_', $mark_type)[1]);

            $subjectList = DB::table('pharmacy_subjects_master')
                ->where('semester', $part_sem)
                ->where('general_code', $subj_code)
                ->where('dept', 'PHARM')
                ->select('subject_type')
                ->first();

            if($u_role_id==3){
                $away_center=$request->away_center;
                $sub_type = $subjectList->subject_type;

                if($mark_type =='internal'){
                   $centerList=DB::table('pharmacy_exam_center')
                                ->join('institute_master','pharmacy_exam_center.inst_code','institute_master.i_code')
                                ->where('center_code',$away_center)
                                ->where('inst_code',$away_center)
                                ->where('exam_year',$exam_year)
                                ->where('part_sem',$part_sem)
                                ->select('inst_code','i_name')->get();  
                }else{
                    if($sub_type=='Theoretical'){
                        $centerList=DB::table('pharmacy_exam_center')
                                ->join('institute_master','pharmacy_exam_center.inst_code','institute_master.i_code')
                                ->where('center_code',$away_center)
                                ->where('exam_year',$exam_year)
                                ->where('part_sem',$part_sem)
                                ->select('inst_code','i_name')->get();
                    }else{
                        $centerList=DB::table('pharmacy_exam_center')
                                ->join('institute_master','pharmacy_exam_center.inst_code','institute_master.i_code')
                                ->where('inst_code',$away_center)
                                ->where('exam_year',$exam_year)
                                ->where('part_sem',$part_sem)
                                ->select('inst_code','i_name')->get();
                    }
                       
                }
                    if ($centerList->isEmpty()) {
                        $centerList=DB::table('pharmacy_exam_center')
                            ->join('institute_master','pharmacy_exam_center.inst_code','institute_master.i_code')
                            ->where('inst_code',$away_center)
                            ->where('exam_year',$exam_year)
                            ->where('part_sem',$part_sem)
                            ->select('inst_code','i_name')->get();  
                    }            
                   
               
            } else {
                $centerList=DB::table('pharmacy_exam_center')
                    ->join('institute_master','pharmacy_exam_center.inst_code','institute_master.i_code')
                    ->where('exam_year',$exam_year)
                    ->where('part_sem',$part_sem)
                    ->select('inst_code','i_name')->get();
            }
            return response()->json([
                'error' => false,
                'centerList' => $centerList,
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    
}
