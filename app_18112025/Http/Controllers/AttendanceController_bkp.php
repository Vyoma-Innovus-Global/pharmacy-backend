<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Attendancepone;
use App\Models\Rollnomodel;
use App\Models\Examcenter;

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
        return $students;

    } catch (\Exception $e) {
        Log::error('Error in getcenterType: ' . $e->getMessage());

        return 0;
    }
}
 public function getStudentsCountByPaper($enrl_type, $exam_year, $part_sem,  $exam_center)
    {
    try {
        $ar_tbl = "pharmacy_register_student_final";
        $rn_tbl = "pharmacy_roll_no";
        
        // Start building query
        $query = DB::table($rn_tbl)
            ->select('roll', 'number')
            ->join($ar_tbl, "$rn_tbl.form_no", '=', "$ar_tbl.s_appl_form_num")
             ->where('enrl_type', $enrl_type)
            ->where('exam_year', $exam_year)
            ->where('part_sem', $part_sem)
            ->where('inst_code', $exam_center);

        // Execute and count
        $students = $query->get();
        return $students->count();

    } catch (\Exception $e) {
        Log::error('Error in getStudentsCountByPaper: ' . $e->getMessage());

        return 0;
    }
}
    
    //Room alocation list
    public function roomAllotmentList(Request $request)
    {
        try{
            $ea_tbl = '';

           $enrl_type   =   $request->enrl_type;
           $part_sem    =   $request->part_sem; 
           $subject     =   $request->subject; 
           $exam_year   =   $request->exam_year; 
           $exam_center =   $request->exam_center;

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
                                        ['ea_student_type', '=', $enrl_type],
                                        ['ea_exam_year', '=', $exam_year],
                                        ['ea_part_sem', '=', $part_sem],
                                        ['ea_center_code', '=', $exam_center],
                                        ['ea_subject_code', '=', $subject]
                                    ])
                                    ->groupBy('ea_inst_code', 'ea_room_code', 'ea_center_code', 'ea_center_type', 'ea_part_sem', 'ea_exam_year', 'ea_subject_code')
                                    ->get();


           // dd($results);
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
            $students_count = $this->getStudentsCountByPaper($enrl_type, $exam_year, $part_sem,  $exam_center
            );
            // Call internal method
            $centerType = $this->getcenterType($exam_year, $part_sem,  $exam_center);

            return [
                'students' => $students,
                'totalCount' => $students_count,
                'centerType' => $centerType->center_type,
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
          
            $allotted = Attendancepone::where('ea_inst_code',$inst_code)
                       ->where('ea_exam_year',$exam_year)
                       ->where('ea_subject_code',$paper_code)
                       ->where('ea_part_sem', $part_sem);
                       
            $allottedCount=$allotted->count();
            $selectFormNo=$allotted->pluck('ea_form_number')->toArray();

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
            
            
           
            $ea_subject_id = DB::table('pharmacy_subjects_master')
            ->where('general_code', $paper_code)
            ->value('subject_id');
            $notAllottedCount=$totalNoOf - $allottedCount;

           
            if ($notAllottedCount >= $noOfStudent) {
              $insertedCount = 0;
              $failedEntries = [];
                foreach($insetData as $getData){
                    
                    $data = [
                        'ea_inst_code'   => $inst_code,
                        'ea_center_code' => $getData->center_code, 
                        'ea_center_type' => $getData->center_type,
                        'ea_room_code'   => $room_no,
                        'ea_part_sem'    => $part_sem,
                        'ea_exam_year'   => $exam_year,
                        'ea_subject_code'=> $paper_code,
                        'ea_form_number' => $getData->form_no,
                        'ea_roll_number' => $getData->roll.$getData->no_prefix.$getData->number,
                        'ea_subject_id'  => $ea_subject_id 
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
                'allottedCount' => $allottedCount,
                'totalNoOf' => $totalNoOf,
                'notAllottedCount' => $notAllottedCount,
                'inserted_count' => $insertedCount,
                'failed_entries' => $failedEntries,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }

    }
    
}
